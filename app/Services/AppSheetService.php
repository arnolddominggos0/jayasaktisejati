<?php

namespace App\Services;

use App\Models\AppSheetSyncLog;
use App\Models\BriefingAttendance;
use App\Models\BriefingAttendancePpeItem;
use App\Models\BriefingChecklist;
use App\Models\BriefingSession;
use App\Models\EquipmentCheck;
use App\Models\LoadingFinding;
use App\Models\LoadingSession;
use App\Models\RackContainerCheck;
use App\Models\UnitCheck;
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

    public function syncFromWebhook(string $tableName, array $data, string $operation = 'create')
    {
        $log = $this->createSyncLog('webhook', $tableName, $data['id'] ?? null, $operation, $data);

        try {
            $result = match ($tableName) {
                'briefing_attendances' => $this->syncBriefingAttendance($data, $operation),
                'briefing_attendance_ppe_items' => $this->syncBriefingPpeItem($data, $operation),
                'briefing_checklists' => $this->syncBriefingChecklist($data, $operation),
                'loading_sessions' => $this->syncLoadingSession($data, $operation),
                'rack_container_checks' => $this->syncRackContainerCheck($data, $operation),
                'equipment_checks' => $this->syncEquipmentCheck($data, $operation),
                'unit_checks' => $this->syncUnitCheck($data, $operation),
                'loading_findings' => $this->syncLoadingFinding($data, $operation),
                default => throw new Exception("Unknown table: {$tableName}"),
            };

            $afterSync = config("appsheet.tables.{$tableName}.after_sync");
            if ($afterSync === 'recalculate_briefing_session') {
                $this->recalculateBriefingSession($tableName, $result);
            }

            $log->markAsSuccess(['result' => $result]);

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
                ]);
            }

            throw $e;
        }
    }

    protected function syncBriefingAttendance(array $data, string $operation)
    {
        $mappedData = $this->mapFields('briefing_attendances', $data);
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

    protected function syncBriefingPpeItem(array $data, string $operation)
    {
        $mappedData = $this->mapFields('briefing_attendance_ppe_items', $data);
        $attendanceId = $mappedData['attendance_id'] ?? null;
        $ppeType = $mappedData['ppe_type'] ?? null;

        if (! $attendanceId) {
            throw new Exception('Attendance ID wajib diisi untuk PPE Item');
        }

        BriefingAttendance::where('id', $attendanceId)->exists()
            || throw new Exception("Briefing Attendance ID {$attendanceId} tidak ditemukan");

        return match ($operation) {
            'create' => BriefingAttendancePpeItem::firstOrCreate(
                array_filter(['attendance_id' => $attendanceId, 'ppe_type' => $ppeType]),
                $mappedData
            ),
            'update' => BriefingAttendancePpeItem::updateOrCreate(
                array_filter(['attendance_id' => $attendanceId, 'ppe_type' => $ppeType]),
                $mappedData
            ),
            'delete' => BriefingAttendancePpeItem::where('id', $mappedData['id'] ?? 0)->delete(),
            default => throw new Exception("Unknown operation: {$operation}"),
        };
    }

    protected function syncBriefingChecklist(array $data, string $operation)
    {
        $mappedData = $this->mapFields('briefing_checklists', $data);
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

    protected function syncLoadingSession(array $data, string $operation)
    {
        $mappedData = $this->mapFields('loading_sessions', $data);

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

    protected function syncRackContainerCheck(array $data, string $operation)
    {
        $mappedData = $this->mapFields('rack_container_checks', $data);
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

    protected function syncEquipmentCheck(array $data, string $operation)
    {
        $mappedData = $this->mapFields('equipment_checks', $data);
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

    protected function syncUnitCheck(array $data, string $operation)
    {
        $mappedData = $this->mapFields('unit_checks', $data);
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

    protected function syncLoadingFinding(array $data, string $operation)
    {
        $mappedData = $this->mapFields('loading_findings', $data);

        return match ($operation) {
            'create' => LoadingFinding::create($mappedData),
            'update' => LoadingFinding::updateOrCreate(['id' => $mappedData['id']], $mappedData),
            'delete' => LoadingFinding::where('id', $mappedData['id'])->delete(),
            default => throw new Exception("Unknown operation: {$operation}"),
        };
    }

    protected function mapFields(string $table, array $data): array
    {
        $config = config("appsheet.tables.{$table}");

        if (! $config) {
            throw new Exception("Table configuration not found: {$table}");
        }

        $mapped = [];
        foreach ($config['fields'] as $laravelField => $appSheetField) {
            if (isset($data[$appSheetField])) {
                $mapped[$laravelField] = $data[$appSheetField];
            }
        }

        $user = auth()->id() ?? 1;
        $mapped['created_by'] = $user;
        if (! in_array($table, ['briefing_attendances', 'briefing_attendance_ppe_items', 'briefing_checklists'])) {
            $mapped['checked_by'] = $user;
        }

        return $mapped;
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

    protected function createSyncLog(string $type, string $table, $recordId, string $operation, array $payload): AppSheetSyncLog
    {
        return AppSheetSyncLog::create([
            'sync_type' => $type,
            'table_name' => $table,
            'record_id' => $recordId,
            'operation' => $operation,
            'payload' => $payload,
            'status' => 'pending',
            'source' => 'appsheet',
            'synced_by' => auth()->user()?->name ?? 'system',
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
