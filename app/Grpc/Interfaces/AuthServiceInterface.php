<?php

namespace App\Grpc\Interfaces;

use App\Grpc\Generated\AuthStatusResponse;
use App\Grpc\Generated\LoginRequest;
use App\Grpc\Generated\LoginResponse;
use App\Grpc\Generated\PBEmpty;
use Spiral\RoadRunner\GRPC\ContextInterface;
use Spiral\RoadRunner\GRPC\ServiceInterface;

interface AuthServiceInterface extends ServiceInterface
{
    const NAME = 'chainbook.AuthService';

    public function Login(ContextInterface $ctx, LoginRequest $request): LoginResponse;
    public function GetAuthStatus(ContextInterface $ctx, PBEmpty $request): AuthStatusResponse;
}
