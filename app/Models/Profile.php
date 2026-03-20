<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Profile extends Model
{
    protected $table = 'profiles';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'bio',
        'website',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'user_id' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
