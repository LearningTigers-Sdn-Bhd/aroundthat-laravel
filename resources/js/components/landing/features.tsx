import { Plus, ShieldCheck } from 'lucide-react';
import type { ReactNode } from 'react';

import redemptionImage from '@/assets/landing/voucher-redemption.webp';

import { DashboardPreviewShell } from './dashboard-preview-shell';
import { MacWindowFrame } from './mac-window-frame';

export function Features() {
    return (
        <section
            id="features"
            className="mx-auto max-w-6xl scroll-mt-20 px-4 py-20 sm:px-6 lg:py-28"
        >
            <div className="max-w-2xl">
                <p className="text-xs font-semibold tracking-[0.18em] text-primary uppercase">
                    The product in practice
                </p>
                <h2 className="mt-2 font-heading text-3xl font-semibold tracking-tight sm:text-4xl">
                    Everything between a recommendation and a real visit.
                </h2>
            </div>

            <div className="mt-14 space-y-20 lg:space-y-28">
                <article className="grid items-center gap-10 lg:grid-cols-[1.08fr_0.92fr] lg:gap-20">
                    <OfferPreview />
                    <FeatureCopy
                        label="Offers and outlets"
                        title="Set the offer once. Choose exactly where it works."
                    >
                        Run discounts or freebies with an end date and voucher
                        limit. Add every outlet, then choose which ones accept
                        each offer—without reprinting anything.
                    </FeatureCopy>
                </article>

                <article className="grid items-center gap-10 lg:grid-cols-[0.92fr_1.08fr] lg:gap-20">
                    <FeatureCopy
                        label="Counter redemption"
                        title="A quick check for staff. One valid use for every code."
                        className="lg:order-1"
                    >
                        Staff scan or type the code and get a clear answer at
                        the counter. Invite the team and give each person only
                        the access their job needs.
                    </FeatureCopy>
                    <div className="relative aspect-[3/2] overflow-hidden lg:order-2">
                        <img
                            src={redemptionImage}
                            alt="A café employee checking a guest's voucher at the counter"
                            width={1536}
                            height={1024}
                            loading="lazy"
                            className="absolute inset-0 size-full object-cover"
                        />
                    </div>
                </article>

                <article className="grid items-center gap-10 lg:grid-cols-[1.08fr_0.92fr] lg:gap-20">
                    <ReportPreview />
                    <FeatureCopy
                        label="Clear reports"
                        title="See what brought people in without seeing who they are."
                    >
                        Follow views, claims, and redemptions outlet by outlet.
                        AroundThat reports totals and trends while guest details
                        stay private.
                    </FeatureCopy>
                </article>
            </div>
        </section>
    );
}

function FeatureCopy({
    label,
    title,
    className = '',
    children,
}: {
    label: string;
    title: string;
    className?: string;
    children: ReactNode;
}) {
    return (
        <div className={className}>
            <p className="text-xs font-semibold tracking-[0.16em] text-primary uppercase">
                {label}
            </p>
            <h3 className="mt-3 font-heading text-2xl font-semibold tracking-tight sm:text-3xl">
                {title}
            </h3>
            <p className="mt-4 max-w-lg leading-relaxed text-muted-foreground">
                {children}
            </p>
        </div>
    );
}

function OfferPreview() {
    return (
        <MacWindowFrame title="AroundThat — Offers">
            <DashboardPreviewShell activeItem="Offers">
                <div className="flex items-start justify-between gap-3">
                    <div>
                        <p className="font-heading text-sm font-semibold text-foreground sm:text-base">
                            Offers
                        </p>
                        <p className="mt-0.5 text-[0.58rem] text-muted-foreground">
                            Voucher offers across your outlets
                        </p>
                    </div>
                    <span className="flex shrink-0 items-center gap-1 rounded-md bg-primary px-2 py-1.5 text-[0.58rem] font-semibold text-primary-foreground">
                        <Plus aria-hidden="true" className="size-3" />
                        <span className="hidden sm:inline">New offer</span>
                    </span>
                </div>

                <div className="mt-4 overflow-hidden rounded-lg border bg-background">
                    <div className="hidden grid-cols-[minmax(0,1fr)_5rem_5rem] gap-3 border-b bg-muted px-3 py-2 text-[0.54rem] font-medium tracking-wide text-muted-foreground uppercase sm:grid">
                        <span>Offer</span>
                        <span>Outlets</span>
                        <span>Redeemed</span>
                    </div>
                    <div className="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-3 px-3 py-3 sm:grid-cols-[minmax(0,1fr)_5rem_5rem]">
                        <div className="min-w-0">
                            <div className="flex items-center gap-2">
                                <p className="truncate font-semibold text-foreground">
                                    Lunch by the water
                                </p>
                                <span className="hidden rounded-full bg-accent px-1.5 py-0.5 text-[0.5rem] font-semibold text-accent-foreground sm:inline">
                                    Active
                                </span>
                            </div>
                            <p className="mt-1 truncate text-[0.58rem] text-muted-foreground">
                                Free house drink · ends 30 Sep
                            </p>
                        </div>
                        <span className="text-muted-foreground sm:hidden">
                            173 used
                        </span>
                        <span className="hidden text-muted-foreground sm:block">
                            2 of 3
                        </span>
                        <span className="hidden font-medium text-foreground sm:block">
                            173
                        </span>
                    </div>
                </div>
            </DashboardPreviewShell>
        </MacWindowFrame>
    );
}

function ReportPreview() {
    const metrics = [
        { label: 'Views', value: '1,248', width: '100%' },
        { label: 'Claims', value: '284', width: '58%' },
        { label: 'Redemptions', value: '173', width: '38%' },
    ];

    return (
        <MacWindowFrame title="AroundThat — Reports">
            <DashboardPreviewShell activeItem="Reports">
                <div className="flex items-start justify-between gap-3">
                    <div>
                        <p className="font-heading text-sm font-semibold text-foreground sm:text-base">
                            Reports
                        </p>
                        <p className="mt-0.5 text-[0.58rem] text-muted-foreground">
                            Privacy-safe engagement funnel
                        </p>
                    </div>
                    <span className="shrink-0 rounded-md border px-2 py-1.5 text-[0.58rem] text-muted-foreground">
                        Last 30 days
                    </span>
                </div>

                <dl className="mt-4 grid grid-cols-3 gap-2">
                    {metrics.map((metric) => (
                        <div
                            key={metric.label}
                            className="rounded-lg border bg-background p-2.5"
                        >
                            <dt className="truncate text-[0.52rem] font-medium tracking-wide text-muted-foreground uppercase">
                                {metric.label}
                            </dt>
                            <dd className="mt-1 font-heading text-sm font-semibold text-foreground sm:text-lg">
                                {metric.value}
                            </dd>
                        </div>
                    ))}
                </dl>

                <div className="mt-3 rounded-lg border bg-background p-3">
                    <div className="flex items-center justify-between gap-4">
                        <p className="font-medium text-foreground">
                            Offer activity
                        </p>
                        <p className="text-[0.52rem] text-muted-foreground">
                            Views to redemptions
                        </p>
                    </div>
                    <div
                        className="mt-3 flex h-12 items-end gap-2"
                        aria-hidden="true"
                    >
                        {metrics.map((metric) => (
                            <div
                                key={metric.label}
                                className="flex-1 bg-chart-2"
                                style={{ height: metric.width }}
                            />
                        ))}
                    </div>
                </div>

                <p className="mt-3 flex items-center gap-1.5 text-[0.56rem] text-muted-foreground">
                    <ShieldCheck
                        aria-hidden="true"
                        className="size-3 text-primary"
                    />
                    Guest details are never included in reports.
                </p>
            </DashboardPreviewShell>
        </MacWindowFrame>
    );
}
