import { Form, Head, setLayoutProps } from '@inertiajs/react';
import OutletPage from '@/components/app/outlets/outlet-page';
import Heading from '@/components/heading';
import PageErrors from '@/components/page-errors';
import TextField from '@/components/text-field';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { edit, index } from '@/routes/outlets';
import { edit as editLocation, update } from '@/routes/outlets/location';

type Props = {
    outlet: App.Data.OutletData;
    recentReverts: App.Data.RevertNoticeData[];
    place: App.Data.PlaceProfileData;
    can: { update: boolean; submit: boolean; archive: boolean };
};

const fieldNames = ['google_maps_url', 'latitude', 'longitude'];

export default function OutletLocation({
    outlet,
    recentReverts,
    place,
    can,
}: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Outlets', href: index() },
            { title: outlet.name, href: edit(outlet.id) },
            { title: 'Location', href: editLocation(outlet.id) },
        ],
    });

    return (
        <>
            <Head title={`${outlet.name} · Location`} />

            <OutletPage outlet={outlet} can={can} recentReverts={recentReverts}>
                <PageErrors except={fieldNames} />

                <Form
                    {...update.form(outlet.id)}
                    options={{ preserveScroll: true }}
                >
                    {({ processing, errors }) => (
                        <fieldset
                            disabled={!can.update}
                            className="space-y-6 disabled:opacity-60"
                        >
                            <Heading
                                variant="small"
                                title="Map location"
                                description="Paste the link from Google Maps, or type the coordinates."
                            />
                            <TextField
                                name="google_maps_url"
                                label="Google Maps link"
                                type="url"
                                defaultValue={place.google_maps_url ?? ''}
                                placeholder="https://www.google.com/maps/place/…"
                                error={errors.google_maps_url}
                            />
                            <div className="grid gap-4 sm:grid-cols-2">
                                <TextField
                                    name="latitude"
                                    label="Latitude"
                                    inputMode="decimal"
                                    defaultValue={place.latitude ?? ''}
                                    error={errors.latitude}
                                />
                                <TextField
                                    name="longitude"
                                    label="Longitude"
                                    inputMode="decimal"
                                    defaultValue={place.longitude ?? ''}
                                    error={errors.longitude}
                                />
                            </div>
                            <p className="text-sm text-muted-foreground">
                                When a link is given, its coordinates replace
                                the ones typed here.
                            </p>

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
            </OutletPage>
        </>
    );
}
