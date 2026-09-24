import { Deferred, Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import ActionButton from '@/components/action-button';
import ActivityTimeline, {
    ActivitySkeleton,
} from '@/components/activity-timeline';
import Detail from '@/components/detail';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import Notice from '@/components/notice';
import PageErrors from '@/components/page-errors';
import ReasonDialog from '@/components/reason-dialog';
import StatusBadge, { recordStatus } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { formatDateTime } from '@/lib/format';
import { dashboard } from '@/routes/admin';
import { index, show as showBusiness } from '@/routes/admin/businesses';
import {
    approve,
    archive,
    reactivate,
    reject,
    restore,
    show,
    suspend,
} from '@/routes/admin/outlets';
import {
    destroy as clearHost,
    update as setHost,
} from '@/routes/admin/outlets/host';

type Props = {
    outlet: App.Data.Admin.OutletData;
    hostCandidates: App.Data.Admin.HostOutletOptionData[];
    hostedOutlets: App.Data.Admin.OutletData[];
    activities?: App.Data.Admin.ActivityData[];
};

export default function ShowOutlet({
    outlet,
    hostCandidates,
    hostedOutlets,
    activities,
}: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Admin', href: dashboard() },
            { title: 'Businesses', href: index() },
            {
                title: outlet.business_name,
                href: showBusiness(outlet.business_id),
            },
            { title: outlet.name, href: show(outlet.id) },
        ],
    });

    return (
        <>
            <Head title={outlet.name} />

            <div className="flex flex-1 flex-col gap-8 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="space-y-1">
                        <div className="flex items-center gap-2">
                            <h1 className="text-xl font-semibold tracking-tight">
                                {outlet.name}
                            </h1>
                            <StatusBadge status={recordStatus(outlet)} />
                        </div>
                        <p className="text-sm text-muted-foreground">
                            Outlet of{' '}
                            <Link
                                href={showBusiness(outlet.business_id)}
                                className="hover:underline"
                            >
                                {outlet.business_name}
                            </Link>
                            {outlet.is_operational
                                ? ' · Trading'
                                : ' · Not trading'}
                        </p>
                    </div>

                    <OutletActions outlet={outlet} />
                </div>

                <PageErrors except={['host_outlet_id']} />

                {outlet.suspended_at && (
                    <Notice title="Suspended">
                        {formatDateTime(outlet.suspended_at)} by{' '}
                        {outlet.suspended_by_name ?? 'an admin'}:{' '}
                        {outlet.suspension_reason}
                    </Notice>
                )}
                {outlet.onboarding_status === 'rejected' && (
                    <Notice title="Rejected">{outlet.rejection_reason}</Notice>
                )}

                <section>
                    <Heading variant="small" title="Details" />
                    <dl className="mt-3 grid gap-x-8 gap-y-3 text-sm sm:grid-cols-2">
                        <Detail label="Address">
                            {[
                                outlet.address_line_1,
                                outlet.address_line_2,
                                `${outlet.postcode} ${outlet.city}`,
                                `${outlet.state}, ${outlet.country_code}`,
                            ]
                                .filter(Boolean)
                                .join('\n')}
                        </Detail>
                        <Detail label="Timezone">{outlet.timezone}</Detail>
                        <Detail label="Contact email">
                            {outlet.contact_email}
                        </Detail>
                        <Detail label="Contact phone">
                            {outlet.contact_phone}
                        </Detail>
                        <Detail label="Submitted">
                            {formatDateTime(outlet.submitted_at)}
                        </Detail>
                        <Detail label="Approved">
                            {outlet.approved_at
                                ? `${formatDateTime(outlet.approved_at)} by ${outlet.approved_by_name ?? 'an admin'}`
                                : null}
                        </Detail>
                        <Detail label="Archived">
                            {outlet.archived_at
                                ? formatDateTime(outlet.archived_at)
                                : null}
                        </Detail>
                    </dl>
                </section>

                <HostOutlet outlet={outlet} candidates={hostCandidates} />

                {hostedOutlets.length > 0 && (
                    <section className="space-y-3">
                        <Heading
                            variant="small"
                            title="Outlets inside this one"
                        />
                        <ul className="divide-y rounded-md border text-sm">
                            {hostedOutlets.map((hosted) => (
                                <li
                                    key={hosted.id}
                                    className="flex items-center justify-between gap-4 px-3 py-2"
                                >
                                    <div className="flex flex-col">
                                        <Link
                                            href={show(hosted.id)}
                                            className="font-medium hover:underline"
                                        >
                                            {hosted.name}
                                        </Link>
                                        <span className="text-muted-foreground">
                                            {hosted.business_name}
                                        </span>
                                    </div>
                                    <StatusBadge
                                        status={recordStatus(hosted)}
                                    />
                                </li>
                            ))}
                        </ul>
                    </section>
                )}

                <section className="space-y-3">
                    <Heading
                        variant="small"
                        title="Activity"
                        description="The latest 100 changes to this outlet."
                    />
                    <Deferred data="activities" fallback={<ActivitySkeleton />}>
                        <ActivityTimeline activities={activities ?? []} />
                    </Deferred>
                </section>
            </div>
        </>
    );
}

function OutletActions({ outlet }: { outlet: App.Data.Admin.OutletData }) {
    return (
        <div className="flex flex-wrap gap-2">
            {outlet.onboarding_status === 'pending' && (
                <>
                    <ActionButton form={approve.form(outlet.id)}>
                        Approve
                    </ActionButton>
                    <ReasonDialog
                        trigger={<Button variant="outline">Reject</Button>}
                        title={`Reject ${outlet.name}?`}
                        description="The owner sees this reason, fixes the details and submits again."
                        form={reject.form(outlet.id)}
                        submitLabel="Reject"
                        destructive
                    />
                </>
            )}

            {outlet.archived_at ? (
                <ActionButton form={restore.form(outlet.id)} variant="outline">
                    Restore
                </ActionButton>
            ) : (
                <>
                    {outlet.suspended_at ? (
                        <ActionButton
                            form={reactivate.form(outlet.id)}
                            variant="outline"
                        >
                            Reactivate
                        </ActionButton>
                    ) : (
                        <ReasonDialog
                            trigger={
                                <Button variant="destructive">Suspend</Button>
                            }
                            title={`Suspend ${outlet.name}?`}
                            description="The outlet stops trading until it is reactivated."
                            form={suspend.form(outlet.id)}
                            submitLabel="Suspend"
                            destructive
                        />
                    )}
                    <ActionButton
                        form={archive.form(outlet.id)}
                        variant="ghost"
                    >
                        Archive
                    </ActionButton>
                </>
            )}
        </div>
    );
}

function HostOutlet({
    outlet,
    candidates,
}: {
    outlet: App.Data.Admin.OutletData;
    candidates: App.Data.Admin.HostOutletOptionData[];
}) {
    return (
        <section className="space-y-3">
            <Heading
                variant="small"
                title="Inside another outlet"
                description="For example a restaurant inside a mall. This gives the host no access to this outlet."
            />

            {outlet.archived_at ? (
                <p className="text-sm text-muted-foreground">
                    {outlet.host_outlet?.name ?? 'No host.'} Archived outlets
                    cannot change their host.
                </p>
            ) : (
                <div className="flex flex-wrap items-start gap-2">
                    <Form
                        {...setHost.form(outlet.id)}
                        options={{ preserveScroll: true }}
                        className="flex flex-wrap items-start gap-2"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-1">
                                    <Select
                                        key={outlet.host_outlet?.id ?? 'none'}
                                        name="host_outlet_id"
                                        defaultValue={outlet.host_outlet?.id}
                                    >
                                        <SelectTrigger
                                            className="w-72"
                                            aria-label="Host outlet"
                                        >
                                            <SelectValue placeholder="Choose the host outlet" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {candidates.map((candidate) => (
                                                <SelectItem
                                                    key={candidate.id}
                                                    value={candidate.id}
                                                >
                                                    {candidate.name} (
                                                    {candidate.business_name})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={errors.host_outlet_id}
                                    />
                                </div>
                                <Button
                                    type="submit"
                                    variant="outline"
                                    disabled={processing}
                                >
                                    {processing && <Spinner />}
                                    Save host
                                </Button>
                            </>
                        )}
                    </Form>

                    {outlet.host_outlet && (
                        <ActionButton
                            form={clearHost.form(outlet.id)}
                            variant="ghost"
                        >
                            Clear host
                        </ActionButton>
                    )}
                </div>
            )}
        </section>
    );
}
