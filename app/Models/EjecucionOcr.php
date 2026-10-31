<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EjecucionOcr extends Model
{
    protected $table = 'ejecuciones_ocr';

    // Log inmutable: solo created_at
    public $timestamps = false;

    protected $fillable = [
        'document_id',
        'status',
        'previous_status',
        'previous_extracted',
        'config_hash',
        'error',
        'created_at',
    ];

    protected $casts = [
        'previous_extracted' => 'array',
        'created_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(DocumentoOcr::class, 'document_id');
    }

    protected static function booted(): void
    {
        static::creating(function (self $ejecucion) {
            $ejecucion->created_at ??= now();
        });
    }
}