<div class="container">
    <div class="columns">
        <div class="column is-one-third">
            <h2 class="title is-4">@lang('balance.header')</h2>
        </div>
        <div class="column is-two-thirds">
            <form wire:submit.prevent="search">
                <div class="field has-addons is-block-mobile">
                    <div class="control" style="width: 100%;">
                        <input class="input" type="date" wire:model="startDate">
                    </div>
                    <div class="control" style="width: 100%;">
                        <input class="input" type="date" wire:model="endDate" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="control">
                        <button class="button is-link" type="submit" style="width: 100%;">@lang('balance.search')</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <canvas id="balance_chart_canvas" wire:ignore></canvas>

    <script>
        document.addEventListener('livewire:navigated', () => {
            const canvas = document.getElementById('balance_chart_canvas');
            if (canvas == null || canvas.dataset.initialized === 'true') {
                return;
            }

            canvas.dataset.initialized = 'true';

            let chart = new Chart(canvas.getContext('2d'), {
                type: 'line',
                data: {
                    datasets: [{
                        label: 'Balance ({{ App\Models\Setting::get('currency_symbol') }})',
                        data: @json($balanceChart),
                        borderColor: getComputedStyle(document.querySelector('.is-link')).backgroundColor,
                        tension: 0.1
                    }]
                },
                options: {
                    animation: false
                }
            });

            const cleanupRefreshChart = Livewire.on('refreshChart', (event) => {
                const data = event.detail?.data ?? event.data;
                chart.destroy();
                chart = new Chart(canvas.getContext('2d'), {
                    type: 'line',
                    data: {
                        datasets: [{
                            label: 'Balance ({{ App\Models\Setting::get('currency_symbol') }})',
                            data: data,
                            borderColor: getComputedStyle(document.querySelector('.is-link')).backgroundColor,
                            tension: 0.1
                        }]
                    },
                    options: {
                        animation: false
                    }
                });
            });

            document.addEventListener('livewire:navigating', () => {
                cleanupRefreshChart();
                chart.destroy();
            }, { once: true });
        }, { once: true });
    </script>
</div>
