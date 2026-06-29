<?php

namespace App\Queries\Monitoring;

use App\DTO\Monitoring\MonitoringFilter;
use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Support\Monitoring\RouteResolver;
use Illuminate\Database\Eloquent\Builder;

final class UnitMonitoringQueryBuilder
{
    public function build(MonitoringFilter $filter): Builder
    {
        $query = Shipment::query();

        $this->applyBaseScope($query, $filter);
        $this->applyModeFilter($query, $filter);
        $this->applyRouteFilter($query, $filter);
        $this->applyShowFinished($query, $filter);
        $this->applyExceptionFilter($query, $filter);
        $this->applySearchMatch($query, $filter);
        $this->applySort($query, $filter);
        $this->applyEagerLoading($query);

        return $query;
    }

    private function applyBaseScope(Builder $q, MonitoringFilter $f): void
    {
        $q->whereNotIn('status', [ShipmentStatus::Draft->value]);

        if ($f->branch_id) {
            $q->where('branch_id', $f->branch_id);
        }
    }

    private function applyModeFilter(Builder $q, MonitoringFilter $f): void
    {
        if ($f->mode) {
            $q->where('mode', $f->mode);
        }
    }

    private function applyRouteFilter(Builder $q, MonitoringFilter $f): void
    {
        $customerIds = RouteResolver::customerIdsForRoute($f->route);
        if ($f->route && $f->route !== 'all' && !empty($customerIds)) {
            $q->whereIn('customer_id', $customerIds);
        }
    }

    private function applyShowFinished(Builder $q, MonitoringFilter $f): void
    {
        if (!$f->show_finished) {
            $q->whereNotIn('status', [
                ShipmentStatus::Delivered->value,
                ShipmentStatus::Cancelled->value,
            ]);
        }
    }

    private function applyExceptionFilter(Builder $q, MonitoringFilter $f): void
    {
        // TODO Sprint 6.2: implement exception-type WHERE EXISTS clauses
    }

    private function applySearchMatch(Builder $q, MonitoringFilter $f): void
    {
        if (blank($f->search)) {
            return;
        }

        $term = '%' . $f->search . '%';

        $q->addSelect([
            'is_search_match' => Shipment::query()
                ->selectRaw('CASE WHEN code ILIKE ? OR doc_number ILIKE ? THEN true ELSE false END', [$term, $term])
                ->whereColumn('id', 'shipments.id')
                ->limit(1),
        ]);
    }

    private function applySort(Builder $q, MonitoringFilter $f): void
    {
        // TODO Sprint 6.2: implement exception-first sort with computed columns
        $q->orderByDesc('created_at');
    }

    private function applyEagerLoading(Builder $q): void
    {
        // TODO Sprint 6.2: full eager loading with column selection
        $q->with([
            'latestTrack:shipment_tracks.id,shipment_tracks.shipment_id,shipment_tracks.status,shipment_tracks.tracked_at',
            'customer:id,name',
            'branch:id,name',
            'originCity:id,name',
            'destinationCity:id,name',
        ]);
    }
}