<?php
namespace App\Support;

use Illuminate\Support\Carbon;

class StringSupport
{
    public static function inisial(string $string, int $length = 3)
    {
        $words = explode(' ', $string);
        $initials = '';

        foreach ($words as $word) {
            $initials .= strtoupper(substr($word, 0, 1));
            if (strlen($initials) >= $length) {
                break;
            }
        }

        return substr($initials, 0, $length);
    }
    public static function generateDateLabels(Carbon $startDate, Carbon $endDate): array
    {
        $labels = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $labels[] = $currentDate->format('Y-m-d');
            $currentDate->addDay();
        }

        return $labels;
    }
}
