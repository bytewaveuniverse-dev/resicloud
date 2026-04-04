<x-layouts::app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 p-4">
        
        <flux:heading size="xl" class="mb-2">Resumen Financiero - {{ auth()->user()->tipo_usuario === 'administrador' ? 'Global' : 'Mi Cuenta' }}</flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 w-full">
            
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm min-w-0">
                <div class="flex items-center gap-3 mb-3">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg text-blue-600">
                        <flux:icon.banknotes variant="outline" />
                    </div>
                    <flux:text weight="medium">Ingresos Percibidos</flux:text>
                </div>
                <div class="flex flex-col">
                    <span class="text-2xl font-bold text-zinc-900 dark:text-white counter" data-target="{{ \App\Models\Asiento::totalIngresosReales()->total_usd ?? 0 }}">
                        $0.00
                    </span>
                    <span class="text-xs text-zinc-500">
                        Bs. {{ number_format(\App\Models\Asiento::totalIngresosReales()->total_bs ?? 0, 2) }}
                    </span>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm border-l-4 border-l-yellow-500 min-w-0">
                <div class="flex items-center gap-3 mb-3 text-yellow-600">
                    <div class="p-2 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                        <flux:icon.clock variant="outline" />
                    </div>
                    <flux:text weight="medium">Cuentas por Cobrar</flux:text>
                </div>
                <div class="flex flex-col">
                    <span class="text-2xl font-bold text-zinc-900 dark:text-white counter" data-target="{{ \App\Models\Asiento::totalCuentasPorCobrar()->total_usd ?? 0 }}">
                        $0.00
                    </span>
                    <span class="text-xs text-zinc-500">Total por recaudar</span>
                </div>
            </div>

            @if(auth()->user()->tipo_usuario === 'administrador')
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm min-w-0">
                    <div class="flex items-center gap-3 mb-3 text-red-600">
                        <div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg">
                            <flux:icon.arrow-trending-down variant="outline" />
                        </div>
                        <flux:text weight="medium">Egresos Totales</flux:text>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-2xl font-bold text-zinc-900 dark:text-white counter" data-target="{{ \App\Models\Asiento::totalEgresos()->total_usd ?? 0 }}">
                            $0.00
                        </span>
                        <span class="text-xs text-zinc-500">
                            Bs. {{ number_format(\App\Models\Asiento::totalEgresos()->total_bs ?? 0, 2) }}
                        </span>
                    </div>
                </div>

                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm min-w-0">
                    <div class="flex items-center gap-3 mb-3 text-purple-600">
                        <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                            <flux:icon.credit-card variant="outline" />
                        </div>
                        <flux:text weight="medium">Fondo Suscripción</flux:text>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-2xl font-bold text-zinc-900 dark:text-white counter" data-target="{{ \App\Models\Asiento::totalSuscripciones()->total_usd ?? 0 }}">
                            $0.00
                        </span>
                        <span class="text-xs text-purple-400">Exclusivo Admin</span>
                    </div>
                </div>
            @endif
        </div>

        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-6 shadow-sm">
            <h2 class="text-lg font-bold mb-4">Flujo de Caja Real vs Egresos ({{ date('Y') }})</h2>
            <div class="h-80">
                <canvas id="resicloudChart"></canvas>
            </div>
        </div>
    </div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // --- LÓGICA DE CONTADORES ---
    document.querySelectorAll('.counter').forEach(counter => {
        const target = parseFloat(counter.getAttribute('data-target'));
        const duration = 1000; 
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

    @php 
    // Pasamos el ID solo si es usuario normal para que el Admin no se "ensucie" con sus propios pagos personales si no quiere
    $esAdmin = auth()->user()->tipo_usuario === 'administrador';
    $datosGrafica = \App\Models\Asiento::datosGraficaMensual($esAdmin ? null : auth()->id()); 
    @endphp

    const ctx = document.getElementById('resicloudChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
            datasets: [
                {
                    label: 'Ingresos Comunidad ($)',
                    data: @json($datosGrafica['ingresos']),
                    borderColor: '#3b82f6', // Azul
                    backgroundColor: 'rgba(59, 130, 246, 0.05)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Egresos Comunidad ($)',
                    data: @json($datosGrafica['egresos']),
                    borderColor: '#ef4444', // Rojo
                    backgroundColor: 'rgba(239, 68, 68, 0.05)',
                    tension: 0.4,
                    fill: true
                }
                @if(!$esAdmin) // Solo agregamos la tercera línea para el vecino
                ,{
                    label: 'Mis Pagos Realizados ($)',
                    data: @json($datosGrafica['personales']),
                    borderColor: '#10b981', // Verde esmeralda
                    backgroundColor: 'rgba(16, 185, 129, 0.2)',
                    borderWidth: 4, // Un poco más gruesa para que resalte
                    pointStyle: 'circle',
                    pointRadius: 5,
                    tension: 0.4,
                    fill: true
                }
                @endif
            ]
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: { 
                    position: 'top',
                    labels: { usePointStyle: true }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });


</script>
@endpush
</x-layouts::app>



<x-layouts::app :title="__('Dashboard')">

    @php 
        // Capturamos el año de la URL o usamos el actual
        $añoSeleccionado = request('year', date('Y'));
        $esAdmin = auth()->user()->tipo_usuario === 'administrador';
        
        // Llamamos al modelo pasando el año seleccionado
        $datosGrafica = \App\Models\Asiento::datosGraficaMensual($esAdmin ? null : auth()->id(), $añoSeleccionado); 
    @endphp

    <div class="flex h-full w-full flex-1 flex-col gap-6 p-4">
        
        <flux:heading size="xl" class="mb-2">
            Resumen Financiero - {{ auth()->user()->tipo_usuario === 'administrador' ? 'Global' : 'Mi Cuenta' }}
        </flux:heading>


        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 w-full">

            <!-- Inicio para el usuario normal -->
            @if(auth()->user()->tipo_usuario !== 'administrador')
            @php $solvencia = \App\Models\Asiento::miEstatusSolvencia(); @endphp
            
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm border-l-4 {{ $solvencia['monto'] > 0 ? 'border-l-red-500' : 'border-l-green-500' }}">
                <div class="flex items-center gap-3 mb-3">
                    <div class="p-2 {{ $solvencia['bg'] }} rounded-lg {{ $solvencia['color'] }}">
                        <flux:icon.check-badge variant="outline" />
                    </div>
                    <flux:text weight="medium">Estatus de Cuenta</flux:text>
                </div>
                <div class="flex flex-col">
                    <span class="text-2xl font-bold {{ $solvencia['color'] }}">
                        {{ $solvencia['label'] }}
                    </span>
                    <span class="text-xs text-zinc-500">
                        Deuda actual: ${{ number_format($solvencia['monto'], 2) }}
                    </span>
                </div>
            </div>

            @php $ultimo = \App\Models\Asiento::miUltimoPago(); @endphp
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center gap-3 mb-3 text-blue-600">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <flux:icon.arrow-path variant="outline" />
                    </div>
                    <flux:text weight="medium">Último Pago</flux:text>
                </div>
                <div class="flex flex-col">
                    <span class="text-xl font-bold text-zinc-900 dark:text-white">
                        {{ $ultimo ? '$'.number_format($ultimo->monto_dolares, 2) : 'Sin pagos' }}
                    </span>
                    <span class="text-xs text-zinc-500">
                        Confirmado el: {{ $ultimo ? \Carbon\Carbon::parse($ultimo->fecha_pago)->format('d/m/Y') : 'N/A' }}
                    </span>
                </div>
            </div>
        @endif
            <!-- Fin usuario normal -->
            
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm min-w-0">
                <div class="flex justify-between items-start mb-3">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg text-blue-600">
                        <flux:icon.banknotes variant="outline" />
                    </div>
                    @php $variacion = \App\Models\Asiento::variacionMensualIngresos(); @endphp
                    <div class="flex items-center gap-1 text-xs font-bold {{ $variacion >= 0 ? 'text-green-500' : 'text-red-500' }}">
                        <span>{{ $variacion >= 0 ? '↑' : '↓' }} {{ number_format(abs($variacion), 1) }}%</span>
                    </div>
                </div>
                <div class="flex flex-col">
                    <flux:text weight="medium" class="text-zinc-500">Ingresos Percibidos</flux:text>
                    <span class="text-2xl font-bold text-zinc-900 dark:text-white counter" data-target="{{ \App\Models\Asiento::totalIngresosReales()->total_usd ?? 0 }}">
                        $0.00
                    </span>
                    <span class="text-xs text-zinc-400">Este mes vs anterior</span>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm border-b-4 border-b-green-500 min-w-0">
                <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg text-green-600 w-fit mb-3">
                    <flux:icon.wallet variant="outline" />
                </div>
                <div class="flex flex-col">
                    <flux:text weight="medium" class="text-zinc-500">Balance Neto en Caja</flux:text>
                    <span class="text-2xl font-bold text-zinc-900 dark:text-white counter" data-target="{{ \App\Models\Asiento::balanceNeto() }}">
                        $0.00
                    </span>
                    <span class="text-xs text-zinc-400">Disponible real</span>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm border-l-4 border-l-yellow-500 min-w-0">
                <div class="flex items-center gap-3 mb-3 text-yellow-600">
                    <div class="p-2 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                        <flux:icon.clock variant="outline" />
                    </div>
                    <flux:text weight="medium">Por Cobrar</flux:text>
                </div>
                <div class="flex flex-col">
                    <span class="text-2xl font-bold text-zinc-900 dark:text-white counter" data-target="{{ \App\Models\Asiento::totalCuentasPorCobrar()->total_usd ?? 0 }}">
                        $0.00
                    </span>
                    <span class="text-xs text-zinc-500">Pendientes/Morosos</span>
                </div>
            </div>

            @if(auth()->user()->tipo_usuario === 'administrador')
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm min-w-0">
                    <div class="flex items-center gap-3 mb-3 text-red-600">
                        <div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg">
                            <flux:icon.arrow-trending-down variant="outline" />
                        </div>
                        <flux:text weight="medium">Egresos Totales</flux:text>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-2xl font-bold text-zinc-900 dark:text-white counter" data-target="{{ \App\Models\Asiento::totalEgresos()->total_usd ?? 0 }}">
                            $0.00
                        </span>
                        <span class="text-xs text-zinc-500">Gastos operativos</span>
                    </div>
                </div>
            @else
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm min-w-0 border-l-4 border-l-purple-500">
                    <div class="flex items-center gap-3 mb-3 text-purple-600">
                        <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                            <flux:icon.credit-card variant="outline" />
                        </div>
                        <flux:text weight="medium">Fondo Suscripción</flux:text>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-2xl font-bold text-zinc-900 dark:text-white counter" data-target="{{ \App\Models\Asiento::totalSuscripciones()->total_usd ?? 0 }}">
                            $0.00
                        </span>
                        <span class="text-xs text-purple-400">Mi aporte al sistema</span>
                    </div>
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <div class="lg:col-span-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-6 shadow-sm">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-bold">Flujo de Caja vs Egresos</h2>
                    <select 
                        class="text-sm border-zinc-200 dark:border-zinc-700 bg-transparent rounded-lg cursor-pointer"
                        onchange="window.location.href = '{{ route('dashboard') }}?year=' + this.value">
                        @for ($i = date('Y'); $i >= 2024; $i--)
                            <option value="{{ $i }}" {{ $añoSeleccionado == $i ? 'selected' : '' }}>
                                {{ $i }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="h-80">
                    <canvas id="resicloudChart"></canvas>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-6 shadow-sm">
                @if(auth()->user()->tipo_usuario === 'administrador')
                    <h2 class="text-lg font-bold mb-4 text-red-600 flex items-center gap-2">
                        <flux:icon.exclamation-triangle variant="outline" size="sm" />
                        Top 10 Morosos
                    </h2>
                    <div class="space-y-4">
                        @forelse(\App\Models\Asiento::topMorosos() as $moroso)
                            <div class="flex justify-between items-center border-b border-zinc-100 dark:border-zinc-800 pb-2">
                                <flux:text size="sm" weight="medium">{{ $moroso->name }}</flux:text>
                                <flux:badge color="red" size="sm" variant="flat">${{ number_format($moroso->asientos_sum_monto_dolares, 2) }}</flux:badge>
                            </div>
                        @empty
                            <flux:text class="text-center italic">No hay deudas pendientes</flux:text>
                        @endforelse
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center h-full text-center p-4">
                        <flux:icon.shield-check size="xl" class="text-green-500 mb-2" />
                        <flux:text weight="bold">Estado de Cuenta</flux:text>
                        <flux:text size="xs" class="text-zinc-500">Manténgase al día para disfrutar de todos los servicios.</flux:text>
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
        const target = parseFloat(counter.getAttribute('data-target'));
        const duration = 1200; 
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

    // --- DATOS PARA LA GRÁFICA ---
  

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
                    fill: true
                },
                {
                    label: 'Egresos Comunidad ($)',
                    data: @json($datosGrafica['egresos']),
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.05)',
                    tension: 0.4,
                    fill: true
                }
                @if(!$esAdmin) 
                ,{
                    label: 'Mis Pagos ($)',
                    data: @json($datosGrafica['personales']),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 3,
                    pointBackgroundColor: '#10b981',
                    tension: 0.4,
                    fill: true
                }
                @endif
            ]
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top', labels: { usePointStyle: true } },
                tooltip: { mode: 'index', intersect: false }
            },
            scales: {
                y: { 
                    beginAtZero: true,
                    grid: { color: 'rgba(200, 200, 200, 0.1)' }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
</script>
@endpush
</x-layouts::app>

<x-layouts::app :title="__('Dashboard')">

    @php 
        $añoSeleccionado = request('year', date('Y'));
        $esAdmin = auth()->user()->tipo_usuario === 'administrador';
        
        /** * CAMBIO CLAVE: 
         * Para que la gráfica muestre los totales de la comunidad al vecino, 
         * enviamos el auth()->id() como segundo parámetro, pero el método en el modelo 
         * debe estar preparado para calcular los globales independientemente del ID.
         */
        $datosGrafica = \App\Models\Asiento::datosGraficaMensual(auth()->id(), $añoSeleccionado); 
    @endphp

    <div class="flex h-full w-full flex-1 flex-col gap-6 p-4">
        
        <flux:heading size="xl" class="mb-2">
            Resumen Financiero - {{ $esAdmin ? 'Global' : 'Mi Cuenta' }}
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 w-full">

            @if(!$esAdmin)
                @php $solvencia = \App\Models\Asiento::miEstatusSolvencia(); @endphp
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm border-l-4 {{ $solvencia['monto'] > 0 ? 'border-l-red-500' : 'border-l-green-500' }}">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2 {{ $solvencia['bg'] }} rounded-lg {{ $solvencia['color'] }}">
                            <flux:icon.check-badge variant="outline" />
                        </div>
                        <flux:text weight="medium">Estatus de Cuenta</flux:text>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-2xl font-bold {{ $solvencia['color'] }}">{{ $solvencia['label'] }}</span>
                        <span class="text-xs text-zinc-500">Deuda: ${{ number_format($solvencia['monto'], 2) }}</span>
                    </div>
                </div>

                @php $ultimo = \App\Models\Asiento::miUltimoPago(); @endphp
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
                    <div class="flex items-center gap-3 mb-3 text-blue-600">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <flux:icon.arrow-path variant="outline" />
                        </div>
                        <flux:text weight="medium">Último Pago</flux:text>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-xl font-bold text-zinc-900 dark:text-white">
                            {{ $ultimo ? '$'.number_format($ultimo->monto_dolares, 2) : 'Sin pagos' }}
                        </span>
                        <span class="text-xs text-zinc-500">
                            {{ $ultimo ? \Carbon\Carbon::parse($ultimo->fecha_pago)->format('d/m/Y') : 'N/A' }}
                        </span>
                    </div>
                </div>
            @endif

            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm min-w-0">
                <div class="flex justify-between items-start mb-3">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg text-blue-600">
                        <flux:icon.banknotes variant="outline" />
                    </div>
                    @php $variacion = \App\Models\Asiento::variacionMensualIngresos(); @endphp
                    <div class="flex items-center gap-1 text-xs font-bold {{ $variacion >= 0 ? 'text-green-500' : 'text-red-500' }}">
                        <span>{{ $variacion >= 0 ? '↑' : '↓' }} {{ number_format(abs($variacion), 1) }}%</span>
                    </div>
                </div>
                <div class="flex flex-col">
                    <flux:text weight="medium" class="text-zinc-500">{{ $esAdmin ? 'Ingresos Percibidos' : 'Mis Pagos Totales' }}</flux:text>
                    <span class="text-2xl font-bold text-zinc-900 dark:text-white counter" data-target="{{ \App\Models\Asiento::totalIngresosReales()->total_usd ?? 0 }}">
                        $0.00
                    </span>
                    <span class="text-xs text-zinc-400">Total en {{ $añoSeleccionado }}</span>
                </div>
            </div>

            @if($esAdmin)
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm border-b-4 border-b-green-500 min-w-0">
                    <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg text-green-600 w-fit mb-3">
                        <flux:icon.wallet variant="outline" />
                    </div>
                    <div class="flex flex-col">
                        <flux:text weight="medium" class="text-zinc-500">Balance en Caja</flux:text>
                        <span class="text-2xl font-bold text-zinc-900 dark:text-white counter" data-target="{{ \App\Models\Asiento::balanceNeto() }}">
                            $0.00
                        </span>
                        <span class="text-xs text-zinc-400">Efectivo Real</span>
                    </div>
                </div>
            @else
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm min-w-0 border-l-4 border-l-purple-500">
                    <div class="flex items-center gap-3 mb-3 text-purple-600">
                        <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                            <flux:icon.credit-card variant="outline" />
                        </div>
                        <flux:text weight="medium">Fondo Suscripción</flux:text>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-2xl font-bold text-zinc-900 dark:text-white counter" data-target="{{ \App\Models\Asiento::totalSuscripciones()->total_usd ?? 0 }}">
                            $0.00
                        </span>
                        <span class="text-xs text-purple-400">Aporte al sistema</span>
                    </div>
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-6 shadow-sm">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-bold">Transparencia: Ingresos vs Egresos</h2>
                    <select class="text-sm border-zinc-200 dark:border-zinc-700 bg-transparent rounded-lg cursor-pointer" onchange="window.location.href = window.location.pathname + '?year=' + this.value">
                        @for ($i = date('Y'); $i >= 2024; $i--)
                            <option value="{{ $i }}" {{ $añoSeleccionado == $i ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>
                </div>
                <div class="h-80">
                    <canvas id="resicloudChart"></canvas>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-6 shadow-sm">
                @if($esAdmin)
                    <h2 class="text-lg font-bold mb-4 text-red-600 flex items-center gap-2">
                        <flux:icon.exclamation-triangle variant="outline" size="sm" />
                        Top 10 Morosos
                    </h2>
                    <div class="space-y-4">
                        @forelse(\App\Models\Asiento::topMorosos() as $moroso)
                            <div class="flex justify-between items-center border-b border-zinc-100 dark:border-zinc-800 pb-2">
                                <flux:text size="sm" weight="medium">{{ $moroso->name }}</flux:text>
                                <flux:badge color="red" size="sm" variant="flat">${{ number_format($moroso->asientos_sum_monto_dolares, 2) }}</flux:badge>
                            </div>
                        @empty
                            <flux:text class="text-center italic">Sin deudas</flux:text>
                        @endforelse
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center h-full text-center p-4">
                        <flux:icon.shield-check size="xl" class="text-green-500 mb-2" />
                        <flux:text weight="bold">Estado de Cuenta</flux:text>
                        <flux:text size="xs" class="text-zinc-500">Usted está viendo los totales de la comunidad para asegurar la transparencia.</flux:text>
                    </div>
                @endif
            </div>
        </div>
    </div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Lógica de contadores (sin cambios)
    document.querySelectorAll('.counter').forEach(counter => {
        const target = parseFloat(counter.getAttribute('data-target'));
        const duration = 1200; 
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

    const ctx = document.getElementById('resicloudChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
            datasets: [
                {
                    label: 'Ingresos Comunidad ($)',
                    data: @json($datosGrafica['ingresos']),
                    borderColor: '#3b82f6', // Azul: Global
                    backgroundColor: 'rgba(59, 130, 246, 0.05)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Egresos Comunidad ($)',
                    data: @json($datosGrafica['egresos']),
                    borderColor: '#ef4444', // Rojo: Global
                    backgroundColor: 'rgba(239, 68, 68, 0.05)',
                    tension: 0.4,
                    fill: true
                }
                @if(!$esAdmin) 
                ,{
                    label: 'Mis Pagos Personales ($)',
                    data: @json($datosGrafica['personales']),
                    borderColor: '#10b981', // Verde: Solo mío
                    backgroundColor: 'rgba(16, 185, 129, 0.2)',
                    borderWidth: 4,
                    pointRadius: 5,
                    tension: 0.4,
                    fill: true
                }
                @endif
            ]
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top', labels: { usePointStyle: true } },
                tooltip: { mode: 'index', intersect: false }
            },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(200, 200, 200, 0.1)' } },
                x: { grid: { display: false } }
            }
        }
    });
</script>
@endpush
</x-layouts::app>