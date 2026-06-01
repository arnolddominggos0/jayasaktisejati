<?php

namespace App\Services\AppSheet\Handlers;

use App\Models\BriefingAttendance;
use App\Models\BriefingAttendancePpeItem;
use Exception;

class BriefingPpeItemHandler extends BaseSyncHandler
{
    protected function modelClass(): string
    {
        return BriefingAttendancePpeItem::class;
    }

    protected function primaryKey(): string|array
    {
        return ['attendance_id', 'ppe_type'];
    }

    protected bool $useFirstOrCreate = false;

    public function preNormalize(array $rawData): array
    {
        while (isset($rawData['data']) && is_array($rawData['data'])) {
            $rawData = $rawData['data'];
        }

        return $rawData;
    }

    public function sync(array $mappedData, array $rawData, string $operation, ?int $submittedByUserId = null)
    {
        $this->validateData($mappedData, $rawData);

        return match ($operation) {
            'create' => $this->create($mappedData, $rawData),
            'update' => $this->update($mappedData, $rawData),
            'delete' => $this->delete($mappedData, $rawData),
            default => throw new Exception("Unknown operation: {$operation}"),
        };
    }

    protected function validateData(array $mappedData, array $rawData): void
    {
        $attendanceId = $this->resolveAttendanceId($rawData, $mappedData);

        if (!$attendanceId) {
            throw new Exception('Attendance ID wajib diisi untuk PPE Item');
        }

        $attendance = BriefingAttendance::find($attendanceId);

        if (!$attendance) {
            throw new Exception("Attendance ID {$attendanceId} tidak ditemukan");
        }

        $ppeType = $this->resolveValue('ppe_type', $rawData, $mappedData);
        if (!$ppeType) {
            throw new Exception('PPE Type wajib diisi');
        }

        $status = $this->resolveValue('status', $rawData, $mappedData);
        if (!$status) {
            throw new Exception('Status wajib diisi');
        }
    }

    protected function create(array $mappedData, array $rawData)
    {
        return BriefingAttendancePpeItem::create($this->buildFinalData($rawData, $mappedData));
    }

    protected function update(array $mappedData, array $rawData)
    {
        $attendanceId = $this->resolveAttendanceId($rawData, $mappedData);
        $ppeType = $this->resolveValue('ppe_type', $rawData, $mappedData);

        return BriefingAttendancePpeItem::updateOrCreate(
            ['attendance_id' => $attendanceId, 'ppe_type' => $ppeType],
            $this->buildFinalData($rawData, $mappedData)
        );
    }

    protected function delete(array $mappedData, array $rawData)
    {
        $id = $mappedData['id'] ?? $rawData['id'] ?? null;

        if (!$id) {
            throw new Exception('ID wajib diisi untuk delete PPE item');
        }

        return BriefingAttendancePpeItem::where('id', $id)->delete();
    }

    public function resolveScopeContext(array $mappedData): ?array
    {
        $attendanceId = $mappedData['attendance_id'] ?? null;

        if ($attendanceId) {
            $attendance = BriefingAttendance::with('session.depot')->find($attendanceId);

            return [
                'branch_id' => $attendance?->session?->depot?->branch_id,
                'depot_id' => $attendance?->session?->depot_id,
            ];
        }

        return null;
    }

    private function resolveAttendanceId(array $rawData, array $mappedData): ?int
    {
        return $rawData['attendance_id']
            ?? ($rawData['data']['attendance_id'] ?? null)
            ?? ($mappedData['attendance_id'] ?? null);
    }

    private function resolveValue(string $key, array $rawData, array $mappedData)
    {
        return $rawData[$key]
            ?? ($rawData['data'][$key] ?? null)
            ?? ($mappedData[$key] ?? null);
    }

    private function buildFinalData(array $rawData, array $mappedData): array
    {
        return [
            'attendance_id' => $this->resolveAttendanceId($rawData, $mappedData),
            'ppe_type' => $this->resolveValue('ppe_type', $rawData, $mappedData),
            'status' => $this->resolveValue('status', $rawData, $mappedData),
            'catatan' => $this->resolveValue('catatan', $rawData, $mappedData),
        ];
    }
}
