<?php

namespace App\Grpc\Services;

use App\Grpc\Generated\CreateLeaseRequest;
use App\Grpc\Generated\GetLeaseRequest;
use App\Grpc\Generated\GetLeasesRequest;
use App\Grpc\Generated\LeaseActionRequest;
use App\Grpc\Generated\LeaseResponse;
use App\Grpc\Generated\LeasesResponse;
use App\Grpc\Generated\ScheduleResponse;
use App\Grpc\Generated\UpdateLeaseRequest;
use App\Grpc\Generated\WatchRequest;
use App\Grpc\Generated\LeaseEventUpdate;
use Spiral\RoadRunner\GRPC\ContextInterface;

class LeaseGrpcService implements \App\Grpc\Interfaces\LeaseServiceInterface
{
    public function GetLeases(ContextInterface $ctx, GetLeasesRequest $request): LeasesResponse
    {
        $result = app('grpc.dispatcher')->dispatch('GET', '/api/leases', [
            'company_id' => $request->getCompanyId(),
            'role' => $request->getRole() ?: null,
            'classification' => $request->getClassification() ?: null,
            'status' => $request->getStatus() ?: null,
        ]);

        $response = new LeasesResponse();
        $response->setData($result);
        return $response;
    }

    public function GetLease(ContextInterface $ctx, GetLeaseRequest $request): LeaseResponse
    {
        $result = app('grpc.dispatcher')->dispatch('GET', '/api/leases/'.$request->getId());

        $response = new LeaseResponse();
        $response->setData($result);
        return $response;
    }

    public function CreateLease(ContextInterface $ctx, CreateLeaseRequest $request): LeaseResponse
    {
        $result = app('grpc.dispatcher')->dispatch('POST', '/api/leases', json_decode($request->getDataJson(), true));

        $response = new LeaseResponse();
        $response->setData($result);
        $response->setMessage('Lease created successfully.');
        return $response;
    }

    public function UpdateLease(ContextInterface $ctx, UpdateLeaseRequest $request): LeaseResponse
    {
        $result = app('grpc.dispatcher')->dispatch('PATCH', '/api/leases/'.$request->getId(), json_decode($request->getDataJson(), true));

        $response = new LeaseResponse();
        $response->setData($result);
        $response->setMessage('Lease updated.');
        return $response;
    }

    public function ModifyLease(ContextInterface $ctx, LeaseActionRequest $request): LeaseResponse
    {
        $result = app('grpc.dispatcher')->dispatch('POST', '/api/leases/'.$request->getId().'/modify', json_decode($request->getDataJson(), true));

        $response = new LeaseResponse();
        $response->setData($result);
        return $response;
    }

    public function ImpairLease(ContextInterface $ctx, LeaseActionRequest $request): LeaseResponse
    {
        $result = app('grpc.dispatcher')->dispatch('POST', '/api/leases/'.$request->getId().'/impair', json_decode($request->getDataJson(), true));

        $response = new LeaseResponse();
        $response->setData($result);
        return $response;
    }

    public function ReverseLeaseImpairment(ContextInterface $ctx, LeaseActionRequest $request): LeaseResponse
    {
        $result = app('grpc.dispatcher')->dispatch('POST', '/api/leases/'.$request->getId().'/reverse-impairment', json_decode($request->getDataJson(), true));

        $response = new LeaseResponse();
        $response->setData($result);
        return $response;
    }

    public function TerminateLease(ContextInterface $ctx, LeaseActionRequest $request): LeaseResponse
    {
        $result = app('grpc.dispatcher')->dispatch('POST', '/api/leases/'.$request->getId().'/terminate', json_decode($request->getDataJson(), true));

        $response = new LeaseResponse();
        $response->setData($result);
        return $response;
    }

    public function GetLeaseSchedule(ContextInterface $ctx, GetLeaseRequest $request): ScheduleResponse
    {
        $result = app('grpc.dispatcher')->dispatch('GET', '/api/leases/'.$request->getId().'/schedule');

        $response = new ScheduleResponse();
        $response->setData($result);
        return $response;
    }

    public function WatchLeaseEvents(ContextInterface $ctx, WatchRequest $request): LeaseEventUpdate
    {
        $response = new LeaseEventUpdate();
        $response->setLeaseId(0);
        $response->setMessage('Streaming not yet implemented — use Reverb WebSockets.');
        return $response;
    }
}
