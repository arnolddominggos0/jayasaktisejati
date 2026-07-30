<?php

namespace App\Filament\FC\Pages;

use App\Models\Container;
use App\Models\Shipment;
use App\Models\Unit;
use App\Services\DailyBriefingGate;
use App\Services\Stuffing\StuffingService;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class StuffingWorkspace extends Page implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    protected static ?string $navigationGroup = 'Operasional Lapangan';

    protected static ?string $navigationLabel = 'Stuffing';

    protected static ?string $navigationIcon = 'heroicon-o-cube-transparent';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.fc.pages.stuffing-workspace';

    public ?int $shipmentId = null;

    public function mount(): void
    {
        $this->shipmentId = request()->integer('shipment') ?: null;
    }

    public function getTitle(): string
    {
        return 'Stuffing';
    }

    protected function depotId(): ?int
    {
        return app()->bound('scope.depot_id') ? (int) app('scope.depot_id') : null;
    }

    // ── Data untuk view ───────────────────────────────────────────────────────

    public function getCandidateShipments(): Collection
    {
        $depotId = $this->depotId();

        return Shipment::query()
            ->when($depotId, fn (Builder $q) => $q->where('assigned_depot_id', $depotId))
            ->whereNotIn('status', ['draft', 'delivered', 'cancelled'])
            ->orderBy('code')
            ->get(['id', 'code', 'assigned_depot_id', 'status']);
    }

    public function getSelectedShipment(): ?Shipment
    {
        return $this->shipmentId ? Shipment::find($this->shipmentId) : null;
    }

    public function getPreconditions(): ?array
    {
        $shipment = $this->getSelectedShipment();

        return $shipment ? app(StuffingService::class)->checkPreconditions($shipment) : null;
    }

    public function getBriefingBlockReason(): ?string
    {
        $shipment = $this->getSelectedShipment();
        $depotId = $shipment?->assigned_depot_id;

        return $depotId ? DailyBriefingGate::blockReason($depotId) : null;
    }

    public function getContainers(): Collection
    {
        $shipment = $this->getSelectedShipment();

        if (! $shipment) {
            return collect();
        }

        return Container::query()
            ->where('is_ready_for_stuffing', true)
            ->whereHas('units', fn ($q) => $q->where('shipment_id', $shipment->id))
            ->with(['units' => fn ($q) => $q->where('shipment_id', $shipment->id)->orderBy('id')])
            ->orderBy('container_no')
            ->get();
    }

    public function getShipmentSummary(): ?array
    {
        $shipment = $this->getSelectedShipment();

        return $shipment ? app(StuffingService::class)->shipmentStuffingSummary($shipment) : null;
    }

    protected function getViewData(): array
    {
        return [
            'candidateShipments' => $this->getCandidateShipments(),
            'selectedShipment' => $this->getSelectedShipment(),
            'preconditions' => $this->getPreconditions(),
            'containers' => $this->getContainers(),
            'summary' => $this->getShipmentSummary(),
        ];
    }

    // ── Actions ──────────────────────────────────────────────────────────────

    public function selectShipment(?int $shipmentId): void
    {
        $this->shipmentId = $shipmentId;
    }

    /** Action: operator menandai satu unit sudah masuk container. */
    public function markUnitStuffedAction(): Action
    {
        return Action::make('markUnitStuffed')
            ->label('Tandai Masuk Container')
            ->modalHeading('Tandai Unit Masuk Container')
            ->form([
                Textarea::make('remarks')
                    ->label('Catatan (opsional)')
                    ->rows(2),
            ])
            ->action(function (array $data, array $arguments) {
                $this->runService(function (StuffingService $service) use ($data, $arguments) {
                    $service->markUnitStuffed(
                        Unit::findOrFail($arguments['unit']),
                        auth()->user(),
                        $data['remarks'] ?: null,
                    );
                });
            });
    }

    /** Koreksi — operator salah tandai. */
    public function unmarkUnitStuffedAction(): Action
    {
        return Action::make('unmarkUnitStuffed')
            ->label('Batalkan Tanda')
            ->color('gray')
            ->requiresConfirmation()
            ->action(function (array $arguments) {
                $this->runService(function (StuffingService $service) use ($arguments) {
                    $service->unmarkUnitStuffed(Unit::findOrFail($arguments['unit']));
                });
            });
    }

    private function runService(\Closure $callback): void
    {
        try {
            $callback(app(StuffingService::class));

            Notification::make()->success()->title('Berhasil diperbarui')->send();
        } catch (DomainException $e) {
            Notification::make()->danger()->title('Tidak bisa dilanjutkan')->body($e->getMessage())->send();
        }
    }
}
