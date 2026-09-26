import { Link, router } from '@inertiajs/react';
import { EllipsisVertical, Store } from 'lucide-react';
import { useState } from 'react';
import ReasonDialog from '@/components/reason-dialog';
import StatusBadge, { recordStatus } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Empty,
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
import {
    approve,
    archive,
    hide,
    reactivate,
    reject,
    restore,
    show,
    suspend,
    unhide,
} from '@/routes/admin/outlets';
import type { RouteDefinition } from '@/wayfinder';

type Props = {
    outlets: App.Data.Admin.OutletData[];
};

type ReasonAction = 'reject' | 'suspend' | 'hide';

const reasonDialogs: Record<
    ReasonAction,
    {
        title: (outlet: App.Data.Admin.OutletData) => string;
        description: string;
        route: typeof reject;
        submitLabel: string;
    }
> = {
    reject: {
        title: (outlet) => `Reject ${outlet.name}?`,
        description:
            'The owner sees this reason, fixes the details and submits again.',
        route: reject,
        submitLabel: 'Reject',
    },
    suspend: {
        title: (outlet) => `Suspend ${outlet.name}?`,
        description: 'The outlet stops trading until it is reactivated.',
        route: suspend,
        submitLabel: 'Suspend',
    },
    hide: {
        title: (outlet) => `Hide ${outlet.name} from visitors?`,
        description:
            'The outlet keeps trading, but visitors cannot find it and the owners cannot list it again until you unhide it. The owners get an email with your reason.',
        route: hide,
        submitLabel: 'Hide',
    },
};

/**
 * A business's outlets, each linking to its admin page, with a menu of the review, suspension, visibility and
 * archive actions for each one.
 */
export default function OutletsTable({ outlets }: Props) {
    const [reasonFor, setReasonFor] = useState<{
        outlet: App.Data.Admin.OutletData;
        action: ReasonAction;
    } | null>(null);

    if (outlets.length === 0) {
        return (
            <Empty className="border p-6">
                <EmptyHeader>
                    <EmptyMedia variant="icon">
                        <Store />
                    </EmptyMedia>
                    <EmptyTitle>No outlets yet</EmptyTitle>
                </EmptyHeader>
            </Empty>
        );
    }

    const dialog = reasonFor && reasonDialogs[reasonFor.action];

    return (
        <>
            <div className="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Outlet</TableHead>
                            <TableHead>City</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Inside</TableHead>
                            <TableHead className="w-12">
                                <span className="sr-only">Actions</span>
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {outlets.map((outlet) => (
                            <TableRow key={outlet.id}>
                                <TableCell className="font-medium">
                                    <Link
                                        href={show(outlet.id)}
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
                                <TableCell className="text-right">
                                    <OutletMenu
                                        outlet={outlet}
                                        askReason={(action) =>
                                            setReasonFor({ outlet, action })
                                        }
                                    />
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>

            {reasonFor && dialog && (
                <ReasonDialog
                    key={`${reasonFor.outlet.id}-${reasonFor.action}`}
                    open
                    onOpenChange={(open) => !open && setReasonFor(null)}
                    title={dialog.title(reasonFor.outlet)}
                    description={dialog.description}
                    form={dialog.route.form(reasonFor.outlet.id)}
                    submitLabel={dialog.submitLabel}
                    destructive
                />
            )}
        </>
    );
}

/**
 * The actions an outlet's state allows, the same ones as on its admin page.
 */
function OutletMenu({
    outlet,
    askReason,
}: {
    outlet: App.Data.Admin.OutletData;
    askReason: (action: ReasonAction) => void;
}) {
    const post = (route: RouteDefinition<'post'>) =>
        router.visit(route, { preserveScroll: true });

    return (
        <DropdownMenu>
            <DropdownMenuTrigger
                render={
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        aria-label={`Actions for ${outlet.name}`}
                    />
                }
            >
                <EllipsisVertical />
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="min-w-40">
                <DropdownMenuItem render={<Link href={show(outlet.id)} />}>
                    View outlet
                </DropdownMenuItem>

                <DropdownMenuSeparator />

                {outlet.onboarding_status === 'pending' && (
                    <>
                        <DropdownMenuItem
                            onClick={() => post(approve(outlet.id))}
                        >
                            Approve
                        </DropdownMenuItem>
                        <DropdownMenuItem onClick={() => askReason('reject')}>
                            Reject…
                        </DropdownMenuItem>
                    </>
                )}

                {outlet.archived_at ? (
                    <DropdownMenuItem onClick={() => post(restore(outlet.id))}>
                        Restore
                    </DropdownMenuItem>
                ) : (
                    <>
                        {outlet.suspended_at ? (
                            <DropdownMenuItem
                                onClick={() => post(reactivate(outlet.id))}
                            >
                                Reactivate
                            </DropdownMenuItem>
                        ) : (
                            <DropdownMenuItem
                                variant="destructive"
                                onClick={() => askReason('suspend')}
                            >
                                Suspend…
                            </DropdownMenuItem>
                        )}
                        {outlet.hidden_at ? (
                            <DropdownMenuItem
                                onClick={() => post(unhide(outlet.id))}
                            >
                                Unhide
                            </DropdownMenuItem>
                        ) : (
                            <DropdownMenuItem onClick={() => askReason('hide')}>
                                Hide…
                            </DropdownMenuItem>
                        )}
                        <DropdownMenuItem
                            onClick={() => post(archive(outlet.id))}
                        >
                            Archive
                        </DropdownMenuItem>
                    </>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
