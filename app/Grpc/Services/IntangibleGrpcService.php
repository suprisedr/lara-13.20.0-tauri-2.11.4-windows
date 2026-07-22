<?php

namespace App\Grpc\Services;

use App\Grpc\Generated\CreateIntangibleRequest;
use App\Grpc\Generated\GetIntangibleRequest;
use App\Grpc\Generated\GetIntangiblesRequest;
use App\Grpc\Generated\IntangibleActionRequest;
use App\Grpc\Generated\IntangibleResponse;
use App\Grpc\Generated\IntangiblesResponse;
use App\Grpc\Generated\UpdateIntangibleRequest;
use App\Grpc\GrpcDispatcher;
use Spiral\RoadRunner\GRPC\ContextInterface;

class IntangibleGrpcService implements \App\Grpc\Interfaces\IntangibleServiceInterface
{
    public function GetIntangibles(ContextInterface $ctx, GetIntangiblesRequest $request): IntangiblesResponse
    {
        $params = ['company_id' => $request->getCompanyId()];
        if ($request->getStatus()) $params['status'] = $request->getStatus();
        $result = app(GrpcDispatcher::class)->dispatch('GET', '/api/intangibles?'.http_build_query($params));
        return (new IntangiblesResponse())->setData($result);
    }

    public function GetIntangible(ContextInterface $ctx, GetIntangibleRequest $request): IntangibleResponse
    {
        $result = app(GrpcDispatcher::class)->dispatch('GET', '/api/intangibles/'.$request->getId());
        return (new IntangibleResponse())->setData($result);
    }

    public function CreateIntangible(ContextInterface $ctx, CreateIntangibleRequest $request): IntangibleResponse
    {
        $result = app(GrpcDispatcher::class)->dispatch('POST', '/api/intangibles', json_decode($request->getDataJson(), true));
        return (new IntangibleResponse())->setData($result)->setMessage('Intangible asset created.');
    }

    public function UpdateIntangible(ContextInterface $ctx, UpdateIntangibleRequest $request): IntangibleResponse
    {
        $result = app(GrpcDispatcher::class)->dispatch('PATCH', '/api/intangibles/'.$request->getId(), json_decode($request->getDataJson(), true));
        return (new IntangibleResponse())->setData($result);
    }

    public function DisposeIntangible(ContextInterface $ctx, IntangibleActionRequest $request): IntangibleResponse
    {
        $result = app(GrpcDispatcher::class)->dispatch('POST', '/api/intangibles/'.$request->getId().'/dispose', json_decode($request->getDataJson(), true));
        return (new IntangibleResponse())->setData($result);
    }

    public function RevalueIntangible(ContextInterface $ctx, IntangibleActionRequest $request): IntangibleResponse
    {
        $result = app(GrpcDispatcher::class)->dispatch('POST', '/api/intangibles/'.$request->getId().'/revalue', json_decode($request->getDataJson(), true));
        return (new IntangibleResponse())->setData($result);
    }

    public function ImpairIntangible(ContextInterface $ctx, IntangibleActionRequest $request): IntangibleResponse
    {
        $result = app(GrpcDispatcher::class)->dispatch('POST', '/api/intangibles/'.$request->getId().'/impair', json_decode($request->getDataJson(), true));
        return (new IntangibleResponse())->setData($result);
    }

    public function ReverseImpairment(ContextInterface $ctx, IntangibleActionRequest $request): IntangibleResponse
    {
        $result = app(GrpcDispatcher::class)->dispatch('POST', '/api/intangibles/'.$request->getId().'/reverse-impairment', json_decode($request->getDataJson(), true));
        return (new IntangibleResponse())->setData($result);
    }

    public function CapitaliseSubsequentCost(ContextInterface $ctx, IntangibleActionRequest $request): IntangibleResponse
    {
        $result = app(GrpcDispatcher::class)->dispatch('POST', '/api/intangibles/'.$request->getId().'/capitalise', json_decode($request->getDataJson(), true));
        return (new IntangibleResponse())->setData($result);
    }
}
