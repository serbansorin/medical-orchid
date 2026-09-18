<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Chat message model for AI chatbot.
 *
 * @property int $id
 * @property int $conversation_id
 * @property string $role
 * @property string $content
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property ChatConversation $conversation
 */
class ChatMessage extends Model
{
    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }

    /**
     * Check if message is from user.
     */
    public function isFromUser(): bool
    {
        return $this->role === 'user';
    }

    /**
     * Check if message is from assistant.
     */
    public function isFromAssistant(): bool
    {
        return $this->role === 'assistant';
    }
}
