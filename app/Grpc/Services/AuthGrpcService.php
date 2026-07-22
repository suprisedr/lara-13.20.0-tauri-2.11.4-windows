<?php

namespace App\Grpc\Services;

use App\Grpc\Generated\AuthStatusResponse;
use App\Grpc\Generated\LoginRequest;
use App\Grpc\Generated\LoginResponse;
use App\Grpc\Generated\PBEmpty;
use App\Grpc\Generated\User;
use App\Grpc\GrpcDispatcher;
use Spiral\RoadRunner\GRPC\ContextInterface;

class AuthGrpcService implements \App\Grpc\Interfaces\AuthServiceInterface
{
    public function Login(ContextInterface $ctx, LoginRequest $request): LoginResponse
    {
        $dispatcher = app(GrpcDispatcher::class);

        $result = $dispatcher->dispatch('POST', '/api/auth/token', [
            'email' => $request->getEmail(),
            'password' => $request->getPassword(),
            'device_name' => $request->getDeviceName(),
        ]);

        $data = json_decode($result, true);

        $dispatcher->setBearerToken($data['token'] ?? '');

        $response = new LoginResponse();
        $response->setToken($data['token'] ?? '');

        if (isset($data['user'])) {
            $user = new User();
            $user->setId($data['user']['id'] ?? 0);
            $user->setName($data['user']['name'] ?? '');
            $user->setEmail($data['user']['email'] ?? '');
            $response->setUser($user);
        }

        return $response;
    }

    public function GetAuthStatus(ContextInterface $ctx, PBEmpty $request): AuthStatusResponse
    {
        $dispatcher = app(GrpcDispatcher::class);
        $authenticated = $dispatcher->getBearerToken() !== null;

        $response = new AuthStatusResponse();
        $response->setAuthenticated($authenticated);
        $response->setMessage($authenticated
            ? 'Bearer token is present. Ready to make authenticated requests.'
            : 'No bearer token. Please run the login tool.');

        return $response;
    }
}
