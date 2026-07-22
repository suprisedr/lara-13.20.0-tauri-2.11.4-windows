<?php

namespace App\Grpc\Interfaces;

use App\Grpc\Generated\{CreateLeaseRequest, GetLeaseRequest, GetLeasesRequest, LeaseActionRequest, LeaseEventUpdate, LeaseResponse, LeasesResponse, ScheduleResponse, UpdateLeaseRequest, WatchRequest};
use Spiral\RoadRunner\GRPC\ContextInterface;
use Spiral\RoadRunner\GRPC\ServiceInterface;

interface LeaseServiceInterface extends ServiceInterface
{
    const NAME = 'chainbook.LeaseService';

    public function GetLeases(ContextInterface $ctx, GetLeasesRequest $request): LeasesResponse;
    public function GetLease(ContextInterface $ctx, GetLeaseRequest $request): LeaseResponse;
    public function CreateLease(ContextInterface $ctx, CreateLeaseRequest $request): LeaseResponse;
    public function UpdateLease(ContextInterface $ctx, UpdateLeaseRequest $request): LeaseResponse;
    public function ModifyLease(ContextInterface $ctx, LeaseActionRequest $request): LeaseResponse;
    public function ImpairLease(ContextInterface $ctx, LeaseActionRequest $request): LeaseResponse;
    public function ReverseLeaseImpairment(ContextInterface $ctx, LeaseActionRequest $request): LeaseResponse;
    public function TerminateLease(ContextInterface $ctx, LeaseActionRequest $request): LeaseResponse;
    public function GetLeaseSchedule(ContextInterface $ctx, GetLeaseRequest $request): ScheduleResponse;
    public function WatchLeaseEvents(ContextInterface $ctx, WatchRequest $request): LeaseEventUpdate;
}
