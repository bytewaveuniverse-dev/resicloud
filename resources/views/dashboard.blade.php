<x-layouts::app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 p-4">
        
        <flux:heading size="xl" class="mb-2">Resumen Financiero - {{ auth()->user()->tipo_usuario === 'administrador' ? 'Global' : 'Mi Cuenta' }}</flux:heading>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center gap-3 mb-3">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg text-blue-600">
                        <flux:icon.banknotes variant="outline" />
                    </div>
                    <flux:text weight="medium">Ingresos Percibidos</flux:text>
                </div>
                <div class="flex flex-col">
                    <span class="text-3xl font-bold text-zinc-900 dark:text-white counter" data-target="{{ \App\Models\Asiento::totalIngresosReales()->total_usd ?? 0 }}">
                        $0.00
                    </span>
                    <span class="text-sm text-zinc-500">
                        Bs. {{ number_format(\App\Models\Asiento::totalIngresosReales()->total_bs ?? 0, 2) }}
                    </span>
                </div>
            </div>

            @if(auth()->user()->tipo_usuario === 'administrador')
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center gap-3 mb-3 text-red-600">
                    <div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg">
                        <flux:icon.arrow-trending-down variant="outline" />
                    </div>
                    <flux:text weight="medium">Egresos Totales</flux:text>
                </div>
                <div class="flex flex-col">
                    <span class="text-3xl font-bold text-zinc-900 dark:text-white counter" data-target="{{ \App\Models\Asiento::totalEgresos()->total_usd ?? 0 }}">
                        $0.00
                    </span>
                    <span class="text-sm text-zinc-500">
                        Bs. {{ number_format(\App\Models\Asiento::totalEgresos()->total_bs ?? 0, 2) }}
                    </span>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center gap-3 mb-3 text-purple-600">
                    <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                        <flux:icon.credit-card variant="outline" />
                    </div>
                    <flux:text weight="medium">Fondo Suscripción</flux:text>
                </div>
                <div class="flex flex-col">
                    <span class="text-3xl font-bold text-zinc-900 dark:text-white counter" data-target="{{ \App\Models\Asiento::totalSuscripciones()->total_usd ?? 0 }}">
                        $0.00
                    </span>
                    <span class="text-sm text-zinc-500 text-purple-400">Exclusivo Admin</span>
                </div>
            </div>
            @endif

            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm border-l-4 border-l-yellow-500">
                <div class="flex items-center gap-3 mb-3 text-yellow-600">
                    <div class="p-2 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                        <flux:icon.clock variant="outline" />
                    </div>
                    <flux:text weight="medium">Cuentas por Cobrar</flux:text>
                </div>
                <div class="flex flex-col">
                    <span class="text-3xl font-bold text-zinc-900 dark:text-white counter" data-target="{{ \App\Models\Asiento::totalCuentasPorCobrar()->total_usd ?? 0 }}">
                        $0.00
                    </span>
                    <span class="text-sm text-zinc-500">
                        Total por recaudar
                    </span>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-6 shadow-sm">
            <h2 class="text-lg font-bold mb-4">Flujo de Caja Real (Por Fecha de Pago)</h2>
            <div class="h-80">
                <canvas id="resicloudChart"></canvas>
            </div>
        </div>
    </div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // --- ANIMACIÓN DE NÚMEROS (CONTADORES) ---
    document.querySelectorAll('.counter').forEach(counter => {
        const target = +counter.getAttribute('data-target');
        const increment = target / 50; // Velocidad de la animación

        const updateCount = () => {
            const count = +counter.innerText.replace('$', '').replace(',', '');
            if (count < target) {
                counter.innerText = '$' + (count + increment).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                setTimeout(updateCount, 20);
            } else {
                counter.innerText = '$' + target.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }
        };
        updateCount();
    });

    // --- GRÁFICA (Estructura base para luego hacerla dinámica) ---
    const ctx = document.getElementById('resicloudChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'], // Esto lo traeremos de la BD luego
            datasets: [{
                label: 'Ingresos Reales ($)',
                data: [500, 800, 600, 1200, 900, 1500],
                borderColor: '#3b82f6',
                tension: 0.4,
                fill: true,
                backgroundColor: 'rgba(59, 130, 246, 0.1)'
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }
        }
    });
</script>
@endpush
</x-layouts::app>