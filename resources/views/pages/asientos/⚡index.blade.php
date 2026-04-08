<?php

namespace App\Livewire\Pages\Asientos;

use Livewire\Component;
use Livewire\WithFileUploads; 
use App\Models\Asiento;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

new class extends Component
{
    use WithFileUploads;

    // --- PROPIEDADES ORIGINALES (REGISTRO Y PAGO) ---
    public ?int $asiento_id = null;
    public ?int $usuario_id = null;
    public string $searchUsuario = '';
    public $usuariosFiltrados = [];
    public string $nombreUsuarioSeleccionado = '';
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
    public bool $showModal = false;
    public bool $editMode = false;

    // --- PROPIEDADES NUEVAS (BUSCADOR EN MODAL) ---
    public bool $showSearchModal = false;
    public string $queryBusqueda = '';
    public $resultadosBusqueda = [];

    // Lógica del Buscador de Usuarios (Para Admin al crear deudas)
    public function updatedSearchUsuario($value) {
        if (strlen($value) > 1) {
            $this->usuariosFiltrados = User::where('name', 'like', "%$value%")->take(5)->get();
        }
    }

    // Lógica del Buscador Global (El botón Search)
    public function buscar() {
        if (empty($this->queryBusqueda)) {
            $this->resultadosBusqueda = [];
            return;
        }

        $q = Asiento::with('usuario')->orderBy('id', 'desc');

        if (Auth::user()->tipo_usuario !== 'administrador') {
            $q->where('usuario_id', Auth::id());
        }

        $this->resultadosBusqueda = $q->where(function($sub) {
            $sub->where('descripcion', 'like', '%' . $this->queryBusqueda . '%')
                ->orWhere('estado', 'like', '%' . $this->queryBusqueda . '%')
                ->orWhereHas('usuario', function($u) {
                    $u->where('name', 'like', '%' . $this->queryBusqueda . '%');
                });
        })->get();
    }

    public function seleccionarUsuario($id, $nombre) {
        $this->usuario_id = $id;
        $this->nombreUsuarioSeleccionado = $nombre;
        $this->usuariosFiltrados = [];
    }

    public function openModal($id = null)
    {
        $this->resetForm();

        if ($id) {
            $asiento = Asiento::with('usuario')->findOrFail($id);

            // --- CAPA DE SEGURIDAD PARA RESICLOUD ---
            // Si el usuario no es admin y el recibo ya está pagado, bloqueamos la edición.
            if (Auth::user()->tipo_usuario !== 'administrador' && $asiento->estado === 'pagado') {
                $this->showSearchModal = false;
                // Opcional: podrías lanzar un mensaje de alerta aquí
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
            
            // Datos de Pago (Importante no perderlos)
            $this->monto_bs = $asiento->monto_bs;
            $this->referencia = $asiento->referencia;
            $this->fecha_pago = $asiento->fecha_pago;
            $this->existenteCapture = $asiento->capture; 

            $this->editMode = true;
        }

        // Al abrir el modal de edición, siempre cerramos el de búsqueda para limpiar la UI
        $this->showSearchModal = false; 
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
        $this->reset(['asiento_id', 'usuario_id', 'searchUsuario', 'usuariosFiltrados', 'nombreUsuarioSeleccionado', 'descripcion', 'monto_dolares', 'monto_bs', 'referencia', 'capture', 'fecha_pago', 'existenteCapture', 'editMode', 'queryBusqueda', 'resultadosBusqueda']);
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

<div class="p-6">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold">Gestión de Pagos y Asientos</h3>
        <div class="flex gap-2">
            <flux:button wire:click="$set('showSearchModal', true)" icon="magnifying-glass" variant="outline">Buscar</flux:button>
            
            @if(Auth::user()->tipo_usuario === 'administrador')
                <flux:button wire:click="openModal" color="primary" icon="plus">Nuevo Asiento</flux:button>
            @endif
        </div>
    </div>

    <flux:table>
        <flux:table.row>
            <flux:table.cell class="font-semibold">ID</flux:table.cell>
            <flux:table.cell class="font-semibold">Usuario</flux:table.cell>
            <flux:table.cell class="font-semibold">Descripción</flux:table.cell>
            <flux:table.cell class="font-semibold">Monto $</flux:table.cell>
            <flux:table.cell class="font-semibold">Estado</flux:table.cell>
            <flux:table.cell class="font-semibold text-right">Acciones</flux:table.cell>
        </flux:table.row>

        @php
            $query = \App\Models\Asiento::with('usuario')->orderBy('id', 'desc');
            if (Auth::user()->tipo_usuario !== 'administrador') {
                $query->where('usuario_id', Auth::id());
            }
            $asientos = $query->get();
        @endphp

        @foreach($asientos as $asiento)
            <flux:table.row>
                <flux:table.cell>#{{ $asiento->id }}</flux:table.cell>
                <flux:table.cell>{{ $asiento->usuario->name ?? 'N/A' }}</flux:table.cell>
                <flux:table.cell>{{ $asiento->descripcion }}</flux:table.cell>
                <flux:table.cell class="font-bold">${{ number_format($asiento->monto_dolares, 2) }}</flux:table.cell>
                <flux:table.cell>
                    <flux:badge color="{{ $asiento->estado == 'pagado' ? 'green' : ($asiento->estado == 'por_validar' ? 'yellow' : 'red') }}">
                        {{ ucfirst(str_replace('_', ' ', $asiento->estado)) }}
                    </flux:badge>
                </flux:table.cell>
                <flux:table.cell class="text-right">
                    @if(Auth::user()->tipo_usuario === 'administrador')
                        <flux:button size="sm" variant="ghost" wire:click="openModal({{ $asiento->id }})">Editar</flux:button>
                        <flux:button size="sm" variant="ghost" color="danger" wire:click="delete({{ $asiento->id }})" wire:confirm="¿Borrar este asiento?">Borrar</flux:button>
                    @else
                        @if($asiento->estado !== 'pagado')
                            <flux:button size="sm" variant="ghost" wire:click="openModal({{ $asiento->id }})">Pagar</flux:button>
                        @else
                            <flux:badge variant="pill" color="green" icon="check">Completado</flux:badge>
                        @endif
                    @endif
                </flux:table.cell>
            </flux:table.row>
        @endforeach
    </flux:table>

    <flux:modal wire:model="showSearchModal" class="max-w-3xl">
        <flux:heading size="lg">Consultar Historial</flux:heading>
        
        <div class="mt-4 space-y-4">
            {{-- Barra de búsqueda --}}
            <div class="flex gap-2">
                <flux:input 
                    wire:model="queryBusqueda" 
                    placeholder="Buscar por vecino, descripción o estado..." 
                    class="flex-1" 
                    wire:keydown.enter="buscar" 
                />
                <flux:button wire:click="buscar" color="primary">Filtrar</flux:button>
            </div>

            {{-- Contenedor de resultados --}}
            <div class="max-h-80 overflow-y-auto border rounded-xl">
                @if(count($resultadosBusqueda) > 0)
                    <flux:table>
                        @foreach($resultadosBusqueda as $res)
                            <flux:table.row>
                                <flux:table.cell>#{{ $res->id }}</flux:table.cell>
                                <flux:table.cell>{{ $res->descripcion }}</flux:table.cell>
                                <flux:table.cell class="font-bold">${{ number_format($res->monto_dolares, 2) }}</flux:table.cell>
                                
                                {{-- Columna de Acciones con Validación de Seguridad --}}
                                <flux:table.cell class="text-right">
                                    @if(Auth::user()->tipo_usuario === 'administrador')
                                        {{-- El Admin siempre puede abrir cualquier asiento --}}
                                        <flux:button size="xs" variant="outline" wire:click="openModal({{ $res->id }})">
                                            Gestionar
                                        </flux:button>
                                    @else
                                        {{-- Lógica para el Usuario Normal (Vecino) --}}
                                        @if($res->estado === 'pagado')
                                            {{-- Si ya está pagado, solo mostramos el estatus, no el botón de abrir --}}
                                            <flux:badge variant="pill" color="green" icon="check" size="sm">
                                                Pago Procesado
                                            </flux:badge>
                                        @else
                                            {{-- Si está pendiente o por validar, puede abrirlo para pagar/ver --}}
                                            <flux:button size="xs" variant="outline" wire:click="openModal({{ $res->id }})">
                                                Abrir
                                            </flux:button>
                                        @endif
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table>
                @else
                    <div class="p-6 text-center text-zinc-400">
                        {{ empty($queryBusqueda) ? 'Ingrese un término para buscar.' : 'Sin resultados previos.' }}
                    </div>
                @endif
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model="showModal" class="max-w-xl">
        <flux:heading size="lg">
            {{ Auth::user()->tipo_usuario === 'administrador' ? ($editMode ? 'Gestionar Asiento' : 'Nuevo Asiento') : 'Registrar Pago' }}
        </flux:heading>

        <form wire:submit.prevent="save" class="space-y-4 mt-4">
            @if(Auth::user()->tipo_usuario === 'administrador')
                <div class="p-4 bg-zinc-50 rounded-lg space-y-4 border border-zinc-200">
                    <div class="relative">
                        <flux:input label="Usuario" wire:model.live="searchUsuario" placeholder="Buscar propietario..." />
                        @if($nombreUsuarioSeleccionado) <div class="text-xs text-blue-600 font-bold mt-1 italic">Viene de: {{ $nombreUsuarioSeleccionado }}</div> @endif
                        @if(!empty($usuariosFiltrados))
                            <div class="absolute z-50 w-full bg-white border shadow-xl rounded-md">
                                @foreach($usuariosFiltrados as $u)
                                    <div wire:click="seleccionarUsuario({{ $u->id }}, '{{ $u->name }}')" class="p-2 hover:bg-zinc-100 cursor-pointer text-sm">{{ $u->name }}</div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <flux:select wire:model="tipo" label="Tipo">
                            <option value="sistema">Sistema (mensualidad)</option>
                            <option value="suscripcion">Suscripción</option>
                            <option value="especial">Especial</option>
                            <option value="egreso">Egreso</option>
                        </flux:select>
                        <flux:input type="date" wire:model="fecha" label="Emisión" />
                    </div>
                    <flux:input wire:model="descripcion" label="Descripción" />
                    <div class="grid grid-cols-2 gap-4">
                        <flux:input type="number" step="0.01" wire:model="monto_dolares" label="Monto $" />
                        <flux:select wire:model="estado" label="Estado">
                            <option value="pendiente">Pendiente</option>
                            <option value="por_validar">Por Validar</option>
                            <option value="pagado">Pagado</option>
                            <option value="moroso">Moroso</option>
                            <option value="rechazado">Rechazado</option>
                        </flux:select>
                    </div>
                </div>
            @else
                <div class="p-3 bg-blue-50 border border-blue-100 rounded-lg">
                    <flux:text class="text-blue-800 font-bold">{{ $descripcion }} - ${{ number_format($monto_dolares, 2) }}</flux:text>
                </div>
            @endif

            <div class="p-4 border rounded-lg space-y-4 bg-white shadow-sm">
                <flux:heading size="sm" class="text-zinc-500">Comprobante Bancario</flux:heading>
                <div class="grid grid-cols-2 gap-4">
                    <flux:input type="number" step="0.01" wire:model="monto_bs" label="Monto Bs." />
                    <flux:input type="date" wire:model="fecha_pago" label="Fecha Pago" />
                </div>
                <flux:input wire:model="referencia" label="Nº de Referencia" />
                
                <div>
                    <flux:input type="file" wire:model="capture" label="Adjuntar Comprobante (JPG/PNG)" />
                    <div class="mt-2 flex justify-center border-2 border-dashed p-4 rounded-xl bg-zinc-50">
                        @if ($capture)
                            <img src="{{ $capture->temporaryUrl() }}" class="max-h-48 rounded shadow-lg">
                        @elseif ($existenteCapture)
                            <img src="{{ asset('storage/' . $existenteCapture) }}" class="max-h-48 rounded shadow-lg">
                        @else
                            <div class="text-zinc-400 text-xs italic">Cargue el capture para validar</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <flux:button wire:click="closeModal">Cancelar</flux:button>
                <flux:button type="submit" color="primary" class="px-6">Guardar Datos</flux:button>
            </div>
        </form>
    </flux:modal>
</div>