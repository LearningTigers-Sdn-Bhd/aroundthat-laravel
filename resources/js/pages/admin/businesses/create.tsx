import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import ComboboxField from '@/components/combobox-field';
import Heading from '@/components/heading';
import InertiaSheet from '@/components/inertia-sheet';
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
import { SheetClose, SheetFooter } from '@/components/ui/sheet';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { timezoneOptions } from '@/lib/locations';
import { store } from '@/routes/admin/businesses';

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
    locationOptions,
}: {
    ownerMethods: App.Enums.OwnerMethod[];
    locationOptions: App.Data.LocationOptionsData;
}) {
    const [ownerMethod, setOwnerMethod] =
        useState<App.Enums.OwnerMethod>('invite');

    return (
        <InertiaSheet
            title="New business"
            description="Create the business and give it its first owner."
            className="data-[side=right]:sm:max-w-2xl"
        >
            <Head title="New business" />

            <Form {...store.form()} className="flex min-h-0 flex-1 flex-col">
                {({ processing, errors }) => (
                    <>
                        <div className="flex-1 space-y-10 overflow-y-auto px-4">
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
                                <ComboboxField
                                    name="business[timezone]"
                                    label="Timezone"
                                    options={timezoneOptions(
                                        locationOptions.timezones,
                                    )}
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
                                        items={ownerMethodLabels}
                                        value={ownerMethod}
                                        onValueChange={(value) => {
                                            if (value) {
                                                setOwnerMethod(value);
                                            }
                                        }}
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
                        </div>

                        <SheetFooter className="flex-row justify-end border-t">
                            <SheetClose
                                render={
                                    <Button type="button" variant="outline" />
                                }
                            >
                                Cancel
                            </SheetClose>
                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner />}
                                Create business
                            </Button>
                        </SheetFooter>
                    </>
                )}
            </Form>
        </InertiaSheet>
    );
}
