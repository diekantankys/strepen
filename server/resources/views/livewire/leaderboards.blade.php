@php
    $beerProductIds = App\Helpers\ParseProductIds::parse(App\Models\Setting::get('product_beer_ids'));
    $sodaProductIds = App\Helpers\ParseProductIds::parse(App\Models\Setting::get('product_soda_ids'));
    $snackProductIds = App\Helpers\ParseProductIds::parse(App\Models\Setting::get('product_snack_ids'));
@endphp

<div class="container">
    <div class="columns">
        <div class="column is-two-thirds">
            <h2 class="title is-4">@lang('leaderboards.header')</h2>
        </div>
        <div class="column is-one-third">
            <form wire:submit.prevent="select">
                <div class="field has-addons is-block-mobile">
                    <div class="control" style="width: 100%;">
                        <div class="select is-fullwidth">
                            <select wire:model="range">
                                <option value="month_to_date">@lang('leaderboards.range_chooser_month_to_date') (@lang('leaderboards.range_chooser_from') {{ date('Y-m-01') }})</option>
                                <option value="month">@lang('leaderboards.range_chooser_month') (@lang('leaderboards.range_chooser_from') {{ date('Y-m-d', time() - 30 * 24 * 60 * 60) }})</option>
                                <option value="half_year">@lang('leaderboards.range_chooser_half_year') (@lang('leaderboards.range_chooser_from') {{ date('Y-m-d', time() - 182 * 24 * 60 * 60) }})</option>
                                <option value="null">@lang('leaderboards.range_chooser_year_to_date') (@lang('leaderboards.range_chooser_from') {{ date('Y-01-01') }})</option>
                                <option value="year">@lang('leaderboards.range_chooser_year') (@lang('leaderboards.range_chooser_from') {{ date('Y-m-d', time() - 365 * 24 * 60 * 60) }})</option>
                                <option value="two_year">@lang('leaderboards.range_chooser_two_year') (@lang('leaderboards.range_chooser_from') {{ date('Y-m-d', time() - 2 * 365 * 24 * 60 * 60) }})</option>
                                <option value="five_year">@lang('leaderboards.range_chooser_five_year') (@lang('leaderboards.range_chooser_from') {{ date('Y-m-d', time() - 5 * 365 * 24 * 60 * 60) }})</option>
                                <option value="ten_year">@lang('leaderboards.range_chooser_ten_year') (@lang('leaderboards.range_chooser_from') {{ date('Y-m-d', time() - 10 * 365 * 24 * 60 * 60) }})</option>
                                <option value="everything">@lang('leaderboards.range_chooser_everything') (@lang('leaderboards.range_chooser_from') {{ $oldestItemDate }})</option>
                            </select>
                        </div>
                    </div>
                    <div class="control">
                        <input class="input" type="number" wire:model="amountUsers" placeholder="@lang('leaderboards.amount_users')">
                    </div>
                    <div class="control">
                        <button class="button is-link" type="submit" style="width: 100%;">@lang('leaderboards.select')</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if (count($beerProductIds) == 0 || count($sodaProductIds) == 0 || count($snackProductIds) == 0)
        <div class="notification is-danger">
            The admin has not configured all the product IDs, so this page isn't calculated correctly!
        </div>
    @endif

    <div class="columns is-multiline">
        <div class="column is-full-tablet is-half-desktop">
            <div class="box">
                <h2 class="title is-4 has-text-centered">@lang('leaderboards.live_stats')</h2>

                @php
                    function todayStats($productIds) {
                        $amountToday = DB::table('transaction_product')
                            ->join('transactions', 'transactions.id', 'transaction_id')
                            ->whereNull('deleted_at')
                            ->whereIn('product_id', $productIds)
                            ->where('transactions.created_at', '>=', now()->subHours(6))
                            ->sum('amount');
                        $firstPurchaseToday = DB::table('transaction_product')
                            ->join('transactions', 'transactions.id', 'transaction_id')
                            ->whereNull('deleted_at')
                            ->whereIn('product_id', $productIds)
                            ->where('transactions.created_at', '>=', now()->subHours(6))
                            ->orderBy('transactions.created_at', 'asc')
                            ->value('transactions.created_at');
                        $hoursSinceFirstPurchase = $firstPurchaseToday
                            ? max(ceil((now()->diffInMinutes(\Carbon\Carbon::parse($firstPurchaseToday))) / 60), 1)
                            : 1;
                        return [$amountToday, $hoursSinceFirstPurchase];
                    }

                    [$beerToday, $hoursSinceFirstBeer] = todayStats($beerProductIds);
                    [$sodaToday, $hoursSinceFirstSoda] = todayStats($sodaProductIds);
                    [$snackToday, $hoursSinceFirstSnack] = todayStats($snackProductIds);
                    $maxHoursSinceFirstPurchase = max(...[$hoursSinceFirstBeer, $hoursSinceFirstSoda, $hoursSinceFirstSnack]);
                @endphp
                <table class="table is-fullwidth">
                    <thead>
                        <tr>
                            <th>@lang('leaderboards.product')</th>
                            <th>@lang('leaderboards.amount')</th>
                            <th>@lang('leaderboards.per_hour')</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>@lang('leaderboards.beer')</strong></td>
                            <td><x-amount-format :amount="$beerToday" /></td>
                            <td><x-amount-format :amount="ceil($beerToday / $hoursSinceFirstBeer)" isPerHour /></td>
                        </tr>
                        <tr>
                            <td><strong>@lang('leaderboards.soda')</strong></td>
                            <td><x-amount-format :amount="$sodaToday" /></td>
                            <td><x-amount-format :amount="ceil($sodaToday / $hoursSinceFirstSoda)" isPerHour /></td>
                        </tr>
                        <tr>
                            <td><strong>@lang('leaderboards.snacks')</strong></td>
                            <td><x-amount-format :amount="$snackToday" /></td>
                            <td><x-amount-format :amount="ceil($snackToday / $hoursSinceFirstSnack)" isPerHour /></td>
                        </tr>
                    </tbody>
                </table>

                @php
                    $totalSpend = DB::table('transactions')
                        ->whereNull('deleted_at')
                        ->where('type', App\Models\Transaction::TYPE_TRANSACTION)
                        ->where('transactions.created_at', '>=', now()->subHours(6))
                        ->sum('price')
                        + DB::table('transactions')
                        ->whereNull('deleted_at')
                        ->where('type', App\Models\Transaction::TYPE_PAYMENT)
                        ->where('price', '>', 0)
                        ->where('transactions.created_at', '>=', now()->subHours(6))
                        ->sum('price');
                @endphp
                <div style="font-size: 1.25rem;">
                    <p>@lang("leaderboards.total_spend"): <x-money-format :money="$totalSpend" /></p>
                    <p>@lang("leaderboards.average_per_hour_spend"): <x-money-format :money="round($totalSpend / $maxHoursSinceFirstPurchase, 2)" isPerHour /></p>
                    <p>@lang("leaderboards.by_different_users"):
                        <x-amount-format :amount="DB::table('transactions')
                            ->whereNull('deleted_at')
                            ->where('type', App\Models\Transaction::TYPE_TRANSACTION)
                            ->where('transactions.created_at', '>=', now()->subHours(6))
                            ->distinct('user_id')
                            ->count('user_id')
                            +
                            DB::table('transactions')
                            ->whereNull('deleted_at')
                            ->where('type', App\Models\Transaction::TYPE_PAYMENT)
                            ->where('price', '>', 0)
                            ->where('transactions.created_at', '>=', now()->subHours(6))
                            ->distinct('user_id')
                            ->count('user_id')" />
                    </p>
                </div>
            </div>
        </div>

        <div class="column is-full-tablet is-half-desktop">
            <div class="box">
                <h2 class="title is-4 has-text-centered">@lang('leaderboards.live_best_spenders')</h2>

                @php
                    $spendingUsers = App\Models\User::where('active', true)->get()
                        ->map(function ($user) { // Very slow
                            $user->spending = DB::table('transactions')
                                ->whereNull('deleted_at')
                                ->where('user_id', $user->id)
                                ->where('type', App\Models\Transaction::TYPE_TRANSACTION)
                                ->where('transactions.created_at', '>=', now()->subHours(6))
                                ->sum('price')
                                + DB::table('transactions')
                                ->whereNull('deleted_at')
                                ->where('user_id', $user->id)
                                ->where('type', App\Models\Transaction::TYPE_PAYMENT)
                                ->where('price', '>', 0)
                                ->where('transactions.created_at', '>=', now()->subHours(6))
                                ->sum('price');
                            return $user;
                        })
                        ->filter(fn($user) => $user->spending > 0)
                        ->sortByDesc('spending')->values()
                        ->slice(0, $amountUsers);
                @endphp

                @if (count($spendingUsers) > 0)
                    <table class="table is-fullwidth">
                        <thead>
                            <tr>
                                <th class="medal-column">#</th>
                                <th>@lang('leaderboards.name')</th>
                                <th>@lang('leaderboards.beer')</th>
                                <th>@lang('leaderboards.soda')</th>
                                <th>@lang('leaderboards.snacks')</th>
                                <th style="width: @if ($range != 'month_to_date' && $range != 'month') 20% @else 40% @endif;">
                                    @lang('leaderboards.cost')
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($spendingUsers as $index => $user)
                                <tr>
                                    <td><x-index-medal :index="$index" /></td>
                                    <td style="vertical-align: middle;">
                                        <div class="image is-small is-round is-inline" style="background-image: url(/storage/avatars/{{ $user->avatar ?? App\Models\Setting::get('default_user_avatar') }});"></div>
                                        {{ $user->name }}
                                    </td>
                                    <td><x-amount-format :amount="DB::table('transaction_product')
                                        ->join('transactions', 'transactions.id', 'transaction_id')
                                        ->whereNull('deleted_at')
                                        ->where('user_id', $user->id)
                                        ->whereIn('product_id', $beerProductIds)
                                        ->where('transactions.created_at', '>=', now()->subHours(6))
                                        ->sum('amount')" /></td>
                                    <td><x-amount-format :amount="DB::table('transaction_product')
                                        ->join('transactions', 'transactions.id', 'transaction_id')
                                        ->whereNull('deleted_at')
                                        ->where('user_id', $user->id)
                                        ->whereIn('product_id', $sodaProductIds)
                                        ->where('transactions.created_at', '>=', now()->subHours(6))
                                        ->sum('amount')" /></td>
                                    <td><x-amount-format :amount="DB::table('transaction_product')
                                        ->join('transactions', 'transactions.id', 'transaction_id')
                                        ->whereNull('deleted_at')
                                        ->where('user_id', $user->id)
                                        ->whereIn('product_id', $snackProductIds)
                                        ->where('transactions.created_at', '>=', now()->subHours(6))
                                        ->sum('amount')" /></td>
                                    <td><x-money-format :money="$user->spending" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="has-text-centered"><i>@lang('leaderboards.live_best_spenders_none')</i></p>
                @endif
            </div>
        </div>
    </div>

    <div class="columns is-multiline">
        <div class="column is-full-tablet is-half-desktop">
            <div class="box">
                <h2 class="title is-4 has-text-centered">@lang('leaderboards.best_beer_header')</h2>

                @php
                    $beerUsers = App\Models\User::where('active', true)->get()
                        ->map(function ($user) use ($beerProductIds, $startDate) { // Very slow
                            $user->amount = DB::table('transaction_product')
                                ->join('transactions', 'transactions.id', 'transaction_id')
                                ->whereNull('deleted_at')
                                ->where('user_id', $user->id)
                                ->whereIn('product_id', $beerProductIds)
                                ->where('transactions.created_at', '>=', $startDate)
                                ->sum('amount');
                            return $user;
                        })
                        ->sortByDesc('amount')->values()
                        ->slice(0, $amountUsers);
                @endphp

                <table class="table is-fullwidth">
                    <thead>
                        <tr>
                            <th class="medal-column">#</th>
                            <th>@lang('leaderboards.name')</th>
                            <th style="width: @if ($range != 'month_to_date' && $range != 'month') 20% @else 40% @endif;">
                                @lang('leaderboards.amount')
                            </th>
                            @if ($range == 'half_year' || $range == null || $range == 'year')
                                <th style="width: 20%;">@lang('leaderboards.change_month')</th>
                            @endif
                            @if ($range == 'two_year' || $range == 'five_year' || $range == 'ten_year' || $range == 'everything')
                                <th style="width: 20%;">@lang('leaderboards.change_year')</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($beerUsers as $index => $user)
                            <tr>
                                <td><x-index-medal :index="$index" /></td>
                                <td style="vertical-align: middle;">
                                    <div class="image is-small is-round is-inline" style="background-image: url(/storage/avatars/{{ $user->avatar ?? App\Models\Setting::get('default_user_avatar') }});"></div>
                                    {{ $user->name }}
                                </td>
                                <td><x-amount-format :amount="$user->amount" /></td>

                                @if ($range == 'half_year' || $range == null || $range == 'year')
                                    <td>
                                        <x-change-format :change="DB::table('transaction_product')
                                            ->join('transactions', 'transactions.id', 'transaction_id')
                                            ->whereNull('deleted_at')
                                            ->where('user_id', $user->id)
                                            ->whereIn('product_id', $beerProductIds)
                                            ->where('transactions.created_at', '>=', date('Y-m-d', time() - 30 * 24 * 60 * 60))
                                            ->sum('amount')" />
                                    </td>
                                @endif

                                @if ($range == 'two_year' || $range == 'five_year' || $range == 'ten_year' || $range == 'everything')
                                    <td>
                                        <x-change-format :change="DB::table('transaction_product')
                                            ->join('transactions', 'transactions.id', 'transaction_id')
                                            ->whereNull('deleted_at')
                                            ->where('user_id', $user->id)
                                            ->whereIn('product_id', $beerProductIds)
                                            ->where('transactions.created_at', '>=', date('Y-m-d', time() - 365 * 24 * 60 * 60))
                                            ->sum('amount')" />
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="column is-full-tablet is-half-desktop">
            <div class="box">
                <h2 class="title is-4 has-text-centered">@lang('leaderboards.best_soda_header')</h2>

                @php
                    $sodaUsers = App\Models\User::where('active', true)->get()
                        ->map(function ($user) use ($sodaProductIds, $startDate) { // Very slow
                            $user->amount = DB::table('transaction_product')
                                ->join('transactions', 'transactions.id', 'transaction_id')
                                ->whereNull('deleted_at')
                                ->where('user_id', $user->id)
                                ->whereIn('product_id', $sodaProductIds)
                                ->where('transactions.created_at', '>=', $startDate)
                                ->sum('amount');
                            return $user;
                        })
                        ->sortByDesc('amount')->values()
                        ->slice(0, $amountUsers);
                @endphp

                <table class="table is-fullwidth">
                    <thead>
                        <tr>
                            <th class="medal-column">#</th>
                            <th>@lang('leaderboards.name')</th>
                            <th style="width: @if ($range != 'month_to_date' && $range != 'month') 20% @else 40% @endif;">
                                @lang('leaderboards.amount')
                            </th>
                            @if ($range == 'half_year' || $range == null || $range == 'year')
                                <th style="width: 20%;">@lang('leaderboards.change_month')</th>
                            @endif
                            @if ($range == 'two_year' || $range == 'five_year' || $range == 'ten_year' || $range == 'everything')
                                <th style="width: 20%;">@lang('leaderboards.change_year')</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sodaUsers as $index => $user)
                            <tr>
                                <td><x-index-medal :index="$index" /></td>
                                <td style="vertical-align: middle;">
                                    <div class="image is-small is-round is-inline" style="background-image: url(/storage/avatars/{{ $user->avatar ?? App\Models\Setting::get('default_user_avatar') }});"></div>
                                    {{ $user->name }}
                                </td>
                                <td><x-amount-format :amount="$user->amount" /></td>

                                @if ($range == 'half_year' || $range == null || $range == 'year')
                                    <td>
                                        <x-change-format :change="DB::table('transaction_product')
                                            ->join('transactions', 'transactions.id', 'transaction_id')
                                            ->whereNull('deleted_at')
                                            ->where('user_id', $user->id)
                                            ->whereIn('product_id', $sodaProductIds)
                                            ->where('transactions.created_at', '>=', date('Y-m-d', time() - 30 * 24 * 60 * 60))
                                            ->sum('amount')" />
                                    </td>
                                @endif

                                @if ($range == 'two_year' || $range == 'five_year' || $range == 'ten_year' || $range == 'everything')
                                    <td>
                                        <x-change-format :change="DB::table('transaction_product')
                                            ->join('transactions', 'transactions.id', 'transaction_id')
                                            ->whereNull('deleted_at')
                                            ->where('user_id', $user->id)
                                            ->whereIn('product_id', $sodaProductIds)
                                            ->where('transactions.created_at', '>=', date('Y-m-d', time() - 365 * 24 * 60 * 60))
                                            ->sum('amount')" />
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="column is-full-tablet is-half-desktop">
            <div class="box">
                <h2 class="title is-4 has-text-centered">@lang('leaderboards.best_snack_header')</h2>

                @php
                    $snackUsers = App\Models\User::where('active', true)->get()
                        ->map(function ($user) use ($snackProductIds, $startDate) { // Very slow
                            $user->amount = DB::table('transaction_product')
                                ->join('transactions', 'transactions.id', 'transaction_id')
                                ->whereNull('deleted_at')
                                ->where('user_id', $user->id)
                                ->whereIn('product_id', $snackProductIds)
                                ->where('transactions.created_at', '>=', $startDate)
                                ->sum('amount');
                            return $user;
                        })
                        ->sortByDesc('amount')->values()
                        ->slice(0, $amountUsers);
                @endphp

                <table class="table is-fullwidth">
                    <thead>
                        <tr>
                            <th class="medal-column">#</th>
                            <th>@lang('leaderboards.name')</th>
                            <th style="width: @if ($range != 'month_to_date' && $range != 'month') 20% @else 40% @endif;">
                                @lang('leaderboards.amount')
                            </th>
                            @if ($range == 'half_year' || $range == null || $range == 'year')
                                <th style="width: 20%;">@lang('leaderboards.change_month')</th>
                            @endif
                            @if ($range == 'two_year' || $range == 'five_year' || $range == 'ten_year' || $range == 'everything')
                                <th style="width: 20%;">@lang('leaderboards.change_year')</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($snackUsers as $index => $user)
                            <tr>
                                <td><x-index-medal :index="$index" /></td>
                                <td style="vertical-align: middle;">
                                    <div class="image is-small is-round is-inline" style="background-image: url(/storage/avatars/{{ $user->avatar ?? App\Models\Setting::get('default_user_avatar') }});"></div>
                                    {{ $user->name }}
                                </td>
                                <td><x-amount-format :amount="$user->amount" /></td>

                                @if ($range == 'half_year' || $range == null || $range == 'year')
                                    <td>
                                        <x-change-format :change="DB::table('transaction_product')
                                            ->join('transactions', 'transactions.id', 'transaction_id')
                                            ->whereNull('deleted_at')
                                            ->where('user_id', $user->id)
                                            ->whereIn('product_id', $snackProductIds)
                                            ->where('transactions.created_at', '>=', date('Y-m-d', time() - 30 * 24 * 60 * 60))
                                            ->sum('amount')" />
                                    </td>
                                @endif

                                @if ($range == 'two_year' || $range == 'five_year' || $range == 'ten_year' || $range == 'everything')
                                    <td>
                                        <x-change-format :change="DB::table('transaction_product')
                                            ->join('transactions', 'transactions.id', 'transaction_id')
                                            ->whereNull('deleted_at')
                                            ->where('user_id', $user->id)
                                            ->whereIn('product_id', $snackProductIds)
                                            ->where('transactions.created_at', '>=', date('Y-m-d', time() - 365 * 24 * 60 * 60))
                                            ->sum('amount')" />
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="column is-full-tablet is-half-desktop">
            <div class="box">
                <h2 class="title is-4 has-text-centered">@lang('leaderboards.best_spenders_header')</h2>

                @php
                    $spendingUsers = App\Models\User::where('active', true)->get()
                        ->map(function ($user) use ($startDate) { // Very slow
                            $user->spending = DB::table('transactions')
                                ->whereNull('deleted_at')
                                ->where('user_id', $user->id)
                                ->where('type', App\Models\Transaction::TYPE_TRANSACTION)
                                ->where('transactions.created_at', '>=', $startDate)
                                ->sum('price')
                                + DB::table('transactions')
                                ->whereNull('deleted_at')
                                ->where('user_id', $user->id)
                                ->where('type', App\Models\Transaction::TYPE_PAYMENT)
                                ->where('price', '>', 0)
                                ->where('transactions.created_at', '>=', $startDate)
                                ->sum('price');
                            return $user;
                        })
                        ->sortByDesc('spending')->values()
                        ->slice(0, $amountUsers);
                @endphp

                <table class="table is-fullwidth">
                    <thead>
                        <tr>
                            <th class="medal-column">#</th>
                            <th>@lang('leaderboards.name')</th>
                            <th style="width: @if ($range != 'month_to_date' && $range != 'month') 20% @else 40% @endif;">
                                @lang('leaderboards.cost')
                            </th>
                            @if ($range == 'half_year' || $range == null || $range == 'year')
                                <th style="width: 20%;">@lang('leaderboards.change_month')</th>
                            @endif
                            @if ($range == 'two_year' || $range == 'five_year' || $range == 'ten_year' || $range == 'everything')
                                <th style="width: 20%;">@lang('leaderboards.change_year')</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($spendingUsers as $index => $user)
                            <tr>
                                <td><x-index-medal :index="$index" /></td>
                                <td style="vertical-align: middle;">
                                    <div class="image is-small is-round is-inline" style="background-image: url(/storage/avatars/{{ $user->avatar ?? App\Models\Setting::get('default_user_avatar') }});"></div>
                                    {{ $user->name }}
                                </td>
                                <td><x-money-format :money="$user->spending" /></td>

                                @if ($range == 'half_year' || $range == null || $range == 'year')
                                    <td>
                                        <x-change-format :change="DB::table('transactions')
                                            ->whereNull('deleted_at')
                                            ->where('user_id', $user->id)
                                            ->where('type', App\Models\Transaction::TYPE_TRANSACTION)
                                            ->where('created_at', '>=', date('Y-m-d', time() - 30 * 24 * 60 * 60))
                                            ->sum('price')
                                            + DB::table('transactions')
                                            ->whereNull('deleted_at')
                                            ->where('user_id', $user->id)
                                            ->where('type', App\Models\Transaction::TYPE_PAYMENT)
                                            ->where('price', '>', 0)
                                            ->where('created_at', '>=', date('Y-m-d', time() - 30 * 24 * 60 * 60))
                                            ->sum('price')" isMoney="true" />
                                    </td>
                                @endif

                                @if ($range == 'two_year' || $range == 'five_year' || $range == 'ten_year' || $range == 'everything')
                                    <td>
                                        <x-change-format :change="DB::table('transactions')
                                            ->whereNull('deleted_at')
                                            ->where('user_id', $user->id)
                                            ->where('type', App\Models\Transaction::TYPE_TRANSACTION)
                                            ->where('created_at', '>=', date('Y-m-d', time() - 365 * 24 * 60 * 60))
                                            ->sum('price')
                                            + DB::table('transactions')
                                            ->whereNull('deleted_at')
                                            ->where('user_id', $user->id)
                                            ->where('type', App\Models\Transaction::TYPE_PAYMENT)
                                            ->where('price', '>', 0)
                                            ->where('created_at', '>=', date('Y-m-d', time() - 365 * 24 * 60 * 60))
                                            ->sum('price')" isMoney="true" />
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="column is-full-tablet is-half-desktop">
            <div class="box">
                <h2 class="title is-4 has-text-centered">@lang('leaderboards.best_balance_header')</h2>

                @php
                    $users = App\Models\User::where('active', true)->orderBy('balance', 'DESC')->limit($amountUsers)->get();
                @endphp

                <table class="table is-fullwidth">
                    <thead>
                        <tr>
                            <th class="medal-column">#</th>
                            <th>@lang('leaderboards.name')</th>
                            <th style="width: @if ($range != 'month_to_date' && $range != 'month') 20% @else 40% @endif;">
                                @lang('leaderboards.balance')
                            </th>
                            @if ($range == 'half_year' || $range == null || $range == 'year')
                                <th style="width: 20%;">@lang('leaderboards.change_month')</th>
                            @endif
                            @if ($range == 'two_year' || $range == 'five_year' || $range == 'ten_year' || $range == 'everything')
                                <th style="width: 20%;">@lang('leaderboards.change_year')</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $index => $user)
                            <tr>
                                <td><x-index-medal :index="$index" /></td>
                                <td style="vertical-align: middle;">
                                    <div class="image is-small is-round is-inline" style="background-image: url(/storage/avatars/{{ $user->avatar ?? App\Models\Setting::get('default_user_avatar') }});"></div>
                                    {{ $user->name }}
                                </td>
                                <td><x-money-format :money="$user->balance" /></td>

                                @if ($range == 'half_year' || $range == null || $range == 'year')
                                    <td>
                                        <x-change-format :change="DB::table('transactions')
                                            ->whereNull('deleted_at')
                                            ->where('user_id', $user->id)
                                            ->where('type', App\Models\Transaction::TYPE_DEPOSIT)
                                            ->where('created_at', '>=', date('Y-m-d', time() - 30 * 24 * 60 * 60))
                                            ->sum('price')
                                            -
                                            DB::table('transactions')
                                            ->whereNull('deleted_at')
                                            ->where('user_id', $user->id)
                                            ->where(fn ($query) => $query->where('type', App\Models\Transaction::TYPE_TRANSACTION)
                                                ->orWhere('type', App\Models\Transaction::TYPE_PAYMENT))
                                            ->where('created_at', '>=', date('Y-m-d', time() - 30 * 24 * 60 * 60))
                                            ->sum('price')" isMoney="true" />
                                    </td>
                                @endif

                                @if ($range == 'two_year' || $range == 'five_year' || $range == 'ten_year' || $range == 'everything')
                                    <td>
                                        <x-change-format :change="DB::table('transactions')
                                            ->whereNull('deleted_at')
                                            ->where('user_id', $user->id)
                                            ->where('type', App\Models\Transaction::TYPE_DEPOSIT)
                                            ->where('created_at', '>=', date('Y-m-d', time() - 365 * 24 * 60 * 60))
                                            ->sum('price')
                                            -
                                            DB::table('transactions')
                                            ->whereNull('deleted_at')
                                            ->where('user_id', $user->id)
                                            ->where(fn ($query) => $query->where('type', App\Models\Transaction::TYPE_TRANSACTION)
                                                ->orWhere('type', App\Models\Transaction::TYPE_PAYMENT))
                                            ->where('created_at', '>=', date('Y-m-d', time() - 365 * 24 * 60 * 60))
                                            ->sum('price')" isMoney="true" />
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="column is-full-tablet is-half-desktop">
            <div class="box">
                <h2 class="title is-4 has-text-centered">@lang('leaderboards.worst_balance_header')</h2>

                @php
                    $users = App\Models\User::where('active', true)->orderBy('balance')->limit($amountUsers)->get();
                @endphp

                <table class="table is-fullwidth">
                    <thead>
                        <tr>
                            <th class="medal-column">#</th>
                            <th>@lang('leaderboards.name')</th>
                            <th style="width: @if ($range != 'month_to_date' && $range != 'month') 20% @else 40% @endif;">
                                @lang('leaderboards.balance')
                            </th>
                            @if ($range == 'half_year' || $range == null || $range == 'year')
                                <th style="width: 20%;">@lang('leaderboards.change_month')</th>
                            @endif
                            @if ($range == 'two_year' || $range == 'five_year' || $range == 'ten_year' || $range == 'everything')
                                <th style="width: 20%;">@lang('leaderboards.change_year')</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $index => $user)
                            <tr>
                                <td><x-index-medal :index="$index" /></td>
                                <td style="vertical-align: middle;">
                                    <div class="image is-small is-round is-inline" style="background-image: url(/storage/avatars/{{ $user->avatar ?? App\Models\Setting::get('default_user_avatar') }});"></div>
                                    {{ $user->name }}
                                </td>
                                <td><x-money-format :money="$user->balance" /></td>

                                @if ($range == 'half_year' || $range == null || $range == 'year')
                                    <td>
                                        <x-change-format :change="DB::table('transactions')
                                            ->whereNull('deleted_at')
                                            ->where('user_id', $user->id)
                                            ->where('type', App\Models\Transaction::TYPE_DEPOSIT)
                                            ->where('created_at', '>=', date('Y-m-d', time() - 30 * 24 * 60 * 60))
                                            ->sum('price')
                                            -
                                            DB::table('transactions')
                                            ->whereNull('deleted_at')
                                            ->where('user_id', $user->id)
                                            ->where(fn ($query) => $query->where('type', App\Models\Transaction::TYPE_TRANSACTION)
                                                ->orWhere('type', App\Models\Transaction::TYPE_PAYMENT))
                                            ->where('created_at', '>=', date('Y-m-d', time() - 30 * 24 * 60 * 60))
                                            ->sum('price')" isMoney="true" />
                                    </td>
                                @endif

                                @if ($range == 'two_year' || $range == 'five_year' || $range == 'ten_year' || $range == 'everything')
                                    <td>
                                        <x-change-format :change="DB::table('transactions')
                                            ->whereNull('deleted_at')
                                            ->where('user_id', $user->id)
                                            ->where('type', App\Models\Transaction::TYPE_DEPOSIT)
                                            ->where('created_at', '>=', date('Y-m-d', time() - 365 * 24 * 60 * 60))
                                            ->sum('price')
                                            -
                                            DB::table('transactions')
                                            ->whereNull('deleted_at')
                                            ->where('user_id', $user->id)
                                            ->where(fn ($query) => $query->where('type', App\Models\Transaction::TYPE_TRANSACTION)
                                                ->orWhere('type', App\Models\Transaction::TYPE_PAYMENT))
                                            ->where('created_at', '>=', date('Y-m-d', time() - 365 * 24 * 60 * 60))
                                            ->sum('price')" isMoney="true" />
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="box">
        <h2 class="title is-4 has-text-centered">@lang('leaderboards.totals')</h2>

        @php
            $totalBeer = DB::table('transaction_product')
                ->join('transactions', 'transactions.id', 'transaction_id')
                ->whereNull('deleted_at')
                ->whereIn('product_id', $beerProductIds)
                ->where('transactions.created_at', '>=', $startDate)
                ->sum('amount');

            $totalSoda = DB::table('transaction_product')
                ->join('transactions', 'transactions.id', 'transaction_id')
                ->whereNull('deleted_at')
                ->whereIn('product_id', $sodaProductIds)
                ->where('transactions.created_at', '>=', $startDate)
                ->sum('amount');

            $totalSnacks = DB::table('transaction_product')
                ->join('transactions', 'transactions.id', 'transaction_id')
                ->whereNull('deleted_at')
                ->whereIn('product_id', $snackProductIds)
                ->where('transactions.created_at', '>=', $startDate)
                ->sum('amount');

            $totalSpend = DB::table('transactions')
                ->whereNull('deleted_at')
                ->where('type', App\Models\Transaction::TYPE_TRANSACTION)
                ->where('transactions.created_at', '>=', $startDate)
                ->sum('price')
                + DB::table('transactions')
                ->whereNull('deleted_at')
                ->where('type', App\Models\Transaction::TYPE_PAYMENT)
                ->where('price', '>', 0)
                ->where('transactions.created_at', '>=', $startDate)
                ->sum('price');
        @endphp

        <table class="table is-fullwidth">
            <thead>
                <tr>
                    <th>@lang('leaderboards.product')</th>
                    <th>@lang('leaderboards.total_amount')</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>@lang('leaderboards.beer')</strong></td>
                    <td><x-amount-format :amount="$totalBeer" /></td>
                </tr>
                <tr>
                    <td><strong>@lang('leaderboards.soda')</strong></td>
                    <td><x-amount-format :amount="$totalSoda" /></td>
                </tr>
                <tr>
                    <td><strong>@lang('leaderboards.snacks')</strong></td>
                    <td><x-amount-format :amount="$totalSnacks" /></td>
                </tr>
                <tr>
                    <td><strong>@lang('leaderboards.total_spend')</strong></td>
                    <td><x-money-format :money="$totalSpend" /></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
