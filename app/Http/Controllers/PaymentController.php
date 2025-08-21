<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Vehicle;
use App\Services\ChapaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function start(Request $request, \App\Models\Vehicle $vehicle): RedirectResponse
    {
        if (! is_null($vehicle->checkout_time)) {
            return back()->with('error', 'This vehicle is already checked out.');
        }

        // In a real app determine amount from pricing policy; placeholder 10 ETB
        $amount = (float) ($request->input('amount', 10));

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

        // Always verify again on return to be safe
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
            return redirect()->route('filament.pages.checkout')->with('success', 'Payment successful and checkout completed.');
        }

        $payment->update([
            'verify_response' => $verification['body'] ?? null,
            'status' => 'failed',
        ]);
        return redirect()->route('filament.pages.checkout')->with('error', 'Payment failed or not verified.');
    }
}
