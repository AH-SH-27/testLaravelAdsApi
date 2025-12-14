<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoryField extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'external_id',
        'attribute',
        'name',
        'value_type',
        'filter_type',
        'is_mandatory',
        'roles',
        'state',
        'min_value',
        'max_value',
        'min_length',
        'max_length',
        'display_priority',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'roles' => 'array',
        'min_value' => 'decimal:2',
        'max_value' => 'decimal:2',
        'min_length' => 'integer',
        'max_length' => 'integer',
        'display_priority' => 'integer',
        'meta' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(CategoryFieldOption::class);
    }

    public function adFieldValues(): HasMany
    {
        return $this->hasMany(AdFieldValue::class);
    }
}
