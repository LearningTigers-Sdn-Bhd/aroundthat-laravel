<?php

namespace App\Http\Controllers\App;

use App\Data\Forms\ReportFiltersData;
use App\Data\Reports\ReportPageData;
use App\Data\Reports\ReportSummaryData;
use App\Enums\Ability;
use App\Http\Controllers\Controller;
use App\Support\Reports\Report;
use App\Support\Reports\ReportCsv;
use App\Support\Reports\ReportFilters;
use App\Support\Reports\ReportRegistry;
use App\Support\Reports\ReportScope;
use App\Support\Workspace;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The business's reports: the list, one report on the shared page, and its CSV. Owners and managers only.
 */
class ReportController extends Controller
{
    public function __construct(protected Workspace $workspace, protected ReportRegistry $reports) {}

    public function index(): Response
    {
        $this->workspace->authorize(Ability::ViewReports);

        return Inertia::render('app/reports/index', [
            'reports' => array_map(ReportSummaryData::fromReport(...), $this->reports->all()),
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function show(Request $request, string $report): Response
    {
        [$report, $filters] = $this->read($request, $report);

        return Inertia::render('app/reports/show', [
            'report' => ReportPageData::fromReport($report, $filters),
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function export(Request $request, string $report, ReportCsv $csv): StreamedResponse
    {
        [$report, $filters] = $this->read($request, $report);

        return $csv->download($report, $filters);
    }

    /**
     * The report and its filters from the query string, once the member may see reports at all.
     *
     * @return array{Report, ReportFilters}
     *
     * @throws ValidationException
     */
    protected function read(Request $request, string $key): array
    {
        $this->workspace->authorize(Ability::ViewReports);

        $report = $this->reports->find($key);
        $data = ReportFiltersData::validateAndCreate($request->query());

        return [$report, $data->toFilters($report, ReportScope::for($this->workspace->membership()))];
    }
}
