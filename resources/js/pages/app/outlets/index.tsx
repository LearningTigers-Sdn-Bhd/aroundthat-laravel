import { Head, Link } from '@inertiajs/react';
import { Plus, Store } from 'lucide-react';
import Heading from '@/components/heading';
import StatusBadge, { recordStatus } from '@/components/status-badge';
import ButtonLink from '@/components/button-link';
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { create, edit, index } from '@/routes/outlets';

type Props = {
    outlets: App.Data.OutletData[];
    canCreate: boolean;
};

export default function OutletsIndex({ outlets, canCreate }: Props) {
    return (
        <>
            <Head title="Outlets" />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Outlets"
                        description="The places where your business trades. An admin reviews each new outlet before it goes live."
                    />
                    {canCreate && (
                        <ButtonLink href={create()}>
                                <Plus />
                                Add outlet
                            </ButtonLink>
                    )}
                </div>

                {outlets.length === 0 ? (
                    <Empty className="border">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <Store />
                            </EmptyMedia>
                            <EmptyTitle>No outlets yet</EmptyTitle>
                            <EmptyDescription>
                                Add the places where your business trades. An
                                admin reviews each one before it goes live.
                            </EmptyDescription>
                        </EmptyHeader>
                        {canCreate && (
                            <EmptyContent>
                                <ButtonLink href={create()}>
                                        <Plus />
                                        Add outlet
                                    </ButtonLink>
                            </EmptyContent>
                        )}
                    </Empty>
                ) : (
                    <div className="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Outlet</TableHead>
                                    <TableHead>City</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Inside</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {outlets.map((outlet) => (
                                    <TableRow key={outlet.id}>
                                        <TableCell className="font-medium">
                                            <Link
                                                href={edit(outlet.id)}
                                                className="hover:underline"
                                            >
                                                {outlet.name}
                                            </Link>
                                        </TableCell>
                                        <TableCell>{outlet.city}</TableCell>
                                        <TableCell>
                                            <StatusBadge
                                                status={recordStatus(outlet)}
                                            />
                                        </TableCell>
                                        <TableCell>
                                            {outlet.host_outlet?.name ?? '—'}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}
            </div>
        </>
    );
}

OutletsIndex.layout = {
    breadcrumbs: [{ title: 'Outlets', href: index() }],
};
