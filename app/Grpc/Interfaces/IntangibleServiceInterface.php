<?php

namespace App\Grpc\Interfaces;

use App\Grpc\Generated\{CreateIntangibleRequest, GetIntangibleRequest, GetIntangiblesRequest, IntangibleActionRequest, IntangibleResponse, IntangiblesResponse, UpdateIntangibleRequest};
use Spiral\RoadRunner\GRPC\ContextInterface;
use Spiral\RoadRunner\GRPC\ServiceInterface;

interface IntangibleServiceInterface extends ServiceInterface
{
    const NAME = 'chainbook.IntangibleService';

    public function GetIntangibles(ContextInterface $ctx, GetIntangiblesRequest $request): IntangiblesResponse;
    public function GetIntangible(ContextInterface $ctx, GetIntangibleRequest $request): IntangibleResponse;
    public function CreateIntangible(ContextInterface $ctx, CreateIntangibleRequest $request): IntangibleResponse;
    public function UpdateIntangible(ContextInterface $ctx, UpdateIntangibleRequest $request): IntangibleResponse;
    public function DisposeIntangible(ContextInterface $ctx, IntangibleActionRequest $request): IntangibleResponse;
    public function RevalueIntangible(ContextInterface $ctx, IntangibleActionRequest $request): IntangibleResponse;
    public function ImpairIntangible(ContextInterface $ctx, IntangibleActionRequest $request): IntangibleResponse;
    public function ReverseImpairment(ContextInterface $ctx, IntangibleActionRequest $request): IntangibleResponse;
    public function CapitaliseSubsequentCost(ContextInterface $ctx, IntangibleActionRequest $request): IntangibleResponse;
}
