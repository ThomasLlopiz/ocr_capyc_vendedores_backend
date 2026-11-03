<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentoOcr extends Model
{
    protected $table = 'documentos_ocr';

    protected $fillable = [
        'original_name',
        'storage_path',
        'sha256',
        'status',
        'extracted',
        'error',
        'assigned_seller_code',
        'seller_portfolio',
        'uploaded_by',
    ];

    protected $casts = [
        'extracted' => 'array',
        'seller_portfolio' => 'boolean',
    ];

    // Estados
    public const STATUS_PENDING = 'pending';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_REVIEW = 'review';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_FAILED = 'failed';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_QUEUED,
        self::STATUS_PROCESSING,
        self::STATUS_REVIEW,
        self::STATUS_ACCEPTED,
        self::STATUS_FAILED,
    ];

    // Relaciones
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function ejecuciones(): HasMany
    {
        return $this->hasMany(EjecucionOcr::class, 'document_id')
            ->orderByDesc('created_at');
    }

    // Helpers
    public function pdfAbsolutePath(): string
    {
        // Linux absoluto (/media/...) o Windows absoluto (C:\... o C:/...)
        if (
            str_starts_with($this->storage_path, '/')
            || preg_match('/^[a-zA-Z]:[\\\\\/]/', $this->storage_path)
        ) {
            return $this->storage_path;
        }

        // Relativo: se resuelve desde la raíz del proyecto Laravel
        return base_path($this->storage_path);
    }

    public function pdfExists(): bool
    {
        return is_file($this->pdfAbsolutePath());
    }

    public function estaProcesando(): bool
    {
        return in_array($this->status, [
            self::STATUS_QUEUED,
            self::STATUS_PROCESSING,
        ], true);
    }

    // Scopes
    public function scopeConEstado($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeVisiblesParaVendedor($query, array $codes)
    {
        if (empty($codes)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('seller_portfolio', true)
            ->whereIn('assigned_seller_code', $codes);
    }
}