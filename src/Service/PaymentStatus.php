<?php

declare(strict_types=1);

namespace App\Service;

/** Our own lifecycle for a payment, independent of the gateway's vocabulary. */
enum PaymentStatus: string
{
    case Initiated = 'initiated'; // created at Gate-E, customer has not tried to pay yet
    case Pending   = 'pending';   // customer returned without a successful or failed charge
    case Paid      = 'paid';
    case Failed    = 'failed';
    case Error     = 'error';     // Gate-E refused to create the payment

    public function isFinal(): bool
    {
        return $this === self::Paid || $this === self::Failed;
    }

    public function label(): string
    {
        return match ($this) {
            self::Initiated => 'Initiated',
            self::Pending   => 'Not completed',
            self::Paid      => 'Paid',
            self::Failed    => 'Failed',
            self::Error     => 'Error',
        };
    }

    /** Visual tone used by the status badge. */
    public function tone(): string
    {
        return match ($this) {
            self::Paid                    => 'positive',
            self::Failed, self::Error     => 'negative',
            self::Pending                 => 'waiting',
            self::Initiated               => 'neutral',
        };
    }
}
