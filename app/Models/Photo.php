<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Photo extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'uuid',
        'storage_path',
        'source_path',
        'preview_path',
        'original_filename',
        'mime_type',
        'file_size',
        'preview_file_size',
        'width',
        'height',
        'status',
        'moderated_by',
        'moderated_at',
        'moderation_reason',
        'processing_error',
        'is_active',
        'internal_comment',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'moderated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function helpfulVotes(): HasMany
    {
        return $this->hasMany(PhotoHelpfulVote::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(PhotoReport::class);
    }
}
