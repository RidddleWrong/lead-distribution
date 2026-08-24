<?php

namespace App\Jobs;

use App\Models\Lead;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessDistributedLead implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 5;

    public function __construct(
        public readonly Lead $lead,
    ) {
    }

    public function handle(): void
    {
        Log::info('Distributed lead processed', [
            'lead_id' => $this->lead->id,
            'manager_id' => $this->lead->manager_id,
        ]);
    }
}
