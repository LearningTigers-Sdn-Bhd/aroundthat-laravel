<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Integrations\ManageApiKeys;
use App\Data\Admin\ApiKeyData as ApiKeyRowData;
use App\Data\Admin\IntegrationData;
use App\Data\Forms\ApiKeyData;
use App\Http\Controllers\Controller;
use App\Models\Integration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * An admin creates, rotates and revokes an integration's API keys. A new key is flashed once as `api_key`.
 */
class ApiKeyController extends Controller
{
    /**
     * The API keys tab of an integration. The key itself is never sent, only its name and dates.
     */
    public function index(Integration $integration): Response
    {
        $integration->load('suspendedBy')->loadCount('tokens');

        return Inertia::render('admin/integrations/keys', [
            'integration' => IntegrationData::fromModel($integration),
            'keys' => ApiKeyRowData::collect(
                $integration->tokens()->latest('id')->get()->map(fn (PersonalAccessToken $token): ApiKeyRowData => ApiKeyRowData::fromModel($token)),
            ),
        ]);
    }

    public function store(Integration $integration, ApiKeyData $data, ManageApiKeys $keys): RedirectResponse
    {
        $key = $keys->create($integration, $data->name, $data->expiresAt());

        return $this->showOnce($data->name, $key);
    }

    /**
     * @throws ValidationException
     */
    public function rotate(Integration $integration, int $key, ManageApiKeys $keys): RedirectResponse
    {
        $token = $this->findKey($integration, $key);

        return $this->showOnce($token->name, $keys->rotate($integration, $token));
    }

    /**
     * @throws ValidationException
     */
    public function destroy(Integration $integration, int $key, ManageApiKeys $keys): RedirectResponse
    {
        $keys->revoke($integration, $this->findKey($integration, $key));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('API key revoked.')]);

        return back();
    }

    protected function findKey(Integration $integration, int $key): PersonalAccessToken
    {
        /** @var PersonalAccessToken */
        return $integration->tokens()->findOrFail($key);
    }

    protected function showOnce(string $name, string $key): RedirectResponse
    {
        Inertia::flash('api_key', ['name' => $name, 'key' => $key]);

        return back();
    }
}
