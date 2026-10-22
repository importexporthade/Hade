<?php

namespace App\Enums;

/**
 * The emails that remind the owner to answer a sheet, in the order they go out.
 */
enum ComplaintAlert: string
{
    case Filed = 'filed';
    case DueSoon = 'due_soon';
    case DueToday = 'due_today';
    case Overdue = 'overdue';

    /**
     * Days before the deadline when the "due soon" reminder goes out.
     */
    public const int DUE_SOON_DAYS = 3;

    public function comesAfter(?self $other): bool
    {
        if ($other === null) {
            return true;
        }

        $order = self::cases();

        return array_search($this, $order, true) > array_search($other, $order, true);
    }
}
