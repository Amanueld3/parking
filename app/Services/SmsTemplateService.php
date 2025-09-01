<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Vehicle;
use Carbon\Carbon;

class SmsTemplateService
{
    /**
     * Format a check-in SMS message using a consistent, branded template.
     */
    public static function formatCheckin(Vehicle $vehicle): string
    {
        $brand = (string) config('parking.brand', 'Qelale');
        $timeFormat = (string) config('parking.time_format', 'M d, Y h:i A');

        $checkIn = self::formatTime($vehicle->checkin_time, $timeFormat);
        $place = trim((string) ($vehicle->place?->name ?? ''));
        $plate = (string) ($vehicle->plate_number ?? '');

        $lines = [];
        $lines[] = 'Parking Check-In';
        $lines[] = '';

        if ($checkIn !== null) {
            $lines[] = 'Check-In: ' . $checkIn;
        }
        if ($place !== '') {
            $lines[] = 'Location: ' . $place;
        }
        if ($plate !== '') {
            $lines[] = 'Plate Number: ' . $plate;
        }
        // blank line before signature
        $lines[] = '';
        $lines[] = 'Thanks, ' . $brand;

        return implode("\n", $lines);
    }

    /**
     * Format a check-out SMS message using a consistent, branded template.
     * Optional $payment enriches the message with ticket number and paid amount.
     */
    public static function formatCheckout(Vehicle $vehicle, ?Payment $payment = null, ?string $rateText = null): string
    {
        $brand = (string) config('parking.brand', 'Qelale');
        $timeFormat = (string) config('parking.time_format', 'M d, Y h:i A');
        $rate = $rateText ?? (string) config('parking.rate_text', '10.00 ETB / 30 minutes');

        $checkIn = self::formatTime($vehicle->checkin_time, $timeFormat);
        $checkOut = self::formatTime($vehicle->checkout_time, $timeFormat);
        $place = trim((string) ($vehicle->place?->name ?? ''));
        $plate = (string) ($vehicle->plate_number ?? '');

        $duration = null;
        if ($vehicle->checkin_time && $vehicle->checkout_time) {
            $start = Carbon::parse($vehicle->checkin_time);
            $end = Carbon::parse($vehicle->checkout_time);
            $totalSeconds = max(0, $end->getTimestamp() - $start->getTimestamp());

            if ($totalSeconds < 60) {
                $duration = 'less than 1 minute';
            } elseif ($totalSeconds < 3600) {
                $minutes = intdiv($totalSeconds, 60);
                $duration = $minutes . ' minute' . ($minutes === 1 ? '' : 's');
            } elseif ($totalSeconds < 86400) {
                $hours = intdiv($totalSeconds, 3600);
                $minutes = intdiv($totalSeconds % 3600, 60);
                $duration = $hours . ' hour' . ($hours === 1 ? '' : 's');
                if ($minutes > 0) {
                    $duration .= ' ' . $minutes . ' minute' . ($minutes === 1 ? '' : 's');
                }
            } else {
                $days = intdiv($totalSeconds, 86400);
                $hours = intdiv(($totalSeconds % 86400), 3600);
                $duration = $days . ' day' . ($days === 1 ? '' : 's');
                if ($hours > 0) {
                    $duration .= ' ' . $hours . ' hour' . ($hours === 1 ? '' : 's');
                }
            }
        }

        $lines = [];
        $lines[] = 'Parking Check-Out';
        $lines[] = '';
        if ($payment && ($payment->ticket_no ?? null)) {
            $lines[] = 'Ticket Number: ' . $payment->ticket_no;
        }
        if ($checkIn !== null) {
            $lines[] = 'Check-In: ' . $checkIn;
        }
        if ($checkOut !== null) {
            $lines[] = 'Check-Out: ' . $checkOut;
        }
        if ($duration !== null) {
            $lines[] = 'Duration: ' . $duration;
        }
        if ($place !== '') {
            $lines[] = 'Location: ' . $place;
        }
        if ($rate !== '') {
            $lines[] = 'Price Rate: ' . $rate;
        }
        if ($plate !== '') {
            $lines[] = 'Plate Number: ' . $plate;
        }
        if ($payment && $payment->amount) {
            $amt = number_format((float) $payment->amount, 2, '.', '');
            $cur = $payment->currency ?: 'ETB';
            $lines[] = 'Payment: ' . $amt . ' ' . $cur;
        }

        // blank line before signature
        $lines[] = '';
        $lines[] = 'Thanks, ' . $brand;

        return implode("\n", $lines);
    }

    private static function formatTime($dt, string $format): ?string
    {
        if (! $dt) {
            return null;
        }
        try {
            return Carbon::parse($dt)->format($format);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
