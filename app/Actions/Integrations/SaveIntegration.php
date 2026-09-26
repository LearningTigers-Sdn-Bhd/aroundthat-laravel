<?php

namespace App\Actions\Integrations;

use App\Data\Forms\IntegrationData;
use App\Models\Integration;

/**
 * An admin adds an integration or changes its name, type, capabilities and access period.
 * A capability change applies to its existing API keys at once.
 */
class SaveIntegration
{
    public function create(IntegrationData $data): Integration
    {
        return Integration::query()->create($data->toModelAttributes());
    }

    public function update(Integration $integration, IntegrationData $data): Integration
    {
        $integration->update($data->toModelAttributes());

        return $integration;
    }
}
