<?php

namespace App\Support\Reports;

use App\Enums\ReportValueFormat;
use SplFileObject;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * A report's table as a CSV download: the column labels, then one line per row with plain numbers. Hidden values
 * stay empty.
 */
final class ReportCsv
{
    public function download(Report $report, ReportFilters $filters): StreamedResponse
    {
        $columns = $report->columns($filters);
        $rows = $report->rows($filters);

        $filename = sprintf('%s-%s-to-%s.csv', $report->key(), $filters->period->from->toDateString(), $filters->period->to->toDateString());

        return response()->streamDownload(function () use ($columns, $rows): void {
            $output = new SplFileObject('php://output', 'w');

            $output->fputcsv(array_map(fn (ReportColumn $column): string => $column->label, $columns), escape: '');

            foreach ($rows as $row) {
                $output->fputcsv(array_map(fn (ReportColumn $column): string => $this->cell($row[$column->key] ?? null, $column->format), $columns), escape: '');
            }
        }, $filename, ['Content-Type' => 'text/csv; charset=utf-8']);
    }

    /**
     * Text comes from members (outlet and offer names), so a leading `=`, `+`, `-`, `@`, tab or return is escaped
     * with a quote: a spreadsheet would otherwise run it as a formula.
     */
    protected function cell(mixed $value, ReportValueFormat $format): string
    {
        if ($value === null) {
            return '';
        }

        $text = (string) $value;

        if ($format === ReportValueFormat::Text && preg_match('/^[=+\-@\t\r]/', $text) === 1) {
            return "'".$text;
        }

        return $text;
    }
}
