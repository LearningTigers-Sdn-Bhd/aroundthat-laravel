import { Form, Head } from '@inertiajs/react';
import InertiaSheet from '@/components/inertia-sheet';
import InputError from '@/components/input-error';
import OutletFields from '@/components/outlet-fields';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { SheetClose, SheetFooter } from '@/components/ui/sheet';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/admin/businesses/outlets';

export default function CreateOutlet({
    business,
    locationOptions,
}: {
    business: App.Data.Admin.BusinessData;
    locationOptions: App.Data.LocationOptionsData;
}) {
    const canApprove = business.onboarding_status === 'approved';

    return (
        <InertiaSheet
            title="New outlet"
            description={`A place where ${business.name} trades.`}
            className="data-[side=right]:sm:max-w-2xl"
        >
            <Head title={`New outlet for ${business.name}`} />

            <Form
                {...store.form(business.id)}
                className="flex min-h-0 flex-1 flex-col"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="flex-1 space-y-6 overflow-y-auto px-4">
                            <OutletFields
                                errors={errors}
                                locationOptions={locationOptions}
                                defaultTimezone={business.timezone}
                            />

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
