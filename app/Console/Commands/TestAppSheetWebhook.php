<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestAppSheetWebhook extends Command
{
    protected $signature = 'appsheet:test-webhook 
                            {--url=http://localhost:8000/api/appsheet/webhook : Webhook URL}
                            {--table=loading_sessions : Table name}
                            {--operation=create : Operation type (create, update, delete)}';

    protected $description = 'Test AppSheet webhook integration';

    public function handle()
    {
        $url = $this->option('url');
        $table = $this->option('table');
        $operation = $this->option('operation');

        $this->info('========================================');
        $this->info('Testing AppSheet Webhook Integration');
        $this->info('========================================');
        $this->info("URL: {$url}");
        $this->info("Table: {$table}");
        $this->info("Operation: {$operation}");
        $this->info('');

        $testData = [
            'table' => $table,
            'operation' => $operation,
            'data' => $this->getTestData($table),
        ];

        $this->info('Request Payload:');
        $this->line(json_encode($testData, JSON_PRETTY_PRINT));
        $this->info('');

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($url, $testData);

            $this->info('========================================');
            $this->info('Response Status: '.$response->status());
            $this->info('========================================');
            $this->info('Response Body:');
            $this->line(json_encode($response->json(), JSON_PRETTY_PRINT));

            if ($response->successful()) {
                $this->info('');
                $this->info('Test PASSED - Webhook working correctly!');

                Log::channel('appsheet')->info('Webhook test successful', [
                    'url' => $url,
                    'table' => $table,
                    'operation' => $operation,
                    'response' => $response->json(),
                ]);

                return 0;
            } else {
                $this->error('');
                $this->error('Test FAILED - Response not successful');

                return 1;
            }
        } catch (\Exception $e) {
            $this->error('');
            $this->error('Test FAILED - Exception: '.$e->getMessage());

            Log::channel('appsheet')->error('Webhook test failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return 1;
        }
    }

    private function getTestData(string $table): array
    {
        return match ($table) {
            'briefing_attendances' => [
                'Sesi ID' => 1,
                'MP ID' => 1,
                'Status Kehadiran' => 'present',
                'Suhu' => '36.5',
                'TD Sistolik' => 120,
                'TD Diastolik' => 80,
                'Keluhan Kesehatan' => null,
                'APD Lengkap' => true,
                'Hasil Cek Ulang' => null,
                'Catatan' => 'Test dari artisan',
            ],
            'briefing_attendance_ppe_items' => [
                'Attendance ID' => 1,
                'Jenis APD' => 'helm',
                'Kondisi APD' => 'baik',
                'Catatan' => null,
            ],
            'briefing_checklists' => [
                'Sesi ID' => 1,
                'Item' => 'APAR tersedia',
                'Tipe' => 'briefing',
                'Status' => 'ok',
                'Catatan' => null,
            ],
            'loading_sessions' => [
                'Code' => 'LD-TEST-'.rand(1000, 9999),
                'Jenis Operasi' => 'loading',
                'Status' => 'draft',
                'Depot ID' => 1,
                'Koordinator ID' => 1,
                'Branch ID' => 1,
                'MP Dibutuhkan' => 8,
                'MP Hadir' => 8,
                'Latitude' => '-6.1000',
                'Longitude' => '106.8833',
                'Catatan' => 'Test from artisan command',
            ],
            'rack_container_checks' => [
                'Loading Session ID' => 1,
                'Pilar A Kondisi' => 'strong_and_straight',
                'Pilar A Pengait' => 'present_and_strong',
                'Pilar A Ikatan' => 'tied_strong',
                'Pilar B Kondisi' => 'strong_and_straight',
                'Pilar B Pengait' => 'present_and_strong',
                'Pilar B Ikatan' => 'tied_strong',
            ],
            'equipment_checks' => [
                'Loading Session ID' => 1,
                'Katrol Atas' => 'ok',
                'Katrol Bawah' => 'ok',
                'Tali Mono' => 'new',
                'Rantai' => 'strong',
            ],
            default => [
                'id' => rand(1, 1000),
                'name' => 'Test Data '.rand(1000, 9999),
                'created_at' => now()->toIso8601String(),
            ],
        };
    }
}
