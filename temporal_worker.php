<?php

/**
 * Temporal workflow worker for month-end automated postings.
 *
 * Registers workflows and activities with the Temporal server via RoadRunner.
 * Start with: ./rr serve -c .rr.yaml
 */

declare(strict_types=1);

use App\Temporal\Activities\BackdateActivity;
use App\Temporal\Activities\MonthEndActivity;
use App\Temporal\Workflows\BackdateDepreciationWorkflow;
use App\Temporal\Workflows\MonthEndWorkflow;
use Temporal\WorkerFactory;

ini_set('display_errors', 'stderr');
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$factory = WorkerFactory::create();

$worker = $factory->newWorker('month-end');

$worker->registerWorkflowTypes(MonthEndWorkflow::class, BackdateDepreciationWorkflow::class);
$worker->registerActivity(MonthEndActivity::class);
$worker->registerActivity(BackdateActivity::class);

$factory->run();
