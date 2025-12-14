<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CategoryField;
use App\Models\CategoryFieldOption;
use App\Services\CategoryFieldService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CategorySeeder extends Seeder
{

    private CategoryFieldService $categoryFieldService;

    public function __construct(CategoryFieldService $categoryFieldService)
    {
        $this->categoryFieldService = $categoryFieldService;
    }
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::beginTransaction();

        try {
            $this->command->info('Fetching categories from OLX API...');
            $categories = $this->categoryFieldService->fetchCategories();

            if (empty($categories)) {
                $this->command->error('No categories fetched from API');
                return;
            }
            $this->command->info('Found ' . count($categories) . ' root categories');

            foreach ($categories as $categoryData) {
                $this->seedCategory($categoryData);
            }
            $this->command->info('Categories seeded successfully');

            $this->command->info('Fetching and seeding category fields...');
            $this->seedCategoryFields();

            DB::commit();
            $this->command->info('All data seeded successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Seeder failed ' . $e->getMessage());
            $this->command->error('Seeding failed ' . $e->getMessage());
        }
    }

    private function seedCategory(array $data, ?int $parentId = null): void
    {
        $category = Category::updateOrCreate(
            ['external_id' => $data['externalID']],
            [
                'name' => $data['name'],
                'name_l1' => $data['name_l1'] ?? null,
                'slug' => $data['slug'],
                'level' => $data['level'],
                'parent_id' => $parentId,
                'display_priority' => $data['displayPriority'] ?? 0,
                'purpose' => $data['purpose'] ?? null,
                'roles' => $data['roles'] ?? [],
                'is_active' => true,
            ]
        );
        $this->command->info("  - Seeded: {$category->name} (ID: {$category->external_id})");

        if (!empty($data['children'])) {
            foreach ($data['children'] as $childData) {
                $this->seedCategory($childData, $category->id);
            }
        }
    }

    private function seedCategoryFields(): void
    {
        $categories = Category::all();

        $processedExternalIds = [];

        foreach ($categories as $category) {
            if (in_array($category->external_id, $processedExternalIds)) {
                continue;
            }

            $this->command->info("Fetching fields for: {$category->name}");
            $this->seedFieldsForCategory($category->external_id);

            $processedExternalIds[] = $category->external_id;
        }
    }

    private function seedFieldsForCategory(string $externalId): void
    {
        $fieldData = $this->categoryFieldService->fetchCategoryFields($externalId);

        if (empty($fieldData)) {
            return;
        }
        $this->processFields($fieldData, $externalId);
    }

    private function processFields(array $fieldsData, ?string $categoryExternalId = null): void
    {
        if (!empty($fieldsData['common_category_fields']['flatFields'])) {
            foreach ($fieldsData['common_category_fields']['flatFields'] as $fieldData) {
                $this->createField($fieldData, null);
            }
        }
    }

    private function createField(array $fieldData, ?int $categoryId): void
    {
        $field = CategoryField::updateOrCreate(
            [
                'category_id' => $categoryId,
                'external_id' => $fieldData['id'],
            ],
            [
                'attribute' => $fieldData['attribute'],
                'name' => $fieldData['name'],
                'value_type' => $this->mapValueType($fieldData['valueType']),
                'filter_type' => $fieldData['filterType'] ?? null,
                'is_mandatory' => $fieldData['isMandatory'] ?? false,
                'roles' => $fieldData['roles'] ?? [],
                'state' => $fieldData['state'] ?? 'active',
                'min_value' => $fieldData['minValue'] ?? null,
                'max_value' => $fieldData['maxValue'] ?? null,
                'min_length' => $fieldData['minLength'] ?? null,
                'max_length' => $fieldData['maxLength'] ?? null,
                'display_priority' => $fieldData['displayPriority'] ?? 0,
            ]
        );

        if (!empty($fieldData['choices'])) {
            $this->seedFieldOptions($field, $fieldData['choices']);
        }
    }

    private function seedFieldOptions(CategoryField $field, array $choices): void
    {
        foreach ($choices as $choice) {
            CategoryFieldOption::updateOrCreate(
                [
                    'category_field_id' => $field->id,
                    'external_id' => $choice['id'],
                ],
                [
                    'value' => $choice['value'],
                    'label' => $choice['label'],
                    'slug' => $choice['slug'] ?? null,
                    'display_priority' => $choice['displayPriority'] ?? 0,
                ]
            );
        }
    }

    private function mapValueType(string $apiType): string
    {
        return match ($apiType) {
            'float' => 'float',
            'string' => 'string',
            'enum' => 'enum',
            'integer', 'int' => 'integer',
            'bool', 'boolean' => 'boolean',
            default => 'string',
        };
    }
}
