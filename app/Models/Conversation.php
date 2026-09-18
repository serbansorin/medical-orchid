<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends Model
{
    /** @use HasFactory<\Database\Factories\ConversationFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'type',
        'subject',
        'status',
        'clinic_id',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    /**
     * Get the clinic that owns this conversation
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    /**
     * Get all participants in the conversation
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot(['role', 'joined_at', 'last_read_at', 'is_typing', 'typing_at'])
            ->withTimestamps();
    }

    /**
     * Get all messages in the conversation
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    /**
     * Get the latest message
     */
    public function latestMessage(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /**
     * Scope to get conversations for a specific user
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->whereHas('participants', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }

    /**
     * Scope to get active conversations
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get archived conversations
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', 'archived');
    }

    /**
     * Get unread message count for a specific user
     */
    public function unreadCount(int $userId): int
    {
        $participant = $this->participants()->where('user_id', $userId)->first();

        if (! $participant) {
            return 0;
        }

        $lastReadAt = $participant->pivot->last_read_at;

        return $this->messages()
            ->where('sender_id', '!=', $userId)
            ->when($lastReadAt, function ($query) use ($lastReadAt) {
                return $query->where('created_at', '>', $lastReadAt);
            }, function ($query) {
                return $query->whereNull('read_at');
            })
            ->count();
    }

    /**
     * Mark all messages as read for a user
     */
    public function markAsRead(int $userId): void
    {
        $this->participants()->updateExistingPivot($userId, [
            'last_read_at' => now(),
        ]);

        // Also mark individual messages as read
        $this->messages()
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Update typing status for a user
     */
    public function updateTypingStatus(int $userId, bool $isTyping): void
    {
        $this->participants()->updateExistingPivot($userId, [
            'is_typing' => $isTyping,
            'typing_at' => $isTyping ? now() : null,
        ]);
    }

    /**
     * Get users currently typing (within last 5 seconds)
     */
    public function whoIsTyping(): \Illuminate\Support\Collection
    {
        return $this->participants()
            ->where('is_typing', true)
            ->where('typing_at', '>', now()->subSeconds(5))
            ->get()
            ->pluck('name', 'id');
    }

    /**
     * Archive the conversation
     */
    public function archive(): void
    {
        $this->update(['status' => 'archived']);
    }

    /**
     * Restore the conversation
     */
    public function restore(): void
    {
        $this->update(['status' => 'active']);
    }

    /**
     * Check if user is participant
     */
    public function hasParticipant(int $userId): bool
    {
        return $this->participants()->where('user_id', $userId)->exists();
    }
}
