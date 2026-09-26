import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import OutletPage from '@/components/app/outlets/outlet-page';
import TagPicker from '@/components/app/outlets/tag-picker';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PageErrors from '@/components/page-errors';
import { Button } from '@/components/ui/button';
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

const fieldNames = ['summary', 'description', 'category_id', 'tags'];

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

            <OutletPage outlet={outlet} can={can} recentReverts={recentReverts}>
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
                                            className="w-full"
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
