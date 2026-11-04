<?php
namespace App\Support;

use App\Models\User;

/** Permisos ISP. Se verifican también en cada solicitud Livewire. */
final class IspAccess
{
    private const RULES = [
        'admin' => ['*'],
        'cobrador' => ['clientes.view', 'cobranza.view', 'cobranza.pay', 'finanzas.cobros'],
        'tecnico' => ['servicios.view', 'servicios.edit', 'nap.view', 'nap.edit'],
        'visualizador' => ['clientes.view', 'servicios.view', 'nap.view', 'cobranza.view'],
    ];

    public static function allows(?User $user, string $permission): bool
    {
        if (!$user || !$user->activo) return false;
        if ($permission === 'dashboard.view') return true;
        $granted = self::RULES[$user->rol ?? ''] ?? [];
        return in_array('*', $granted, true) || in_array($permission, $granted, true);
    }

    public static function enforce(string $permission): void
    {
        abort_unless(self::allows(auth()->user(), $permission), 403, 'No tienes permiso para esta operación.');
    }

    public static function roles(): array
    {
        return ['admin' => 'Administrador', 'cobrador' => 'Cobrador', 'tecnico' => 'Técnico', 'visualizador' => 'Visualizador'];
    }
}
