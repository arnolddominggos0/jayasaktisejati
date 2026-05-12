<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AppSheetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AppSheetWebhookController extends Controller
{
    protected $appSheetService;

    public function __construct(AppSheetService $appSheetService)
    {
        $this->appSheetService = $appSheetService;
    }

    /**
     * Handle incoming webhook dari AppSheet
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            $signature = $request->header('X-AppSheet-Signature');
            if ($signature && ! $this->appSheetService->validateWebhookSignature($signature, $request->all())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid signature',
                ], 401);
            }

            $data = $request->validate([
                'table' => 'required|string',
                'operation' => 'required|string|in:create,update,delete',
                'data' => 'required|array',
                'submitted_by_user_id' => 'nullable|integer|exists:users,id',
            ]);

            $table = $data['table'];
            $operation = $data['operation'];
            $recordData = $data['data'];
            $submittedByUserId = $data['submitted_by_user_id'] ?? null;

            $allowedTables = array_keys(config('appsheet.tables', []));
            if (! in_array($table, $allowedTables, true)) {
                return response()->json([
                    'success' => false,
                    'message' => "Unknown table: {$table}",
                    'allowed_tables' => $allowedTables,
                ], 422);
            }

            $result = $this->appSheetService->syncFromWebhook($table, $recordData, $operation, $submittedByUserId);

            return response()->json([
                'success' => true,
                'message' => 'Data synced successfully',
                'table' => $table,
                'operation' => $operation,
                'data' => $result,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\DomainException $e) {
            Log::warning('AppSheet webhook domain error: '.$e->getMessage(), [
                'request' => $request->all(),
                'submitted_by_user_id' => $submittedByUserId ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'SCOPE_VIOLATION',
            ], 403);
        } catch (\Exception $e) {
            Log::error('AppSheet webhook error: '.$e->getMessage(), [
                'request' => $request->all(),
                'submitted_by_user_id' => $submittedByUserId ?? null,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'INTERNAL_ERROR',
            ], 500);
        }
    }


    /**
     * Ringkasan briefing beserta attendance, PPE items, checklist, dan loading session untuk AppSheet.
     */
    public function briefingSummary(Request $request): JsonResponse
    {
        try {
            $signature = $request->header('X-AppSheet-Signature');
            if ($signature && ! $this->appSheetService->validateWebhookSignature($signature, $request->all())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid signature',
                ], 401);
            }

            $filters = $request->validate([
                'session_id' => 'nullable|integer|exists:briefing_sessions,id',
                'date' => 'nullable|date',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
                'depot_id' => 'nullable|integer|exists:depots,id',
                'limit' => 'nullable|integer|min:1|max:200',
                'submitted_by_user_id' => 'nullable|integer|exists:users,id',
            ]);

            $submittedByUserId = $filters['submitted_by_user_id'] ?? null;
            unset($filters['submitted_by_user_id']);

            return response()->json([
                'success' => true,
                'message' => 'Briefing summary generated successfully',
                'data' => $this->appSheetService->getBriefingSummary($filters, $submittedByUserId),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\DomainException $e) {
            Log::warning('AppSheet briefing summary scope error: '.$e->getMessage(), [
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'SCOPE_VIOLATION',
            ], 403);
        }
    }

    /**
     * Test endpoint untuk cek koneksi
     */
    public function test(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'AppSheet webhook endpoint is active',
            'timestamp' => now()->toIso8601String(),
            'config' => [
                'sync_mode' => config('appsheet.sync_mode'),
                'tables_available' => array_keys(config('appsheet.tables', [])),
            ],
        ]);
    }
}
