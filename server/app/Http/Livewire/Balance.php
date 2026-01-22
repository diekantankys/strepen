<?php

namespace App\Http\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Balance extends Component
{
    public $startDate;

    public $endDate;

    public function mount()
    {
        if ($this->startDate == null || strtotime($this->startDate) == false) {
            $firstTransaction = Auth::user()->transactions()->orderBy('created_at')->first();
            if ($firstTransaction != null) {
                $maxDiff = 365 * 24 * 60 * 60;
                if (time() - $firstTransaction->created_at->getTimestamp() < $maxDiff) {
                    $this->startDate = $firstTransaction->created_at->format('Y-m-d');
                } else {
                    $this->startDate = date('Y-m-d', time() - $maxDiff);
                }
            }
        }

        if ($this->endDate == null || strtotime($this->endDate) == false) {
            $this->endDate = date('Y-m-d');
        }
    }

    public function search()
    {
        $this->mount();
        $this->emit('refreshChart', Auth::user()->getBalanceChart($this->startDate, $this->endDate));
    }

    public function render()
    {
        return view('livewire.balance', [
            'balanceChart' => Auth::user()->getBalanceChart($this->startDate, $this->endDate),
        ])->layout('layouts.app', ['title' => __('balance.title'), 'chartjs' => true]);
    }
}
