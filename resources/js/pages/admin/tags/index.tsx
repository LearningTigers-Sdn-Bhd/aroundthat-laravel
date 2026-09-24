import { Head, Link } from '@inertiajs/react';
import { Merge, Pencil, Plus, Tags } from 'lucide-react';
import ActionButton from '@/components/action-button';
import ComboboxField from '@/components/combobox-field';
import type { ComboboxOption } from '@/components/combobox-field';
import type { DataTableColumn } from '@/components/data-table';
import DataTable from '@/components/data-table';
import FormDialog from '@/components/form-dialog';
import Heading from '@/components/heading';
import PageErrors from '@/components/page-errors';
import ReasonDialog from '@/components/reason-dialog';
import StatusBadge from '@/components/status-badge';
import TextField from '@/components/text-field';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { formatDate } from '@/lib/format';
import { dashboard } from '@/routes/admin';
import { show as showBusiness } from '@/routes/admin/businesses';
import {
    approve,
    index,
    merge,
    reject,
    store,
    update,
} from '@/routes/admin/tags';

type Props = {
    tags: Illuminate.LengthAwarePaginator<number, App.Data.Admin.TagData>;
    mergeTargets: ComboboxOption[];
};

export default function TagsIndex({ tags, mergeTargets }: Props) {
    const columns: DataTableColumn<App.Data.Admin.TagData>[] = [
        {
            key: 'name',
            header: 'Tag',
            sort: 'name',
            cell: (tag) => (
                <div className="space-y-0.5">
                    <div className="font-medium">{tag.name}</div>
                    <div className="text-xs text-muted-foreground">
                        {tag.slug}
                    </div>
                </div>
            ),
        },
        {
            key: 'status',
            header: 'Status',
            cell: (tag) =>
                tag.merged_into_name ? (
                    <Badge variant="outline">
                        Merged into {tag.merged_into_name}
                    </Badge>
                ) : (
                    <div className="flex gap-1">
                        <StatusBadge status={tag.status} />
                        {!tag.is_active && (
                            <Badge variant="outline">Hidden</Badge>
                        )}
                    </div>
                ),
        },
        {
            key: 'created_by',
            header: 'Created by',
            cell: (tag) =>
                tag.created_by_business_id ? (
                    <Link
                        href={showBusiness(tag.created_by_business_id)}
                        className="hover:underline"
                    >
                        {tag.created_by_business_name}
                    </Link>
                ) : (
                    <span className="text-muted-foreground">Admin</span>
                ),
        },
        {
            key: 'outlets_count',
            header: 'Outlets',
            cell: (tag) => tag.outlets_count,
        },
        {
            key: 'created_at',
            header: 'Added',
            sort: 'created_at',
            cell: (tag) => formatDate(tag.created_at),
        },
        {
            key: 'actions',
            header: '',
            className: 'w-0',
            cell: (tag) => (
                <TagActions
                    tag={tag}
                    mergeTargets={mergeTargets.filter(
                        (target) => target.value !== tag.id,
                    )}
                />
            ),
        },
    ];

    return (
        <>
            <Head title="Tags" />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Tags"
                        description="Labels owners add to their outlets. Tags owners create wait here for review; owners are not told."
                    />
                    <FormDialog
                        trigger={
                            <Button>
                                <Plus />
                                New tag
                            </Button>
                        }
                        title="New tag"
                        form={store.form()}
                        submitLabel="Add"
                    >
                        {(errors) => <TagFields errors={errors} />}
                    </FormDialog>
                </div>

                <PageErrors />

                <DataTable
                    rows={tags}
                    columns={columns}
                    rowKey={(tag) => tag.id}
                    searchPlaceholder="Search tags"
                    filters={[
                        {
                            name: 'status',
                            label: 'Statuses',
                            options: [
                                { value: 'pending', label: 'Pending review' },
                                { value: 'approved', label: 'Approved' },
                                { value: 'rejected', label: 'Rejected' },
                            ],
                        },
                        {
                            name: 'merged',
                            label: 'Merge states',
                            options: [
                                { value: 'false', label: 'Not merged' },
                                { value: 'true', label: 'Merged' },
                            ],
                        },
                    ]}
                    emptyTitle="No tags yet"
                    emptyIcon={Tags}
                />
            </div>
        </>
    );
}

function TagActions({
    tag,
    mergeTargets,
}: {
    tag: App.Data.Admin.TagData;
    mergeTargets: ComboboxOption[];
}) {
    if (tag.merged_into_name) {
        return null;
    }

    return (
        <div className="flex items-center justify-end gap-1">
            {tag.status === 'pending' && (
                <>
                    <ActionButton form={approve.form(tag.id)} size="sm">
                        Approve
                    </ActionButton>
                    <ReasonDialog
                        trigger={
                            <Button variant="outline" size="sm">
                                Reject
                            </Button>
                        }
                        title={`Reject “${tag.name}”?`}
                        description="The tag is removed from every outlet that carries it, and owners cannot add it again."
                        form={reject.form(tag.id)}
                        submitLabel="Reject"
                        destructive
                    />
                </>
            )}
            {tag.status !== 'rejected' && (
                <FormDialog
                    trigger={
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            aria-label={`Merge ${tag.name}`}
                        >
                            <Merge />
                        </Button>
                    }
                    title={`Merge “${tag.name}”`}
                    description="Its outlets carry the chosen tag instead, and typing this name finds the chosen tag."
                    form={merge.form(tag.id)}
                    submitLabel="Merge"
                >
                    {(errors) => (
                        <ComboboxField
                            name="target_id"
                            label="Merge into"
                            options={mergeTargets}
                            placeholder="Search approved tags"
                            error={errors.target_id}
                            required
                        />
                    )}
                </FormDialog>
            )}
            <FormDialog
                trigger={
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        aria-label={`Edit ${tag.name}`}
                    >
                        <Pencil />
                    </Button>
                }
                title={`Edit “${tag.name}”`}
                description="The slug stays the same when you rename it."
                form={update.form(tag.id)}
                submitLabel="Save"
            >
                {(errors) => <TagFields errors={errors} tag={tag} />}
            </FormDialog>
        </div>
    );
}

function TagFields({
    errors,
    tag,
}: {
    errors: Record<string, string>;
    tag?: App.Data.Admin.TagData;
}) {
    return (
        <>
            <TextField
                name="name"
                label="Name"
                defaultValue={tag?.name}
                maxLength={40}
                error={errors.name}
                required
            />
            <div className="flex items-center gap-2">
                <Checkbox
                    id="is_active"
                    name="is_active"
                    value="1"
                    defaultChecked={tag?.is_active ?? true}
                />
                <Label htmlFor="is_active">Owners can pick it</Label>
            </div>
        </>
    );
}

TagsIndex.layout = {
    breadcrumbs: [
        { title: 'Admin', href: dashboard() },
        { title: 'Tags', href: index() },
    ],
};
