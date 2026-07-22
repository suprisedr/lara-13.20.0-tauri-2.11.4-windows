<?php

namespace App\Grpc\Interfaces;

use App\Grpc\Generated\{AssetActionRequest, AssetResponse, AssetsResponse, CreateAssetRequest, DisposeAssetRequest, GetAssetRequest, GetAssetsRequest, ScheduleResponse, UpdateAssetRequest};
use Spiral\RoadRunner\GRPC\ContextInterface;
use Spiral\RoadRunner\GRPC\ServiceInterface;

interface AssetServiceInterface extends ServiceInterface
{
    const NAME = 'chainbook.AssetService';

    public function GetAssets(ContextInterface $ctx, GetAssetsRequest $request): AssetsResponse;
    public function GetAsset(ContextInterface $ctx, GetAssetRequest $request): AssetResponse;
    public function CreateAsset(ContextInterface $ctx, CreateAssetRequest $request): AssetResponse;
    public function UpdateAsset(ContextInterface $ctx, UpdateAssetRequest $request): AssetResponse;
    public function DisposeAsset(ContextInterface $ctx, DisposeAssetRequest $request): AssetResponse;
    public function GetDepreciationSchedule(ContextInterface $ctx, GetAssetRequest $request): ScheduleResponse;
    public function RevalueAsset(ContextInterface $ctx, AssetActionRequest $request): AssetResponse;
    public function ImpairAsset(ContextInterface $ctx, AssetActionRequest $request): AssetResponse;
    public function ReverseImpairment(ContextInterface $ctx, AssetActionRequest $request): AssetResponse;
    public function CapitaliseSubsequentCost(ContextInterface $ctx, AssetActionRequest $request): AssetResponse;
}
