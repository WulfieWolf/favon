<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'public_handle',
        'public_alias',
        'alias_finalized_at',
        'selected_badge_id',
        'handle_finalized_at',
        'bio',
        'hometown_city',
        'hometown_country_code',
        'hometown_latitude',
        'hometown_longitude',
        'hometown_source_id',
        'birth_date',
        'gender',
        'gender_custom',
        'vehicle_type',
        'vehicle_details',
    ];

    protected function casts(): array
    {
        return [
            'handle_finalized_at' => 'datetime',
            'alias_finalized_at' => 'datetime',
            'birth_date' => 'date',
            'hometown_latitude' => 'decimal:7',
            'hometown_longitude' => 'decimal:7',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function socialLinks(): HasMany
    {
        return $this->hasMany(UserProfileSocialLink::class, 'user_id', 'user_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
