import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import TextField from '@/components/text-field';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { dashboard } from '@/routes/admin';
import { create, index, store } from '@/routes/admin/businesses';

const ownerMethodLabels: Record<App.Enums.OwnerMethod, string> = {
    existing: 'An existing login',
    temporary_password: 'A new login with a temporary password',
    invite: 'Email an invitation',
};

const ownerMethodHelp: Record<App.Enums.OwnerMethod, string> = {
    existing: 'The email must belong to an active, verified login.',
    temporary_password:
        'Share the password with the owner. They must change it when they first log in.',
    invite: 'The owner sets their own password when they accept. The business has no members until then.',
};

export default function CreateBusiness({
    ownerMethods,
}: {
    ownerMethods: App.Enums.OwnerMethod[];
}) {
    const [ownerMethod, setOwnerMethod] =
        useState<App.Enums.OwnerMethod>('invite');

    return (
        <>
            <Head title="New business" />

            <div className="flex max-w-2xl flex-1 flex-col gap-6 p-4">
                <Heading
                    title="New business"
                    description="Create the business and give it its first owner."
                />

                <Form {...store.form()} className="space-y-10">
                    {({ processing, errors }) => (
                        <>
                            <section className="space-y-4">
                                <Heading variant="small" title="Business" />

                                <TextField
                                    name="business[name]"
                                    label="Name"
                                    error={errors['business.name']}
                                    required
                                />
                                <TextField
                                    name="business[contact_email]"
                                    label="Contact email"
                                    type="email"
                                    error={errors['business.contact_email']}
                                    required
                                />
                                <TextField
                                    name="business[contact_phone]"
                                    label="Contact phone"
                                    error={errors['business.contact_phone']}
                                />
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <TextField
                                        name="business[registered_name]"
                                        label="Registered name"
                                        error={
                                            errors['business.registered_name']
                                        }
                                    />
                                    <TextField
                                        name="business[registration_number]"
                                        label="Registration number"
                                        error={
                                            errors[
                                                'business.registration_number'
                                            ]
                                        }
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="business[address]">
                                        Address
                                    </Label>
                                    <Textarea
                                        id="business[address]"
                                        name="business[address]"
                                        rows={3}
                                    />
                                    <InputError
                                        message={errors['business.address']}
                                    />
                                </div>
                                <TextField
                                    name="business[timezone]"
                                    label="Timezone"
                                    defaultValue="Asia/Kuala_Lumpur"
                                    error={errors['business.timezone']}
                                    required
                                />
                            </section>

                            <section className="space-y-4">
                                <Heading variant="small" title="First owner" />

                                <div className="grid gap-2">
                                    <Label htmlFor="owner_method">
                                        How the owner gets access
                                    </Label>
                                    <Select
                                        name="owner_method"
                                        value={ownerMethod}
                                        onValueChange={(value) =>
                                            setOwnerMethod(
                                                value as App.Enums.OwnerMethod,
                                            )
                                        }
                                    >
                                        <SelectTrigger id="owner_method">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {ownerMethods.map((method) => (
                                                <SelectItem
                                                    key={method}
                                                    value={method}
                                                >
                                                    {ownerMethodLabels[method]}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <p className="text-sm text-muted-foreground">
                                        {ownerMethodHelp[ownerMethod]}
                                    </p>
                                    <InputError message={errors.owner_method} />
                                </div>

                                <TextField
                                    name="owner_email"
                                    label="Owner email"
                                    type="email"
                                    error={errors.owner_email}
                                    required
                                />

                                {ownerMethod === 'temporary_password' && (
                                    <>
                                        <TextField
                                            name="owner_name"
                                            label="Owner name"
                                            error={errors.owner_name}
                                            required
                                        />
                                        <TextField
                                            name="owner_password"
                                            label="Temporary password"
                                            type="text"
                                            autoComplete="off"
                                            error={errors.owner_password}
                                            required
                                        />
                                    </>
                                )}
                            </section>

                            <section className="space-y-2">
                                <div className="flex items-center gap-3">
                                    <Checkbox
                                        id="approve_immediately"
                                        name="approve_immediately"
                                        value="1"
                                    />
                                    <Label htmlFor="approve_immediately">
                                        Approve the business now
                                    </Label>
                                </div>
                                <p className="text-sm text-muted-foreground">
                                    Leave this off to let the owner complete the
                                    details and submit them for review.
                                </p>
                                <InputError message={errors.business} />
                            </section>

                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner />}
                                Create business
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

CreateBusiness.layout = {
    breadcrumbs: [
        { title: 'Admin', href: dashboard() },
        { title: 'Businesses', href: index() },
        { title: 'New business', href: create() },
    ],
};
