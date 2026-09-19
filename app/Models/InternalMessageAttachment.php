<?php

namespace App\Models;

use Illuminate\Support\Facades\Storage;
use Orchid\Screen\AsSource;

class InternalMessageAttachment extends \Illuminate\Database\Eloquent\Model
{
    use AsSource;

    protected $fillable = [
        'message_id',
        'filename',
        'original_filename',
        'mime_type',
        'size',
        'disk',
        'path',
    ];

    /**
     * Get the full URL to the attachment
     */
    public function getUrlAttribute(): string
    {
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk($this->disk);

        return $disk->url($this->path);
    }

    /**
     * Get human-readable file size
     */
    public function getHumanSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * Get file extension
     */
    public function getExtensionAttribute(): string
    {
        return pathinfo($this->original_filename, PATHINFO_EXTENSION);
    }

    /**
     * Check if attachment is an image
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Check if attachment is a PDF
     */
    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    /**
     * Get icon based on file type
     */
    public function getIconAttribute(): string
    {
        if ($this->isImage()) {
            return 'photo';
        }

        if ($this->isPdf()) {
            return 'document';
        }

        return match ($this->extension) {
            'doc', 'docx' => 'document-text',
            'xls', 'xlsx' => 'table-cells',
            'ppt', 'pptx' => 'presentation-chart-bar',
            'zip', 'rar', '7z' => 'archive-box',
            default => 'paper-clip',
        };
    }

    /**
     * Delete the file from storage when model is deleted
     */
    protected static function booted(): void
    {
        static::deleting(function (InternalMessageAttachment $attachment) {
            Storage::disk($attachment->disk)->delete($attachment->path);
        });
    }

    protected $table = 'internal_message_attachments';

    protected $casts = [
        'message_id' => 'int',
        'size' => 'int',
    ];

    public function internal_message()
    {
        return $this->belongsTo(InternalMessage::class, 'message_id');
    }
}
