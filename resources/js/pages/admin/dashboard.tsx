import { Head, Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import ButtonLink from '@/components/button-link';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { formatRelative } from '@/lib/format';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes/admin';
import {
    index as businessesIndex,
    show as showBusiness,
} from '@/routes/admin/businesses';
import { index as changesIndex } from '@/routes/admin/changes';
import { show as showOutlet } from '@/routes/admin/outlets';
import { index as tagsIndex } from '@/routes/admin/tags';

type Props = {
    pendingBusinessCount: number;
    pendingTagCount: number;
    unreviewedChangeCount: number;
    pendingOutletCount: number;
    pendingBusinesses: App.Data.Admin.BusinessData[];
    pendingTags: App.Data.Admin.TagData[];
    unreviewedChanges: App.Data.Admin.ChangeData[];
    pendingOutlets: App.Data.Admin.OutletData[];
};

export default function AdminDashboard({
    pendingBusinessCount,
    pendingTagCount,
    unreviewedChangeCount,
    pendingOutletCount,
    pendingBusinesses,
    pendingTags,
    unreviewedChanges,
    pendingOutlets,
}: Props) {
    const businessesHref = businessesIndex({
        query: { filter: { onboarding_status: 'pending' } },
    });
    const tagsHref = tagsIndex({ query: { filter: { status: 'pending' } } });

    return (
        <>
            <Head title="Admin" />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Waiting for review"
                    description="Businesses, outlets and tags their owners have submitted, and what owners changed on their public pages."
                />

                <div className="grid flex-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 lg:items-start">
                    <Column
                        title="Businesses"
                        count={pendingBusinessCount}
                        shown={pendingBusinesses.length}
                        href={businessesHref}
                        reviewLabel="Review businesses"
                        emptyLabel="No businesses are waiting for review."
                    >
                        {pendingBusinesses.map((business) => (
                            <ColumnItem
                                key={business.id}
                                href={showBusiness(business.id)}
                                title={business.name}
                                detail={business.contact_email}
                                time={business.submitted_at}
                            />
                        ))}
                    </Column>

                    <Column
                        title="Outlets"
                        count={pendingOutletCount}
                        shown={pendingOutlets.length}
                        emptyLabel="No outlets are waiting for review."
                    >
                        {pendingOutlets.map((outlet) => (
                            <ColumnItem
                                key={outlet.id}
                                href={showOutlet(outlet.id)}
                                title={outlet.name}
                                detail={outlet.business_name}
                                time={outlet.submitted_at}
                            />
                        ))}
                    </Column>

                    <Column
                        title="Tags"
                        count={pendingTagCount}
                        shown={pendingTags.length}
                        href={tagsHref}
                        reviewLabel="Review tags"
                        emptyLabel="No tags are waiting for review."
                    >
                        {pendingTags.map((tag) => (
                            <ColumnItem
                                key={tag.id}
                                href={tagsHref}
                                title={tag.name}
                                detail={
                                    tag.created_by_business_name ??
                                    'Created by an admin'
                                }
                                time={tag.created_at}
                            />
                        ))}
                    </Column>

                    <Column
                        title="Changes"
                        count={unreviewedChangeCount}
                        shown={unreviewedChanges.length}
                        href={changesIndex()}
                        reviewLabel="Review changes"
                        emptyLabel="No changes are waiting for review."
                    >
                        {unreviewedChanges.map(({ activity, subject }) => (
                            <ColumnItem
                                key={activity.id}
                                href={
                                    subject === null
                                        ? changesIndex()
                                        : subject.type === 'outlet'
                                          ? showOutlet(subject.id)
                                          : showBusiness(subject.id)
                                }
                                title={subject?.name ?? 'Deleted page'}
                                detail={[
                                    activity.causer_name ?? 'Someone',
                                    activity.changes.length === 1
                                        ? 'changed 1 field'
                                        : `changed ${activity.changes.length} fields`,
                                ].join(' ')}
                                time={activity.created_at}
                            />
                        ))}
                    </Column>
                </div>
            </div>
        </>
    );
}

type ColumnProps = (
    | { href: ReturnType<typeof changesIndex>; reviewLabel: string }
    | { href?: never; reviewLabel?: never }
) & {
    title: string;
    count: number;
    shown: number;
    emptyLabel: string;
    children: ReactNode;
};

/**
 * One kind of submission. On large screens it is a board column listing the
 * oldest items; on smaller screens it is a compact summary with a link. Kinds
 * without a list page to link to keep their items on every screen.
 */
function Column({
    title,
    count,
    shown,
    href,
    reviewLabel,
    emptyLabel,
    children,
}: ColumnProps) {
    return (
        <section className="flex flex-col gap-3 rounded-xl border bg-card p-4 lg:h-[76dvh] lg:bg-muted/40 lg:p-3">
            <header className="flex items-center justify-between gap-2 lg:px-1">
                <h2 className="text-sm font-medium">{title}</h2>
                <Badge variant={count === 0 ? 'outline' : 'secondary'}>
                    {count}
                </Badge>
            </header>

            {count === 0 ? (
                <p className="text-sm text-muted-foreground lg:flex lg:flex-1 lg:items-center lg:justify-center lg:rounded-lg lg:border lg:border-dashed lg:p-4 lg:text-center">
                    {emptyLabel}
                </p>
            ) : (
                <ul
                    className={cn(
                        '-mx-1 flex-col gap-2 overflow-y-auto px-1 lg:min-h-0 lg:flex-1',
                        href === undefined ? 'flex' : 'hidden lg:flex',
                    )}
                >
                    {children}
                </ul>
            )}

            {href !== undefined && count > 0 && (
                <ButtonLink variant="outline" href={href}>
                    {reviewLabel}
                    {count > shown && (
                        <span className="hidden text-muted-foreground lg:inline">
                            +{count - shown} more
                        </span>
                    )}
                </ButtonLink>
            )}
        </section>
    );
}

type ColumnItemProps = {
    href: ReturnType<typeof changesIndex>;
    title: string;
    detail: string;
    time: string | null;
};

function ColumnItem({ href, title, detail, time }: ColumnItemProps) {
    return (
        <li>
            <Link
                href={href}
                className="flex flex-col gap-1 rounded-lg border bg-card p-3 text-sm shadow-xs transition-colors hover:bg-accent"
            >
                <span className="truncate font-medium">{title}</span>
                <span className="truncate text-muted-foreground">{detail}</span>
                {time && (
                    <span className="text-xs text-muted-foreground">
                        {formatRelative(time)}
                    </span>
                )}
            </Link>
        </li>
    );
}

AdminDashboard.layout = {
    breadcrumbs: [{ title: 'Admin', href: dashboard() }],
};
