<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Give every API request an ID: the partner's own `X-Request-Id` when it is safe to echo back, or a new one.
 * The ID is returned in the `X-Request-Id` header and added to every log line, so a partner can quote it.
 */
class AssignRequestId
{
    public const string HEADER = 'X-Request-Id';

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $sent = (string) $request->header(self::HEADER);
        $requestId = preg_match('/^[A-Za-z0-9._-]{8,100}$/', $sent) === 1 ? $sent : (string) Str::uuid();

        Context::add('request_id', $requestId);

        $response = $next($request);
        $response->headers->set(self::HEADER, $requestId);

        return $response;
    }
}
