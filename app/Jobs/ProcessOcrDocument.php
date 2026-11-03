<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Log;
use Illuminate\Support\Facdes\Logs;
class ProcessOcrDocument extends Model
{
    public function failed(\Throwable $e): void
    {
        Log::error('ProcessOcrDocument failed', [
            'documentId' => $this->documentId,
            'runId' => $this->runId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}