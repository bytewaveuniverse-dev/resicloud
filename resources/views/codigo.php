<?php

namespace App\Livewire\Pages\Asientos;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Asiento;
use App\Models\User;

class Index extends Component
{
    use WithPagination;

    // Listado de usuarios
    public $usuarios;
    public $asientos;

    // Form properties
    public ?int $asientoId = null;
    public ?int $usuario_id = null;
    public string $tipo = 'sistema';
    public string $descripcion = '';
    public ?string $monto_dolares = null;
    public ?string $monto_bs = null;
    public string $estado = 'pendiente';
    public ?string $referencia = null;
    public ?string $capture = null;
    public ?string $fecha = null;
    public ?string $fecha_pago = null;

    // UI
    public bool $showModal = false;

    // Filters & search
    public ?int $filterUsuario = null;
    public ?string $filterTipo = null;
    public ?string $filterEstado = null;
    public ?string $search = null;

    // Use bootstrap pagination theme (optional)
    protected $paginationTheme = 'bootstrap';

    // Inicialización
    public function mount()
    {
        // Cargar usuarios una sola vez
        $this->usuarios = User::orderBy('name')->get();
        $this->asientos = Asiento::orderBy('id','DESC')->get();
    }

    // Validation rules
    protected function rules(): array
    {
        return [
            'usuario_id' => 'required|exists:users,id',
            'tipo' => 'required|in:sistema,suscripcion,especial,egreso',
            'descripcion' => 'required|string|max:255',
            'monto_dolares' => 'required|numeric',
            'monto_bs' => 'nullable|numeric',
            'estado' => 'required|in:pendiente,por_validar,pagado,moroso',
            'fecha' => 'required|date',
            'fecha_pago' => 'nullable|date',
        ];
    }

    // Reset pagination when filters/search change
    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterUsuario() { $this->resetPage(); }
    public function updatingFilterTipo() { $this->resetPage(); }
    public function updatingFilterEstado() { $this->resetPage(); }

    

    // Open modal for create or edit
    public function openModal(?int $id = null)
    {
        $this->resetValidation();
        $this->resetForm();

        if ($id) {
            $asiento = Asiento::findOrFail($id);
            $this->asientoId = $asiento->id;
            $this->usuario_id = $asiento->usuario_id;
            $this->tipo = $asiento->tipo;
            $this->descripcion = $asiento->descripcion;
            $this->monto_dolares = (string)$asiento->monto_dolares;
            $this->monto_bs = $asiento->monto_bs !== null ? (string)$asiento->monto_bs : null;
            $this->estado = $asiento->estado;
            $this->referencia = $asiento->referencia;
            $this->capture = $asiento->capture;
            $this->fecha = $asiento->fecha ? $asiento->fecha->format('Y-m-d') : null;
            $this->fecha_pago = $asiento->fecha_pago ? $asiento->fecha_pago->format('Y-m-d') : null;
        }

        $this->showModal = true;
    }

    // Save new or update existing
    public function save()
    {
        $this->validate();

        Asiento::updateOrCreate(
            ['id' => $this->asientoId],
            [
                'usuario_id' => $this->usuario_id,
                'tipo' => $this->tipo,
                'descripcion' => $this->descripcion,
                'monto_dolares' => $this->monto_dolares,
                'monto_bs' => $this->monto_bs,
                'estado' => $this->estado,
                'referencia' => $this->referencia,
                'capture' => $this->capture,
                'fecha' => $this->fecha,
                'fecha_pago' => $this->fecha_pago,
            ]
        );

        $this->closeModal();
    }

    // Delete asiento
    public function delete(int $id)
    {
        $asiento = Asiento::findOrFail($id);
        $asiento->delete();

        if ($this->page > $this->asientosLastPage()) {
            $this->resetPage();
        }
    }

    // Evento que dispara el eliminar
    public function confirmDelete(int $id)
    {
        $this->dispatchBrowserEvent('confirm-delete', ['id' => $id]);
    }


    // Helper to get last page (used after delete)
    private function asientosLastPage(): int
    {
        $query = Asiento::query();
        if ($this->filterUsuario) $query->where('usuario_id', $this->filterUsuario);
        if ($this->filterTipo) $query->where('tipo', $this->filterTipo);
        if ($this->filterEstado) $query->where('estado', $this->filterEstado);
        if ($this->search) $query->where('descripcion', 'like', '%'.$this->search.'%');
        $count = $query->count();
        return (int) ceil($count / 15);
    }

    // Close modal and reset
    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    // Reset form fields
    private function resetForm()
    {
        $this->asientoId = null;
        $this->usuario_id = null;
        $this->tipo = 'sistema';
        $this->descripcion = '';
        $this->monto_dolares = null;
        $this->monto_bs = null;
        $this->estado = 'pendiente';
        $this->referencia = null;
        $this->capture = null;
        $this->fecha = null;
        $this->fecha_pago = null;
    }
}

?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Asientos</h3>
        <button wire:click="openModal" class="btn btn-primary">Nuevo Asiento</button>
    </div>

    {{-- Filtros y búsqueda --}}
    <div class="row g-2 mb-3">
        <div class="col-md-4">
            <input type="text" wire:model.debounce.500ms="search" class="form-control" placeholder="Buscar descripción...">
        </div>
        <div class="col-md-3">
            <select wire:model="filterUsuario" class="form-select">
                <option value="">Todos los usuarios</option>
                @foreach($usuarios as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                @endforeach 
            </select>
        </div>
        <div class="col-md-2">
            <select wire:model="filterTipo" class="form-select">
                <option value="">Todos los tipos</option>
                <option value="sistema">Sistema</option>
                <option value="suscripcion">Suscripción</option>
                <option value="especial">Especial</option>
                <option value="egreso">Egreso</option>
            </select>
        </div>
        <div class="col-md-3">
            <select wire:model="filterEstado" class="form-select">
                <option value="">Todos los estados</option>
                <option value="pendiente">Pendiente</option>
                <option value="por_validar">Por Validar</option>
                <option value="pagado">Pagado</option>
                <option value="moroso">Moroso</option>
            </select>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="table-responsive">
        <table class="table table-striped">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Usuario</th>
                    <th>Tipo</th>
                    <th>Descripción</th>
                    <th>Monto $</th>
                    <th>Monto Bs</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($asientos as $asiento)
                    <tr>
                        <td>{{ $asiento->id }}</td>
                        <td>{{ $asiento->usuario->name ?? '—' }}</td>
                        <td>{{ $asiento->tipo }}</td>
                        <td>{{ $asiento->descripcion }}</td>
                        <td>{{ number_format($asiento->monto_dolares, 2) }}</td>
                        <td>{{ $asiento->monto_bs !== null ? number_format($asiento->monto_bs, 2) : '—' }}</td>
                        <td>{{ $asiento->estado }}</td>
                        <td>{{ $asiento->fecha ? $asiento->fecha->format('d/m/Y') : '—' }}</td>
                        <td class="text-end">
                            <button wire:click="openModal({{ $asiento->id }})" class="btn btn-sm btn-outline-warning">Editar</button>
                            <button wire:click="confirmDelete({{ $asiento->id }})" class="btn btn-sm btn-outline-danger">Eliminar</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center">No hay asientos registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Paginación --}}
    <div class="d-flex justify-content-center mt-3">
        {{ $asientos->links() }}
    </div>

    {{-- Modal (crear / editar) --}}
    @if($showModal)
        <div class="modal-backdrop fade show"></div>
        <div class="modal d-block" tabindex="-1" role="dialog" style="background: rgba(0,0,0,0.4);" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $asientoId ? 'Editar Asiento' : 'Nuevo Asiento' }}</h5>
                        <button type="button" class="btn-close" wire:click="closeModal"></button>
                    </div>
                    <div class="modal-body">
                        <form wire:submit.prevent="save">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Usuario</label>
                                    <select wire:model="usuario_id" class="form-select">
                                        <option value="">Seleccione</option>
                                        @foreach($usuarios as $u)
                                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('usuario_id') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Tipo</label>
                                    <select wire:model="tipo" class="form-select">
                                        <option value="sistema">Sistema</option>
                                        <option value="suscripcion">Suscripción</option>
                                        <option value="especial">Especial</option>
                                        <option value="egreso">Egreso</option>
                                    </select>
                                    @error('tipo') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Descripción</label>
                                    <input type="text" wire:model="descripcion" class="form-control">
                                    @error('descripcion') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Monto $</label>
                                    <input type="number" step="0.01" wire:model="monto_dolares" class="form-control">
                                    @error('monto_dolares') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Monto Bs</label>
                                    <input type="number" step="0.01" wire:model="monto_bs" class="form-control">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Estado</label>
                                    <select wire:model="estado" class="form-select">
                                        <option value="pendiente">Pendiente</option>
                                        <option value="por_validar">Por Validar</option>
                                        <option value="pagado">Pagado</option>
                                        <option value="moroso">Moroso</option>
                                    </select>
                                    @error('estado') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Fecha</label>
                                    <input type="date" wire:model="fecha" class="form-control">
                                    @error('fecha') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Fecha Pago</label>
                                    <input type="date" wire:model="fecha_pago" class="form-control">
                                </div>

                                <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                                    <button type="button" class="btn btn-secondary" wire:click="closeModal">Cancelar</button>
                                    <button type="submit" class="btn btn-success">{{ $asientoId ? 'Actualizar' : 'Crear' }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

{{-- Script para confirmación de eliminación --}}
<script>
    window.addEventListener('confirm-delete', event => {
        if (confirm('¿Eliminar asiento #' + event.detail.id + '?')) {
            Livewire.dispatch('delete', { id: event.detail.id });
        }
    });
</script>
