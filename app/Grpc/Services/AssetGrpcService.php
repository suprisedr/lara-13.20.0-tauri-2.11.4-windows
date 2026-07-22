<?php

namespace App\Grpc\Services;

use App\Grpc\Generated\AssetActionRequest;
use App\Grpc\Generated\AssetResponse;
use App\Grpc\Generated\AssetsResponse;
use App\Grpc\Generated\CreateAssetRequest;
use App\Grpc\Generated\DisposeAssetRequest;
use App\Grpc\Generated\GetAssetRequest;
use App\Grpc\Generated\GetAssetsRequest;
use App\Grpc\Generated\ScheduleResponse;
use App\Grpc\Generated\UpdateAssetRequest;
use App\Grpc\GrpcDispatcher;
use Spiral\RoadRunner\GRPC\ContextInterface;

class AssetGrpcService implements \App\Grpc\Interfaces\AssetServiceInterface
{
    public function GetAssets(ContextInterface $ctx, GetAssetsRequest $request): AssetsResponse
    {
        $params = ['company_id' => $request->getCompanyId()];
        if ($request->getStatus()) $params['status'] = $request->getStatus();
        if ($request->getPpeClass()) $params['ppe_class'] = $request->getPpeClass();
        $result = app(GrpcDispatcher::class)->dispatch('GET', '/api/assets?'.http_build_query($params));
        return (new AssetsResponse())->setData($result);
    }

    public function GetAsset(ContextInterface $ctx, GetAssetRequest $request): AssetResponse
    {
        $result = app(GrpcDispatcher::class)->dispatch('GET', '/api/assets/'.$request->getId());
        return (new AssetResponse())->setData($result);
    }

    public function CreateAsset(ContextInterface $ctx, CreateAssetRequest $request): AssetResponse
    {
        $result = app(GrpcDispatcher::class)->dispatch('POST', '/api/assets', json_decode($request->getDataJson(), true));
        return (new AssetResponse())->setData($result);
    }

    public function UpdateAsset(ContextInterface $ctx, UpdateAssetRequest $request): AssetResponse
    {
        $result = app(GrpcDispatcher::class)->dispatch('PATCH', '/api/assets/'.$request->getId(), json_decode($request->getDataJson(), true));
        return (new AssetResponse())->setData($result);
    }

    public function DisposeAsset(ContextInterface $ctx, DisposeAssetRequest $request): AssetResponse
    {
        $result = app(GrpcDispatcher::class)->dispatch('POST', '/api/assets/'.$request->getId().'/dispose', json_decode($request->getDataJson(), true));
        return (new AssetResponse())->setData($result);
    }

    public function GetDepreciationSchedule(ContextInterface $ctx, GetAssetRequest $request): ScheduleResponse
    {
        $result = app(GrpcDispatcher::class)->dispatch('GET', '/api/assets/'.$request->getId().'/depreciation-schedule');
        return (new ScheduleResponse())->setData($result);
    }

    public function RevalueAsset(ContextInterface $ctx, AssetActionRequest $request): AssetResponse
    {
        $result = app(GrpcDispatcher::class)->dispatch('POST', '/api/assets/'.$request->getId().'/revalue', json_decode($request->getDataJson(), true));
        return (new AssetResponse())->setData($result);
    }

    public function ImpairAsset(ContextInterface $ctx, AssetActionRequest $request): AssetResponse
    {
        $result = app(GrpcDispatcher::class)->dispatch('POST', '/api/assets/'.$request->getId().'/impair', json_decode($request->getDataJson(), true));
        return (new AssetResponse())->setData($result);
    }

    public function ReverseImpairment(ContextInterface $ctx, AssetActionRequest $request): AssetResponse
    {
        $result = app(GrpcDispatcher::class)->dispatch('POST', '/api/assets/'.$request->getId().'/reverse-impairment', json_decode($request->getDataJson(), true));
        return (new AssetResponse())->setData($result);
    }

    public function CapitaliseSubsequentCost(ContextInterface $ctx, AssetActionRequest $request): AssetResponse
    {
        $result = app(GrpcDispatcher::class)->dispatch('POST', '/api/assets/'.$request->getId().'/capitalise', json_decode($request->getDataJson(), true));
        return (new AssetResponse())->setData($result);
    }
}
