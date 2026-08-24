<?php

use App\Http\Controllers\LeadDistributionController;
use Illuminate\Support\Facades\Route;

Route::post(
    '/leads/distribute',
    LeadDistributionController::class
);
