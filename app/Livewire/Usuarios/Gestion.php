<?php
namespace App\Livewire\Usuarios;

use App\Models\User;
use App\Support\IspAccess;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Gestion extends Component
{
    use WithPagination;
    public ?int $editando = null;
    public bool $mostrarFormulario = false;
    public string $nombre = '';
    public string $correo = '';
    public string $rol = 'visualizador';
    public string $clave = '';
    public bool $activo = true;
    public string $buscar = '';

    public function boot(): void { IspAccess::enforce('usuarios.manage'); }
    public function updatedBuscar(): void { $this->resetPage(); }

    public function nuevo(): void
    {
        $this->reset(['editando', 'nombre', 'correo', 'clave']);
        $this->rol = 'visualizador';
        $this->activo = true;
        $this->mostrarFormulario = true;
        $this->resetValidation();
    }

    public function editar(int $id): void
    {
        $u = User::findOrFail($id);
        $this->editando = (int) $u->id;
        $this->nombre = $u->name;
        $this->correo = $u->email;
        $this->rol = $u->rol;
        $this->activo = $u->activo;
        $this->clave = '';
        $this->mostrarFormulario = true;
        $this->resetValidation();
    }

    public function cancelar(): void
    {
        $this->mostrarFormulario = false;
        $this->editando = null;
        $this->clave = '';
        $this->resetValidation();
    }

    public function guardar(): void
    {
        $u = $this->editando === null ? null : User::findOrFail($this->editando);
        $datos = $this->validate([
            'nombre' => ['required', 'string', 'min:3', 'max:255'],
            'correo' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($u?->id)],
            'rol' => ['required', Rule::in(array_keys(IspAccess::roles()))],
            'activo' => ['boolean'],
            'clave' => [$u ? 'nullable' : 'required', 'string', 'min:12', 'max:255'],
        ], ['clave.min' => 'La contraseña debe tener por lo menos 12 caracteres.']);

        if ($u && (int)$u->id === (int)auth()->id() && ($datos['rol'] !== 'admin' || !$datos['activo'])) {
            $this->addError('rol', 'No puedes quitarte el rol de administrador ni desactivar tu propia cuenta.');
            return;
        }
        if ($u && $u->rol === 'admin' && ($datos['rol'] !== 'admin' || !$datos['activo'])
            && User::where('rol', 'admin')->where('activo', true)->count() <= 1) {
            $this->addError('rol', 'Debe existir al menos un administrador activo.');
            return;
        }
        if ($u) {
            $u->name = trim($datos['nombre']);
            $u->email = mb_strtolower(trim($datos['correo']));
            $u->rol = $datos['rol'];
            $u->activo = (bool)$datos['activo'];
            if ($datos['clave'] !== '') $u->password = $datos['clave'];
            $u->save();
            session()->flash('usuarios_mensaje', 'Usuario actualizado.');
        } else {
            User::create([
                'name' => trim($datos['nombre']),
                'email' => mb_strtolower(trim($datos['correo'])),
                'password' => $datos['clave'],
                'rol' => $datos['rol'],
                'activo' => (bool)$datos['activo'],
                'email_verified_at' => now(),
            ]);
            session()->flash('usuarios_mensaje', 'Cuenta creada. Entrega la contraseña al trabajador por un medio privado.');
        }
        $this->cancelar();
        $this->resetPage();
    }

    public function cambiarEstado(int $id): void
    {
        $u = User::findOrFail($id);
        if ($u->id === auth()->id()) abort(403, 'No puedes bloquearte a ti mismo.');
        if ($u->rol === 'admin' && $u->activo && User::where('rol', 'admin')->where('activo', true)->count() <= 1) {
            $this->addError('estado', 'No puedes desactivar al último administrador.');
            return;
        }
        $u->update(['activo' => !$u->activo]);
        session()->flash('usuarios_mensaje', $u->activo ? 'Cuenta activada.' : 'Cuenta desactivada.');
    }

    public function render()
    {
        return view('livewire.usuarios.gestion', [
            'usuarios' => User::query()->when(trim($this->buscar) !== '', fn ($q) => $q->where(function ($w) {
                $v = trim($this->buscar);
                $w->where('name', 'like', "%{$v}%")->orWhere('email', 'like', "%{$v}%");
            }))->orderBy('name')->paginate(15),
            'nombresRoles' => IspAccess::roles(),
        ]);
    }
}
