<?php

namespace Tests\Feature;

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Services\LeadStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_lead_status_can_be_changed_with_history(): void
    {
        $lead = Lead::create([
            'status' => LeadStatus::NEW,
        ]);

        $service = app(LeadStatusService::class);

        $service->changeStatus(
            $lead,
            LeadStatus::IN_PROGRESS
        );

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'status' => LeadStatus::IN_PROGRESS->value,
        ]);

        $this->assertDatabaseHas('lead_status_histories', [
            'lead_id' => $lead->id,
            'from_status' => LeadStatus::NEW->value,
            'to_status' => LeadStatus::IN_PROGRESS->value,
        ]);
    }

    public function test_lead_status_cannot_be_changed_to_the_same_status(): void
    {
        $lead = Lead::create([
            'status' => LeadStatus::NEW,
        ]);

        $service = app(LeadStatusService::class);

        $this->expectException(\InvalidArgumentException::class);

        $service->changeStatus(
            $lead,
            LeadStatus::NEW
        );
    }

    public function test_transaction_rolls_back_changes_when_exception_occurs(): void
    {
        $lead = Lead::create([
            'status' => LeadStatus::NEW,
        ]);

        try {
            \DB::transaction(function () use ($lead) {
                $lead->update([
                    'status' => LeadStatus::IN_PROGRESS,
                ]);

                throw new \RuntimeException('Test exception');
            });
        } catch (\RuntimeException) {
            // Expected exception.
        }

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'status' => LeadStatus::NEW->value,
        ]);
    }

}
