<?php

/**
 * @deprecated DEAD CODE — SyncHandlerRegistry is never instantiated from any active code path.
 * The active path is AppSheetService (monolith). See BaseSyncHandler.php for full context.
 */

namespace App\Services\AppSheet;

use App\Services\AppSheet\Handlers\BaseSyncHandler;
use App\Services\AppSheet\Handlers\BriefingSessionHandler;
use App\Services\AppSheet\Handlers\BriefingAttendanceHandler;
use App\Services\AppSheet\Handlers\BriefingPpeItemHandler;
use App\Services\AppSheet\Handlers\BriefingChecklistHandler;
use App\Services\AppSheet\Handlers\LoadingSessionHandler;
use App\Services\AppSheet\Handlers\RackContainerCheckHandler;
use App\Services\AppSheet\Handlers\EquipmentCheckHandler;
use App\Services\AppSheet\Handlers\UnitCheckHandler;
use App\Services\AppSheet\Handlers\LoadingFindingHandler;
use Exception;

class SyncHandlerRegistry
{
    protected array $handlers = [
        'briefing_sessions' => BriefingSessionHandler::class,
        'briefing_attendances' => BriefingAttendanceHandler::class,
        'briefing_attendance_ppe_items' => BriefingPpeItemHandler::class,
        'briefing_checklists' => BriefingChecklistHandler::class,
        'loading_sessions' => LoadingSessionHandler::class,
        'rack_container_checks' => RackContainerCheckHandler::class,
        'equipment_checks' => EquipmentCheckHandler::class,
        'unit_checks' => UnitCheckHandler::class,
        'loading_findings' => LoadingFindingHandler::class,
    ];

    public function get(string $table): BaseSyncHandler
    {
        if (! isset($this->handlers[$table])) {
            throw new Exception("Unknown table: {$table}");
        }

        return app($this->handlers[$table]);
    }

    public function tables(): array
    {
        return array_keys($this->handlers);
    }
}