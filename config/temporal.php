<?php

return [
    'address' => env('TEMPORAL_ADDRESS', 'localhost:7233'),
    'namespace' => env('TEMPORAL_NAMESPACE', 'default'),
    'task_queue' => env('TEMPORAL_TASK_QUEUE', 'month-end'),
];
