<?php

namespace App\Temporal\Workflows;

use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;

#[WorkflowInterface]
interface MonthEndWorkflowInterface
{
    #[WorkflowMethod(name: 'MonthEndClose')]
    public function run(int $companyId, string $monthEnd): \Generator;
}
