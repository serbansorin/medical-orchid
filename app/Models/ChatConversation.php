<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Orchid\Screen\AsSource;

/**
 * Chat conversation model for AI chatbot.
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $page_context
 * @property string|null $title
 * @property array|null $metadata
 * @property \Carbon\Carbon|null $last_message_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property User $user
 * @property \Illuminate\Database\Eloquent\Collection<ChatMessage> $messages
 */
class ChatConversation extends Model
{
    use AsSource;

    protected $fillable = [
        'user_id',
        'page_context',
        'title',
        'metadata',
        'last_message_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'last_message_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id')->orderBy('created_at');
    }

    /**
     * Get the last N messages for context.
     */
    public function getRecentMessages(int $limit = 10): array
    {
        return $this->messages()
            ->latest()
            ->limit($limit)
            ->get()
            ->reverse()
            ->map(fn (ChatMessage $msg) => [
                'role' => $msg->role,
                'content' => $msg->content,
            ])
            ->values()
            ->toArray();
    }

    /**
     * Add a message to the conversation.
     */
    public function addMessage(string $role, string $content, ?array $metadata = null): ChatMessage
    {
        $message = $this->messages()->create([
            'role' => $role,
            'content' => $content,
            'metadata' => $metadata,
        ]);

        $this->update(['last_message_at' => now()]);

        return $message;
    }
}
