<?php

namespace App\Models;

use App\Events\MessageSent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Orchid\Screen\AsSource;

class Message extends Model
{
    use AsSource;

    /** @use HasFactory<\Database\Factories\MessageFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'body',
        'attachments_json',
        'metadata',
        'read_at',
    ];

    protected $casts = [
        'attachments_json' => 'array',
        'metadata' => 'array',
        'read_at' => 'datetime',
    ];

    protected $dispatchesEvents = [
        'created' => MessageSent::class,
    ];

    /**
     * Get the conversation this message belongs to
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the user who sent this message
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Mark message as read
     */
    public function markAsRead(): void
    {
        if (is_null($this->read_at)) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Check if message has been read
     */
    public function isRead(): bool
    {
        return ! is_null($this->read_at);
    }

    /**
     * Get attachments
     */
    public function getAttachments(): array
    {
        return $this->attachments_json ?? [];
    }

    /**
     * Add attachment
     */
    public function addAttachment(string $filename, string $url, string $type, int $size): void
    {
        $attachments = $this->attachments_json ?? [];
        $attachments[] = [
            'filename' => $filename,
            'url' => $url,
            'type' => $type,
            'size' => $size,
            'uploaded_at' => now()->toISOString(),
        ];

        $this->update(['attachments_json' => $attachments]);
    }

    /**
     * Boot method to update conversation's last_message_at
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($message) {
            $message->conversation->update([
                'last_message_at' => $message->created_at,
            ]);
        });
    }
}
