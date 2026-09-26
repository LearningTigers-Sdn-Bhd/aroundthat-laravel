<?php

use App\Enums\ReportGrouping;
use App\Enums\ReportValueFormat;
use App\Models\Business;
use App\Support\Reports\Report;
use App\Support\Reports\ReportColumn;
use App\Support\Reports\ReportCsv;
use App\Support\Reports\ReportFilters;
use App\Support\Reports\ReportPeriod;
use App\Support\Reports\ReportScope;
use Carbon\CarbonImmutable;
use Illuminate\Testing\TestResponse;

function csvReport(array $rows): Report
{
    return new class($rows) implements Report
    {
        public function __construct(protected array $fixedRows) {}

        public function key(): string
        {
            return 'sample';
        }

        public function title(): string
        {
            return 'Sample';
        }

        public function question(): string
        {
            return 'What does the CSV hold?';
        }

        public function groupings(): array
        {
            return [ReportGrouping::Outlet];
        }

        public function filters(): array
        {
            return [];
        }

        public function columns(ReportFilters $filters): array
        {
            return [
                new ReportColumn('label', 'Outlet', ReportValueFormat::Text),
                new ReportColumn('used', 'Vouchers used', ReportValueFormat::Count),
                new ReportColumn('discount', 'Discount given', ReportValueFormat::Money),
            ];
        }

        public function chartSeries(): array
        {
            return [];
        }

        public function rows(ReportFilters $filters): array
        {
            return $this->fixedRows;
        }

        public function summary(ReportFilters $filters): array
        {
            return [];
        }
    };
}

function csvFilters(): ReportFilters
{
    return new ReportFilters(
        scope: new ReportScope(Business::factory()->make(), [], false),
        period: ReportPeriod::custom('2026-09-01', '2026-09-24', 'Asia/Kuala_Lumpur'),
        grouping: ReportGrouping::Outlet,
    );
}

test('the CSV is named after the report and its dates', function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-25 04:00:00', 'UTC'));

    $response = TestResponse::fromBaseResponse((new ReportCsv)->download(csvReport([]), csvFilters()));

    $response->assertDownload('sample-2026-09-01-to-2026-09-24.csv')
        ->assertHeader('Content-Type', 'text/csv; charset=utf-8');
});

test('the CSV has a header line and plain values, with hidden values left empty', function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-25 04:00:00', 'UTC'));
    $report = csvReport([
        ['key' => 'a', 'label' => 'Bangsar, Level 2', 'used' => 12, 'discount' => '1250.50', 'hidden' => false],
        ['key' => 'b', 'label' => 'Partner cafe', 'used' => null, 'discount' => null, 'hidden' => true],
    ]);

    $response = TestResponse::fromBaseResponse((new ReportCsv)->download($report, csvFilters()));

    expect($response->streamedContent())->toBe("Outlet,\"Vouchers used\",\"Discount given\"\n\"Bangsar, Level 2\",12,1250.50\n\"Partner cafe\",,\n");
});

test('a name that a spreadsheet would run as a formula is escaped', function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-25 04:00:00', 'UTC'));
    $report = csvReport([['key' => 'a', 'label' => '=HYPERLINK("http://evil.test")', 'used' => 1, 'discount' => '5.00', 'hidden' => false]]);

    $response = TestResponse::fromBaseResponse((new ReportCsv)->download($report, csvFilters()));

    expect($response->streamedContent())->toContain("\"'=HYPERLINK(\"\"http://evil.test\"\")\",1,5.00");
});
