<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\AdFieldValue;
use App\Models\Category;
use App\Models\CategoryField;
use App\Models\CategoryFieldOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdCreationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->category = Category::create([
            'external_id' => '23',
            'name' => 'Cars for Sale',
            'name_l1' => 'سيارات للبيع',
            'slug' => 'cars-for-sale',
            'level' => 1,
            'parent_id' => null,
            'display_priority' => 1,
            'purpose' => 'for-sale',
            'roles' => [],
            'is_active' => true,
        ]);

        $this->createDynamicFields();
    }

    private function createDynamicFields(): void
    {
        CategoryField::create([
            'category_id' => $this->category->id,
            'external_id' => 100,
            'attribute' => 'year',
            'name' => 'Year',
            'value_type' => 'integer',
            'filter_type' => 'range',
            'is_mandatory' => true,
            'roles' => [],
            'state' => 'active',
            'min_value' => 1990,
            'max_value' => 2025,
        ]);

        CategoryField::create([
            'category_id' => $this->category->id,
            'external_id' => 101,
            'attribute' => 'mileage',
            'name' => 'Mileage (km)',
            'value_type' => 'float',
            'filter_type' => 'range',
            'is_mandatory' => false,
            'roles' => [],
            'state' => 'active',
            'min_value' => 0,
            'max_value' => 500000,
        ]);

        $conditionField = CategoryField::create([
            'category_id' => $this->category->id,
            'external_id' => 102,
            'attribute' => 'condition',
            'name' => 'Condition',
            'value_type' => 'enum',
            'filter_type' => 'single_choice',
            'is_mandatory' => true,
            'roles' => [],
            'state' => 'active',
        ]);

        CategoryFieldOption::create([
            'category_field_id' => $conditionField->id,
            'external_id' => 1001,
            'value' => 'new',
            'label' => 'New',
            'slug' => 'new',
        ]);

        CategoryFieldOption::create([
            'category_field_id' => $conditionField->id,
            'external_id' => 1002,
            'value' => 'used',
            'label' => 'Used',
            'slug' => 'used',
        ]);

        CategoryField::create([
            'category_id' => $this->category->id,
            'external_id' => 103,
            'attribute' => 'is_negotiable',
            'name' => 'Negotiable',
            'value_type' => 'boolean',
            'filter_type' => 'single_choice',
            'is_mandatory' => false,
            'roles' => [],
            'state' => 'active',
        ]);

        CategoryField::create([
            'category_id' => $this->category->id,
            'external_id' => 104,
            'attribute' => 'vin',
            'name' => 'VIN Number',
            'value_type' => 'string',
            'filter_type' => null,
            'is_mandatory' => false,
            'roles' => [],
            'state' => 'active',
            'min_length' => 17,
            'max_length' => 17,
        ]);

        $electronicsCategory = Category::create([
            'external_id' => '999',
            'name' => 'Electronics',
            'slug' => 'electronics',
            'level' => 0,
            'is_active' => true,
        ]);

        CategoryField::create([
            'category_id' => $electronicsCategory->id,
            'external_id' => 200,
            'attribute' => 'screen_size',
            'name' => 'Screen Size',
            'value_type' => 'float',
            'is_mandatory' => false,
            'roles' => [],
            'state' => 'active',
        ]);
    }

    /**
     * Test successful ad creation with all dynamic field types
     */
    public function test_authenticated_user_can_create_ad_with_dynamic_fields(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/ads', [
                'category_id' => $this->category->id,
                'title' => 'BMW 320i 2020',
                'description' => 'Excellent condition, German specs',
                'price' => 25000,
                'year' => 2020,
                'mileage' => 15000.5,
                'condition' => 'used',
                'is_negotiable' => true,
                'vin' => '1HGBH41JXMN109186',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'BMW 320i 2020')
            ->assertJsonPath('data.price', 25000);

        $this->assertDatabaseHas('ads', [
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'title' => 'BMW 320i 2020',
        ]);

        $ad = Ad::latest()->first();

        $this->assertDatabaseHas('ad_field_values', [
            'ad_id' => $ad->id,
            'value_integer' => 2020,
        ]);

        $this->assertDatabaseHas('ad_field_values', [
            'ad_id' => $ad->id,
            'value_float' => 15000.5,
        ]);

        $this->assertDatabaseHas('ad_field_values', [
            'ad_id' => $ad->id,
            'value_string' => 'used',
        ]);

        $this->assertDatabaseHas('ad_field_values', [
            'ad_id' => $ad->id,
            'value_boolean' => true,
        ]);

        $this->assertDatabaseHas('ad_field_values', [
            'ad_id' => $ad->id,
            'value_string' => '1HGBH41JXMN109186',
        ]);

        // Verify 5 field values stored
        $this->assertEquals(5, AdFieldValue::where('ad_id', $ad->id)->count());
    }

    /**
     * Test validation fails when mandatory dynamic fields are missing
     */
    public function test_ad_creation_fails_without_mandatory_dynamic_fields(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/ads', [
                'category_id' => $this->category->id,
                'title' => 'BMW 320i',
                'description' => 'Good car',
                'price' => 25000,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['year', 'condition']);
    }

    /**
     * Test integer field validation (min/max constraints)
     */
    public function test_integer_field_validates_min_max_values(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/ads', [
                'category_id' => $this->category->id,
                'title' => 'Old Car',
                'description' => 'Very old',
                'price' => 5000,
                'year' => 1950,
                'condition' => 'used',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['year']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/ads', [
                'category_id' => $this->category->id,
                'title' => 'Future Car',
                'description' => 'From the future',
                'price' => 5000,
                'year' => 2030,
                'condition' => 'new',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['year']);
    }

    /**
     * Test enum field validation (invalid choice)
     */
    public function test_enum_field_validates_against_options(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/ads', [
                'category_id' => $this->category->id,
                'title' => 'Test Car',
                'description' => 'Test',
                'price' => 10000,
                'year' => 2020,
                'condition' => 'excellent',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['condition']);
    }

    /**
     * Test data type validation (string sent to integer field)
     */
    public function test_integer_field_rejects_string_values(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/ads', [
                'category_id' => $this->category->id,
                'title' => 'Test Car',
                'description' => 'Test',
                'price' => 10000,
                'year' => 'very old',
                'condition' => 'used',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['year']);
    }

    /**
     * Test float field validates numeric values
     */
    public function test_float_field_validates_numeric_values(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/ads', [
                'category_id' => $this->category->id,
                'title' => 'Test Car',
                'description' => 'Test',
                'price' => 10000,
                'year' => 2020,
                'condition' => 'used',
                'mileage' => 'very high',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['mileage']);
    }

    /**
     * Test string field validates length constraints
     */
    public function test_string_field_validates_length(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/ads', [
                'category_id' => $this->category->id,
                'title' => 'Test Car',
                'description' => 'Test',
                'price' => 10000,
                'year' => 2020,
                'condition' => 'used',
                'vin' => '12345',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['vin']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/ads', [
                'category_id' => $this->category->id,
                'title' => 'Test Car',
                'description' => 'Test',
                'price' => 10000,
                'year' => 2020,
                'condition' => 'used',
                'vin' => '1HGBH41JXMN109186EXTRA',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['vin']);
    }

    /**
     * Test field isolation (can't send fields from other categories)
     */
    public function test_fields_from_other_categories_are_ignored(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/ads', [
                'category_id' => $this->category->id,
                'title' => 'Test Car',
                'description' => 'Test',
                'price' => 10000,
                'year' => 2020,
                'condition' => 'used',
                'screen_size' => 15.6,
            ]);

        $response->assertStatus(201);

        $ad = Ad::latest()->first();

        $this->assertDatabaseMissing('ad_field_values', [
            'ad_id' => $ad->id,
            'value_float' => 15.6,
        ]);
    }

    /**
     * Test optional fields can be omitted
     */
    public function test_optional_fields_can_be_omitted(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/ads', [
                'category_id' => $this->category->id,
                'title' => 'Simple Car',
                'description' => 'Minimal info',
                'price' => 10000,
                'year' => 2020,
                'condition' => 'used',
            ]);

        $response->assertStatus(201);

        $ad = Ad::latest()->first();

        $this->assertEquals(2, AdFieldValue::where('ad_id', $ad->id)->count());
    }

    /**
     * Test boolean field handles various formats
     */
    public function test_boolean_field_handles_various_formats(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/ads', [
                'category_id' => $this->category->id,
                'title' => 'Test 1',
                'description' => 'Test',
                'price' => 10000,
                'year' => 2020,
                'condition' => 'used',
                'is_negotiable' => true,
            ]);

        $response->assertStatus(201);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/ads', [
                'category_id' => $this->category->id,
                'title' => 'Test 2',
                'description' => 'Test',
                'price' => 10000,
                'year' => 2020,
                'condition' => 'used',
                'is_negotiable' => 'false',
            ]);

        $response->assertStatus(201);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/ads', [
                'category_id' => $this->category->id,
                'title' => 'Test 3',
                'description' => 'Test',
                'price' => 10000,
                'year' => 2020,
                'condition' => 'used',
                'is_negotiable' => 0,
            ]);

        $response->assertStatus(201);

        $this->assertGreaterThan(0, AdFieldValue::whereNotNull('value_boolean')->count());
    }

    /**
     * Test unauthenticated user cannot create ad
     */
    public function test_unauthenticated_user_cannot_create_ad(): void
    {
        $response = $this->postJson('/api/v1/ads', [
            'category_id' => $this->category->id,
            'title' => 'Test Ad',
            'description' => 'Test',
            'year' => 2020,
            'condition' => 'used',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test invalid category returns validation error
     */
    public function test_ad_creation_fails_with_invalid_category(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/ads', [
                'category_id' => 99999,
                'title' => 'Test Ad',
                'description' => 'Test',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id']);
    }
}
