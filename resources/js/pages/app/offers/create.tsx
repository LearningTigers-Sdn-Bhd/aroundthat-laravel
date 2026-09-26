import { Form, Head } from '@inertiajs/react';
import OfferFields, {
    offerFieldNames,
} from '@/components/app/offers/offer-fields';
import OfferOutletsField from '@/components/app/offers/offer-outlets-field';
import InertiaSheet from '@/components/inertia-sheet';
import PageErrors from '@/components/page-errors';
import { Button } from '@/components/ui/button';
import { SheetClose, SheetFooter } from '@/components/ui/sheet';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/offers';

export default function CreateOffer({
    timezone,
    outletOptions,
    currency,
}: {
    timezone: string;
    outletOptions: App.Data.OutletOptionData[];
    currency: string;
}) {
    return (
        <InertiaSheet
            title="New offer"
            description="It is saved as a draft. Activate it when it is ready."
            className="data-[side=right]:sm:max-w-2xl"
        >
            <Head title="New offer" />

            <Form {...store.form()} className="flex min-h-0 flex-1 flex-col">
                {({ processing, errors }) => (
                    <>
                        <div className="flex-1 space-y-6 overflow-y-auto px-4">
                            <PageErrors
                                except={[...offerFieldNames, 'outlet_ids']}
                            />
                            <OfferFields
                                errors={errors}
                                currency={currency}
                                timezone={timezone}
                            />
                            <OfferOutletsField
                                outletOptions={outletOptions}
                                defaultOutletIds={
                                    outletOptions.length === 1
                                        ? [outletOptions[0].id]
                                        : []
                                }
                                error={errors.outlet_ids}
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
                                Create offer
                            </Button>
                        </SheetFooter>
                    </>
                )}
            </Form>
        </InertiaSheet>
    );
}
