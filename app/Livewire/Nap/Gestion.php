<?php

namespace App\Livewire\Nap;

use App\Models\CajaNap;
use App\Models\MovimientoNap;
use App\Models\PuertoNap;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class Gestion extends Component
{
    use WithPagination;

    public string $buscar = '';
    public string $filtroSede = 'todas';

    public ?int $cajaEditandoId = null;
    public ?int $cajaSeleccionadaId = null;
    public string $sede = 'upina';
    public string $codigo = '';
    public string $capacidad = '8';
    public string $ubicacionGps = '';
    public string $referencia = '';
    public string $observaciones = '';
    public bool $activa = true;

    public ?int $puertoEditandoId = null;
    public string $estadoPuerto = 'sin_verificar';
    public string $observacionesPuerto = '';

    private const ESTADOS_PUERTO = [
        'sin_verificar', 'disponible', 'reservado', 'ocupado', 'fuera_de_servicio',
    ];

    public function updatedBuscar(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroSede(): void
    {
        $this->resetPage();
    }

    public function nuevaCaja(): void
    {
        $this->reset(['cajaEditandoId', 'codigo', 'ubicacionGps', 'referencia', 'observaciones']);
        $this->sede = $this->filtroSede === 'calasuca' ? 'calasuca' : 'upina';
        $this->capacidad = '8';
        $this->activa = true;
        $this->resetValidation();
    }

    public function editarCaja(int $id): void
    {
        $caja = CajaNap::findOrFail($id);
        $this->cajaEditandoId = $caja->id;
        $this->sede = $caja->sede;
        $this->codigo = $caja->codigo;
        $this->capacidad = (string) $caja->capacidad;
        $this->ubicacionGps = ($caja->latitud !== null && $caja->longitud !== null)
            ? $caja->latitud.', '.$caja->longitud : '';
        $this->referencia = $caja->referencia ?? '';
        $this->observaciones = $caja->observaciones ?? '';
        $this->activa = (bool) $caja->activa;
        $this->resetValidation();
    }

    private function coordenadas(): array
    {
        $texto = trim($this->ubicacionGps);
        if ($texto === '') {
            return [null, null];
        }

        if (!preg_match('/\A\s*([+-]?(?:\d+(?:\.\d+)?|\.\d+))\s*,\s*([+-]?(?:\d+(?:\.\d+)?|\.\d+))\s*\z/D', $texto, $m)) {
            throw ValidationException::withMessages([
                'ubicacionGps' => 'Escribe latitud y longitud separadas por una coma. Ejemplo: -13.8, -70.0',
            ]);
        }
        $lat = (float) $m[1];
        $lng = (float) $m[2];
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            throw ValidationException::withMessages([
                'ubicacionGps' => 'Las coordenadas GPS están fuera del rango válido.',
            ]);
        }
        return [number_format($lat, 7, '.', ''), number_format($lng, 7, '.', '')];
    }

    public function guardarCaja(): void
    {
        $this->codigo = trim($this->codigo);
        $this->referencia = trim($this->referencia);
        $this->observaciones = trim($this->observaciones);

        $datos = $this->validate([
            'sede' => ['required', Rule::in(['upina', 'calasuca'])],
            'codigo' => ['required', 'string', 'min:1', 'max:80'],
            'capacidad' => ['required', Rule::in(['8', '16'])],
            'ubicacionGps' => ['nullable', 'string', 'max:100'],
            'referencia' => ['nullable', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string', 'max:3000'],
            'activa' => ['boolean'],
        ]);
        [$lat, $lng] = $this->coordenadas();
        $capacidad = (int) $datos['capacidad'];

        DB::transaction(function () use ($datos, $lat, $lng, $capacidad) {
            $caja = $this->cajaEditandoId === null
                ? null
                : CajaNap::whereKey($this->cajaEditandoId)->lockForUpdate()->firstOrFail();

            // Dentro de la misma sede un código identifica una sola caja.
            $repetida = CajaNap::query()
                ->where('sede', $datos['sede'])
                ->where('codigo', $datos['codigo'])
                ->when($caja !== null, fn ($q) => $q->where('id', '!=', $caja->id))
                ->exists();
            if ($repetida) {
                throw ValidationException::withMessages([
                    'codigo' => 'Ya hay una caja NAP con ese código en esta sede.',
                ]);
            }

            $valores = [
                'sede' => $datos['sede'],
                'codigo' => $datos['codigo'],
                'capacidad' => $capacidad,
                'latitud' => $lat,
                'longitud' => $lng,
                'referencia' => $this->referencia !== '' ? $this->referencia : null,
                'observaciones' => $this->observaciones !== '' ? $this->observaciones : null,
                'activa' => (bool) $datos['activa'],
            ];

            if ($caja === null) {
                $caja = CajaNap::create($valores);
                for ($n = 1; $n <= $capacidad; $n++) {
                    $caja->puertos()->create(['numero' => $n, 'estado' => 'sin_verificar']);
                }
                $this->anotar($caja, null, 'caja_creada',
                    "Caja {$caja->codigo} creada en {$caja->sede} con {$capacidad} puertos sin verificar.");
            } else {
                // No cambiar de sede si ya existe un puerto vinculado a un servicio real.
                if ($caja->sede !== $datos['sede'] && $caja->puertos()->where(function ($q) {
                    $q->whereNotNull('servicio_id')->orWhere('estado', '!=', 'sin_verificar');
                })->exists()) {
                    throw ValidationException::withMessages([
                        'sede' => 'La caja ya tiene puertos revisados o conexiones vinculadas. No puede trasladarse de sede sin revisión.',
                    ]);
                }
                $anterior = (int) $caja->capacidad;
                if ($capacidad < $anterior) {
                    // Solo eliminamos puertos autogenerados vacíos y nunca utilizados.
                    $extras = $caja->puertos()->where('numero', '>', $capacidad)->lockForUpdate()->get();
                    if ($extras->count() !== $anterior - $capacidad || $extras->contains(fn ($p) =>
                        $p->estado !== 'sin_verificar' || $p->servicio_id !== null || trim((string) $p->observaciones) !== ''
                    )) {
                        throw ValidationException::withMessages([
                            'capacidad' => 'No se puede reducir la capacidad: hay puertos adicionales revisados, ocupados o vinculados. Conserva la capacidad y revísalos primero.',
                        ]);
                    }
                    // El registro auditado de reducción conserva qué ocurrió.
                    $caja->puertos()->where('numero', '>', $capacidad)->delete();
                }
                $codigoAnterior = $caja->codigo;
                $sedeAnterior = $caja->sede;
                $caja->update($valores);
                if ($capacidad > $anterior) {
                    for ($n = $anterior + 1; $n <= $capacidad; $n++) {
                        $caja->puertos()->create(['numero' => $n, 'estado' => 'sin_verificar']);
                    }
                }
                $this->anotar($caja, null, 'caja_editada',
                    "Caja editada: {$codigoAnterior} ({$sedeAnterior}), capacidad {$anterior} → {$capacidad}; datos actualizados.");
            }
            $this->cajaSeleccionadaId = $caja->id;
        });

        $this->nuevaCaja();
        $this->resetPage();
        session()->flash('nap_mensaje', 'Caja NAP guardada. Revisa sus puertos antes de marcar alguno como disponible.');
    }

    public function seleccionarCaja(int $id): void
    {
        $caja = CajaNap::findOrFail($id);
        $this->cajaSeleccionadaId = $caja->id;
        $this->cancelarPuerto();
    }

    public function abrirPuerto(int $id): void
    {
        $puerto = PuertoNap::where('caja_nap_id', $this->cajaSeleccionadaId)->findOrFail($id);
        $this->puertoEditandoId = $puerto->id;
        $this->estadoPuerto = $puerto->estado;
        $this->observacionesPuerto = $puerto->observaciones ?? '';
        $this->resetValidation();
    }

    public function cancelarPuerto(): void
    {
        $this->puertoEditandoId = null;
        $this->estadoPuerto = 'sin_verificar';
        $this->observacionesPuerto = '';
        $this->resetValidation();
    }

    public function guardarPuerto(): void
    {
        $this->observacionesPuerto = trim($this->observacionesPuerto);
        $datos = $this->validate([
            'estadoPuerto' => ['required', Rule::in(self::ESTADOS_PUERTO)],
            'observacionesPuerto' => ['nullable', 'string', 'max:1500'],
        ]);
        if ($this->puertoEditandoId === null || $this->cajaSeleccionadaId === null) {
            throw ValidationException::withMessages(['estadoPuerto' => 'Selecciona primero un puerto.']);
        }

        DB::transaction(function () use ($datos) {
            $caja = CajaNap::whereKey($this->cajaSeleccionadaId)->lockForUpdate()->firstOrFail();
            $puerto = PuertoNap::where('caja_nap_id', $caja->id)
                ->whereKey($this->puertoEditandoId)->lockForUpdate()->firstOrFail();
            if ($puerto->servicio_id !== null) {
                throw ValidationException::withMessages([
                    'estadoPuerto' => 'Este puerto tiene un servicio vinculado y no puede cambiarse manualmente.',
                ]);
            }
            $anterior = $puerto->estado;
            $notasAnteriores = (string) $puerto->observaciones;
            $notasNuevas = $this->observacionesPuerto;
            if ($anterior === $datos['estadoPuerto'] && $notasAnteriores === $notasNuevas) {
                return;
            }
            $puerto->update([
                'estado' => $datos['estadoPuerto'],
                'observaciones' => $notasNuevas !== '' ? $notasNuevas : null,
            ]);
            $this->anotar($caja, (int) $puerto->numero, 'estado_puerto',
                "Puerto {$puerto->numero}: {$anterior} → {$datos['estadoPuerto']}. Nota: ".($notasNuevas !== '' ? $notasNuevas : 'sin nota'));
        });
        $this->cancelarPuerto();
        session()->flash('nap_mensaje', 'Estado del puerto actualizado y anotado en el historial técnico.');
    }

    private function anotar(CajaNap $caja, ?int $numero, string $tipo, string $detalle): void
    {
        MovimientoNap::create([
            'caja_nap_id' => $caja->id,
            'numero_puerto' => $numero,
            'tipo' => $tipo,
            'detalle' => $detalle,
            'usuario_id' => Auth::id(),
        ]);
    }

    public function render()
    {
        $cajas = CajaNap::query()
            ->when(in_array($this->filtroSede, ['upina', 'calasuca'], true),
                fn ($q) => $q->where('sede', $this->filtroSede))
            ->when(trim($this->buscar) !== '', function ($q) {
                $texto = trim($this->buscar);
                $q->where(function ($sub) use ($texto) {
                    $sub->where('codigo', 'like', '%'.$texto.'%')
                        ->orWhere('referencia', 'like', '%'.$texto.'%');
                });
            })
            ->withCount('puertos')
            ->orderBy('sede')->orderBy('codigo')
            ->paginate(10);

        $seleccionada = $this->cajaSeleccionadaId
            ? CajaNap::with(['puertos' => fn ($q) => $q->orderBy('numero'),
                'movimientos' => fn ($q) => $q->latest('id')->limit(15)])
                ->find($this->cajaSeleccionadaId)
            : null;

        return view('livewire.nap.gestion', [
            'cajas' => $cajas,
            'seleccionada' => $seleccionada,
            'totalUpina' => CajaNap::where('sede', 'upina')->count(),
            'totalCalasuca' => CajaNap::where('sede', 'calasuca')->count(),
        ]);
    }
}
