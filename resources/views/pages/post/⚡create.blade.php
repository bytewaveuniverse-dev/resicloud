<?php

namespace App\Livewire\Pages\Users;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;

new class extends Component
{
    use WithPagination;

    // Computed property para obtener usuarios paginados
    public function getUsersProperty()
    {
        return User::orderBy('id', 'DESC')->paginate(10);
    }
}
?>

<!-- Vista dentro del mismo archivo -->
<div>
    <h2 class="mb-3">Lista de Usuarios</h2>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Email</th>
            </tr>
        </thead>
        <tbody>
            @foreach($this->users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Paginación -->
    <div class="d-flex justify-content-center mt-3">
        {{ $this->users->links() }}
    </div>
</div>
