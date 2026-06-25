<?php

namespace App\Http\Livewire\Concerns;

trait ManagesChooserValidation
{
    public $invalidInputs = [];

    public function isInputInvalid($name)
    {
        return in_array($name, $this->invalidInputs, true);
    }

    protected function setInputInvalid($name, $invalid = true)
    {
        $this->invalidInputs = array_values(array_diff($this->invalidInputs, [$name]));

        if ($invalid) {
            $this->invalidInputs[] = $name;
        }
    }

    protected function requireInput($name, $value)
    {
        $invalid = blank($value) || (is_countable($value) && count($value) === 0);

        $this->setInputInvalid($name, $invalid);

        return ! $invalid;
    }
}
