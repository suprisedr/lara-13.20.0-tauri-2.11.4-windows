<?php

use Spiral\RoadRunner\GRPC\Server;
use Spiral\RoadRunner\Worker;

ini_set('display_errors', 'stderr');
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$app->singleton(\App\Grpc\GrpcDispatcher::class);

$worker = Worker::create();
$server = new Server(options: ['debug' => env('APP_DEBUG', false)]);

$server->registerService(\App\Grpc\Interfaces\AuthServiceInterface::class, new \App\Grpc\Services\AuthGrpcService());
$server->registerService(\App\Grpc\Interfaces\CompanyServiceInterface::class, new \App\Grpc\Services\CompanyGrpcService());
$server->registerService(\App\Grpc\Interfaces\TransactionServiceInterface::class, new \App\Grpc\Services\TransactionGrpcService());
$server->registerService(\App\Grpc\Interfaces\AssetServiceInterface::class, new \App\Grpc\Services\AssetGrpcService());
$server->registerService(\App\Grpc\Interfaces\LeaseServiceInterface::class, new \App\Grpc\Services\LeaseGrpcService());
$server->registerService(\App\Grpc\Interfaces\InventoryServiceInterface::class, new \App\Grpc\Services\InventoryGrpcService());
$server->registerService(\App\Grpc\Interfaces\IntangibleServiceInterface::class, new \App\Grpc\Services\IntangibleGrpcService());
$server->registerService(\App\Grpc\Interfaces\InvoiceServiceInterface::class, new \App\Grpc\Services\InvoiceGrpcService());

$server->serve($worker);
