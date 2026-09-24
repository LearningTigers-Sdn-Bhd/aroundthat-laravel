import { Head, router, setLayoutProps } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, ImagePlus, Pencil, Trash2 } from 'lucide-react';
import OutletHeader from '@/components/app/outlets/outlet-header';
import ConfirmDialog from '@/components/confirm-dialog';
import FormDialog from '@/components/form-dialog';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PageErrors from '@/components/page-errors';
import TextField from '@/components/text-field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit, index } from '@/routes/outlets';
import {
    destroy,
    index as photos,
    reorder,
    store,
    update,
} from '@/routes/outlets/photos';

type Props = {
    outlet: App.Data.OutletData;
    images: App.Data.ImageData[];
    galleryLimit: number;
    can: { update: boolean; submit: boolean; archive: boolean };
};

export default function OutletPhotos({
    outlet,
    images,
    galleryLimit,
    can,
}: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Outlets', href: index() },
            { title: outlet.name, href: edit(outlet.id) },
            { title: 'Photos', href: photos(outlet.id) },
        ],
    });

    const cover = images.find((image) => image.kind === 'cover');
    const gallery = images.filter((image) => image.kind === 'gallery');

    const move = (from: number, to: number) => {
        const ids = gallery.map((image) => image.id);
        [ids[from], ids[to]] = [ids[to], ids[from]];

        router.put(reorder.url(outlet.id), { ids }, { preserveScroll: true });
    };

    return (
        <>
            <Head title={`${outlet.name} · Photos`} />

            <div className="flex max-w-3xl flex-1 flex-col gap-6 p-4">
                <OutletHeader outlet={outlet} can={can} />

                <PageErrors except={['file', 'alt_text', 'kind']} />

                <section className="space-y-4">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <Heading
                            variant="small"
                            title="Cover photo"
                            description="The first photo visitors see. A new cover replaces the current one."
                        />
                        {can.update && (
                            <UploadDialog
                                outletId={outlet.id}
                                kind="cover"
                                label={cover ? 'Replace cover' : 'Add cover'}
                            />
                        )}
                    </div>
                    {cover ? (
                        <PhotoCard
                            outletId={outlet.id}
                            image={cover}
                            canUpdate={can.update}
                            wide
                        />
                    ) : (
                        <EmptySlot text="No cover photo yet." />
                    )}
                </section>

                <section className="space-y-4">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <Heading
                            variant="small"
                            title="Gallery"
                            description={`Up to ${galleryLimit} photos, shown in this order.`}
                        />
                        {can.update && gallery.length < galleryLimit && (
                            <UploadDialog
                                outletId={outlet.id}
                                kind="gallery"
                                label="Add photo"
                            />
                        )}
                    </div>
                    {gallery.length === 0 ? (
                        <EmptySlot text="No gallery photos yet." />
                    ) : (
                        <div className="grid gap-4 sm:grid-cols-2">
                            {gallery.map((image, position) => (
                                <PhotoCard
                                    key={image.id}
                                    outletId={outlet.id}
                                    image={image}
                                    canUpdate={can.update}
                                    onMoveBack={
                                        position > 0
                                            ? () => move(position, position - 1)
                                            : undefined
                                    }
                                    onMoveForward={
                                        position < gallery.length - 1
                                            ? () => move(position, position + 1)
                                            : undefined
                                    }
                                />
                            ))}
                        </div>
                    )}
                </section>
            </div>
        </>
    );
}

function EmptySlot({ text }: { text: string }) {
    return (
        <p className="rounded-md border border-dashed p-8 text-center text-sm text-muted-foreground">
            {text}
        </p>
    );
}

function UploadDialog({
    outletId,
    kind,
    label,
}: {
    outletId: string;
    kind: App.Enums.ImageKind;
    label: string;
}) {
    return (
        <FormDialog
            trigger={
                <Button variant="outline">
                    <ImagePlus />
                    {label}
                </Button>
            }
            title={label}
            description="JPEG, PNG or WebP, up to 10 MB."
            form={store.form(outletId)}
            submitLabel="Upload"
        >
            {(errors) => (
                <>
                    <input type="hidden" name="kind" value={kind} />
                    <div className="grid gap-2">
                        <Label htmlFor="file">Photo</Label>
                        <Input
                            id="file"
                            name="file"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            aria-invalid={!!errors.file}
                            required
                        />
                        <InputError message={errors.file ?? errors.kind} />
                    </div>
                    <TextField
                        name="alt_text"
                        label="Description"
                        placeholder="Outdoor seating by the river at sunset"
                        maxLength={250}
                        error={errors.alt_text}
                        required
                    />
                    <p className="text-sm text-muted-foreground">
                        Describe what the photo shows, for visitors who use
                        screen readers.
                    </p>
                </>
            )}
        </FormDialog>
    );
}

function PhotoCard({
    outletId,
    image,
    canUpdate,
    wide = false,
    onMoveBack,
    onMoveForward,
}: {
    outletId: string;
    image: App.Data.ImageData;
    canUpdate: boolean;
    wide?: boolean;
    onMoveBack?: () => void;
    onMoveForward?: () => void;
}) {
    const route = { outlet: outletId, image: image.id };

    return (
        <figure className="overflow-hidden rounded-md border">
            <img
                src={image.url}
                alt={image.alt_text}
                width={image.width}
                height={image.height}
                loading="lazy"
                className={
                    wide
                        ? 'aspect-[21/9] w-full object-cover'
                        : 'aspect-[4/3] w-full object-cover'
                }
            />
            <figcaption className="flex items-center gap-2 p-3">
                <p className="flex-1 truncate text-sm text-muted-foreground">
                    {image.alt_text}
                </p>
                {canUpdate && (
                    <div className="flex shrink-0">
                        {(onMoveBack || onMoveForward) && (
                            <>
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    aria-label="Move earlier"
                                    disabled={!onMoveBack}
                                    onClick={onMoveBack}
                                >
                                    <ArrowLeft />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    aria-label="Move later"
                                    disabled={!onMoveForward}
                                    onClick={onMoveForward}
                                >
                                    <ArrowRight />
                                </Button>
                            </>
                        )}
                        <FormDialog
                            trigger={
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    aria-label="Edit description"
                                >
                                    <Pencil />
                                </Button>
                            }
                            title="Photo description"
                            form={update.form(route)}
                            submitLabel="Save"
                        >
                            {(errors) => (
                                <TextField
                                    name="alt_text"
                                    label="Description"
                                    defaultValue={image.alt_text}
                                    maxLength={250}
                                    error={errors.alt_text}
                                    required
                                />
                            )}
                        </FormDialog>
                        <ConfirmDialog
                            trigger={
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    aria-label="Remove photo"
                                >
                                    <Trash2 />
                                </Button>
                            }
                            title="Remove this photo?"
                            description="Visitors stop seeing it at once."
                            form={destroy.form(route)}
                            confirmLabel="Remove"
                            destructive
                        />
                    </div>
                )}
            </figcaption>
        </figure>
    );
}
