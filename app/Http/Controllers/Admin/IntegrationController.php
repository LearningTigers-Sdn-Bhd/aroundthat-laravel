<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Integrations\SaveIntegration;
use App\Data\Admin\IntegrationData;
use App\Data\Forms\IntegrationData as IntegrationFormData;
use App\Http\Controllers\Controller;
use App\Models\Integration;
use App\Support\QueryFilters\SearchFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * The partners that call the partner API, and their API keys.
 */
class IntegrationController extends Controller
{
    public function index(Request $request): Response
    {
        $integrations = QueryBuilder::for(Integration::class, $request)
            ->allowedFilters(
                SearchFilter::on(['name']),
                AllowedFilter::exact('type'),
            )
            ->allowedSorts('name', 'created_at')
            ->defaultSort('name')
            ->with('suspendedBy')
            ->withCount('tokens')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/integrations/index', [
            'integrations' => IntegrationData::collect($integrations, PaginatedDataCollection::class),
        ]);
    }

    public function store(IntegrationFormData $data, SaveIntegration $saveIntegration): RedirectResponse
    {
        $integration = $saveIntegration->create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Integration added. Create an API key for it next.')]);

        return to_route('admin.integrations.keys.index', $integration);
    }

    /**
     * The Access tab of an integration.
     */
    public function show(Integration $integration): Response
    {
        $integration->load('suspendedBy')->loadCount('tokens');

        return Inertia::render('admin/integrations/show', [
            'integration' => IntegrationData::fromModel($integration),
        ]);
    }

    public function update(Integration $integration, IntegrationFormData $data, SaveIntegration $saveIntegration): RedirectResponse
    {
        $saveIntegration->update($integration, $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Integration saved.')]);

        return back();
    }
}
