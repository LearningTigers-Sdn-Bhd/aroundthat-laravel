<?php

namespace App\Http\Middleware;

use App\Enums\IntegrationCapability;
use App\Models\Integration;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * `capability:places:read`: the calling integration must have the capability. Runs after `auth:sanctum`.
 */
class EnsureIntegrationCan
{
    /**
     * @param  Closure(Request): (Response)  $next
     *
     * @throws AuthorizationException
     */
    public function handle(Request $request, Closure $next, string $capability): Response
    {
        $integration = Auth::guard('sanctum')->user();

        if (! $integration instanceof Integration || ! $integration->hasCapability(IntegrationCapability::from($capability))) {
            throw new AuthorizationException(__('This API key cannot use this endpoint.'));
        }

        return $next($request);
    }
}
