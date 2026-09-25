import { Form, Head, setLayoutProps } from '@inertiajs/react';
import OutletPage from '@/components/app/outlets/outlet-page';
import Heading from '@/components/heading';
import PageErrors from '@/components/page-errors';
import TextField from '@/components/text-field';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { edit, index } from '@/routes/outlets';
import { edit as editLinks, update } from '@/routes/outlets/links';

type Props = {
    outlet: App.Data.OutletData;
    recentReverts: App.Data.RevertNoticeData[];
    place: App.Data.PlaceProfileData;
    can: { update: boolean; submit: boolean; archive: boolean };
};

const fieldNames = ['website', 'whatsapp', 'facebook', 'instagram'];

export default function OutletLinks({
    outlet,
    recentReverts,
    place,
    can,
}: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Outlets', href: index() },
            { title: outlet.name, href: edit(outlet.id) },
            { title: 'Social links', href: editLinks(outlet.id) },
        ],
    });

    return (
        <>
            <Head title={`${outlet.name} · Social links`} />

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
                                title="Social links"
                                description="The outlet's phone and email come from the Details tab."
                            />
                            <div className="grid gap-4 sm:grid-cols-2">
                                <TextField
                                    name="website"
                                    label="Website"
                                    type="url"
                                    defaultValue={place.website ?? ''}
                                    placeholder="https://"
                                    error={errors.website}
                                />
                                <TextField
                                    name="whatsapp"
                                    label="WhatsApp"
                                    type="tel"
                                    defaultValue={place.whatsapp ?? ''}
                                    placeholder="+60 12-345 6789"
                                    error={errors.whatsapp}
                                />
                                <TextField
                                    name="facebook"
                                    label="Facebook"
                                    type="url"
                                    defaultValue={place.facebook ?? ''}
                                    placeholder="https://facebook.com/…"
                                    error={errors.facebook}
                                />
                                <TextField
                                    name="instagram"
                                    label="Instagram"
                                    type="url"
                                    defaultValue={place.instagram ?? ''}
                                    placeholder="https://instagram.com/…"
                                    error={errors.instagram}
                                />
                            </div>

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
