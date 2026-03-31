<?php

namespace App\Livewire\Pages\Usuarios;

use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

new class extends Component
{
    public ?int $user_id = null;
    public string $name = '';
    public ?string $telefono = null;
    public string $tipo_usuario = 'normal';
    public string $estado_cuenta = 'pendiente';
    public string $email = '';
    public string $password = '';

    public bool $showModal = false;
    public bool $editMode = false;

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
        User::findOrFail($id)->delete();
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->user_id = null;
        $this->name = '';
        $this->telefono = null;
        $this->tipo_usuario = 'normal';
        $this->estado_cuenta = 'pendiente';
        $this->email = '';
        $this->password = '';
        $this->editMode = false;
    }
}
?>

<!-- Vista Usuarios -->
<div class="p-6">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold">Usuarios</h3>
        <flux:button wire:click="openModal" color="primary">Nuevo Usuario</flux:button>
    </div>

    <flux:table>
        <flux:table.row>
            <flux:table.cell class="font-semibold">ID</flux:table.cell>
            <flux:table.cell class="font-semibold">Nombre</flux:table.cell>
            <flux:table.cell class="font-semibold">Teléfono</flux:table.cell>
            <flux:table.cell class="font-semibold">Tipo</flux:table.cell>
            <flux:table.cell class="font-semibold">Estado</flux:table.cell>
            <flux:table.cell class="font-semibold">Email</flux:table.cell>
            <flux:table.cell class="font-semibold text-right">Acciones</flux:table.cell>
        </flux:table.row>

        @forelse(\App\Models\User::latest()->get() as $user)
            <flux:table.row>
                <flux:table.cell>{{ $user->id }}</flux:table.cell>
                <flux:table.cell>{{ $user->name }}</flux:table.cell>
                <flux:table.cell>{{ $user->telefono ?? '—' }}</flux:table.cell>
                <flux:table.cell>{{ ucfirst($user->tipo_usuario) }}</flux:table.cell>
                <flux:table.cell>{{ ucfirst($user->estado_cuenta) }}</flux:table.cell>
                <flux:table.cell>{{ $user->email }}</flux:table.cell>
                <flux:table.cell class="text-right">
                    <flux:button size="sm" color="secondary" wire:click="openModal({{ $user->id }})">Editar</flux:button>
                    <flux:button size="sm" color="danger" wire:click="delete({{ $user->id }})">Eliminar</flux:button>
                </flux:table.cell>
            </flux:table.row>
        @empty
            <flux:table.row>
                <flux:table.cell colspan="7" class="text-center text-zinc-500">
                    No hay usuarios registrados.
                </flux:table.cell>
            </flux:table.row>
        @endforelse
    </flux:table>

    <flux:modal wire:model="showModal">
        <flux:heading size="lg">{{ $editMode ? 'Editar Usuario' : 'Nuevo Usuario' }}</flux:heading>

        <div class="space-y-4 mt-4">
            <form wire:submit.prevent="save" id="form-user">
                <flux:input type="text" wire:model="name" label="Nombre" />
                <flux:input type="text" wire:model="telefono" label="Teléfono" />

                <flux:select wire:model="tipo_usuario" label="Tipo de Usuario">
                    <option value="administrador">Administrador</option>
                    <option value="normal">Normal</option>
                </flux:select>

                <flux:select wire:model="estado_cuenta" label="Estado de Cuenta">
                    <option value="al_dia">Al día</option>
                    <option value="pendiente">Pendiente</option>
                    <option value="moroso">Moroso</option>
                </flux:select>

                <flux:input type="email" wire:model="email" label="Email" />

                @if(!$editMode)
                    <flux:input type="password" wire:model="password" label="Contraseña" />
                @endif
            </form>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <flux:button wire:click="closeModal">Cancelar</flux:button>
            <flux:button type="submit" form="form-user" color="primary">
                {{ $editMode ? 'Actualizar' : 'Crear' }}
            </flux:button>
        </div>
    </flux:modal>
</div>
