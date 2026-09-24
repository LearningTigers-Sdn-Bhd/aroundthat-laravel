import { Form, Head, usePage } from '@inertiajs/react';
import ActionButton from '@/components/action-button';
import ComboboxField from '@/components/combobox-field';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import Notice from '@/components/notice';
import PageErrors from '@/components/page-errors';
import StatusBadge, { recordStatus } from '@/components/status-badge';
import TextField from '@/components/text-field';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { formatDateTime } from '@/lib/format';
import { timezoneOptions } from '@/lib/locations';
import { edit, submit, update } from '@/routes/business';

type Props = {
    business: App.Data.BusinessData;
    locationOptions: App.Data.LocationOptionsData;
    can: { update: boolean; submit: boolean };
};

const detailFields = [
    'name',
    'registered_name',
    'registration_number',
    'contact_email',
    'contact_phone',
    'address',
    'timezone',
];

export default function EditBusiness({
    business,
    locationOptions,
    can,
}: Props) {
    // Read errors from the page, not the form, so "Submit for review" can point at the fields it needs.
    const errors = usePage().props.errors as Record<string, string>;

    return (
        <>
            <Head title="Business details" />

            <div className="flex max-w-2xl flex-1 flex-col gap-6 p-4">
                <div className="flex items-center gap-2">
                    <Heading
                        title="Business details"
                        description="Changes go live when you save them."
                    />
                    <StatusBadge
                        status={recordStatus(business)}
                        className="mb-8"
                    />
                </div>

                <ReviewBanner business={business} canSubmit={can.submit} />

                <PageErrors except={detailFields} />

                <Form
                    {...update.form()}
                    options={{ preserveScroll: true }}
                    className="space-y-6"
                >
                    {({ processing }) => (
                        <fieldset
                            disabled={!can.update}
                            className="space-y-6 disabled:opacity-60"
                        >
                            <TextField
                                name="name"
                                label="Name"
                                defaultValue={business.name}
                                error={errors.name}
                                required
                            />
                            <div className="grid gap-4 sm:grid-cols-2">
                                <TextField
                                    name="registered_name"
                                    label="Registered name"
                                    defaultValue={
                                        business.registered_name ?? ''
                                    }
                                    error={errors.registered_name}
                                />
                                <TextField
                                    name="registration_number"
                                    label="Registration number"
                                    defaultValue={
                                        business.registration_number ?? ''
                                    }
                                    error={errors.registration_number}
                                />
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <TextField
                                    name="contact_email"
                                    label="Contact email"
                                    type="email"
                                    defaultValue={business.contact_email}
                                    error={errors.contact_email}
                                    required
                                />
                                <TextField
                                    name="contact_phone"
                                    label="Contact phone"
                                    defaultValue={business.contact_phone ?? ''}
                                    error={errors.contact_phone}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="address">Address</Label>
                                <Textarea
                                    id="address"
                                    name="address"
                                    rows={3}
                                    defaultValue={business.address ?? ''}
                                />
                                <InputError message={errors.address} />
                            </div>
                            <ComboboxField
                                name="timezone"
                                label="Timezone"
                                options={timezoneOptions(
                                    locationOptions.timezones,
                                )}
                                defaultValue={business.timezone}
                                error={errors.timezone}
                                required
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
 * Where the business is in admin review, and the button to send it there.
 */
function ReviewBanner({
    business,
    canSubmit,
}: {
    business: App.Data.BusinessData;
    canSubmit: boolean;
}) {
    if (business.is_suspended) {
        return (
            <Notice title="Suspended">
                An admin suspended this business. Contact support to have it
                reactivated.
            </Notice>
        );
    }

    if (business.onboarding_status === 'pending') {
        return (
            <p className="rounded-md border p-4 text-sm text-muted-foreground">
                Submitted for review {formatDateTime(business.submitted_at)}.
                You can change the details again once an admin has reviewed
                them.
            </p>
        );
    }

    return (
        <>
            {business.onboarding_status === 'rejected' && (
                <Notice title="Rejected">{business.rejection_reason}</Notice>
            )}
            {canSubmit && (
                <div className="flex flex-wrap items-center justify-between gap-4 rounded-md border p-4">
                    <p className="text-sm text-muted-foreground">
                        {business.onboarding_status === 'rejected'
                            ? 'Fix the details, then submit them again.'
                            : 'Complete the details, then submit them for an admin to review.'}
                    </p>
                    <ActionButton form={submit.form()}>
                        Submit for review
                    </ActionButton>
                </div>
            )}
        </>
    );
}

EditBusiness.layout = {
    breadcrumbs: [{ title: 'Business', href: edit() }],
};
