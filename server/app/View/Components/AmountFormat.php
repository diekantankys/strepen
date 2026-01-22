<?php

namespace App\View\Components;

use Illuminate\View\Component;

class AmountFormat extends Component
{
    public $amount;

    public $isBold;

    public $isPerHour;

    public function __construct($amount, $isBold = true, $isPerHour = false)
    {
        $this->amount = $amount;
        $this->isBold = $isBold;
        $this->isPerHour = $isPerHour;
    }

    public function render()
    {
        return view('components.amount-format');
    }
}
