<?php

namespace App\View\Components;

use Illuminate\View\Component;

class MoneyFormat extends Component
{
    public $money;

    public $isBold;

    public $isPerHour = false;

    public function __construct($money, $isBold = true, $isPerHour = false)
    {
        $this->money = $money;
        $this->isBold = $isBold;
        $this->isPerHour = $isPerHour;
    }

    public function render()
    {
        return view('components.money-format');
    }
}
