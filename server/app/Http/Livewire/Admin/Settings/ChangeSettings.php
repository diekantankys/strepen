<?php

namespace App\Http\Livewire\Admin\Settings;

use App\Helpers\ParseProductIds;
use App\Http\Livewire\Components\ProductChooser;
use App\Models\Setting;
use Livewire\Component;

class ChangeSettings extends Component
{
    public $currencySymbol;

    public $currencyName;

    public $minUserBalance;

    public $maxStripeAmount;

    public $minorAge;

    public $paginationRows;

    public $kioskIpWhitelist;

    public $leaderboardsEnabled;

    public $bankAccountIban;

    public $bankAccountHolder;

    public $isChanged = false;

    public $productBeerIds;

    public $productSodaIds;

    public $productSnackIds;

    public $rules = [
        'currencySymbol' => 'required|min:1|max:1',
        'currencyName' => 'required|min:2|max:24',
        'minUserBalance' => 'required|numeric',
        'maxStripeAmount' => 'required|integer|min:1',
        'minorAge' => 'required|integer|min:1',
        'paginationRows' => 'required|integer|min:1|max:10',
        'kioskIpWhitelist' => 'nullable|min:7|max:255',
        'leaderboardsEnabled' => 'nullable|boolean',
        'bankAccountIban' => 'nullable|min:8|max:48',
        'bankAccountHolder' => 'nullable|min:2|max:48',
    ];

    public $listeners = ['inputValue'];

    public function mount()
    {
        $this->currencySymbol = Setting::get('currency_symbol');
        $this->currencyName = Setting::get('currency_name');
        $this->minUserBalance = Setting::get('min_user_balance');
        $this->maxStripeAmount = Setting::get('max_stripe_amount');
        $this->minorAge = Setting::get('minor_age');
        $this->paginationRows = Setting::get('pagination_rows');
        $this->kioskIpWhitelist = Setting::get('kiosk_ip_whitelist');
        $this->leaderboardsEnabled = Setting::get('leaderboards_enabled') == 'true';
        $this->bankAccountIban = Setting::get('bank_account_iban');
        $this->bankAccountHolder = Setting::get('bank_account_holder');
        $this->productBeerIds = ParseProductIds::parse(Setting::get('product_beer_ids'));
        $this->productSodaIds = ParseProductIds::parse(Setting::get('product_soda_ids'));
        $this->productSnackIds = ParseProductIds::parse(Setting::get('product_snack_ids'));
    }

    public function inputValue($name, $value)
    {
        if ($name == 'product_beer' && $value != null) {
            $this->productBeerIds[] = $value;
            $this->dispatch('inputClear', 'product_beer')->to(ProductChooser::class);
        }
        if ($name == 'product_soda' && $value != null) {
            $this->productSodaIds[] = $value;
            $this->dispatch('inputClear', 'product_soda')->to(ProductChooser::class);
        }
        if ($name == 'product_snack' && $value != null) {
            $this->productSnackIds[] = $value;
            $this->dispatch('inputClear', 'product_snack')->to(ProductChooser::class);
        }
    }

    public function removeProductId($type, $id)
    {
        if ($type == 'beer') {
            $this->productBeerIds = array_filter($this->productBeerIds, fn ($item) => $item != $id);
        }
        if ($type == 'soda') {
            $this->productSodaIds = array_filter($this->productSodaIds, fn ($item) => $item != $id);
        }
        if ($type == 'snack') {
            $this->productSnackIds = array_filter($this->productSnackIds, fn ($item) => $item != $id);
        }
    }

    public function changeDetails()
    {
        $this->validate();
        Setting::set('currency_symbol', $this->currencySymbol);
        Setting::set('currency_name', $this->currencyName);
        Setting::set('min_user_balance', $this->minUserBalance);
        Setting::set('max_stripe_amount', $this->maxStripeAmount);
        Setting::set('minor_age', $this->minorAge);
        Setting::set('pagination_rows', $this->paginationRows);
        Setting::set('kiosk_ip_whitelist', $this->kioskIpWhitelist);
        Setting::set('leaderboards_enabled', $this->leaderboardsEnabled == true ? 'true' : 'false');
        Setting::set('bank_account_iban', $this->bankAccountIban);
        Setting::set('bank_account_holder', $this->bankAccountHolder);
        Setting::set('product_beer_ids', implode(',', $this->productBeerIds));
        Setting::set('product_soda_ids', implode(',', $this->productSodaIds));
        Setting::set('product_snack_ids', implode(',', $this->productSnackIds));
        $this->isChanged = true;
    }

    public function render()
    {
        return view('livewire.admin.settings.change-settings');
    }
}
