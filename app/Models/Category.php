<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'name',
        'name_l1',
        'slug',
        'level',
        'parent_id',
        'display_priority',
        'purpose',
        'roles',
        'is_active',
    ];

protected $casts = [ 
    'is_active' => 'boolean',
    'roles' => 'array',
    'level' => 'integer',
    'display_priority' => 'integer',
];

    // category - sub category relation
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(CategoryField::class);
    }

    public function ads(): HasMany
    {
        return $this->hasMany(Ad::class);
    }
}
