<?php

declare(strict_types=1);

namespace App\Supports;

use App\Enums\SlaStatus;
use App\Enums\VoyageOperationalStatus;
use App\Models\VoyageCheckpoint;
use App\Models\VoyageMilestone;

/**
 * Centralized operational UI rendering helpers.
 *
 * Provides canonical Tailwind classes, badge HTML, and severity
 * mappings used across the operational monitoring dashboard.
 *
 * DO NOT add business logic here — UI presentation only.
 */
final class OperationalUi
{
    // ═════════════════════════════════════════════════════════════════
    // Severity system
    // ═════════════════════════════════════════════════════════════════

    public static function severityBadge(string $severity): string
    {
        return match ($severity) {
            'critical' => 'bg-red-100 text-red-700 border-red-200',
            'warning'  => 'bg-orange-100 text-orange-700 border-orange-200',
            'success'  => 'bg-emerald-100 text-emerald-700 border-emerald-200',
            'info'     => 'bg-blue-100 text-blue-700 border-blue-200',
            default    => 'bg-gray-100 text-gray-600 border-gray-200',
        };
    }

    public static function severityBorder(string $severity): string
    {
        return match ($severity) {
            'critical' => 'border-l-4 border-l-red-500',
            'warning'  => 'border-l-4 border-l-orange-500',
            'success'  => 'border-l-4 border-l-emerald-500',
            default    => 'border-l-4 border-l-transparent',
        };
    }

    public static function severityLabel(string $severity): string
    {
        return match ($severity) {
            'critical' => 'KRITIS',
            'warning'  => 'PERHATIAN',
            'success'  => 'NORMAL',
            default    => 'NORMAL',
        };
    }

    // ═════════════════════════════════════════════════════════════════
    // Operational status (light variant for dense tables)
    // ═════════════════════════════════════════════════════════════════

    public static function operationalStatusLight(VoyageOperationalStatus $status): array
    {
        return match ($status) {
            VoyageOperationalStatus::SAILING   => ['label' => 'Berlayar',   'class' => 'bg-blue-50 text-blue-700 border-blue-200'],
            VoyageOperationalStatus::COMPLETED => ['label' => 'Selesai',    'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
            VoyageOperationalStatus::DELAYED   => ['label' => 'Terlambat',  'class' => 'bg-red-50 text-red-700 border-red-200'],
            VoyageOperationalStatus::SCHEDULED => ['label' => 'Terjadwal',  'class' => 'bg-gray-50 text-gray-600 border-gray-200'],
        };
    }

    // ═════════════════════════════════════════════════════════════════
    // KPI badge (OK / NG)
    // ═════════════════════════════════════════════════════════════════

    public static function kpiBadge(?SlaStatus $status, string $label): string
    {
        if (! $status) {
            return sprintf(
                '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-400 border border-gray-200">%s —</span>',
                e($label)
            );
        }

        $ok = $status->value === 'ontime';
        $color = $ok
            ? 'bg-emerald-100 text-emerald-700 border-emerald-200'
            : 'bg-red-100 text-red-700 border-red-200';

        return sprintf(
            '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium %s">%s %s</span>',
            $color,
            e($label),
            $ok ? 'OK' : 'NG'
        );
    }

    // ═════════════════════════════════════════════════════════════════
    // Circular indicator (used in matrix cells)
    // ═════════════════════════════════════════════════════════════════

    public static function indicatorClasses(string $type): string
    {
        return match ($type) {
            'success' => 'bg-emerald-100 text-emerald-700 border border-emerald-200',
            'warning' => 'bg-amber-100 text-amber-700 border border-amber-200',
            'danger'  => 'bg-red-100 text-red-700 border border-red-200',
            default   => 'bg-gray-100 text-gray-400 border border-gray-200',
        };
    }

    // ═════════════════════════════════════════════════════════════════
    // Milestone chip
    // ═════════════════════════════════════════════════════════════════

    public static function milestoneChip(VoyageMilestone $m): array
    {
        if ($m->actual_date) {
            $ok = $m->status === 'ontime';
            return [
                'class' => $ok
                    ? 'bg-emerald-100 text-emerald-700 border-emerald-200'
                    : 'bg-red-100 text-red-700 border-red-200',
                'icon'  => $ok ? '✓' : '✕',
                'title' => ($m->port?->name ?? 'Milestone ' . strtoupper($m->code))
                    . ' — Target: ' . optional($m->milestone_date)->format('d M'),
            ];
        }

        if ($m->is_overdue) {
            return [
                'class' => 'bg-red-100 text-red-700 border-red-200',
                'icon'  => '✕',
                'title' => ($m->port?->name ?? 'Milestone ' . strtoupper($m->code))
                    . ' — Target: ' . optional($m->milestone_date)->format('d M'),
            ];
        }

        if ($m->is_due_today) {
            return [
                'class' => 'bg-orange-100 text-orange-700 border-orange-200',
                'icon'  => '⚠',
                'title' => ($m->port?->name ?? 'Milestone ' . strtoupper($m->code))
                    . ' — Target: ' . optional($m->milestone_date)->format('d M'),
            ];
        }

        return [
            'class' => 'bg-gray-100 text-gray-400 border-gray-200',
            'icon'  => '—',
            'title' => ($m->port?->name ?? 'Milestone ' . strtoupper($m->code))
                . ' — Target: ' . optional($m->milestone_date)->format('d M'),
        ];
    }

    // ═════════════════════════════════════════════════════════════════
    // Checkpoint / readiness cell
    // ═════════════════════════════════════════════════════════════════

    public static function checkpointCell(VoyageCheckpoint $cp): array
    {
        if ($cp->is_completed) {
            return ['label' => strtoupper($cp->code) . ' OK', 'state' => 'success'];
        }

        if ($cp->is_late || $cp->scheduled_at?->isPast()) {
            return ['label' => strtoupper($cp->code) . ' OVERDUE', 'state' => 'danger'];
        }

        return ['label' => strtoupper($cp->code) . ' MENUNGGU', 'state' => 'warning'];
    }

    public static function vesselCheckCell(?object $vc): array
    {
        if (! $vc) {
            return ['label' => 'H-1 —', 'state' => 'default'];
        }

        $code = strtoupper($vc->day_code ?? 'H-1');

        return match ($vc->status?->value) {
            'on_schedule'     => ['label' => $code . ' OK',      'state' => 'success'],
            'potential_delay' => ['label' => $code . ' RISIKO',  'state' => 'danger'],
            default           => ['label' => $code . ' —',       'state' => 'default'],
        };
    }

    public static function milestoneIndicatorState(VoyageMilestone $m): string
    {
        if ($m->actual_date) {
            return $m->status === 'ontime' ? 'success' : 'danger';
        }
        if ($m->is_overdue) {
            return 'danger';
        }
        if ($m->is_due_today) {
            return 'warning';
        }
        return 'default';
    }

    // ═════════════════════════════════════════════════════════════════
    // Next-action badge (matrix view)
    // ═════════════════════════════════════════════════════════════════

    public static function readinessDot(?object $d1, ?object $h1): array
    {
        $d1State = 'neutral';
        if ($d1) {
            $d1State = $d1->is_completed ? 'done' : ($d1->is_late || $d1->scheduled_at?->isPast() ? 'overdue' : 'pending');
        }

        $h1State = 'neutral';
        if ($h1) {
            $h1State = match ($h1->status?->value) {
                'on_schedule' => 'done',
                'potential_delay' => 'overdue',
                default => 'pending',
            };
        }

        return [
            'd1' => $d1State,
            'h1' => $h1State,
        ];
    }

    public static function readinessDotClasses(string $state): string
    {
        return match ($state) {
            'done'    => 'bg-emerald-400',
            'overdue' => 'bg-red-500',
            'pending' => 'bg-orange-400',
            default   => 'bg-gray-300',
        };
    }

    public static function nextActionClasses(string $action): string
    {
        return match (true) {
            str_contains($action, 'Terlambat') => 'text-red-700 bg-red-50 border-red-200',
            str_contains($action, 'Risiko')    => 'text-orange-700 bg-orange-50 border-orange-200',
            str_contains($action, 'Input')     => 'text-blue-700 bg-blue-50 border-blue-200',
            default                            => 'text-gray-600 bg-gray-50 border-gray-200',
        };
    }
}
