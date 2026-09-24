import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import OutletHeader from '@/components/app/outlets/outlet-header';
import TagPicker from '@/components/app/outlets/tag-picker';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import Notice from '@/components/notice';
import PageErrors from '@/components/page-errors';
import TextField from '@/components/text-field';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { edit, index } from '@/routes/outlets';
import { edit as editPublic, update } from '@/routes/outlets/public';

type Props = {
    outlet: App.Data.OutletData;
    recentReverts: App.Data.RevertNoticeData[];
    place: App.Data.PlaceProfileData;
    categories: App.Data.CategoryOptionData[];
    tagOptions: App.Data.TagOptionData[];
    can: { update: boolean; submit: boolean; archive: boolean };
};

const MAX_TAGS = 10;

const fieldNames = [
    'summary',
    'description',
    'category_id',
    'google_maps_url',
    'latitude',
    'longitude',
    'website',
    'whatsapp',
    'facebook',
    'instagram',
    'tags',
    'is_listed',
];

const missingLabels: Record<string, string> = {
    summary: 'a summary',
    category_id: 'a category',
    coordinates: 'the map location',
    hours: 'opening hours (on the Hours tab)',
};

export default function OutletPublicPage({
    outlet,
    recentReverts,
    place,
    categories,
    tagOptions,
    can,
}: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Outlets', href: index() },
            { title: outlet.name, href: edit(outlet.id) },
            { title: 'Public page', href: editPublic(outlet.id) },
        ],
    });

    return (
        <>
            <Head title={`${outlet.name} · Public page`} />

            <div className="flex max-w-2xl flex-1 flex-col gap-6 p-4">
                <OutletHeader outlet={outlet} can={can} recentReverts={recentReverts} />

                <PageErrors except={fieldNames} />

                <Form
                    {...update.form(outlet.id)}
                    options={{ preserveScroll: true }}
                >
                    {({ processing, errors }) => (
                        <fieldset
                            disabled={!can.update}
                            className="space-y-8 disabled:opacity-60"
                        >
                            <section className="space-y-4">
                                <Heading
                                    variant="small"
                                    title="From the outlet details"
                                    description="Visitors see these as they are. Change them on the Details tab."
                                />
                                <dl className="grid gap-x-8 gap-y-2 rounded-md border p-4 text-sm sm:grid-cols-2">
                                    <div>
                                        <dt className="text-muted-foreground">
                                            Name
                                        </dt>
                                        <dd>{outlet.name}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-muted-foreground">
                                            Phone
                                        </dt>
                                        <dd>{outlet.contact_phone ?? '—'}</dd>
                                    </div>
                                    <div className="sm:col-span-2">
                                        <dt className="text-muted-foreground">
                                            Address
                                        </dt>
                                        <dd>
                                            {[
                                                outlet.address_line_1,
                                                outlet.address_line_2,
                                                `${outlet.postcode} ${outlet.city}`,
                                                outlet.state,
                                            ]
                                                .filter(Boolean)
                                                .join(', ')}
                                        </dd>
                                    </div>
                                    <Link
                                        href={edit(outlet.id)}
                                        className="text-sm underline underline-offset-4 sm:col-span-2"
                                    >
                                        Edit details
                                    </Link>
                                </dl>
                            </section>

                            <section className="space-y-6">
                                <Heading
                                    variant="small"
                                    title="About the place"
                                />
                                <div className="grid gap-2">
                                    <Label htmlFor="summary">Summary</Label>
                                    <Textarea
                                        id="summary"
                                        name="summary"
                                        rows={2}
                                        maxLength={280}
                                        defaultValue={place.summary ?? ''}
                                        aria-invalid={!!errors.summary}
                                        placeholder="One or two sentences shown in search results."
                                    />
                                    <InputError message={errors.summary} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="description">
                                        Description
                                    </Label>
                                    <Textarea
                                        id="description"
                                        name="description"
                                        rows={6}
                                        maxLength={5000}
                                        defaultValue={place.description ?? ''}
                                        aria-invalid={!!errors.description}
                                    />
                                    <InputError message={errors.description} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="category_id">
                                        Category
                                    </Label>
                                    <Select
                                        name="category_id"
                                        items={[
                                            {
                                                value: null,
                                                label: 'Choose a category',
                                            },
                                            ...categories.map((category) => ({
                                                value: category.id,
                                                label: category.name,
                                            })),
                                        ]}
                                        defaultValue={
                                            place.category?.id ?? null
                                        }
                                    >
                                        <SelectTrigger
                                            id="category_id"
                                            aria-invalid={!!errors.category_id}
                                        >
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {categories.map((category) => (
                                                <SelectItem
                                                    key={category.id}
                                                    value={category.id}
                                                >
                                                    {category.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.category_id} />
                                </div>
                                <TagPicker
                                    name="tags"
                                    label="Tags"
                                    options={tagOptions}
                                    defaultValue={place.tags}
                                    max={MAX_TAGS}
                                    error={errors.tags}
                                />
                            </section>

                            <section className="space-y-6">
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
                                    When a link is given, its coordinates
                                    replace the ones typed here.
                                </p>
                            </section>

                            <section className="space-y-6">
                                <Heading
                                    variant="small"
                                    title="Links"
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
                            </section>

                            <section className="space-y-4">
                                <Heading variant="small" title="Listing" />
                                {place.hidden_reason && (
                                    <Notice title="Hidden by an admin">
                                        {place.hidden_reason} Visitors cannot
                                        find this outlet until an admin shows it
                                        again.
                                    </Notice>
                                )}
                                {place.missing_for_listing.length > 0 && (
                                    <Notice title="Not ready to list">
                                        Add{' '}
                                        {place.missing_for_listing
                                            .map(
                                                (field) =>
                                                    missingLabels[field] ??
                                                    field,
                                            )
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
                                        disabled={
                                            !!place.hidden_reason &&
                                            !place.is_listed
                                        }
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
                            </section>

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
