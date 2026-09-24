import { Form, Head, setLayoutProps } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import TextField from '@/components/text-field';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes/admin';
import { index, show } from '@/routes/admin/businesses';
import { create, store } from '@/routes/admin/businesses/outlets';

export default function CreateOutlet({
    business,
}: {
    business: App.Data.Admin.BusinessData;
}) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Admin', href: dashboard() },
            { title: 'Businesses', href: index() },
            { title: business.name, href: show(business.id) },
            { title: 'New outlet', href: create(business.id) },
        ],
    });

    const canApprove = business.onboarding_status === 'approved';

    return (
        <>
            <Head title={`New outlet for ${business.name}`} />

            <div className="flex max-w-2xl flex-1 flex-col gap-6 p-4">
                <Heading
                    title="New outlet"
                    description={`A place where ${business.name} trades.`}
                />

                <Form {...store.form(business.id)} className="space-y-6">
                    {({ processing, errors }) => (
                        <>
                            <TextField
                                name="name"
                                label="Name"
                                error={errors.name}
                                required
                            />
                            <div className="grid gap-4 sm:grid-cols-2">
                                <TextField
                                    name="contact_email"
                                    label="Contact email"
                                    type="email"
                                    error={errors.contact_email}
                                />
                                <TextField
                                    name="contact_phone"
                                    label="Contact phone"
                                    error={errors.contact_phone}
                                />
                            </div>
                            <TextField
                                name="address_line_1"
                                label="Address line 1"
                                error={errors.address_line_1}
                                required
                            />
                            <TextField
                                name="address_line_2"
                                label="Address line 2"
                                error={errors.address_line_2}
                            />
                            <div className="grid gap-4 sm:grid-cols-3">
                                <TextField
                                    name="city"
                                    label="City"
                                    error={errors.city}
                                    required
                                />
                                <TextField
                                    name="state"
                                    label="State"
                                    error={errors.state}
                                    required
                                />
                                <TextField
                                    name="postcode"
                                    label="Postcode"
                                    error={errors.postcode}
                                    required
                                />
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <TextField
                                    name="country_code"
                                    label="Country code"
                                    defaultValue="MY"
                                    maxLength={2}
                                    error={errors.country_code}
                                    required
                                />
                                <TextField
                                    name="timezone"
                                    label="Timezone"
                                    defaultValue={business.timezone}
                                    error={errors.timezone}
                                    required
                                />
                            </div>

                            <div className="space-y-2">
                                <div className="flex items-center gap-3">
                                    <Checkbox
                                        id="approve_immediately"
                                        name="approve_immediately"
                                        value="1"
                                        disabled={!canApprove}
                                    />
                                    <Label htmlFor="approve_immediately">
                                        Approve the outlet now
                                    </Label>
                                </div>
                                <p className="text-sm text-muted-foreground">
                                    {canApprove
                                        ? 'Leave this off to let the owner complete the details and submit them for review.'
                                        : 'The business must be approved before its outlets can be.'}
                                </p>
                                <InputError
                                    message={
                                        errors.approve_immediately ??
                                        errors.business
                                    }
                                />
                            </div>

                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner />}
                                Create outlet
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}
