<?php

namespace App\Livewire\Pages\Usuarios;

use Livewire\Component;
use Livewire\WithPagination; // Importante para la paginación
use App\Models\User;
use App\Models\Asiento;
use Illuminate\Support\Facades\Hash;

new class extends Component
{
    use WithPagination; // Habilitamos la paginación dinámica

    public ?int $user_id = null;
    public string $name = '';
    public ?string $telefono = null;
    public string $tipo_usuario = 'normal';
    public string $estado_cuenta = 'pendiente';
    public string $email = '';
    public string $password = '';

    public bool $showModal = false;
    public bool $editMode = false;

    // --- PROPIEDADES DE BÚSQUEDA ---
    public bool $showSearchModal = false;
    public string $queryBusqueda = '';
    public $resultadosBusqueda = [];

    // Función para el buscador del modal
    public function buscarUsuarios()
    {
        if (empty($this->queryBusqueda)) {
            $this->resultadosBusqueda = [];
            return;
        }

        $this->resultadosBusqueda = User::where('name', 'like', '%' . $this->queryBusqueda . '%')
            ->orWhere('email', 'like', '%' . $this->queryBusqueda . '%')
            ->orWhere('telefono', 'like', '%' . $this->queryBusqueda . '%')
            ->latest()
            ->get();
    }

    public function openModal($id = null)
    {
        $this->resetForm();

        if ($id) {
            $user = User::findOrFail($id);
            $this->user_id = $user->id;
            $this->name = $user->name;
            $this->telefono = $user->telefono;
            $this->tipo_usuario = $user->tipo_usuario;
            $this->estado_cuenta = $user->estado_cuenta;
            $this->email = $user->email;
            $this->editMode = true;
        }

        $this->showSearchModal = false; 
        $this->showModal = true;
    }

    public function save()
    {
        if ($this->editMode) {
            $user = User::findOrFail($this->user_id);
            $user->update([
                'name' => $this->name,
                'telefono' => $this->telefono,
                'tipo_usuario' => $this->tipo_usuario,
                'estado_cuenta' => $this->estado_cuenta,
                'email' => $this->email,
            ]);
        } else {
            User::create([
                'name' => $this->name,
                'telefono' => $this->telefono,
                'tipo_usuario' => $this->tipo_usuario,
                'estado_cuenta' => $this->estado_cuenta,
                'email' => $this->email,
                'password' => Hash::make($this->password),
            ]);
        }

        $this->closeModal();
    }

    public function delete($id)
    {
        $tieneAsientos = Asiento::where('usuario_id', $id)->exists();

        if ($tieneAsientos) {
            session()->flash('error', 'No se puede eliminar el usuario porque tiene registros contables vinculados.');
            return; 
        }

        $user = User::find($id);
        if ($user) {
            $user->delete();
            session()->flash('success', 'Usuario eliminado correctamente.');
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->reset(['user_id', 'name', 'telefono', 'tipo_usuario', 'estado_cuenta', 'email', 'password', 'editMode', 'queryBusqueda', 'resultadosBusqueda']);
        $this->tipo_usuario = 'normal';
        $this->estado_cuenta = 'pendiente';
    }

    // Enviamos los usuarios paginados a la vista
    public function with(): array
    {
        return [
            'usuarios' => User::latest()->paginate(10),
        ];
    }
}
?>

<div class="p-6">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold">Gestión de Usuarios - Resicloud</h3>
        <div class="flex gap-2">
            {{-- BOTÓN DE BÚSQUEDA --}}
            <flux:button wire:click="$set('showSearchModal', true)" icon="magnifying-glass" variant="outline">Buscar</flux:button>
            <flux:button wire:click="openModal" color="primary" icon="plus">Nuevo Usuario</flux:button>
        </div>
    </div>

    {{-- Notificaciones --}}
    @if (session()->has('error'))
        <div class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 border border-red-200 text-sm">
            {{ session('error') }}
        </div>
    @endif

    @if (session()->has('success'))
        <div class="mb-4 p-3 rounded-lg bg-green-50 text-green-700 border border-green-200 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <flux:table>
        <flux:table.row>
            <flux:table.cell class="font-semibold text-xs uppercase text-zinc-500">ID</flux:table.cell>
            <flux:table.cell class="font-semibold text-xs uppercase text-zinc-500">Nombre</flux:table.cell>
            <flux:table.cell class="font-semibold text-xs uppercase text-zinc-500">Teléfono</flux:table.cell>
            <flux:table.cell class="font-semibold text-xs uppercase text-zinc-500">Tipo</flux:table.cell>
            <flux:table.cell class="font-semibold text-xs uppercase text-zinc-500">Estado</flux:table.cell>
            <flux:table.cell class="font-semibold text-xs uppercase text-zinc-500">Email</flux:table.cell>
            <flux:table.cell class="font-semibold text-right text-xs uppercase text-zinc-500">Acciones</flux:table.cell>
        </flux:table.row>

        @forelse($usuarios as $user)
            <flux:table.row>
                <flux:table.cell>#{{ $user->id }}</flux:table.cell>
                <flux:table.cell class="font-medium text-zinc-800">{{ $user->name }}</flux:table.cell>
                <flux:table.cell>{{ $user->telefono ?? '—' }}</flux:table.cell>
                <flux:table.cell>
                    <flux:badge size="sm">{{ ucfirst($user->tipo_usuario) }}</flux:badge>
                </flux:table.cell>
                <flux:table.cell>
                    <flux:badge color="{{ $user->estado_cuenta == 'al_dia' ? 'green' : ($user->estado_cuenta == 'moroso' ? 'red' : 'yellow') }}" size="sm">
                        {{ ucfirst(str_replace('_', ' ', $user->estado_cuenta)) }}
                    </flux:badge>
                </flux:table.cell>
                <flux:table.cell class="text-zinc-500">{{ $user->email }}</flux:table.cell>
                <flux:table.cell class="text-right">
                    <flux:button size="sm" variant="ghost" wire:click="openModal({{ $user->id }})">Editar</flux:button>
                    <flux:button size="sm" variant="ghost" color="danger" 
                        wire:click="delete({{ $user->id }})" 
                        wire:confirm="¿Está seguro de eliminar a {{ $user->name }}?">
                        Eliminar
                    </flux:button>
                </flux:table.cell>
            </flux:table.row>
        @empty
            <flux:table.row>
                <flux:table.cell colspan="7" class="text-center text-zinc-400 py-8">
                    No se encontraron usuarios en esta sección.
                </flux:table.cell>
            </flux:table.row>
        @endforelse
    </flux:table>

    {{-- PAGINACIÓN --}}
    <div class="mt-6">
        {{ $usuarios->links() }}
    </div>

    {{-- 1. MODAL DE BÚSQUEDA GLOBAL --}}
    <flux:modal wire:model="showSearchModal" class="max-w-3xl">
        <flux:heading size="lg">Buscador Avanzado</flux:heading>
        <div class="mt-4 space-y-4">
            <div class="flex gap-2">
                <flux:input wire:model="queryBusqueda" placeholder="Nombre, email o teléfono..." class="flex-1" wire:keydown.enter="buscarUsuarios" />
                <flux:button wire:click="buscarUsuarios" color="primary">Filtrar</flux:button>
            </div>

            <div class="max-h-80 overflow-y-auto border rounded-xl bg-zinc-50">
                @if(count($resultadosBusqueda) > 0)
                    <flux:table>
                        @foreach($resultadosBusqueda as $res)
                            <flux:table.row>
                                <flux:table.cell class="font-bold">#{{ $res->id }}</flux:table.cell>
                                <flux:table.cell>{{ $res->name }}</flux:table.cell>
                                <flux:table.cell>{{ $res->email }}</flux:table.cell>
                                <flux:table.cell class="text-right">
                                    <flux:button size="xs" variant="outline" wire:click="openModal({{ $res->id }})">Abrir Perfil</flux:button>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table>
                @else
                    <div class="p-8 text-center text-zinc-400 italic text-sm">Realice una búsqueda para ver resultados.</div>
                @endif
            </div>
        </div>
    </flux:modal>

    {{-- 2. MODAL DE FORMULARIO (NUEVO/EDITAR) --}}
    <flux:modal wire:model="showModal">
        <flux:heading size="lg">{{ $editMode ? 'Actualizar Información' : 'Registrar Nuevo Vecino' }}</flux:heading>

        <form wire:submit.prevent="save" id="form-user" class="space-y-4 mt-4">
            <flux:input type="text" wire:model="name" label="Nombre Completo" />
            <flux:input type="text" wire:model="telefono" label="Teléfono / Celular" />

            <div class="grid grid-cols-2 gap-4">
                <flux:select wire:model="tipo_usuario" label="Rol">
                    <option value="administrador">Administrador</option>
                    <option value="normal">Usuario Normal</option>
                </flux:select>

                <flux:select wire:model="estado_cuenta" label="Estado Administrativo">
                    <option value="al_dia">Al día</option>
                    <option value="pendiente">Pendiente</option>
                    <option value="moroso">Moroso</option>
                </flux:select>
            </div>

            <flux:input type="email" wire:model="email" label="Correo Electrónico" />

            @if(!$editMode)
                <flux:input type="password" wire:model="password" label="Contraseña de Acceso" />
            @endif

            <div class="mt-8 flex justify-end gap-2 border-t pt-4">
                <flux:button wire:click="closeModal" variant="ghost">Cerrar</flux:button>
                <flux:button type="submit" color="primary">
                    {{ $editMode ? 'Guardar Cambios' : 'Registrar Usuario' }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>