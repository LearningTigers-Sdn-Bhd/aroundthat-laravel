import { Form, Head, setLayoutProps } from '@inertiajs/react';
import OutletHeader from '@/components/app/outlets/outlet-header';
import OutletFields, { outletFieldNames } from '@/components/outlet-fields';
import PageErrors from '@/components/page-errors';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { edit, index, update } from '@/routes/outlets';

type Props = {
    outlet: App.Data.OutletData;
    recentReverts: App.Data.RevertNoticeData[];
    locationOptions: App.Data.LocationOptionsData;
    can: { update: boolean; submit: boolean; archive: boolean };
};

export default function EditOutlet({ outlet, recentReverts, locationOptions, can }: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Outlets', href: index() },
            { title: outlet.name, href: edit(outlet.id) },
        ],
    });

    return (
        <>
            <Head title={outlet.name} />

            <div className="flex max-w-2xl flex-1 flex-col gap-6 p-4">
                <OutletHeader outlet={outlet} can={can} recentReverts={recentReverts} />

                <PageErrors except={outletFieldNames} />

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
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    Save
                                </Button>
                            )}
                        </fieldset>
                    )}
                </Form>
            </div>
        </>
    );
}
