<?php

namespace App\Filament\Resources\ShipmentTrackingResource\Pages;

use App\Filament\Resources\ShipmentTrackingResource;
use App\Services\Monitoring\DetailUnitProvider;
use App\ViewModels\Monitoring\UnitDetailData;
use Filament\Resources\Pages\Page;

/**
 * Detail Unit Workspace (v1.0) — halaman operasional penuh untuk satu unit,
 * dibuka Coordinator setelah memilih unit dari Monitoring.
 *
 * READ-ONLY / presentation-only. Menggunakan kembali DetailUnitProvider yang
 * sudah ada — TIDAK ada query, service, tracking, atau business logic baru.
 * Tugas halaman ini hanya menyusun UnitDetailData ke dalam urutan kerja
 * coordinator: Hero → Timeline → Informasi Operasional → Pemeriksaan →
 * Riwayat → Dokumen. Bukan halaman CRUD, bukan detail model Laravel.
 */
class UnitWorkspace extends Page
{
    protected static string $resource = ShipmentTrackingResource::class;

    protected static string $view = 'filament.resources.shipment-tracking-resource.pages.unit-workspace';

    protected ?string $maxContentWidth = 'full';

    /** Route param — public agar bertahan lintas round-trip Livewire. */
    public int $unitId;

    /**
     * Protected: UnitDetailData (readonly VM) tidak diserialisasi Livewire —
     * sama seperti WorkspaceShell. Direkomputasi di getViewData() bila hilang.
     */
    protected ?UnitDetailData $detail = null;

    public function mount(int $unitId): void
    {
        $this->unitId = $unitId;
        $this->detail = $this->loadDetail();

        // Unit tidak ada / di luar scope cabang → 404 (branch guard ada di
        // DetailUnitProvider → UnitDetailData::empty()).
        abort_if($this->detail->unit_id === 0, 404);
    }

    /** Panel middleware sudah membatasi ke office user; ini defense-in-depth. */
    public static function canAccess(array $parameters = []): bool
    {
        return (bool) (auth_user()?->isOfficeUser() ?? false);
    }

    public function getTitle(): string
    {
        return $this->detail && $this->detail->unit_id !== 0
            ? 'Unit ' . $this->detail->unit_reg_no
            : 'Detail Unit';
    }

    public function getBreadcrumb(): string
    {
        return 'Detail Unit';
    }

    protected function getViewData(): array
    {
        // Recompute jika round-trip Livewire menghapus VM protected.
        $this->detail ??= $this->loadDetail();

        return ['detail' => $this->detail];
    }

    private function loadDetail(): UnitDetailData
    {
        $user = auth_user();
        $branchId = ($user && $user->isOfficeAdmin()) ? $user->effectiveBranchId() : null;

        return app(DetailUnitProvider::class)->provide($this->unitId, $branchId);
    }
}
