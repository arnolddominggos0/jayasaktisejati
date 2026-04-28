<?php

namespace App\Services;

use App\Enums\ShipmentStatus;
use App\Models\AppSheetSyncLog;
use App\Models\BriefingAttendance;
use App\Models\BriefingAttendancePpeItem;
use App\Models\BriefingChecklist;
use App\Models\BriefingSession;
use App\Models\Depot;
use App\Models\EquipmentCheck;
use App\Models\LoadingFinding;
use App\Models\LoadingSession;
use App\Models\RackContainerCheck;
use App\Models\Shipment;
use App\Models\UnitCheck;
use App\Models\User;
use DomainException;
use Exception;
use Illuminate\Support\Facades\Log;

class AppSheetService
{
    protected $apiKey;

    protected $appAccessKey;

    protected $applicationId;

    protected $baseUrl;

    protected $loggingEnabled;

    public function __construct()
    {
        $this->apiKey = config('appsheet.api_key');
        $this->appAccessKey = config('appsheet.app_access_key');
        $this->applicationId = config('appsheet.application_id');
        $this->baseUrl = config('appsheet.base_url');
        $this->loggingEnabled = config('appsheet.logging_enabled', true);
    }

    public function syncFromWebhook(string $tableName, array $data, string $operation = 'create', ?int $submittedByUserId = null)
    {
        $log = $this->createSyncLog('webhook', $tableName, $data['id'] ?? null, $operation, $data, $submittedByUserId);

        try {
            $mappedData = $this->mapFields($tableName, $data, $submittedByUserId);

            if ($submittedByUserId !== null) {
                $this->validateFcScope($tableName, $mappedData, $submittedByUserId);
            }

            $this->validateShipmentStatusGuard($tableName, $mappedData);

            $result = match ($tableName) {
                'briefing_sessions' => $this->syncBriefingSession($data, $operation, $submittedByUserId),
                'briefing_attendances' => $this->syncBriefingAttendance($data, $operation, $submittedByUserId),
                'briefing_attendance_ppe_items' => $this->syncBriefingPpeItem($data, $operation, $submittedByUserId),
                'briefing_checklists' => $this->syncBriefingChecklist($data, $operation, $submittedByUserId),
                'loading_sessions' => $this->syncLoadingSession($data, $operation, $submittedByUserId),
                'rack_container_checks' => $this->syncRackContainerCheck($data, $operation, $submittedByUserId),
                'equipment_checks' => $this->syncEquipmentCheck($data, $operation, $submittedByUserId),
                'unit_checks' => $this->syncUnitCheck($data, $operation, $submittedByUserId),
                'loading_findings' => $this->syncLoadingFinding($data, $operation, $submittedByUserId),
                default => throw new Exception("Unknown table: {$tableName}"),
            };

            $afterSync = config("appsheet.tables.{$tableName}.after_sync");
            if ($afterSync === 'recalculate_briefing_session') {
                $this->recalculateBriefingSession($tableName, $result);
            }

            $log->markAsSuccess(['result' => $result->toArray() ?? $result]);

            if ($this->loggingEnabled) {
                Log::channel('appsheet')->info("Sync successful: {$tableName}", ['record_id' => $data['id'] ?? null]);
            }

            return ['success' => true, 'data' => $result];
        } catch (Exception $e) {
            $log->markAsFailed($e->getMessage());

            if ($this->loggingEnabled) {
                Log::channel('appsheet')->error("Sync failed: {$tableName}", [
                    'error' => $e->getMessage(),
                    'data' => $data,
                    'submitted_by_user_id' => $submittedByUserId,
                ]);
            }

            throw $e;
        }
    }

    protected function syncBriefingSession(array $data, string $operation, ?int $submittedByUserId = null)
    {
        $mappedData = $this->mapFields('briefing_sessions', $data, $submittedByUserId);
        $date = $mappedData['date'] ?? null;
        $depotId = $mappedData['depot_id'] ?? null;

        if (! $date || ! $depotId) {
            throw new Exception('Tanggal dan Depot ID wajib diisi untuk Briefing Session');
        }

        Depot::where('id', $depotId)->exists()
            || throw new Exception("Depot ID {$depotId} tidak ditemukan");

        return match ($operation) {
            'create' => BriefingSession::firstOrCreate(
                ['date' => $date, 'depot_id' => $depotId],
                $mappedData
            ),
            'update' => BriefingSession::updateOrCreate(
                ['date' => $date, 'depot_id' => $depotId],
                $mappedData
            ),
            'delete' => BriefingSession::where('date', $date)
                ->where('depot_id', $depotId)->delete(),
            default => throw new Exception("Unknown operation: {$operation}"),
        };
    }

    protected function syncBriefingAttendance(array $data, string $operation, ?int $submittedByUserId = null)
    {
        $mappedData = $this->mapFields('briefing_attendances', $data, $submittedByUserId);
        $sessionId = $mappedData['session_id'] ?? null;
        $manpowerId = $mappedData['manpower_id'] ?? null;

        if (! $sessionId || ! $manpowerId) {
            throw new Exception('Session ID dan Manpower ID wajib diisi untuk Briefing Attendance');
        }

        BriefingSession::where('id', $sessionId)->exists()
            || throw new Exception("Briefing Session ID {$sessionId} tidak ditemukan");

        return match ($operation) {
            'create' => BriefingAttendance::firstOrCreate(
                ['session_id' => $sessionId, 'manpower_id' => $manpowerId],
                $mappedData
            ),
            'update' => BriefingAttendance::updateOrCreate(
                ['session_id' => $sessionId, 'manpower_id' => $manpowerId],
                $mappedData
            ),
            'delete' => BriefingAttendance::where('session_id', $sessionId)
                ->where('manpower_id', $manpowerId)->delete(),
            default => throw new Exception("Unknown operation: {$operation}"),
        };
    }

    protected function syncBriefingPpeItem(array $data, string $operation, ?int $submittedByUserId = null)
    {
        $mappedData = $this->mapFields('briefing_attendance_ppe_items', $data, $submittedByUserId);

        $attendanceId = $mappedData['attendance_id']
            ?? $data['attendance_id']
            ?? null;

        if (!$attendanceId) {
            throw new \Exception('Attendance ID wajib diisi untuk PPE Item');
        }

        $attendance = BriefingAttendance::find($attendanceId);

        if (!$attendance) {
            throw new \Exception("Attendance ID {$attendanceId} tidak ditemukan");
        }

        $ppeType = $mappedData['ppe_type'] ?? $data['ppe_type'] ?? null;
        $status  = $mappedData['status'] ?? $data['status'] ?? null;
        $catatan = $mappedData['catatan'] ?? $data['catatan'] ?? null;

        if (!$ppeType) {
            throw new \Exception('PPE Type wajib diisi');
        }

        if (!$status) {
            throw new \Exception('Status wajib diisi');
        }

        $finalData = [
            'attendance_id' => $attendanceId,
            'ppe_type'      => $ppeType,
            'status'        => $status,
            'catatan'       => $catatan,
        ];

        return match ($operation) {
            'create' => BriefingAttendancePpeItem::create($finalData),

            'update' => BriefingAttendancePpeItem::updateOrCreate(
                [
                    'attendance_id' => $attendanceId,
                    'ppe_type'      => $ppeType,
                ],
                $finalData
            ),

            'delete' => $this->deletePpeItem($mappedData, $data),

            default => throw new \Exception("Unknown operation: {$operation}"),
        };
    }

    protected function deletePpeItem(array $mappedData, array $data)
    {
        $id = $mappedData['id'] ?? $data['id'] ?? null;

        if (!$id) {
            throw new \Exception('ID wajib diisi untuk delete PPE item');
        }

        return BriefingAttendancePpeItem::where('id', $id)->delete();
    }

    protected function syncBriefingChecklist(array $data, string $operation, ?int $submittedByUserId = null)
    {
        $mappedData = $this->mapFields('briefing_checklists', $data, $submittedByUserId);
        $sessionId = $mappedData['session_id'] ?? null;
        $item = $mappedData['item'] ?? null;

        if (! $sessionId || ! $item) {
            throw new Exception('Session ID dan Item wajib diisi untuk Briefing Checklist');
        }

        BriefingSession::where('id', $sessionId)->exists()
            || throw new Exception("Briefing Session ID {$sessionId} tidak ditemukan");

        return match ($operation) {
            'create' => BriefingChecklist::firstOrCreate(
                ['session_id' => $sessionId, 'item' => $item],
                $mappedData
            ),
            'update' => BriefingChecklist::updateOrCreate(
                ['session_id' => $sessionId, 'item' => $item],
                $mappedData
            ),
            'delete' => BriefingChecklist::where('session_id', $sessionId)
                ->where('item', $item)->delete(),
            default => throw new Exception("Unknown operation: {$operation}"),
        };
    }

    protected function syncLoadingSession(array $data, string $operation, ?int $submittedByUserId = null)
    {
        $mappedData = $this->mapFields('loading_sessions', $data, $submittedByUserId);

        return match ($operation) {
            'create' => LoadingSession::firstOrCreate(
                ['code' => $mappedData['code']],
                $mappedData
            ),
            'update' => LoadingSession::updateOrCreate(['code' => $mappedData['code']], $mappedData),
            'delete' => LoadingSession::where('code', $mappedData['code'])->delete(),
            default => throw new Exception("Unknown operation: {$operation}"),
        };
    }

    protected function syncRackContainerCheck(array $data, string $operation, ?int $submittedByUserId = null)
    {
        $mappedData = $this->mapFields('rack_container_checks', $data, $submittedByUserId);
        $loadingSessionId = $mappedData['loading_session_id'] ?? null;

        if (! $loadingSessionId) {
            throw new Exception('Loading Session ID is required');
        }

        return match ($operation) {
            'create' => RackContainerCheck::create($mappedData),
            'update' => RackContainerCheck::updateOrCreate(['loading_session_id' => $loadingSessionId], $mappedData),
            'delete' => RackContainerCheck::where('loading_session_id', $loadingSessionId)->delete(),
            default => throw new Exception("Unknown operation: {$operation}"),
        };
    }

    protected function syncEquipmentCheck(array $data, string $operation, ?int $submittedByUserId = null)
    {
        $mappedData = $this->mapFields('equipment_checks', $data, $submittedByUserId);
        $loadingSessionId = $mappedData['loading_session_id'] ?? null;

        if (! $loadingSessionId) {
            throw new Exception('Loading Session ID is required');
        }

        return match ($operation) {
            'create' => EquipmentCheck::create($mappedData),
            'update' => EquipmentCheck::updateOrCreate(['loading_session_id' => $loadingSessionId], $mappedData),
            'delete' => EquipmentCheck::where('loading_session_id', $loadingSessionId)->delete(),
            default => throw new Exception("Unknown operation: {$operation}"),
        };
    }

    protected function syncUnitCheck(array $data, string $operation, ?int $submittedByUserId = null)
    {
        $mappedData = $this->mapFields('unit_checks', $data, $submittedByUserId);
        $loadingSessionId = $mappedData['loading_session_id'] ?? null;

        if (! $loadingSessionId) {
            throw new Exception('Loading Session ID is required');
        }

        return match ($operation) {
            'create' => UnitCheck::create($mappedData),
            'update' => UnitCheck::updateOrCreate(['loading_session_id' => $loadingSessionId], $mappedData),
            'delete' => UnitCheck::where('loading_session_id', $loadingSessionId)->delete(),
            default => throw new Exception("Unknown operation: {$operation}"),
        };
    }

    protected function syncLoadingFinding(array $data, string $operation, ?int $submittedByUserId = null)
    {
        $mappedData = $this->mapFields('loading_findings', $data, $submittedByUserId);

        return match ($operation) {
            'create' => LoadingFinding::create($mappedData),
            'update' => LoadingFinding::updateOrCreate(['id' => $mappedData['id']], $mappedData),
            'delete' => LoadingFinding::where('id', $mappedData['id'])->delete(),
            default => throw new Exception("Unknown operation: {$operation}"),
        };
    }

    protected function mapFields(string $table, array $data, ?int $submittedByUserId = null): array
    {
        $config = config("appsheet.tables.{$table}");

        if (! $config) {
            throw new Exception("Table configuration not found: {$table}");
        }

        $mapped = [];
        foreach ($config['fields'] as $laravelField => $appSheetField) {
            if (array_key_exists($appSheetField, $data)) {
                $mapped[$laravelField] = $data[$appSheetField];
            }
        }

        $user = auth()->id() ?? $submittedByUserId ?? 1;
        $mapped['created_by'] = $user;
        if (! in_array($table, ['briefing_attendances', 'briefing_attendance_ppe_items', 'briefing_checklists'])) {
            $mapped['checked_by'] = $user;
        }

        return $mapped;
    }

    protected function validateFcScope(string $tableName, array $mappedData, int $submittedByUserId): void
    {
        $user = User::find($submittedByUserId);

        if (! $user || ! $user->hasRole('field_coordinator')) {
            throw new DomainException('Pengguna tidak memiliki role Field Coordinator.');
        }

        $branchId = null;
        $depotId = null;

        match ($tableName) {
            'briefing_sessions' => (function () use ($mappedData, &$branchId, &$depotId) {
                $depotId = $mappedData['depot_id'] ?? null;
                if ($depotId) {
                    $depot = Depot::find($depotId);
                    $branchId = $depot?->branch_id;
                }
            })(),

            'briefing_attendances', 'briefing_checklists' => (function () use ($mappedData, &$branchId, &$depotId) {
                $sessionId = $mappedData['session_id'] ?? null;
                if ($sessionId) {
                    $session = BriefingSession::with('depot')->find($sessionId);
                    $depotId = $session?->depot_id;
                    $branchId = $session?->depot?->branch_id;
                }
            })(),

            'briefing_attendance_ppe_items' => (function () use ($mappedData, &$branchId, &$depotId) {
                $attendanceId = $mappedData['attendance_id'] ?? null;
                if ($attendanceId) {
                    $attendance = BriefingAttendance::with('session.depot')->find($attendanceId);
                    $depotId = $attendance?->session?->depot_id;
                    $branchId = $attendance?->session?->depot?->branch_id;
                }
            })(),

            'loading_sessions', 'loading_findings' => (function () use ($mappedData, &$branchId, &$depotId) {
                $branchId = $mappedData['branch_id'] ?? null;
                $depotId = $mappedData['depot_id'] ?? null;
            })(),

            'rack_container_checks', 'equipment_checks', 'unit_checks' => (function () use ($mappedData, &$branchId, &$depotId) {
                $loadingSessionId = $mappedData['loading_session_id'] ?? null;
                if ($loadingSessionId) {
                    $session = LoadingSession::find($loadingSessionId);
                    $branchId = $session?->branch_id;
                    $depotId = $session?->depot_id;
                }
            })(),

            default => null,
        };

        // Reject impersonation: mapped coordinator must match the submitting user.
        $mappedCoordinatorId = $mappedData['coordinator_user_id'] ?? null;
        if ($mappedCoordinatorId !== null && (int) $mappedCoordinatorId !== (int) $user->id) {
            throw new DomainException(
                sprintf(
                    '[IMPERSONATION_REJECTED] submitted_by_user_id=%d (%s) but payload coordinator_user_id=%d. Table=%s. Koordinator di payload harus sama dengan pengguna yang mengirim.',
                    $user->id,
                    $user->name,
                    $mappedCoordinatorId,
                    $tableName
                )
            );
        }

        $hasCanonicalScope = $user->scope_branch_id !== null
            && $user->scope_unit_id !== null
            && $user->scope_unit_type !== null;

        if ($hasCanonicalScope) {
            // STRICT MODE: payload must align exactly with canonical scope.
            // No fallback to legacy depot/pool lookups or branch-wide access.

            if ($branchId !== null && (int) $branchId !== (int) $user->scope_branch_id) {
                throw new DomainException(
                    sprintf(
                        '[SCOPE_MISMATCH] FC user:%d (%s) canonical branch=%d, but payload targets branch=%d. Table=%s. Harap periksa penugasan FC di Master Depo/Pool.',
                        $user->id,
                        $user->name,
                        $user->scope_branch_id,
                        $branchId,
                        $tableName
                    )
                );
            }

            if ($depotId !== null) {
                if ($user->scope_unit_type !== 'depot' || (int) $depotId !== (int) $user->scope_unit_id) {
                    throw new DomainException(
                        sprintf(
                            '[SCOPE_MISMATCH] FC user:%d (%s) canonical unit=%s:%d, but payload targets depot=%d. Table=%s. Harap periksa penugasan FC di Master Depo/Pool.',
                            $user->id,
                            $user->name,
                            $user->scope_unit_type,
                            $user->scope_unit_id,
                            $depotId,
                            $tableName
                        )
                    );
                }
            }

            return;
        }

        // LEGACY MODE: user has not been backfilled with canonical scope.
        // Allow existing fallback behavior but log deprecation for ops visibility.
        $effectiveBranchId = $user->effectiveBranchId();

        if ($branchId && $effectiveBranchId !== $branchId) {
            throw new DomainException(
                sprintf(
                    '[LEGACY_SCOPE_DENIED] FC user:%d (%s) legacy branch=%d, but payload targets branch=%d. Table=%s. Mohon jalankan backfill scope agar validasi lebih ketat.',
                    $user->id,
                    $user->name,
                    $effectiveBranchId ?? 'null',
                    $branchId,
                    $tableName
                )
            );
        }

        if ($depotId) {
            $hasDepotAccess = Depot::where('id', $depotId)
                ->where(function ($q) use ($user, $effectiveBranchId) {
                    $q->where('coordinator_user_id', $user->id)
                        ->orWhere('branch_id', $effectiveBranchId);
                })
                ->exists();

            if (! $hasDepotAccess) {
                throw new DomainException(
                    sprintf(
                        '[LEGACY_SCOPE_DENIED] FC user:%d (%s) tidak memiliki akses ke depot=%d. Table=%s. Mohon jalankan backfill scope agar validasi lebih ketat.',
                        $user->id,
                        $user->name,
                        $depotId,
                        $tableName
                    )
                );
            }
        }

        if ($this->loggingEnabled) {
            Log::channel('appsheet')->warning("[DEPRECATED] AppSheet payload accepted using legacy scope fallback", [
                'user_id' => $user->id,
                'table' => $tableName,
                'branch_id' => $branchId,
                'depot_id' => $depotId,
                'note' => 'User belum memiliki canonical scope. Jalankan backfill segera.',
            ]);
        }
    }

    protected function validateShipmentStatusGuard(string $tableName, array $mappedData): void
    {
        $shipmentId = null;

        match ($tableName) {
            'loading_sessions', 'loading_findings' => (function () use ($mappedData, &$shipmentId) {
                $shipmentId = $mappedData['shipment_id'] ?? null;
            })(),

            'rack_container_checks', 'equipment_checks', 'unit_checks' => (function () use ($mappedData, &$shipmentId) {
                $loadingSessionId = $mappedData['loading_session_id'] ?? null;
                if ($loadingSessionId) {
                    $shipmentId = LoadingSession::where('id', $loadingSessionId)->value('shipment_id');
                }
            })(),

            default => null,
        };

        if (! $shipmentId) {
            return;
        }

        $shipment = Shipment::find($shipmentId);

        if (! $shipment) {
            throw new DomainException('Shipment tidak ditemukan.');
        }

        if (! in_array($shipment->status, ShipmentStatus::inProgress(), true)) {
            throw new DomainException(
                'Form tidak dapat disubmit untuk shipment dengan status "' . $shipment->status->label() . '".'
            );
        }
    }

    protected function recalculateBriefingSession(string $tableName, $result): void
    {
        $sessionId = null;

        if ($result instanceof BriefingAttendance) {
            $sessionId = $result->session_id;
        } elseif ($result instanceof BriefingAttendancePpeItem && $result->attendance) {
            $sessionId = $result->attendance->session_id;
        } elseif ($result instanceof BriefingChecklist) {
            $sessionId = $result->session_id;
        }

        if (! $sessionId) {
            return;
        }

        $session = BriefingSession::find($sessionId);
        if (! $session) {
            return;
        }

        $present = $session->attendances()
            ->where('attendance_status', 'present')
            ->count();

        $target = (int) $session->summary_headcount;
        $session->summary_sufficient = $target > 0 && $present >= $target;
        $session->saveQuietly();

        if ($this->loggingEnabled) {
            Log::channel('appsheet')->info("Recalculated briefing session #{$sessionId}", [
                'present' => $present,
                'target' => $target,
                'sufficient' => $session->summary_sufficient,
            ]);
        }
    }

    protected function createSyncLog(string $type, string $table, $recordId, string $operation, array $payload, ?int $submittedByUserId = null): AppSheetSyncLog
    {
        $syncedBy = 'system';
        if ($submittedByUserId) {
            $syncedBy = User::where('id', $submittedByUserId)->value('name') ?? "user:{$submittedByUserId}";
        } elseif (auth()->check()) {
            $syncedBy = auth()->user()->name;
        }

        return AppSheetSyncLog::create([
            'sync_type' => $type,
            'table_name' => $table,
            'record_id' => $recordId,
            'operation' => $operation,
            'payload' => $payload,
            'status' => 'pending',
            'source' => 'appsheet',
            'synced_by' => $syncedBy,
        ]);
    }

    public function validateWebhookSignature(string $signature, array $payload): bool
    {
        $secret = config('appsheet.webhook_secret');

        if (empty($secret)) {
            return true;
        }

        $computed = hash_hmac('sha256', json_encode($payload), $secret);

        return hash_equals($computed, $signature);
    }
}
