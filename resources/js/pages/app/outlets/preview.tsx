import { Head, setLayoutProps } from '@inertiajs/react';
import OutletPage from '@/components/app/outlets/outlet-page';
import Notice from '@/components/notice';
import PlacePreview from '@/components/place-preview';
import { edit, index, preview as previewRoute } from '@/routes/outlets';

type Props = {
    outlet: App.Data.OutletData;
    recentReverts: App.Data.RevertNoticeData[];
    preview: App.Data.PlacePreviewData;
    can: { submit: boolean; archive: boolean };
};

export default function OutletPreview({
    outlet,
    recentReverts,
    preview,
    can,
}: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Outlets', href: index() },
            { title: outlet.name, href: edit(outlet.id) },
            { title: 'Preview', href: previewRoute(outlet.id) },
        ],
    });

    return (
        <>
            <Head title={`${outlet.name} · Preview`} />

            <OutletPage outlet={outlet} can={can} recentReverts={recentReverts}>
                {!preview.place.is_public && (
                    <Notice title="Visitors cannot see this yet">
                        {preview.place.hidden_reason
                            ? `An admin hid this outlet: ${preview.place.hidden_reason}`
                            : preview.place.is_listed
                              ? 'It shows once an admin has approved the outlet and its business, and its public page is complete.'
                              : 'List the outlet on the Details tab when it is ready.'}
                    </Notice>
                )}

                <PlacePreview preview={preview} />
            </OutletPage>
        </>
    );
}
