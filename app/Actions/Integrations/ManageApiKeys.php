<?php

namespace App\Actions\Integrations;

use App\Models\Integration;
use App\Support\ActivityLog\AuditTrail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * An admin creates, rotates and revokes an integration's API keys. Only a hash of each key is stored,
 * so the full key is returned once, from create() or rotate(), and can never be shown again.
 */
class ManageApiKeys
{
    public function __construct(protected AuditTrail $audit) {}

    /**
     * @return string The full key, to show the admin once.
     */
    public function create(Integration $integration, string $name, ?Carbon $expiresAt = null): string
    {
        $name = trim($name);
        $key = $integration->createToken($name, ['*'], $expiresAt);

        $this->audit->record($integration, 'key_created', null, ['key' => $name]);

        return $key->plainTextToken;
    }

    /**
     * Replace the key with a new one that has the same name and expiry. The old key stops working at once.
     *
     * @return string The full new key, to show the admin once.
     *
     * @throws ValidationException
     */
    public function rotate(Integration $integration, PersonalAccessToken $token): string
    {
        $this->ensureBelongsTo($integration, $token);

        return DB::transaction(function () use ($integration, $token): string {
            $key = $integration->createToken($token->name, ['*'], $token->expires_at);
            $token->delete();

            $this->audit->record($integration, 'key_rotated', null, ['key' => $token->name]);

            return $key->plainTextToken;
        });
    }

    /**
     * @throws ValidationException
     */
    public function revoke(Integration $integration, PersonalAccessToken $token): void
    {
        $this->ensureBelongsTo($integration, $token);

        $token->delete();

        $this->audit->record($integration, 'key_revoked', null, ['key' => $token->name]);
    }

    /**
     * @throws ValidationException
     */
    protected function ensureBelongsTo(Integration $integration, PersonalAccessToken $token): void
    {
        if (! $token->tokenable()->is($integration)) {
            throw ValidationException::withMessages(['key' => __('This key does not belong to the integration.')]);
        }
    }
}
