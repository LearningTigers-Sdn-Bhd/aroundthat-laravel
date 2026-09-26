<?php

namespace App\Http\Controllers\Admin;

use App\Data\Admin\ActivityData;
use App\Data\Admin\IntegrationData;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Integration;
use Inertia\Inertia;
use Inertia\Response;

class IntegrationActivityController extends Controller
{
    /**
     * The Activity tab of an integration: the latest changes to it and its keys.
     */
    public function index(Integration $integration): Response
    {
        $integration->load('suspendedBy')->loadCount('tokens');

        return Inertia::render('admin/integrations/activity', [
            'integration' => IntegrationData::fromModel($integration),
            'activities' => Inertia::defer(fn () => ActivityData::collect(
                Activity::forSubject($integration)->with('causer')->latest('id')->limit(100)->get(),
            )),
        ]);
    }
}
