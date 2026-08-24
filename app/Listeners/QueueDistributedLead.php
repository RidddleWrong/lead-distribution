<?php

namespace App\Listeners;

use App\Events\LeadDistributed;
use App\Jobs\ProcessDistributedLead;

class QueueDistributedLead
{
    public function handle(LeadDistributed $event): void
    {
        ProcessDistributedLead::dispatch(
            $event->lead
        );
    }
}
