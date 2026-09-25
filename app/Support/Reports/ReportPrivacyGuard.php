<?php

namespace App\Support\Reports;

/**
 * Hides report rows about another business's outlet that hold too few redemptions to share, so an owner cannot
 * read a single guest's visit at a sponsored outlet. The business's own outlets are always shown exactly.
 *
 * One hidden row is never enough: the total is exact, so the owner could subtract the rows they see and get it
 * back. A second protected row is hidden with it. When no second one exists, the total must be hidden instead.
 */
final class ReportPrivacyGuard
{
    public const int MINIMUM_GROUP_SIZE = 5;

    /**
     * Each row carries `key`, `privacy_count` (the redemptions in it) and `protected` (whether it is another
     * business's outlet). Those two are removed; `hidden` is added, and a hidden row's measures become null.
     *
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $measures  the row keys that hold numbers
     * @return array{rows: list<array<string, mixed>>, summaryHidden: bool}
     */
    public function protect(array $rows, array $measures): array
    {
        $small = array_values(array_map(
            fn (array $row): string => $row['key'],
            array_filter($rows, fn (array $row): bool => $row['protected'] && $row['privacy_count'] > 0 && $row['privacy_count'] < self::MINIMUM_GROUP_SIZE),
        ));

        $hidden = $small;

        if (count($small) === 1) {
            $others = array_filter($rows, fn (array $row): bool => $row['protected'] && ! in_array($row['key'], $small, true));
            usort($others, fn (array $a, array $b): int => $a['privacy_count'] <=> $b['privacy_count']);

            if ($others !== []) {
                $hidden[] = $others[0]['key'];
            }
        }

        $guarded = array_map(function (array $row) use ($hidden, $measures): array {
            $isHidden = in_array($row['key'], $hidden, true);
            unset($row['privacy_count'], $row['protected']);

            return $isHidden
                ? [...$row, ...array_fill_keys($measures, null), 'hidden' => true]
                : [...$row, 'hidden' => false];
        }, $rows);

        return ['rows' => $guarded, 'summaryHidden' => count($hidden) === 1];
    }
}
