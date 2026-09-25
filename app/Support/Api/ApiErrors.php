<?php

namespace App\Support\Api;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Every partner API error has one shape: `{ "errors": [{ "path", "code", "message" }] }`.
 *
 * `path` is a JSON pointer to the input field, such as `/events/0/outlet`, and is empty for errors about the whole
 * request. `code` is stable for partners to branch on; `message` is for people and may change.
 */
class ApiErrors
{
    /**
     * Validation codes for the rules that have their own, by rule name. Other failed rules read `invalid`.
     *
     * @var array<string, string>
     */
    protected const array RULE_CODES = [
        'Required' => 'required',
    ];

    public static function handles(Request $request): bool
    {
        return $request->is('api/*');
    }

    /**
     * The API response for the exception, or null to let Laravel render it (an unexpected error while debugging).
     */
    public static function render(Throwable $exception, Request $request): ?JsonResponse
    {
        if (! self::handles($request)) {
            return null;
        }

        return match (true) {
            $exception instanceof ValidationException => self::validation($exception),
            $exception instanceof AuthenticationException => self::response(401, 'unauthenticated', __('A valid API key is required.')),
            $exception instanceof AuthorizationException => self::response(403, 'forbidden', $exception->getMessage()),
            $exception instanceof ModelNotFoundException => self::response(404, 'not_found', __('Not found.')),
            $exception instanceof HttpExceptionInterface => self::http($exception),
            config('app.debug') === true => null,
            default => self::response(500, 'server_error', __('Something went wrong. Try again later.')),
        };
    }

    public static function response(int $status, string $code, string $message, string $path = ''): JsonResponse
    {
        return new JsonResponse(['errors' => [['path' => $path, 'code' => $code, 'message' => $message]]], $status);
    }

    protected static function http(HttpExceptionInterface $exception): JsonResponse
    {
        $status = $exception->getStatusCode();

        [$code, $message] = match ($status) {
            400 => ['invalid_query', $exception->getMessage() ?: __('The query is not valid.')],
            403 => ['forbidden', $exception->getMessage() ?: __('This action is not allowed.')],
            404 => ['not_found', __('Not found.')],
            405 => ['method_not_allowed', __('This method is not allowed here.')],
            429 => ['rate_limited', __('Too many requests. Slow down and try again.')],
            default => ['http_error', $exception->getMessage() ?: __('The request failed.')],
        };

        return self::response($status, $code, $message)->withHeaders($exception->getHeaders());
    }

    protected static function validation(ValidationException $exception): JsonResponse
    {
        $failedRules = $exception->validator->failed();
        $errors = [];

        foreach ($exception->errors() as $field => $messages) {
            $rules = array_keys($failedRules[$field] ?? []);

            foreach ($messages as $index => $message) {
                $errors[] = [
                    'path' => '/'.str_replace('.', '/', $field),
                    'code' => self::RULE_CODES[$rules[$index] ?? ''] ?? 'invalid',
                    'message' => $message,
                ];
            }
        }

        return new JsonResponse(['errors' => $errors], $exception->status);
    }
}
