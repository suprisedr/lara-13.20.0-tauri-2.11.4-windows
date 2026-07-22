<?php

namespace App\Grpc\Interfaces;

use App\Grpc\Generated\{CreateItemRequest, GetInventoryRequest, GetItemRequest, InventoryResponse, ItemResponse, MovementRequest, MovementResponse, ReverseWriteDownRequest, UpdateItemRequest, WriteDownRequest};
use Spiral\RoadRunner\GRPC\ContextInterface;
use Spiral\RoadRunner\GRPC\ServiceInterface;

interface InventoryServiceInterface extends ServiceInterface
{
    const NAME = 'chainbook.InventoryService';

    public function GetInventory(ContextInterface $ctx, GetInventoryRequest $request): InventoryResponse;
    public function GetInventoryItem(ContextInterface $ctx, GetItemRequest $request): ItemResponse;
    public function CreateInventoryItem(ContextInterface $ctx, CreateItemRequest $request): ItemResponse;
    public function UpdateInventoryItem(ContextInterface $ctx, UpdateItemRequest $request): ItemResponse;
    public function RecordMovement(ContextInterface $ctx, MovementRequest $request): MovementResponse;
    public function WriteDown(ContextInterface $ctx, WriteDownRequest $request): ItemResponse;
    public function ReverseWriteDown(ContextInterface $ctx, ReverseWriteDownRequest $request): ItemResponse;
}
