<?php

namespace App\Grpc\Services;

use App\Grpc\Generated\CreateItemRequest;
use App\Grpc\Generated\GetInventoryRequest;
use App\Grpc\Generated\GetItemRequest;
use App\Grpc\Generated\InventoryResponse;
use App\Grpc\Generated\ItemResponse;
use App\Grpc\Generated\MovementRequest;
use App\Grpc\Generated\MovementResponse;
use App\Grpc\Generated\ReverseWriteDownRequest;
use App\Grpc\Generated\UpdateItemRequest;
use App\Grpc\Generated\WriteDownRequest;
use App\Grpc\GrpcDispatcher;
use Spiral\RoadRunner\GRPC\ContextInterface;

class InventoryGrpcService implements \App\Grpc\Interfaces\InventoryServiceInterface
{
    public function GetInventory(ContextInterface $ctx, GetInventoryRequest $request): InventoryResponse
    {
        $params = ['company_id' => $request->getCompanyId()];
        if ($request->getSearch()) $params['search'] = $request->getSearch();
        $result = app(GrpcDispatcher::class)->dispatch('GET', '/api/inventory?'.http_build_query($params));
        return (new InventoryResponse())->setData($result);
    }

    public function GetInventoryItem(ContextInterface $ctx, GetItemRequest $request): ItemResponse
    {
        $result = app(GrpcDispatcher::class)->dispatch('GET', '/api/inventory/'.$request->getId());
        return (new ItemResponse())->setData($result);
    }

    public function CreateInventoryItem(ContextInterface $ctx, CreateItemRequest $request): ItemResponse
    {
        $result = app(GrpcDispatcher::class)->dispatch('POST', '/api/inventory', json_decode($request->getDataJson(), true));
        return (new ItemResponse())->setData($result)->setMessage('Item created.');
    }

    public function UpdateInventoryItem(ContextInterface $ctx, UpdateItemRequest $request): ItemResponse
    {
        $result = app(GrpcDispatcher::class)->dispatch('PATCH', '/api/inventory/'.$request->getId(), json_decode($request->getDataJson(), true));
        return (new ItemResponse())->setData($result)->setMessage('Item updated.');
    }

    public function RecordMovement(ContextInterface $ctx, MovementRequest $request): MovementResponse
    {
        $result = app(GrpcDispatcher::class)->dispatch('POST', '/api/inventory/'.$request->getInventoryItemId().'/movement', json_decode($request->getDataJson(), true));
        $data = json_decode($result, true);
        return (new MovementResponse())
            ->setSuccess($data['success'] ?? false)
            ->setMovementId($data['movement_id'] ?? 0)
            ->setQuantityOnHand($data['quantity_on_hand'] ?? 0);
    }

    public function WriteDown(ContextInterface $ctx, WriteDownRequest $request): ItemResponse
    {
        $result = app(GrpcDispatcher::class)->dispatch('POST', '/api/inventory/'.$request->getInventoryItemId().'/write-down', json_decode($request->getDataJson(), true));
        return (new ItemResponse())->setData($result);
    }

    public function ReverseWriteDown(ContextInterface $ctx, ReverseWriteDownRequest $request): ItemResponse
    {
        $result = app(GrpcDispatcher::class)->dispatch('POST', '/api/inventory/'.$request->getInventoryItemId().'/reverse-write-down', json_decode($request->getDataJson(), true));
        return (new ItemResponse())->setData($result);
    }
}
