<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscribed
{
    private array $exemptRoutes = [
        'subscriptions.*',
        'webhooks.*',
        'login',
        'register',
        'logout',
        'password.*',
        'verification.*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isExempt($request)) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if (! $user->subscribed()) {
            return redirect()->route('subscriptions.plans');
        }

        return $next($request);
    }

    private function isExempt(Request $request): bool
    {
        $routeName = $request->route()?->getName();

        if (! $routeName) {
            return true;
        }

        foreach ($this->exemptRoutes as $pattern) {
            if ($routeName === $pattern || fnmatch($pattern, $routeName)) {
                return true;
            }
        }

        return false;
    }
}
