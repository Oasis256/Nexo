<?php

namespace Modules\RenCommissions\Services;

class CommissionCalculatorService
{
    public function calculate(string $method, float $value, float $unitPrice, float $quantity): float
    {
        if ($method === 'on_the_house') {
            return 0;
        }

        if ($method === 'percentage') {
            return round(($unitPrice * $quantity) * ($value / 100), 2);
        }

        return round($value * $quantity, 2);
    }
}
