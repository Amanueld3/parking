<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Vehicle;
use App\Services\ChapaService;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PaymentController extends Controller
{
    public function start(Request $request, \App\Models\Vehicle $vehicle): RedirectResponse
    {
        if (! is_null($vehicle->checkout_time)) {
            return back()->with('error', 'This vehicle is already checked out.');
        }

        // Compute amount: 20 ETB per 30 minutes, minimum 20 ETB
        $blockMinutes = (int) config('parking.block_minutes', 30);
        $ratePerBlock = (float) config('parking.rate_per_30_min', 20);
        $minCharge = (float) config('parking.min_charge', 20);

        $now = Carbon::now();
        $checkIn = $vehicle->checkin_time ? Carbon::parse($vehicle->checkin_time) : null;
        $minutes = $checkIn ? max(0, $checkIn->diffInMinutes($now)) : 0;
        $blocks = max(1, (int) ceil($minutes / max(1, $blockMinutes)));
        $amount = max($minCharge, $blocks * $ratePerBlock);

        // Basic config checks
        if (empty(config('services.chapa.secret'))) {
            return back()->with('error', 'Payment gateway not configured. Please set CHAPA_SECRET.');
        }

        $txRef = ChapaService::generateTxRef('park');

        $payment = Payment::create([
            'vehicle_id' => $vehicle->id,
            'tx_ref' => $txRef,
            'amount' => $amount,
            'currency' => 'ETB',
            'status' => 'initialized',
        ]);

        $chapa = ChapaService::make();
        $email = (string) (config('services.chapa.customer_email') ?: 'no-reply@' . parse_url(config('app.url', 'http://localhost'), PHP_URL_HOST));
        $payload = [
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => 'ETB',
            'email' => $email,
            'first_name' => $vehicle->owner_name ?: 'Parking',
            'last_name' => $vehicle->plate_number,
            'phone_number' => $vehicle->owner_phone ? ('+251' . $vehicle->owner_phone) : null,
            'tx_ref' => $txRef,
            'callback_url' => route('payments.webhook'),
            'return_url' => route('payments.return', ['tx_ref' => $txRef]),
            'customization' => [
                'title' => 'Parking checkout',
                'description' => 'Payment for parking checkout of ' . $vehicle->plate_number,
            ],
            'meta' => [
                'vehicle_id' => $vehicle->id,
                'hide_receipt' => true,
            ],
        ];

        $resp = $chapa->initialize($payload);
        $payment->update([
            'init_response' => $resp['body'] ?? null,
            'status' => $resp['ok'] ? 'pending' : 'failed',
        ]);

        $checkoutUrl = $resp['body']['data']['checkout_url'] ?? null;
        if (! $resp['ok'] || ! $checkoutUrl) {
            Log::error('Chapa initialize failed', [
                'status' => $resp['status'] ?? null,
                'body' => $resp['body'] ?? null,
            ]);
            return back()->with('error', 'Unable to start payment.');
        }
        return redirect()->away($checkoutUrl);
    }

    public function webhook(Request $request)
    {
        // Chapa sends event with tx_ref; verify before marking success
        $txRef = $request->input('tx_ref') ?: ($request->input('data')['tx_ref'] ?? null);
        if (! $txRef) {
            return response()->json(['message' => 'Missing tx_ref'], 400);
        }
        $payment = Payment::where('tx_ref', $txRef)->first();
        if (! $payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        $chapa = ChapaService::make();
        $verification = $chapa->verify($txRef);
        $ok = $verification['ok'] && ($verification['body']['status'] ?? '') === 'success' && (($verification['body']['data']['status'] ?? '') === 'success');

        $payment->update([
            'verify_response' => $verification['body'] ?? null,
            'status' => $ok ? 'success' : 'failed',
            'verified_at' => $ok ? now() : null,
        ]);

        if ($ok) {
            // Only now mark checkout complete
            $vehicle = $payment->vehicle;
            if ($vehicle && is_null($vehicle->checkout_time)) {
                $vehicle->checkout_time = now();
                $vehicle->save();
            }
        }

        return response()->json(['ok' => $ok]);
    }

    public function return(Request $request)
    {
        $txRef = $request->query('tx_ref');
        if (! $txRef) {
            return redirect()->route('filament.pages.checkout')->with('error', 'Missing payment reference.');
        }

        $payment = Payment::where('tx_ref', $txRef)->first();
        if (! $payment) {
            return redirect()->route('filament.pages.checkout')->with('error', 'Payment not found.');
        }

        $chapa = ChapaService::make();
        $verification = $chapa->verify($txRef);
        $ok = $verification['ok'] && ($verification['body']['status'] ?? '') === 'success' && (($verification['body']['data']['status'] ?? '') === 'success');

        if ($ok) {
            $payment->update([
                'verify_response' => $verification['body'] ?? null,
                'status' => 'success',
                'verified_at' => $payment->verified_at ?: now(),
            ]);
            $vehicle = $payment->vehicle;
            if ($vehicle && is_null($vehicle->checkout_time)) {
                $vehicle->checkout_time = now();
                $vehicle->save();
            }

            $this->checkoutNotification($vehicle);
            return redirect()->route('filament.pages.checkout')->with('success', 'Payment successful and checkout completed.');
        }

        $payment->update([
            'verify_response' => $verification['body'] ?? null,
            'status' => 'failed',
        ]);
        return redirect()->route('filament.pages.checkout')->with('error', 'Payment failed or not verified.');
    }

    private function checkoutNotification($record)
    {
        // Find related payment (if any) to enrich SMS with ticket and amount
        $payment = Payment::where('vehicle_id', $record->id)
            ->orderByDesc('id')
            ->first();

        $message = \App\Services\SmsTemplateService::formatCheckout($record, $payment);

        $sender = new class {
            use \App\Traits\SendsSms;
            public function sendNow(string $phone, string $message): bool
            {
                return $this->sendSms($phone, $message);
            }
        };

        $sender->sendNow((string) ($record->owner_phone ?? ''), $message);

        $plateText = (string) ($record->plate_number ?? 'Vehicle');
        Notification::make()
            ->title("{$plateText} has been marked as checked out.")
            ->seconds(5)
            ->success()
            ->send();
    }
}
