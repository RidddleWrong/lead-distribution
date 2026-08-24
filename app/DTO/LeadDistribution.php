<?php

namespace App\DTO;

use App\Models\Lead;
use App\Models\Manager;

readonly class LeadDistribution
{
    public function __construct(
        public Lead $lead,
        public Manager $manager
    ) {
    }
}
