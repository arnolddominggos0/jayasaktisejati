<?php

namespace App\Filament\FC\Pages;

use App\Enums\ContainerAllocationType;
use App\Models\Container;
use App\Models\ContainerReadinessSession;
use App\Models\Unit;
use App\Models\UnitInspection;
use App\Services\ContainerAllocation\ContainerAllocationService;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ContainerAllocationWorkspace extends Page implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    protected static ?string $navigationGroup = 'Operasional Lapangan';

    protected static ?string $navigationLabel = 'Alokasi Container';

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?int $navigationSort = 11;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.fc.pages.container-allocation-workspace';

    public function getTitle(): string
    {
        return 'Alokasi Container';
    }

    public function getSubheading(): ?string
    {
        return 'Tentukan unit ini masuk ke container yang mana, sebelum diserahkan ke tim Stuffing.';
    }

    // ── Scope (mengikuti pola ScopeByBranchAndDepot yang sudah ada) ──────────

    protected function depotId(): ?int
    {
        return app()->bound('scope.depot_id') ? (int) app('scope.depot_id') : null;
    }

    public function getUnallocatedUnits(): Collection
    {
        $depotId = $this->depotId();

        return Unit::query()
            ->whereNull('container_id')
            ->whereHas('shipment', function (Builder $q) use ($depotId) {
                if ($depotId) {
                    $q->where('assigned_depot_id', $depotId);
                }
            })
            ->whereDoesntHave('inspections', function (Builder $q) {
                $q->where('stage', 'handover_depot')
                    ->where('gate_decision', UnitInspection::GATE_RETURN_TO_PDC);
            })
            ->with(['shipment:id,code,destination_city_id', 'shipment.destinationCity:id,name'])
            ->orderBy('id')
            ->get();
    }

    protected function currentSession(): ?ContainerReadinessSession
    {
        return ContainerReadinessSession::query()
            ->whereDate('session_date', today())
            ->first();
    }

    /**
     * Resolusi lazy + tervalidasi Readiness untuk aksi (titik satu-satunya
     * di mana baris anotasi Container boleh tercipta).
     */
    protected function resolveContainer(string $containerNo): Container
    {
        $session = $this->currentSession();

        if (! $session) {
            throw new \DomainException('Belum ada Container Readiness untuk hari ini.');
        }

        return Container::resolveForSession($session, $containerNo);
    }
    public function getContainers(): Collection
    {
        $session = $this->currentSession();

        if (! $session) {
            return collect();
        }

        $annotations = Container::query()
            ->where('container_readiness_session_id', $session->id)
            ->with(['units' => fn ($q) => $q->with('shipment:id,code')])
            ->get()
            ->keyBy('container_no');

        return collect($session->container_number_list)
            ->map(fn (string $no) => $annotations->get($no) ?? new Container([
                'container_readiness_session_id' => $session->id,
                'container_no' => $no,
            ]));
    }
    public function getShipmentsReadyForStuffing(): Collection
    {
        $rows = $this->getContainers()
            ->filter(fn (Container $c) => $c->exists)
            ->map(fn (Container $c) => ['container' => $c, 'shipment' => $c->shipment()])
            ->filter(fn (array $row) => $row['shipment'] !== null);

        return $rows
            ->groupBy(fn (array $row) => $row['shipment']->id)
            ->filter(fn (Collection $group) => $group->every(fn (array $row) => $row['container']->is_ready_for_stuffing))
            ->map(fn (Collection $group) => $group->first()['shipment'])
            ->values();
    }

    protected function getViewData(): array
    {
        return [
            'unallocatedUnits' => $this->getUnallocatedUnits(),
            'containers' => $this->getContainers(),
            'shipmentsReadyForStuffing' => $this->getShipmentsReadyForStuffing(),
        ];
    }

    public function configureContainerAction(): Action
    {
        return Action::make('configureContainer')
            ->label('Lengkapi Tipe & Kapasitas')
            ->modalHeading('Lengkapi Container')
            ->form([
                Select::make('type')
                    ->label('Tipe')
                    ->options([
                        ContainerAllocationType::Rack->value => ContainerAllocationType::Rack->label(),
                        ContainerAllocationType::Regular->value => ContainerAllocationType::Regular->label(),
                    ])
                    ->required(),
                TextInput::make('capacity')
                    ->label('Kapasitas (jumlah unit)')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
            ])
            ->fillForm(function (array $arguments): array {
                $session = $this->currentSession();
                $container = $session
                    ? Container::query()
                        ->where('container_readiness_session_id', $session->id)
                        ->where('container_no', $arguments['containerNo'])
                        ->first()
                    : null;

                return [
                    'type' => $container?->type?->value,
                    'capacity' => $container?->capacity,
                ];
            })
            ->action(function (array $data, array $arguments) {
                $this->runService(function () use ($data, $arguments) {
                    $this->resolveContainer($arguments['containerNo'])->update([
                        'type' => $data['type'],
                        'capacity' => $data['capacity'],
                    ]);
                });
            });
    }

    /** Action: Masukkan ke Container. */
    public function assignToContainerAction(): Action
    {
        return Action::make('assignToContainer')
            ->label('Masukkan ke Container')
            ->modalHeading('Masukkan Unit ke Container')
            ->form([
                Select::make('container_no')
                    ->label('Container')
                    ->options(fn () => $this->availableContainerOptions())
                    ->required(),
            ])
            ->action(function (array $data, array $arguments) {
                $this->runService(function (ContainerAllocationService $service) use ($data, $arguments) {
                    $service->assign(
                        Unit::findOrFail($arguments['unit']),
                        $this->resolveContainer($data['container_no']),
                    );
                });
            });
    }

    /** Action: Pindahkan Container. */
    public function moveToContainerAction(): Action
    {
        return Action::make('moveToContainer')
            ->label('Pindahkan Container')
            ->modalHeading('Pindahkan Unit ke Container Lain')
            ->form([
                Select::make('container_no')
                    ->label('Container Tujuan')
                    ->options(fn (array $arguments) => $this->availableContainerOptions((int) $arguments['unit']))
                    ->required(),
            ])
            ->action(function (array $data, array $arguments) {
                $this->runService(function (ContainerAllocationService $service) use ($data, $arguments) {
                    $service->move(
                        Unit::findOrFail($arguments['unit']),
                        $this->resolveContainer($data['container_no']),
                    );
                });
            });
    }

    /** Action: Keluarkan dari Container. */
    public function removeFromContainerAction(): Action
    {
        return Action::make('removeFromContainer')
            ->label('Keluarkan dari Container')
            ->requiresConfirmation()
            ->color('danger')
            ->action(function (array $arguments) {
                $this->runService(function (ContainerAllocationService $service) use ($arguments) {
                    $service->remove(Unit::findOrFail($arguments['unit']));
                });
            });
    }

    /** Action: Tandai Container Siap Stuffing. */
    public function markContainerReadyAction(): Action
    {
        return Action::make('markContainerReady')
            ->label('Tandai Siap Stuffing')
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription('Seluruh unit di container ini akan ditandai Siap Stuffing dan tidak bisa diubah tanpa membatalkan tanda ini terlebih dahulu.')
            ->action(function (array $arguments) {
                $this->runService(function (ContainerAllocationService $service) use ($arguments) {
                    $service->markContainerReady($this->resolveContainer($arguments['containerNo']), auth()->user());
                });
            });
    }

    /** Pembalikan (jalur eksepsi) — lihat Domain Freeze §8. */
    public function unmarkContainerReadyAction(): Action
    {
        return Action::make('unmarkContainerReady')
            ->label('Batalkan Tanda Siap')
            ->color('gray')
            ->requiresConfirmation()
            ->action(function (array $arguments) {
                $this->runService(function (ContainerAllocationService $service) use ($arguments) {
                    $service->unmarkContainerReady($this->resolveContainer($arguments['containerNo']));
                });
            });
    }
    private function availableContainerOptions(?int $excludingCurrentContainerOfUnit = null): array
    {
        $excludeNo = null;

        if ($excludingCurrentContainerOfUnit) {

            $excludeNo = Unit::find($excludingCurrentContainerOfUnit)?->container?->container_no;
        }

        return $this->getContainers()
            ->filter(fn (Container $c) => $c->container_no !== $excludeNo)
            ->filter(fn (Container $c) => $c->type !== null && $c->capacity !== null)
            ->filter(fn (Container $c) => ! $c->is_ready_for_stuffing)
            ->filter(fn (Container $c) => ! $c->isFull())
            ->mapWithKeys(fn (Container $c) => [
                $c->container_no => "{$c->container_no} ({$c->type->label()}) — sisa {$c->remainingCapacity()} slot",
            ])
            ->all();
    }

    private function runService(\Closure $callback): void
    {
        try {
            $callback(app(ContainerAllocationService::class));

            Notification::make()->success()->title('Berhasil diperbarui')->send();
        } catch (DomainException $e) {
            Notification::make()->danger()->title('Tidak bisa dilanjutkan')->body($e->getMessage())->send();
        }
    }
}
