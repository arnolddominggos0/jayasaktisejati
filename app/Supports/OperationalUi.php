<?php

declare(strict_types=1);

namespace App\Supports;

use App\Enums\SlaStatus;
use App\Enums\VesselCheckLogStatus;
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
            'ok'   => ['label' => $code . ' OK',    'state' => 'success'],
            'late' => ['label' => $code . ' LATE',  'state' => 'danger'],
            default => ['label' => $code . ' —',    'state' => 'default'],
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
                'ok'   => 'done',
                'late' => 'overdue',
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

    // ═════════════════════════════════════════════════════════════════
    // Operational section heading
    // ═════════════════════════════════════════════════════════════════
    // WHY: Section headings with colored dots were duplicated across
    // tam-monitoring-table with hardcoded Tailwind classes.  Now
    // centralized here for consistent dot+label styling.

    public static function sectionHeading(string $label, string $severity): string
    {
        $dot = match ($severity) {
            'critical' => 'bg-red-600',
            'warning'  => 'bg-orange-500',
            'info'     => 'bg-blue-600',
            'caution'  => 'bg-amber-500',
            'normal'   => 'bg-gray-400',
            'success'  => 'bg-emerald-600',
            default    => 'bg-gray-400',
        };

        $text = match ($severity) {
            'critical' => 'text-red-700',
            'warning'  => 'text-orange-700',
            'info'     => 'text-blue-700',
            'caution'  => 'text-amber-700',
            'normal'   => 'text-gray-600',
            'success'  => 'text-emerald-700',
            default    => 'text-gray-600',
        };

        return sprintf(
            '<span class="w-2.5 h-2.5 rounded-full %s"></span><h2 class="font-bold %s uppercase text-sm tracking-wide">%s</h2>',
            $dot,
            $text,
            e($label)
        );
    }

    // ═════════════════════════════════════════════════════════════════
    // Vessel check display (replaces inline status comparison in Blade)
    // ═════════════════════════════════════════════════════════════════
    // WHY: view-voyage.blade.php was comparing $vc->status?->value === 'on_schedule'
    // inline.  Now uses isOnSchedule() and gets canonical display.

    public static function vesselCheckStatusLabel(object $vc): array
    {
        $status = $vc->status ?? null;

        if ($status instanceof \App\Enums\VesselCheckLogStatus) {
            $ok = $status->isOk();
        } else {
            $ok = $status?->value === 'ok';
        }

        return [
            'label' => $ok ? 'OK' : 'Delay',
            'class' => $ok ? 'text-emerald-600' : 'text-red-600',
        ];
    }

    // ═════════════════════════════════════════════════════════════════
    // SlaStatus badge for timeline reuse (replaces inline logic)
    // ═════════════════════════════════════════════════════════════════

    public static function slaStatusDisplay(?SlaStatus $status): array
    {
        if (!$status) {
            return ['icon' => '·', 'color' => 'text-gray-400', 'priority' => 2];
        }

        return match ($status) {
            SlaStatus::ONTIME => ['icon' => '✓', 'color' => 'text-green-600', 'priority' => 1],
            SlaStatus::LATE   => ['icon' => '✗', 'color' => 'text-red-600', 'priority' => 3],
            SlaStatus::RISK   => ['icon' => '⚠', 'color' => 'text-orange-600', 'priority' => 3],
        };
    }

    // ═════════════════════════════════════════════════════════════════
    // Vessel check timeline display (replaces inline match in Blade)
    // ═════════════════════════════════════════════════════════════════

    public static function vesselCheckTimelineState(object $vc): array
    {
        $status = $vc->status ?? null;

        if ($status instanceof \App\Enums\VesselCheckLogStatus) {
            $st = $status->isOk()
                ? ['✓', 'text-green-600', 1]
                : ['!', 'text-orange-600', 3];
        } else {
            $value = $status?->value;
            $st = match ($value) {
                'ok'   => ['✓', 'text-green-600', 1],
                'late' => ['!', 'text-orange-600', 3],
                default => ['·', 'text-gray-400', 2],
            };
        }

        return [
            'state'     => $st[0],
            'color'     => $st[1],
            'priority'  => $st[2],
        ];
    }
}
