<?php

namespace App\View\Components;

use Illuminate\View\Component;

class ChangeFormat extends Component
{
    public $change;

    public $isMoney;

    public $isBold;

    public function __construct($change, $isMoney = false, $isBold = true)
    {
        $this->change = $change;
        $this->isMoney = $isMoney;
        $this->isBold = $isBold;
    }

    public function render()
    {
        return view('components.change-format');
    }
}
