<?php

namespace App\Services;

use App\Enums\LeadStatus;
use App\Models\Lead;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LeadStatusService
{
    public function changeStatus(Lead $lead, LeadStatus $newStatus): void
    {
        if ($lead->status === $newStatus) {
            throw new InvalidArgumentException(
                'Lead already has this status.'
            );
        }

        DB::transaction(function () use ($lead, $newStatus) {
            $oldStatus = $lead->status;

            $lead->update([
                'status' => $newStatus,
            ]);

            $lead->statusHistories()->create([
                'from_status' => $oldStatus,
                'to_status' => $newStatus,
            ]);
        });

    }
}
