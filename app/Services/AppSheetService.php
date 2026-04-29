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

   public function syncFromWebhook(string $tableName, array $data, string $operation = 'create', ?int $submittedByUserId = null)
   {
    while (isset($data['data']) && is_array($data['data'])) {
        $data = $data['data'];
    }

    $handler = $this->registry->get($tableName);

    $prepared = method_exists($handler, 'preNormalize')
        ? $handler->preNormalize($data)
        : $data;

    $mappedData = array_merge(
        $prepared,
        $this->normalizer->mapFields($tableName, $prepared, $submittedByUserId)
    );

    $result = $handler->sync($mappedData, $prepared, $operation, $submittedByUserId);

    if (method_exists($handler, 'afterSync')) {
        $handler->afterSync($result);
    }

    return ['success' => true, 'data' => $result];
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

    protected function syncBriefingPpeItem(array $data, string $operation, ?int $submittedByUserId = null)
   {
	Log::info('MASUK PPE FUNCTION', $data);

    while (isset($data['data']) && is_array($data['data'])) {
        $data = $data['data'];
    }

    // 🔥 AMBIL LANGSUNG TANPA MAPFIELDS
    $attendanceId = $data['attendance_id'] ?? null;
    $ppeType = $data['ppe_type'] ?? null;
    $status = $data['status'] ?? null;
    $catatan = $data['catatan'] ?? null;

    if (!$attendanceId) {
        throw new \Exception('DEBUG: attendance_id NULL');
    }

    $attendance = \App\Models\BriefingAttendance::find($attendanceId);

    if (!$attendance) {
        throw new \Exception("DEBUG: attendance {$attendanceId} NOT FOUND");
    }

    if (!$ppeType) {
        throw new \Exception('DEBUG: ppe_type NULL');
    }

    if (!$status) {
        throw new \Exception('DEBUG: status NULL');
    }

    return match ($operation) {
        'create' => \App\Models\BriefingAttendancePpeItem::create([
            'attendance_id' => $attendanceId,
            'ppe_type' => $ppeType,
            'status' => $status,
            'catatan' => $catatan,
        ]),

        'update' => \App\Models\BriefingAttendancePpeItem::updateOrCreate(
            [
                'attendance_id' => $attendanceId,
                'ppe_type' => $ppeType,
            ],
            [
                'status' => $status,
                'catatan' => $catatan,
            ]
        ),

        'delete' => \App\Models\BriefingAttendancePpeItem::where('id', $data['id'] ?? 0)->delete(),

        default => throw new \Exception("Unknown operation: {$operation}"),
    };
}



        $mappedData = $this->mapFields('briefing_attendance_ppe_items', $data);
        $attendanceId = $mappedData['attendance_id'] ?? null;
        $ppeType = $mappedData['ppe_type'] ?? null;

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
            'delete' => BriefingAttendancePpeItem::where('id', $

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
