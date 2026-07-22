<?php

namespace App\Grpc;

use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Auth;

class GrpcDispatcher
{
    private ?string $bearerToken = null;

    public function setBearerToken(string $token): void
    {
        $this->bearerToken = $token;
    }

    public function getBearerToken(): ?string
    {
        return $this->bearerToken;
    }

    public function dispatch(string $method, string $uri, array $data = []): string
    {
        $request = Request::create($uri, $method, $data);

        if ($this->bearerToken) {
            $request->headers->set('Authorization', 'Bearer '.$this->bearerToken);
        }

        $request->headers->set('Accept', 'application/json');

        $response = app()->handle($request);
        $content = $response->getContent();

        app()->terminate($request, $response);

        return $content ?: '{}';
    }
}
