import { Form, Head, usePage } from '@inertiajs/react';
import ActionButton from '@/components/action-button';
import ComboboxField from '@/components/combobox-field';
import ConfirmDialog from '@/components/confirm-dialog';
import FormDialog from '@/components/form-dialog';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import Notice from '@/components/notice';
import PageErrors from '@/components/page-errors';
import StatusBadge, { recordStatus } from '@/components/status-badge';
import TextField from '@/components/text-field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { formatDateTime } from '@/lib/format';
import { timezoneOptions } from '@/lib/locations';
import { edit, submit, update } from '@/routes/business';
import {
    destroy as destroyLogo,
    store as storeLogo,
} from '@/routes/business/logo';
import { update as updatePublic } from '@/routes/business/public';

type Props = {
    business: App.Data.BusinessData;
    place: App.Data.BusinessPlaceData;
    locationOptions: App.Data.LocationOptionsData;
    can: { update: boolean; submit: boolean; updatePublicProfile: boolean };
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
    place,
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

                <PublicProfile
                    place={place}
                    canUpdate={can.updatePublicProfile}
                />
            </div>
        </>
    );
}

/**
 * The summary, description and logo visitors see on the business page. Name and contacts come from the details above.
 */
function PublicProfile({
    place,
    canUpdate,
}: {
    place: App.Data.BusinessPlaceData;
    canUpdate: boolean;
}) {
    return (
        <section className="space-y-6 border-t pt-6">
            <Heading
                variant="small"
                title="Public profile"
                description="Shown to visitors on every outlet's page. The name and contacts above are used as they are."
            />

            <div className="flex flex-wrap items-center gap-4">
                {place.logo ? (
                    <img
                        src={place.logo.url}
                        alt={place.logo.alt_text}
                        className="size-20 rounded-md border object-contain"
                    />
                ) : (
                    <div className="flex size-20 items-center justify-center rounded-md border border-dashed text-xs text-muted-foreground">
                        No logo
                    </div>
                )}
                {canUpdate && (
                    <div className="flex gap-2">
                        <FormDialog
                            trigger={
                                <Button variant="outline">
                                    {place.logo ? 'Replace logo' : 'Add logo'}
                                </Button>
                            }
                            title="Logo"
                            description="JPEG, PNG or WebP, up to 10 MB. A square image works best."
                            form={storeLogo.form()}
                            submitLabel="Upload"
                        >
                            {(errors) => (
                                <>
                                    <input
                                        type="hidden"
                                        name="kind"
                                        value="logo"
                                    />
                                    <div className="grid gap-2">
                                        <Label htmlFor="file">Image</Label>
                                        <Input
                                            id="file"
                                            name="file"
                                            type="file"
                                            accept="image/jpeg,image/png,image/webp"
                                            aria-invalid={!!errors.file}
                                            required
                                        />
                                        <InputError message={errors.file} />
                                    </div>
                                    <TextField
                                        name="alt_text"
                                        label="Description"
                                        defaultValue={place.logo?.alt_text}
                                        placeholder="Kopi Corner logo"
                                        maxLength={250}
                                        error={errors.alt_text}
                                        required
                                    />
                                </>
                            )}
                        </FormDialog>
                        {place.logo && (
                            <ConfirmDialog
                                trigger={
                                    <Button variant="ghost">Remove</Button>
                                }
                                title="Remove the logo?"
                                form={destroyLogo.form()}
                                confirmLabel="Remove"
                                destructive
                            />
                        )}
                    </div>
                )}
            </div>

            <Form {...updatePublic.form()} options={{ preserveScroll: true }}>
                {({ processing, errors }) => (
                    <fieldset
                        disabled={!canUpdate}
                        className="space-y-6 disabled:opacity-60"
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="summary">Summary</Label>
                            <Textarea
                                id="summary"
                                name="summary"
                                rows={2}
                                maxLength={280}
                                defaultValue={place.summary ?? ''}
                                aria-invalid={!!errors.summary}
                            />
                            <InputError message={errors.summary} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="description">
                                About the business
                            </Label>
                            <Textarea
                                id="description"
                                name="description"
                                rows={5}
                                maxLength={5000}
                                defaultValue={place.description ?? ''}
                                aria-invalid={!!errors.description}
                            />
                            <InputError message={errors.description} />
                        </div>
                        {canUpdate && (
                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner />}
                                Save public profile
                            </Button>
                        )}
                    </fieldset>
                )}
            </Form>
        </section>
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
