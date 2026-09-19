<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Orchid\Screen\AsSource;

class InternalMessage extends \Illuminate\Database\Eloquent\Model
{
    use AsSource;

    protected $fillable = [
        'clinic_id',
        'sender_id',
        'recipient_id',
        'parent_id',
        'subject',
        'body',
        'priority',
        'category',
        'related_patient_id',
        'related_appointment_id',
        'read_at',
        'archived_at',
        'is_starred',
    ];

    /**
     * Priority options
     */
    public const PRIORITY_LOW = 'low';

    public const PRIORITY_NORMAL = 'normal';

    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_URGENT = 'urgent';

    /**
     * Category options
     */
    public const CATEGORY_GENERAL = 'general';

    public const CATEGORY_PATIENT = 'patient';

    public const CATEGORY_APPOINTMENT = 'appointment';

    public const CATEGORY_PRESCRIPTION = 'prescription';

    public const CATEGORY_LAB_RESULT = 'lab_result';

    public const CATEGORY_REFERRAL = 'referral';

    public const CATEGORY_BILLING = 'billing';

    public const CATEGORY_SYSTEM = 'system';

    /**
     * Get priority options for forms
     */
    public static function getPriorityOptions(): array
    {
        return [
            self::PRIORITY_LOW => __('Scăzută'),
            self::PRIORITY_NORMAL => __('Normală'),
            self::PRIORITY_HIGH => __('Ridicată'),
            self::PRIORITY_URGENT => __('Urgentă'),
        ];
    }

    /**
     * Get category options for forms
     */
    public static function getCategoryOptions(): array
    {
        return [
            self::CATEGORY_GENERAL => __('General'),
            self::CATEGORY_PATIENT => __('Pacient'),
            self::CATEGORY_APPOINTMENT => __('Programare'),
            self::CATEGORY_PRESCRIPTION => __('Rețetă'),
            self::CATEGORY_LAB_RESULT => __('Rezultat laborator'),
            self::CATEGORY_REFERRAL => __('Trimitere'),
            self::CATEGORY_BILLING => __('Facturare'),
            self::CATEGORY_SYSTEM => __('Sistem'),
        ];
    }

    /**
     * Scope for inbox (received messages)
     */
    public function scopeInbox(Builder $query, int $userId): Builder
    {
        return $query->where('recipient_id', $userId)
            ->whereNull('archived_at')
            ->whereNull('deleted_at');
    }

    /**
     * Scope for sent messages
     */
    public function scopeSent(Builder $query, int $userId): Builder
    {
        return $query->where('sender_id', $userId)
            ->whereNull('deleted_at');
    }

    /**
     * Scope for archived messages
     */
    public function scopeArchived(Builder $query, int $userId): Builder
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('recipient_id', $userId)
                ->orWhere('sender_id', $userId);
        })->whereNotNull('archived_at');
    }

    /**
     * Scope for starred messages
     */
    public function scopeStarred(Builder $query, int $userId): Builder
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('recipient_id', $userId)
                ->orWhere('sender_id', $userId);
        })->where('is_starred', true);
    }

    /**
     * Scope for unread messages
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope for clinic
     */
    public function scopeForClinic(Builder $query, int $clinicId): Builder
    {
        return $query->where('clinic_id', $clinicId);
    }

    /**
     * Scope for category
     */
    public function scopeCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for priority
     */
    public function scopePriority(Builder $query, string $priority): Builder
    {
        return $query->where('priority', $priority);
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
     * Mark message as unread
     */
    public function markAsUnread(): void
    {
        $this->update(['read_at' => null]);
    }

    /**
     * Toggle starred status
     */
    public function toggleStar(): void
    {
        $this->update(['is_starred' => ! $this->is_starred]);
    }

    /**
     * Archive message
     */
    public function archive(): void
    {
        $this->update(['archived_at' => now()]);
    }

    /**
     * Unarchive message
     */
    public function unarchive(): void
    {
        $this->update(['archived_at' => null]);
    }

    /**
     * Check if message is read
     */
    public function isRead(): bool
    {
        return ! is_null($this->read_at);
    }

    /**
     * Check if message is archived
     */
    public function isArchived(): bool
    {
        return ! is_null($this->archived_at);
    }

    /**
     * Get thread (all messages in conversation)
     */
    public function getThread(): \Illuminate\Database\Eloquent\Collection
    {
        $rootId = $this->parent_id ?? $this->id;

        return self::where('id', $rootId)
            ->orWhere('parent_id', $rootId)
            ->orderBy('created_at', 'asc')
            ->with(['sender', 'recipient', 'attachments'])
            ->get();
    }

    /**
     * Get priority badge color
     */
    public function getPriorityColor(): string
    {
        return match ($this->priority) {
            self::PRIORITY_URGENT => 'red',
            self::PRIORITY_HIGH => 'orange',
            self::PRIORITY_NORMAL => 'blue',
            self::PRIORITY_LOW => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get category icon
     */
    public function getCategoryIcon(): string
    {
        return match ($this->category) {
            self::CATEGORY_PATIENT => 'user',
            self::CATEGORY_APPOINTMENT => 'calendar',
            self::CATEGORY_PRESCRIPTION => 'document-text',
            self::CATEGORY_LAB_RESULT => 'beaker',
            self::CATEGORY_REFERRAL => 'arrow-right',
            self::CATEGORY_BILLING => 'currency-dollar',
            self::CATEGORY_SYSTEM => 'cog',
            default => 'envelope',
        };
    }

    use SoftDeletes;

    protected $table = 'internal_messages';

    protected $casts = [
        'clinic_id' => 'int',
        'sender_id' => 'int',
        'recipient_id' => 'int',
        'parent_id' => 'int',
        'related_patient_id' => 'int',
        'related_appointment_id' => 'int',
        'read_at' => 'datetime',
        'archived_at' => 'datetime',
        'is_starred' => 'bool',
    ];

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function parent()
    {
        return $this->belongsTo(InternalMessage::class, 'parent_id');
    }

    public function related_patient()
    {
        return $this->belongsTo(Patient::class, 'related_patient_id');
    }

    public function related_appointment()
    {
        return $this->belongsTo(Appointment::class, 'related_appointment_id');
    }

    public function replies()
    {
        return $this->hasMany(InternalMessage::class, 'parent_id');
    }

    public function attachments()
    {
        return $this->hasMany(InternalMessageAttachment::class, 'message_id');
    }
}
