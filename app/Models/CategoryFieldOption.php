<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryFieldOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_field_id',
        'external_id',
        'value',
        'label',
        'slug',
        'display_priority',
    ];

    protected $casts = [
        'display_priority' => 'integer',
    ];

    public function field(): BelongsTo
    {
        return $this->belongsTo(CategoryField::class, 'category_field_id');
    }
}
