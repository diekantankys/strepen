<?php

namespace App\Http\Livewire\Components;

use Livewire\Component;

abstract class InputComponent extends Component
{
    // Props
    public $name;

    // State
    public $valid = true;

    // Events
    public $listeners = ['inputValidate', 'inputClear', 'inputProps'];

    abstract public function inputValidate($name);

    abstract public function inputClear($name);

    public function inputProps($name, $props) {}
}
