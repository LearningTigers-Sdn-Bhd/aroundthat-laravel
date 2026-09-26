import { Form, Head } from '@inertiajs/react';
import InertiaSheet from '@/components/inertia-sheet';
import OutletFields, { outletFieldNames } from '@/components/outlet-fields';
import PageErrors from '@/components/page-errors';
import { Button } from '@/components/ui/button';
import { SheetClose, SheetFooter } from '@/components/ui/sheet';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/outlets';

export default function CreateOutlet({
    timezone,
    locationOptions,
}: {
    timezone: string;
    locationOptions: App.Data.LocationOptionsData;
}) {
    return (
        <InertiaSheet
            title="New outlet"
            description="It is saved as a draft. Submit it for review when the details are complete."
            className="data-[side=right]:sm:max-w-2xl"
        >
            <Head title="New outlet" />

            <Form {...store.form()} className="flex min-h-0 flex-1 flex-col">
                {({ processing, errors }) => (
                    <>
                        <div className="flex-1 space-y-6 overflow-y-auto px-4">
                            <PageErrors except={outletFieldNames} />
                            <OutletFields
                                errors={errors}
                                locationOptions={locationOptions}
                                defaultTimezone={timezone}
                            />
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
                                Create outlet
                            </Button>
                        </SheetFooter>
                    </>
                )}
            </Form>
        </InertiaSheet>
    );
}
