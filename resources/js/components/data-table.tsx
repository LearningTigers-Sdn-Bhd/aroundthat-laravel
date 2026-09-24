import { router, usePage } from '@inertiajs/react';
import {
    ArrowDown,
    ArrowUp,
    ChevronsUpDown,
    Inbox,
    Search,
    SearchX,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { useEffect, useRef, useState } from 'react';
import ButtonLink from '@/components/button-link';
import { Button } from '@/components/ui/button';
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { cn } from '@/lib/utils';

export type DataTableColumn<T> = {
    key: string;
    header: string;
    cell: (row: T) => ReactNode;
    /** The query-builder sort name. Leave it out for columns that cannot be sorted. */
    sort?: string;
    className?: string;
};

export type DataTableFilter = {
    /** The query-builder filter name, sent as `filter[name]`. */
    name: string;
    label: string;
    options: { value: string; label: string }[];
};

type Props<T> = {
    /**
     * A page of rows. On the server: `XData::collect($query->paginate()->withQueryString(), PaginatedDataCollection::class)`.
     */
    rows: Illuminate.LengthAwarePaginator<number, T>;
    columns: DataTableColumn<T>[];
    rowKey: (row: T) => string;
    /** Adds a search box, sent as `filter[search]`. */
    searchPlaceholder?: string;
    filters?: DataTableFilter[];
    /** Shown when there are no rows at all, before any search or filter. */
    emptyTitle?: string;
    emptyIcon?: LucideIcon;
};

type Query = {
    filter: Record<string, string>;
    sort: string | null;
};

type SortDirection = 'asc' | 'desc' | null;

const ariaSort = {
    asc: 'ascending',
    desc: 'descending',
    none: 'none',
} as const;

const ALL = '__all__';

function readQuery(url: string): Query {
    const params = new URL(url, 'http://localhost').searchParams;
    const filter: Record<string, string> = {};

    params.forEach((value, key) => {
        const match = key.match(/^filter\[(.+)]$/);

        if (match && value !== '') {
            filter[match[1]] = value;
        }
    });

    return { filter, sort: params.get('sort') };
}

/**
 * A server-side table: search, filters and sorting become spatie/laravel-query-builder query
 * parameters, and every change reloads the current page from the first page of results.
 */
export default function DataTable<T>({
    rows,
    columns,
    rowKey,
    searchPlaceholder,
    filters = [],
    emptyTitle = 'Nothing here yet',
    emptyIcon: EmptyIcon = Inbox,
}: Props<T>) {
    const page = usePage();
    const query = readQuery(page.url);
    const [search, setSearch] = useState(query.filter.search ?? '');
    const lastSearch = useRef(query.filter.search ?? '');

    const visit = (next: Query) => {
        const filter = Object.fromEntries(
            Object.entries(next.filter).filter(([, value]) => value !== ''),
        );

        router.get(
            new URL(page.url, 'http://localhost').pathname,
            { filter, ...(next.sort ? { sort: next.sort } : {}) },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    useEffect(() => {
        if (search === lastSearch.current) {
            return;
        }

        const timeout = setTimeout(() => {
            lastSearch.current = search;
            visit({ ...query, filter: { ...query.filter, search } });
        }, 300);

        return () => clearTimeout(timeout);
        // Only a new search term should trigger a visit.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const setFilter = (name: string, value: string) =>
        visit({
            ...query,
            filter: { ...query.filter, [name]: value === ALL ? '' : value },
        });

    const directionOf = (sort: string): SortDirection =>
        query.sort === sort ? 'asc' : query.sort === `-${sort}` ? 'desc' : null;

    const isFiltered = Object.keys(query.filter).length > 0;

    const clearFilters = () => {
        lastSearch.current = '';
        setSearch('');
        visit({ filter: {}, sort: query.sort });
    };

    const toggleSort = (sort: string) => {
        const next =
            query.sort === sort
                ? `-${sort}`
                : query.sort === `-${sort}`
                  ? null
                  : sort;

        visit({ ...query, sort: next });
    };

    return (
        <div className="space-y-4">
            {(searchPlaceholder || filters.length > 0) && (
                <div className="flex flex-wrap items-center gap-2">
                    {searchPlaceholder && (
                        <div className="relative w-full sm:w-72">
                            <Search className="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                type="search"
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                                placeholder={searchPlaceholder}
                                aria-label={searchPlaceholder}
                                className="pl-8"
                            />
                        </div>
                    )}

                    {filters.map((filter) => {
                        const items = [
                            {
                                value: ALL,
                                label: `All ${filter.label.toLowerCase()}`,
                            },
                            ...filter.options,
                        ];

                        return (
                            <Select
                                key={filter.name}
                                items={items}
                                value={query.filter[filter.name] || ALL}
                                onValueChange={(value) =>
                                    setFilter(filter.name, value ?? ALL)
                                }
                            >
                                <SelectTrigger
                                    className="w-full sm:w-44"
                                    aria-label={filter.label}
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {items.map((item) => (
                                        <SelectItem
                                            key={item.value}
                                            value={item.value}
                                        >
                                            {item.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        );
                    })}
                </div>
            )}

            <div className="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            {columns.map((column) => (
                                <TableHead
                                    key={column.key}
                                    className={column.className}
                                    aria-sort={
                                        column.sort
                                            ? ariaSort[
                                                  directionOf(column.sort) ??
                                                      'none'
                                              ]
                                            : undefined
                                    }
                                >
                                    {column.sort ? (
                                        <SortButton
                                            label={column.header}
                                            direction={directionOf(column.sort)}
                                            onClick={() =>
                                                toggleSort(column.sort!)
                                            }
                                        />
                                    ) : (
                                        column.header
                                    )}
                                </TableHead>
                            ))}
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {rows.data.length === 0 ? (
                            <TableRow>
                                <TableCell
                                    colSpan={columns.length}
                                    className="whitespace-normal"
                                >
                                    {isFiltered ? (
                                        <Empty className="p-8">
                                            <EmptyHeader>
                                                <EmptyMedia variant="icon">
                                                    <SearchX />
                                                </EmptyMedia>
                                                <EmptyTitle>
                                                    No matches
                                                </EmptyTitle>
                                                <EmptyDescription>
                                                    Try a different search or
                                                    clear the filters.
                                                </EmptyDescription>
                                            </EmptyHeader>
                                            <EmptyContent>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={clearFilters}
                                                >
                                                    Clear filters
                                                </Button>
                                            </EmptyContent>
                                        </Empty>
                                    ) : (
                                        <Empty className="p-8">
                                            <EmptyHeader>
                                                <EmptyMedia variant="icon">
                                                    <EmptyIcon />
                                                </EmptyMedia>
                                                <EmptyTitle>
                                                    {emptyTitle}
                                                </EmptyTitle>
                                            </EmptyHeader>
                                        </Empty>
                                    )}
                                </TableCell>
                            </TableRow>
                        ) : (
                            rows.data.map((row) => (
                                <TableRow key={rowKey(row)}>
                                    {columns.map((column) => (
                                        <TableCell
                                            key={column.key}
                                            className={column.className}
                                        >
                                            {column.cell(row)}
                                        </TableCell>
                                    ))}
                                </TableRow>
                            ))
                        )}
                    </TableBody>
                </Table>
            </div>

            <Pagination meta={rows.meta} />
        </div>
    );
}

function SortButton({
    label,
    direction,
    onClick,
}: {
    label: string;
    direction: SortDirection;
    onClick: () => void;
}) {
    const Icon =
        direction === 'asc'
            ? ArrowUp
            : direction === 'desc'
              ? ArrowDown
              : ChevronsUpDown;

    return (
        <button
            type="button"
            onClick={onClick}
            className="-ml-2 inline-flex items-center gap-1 rounded-md px-2 py-1 hover:bg-accent"
        >
            {label}
            <Icon
                className={cn(
                    'size-3.5',
                    direction === null && 'text-muted-foreground',
                )}
            />
        </button>
    );
}

function Pagination({
    meta,
}: {
    meta: Illuminate.LengthAwarePaginator<number, unknown>['meta'];
}) {
    if (meta.total === 0) {
        return null;
    }

    return (
        <div className="flex items-center justify-between gap-4 text-sm text-muted-foreground">
            <p>
                Showing {meta.from}–{meta.to} of {meta.total}
            </p>

            {meta.last_page > 1 && (
                <div className="flex items-center gap-2">
                    <PageLink url={meta.prev_page_url} label="Previous" />
                    <span>
                        Page {meta.current_page} of {meta.last_page}
                    </span>
                    <PageLink url={meta.next_page_url} label="Next" />
                </div>
            )}
        </div>
    );
}

function PageLink({ url, label }: { url: string | null; label: string }) {
    if (!url) {
        return (
            <Button variant="outline" size="sm" disabled>
                {label}
            </Button>
        );
    }

    return (
        <ButtonLink variant="outline" size="sm" href={url} preserveScroll preserveState>
                {label}
            </ButtonLink>
    );
}
