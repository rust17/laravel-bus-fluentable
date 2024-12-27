<?php

if (! function_exists('number_ordinal')) {
    /**
     * Convert the given number to ordinal form.
     *
     * @param  int|float  $number
     * @param  string|null  $locale
     * @return string
     */
    function number_ordinal(int|float $number, ?string $locale = null)
    {
        return (new NumberFormatter($locale ?? 'en', NumberFormatter::ORDINAL))->format($number);
    }
}
