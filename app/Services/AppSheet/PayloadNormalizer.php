<?php

/**
 * @deprecated DEAD CODE — PayloadNormalizer is never used from any active code path.
 * Note: this class lacks the normalizeValue() logic present in AppSheetService.
 * The active normalization path is AppSheetService::normalizeValue(). See BaseSyncHandler.php.
 */

namespace App\Services\AppSheet;

use Exception;

class PayloadNormalizer
{
    public function extractData(array $payload): array
    {
        if (isset($payload['data']) && is_array($payload['data'])) {
            return $payload['data'];
        }

        return $payload;
    }

    public function mapFields(string $table, array $data, ?int $submittedByUserId = null): array
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

        if ($config['add_checked_by'] ?? true) {
            $mapped['checked_by'] = $user;
        }

        return $mapped;
    }
}