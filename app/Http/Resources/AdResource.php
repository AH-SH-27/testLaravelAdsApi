<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'price' => $this->price ? (float) $this->price : null,
            'status' => $this->status,
            'category' => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'external_id' => $this->category->external_id,
            ],
            'dynamic_fields' => $this->formatDynamicFields(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }

    private function formatDynamicFields(): array
    {
        if (!$this->relationLoaded('fieldValues')) {
            return [];
        }

        $formatted = [];

        foreach ($this->fieldValues as $fieldValue) {
            $field = $fieldValue->categoryField;

            $value = match ($field->value_type) {
                'integer' => $fieldValue->value_integer,
                'float' => $fieldValue->value_float,
                'boolean' => $fieldValue->value_boolean,
                default => $fieldValue->value_string,
            };

            $formatted[$field->attribute] = [
                'name' => $field->name,
                'value' => $value,
                'type' => $field->value_type,
            ];
        }

        return $formatted;
    }
}
