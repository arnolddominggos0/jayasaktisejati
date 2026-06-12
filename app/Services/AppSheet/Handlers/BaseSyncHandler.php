<?php

/**
 * @deprecated This Handler architecture is DEAD CODE and is NOT used in production.
 *
 * The active path is:
 *   AppSheetWebhookController → AppSheetService::syncFromWebhook() → sync*() methods
 *
 * These classes were built as a planned refactor but were never wired into any route,
 * service provider, or controller.  They remain here for reference only.
 * Do NOT activate them without a full integration audit and test cycle.
 */

namespace App\Services\AppSheet\Handlers;

use Exception;

abstract class BaseSyncHandler
{
    protected bool $useFirstOrCreate = true;

    abstract protected function modelClass(): string;

    abstract protected function primaryKey(): string|array;

    public function preNormalize(array $rawData): array
    {
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
        //
    }

    protected function keyConditions(array $mappedData): array
    {
        $key = $this->primaryKey();

        if (is_array($key)) {
            $conditions = [];
            foreach ($key as $field) {
                $conditions[$field] = $mappedData[$field] ?? null;
            }

            return $conditions;
        }

        return [$key => $mappedData[$key] ?? null];
    }

    protected function create(array $mappedData, array $rawData)
    {
        $model = $this->modelClass();

        if ($this->useFirstOrCreate) {
            return $model::firstOrCreate($this->keyConditions($mappedData), $mappedData);
        }

        return $model::create($mappedData);
    }

    protected function update(array $mappedData, array $rawData)
    {
        $model = $this->modelClass();

        return $model::updateOrCreate($this->keyConditions($mappedData), $mappedData);
    }

    protected function delete(array $mappedData, array $rawData)
    {
        $model = $this->modelClass();
        $query = $model::query();

        foreach ($this->keyConditions($mappedData) as $field => $value) {
            $query->where($field, $value);
        }

        return $query->delete();
    }

    public function resolveScopeContext(array $mappedData): ?array
    {
        return null;
    }

    public function resolveShipmentId(array $mappedData): ?int
    {
        return null;
    }

    public function afterSync($result): void
    {
        //
    }
}