<?php

namespace App\Filament\Resources\ShipmentResource\Widgets;

use App\Enums\ShipmentStatus;
use App\Filament\Resources\ShipmentResource;
use App\Models\Shipment;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Str;

class RecentShipmentActivities extends Widget
{
    protected static string $view = 'filament.widgets.recent-shipment-activities';
    protected int|string|array $columnSpan = 3;
    protected static ?string $pollingInterval = '30s';

    private const EVENTS = [
        'created',
        'updated',
        'status_changed',
        'cancelled',
        'uncancelled',
        'route_updated',
        'deleted',
        'restored',
    ];

    private function eventMeta(string $event): array
    {
        $map = [
            'created'        => ['Dibuat',           'bg-emerald-500', 'heroicon-m-plus-circle'],
            'updated'        => ['Diubah',           'bg-sky-500',     'heroicon-m-pencil-square'],
            'status_changed' => ['Status diubah',    'bg-indigo-500',  'heroicon-m-adjustments-horizontal'],
            'route_updated'  => ['Rute diubah',      'bg-violet-500',  'heroicon-m-arrows-right-left'],
            'cancelled'      => ['Dibatalkan',       'bg-rose-500',    'heroicon-m-x-circle'],
            'uncancelled'    => ['Dipulihkan',       'bg-zinc-400',    'heroicon-m-arrow-path'],
            'deleted'        => ['Dihapus',          'bg-red-500',     'heroicon-m-trash'],
            'restored'       => ['Dipulihkan',       'bg-green-500',   'heroicon-m-arrow-uturn-left'],
        ];

        return $map[$event] ?? ['Diperbarui', 'bg-gray-400', 'heroicon-m-clock'];
    }

    public static function badgeColor(?string $status): string
    {
        return match ($status) {
            'draft'                 => 'gray',
            'pending', 'hold'       => 'warning',
            'pickup', 'transit'     => 'info',
            'delivered'             => 'success',
            'cancelled'             => 'danger',
            default                 => 'gray',
        };
    }

    private function chipClass(string $key): string
    {
        return match ($key) {
            'danger'  => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
            'success' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
            'warning' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
            'info'    => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
            default   => 'bg-gray-100 text-gray-700 dark:bg-slate-800 dark:text-slate-300',
        };
    }

    protected function getViewData(): array
    {
        $rows = Activity::query()
            ->where('log_name', 'permintaan_pengiriman')
            ->where('subject_type', Shipment::class)
            ->whereIn('event', self::EVENTS)
            ->with(['causer', 'subject'])
            ->latest('created_at')
            ->limit(30)
            ->get();

        $groups = [];

        foreach ($rows as $act) {
            /** @var Activity $act */
            $ship   = $act->subject instanceof Shipment ? $act->subject : null;
            $props  = $act->properties?->toArray() ?? [];
            $event  = (string) $act->event;
            [$eventLabel, $dotClass, $icon] = $this->eventMeta($event);

            $code   = $ship?->code ?? ($props['code'] ?? '-');
            $user   = $act->causer?->name ?? 'Sistem';
            $initial = Str::of($user)->trim()->substr(0, 1)->upper();
            $editUrl = $ship
                ? ShipmentResource::getUrl('edit', ['record' => $ship->getKey()])
                : route('filament.admin.resources.shipments.index');

            $to   = $props['to']      ?? ($props['status'] ?? null);
            $from = $props['from']    ?? null;

            $toLabel = $props['to_label']
                ?? (ShipmentStatus::tryFrom((string) $to)?->label() ?? ($to ? Str::upper((string) $to) : null));

            $fromLabel = $props['from_label']
                ?? (ShipmentStatus::tryFrom((string) $from)?->label() ?? ($from ? Str::upper((string) $from) : null));

            $showStatus = in_array($event, ['created', 'status_changed', 'cancelled', 'uncancelled'], true) && $to;

            $chipColorKey = self::badgeColor(is_string($to) ? $to : null);
            $chipClass    = $this->chipClass($chipColorKey);

            $changed     = $props['changed_fields'] ?? [];
            $changedText = $changed ? implode(', ', array_slice($changed, 0, 6)) . (count($changed) > 6 ? ', …' : '') : null;

            $ts = Carbon::parse($act->created_at);
            $groupKey = $ts->toDateString(); // yyyy-mm-dd
            $groupTitle = $ts->isToday() ? 'Hari ini'
                : ($ts->isYesterday() ? 'Kemarin' : $ts->isoFormat('D MMMM YYYY'));

            $groups[$groupKey]['title'] = $groupTitle;
            $groups[$groupKey]['items'][] = [
                'event'        => $event,
                'eventLabel'   => $eventLabel,
                'dotClass'     => $dotClass,
                'icon'         => $icon,
                'code'         => $code,
                'user'         => $user,
                'initial'      => (string) $initial,
                'editUrl'      => $editUrl,
                'showStatus'   => $showStatus,
                'toLabel'      => $toLabel,
                'fromLabel'    => $fromLabel,
                'chipClass'    => $chipClass,
                'changedText'  => $event === 'updated' ? $changedText : null,
                'calendarTime' => $ts = \Carbon\Carbon::parse($act->created_at)->locale('id'),
                'fullTime'     => $ts->translatedFormat('d F Y, H:i'),
            ];
        }

        krsort($groups);

        return [
            'groups' => $groups,
        ];
    }
}
