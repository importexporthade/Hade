<?php

namespace App\Support;

class Price
{
    /**
     * Formats an amount in soles the way the store shows it: "S/ 76.90".
     */
    public static function format(float|string|null $amount): ?string
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        return 'S/ '.number_format((float) $amount, 2, '.', ',');
    }
}
