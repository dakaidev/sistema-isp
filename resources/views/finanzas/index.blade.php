<x-layouts::app :title="'Finanzas'">
    @if (\App\Support\IspAccess::allows(auth()->user(), 'finanzas.view'))
        <livewire:finanzas.resumen />
    @endif

    <livewire:finanzas.cobros-por-usuario />
</x-layouts::app>
