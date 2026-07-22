<?php

namespace App\Temporal\Workflows;

use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;

#[WorkflowInterface]
interface BackdateDepreciationWorkflowInterface
{
    #[WorkflowMethod(name: 'BackdateDepreciation')]
    public function run(int $companyId): \Generator;
}
