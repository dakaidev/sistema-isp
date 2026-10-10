<?php

namespace App\Livewire\Clientes;

use App\Models\Cliente;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Gestion extends Component
{
    use WithPagination;

    // Cliente que estamos editando
    public ?int $clienteEditandoId = null;

    // Formulario
    public string $codigo = '';
    public string $nombre = '';
    public string $documento = '';
    public string $telefono = '';
    public string $sector = '';
    public string $direccion = '';
    public string $observaciones = '';

    // Buscador
    public string $buscar = '';

    public function updatedBuscar(): void
    {
        $this->resetPage();
    }

    // Cargar los datos de un cliente en el formulario
    public function editar(int $id): void
    {
        $cliente = Cliente::findOrFail($id);

        $this->clienteEditandoId = $cliente->id;
        $this->codigo = $cliente->codigo;
        $this->nombre = $cliente->nombre;
        $this->documento = $cliente->documento ?? '';
        $this->telefono = $cliente->telefono ?? '';
        $this->sector = $cliente->sector ?? '';
        $this->direccion = $cliente->direccion ?? '';
        $this->observaciones = $cliente->observaciones ?? '';

        $this->resetValidation();
    }

    // Guardar un cliente nuevo o actualizar uno existente
    public function guardar(): void
    {
        $this->codigo = strtoupper(trim($this->codigo));
        $this->nombre = trim($this->nombre);

        $cliente = $this->clienteEditandoId !== null
            ? Cliente::findOrFail($this->clienteEditandoId)
            : null;

        // Validar que el código no pertenezca a otro cliente
        $codigoUnico = Rule::unique('clientes', 'codigo');

        if ($cliente !== null) {
            $codigoUnico->ignore($cliente);
        }

        $datos = $this->validate([
            'codigo' => [
                'required',
                'string',
                'max:30',
                $codigoUnico,
            ],
            'nombre' => 'required|string|max:160',
            'documento' => 'nullable|string|max:20',
            'telefono' => 'nullable|string|max:20',
            'sector' => 'nullable|string|max:120',
            'direccion' => 'nullable|string',
            'observaciones' => 'nullable|string',
        ], [
            'codigo.required' => 'Ingresa el código del cliente.',
            'codigo.unique' => 'Este código ya está registrado.',
            'nombre.required' => 'Ingresa el nombre del cliente.',
        ]);

        if ($cliente !== null) {
            // Editar cliente existente
            $cliente->update($datos);

            $mensaje = '¡Cliente actualizado correctamente!';
        } else {
            // Registrar cliente nuevo
            Cliente::create($datos);

            $mensaje = '¡Cliente registrado correctamente!';
        }

        $this->cancelarEdicion();
        $this->resetPage();

        session()->flash('mensaje', $mensaje);
    }

    // Volver al formulario de registro nuevo
    public function cancelarEdicion(): void
    {
        $this->reset([
            'clienteEditandoId',
            'codigo',
            'nombre',
            'documento',
            'telefono',
            'sector',
            'direccion',
            'observaciones',
        ]);

        $this->resetValidation();
    }

    // Listado y búsqueda
    public function render()
    {
        $clientes = Cliente::query()
            ->when(trim($this->buscar) !== '', function ($query) {
                $texto = trim($this->buscar);

                $query->where(function ($q) use ($texto) {
                    $q->where('codigo', 'like', "%{$texto}%")
                      ->orWhere('nombre', 'like', "%{$texto}%")
                      ->orWhere('telefono', 'like', "%{$texto}%");
                });
            })
            ->latest('id')
            ->paginate(10);

        return view('livewire.clientes.gestion', [
            'clientes' => $clientes,
            'totalClientes' => Cliente::count(),
        ]);
    }
}
