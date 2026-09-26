import { Head, router } from '@inertiajs/react';
import { ArrowDown, ArrowUp, Pencil, Plus } from 'lucide-react';
import FormDialog from '@/components/form-dialog';
import Heading from '@/components/heading';
import TextField from '@/components/text-field';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { dashboard } from '@/routes/admin';
import { index, reorder, store, update } from '@/routes/admin/categories';

type Props = {
    categories: App.Data.Admin.CategoryData[];
};

export default function CategoriesIndex({ categories }: Props) {
    const move = (from: number, to: number) => {
        const ids = categories.map((category) => category.id);
        [ids[from], ids[to]] = [ids[to], ids[from]];

        router.put(reorder.url(), { ids }, { preserveScroll: true });
    };

    return (
        <>
            <Head title="Categories" />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Categories"
                        description="What kind of place an outlet is. Owners pick one; hidden categories stay on outlets that already use them."
                    />
                    <FormDialog
                        trigger={
                            <Button>
                                <Plus />
                                New category
                            </Button>
                        }
                        title="New category"
                        form={store.form()}
                        submitLabel="Add"
                    >
                        {(errors) => <CategoryFields errors={errors} />}
                    </FormDialog>
                </div>

                <div className="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-20">Order</TableHead>
                                <TableHead>Name</TableHead>
                                <TableHead>Slug</TableHead>
                                <TableHead>Outlets</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="w-12" />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {categories.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={6}
                                        className="text-center text-muted-foreground"
                                    >
                                        No categories yet.
                                    </TableCell>
                                </TableRow>
                            )}
                            {categories.map((category, position) => (
                                <TableRow key={category.id}>
                                    <TableCell>
                                        <div className="flex">
                                            <Button
                                                variant="ghost"
                                                size="icon-sm"
                                                aria-label={`Move ${category.name} up`}
                                                disabled={position === 0}
                                                onClick={() =>
                                                    move(position, position - 1)
                                                }
                                            >
                                                <ArrowUp />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon-sm"
                                                aria-label={`Move ${category.name} down`}
                                                disabled={
                                                    position ===
                                                    categories.length - 1
                                                }
                                                onClick={() =>
                                                    move(position, position + 1)
                                                }
                                            >
                                                <ArrowDown />
                                            </Button>
                                        </div>
                                    </TableCell>
                                    <TableCell className="font-medium">
                                        {category.name}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {category.slug}
                                    </TableCell>
                                    <TableCell>
                                        {category.outlets_count}
                                    </TableCell>
                                    <TableCell>
                                        {category.is_active ? (
                                            <Badge variant="secondary">
                                                Active
                                            </Badge>
                                        ) : (
                                            <Badge variant="outline">
                                                Hidden
                                            </Badge>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        <FormDialog
                                            trigger={
                                                <Button
                                                    variant="ghost"
                                                    size="icon-sm"
                                                    aria-label={`Edit ${category.name}`}
                                                >
                                                    <Pencil />
                                                </Button>
                                            }
                                            title={`Edit ${category.name}`}
                                            description="The slug stays the same when you rename it."
                                            form={update.form(category.id)}
                                            submitLabel="Save"
                                        >
                                            {(errors) => (
                                                <CategoryFields
                                                    errors={errors}
                                                    category={category}
                                                />
                                            )}
                                        </FormDialog>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>
        </>
    );
}

function CategoryFields({
    errors,
    category,
}: {
    errors: Record<string, string>;
    category?: App.Data.Admin.CategoryData;
}) {
    return (
        <>
            <TextField
                name="name"
                label="Name"
                defaultValue={category?.name}
                maxLength={80}
                error={errors.name}
                required
            />
            <div className="flex items-center gap-2">
                <Checkbox
                    id="is_active"
                    name="is_active"
                    value="1"
                    defaultChecked={category?.is_active ?? true}
                />
                <Label htmlFor="is_active">Owners can pick it</Label>
            </div>
        </>
    );
}

CategoriesIndex.layout = {
    breadcrumbs: [
        { title: 'Admin', href: dashboard() },
        { title: 'Categories', href: index() },
    ],
};
