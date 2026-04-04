<x-layouts::app :title="__('Dashboard')">

    @php 
        // 1. CAPTURA DE DATOS INICIAL
        $añoSeleccionado = request('year', date('Y'));
        $esAdmin = auth()->user()->tipo_usuario === 'administrador';
        
        // Datos para la gráfica (Globales + Personales si aplica)
        $datosGrafica = \App\Models\Asiento::datosGraficaMensual(auth()->id(), $añoSeleccionado); 
    @endphp

    <div class="flex h-full w-full flex-1 flex-col gap-6 p-4">
        
        <div class="flex justify-between items-center mb-2">
            <flux:heading size="xl">
                Resumen Financiero - {{ $esAdmin ? 'Gestión Global' : 'Mi Cuenta' }}
            </flux:heading>
            <flux:badge color="zinc" variant="flat" size="sm">Año Fiscal: {{ $añoSeleccionado }}</flux:badge>
        </div>
        

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 w-full">

             @if(!$esAdmin)

                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
                    <div class="flex justify-between items-start mb-3">
                        <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg text-blue-600">
                            <flux:icon.banknotes variant="outline" />
                        </div>
                        @php $variacion = \App\Models\Asiento::variacionMensualIngresos(); @endphp
                        <div class="text-xs font-bold {{ $variacion >= 0 ? 'text-green-500' : 'text-red-500' }}">
                            {{ $variacion >= 0 ? '↑' : '↓' }} {{ number_format(abs($variacion), 1) }}%
                        </div>
                    </div>
                    <div class="flex flex-col">
                        <flux:text weight="medium" class="text-zinc-500">{{ 'Ingresos Globales' }}</flux:text>
                        <span class="text-2xl font-bold text-zinc-900 dark:text-white counter" 
                            data-target="{{ \App\Models\Asiento::totalIngresosGlobales() }}">
                            $0.00
                        </span>
                    </div>
                </div>


                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm border-b-4 border-b-green-500">
                    <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg text-green-600 w-fit mb-3">
                        <flux:icon.wallet variant="outline" />
                    </div>
                    <div class="flex flex-col">
                        <flux:text weight="medium" class="text-zinc-500">Balance en Caja</flux:text>
                        <span class="text-2xl font-bold text-zinc-900 dark:text-white counter" data-target="{{ \App\Models\Asiento::balanceNeto() }}">
                            $0.00
                        </span>
                        <span class="text-xs text-zinc-400 font-medium">Disponible Real</span>
                    </div>
                </div>
            @endif
            
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
                <div class="flex justify-between items-start mb-3">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg text-blue-600">
                        <flux:icon.banknotes variant="outline" />
                    </div>
                    @php $variacion = \App\Models\Asiento::variacionMensualIngresos(); @endphp
                    <div class="text-xs font-bold {{ $variacion >= 0 ? 'text-green-500' : 'text-red-500' }}">
                        {{ $variacion >= 0 ? '↑' : '↓' }} {{ number_format(abs($variacion), 1) }}%
                    </div>
                </div>
                <div class="flex flex-col">
                    <flux:text weight="medium" class="text-zinc-500">{{ $esAdmin ? 'Ingresos Globales' : 'Mis Pagos Totales' }}</flux:text>
                    <span class="text-2xl font-bold text-zinc-900 dark:text-white counter" 
                        data-target="{{ $esAdmin ? \App\Models\Asiento::totalIngresosReales()->total_usd : auth()->user()->asientos()->where('estado','pagado')->sum('monto_dolares') }}">
                        $0.00
                    </span>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
                <div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg text-red-600 w-fit mb-3">
                    <flux:icon.arrow-trending-down variant="outline" />
                </div>
                <div class="flex flex-col">
                    <flux:text weight="medium" class="text-zinc-500">Egresos Condominio</flux:text>
                    <span class="text-2xl font-bold text-zinc-900 dark:text-white counter" data-target="{{ \App\Models\Asiento::totalEgresos()->total_usd ?? 0 }}">
                        $0.00
                    </span>
                    <span class="text-xs text-zinc-400 italic">Gastos operativos</span>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm border-l-4 border-l-yellow-500">
                <div class="p-2 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg text-yellow-600 w-fit mb-3">
                    <flux:icon.clock variant="outline" />
                </div>
                <div class="flex flex-col">
                    <flux:text weight="medium" class="text-zinc-500">{{ $esAdmin ? 'Total por Cobrar' : 'Mi Deuda Pendiente' }}</flux:text>
                    <span class="text-2xl font-bold text-zinc-900 dark:text-white counter" 
                        data-target="{{ $esAdmin ? \App\Models\Asiento::totalPorCobrarGlobal() : \App\Models\Asiento::miDeudaPendiente() }}">
                        $0.00
                    </span>
                </div>
            </div>

            @if($esAdmin)
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm border-b-4 border-b-green-500">
                    <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg text-green-600 w-fit mb-3">
                        <flux:icon.wallet variant="outline" />
                    </div>
                    <div class="flex flex-col">
                        <flux:text weight="medium" class="text-zinc-500">Balance en Caja</flux:text>
                        <span class="text-2xl font-bold text-zinc-900 dark:text-white counter" data-target="{{ \App\Models\Asiento::balanceNeto() }}">
                            $0.00
                        </span>
                        <span class="text-xs text-zinc-400 font-medium">Disponible Real</span>
                    </div>
                </div>
            @else
                @php $solvencia = \App\Models\Asiento::miEstatusSolvencia(); @endphp
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm border-l-4 {{ $solvencia['monto'] > 0 ? 'border-l-red-500' : 'border-l-green-500' }}">
                    <div class="p-2 {{ $solvencia['bg'] }} rounded-lg {{ $solvencia['color'] }} w-fit mb-3">
                        <flux:icon.check-badge variant="outline" />
                    </div>
                    <div class="flex flex-col">
                        <flux:text weight="medium" class="text-zinc-500">Estatus de Solvencia</flux:text>
                        <span class="text-2xl font-bold {{ $solvencia['color'] }}">{{ $solvencia['label'] }}</span>
                    </div>
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <div class="lg:col-span-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-6 shadow-sm">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 class="text-lg font-bold text-zinc-800 dark:text-white">Flujo de Caja del Condominio</h2>
                        <p class="text-xs text-zinc-500">Comparativa histórica de movimientos en {{ $añoSeleccionado }}</p>
                    </div>
                    <select 
                        class="text-sm border-zinc-200 dark:border-zinc-700 bg-transparent rounded-lg cursor-pointer focus:ring-0"
                        onchange="window.location.href = window.location.pathname + '?year=' + this.value">
                        @for ($i = date('Y'); $i >= 2024; $i--)
                            <option value="{{ $i }}" {{ $añoSeleccionado == $i ? 'selected' : '' }}>
                                Año {{ $i }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="h-80">
                    <canvas id="resicloudChart"></canvas>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-6 shadow-sm">
                @if($esAdmin)
                    <h2 class="text-lg font-bold mb-4 text-red-600 flex items-center gap-2 border-b border-zinc-100 dark:border-zinc-800 pb-2">
                        <flux:icon.exclamation-triangle variant="outline" size="sm" />
                        Top 10 Morosidad
                    </h2>
                    <div class="space-y-4">
                        @forelse(\App\Models\Asiento::topMorosos() as $moroso)
                            <div class="flex justify-between items-center group">
                                <div class="flex flex-col">
                                    <flux:text size="sm" weight="medium" class="group-hover:text-red-500 transition-colors">{{ $moroso->name }}</flux:text>
                                    <flux:text size="xs" class="text-zinc-400">Deuda acumulada</flux:text>
                                </div>
                                <flux:badge color="red" size="sm" variant="flat" class="font-mono">
                                    ${{ number_format($moroso->asientos_sum_monto_dolares, 2) }}
                                </flux:badge>
                            </div>
                        @empty
                            <div class="text-center py-10 italic text-zinc-400">No hay deudas pendientes</div>
                        @endforelse
                    </div>
                @else
                    @php $ultimo = \App\Models\Asiento::miUltimoPago(); @endphp
                    <div class="flex flex-col h-full">
                        <h2 class="text-lg font-bold mb-6 text-blue-600 flex items-center gap-2 border-b border-zinc-100 dark:border-zinc-800 pb-2">
                            <flux:icon.credit-card variant="outline" size="sm" />
                            Última Actividad
                        </h2>
                        @if($ultimo)
                            <div class="bg-blue-50 dark:bg-blue-900/10 rounded-xl p-4 mb-4">
                                <flux:text size="xs" class="text-blue-600 dark:text-blue-400 uppercase tracking-wider font-bold">Último Pago Confirmado</flux:text>
                                <div class="text-2xl font-bold text-zinc-900 dark:text-white mt-1">${{ number_format($ultimo->monto_dolares, 2) }}</div>
                                <flux:text size="xs" class="text-zinc-500">Fecha: {{ \Carbon\Carbon::parse($ultimo->fecha_pago)->format('d/m/Y') }}</flux:text>
                            </div>
                            <div class="mt-auto">
                                <flux:text size="xs" class="text-zinc-500 text-center italic">Gracias por su aporte a la comunidad.</flux:text>
                            </div>
                        @else
                            <div class="flex flex-col items-center justify-center flex-1 text-center">
                                <flux:icon.information-circle size="xl" class="text-zinc-300 mb-2" />
                                <flux:text class="text-zinc-500">No registras pagos confirmados este año.</flux:text>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // --- LÓGICA DE CONTADORES ANIMADOS ---
    document.querySelectorAll('.counter').forEach(counter => {
        const target = parseFloat(counter.getAttribute('data-target')) || 0;
        const duration = 1500; 
        const startTime = performance.now();

        const animate = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const currentVal = progress * target;
            counter.innerText = '$' + currentVal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
            if (progress < 1) requestAnimationFrame(animate);
        };
        requestAnimationFrame(animate);
    });

    // --- CONFIGURACIÓN DE CHART.JS ---
    const ctx = document.getElementById('resicloudChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
            datasets: [
                {
                    label: 'Ingresos Comunidad ($)',
                    data: @json($datosGrafica['ingresos']),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.05)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4
                },
                {
                    label: 'Egresos Comunidad ($)',
                    data: @json($datosGrafica['egresos']),
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.05)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4
                }
                @if(!$esAdmin) 
                ,{
                    label: 'Mis Pagos Personales ($)',
                    data: @json($datosGrafica['personales']),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.15)',
                    borderWidth: 4,
                    pointBackgroundColor: '#10b981',
                    pointRadius: 6,
                    tension: 0.4,
                    fill: true
                }
                @endif
            ]
        },
        options: {
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top', labels: { usePointStyle: true, font: { weight: 'bold' } } },
                tooltip: { backgroundColor: 'rgba(0,0,0,0.8)', padding: 12 }
            },
            scales: {
                y: { 
                    beginAtZero: true, 
                    grid: { color: 'rgba(200, 200, 200, 0.1)' },
                    ticks: { callback: value => '$' + value }
                },
                x: { grid: { display: false } }
            }
        }
    });
</script>
@endpush
</x-layouts::app>