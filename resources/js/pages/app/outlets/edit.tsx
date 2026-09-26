import { Form, Head, setLayoutProps } from '@inertiajs/react';
import OutletPage from '@/components/app/outlets/outlet-page';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import Notice from '@/components/notice';
import OutletFields, { outletFieldNames } from '@/components/outlet-fields';
import PageErrors from '@/components/page-errors';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { edit, index, update } from '@/routes/outlets';
import { update as updateListing } from '@/routes/outlets/listing';

type Props = {
    outlet: App.Data.OutletData;
    recentReverts: App.Data.RevertNoticeData[];
    locationOptions: App.Data.LocationOptionsData;
    /** Only for members who manage the public page. */
    place: App.Data.PlaceProfileData | null;
    can: {
        update: boolean;
        updateListing: boolean;
        submit: boolean;
        archive: boolean;
    };
};

const missingLabels: Record<string, string> = {
    summary: 'a summary (on the Public page tab)',
    category_id: 'a category (on the Public page tab)',
    coordinates: 'the map location (on the Location tab)',
    hours: 'opening hours (on the Hours tab)',
};

export default function EditOutlet({
    outlet,
    recentReverts,
    locationOptions,
    place,
    can,
}: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Outlets', href: index() },
            { title: outlet.name, href: edit(outlet.id) },
        ],
    });

    return (
        <>
            <Head title={outlet.name} />

            <OutletPage outlet={outlet} can={can} recentReverts={recentReverts}>
                <PageErrors except={[...outletFieldNames, 'is_listed']} />

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
                                <div className="flex justify-end">
                                    <Button type="submit" disabled={processing}>
                                        {processing && <Spinner />}
                                        Save
                                    </Button>
                                </div>
                            )}
                        </fieldset>
                    )}
                </Form>

                {place && (
                    <Listing
                        outlet={outlet}
                        place={place}
                        canUpdate={can.updateListing}
                    />
                )}
            </OutletPage>
        </>
    );
}

/**
 * Whether visitors can find the outlet, and what it still needs before they can.
 */
function Listing({
    outlet,
    place,
    canUpdate,
}: {
    outlet: App.Data.OutletData;
    place: App.Data.PlaceProfileData;
    canUpdate: boolean;
}) {
    return (
        <Form
            {...updateListing.form(outlet.id)}
            options={{ preserveScroll: true }}
            className="border-t pt-6"
        >
            {({ processing, errors }) => (
                <fieldset
                    disabled={!canUpdate}
                    className="space-y-4 disabled:opacity-60"
                >
                    <Heading variant="small" title="Listing" />
                    {place.missing_for_listing.length > 0 && (
                        <Notice title="Not ready to list">
                            Add{' '}
                            {place.missing_for_listing
                                .map((field) => missingLabels[field] ?? field)
                                .join(', ')}{' '}
                            before listing the outlet.
                        </Notice>
                    )}
                    <div className="flex items-start gap-2">
                        <Checkbox
                            id="is_listed"
                            name="is_listed"
                            value="1"
                            defaultChecked={place.is_listed}
                            disabled={!!place.hidden_reason && !place.is_listed}
                        />
                        <div className="grid gap-1">
                            <Label htmlFor="is_listed">
                                List this outlet publicly
                            </Label>
                            <p className="text-sm text-muted-foreground">
                                {place.is_public
                                    ? 'Visitors can find it now.'
                                    : place.is_listed
                                      ? 'It shows once an admin has approved the outlet and its business.'
                                      : 'Visitors cannot find it until you list it.'}
                            </p>
                        </div>
                    </div>
                    <InputError message={errors.is_listed} />

                    {canUpdate && (
                        <div className="flex justify-end">
                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner />}
                                Save listing
                            </Button>
                        </div>
                    )}
                </fieldset>
            )}
        </Form>
    );
}
