<?php

namespace App\Support\QueryFilters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\Filters\Filter;

/**
 * `filter[search]=…`: a case-insensitive match against any of the given columns.
 *
 * @implements Filter<Model>
 */
class SearchFilter implements Filter
{
    /**
     * @param  list<string>  $columns
     */
    public function __construct(protected array $columns) {}

    /**
     * The search filter, with the whole term kept together even when it contains commas.
     *
     * @param  list<string>  $columns
     */
    public static function on(array $columns): AllowedFilter
    {
        return AllowedFilter::custom('search', new self($columns))->delimiter('');
    }

    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        $term = '%'.trim((string) $value).'%';

        $query->where(function (Builder $query) use ($term): void {
            foreach ($this->columns as $column) {
                $query->orWhereLike($column, $term);
            }
        });
    }
}
