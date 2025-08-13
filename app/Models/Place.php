<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Place extends Model
{
    use HasUuids, SoftDeletes;

    protected $guarded = [];
    protected $casts = [
        'address' => 'array',
        'location' => 'array',
    ];

    /**
     * Get all of the slots for the Place
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function slots(): HasMany
    {
        return $this->hasMany(Slot::class, 'place_id', 'id');
    }

    /**
     * Get all of the agents for the Place
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class, 'place_id', 'id');
    }

    /**
     * Get the owner that owns the Place
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id', 'id');
    }


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->created_by) && auth()->check()) {
                $model->created_by = auth()->id();
            }
        });
    }
}
