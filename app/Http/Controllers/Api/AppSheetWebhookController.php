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
            ]);

            $table = $data['table'];
            $operation = $data['operation'];
            $recordData = $data['data'];

            $allowedTables = array_keys(config('appsheet.tables', []));
            if (! in_array($table, $allowedTables, true)) {
                return response()->json([
                    'success' => false,
                    'message' => "Unknown table: {$table}",
                    'allowed_tables' => $allowedTables,
                ], 422);
            }

            $result = $this->appSheetService->syncFromWebhook($table, $recordData, $operation);

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
        } catch (\Exception $e) {
            Log::error('AppSheet webhook error: '.$e->getMessage(), [
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
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
