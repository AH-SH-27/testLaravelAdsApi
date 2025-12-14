<?php

namespace App\Services;

use App\Models\Ad;
use App\Models\AdFieldValue;
use App\Models\CategoryField;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdService
{
    private const FIELD_CACHE_TTL = 43200; // 12 hours

    /**
     * Create a new ad with dynamic field values
     */
    public function createAd(array $data, int $userId): Ad
    {
        DB::beginTransaction();

        try {
            $ad = Ad::create([
                'user_id' => $userId,
                'category_id' => $data['category_id'],
                'title' => $data['title'],
                'description' => $data['description'],
                'price' => $data['price'] ?? null,
                'status' => $data['status'] ?? 'published',
            ]);

            $this->saveDynamicFields($ad, $data);

            DB::commit();

            $ad->load(['category', 'fieldValues.categoryField']);

            return $ad;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create ad', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'category_id' => $data['category_id'] ?? null,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Save dynamic field values with batch insert
     */
    private function saveDynamicFields(Ad $ad, array $data): void
    {
        $fields = $this->getCategoryFields($ad->category_id);

        $rows = [];
        $now = now();

        foreach ($fields as $attribute => $field) {
            if (!array_key_exists($attribute, $data)) {
                continue;
            }

            $value = $data[$attribute];

            if ($value === null || $value === '') {
                continue;
            }

            $fieldValue = $this->prepareFieldValue($field, $value);

            $rows[] = array_merge([
                'ad_id' => $ad->id,
                'category_field_id' => $field->id,
                'created_at' => $now,
                'updated_at' => $now,
            ], $fieldValue);
        }

        if (!empty($rows)) {
            AdFieldValue::insert($rows);
        }
    }

    /**
     * Get category fields (cached in production, fresh in tests)
     * the testing part commented as code and tests worked fine
     * But In case for some reason an issue happen related to this
     * uncomment the If 
     */
    private function getCategoryFields(int $categoryId)
    {
        // if (app()->environment('testing')) {
        //     return CategoryField::where('category_id', $categoryId)
        //         ->orWhereNull('category_id')
        //         ->where('state', 'active')
        //         ->with('options')
        //         ->get()
        //         ->keyBy('attribute');
        // }

        return Cache::remember(
            "category_fields:{$categoryId}",
            self::FIELD_CACHE_TTL,
            fn() => CategoryField::where('category_id', $categoryId)
                ->orWhereNull('category_id')
                ->where('state', 'active')
                ->with('options')
                ->get()
                ->keyBy('attribute')
        );
    }

    /**
     * Prepare field value based on type
     */
    private function prepareFieldValue(CategoryField $field, mixed $value): array
    {
        return match ($field->value_type) {
            'integer' => [
                'value_integer' => (int) $value,
                'value_string' => null,
                'value_float' => null,
                'value_boolean' => null,
            ],
            'float' => [
                'value_float' => (float) $value,
                'value_string' => null,
                'value_integer' => null,
                'value_boolean' => null,
            ],
            'boolean' => [
                'value_boolean' => $this->convertToBoolean($value),
                'value_string' => null,
                'value_integer' => null,
                'value_float' => null,
            ],
            'enum', 'string' => [
                'value_string' => (string) $value,
                'value_integer' => null,
                'value_float' => null,
                'value_boolean' => null,
            ],
            default => [
                'value_string' => (string) $value,
                'value_integer' => null,
                'value_float' => null,
                'value_boolean' => null,
            ],
        };
    }

    private function convertToBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $lower = strtolower($value);
            return !in_array($lower, ['false', '0', 'no', 'off', ''], true);
        }

        return (bool) $value;
    }

    /**
     * Clear field cache for a category
     */
    public function clearFieldsCache(int $categoryId): void
    {
        Cache::forget("category_fields:{$categoryId}");
    }
}
