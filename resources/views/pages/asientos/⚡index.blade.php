<?php

namespace App\Livewire\Pages\Asientos;

use Livewire\Component;
use Livewire\WithFileUploads; 
use Livewire\WithPagination;
use App\Models\Asiento;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component
{
    use WithFileUploads, WithPagination;

    // Propiedades del Asiento
    public ?int $asiento_id = null;
    public ?int $usuario_id = null;
    public string $tipo = 'sistema';
    public string $descripcion = '';
    public ?float $monto_dolares = null;
    public string $estado = 'pendiente';
    public ?string $fecha = null;
    public ?float $monto_bs = null;
    public ?string $referencia = null;
    public $capture; 
    public ?string $fecha_pago = null;
    public ?string $existenteCapture = null;

    // UI y Filtros
    public string $search = ''; // Buscador integrado
    public bool $showModal = false;
    public bool $editMode = false;
    
    // Buscador de usuarios (Dentro del modal de creación)
    public string $searchUsuario = '';
    public $usuariosFiltrados = [];
    public string $nombreUsuarioSeleccionado = '';

    // Resetear paginación cuando cambie la búsqueda
    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $query = Asiento::with('usuario')->orderBy('id', 'desc');

        // Filtro de Seguridad: Si no es admin, solo ve lo suyo
        if (Auth::user()->tipo_usuario !== 'administrador') {
            $query->where('usuario_id', Auth::id());
        }

        // Lógica de búsqueda integrada
        if ($this->search) {
            $query->where(function($q) {
                $q->where('descripcion', 'like', '%' . $this->search . '%')
                  ->orWhere('estado', 'like', '%' . $this->search . '%')
                  ->orWhere('id', 'like', '%' . $this->search . '%')
                  ->orWhereHas('usuario', function($u) {
                      $u->where('name', 'like', '%' . $this->search . '%');
                  });
            });
        }

        return [
            'asientos' => $query->paginate(10),
        ];
    }

    // Lógica del Buscador de Usuarios en Modal
    public function updatedSearchUsuario($value) {
        if (strlen($value) > 1) {
            $this->usuariosFiltrados = User::where('name', 'like', "%$value%")->take(5)->get();
        } else {
            $this->usuariosFiltrados = [];
        }
    }

    public function seleccionarUsuario($id, $nombre) {
        $this->usuario_id = $id;
        $this->nombreUsuarioSeleccionado = $nombre;
        $this->usuariosFiltrados = [];
        $this->searchUsuario = '';
    }

    public function openModal($id = null)
    {
        $this->resetForm();

        if ($id) {
            $asiento = Asiento::with('usuario')->findOrFail($id);

            // Capa de seguridad
            if (Auth::user()->tipo_usuario !== 'administrador' && $asiento->estado === 'pagado') {
                return; 
            }

            $this->asiento_id = $asiento->id;
            $this->usuario_id = $asiento->usuario_id;
            $this->nombreUsuarioSeleccionado = $asiento->usuario->name ?? '';
            $this->tipo = $asiento->tipo;
            $this->descripcion = $asiento->descripcion;
            $this->monto_dolares = $asiento->monto_dolares;
            $this->estado = $asiento->estado;
            $this->fecha = $asiento->fecha;
            $this->monto_bs = $asiento->monto_bs;
            $this->referencia = $asiento->referencia;
            $this->fecha_pago = $asiento->fecha_pago;
            $this->existenteCapture = $asiento->capture; 

            $this->editMode = true;
        }

        $this->showModal = true;
    }

    public function save()
    {
        $user = Auth::user();
        $asiento = Asiento::find($this->asiento_id);

        if ($user->tipo_usuario === 'administrador') {
            $data = $this->validate([
                'usuario_id' => 'required',
                'monto_dolares' => 'required|numeric',
                'descripcion' => 'required',
                'tipo' => 'required',
                'estado' => 'required',
                'fecha' => 'nullable',
                'monto_bs' => 'nullable|numeric',
                'referencia' => 'nullable',
                'fecha_pago' => 'nullable',
            ]);
            
            if ($this->capture) {
                $data['capture'] = $this->capture->store('captures', 'public');
            }

            if ($this->editMode) { $asiento->update($data); } 
            else { Asiento::create($data); }

        } else {
            $this->validate([
                'monto_bs' => 'required|numeric',
                'referencia' => 'required',
                'fecha_pago' => 'required',
                'capture' => 'required|image|max:2048',
            ]);

            $asiento->update([
                'monto_bs' => $this->monto_bs,
                'referencia' => $this->referencia,
                'fecha_pago' => $this->fecha_pago,
                'capture' => $this->capture->store('captures', 'public'),
                'estado' => 'por_validar',
            ]);
        }
        $this->closeModal();
    }

    public function closeModal() {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm() {
        $this->reset(['asiento_id', 'usuario_id', 'searchUsuario', 'usuariosFiltrados', 'nombreUsuarioSeleccionado', 'descripcion', 'monto_dolares', 'monto_bs', 'referencia', 'capture', 'fecha_pago', 'existenteCapture', 'editMode']);
        $this->tipo = 'sistema';
        $this->estado = 'pendiente';
    }

    public function delete($id) { 
        $asiento = Asiento::findOrFail($id);
        if($asiento->capture) { Storage::disk('public')->delete($asiento->capture); }
        $asiento->delete(); 
    }
}
?>



<div class="p-8 space-y-8 animate-fade-in">
    {{-- Encabezado con Estilo Resicloud --}}
    <header class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white dark:bg-zinc-900 p-6 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
        {{-- Lado Izquierdo: Títulos --}}
        <div>
            <div class="flex items-center gap-3">
                <div class="p-2 bg-indigo-500/10 rounded-lg">
                    <flux:icon name="banknotes" class="size-6 text-indigo-600 dark:text-indigo-400" />
                </div>
                <flux:heading size="xl" class="!font-bold tracking-tight">Gestión de Pagos</flux:heading>
            </div>
            <flux:subheading class="mt-1 ml-11">Administre los asientos contables, mensualidades y validación de comprobantes.</flux:subheading>
        </div>
        
        {{-- Lado Derecho: Botón (Fuera del div anterior) --}}
        @if(Auth::user()->tipo_usuario === 'administrador')
            <flux:button wire:click="openModal" variant="primary" icon="plus" class="shadow-lg shadow-indigo-500/20 bg-indigo-600 hover:bg-indigo-700">
                Nuevo Asiento
            </flux:button>
        @endif
    </header>

    
    {{-- Área de Tabla y Buscador --}}
    <section class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden">
        
        {{-- Barra de Búsqueda Integrada --}}
        <div class="p-4 border-b border-zinc-100 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50">
            <flux:input 
                wire:model.live="search" 
                icon="magnifying-glass" 
                placeholder="Buscar por vecino, descripción o estado..." 
                variant="filled"
                class="max-w-md"
                clearable 
            />
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column class="!pl-6 w-20">ID</flux:table.column>
                <flux:table.column>Usuario</flux:table.column>
                <flux:table.column>Descripción</flux:table.column>
                <flux:table.column>Monto $</flux:table.column>
                <flux:table.column>Estado</flux:table.column>
                <flux:table.column align="center" class="pr-6">Acciones</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($asientos as $asiento)
                    <flux:table.row :key="$asiento->id" class="hover:bg-indigo-50/30 dark:hover:bg-indigo-500/5 transition-colors">
                        <flux:table.cell class="!pl-6 text-zinc-500 font-mono text-xs">
                            #{{ $asiento->id }}
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <div class="size-7 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-[10px] font-bold text-zinc-600">
                                    {{ substr($asiento->usuario->name ?? 'N', 0, 2) }}
                                </div>
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ $asiento->usuario->name ?? 'No asignado' }}
                                </span>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell class="max-w-xs truncate text-sm">
                            {{ $asiento->descripcion }}
                        </flux:table.cell>

                        <flux:table.cell class="font-bold text-indigo-600 dark:text-indigo-400">
                            ${{ number_format($asiento->monto_dolares, 2) }}
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge size="sm" color="{{ $asiento->estado == 'pagado' ? 'green' : ($asiento->estado == 'por_validar' ? 'yellow' : 'red') }}" variant="pill">
                                {{ ucfirst(str_replace('_', ' ', $asiento->estado)) }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell align="center" class="pr-6">
                            <div class="flex gap-1 justify-center">
                                @if(Auth::user()->tipo_usuario === 'administrador')
                                    <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="openModal({{ $asiento->id }})" class="hover:text-indigo-600" />
                                    <flux:button size="sm" variant="ghost" icon="trash" color="danger" wire:click="delete({{ $asiento->id }})" wire:confirm="¿Desea eliminar este registro?" />
                                @else
                                    @if($asiento->estado !== 'pagado')
                                        <flux:button size="sm" variant="filled" wire:click="openModal({{ $asiento->id }})" class="bg-indigo-600 text-white">Pagar</flux:button>
                                    @else
                                        <flux:badge variant="pill" color="green" icon="check" class="text-[10px]">Completado</flux:badge>
                                    @endif
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="py-12 text-center text-zinc-400">
                            No se encontraron registros que coincidan con la búsqueda.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        {{-- Footer Paginación --}}
        @if($asientos->hasPages())
            <div class="p-4 border-t border-zinc-100 dark:border-zinc-800 bg-zinc-50/30 dark:bg-zinc-900/30 px-6">
                {{ $asientos->links() }}
            </div>
        @endif
    </section>

    {{-- Modal de Formulario Estilizado --}}
    <flux:modal wire:model="showModal" class="md:w-[600px] border border-indigo-500/20">
        <div class="space-y-8">
            <div class="relative overflow-hidden -mx-6 -mt-6 p-6 bg-gradient-to-br from-indigo-600 to-violet-700 text-white">
                <flux:heading size="lg" class="text-white !font-bold">
                    {{ Auth::user()->tipo_usuario === 'administrador' ? ($editMode ? 'Editar Asiento' : 'Nuevo Asiento') : 'Registrar Pago' }}
                </flux:heading>
                <flux:subheading class="text-indigo-100 italic">Complete los datos de la transacción en Resicloud.</flux:subheading>
                <flux:icon name="credit-card" class="absolute -right-4 -bottom-4 size-24 opacity-10 rotate-12" />
            </div>

            <form wire:submit.prevent="save" class="space-y-6">
                @if(Auth::user()->tipo_usuario === 'administrador')
                    <div class="space-y-4">
                        <div class="relative">
                            <flux:input label="Usuario" wire:model.live="searchUsuario" placeholder="Buscar propietario..." icon="user-circle" />
                            @if($nombreUsuarioSeleccionado) 
                                <div class="text-[10px] text-indigo-600 font-bold mt-1 uppercase tracking-wider">Seleccionado: {{ $nombreUsuarioSeleccionado }}</div> 
                            @endif
                            @if(!empty($usuariosFiltrados))
                                <div class="absolute z-50 w-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 shadow-xl rounded-xl overflow-hidden">
                                    @foreach($usuariosFiltrados as $u)
                                        <div wire:click="seleccionarUsuario({{ $u->id }}, '{{ $u->name }}')" class="p-3 hover:bg-indigo-50 dark:hover:bg-indigo-500/10 cursor-pointer text-sm border-b last:border-none border-zinc-100 dark:border-zinc-700">
                                            {{ $u->name }}
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <flux:select wire:model="tipo" label="Categoría">
                                <option value="sistema">Sistema (mensualidad)</option>
                                <option value="suscripcion">Suscripción</option>
                                <option value="especial">Especial</option>
                                <option value="egreso">Egreso</option>
                            </flux:select>
                            <flux:input type="date" wire:model="fecha" label="Fecha Emisión" />
                        </div>
                        
                        <flux:input wire:model="descripcion" label="Descripción del Cargo" placeholder="Ej. Cuota Mayo 2026" />
                        
                        <div class="grid grid-cols-2 gap-4">
                            <flux:input type="number" step="0.01" wire:model="monto_dolares" label="Monto USD ($)" icon="currency-dollar" />
                            <flux:select wire:model="estado" label="Estado Inicial">
                                <option value="pendiente">Pendiente</option>
                                <option value="por_validar">Por Validar</option>
                                <option value="pagado">Pagado</option>
                                <option value="moroso">Moroso</option>
                                <option value="rechazado">Rechazado</option>
                            </flux:select>
                        </div>
                    </div>
                @else
                    <div class="p-4 bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-100 dark:border-indigo-500/20 rounded-xl">
                        <flux:text class="text-indigo-800 dark:text-indigo-300 font-bold text-lg">{{ $descripcion }}</flux:text>
                        <flux:text class="text-indigo-600 font-black">Total a reportar: ${{ number_format($monto_dolares, 2) }}</flux:text>
                    </div>
                @endif

                {{-- Sección de Comprobante --}}
                <div class="p-6 border border-zinc-200 dark:border-zinc-800 rounded-2xl space-y-4 bg-zinc-50/50 dark:bg-zinc-900/50">
                    <flux:heading size="sm" class="text-indigo-600 font-bold uppercase tracking-widest text-[10px]">Datos de la Transferencia</flux:heading>
                    <div class="grid grid-cols-2 gap-4">
                        <flux:input type="number" step="0.01" wire:model="monto_bs" label="Monto Bs." placeholder="0.00" />
                        <flux:input type="date" wire:model="fecha_pago" label="Fecha de Pago" />
                    </div>
                    <flux:input wire:model="referencia" label="Nº de Referencia / Lote" icon="hashtag" />
                    
                    <div class="space-y-2">
                        <flux:input type="file" wire:model="capture" label="Comprobante Digital (Imagen)" />
                        <div class="flex justify-center border-2 border-dashed border-zinc-200 dark:border-zinc-700 p-2 rounded-2xl bg-white dark:bg-zinc-800 min-h-[100px] items-center">
                            @if ($capture)
                                <img src="{{ $capture->temporaryUrl() }}" class="max-h-48 rounded-lg shadow-md animate-fade-in">
                            @elseif ($existenteCapture)
                                <img src="{{ asset('storage/' . $existenteCapture) }}" class="max-h-48 rounded-lg shadow-md">
                            @else
                                <div class="text-zinc-400 text-[10px] italic flex flex-col items-center">
                                    <flux:icon name="photo" class="size-8 opacity-20 mb-2" />
                                    Sin capture adjunto
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-6 border-t border-zinc-200 dark:border-zinc-800">
                    <flux:modal.close>
                        <flux:button variant="ghost">Cerrar</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" icon="check" class="bg-indigo-600 shadow-lg shadow-indigo-500/30">
                        {{ $editMode ? 'Actualizar Registro' : 'Confirmar Datos' }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>