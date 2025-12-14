<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CategoryFieldService
{
    private const CATEGORIES_URL = 'https://www.olx.com.lb/api/categories';
    private const FIELD_URL = 'https://www.olx.com.lb/api/categoryFields';
    private const CACHE_TTL = 86400; // 24 hours

    /**
     * Fetch all categories from OLX API with caching and retry logic
     */
    public function fetchCategories(): array
    {
        return Cache::remember('olx_categories', self::CACHE_TTL, function () {
            try {
                $response = Http::withOptions(['verify' => false])
                    ->timeout(30)
                    ->get(self::CATEGORIES_URL);

                if ($response->successful()) {
                    $data = $response->json();
                    Log::debug('Categories fetched successfully', ['count' => count($data)]);
                    return $data;
                }

                Log::error('Failed to fetch categories from OLX API', [
                    'url' => self::CATEGORIES_URL,
                    'status' => $response->status(),
                ]);

                throw new \RuntimeException(
                    "OLX API returned HTTP {$response->status()} when fetching categories"
                );
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                Log::error('Connection error fetching categories', [
                    'url' => self::CATEGORIES_URL,
                    'error' => $e->getMessage(),
                ]);
                throw new \RuntimeException('Unable to connect to OLX API for categories', 0, $e);
            }
        });
    }

    /**
     * Fetch category fields for a specific category with caching and retry logic
     */
    public function fetchCategoryFields(string $categoryExternalId): array
    {
        $cacheKey = "olx_fields_{$categoryExternalId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($categoryExternalId) {
            try {
                $response = Http::withOptions(['verify' => false,])
                    ->timeout(30)
                    ->get(self::FIELD_URL, ['categoryExternalIDs' => $categoryExternalId, 'includeWithoutCategory' => 'true', 'splitByCategoryIDs' => 'true', 'flatChoices' => 'true', 'groupChoicesBySection' => 'true', 'flat' => 'true',]);

                if ($response->successful()) {
                    Log::debug('Category fields fetched successfully', [
                        'category_external_id' => $categoryExternalId,
                    ]);
                    return $response->json();
                }

                Log::error('Failed to fetch fields from OLX API', [
                    'url' => self::FIELD_URL,
                    'category_external_id' => $categoryExternalId,
                    'status' => $response->status(),
                ]);

                throw new \RuntimeException(
                    "OLX API returned HTTP {$response->status()} when fetching fields for category {$categoryExternalId}"
                );
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                Log::error('Connection error fetching category fields', [
                    'url' => self::FIELD_URL,
                    'category_external_id' => $categoryExternalId,
                    'error' => $e->getMessage(),
                ]);
                throw new \RuntimeException("Unable to connect to OLX API for category {$categoryExternalId} fields", 0, $e);
            }
        });
    }

    /**
     * Clear categories cache
     */
    public function clearCache(): void
    {
        Cache::forget('olx_categories');
        Log::info('Categories cache cleared');
    }

    /**
     * Clear field cache for a specific category
     */
    public function clearFieldsCache(string $categoryExternalId): void
    {
        Cache::forget("olx_fields_{$categoryExternalId}");
        Log::info("Fields cache cleared for category: {$categoryExternalId}");
    }
}
