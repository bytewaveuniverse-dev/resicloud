<x-layouts::app :title="__('Reportes Administrativos')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
        
        <div class="flex justify-between items-center mb-2">
            <flux:heading size="xl">
                Módulo de Reportes PDF
            </flux:heading>
            <flux:badge color="purple" variant="flat" size="sm">Administración</flux:badge>
        </div>

        <div class="container mx-auto max-w-2xl">
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm overflow-hidden">
                <div class="bg-purple-700 p-6">
                    <h2 class="text-white text-lg font-bold">Generar Documento Oficial</h2>
                    <p class="text-purple-100 text-xs">Configure los filtros para el reporte de auditoría.</p>
                </div>

                <form action="{{ route('reportes.generar') }}" method="GET" class="p-8 space-y-6">
                    <div>
                        <flux:label>Nombre del Vecino</flux:label>
                        <flux:input 
                            type="text" 
                            name="nombre" 
                            placeholder="Dejar en blanco para todos..." 
                            icon="user"
                        />
                    </div>

                    {{-- NUEVO FILTRO DE TIPO --}}
                   
                    <div>
                        <flux:label>Tipo</flux:label>
                        {{-- Cambiamos wire:model por name="tipo" --}}
                        <flux:select name="tipo" class="w-40">
                            <option value="">Todos</option>
                            <option value="sistema">Sistema</option>
                            <option value="suscripcion">Suscripción</option>
                            <option value="especial">Especial</option>
                            <option value="egreso">Egreso</option>
                        </flux:select>
                    </div>

                    <div>
                        <flux:label>Estado del Asiento</flux:label>
                        <flux:select name="estado" placeholder="Seleccione un estado...">
                            <option value="">Cualquier Estado</option>
                            <option value="pagado">Pagado</option>
                            <option value="por_validar">Por Validar</option>
                            <option value="pendiente">Pendiente</option>
                            <option value="rechazado">Rechazado</option>
                        </flux:select>
                    </div>

                    
                    <div>
                        <flux:label>Desde</flux:label>
                        <flux:input type="date" name="fecha_inicio" />
                    </div>
                    <div>
                        <flux:label>Hasta</flux:label>
                        <flux:input type="date" name="fecha_fin" />
                    </div>


                    <div class="pt-4">
                        <flux:button type="submit" variant="primary" class="w-full bg-purple-600 hover:bg-purple-700 flex flex-row items-center justify-center gap-2">
                            <flux:icon.document-text variant="outline" class="w-5 h-5" />
                            <span>GENERAR REPORTE PDF</span>
                        </flux:button>
                    </div>


                </form>
            </div>
            
            <div class="mt-6 text-center">
                <flux:text size="xs" class="text-zinc-500 italic">
                    El archivo se generará en formato PDF estándar para impresión.
                </flux:text>
            </div>
        </div>
    </div>
</x-layouts::app>