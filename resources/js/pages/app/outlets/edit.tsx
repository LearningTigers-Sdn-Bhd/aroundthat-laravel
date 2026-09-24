import { Form, Head, setLayoutProps } from '@inertiajs/react';
import ActionButton from '@/components/action-button';
import Notice from '@/components/notice';
import OutletFields, { outletFieldNames } from '@/components/outlet-fields';
import PageErrors from '@/components/page-errors';
import StatusBadge, { recordStatus } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { formatDateTime } from '@/lib/format';
import {
    archive,
    edit,
    index,
    restore,
    submit,
    update,
} from '@/routes/outlets';

type Props = {
    outlet: App.Data.OutletData;
    locationOptions: App.Data.LocationOptionsData;
    can: { update: boolean; submit: boolean; archive: boolean };
};

export default function EditOutlet({ outlet, locationOptions, can }: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Outlets', href: index() },
            { title: outlet.name, href: edit(outlet.id) },
        ],
    });

    return (
        <>
            <Head title={outlet.name} />

            <div className="flex max-w-2xl flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="space-y-1">
                        <div className="flex items-center gap-2">
                            <h1 className="text-xl font-semibold tracking-tight">
                                {outlet.name}
                            </h1>
                            <StatusBadge status={recordStatus(outlet)} />
                        </div>
                        <p className="text-sm text-muted-foreground">
                            {outlet.host_outlet
                                ? `Inside ${outlet.host_outlet.name}`
                                : 'Changes go live when you save them.'}
                        </p>
                    </div>

                    {can.archive && (
                        <ActionButton
                            form={
                                outlet.archived_at
                                    ? restore.form(outlet.id)
                                    : archive.form(outlet.id)
                            }
                            variant="outline"
                        >
                            {outlet.archived_at ? 'Restore' : 'Archive'}
                        </ActionButton>
                    )}
                </div>

                <ReviewBanner outlet={outlet} canSubmit={can.submit} />

                <PageErrors except={outletFieldNames} />

                <Form
                    {...update.form(outlet.id)}
                    options={{ preserveScroll: true }}
                >
                    {({ processing, errors }) => (
                        <fieldset
                            disabled={!can.update}
                            className="space-y-6 disabled:opacity-60"
                        >
                            <OutletFields
                                errors={errors}
                                outlet={outlet}
                                locationOptions={locationOptions}
                            />

                            {can.update && (
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    Save
                                </Button>
                            )}
                        </fieldset>
                    )}
                </Form>
            </div>
        </>
    );
}

/**
 * Why the outlet cannot be changed, or where it is in admin review and the button to send it there.
 */
function ReviewBanner({
    outlet,
    canSubmit,
}: {
    outlet: App.Data.OutletData;
    canSubmit: boolean;
}) {
    if (outlet.is_suspended) {
        return (
            <Notice title="Suspended">
                An admin suspended this outlet. Contact support to have it
                reactivated.
            </Notice>
        );
    }

    if (outlet.archived_at) {
        return (
            <p className="rounded-md border p-4 text-sm text-muted-foreground">
                Archived {formatDateTime(outlet.archived_at)}. Restore it to
                make changes.
            </p>
        );
    }

    if (outlet.onboarding_status === 'pending') {
        return (
            <p className="rounded-md border p-4 text-sm text-muted-foreground">
                Submitted for review {formatDateTime(outlet.submitted_at)}. You
                can change the details again once an admin has reviewed them.
            </p>
        );
    }

    if (outlet.onboarding_status === 'approved') {
        return null;
    }

    return (
        <>
            {outlet.onboarding_status === 'rejected' && (
                <Notice title="Rejected">{outlet.rejection_reason}</Notice>
            )}
            <div className="flex flex-wrap items-center justify-between gap-4 rounded-md border p-4">
                <p className="text-sm text-muted-foreground">
                    {canSubmit
                        ? 'When the details are complete, submit the outlet for an admin to review.'
                        : 'You can submit outlets for review once an admin has approved your business.'}
                </p>
                {canSubmit && (
                    <ActionButton form={submit.form(outlet.id)}>
                        Submit for review
                    </ActionButton>
                )}
            </div>
        </>
    );
}
