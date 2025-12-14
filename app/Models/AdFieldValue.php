<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdFieldValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'ad_id',
        'category_field_id',
        'value_string',
        'value_integer',
        'value_float',
        'value_boolean',
    ];

    protected $casts = [
        'value_integer' => 'integer',
        'value_float' => 'decimal:2',
        'value_boolean' => 'boolean',
    ];

    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }

    public function categoryField(): BelongsTo
    {
        return $this->belongsTo(CategoryField::class);
    }
}
