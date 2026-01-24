<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'gems',
        'price',
        'is_active',
        'order',
    ];

    protected $casts = [
        'gems' => 'integer',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Get all purchases of this package
     */
    public function purchases()
    {
        return $this->hasMany(PackagePurchase::class);
    }

    /**
     * Scope to get only active packages
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get packages ordered by custom order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc')->orderBy('price', 'asc');
    }
}
