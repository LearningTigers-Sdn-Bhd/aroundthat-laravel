import { Form, Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import OutletFields, { outletFieldNames } from '@/components/outlet-fields';
import PageErrors from '@/components/page-errors';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { create, index, store } from '@/routes/outlets';

export default function CreateOutlet({ timezone }: { timezone: string }) {
    return (
        <>
            <Head title="New outlet" />

            <div className="flex max-w-2xl flex-1 flex-col gap-6 p-4">
                <Heading
                    title="New outlet"
                    description="It is saved as a draft. Submit it for review when the details are complete."
                />

                <Form {...store.form()} className="space-y-6">
                    {({ processing, errors }) => (
                        <>
                            <PageErrors except={outletFieldNames} />
                            <OutletFields
                                errors={errors}
                                defaultTimezone={timezone}
                            />

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

CreateOutlet.layout = {
    breadcrumbs: [
        { title: 'Outlets', href: index() },
        { title: 'New outlet', href: create() },
    ],
};
