<?php
/**
 * Application settings.
 */

declare(strict_types=1);

return [
    'name'              => 'Sales Prediction Management System',
    'short_name'        => 'SPMS',
    'timezone'          => 'Africa/Lagos',
    // Flask prediction service (Phase 2/3)
    'ml_api_base'       => 'http://127.0.0.1:5000',
    'ml_retrain_secret' => 'change-me-spms-secret',
    // Session
    'session_name'      => 'SPMSSESSID',
];
