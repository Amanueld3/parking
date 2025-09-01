<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'init_response' => 'array',
        'verify_response' => 'array',
        'verified_at' => 'datetime',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    protected static function booted(): void
    {
        static::creating(function (Payment $payment) {
            if (empty($payment->ticket_no)) {
                $payment->ticket_no = self::generateTicketNo();
            }
        });
    }

    /**
     * Generate an incremental, unique ticket number in the form QPT########
     */
    public static function generateTicketNo(): string
    {
        $prefix = 'QPT';
        // Use the next integer based on current max id-like sequence in ticket_no
        $last = static::query()
            ->select('ticket_no')
            ->where('ticket_no', 'like', $prefix . '%')
            ->orderByDesc('ticket_no')
            ->first();

        $next = 1;
        if ($last && preg_match('/^' . $prefix . '(\d{8})$/', $last->ticket_no, $m)) {
            $next = (int) $m[1] + 1;
        }

        return sprintf('%s%08d', $prefix, $next);
    }
}
