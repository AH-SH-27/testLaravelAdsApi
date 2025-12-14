<?php

namespace App\Http\Requests;

use App\Models\CategoryField;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class StoreAdRequest extends FormRequest
{
    private const FIELD_CACHE_TTL = 43200; // 12 hours

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'category_id' => ['required', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:1000000000'],
            'status' => ['nullable', 'in:draft,published'],
        ];

        if ($this->has('category_id')) {
            $dynamicRules = $this->buildDynamicRules($this->input('category_id'));
            $rules = array_merge($rules, $dynamicRules);
        }

        return $rules;
    }

    private function buildDynamicRules(int $categoryId): array
    {
        $rules = [];
        $fields = $this->getCategoryFields($categoryId);

        foreach ($fields as $field) {
            if ($this->isExcludedFromPost($field)) {
                continue;
            }

            $fieldRules = [];

            if ($field->is_mandatory) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            $fieldRules = array_merge($fieldRules, $this->getTypeRules($field));

            $rules[$field->attribute] = $fieldRules;
        }

        return $rules;
    }

    private function getCategoryFields(int $categoryId)
    {
        return Cache::remember(
            "validation_fields:{$categoryId}",
            self::FIELD_CACHE_TTL,
            fn() => CategoryField::where(function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId)
                    ->orWhereNull('category_id');
            })
                ->where('state', 'active')
                ->with('options')
                ->get()
        );
    }

    private function getTypeRules(CategoryField $field): array
    {
        $rules = [];

        switch ($field->value_type) {
            case 'integer':
                $rules[] = 'integer';
                if (!is_null($field->min_value)) {
                    $rules[] = 'min:' . (int) $field->min_value;
                }
                if (!is_null($field->max_value)) {
                    $rules[] = 'max:' . (int) $field->max_value;
                }
                break;

            case 'float':
                $rules[] = 'numeric';
                if (!is_null($field->min_value)) {
                    $rules[] = 'min:' . $field->min_value;
                }
                if (!is_null($field->max_value)) {
                    $rules[] = 'max:' . $field->max_value;
                }
                break;

            case 'string':
                $rules[] = 'string';
                if (!is_null($field->min_length)) {
                    $rules[] = 'min:' . $field->min_length;
                }
                if (!is_null($field->max_length)) {
                    $rules[] = 'max:' . $field->max_length;
                }
                break;

            case 'enum':
                $rules[] = 'string';
                if ($field->options->isNotEmpty()) {
                    $validValues = $field->options->pluck('value')->toArray();
                    $rules[] = Rule::in($validValues);
                }
                break;

            case 'boolean':
                $rules[] = Rule::in([true, false, 1, 0, '1', '0', 'true', 'false', 'yes', 'no']);
                break;
        }

        return $rules;
    }

    private function isExcludedFromPost(CategoryField $field): bool
    {
        if (is_null($field->roles)) {
            return false;
        }

        return in_array('exclude_from_post_an_ad', $field->roles);
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Please select a category.',
            'category_id.exists' => 'The selected category does not exist.',
            'title.required' => 'Ad title is required.',
            'title.max' => 'Title cannot exceed 255 characters.',
            'description.required' => 'Ad description is required.',
            'description.max' => 'Description cannot exceed 5000 characters.',
            'price.numeric' => 'Price must be a valid number.',
            'price.min' => 'Price cannot be negative.',
            'price.max' => 'Price is too high.',
        ];
    }

    public function attributes(): array
    {
        $attributes = [];

        if ($this->has('category_id')) {
            $fields = $this->getCategoryFields($this->input('category_id'));

            foreach ($fields as $field) {
                $attributes[$field->attribute] = $field->name;
            }
        }

        return $attributes;
    }
}
