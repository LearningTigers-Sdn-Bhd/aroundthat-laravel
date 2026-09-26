import { Head, usePage } from '@inertiajs/react';
import { Users } from 'lucide-react';
import InviteDialog from '@/components/app/staff/invite-dialog';
import MemberActions from '@/components/app/staff/member-actions';
import DataTable from '@/components/data-table';
import Heading from '@/components/heading';
import InvitationsTable from '@/components/invitations-table';
import { memberColumns } from '@/components/members-table';
import PageErrors from '@/components/page-errors';
import { humanize } from '@/lib/format';
import { index } from '@/routes/staff';
import { destroy, resend } from '@/routes/staff/invitations';

type Props = {
    members: Illuminate.LengthAwarePaginator<number, App.Data.MemberData>;
    invitations: App.Data.InvitationData[];
    outletOptions: App.Data.OutletOptionData[];
    roles: App.Enums.MembershipRole[];
    canInvite: boolean;
};

export default function StaffIndex({
    members,
    invitations,
    outletOptions,
    roles,
    canInvite,
}: Props) {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Staff" />

            <div className="flex flex-1 flex-col gap-8 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Staff"
                        description="Who works in this business, what they can do, and at which outlets."
                    />
                    {canInvite && (
                        <InviteDialog
                            roles={roles}
                            outletOptions={outletOptions}
                        />
                    )}
                </div>

                <PageErrors
                    except={['email', 'role', 'outlet_ids', 'reason']}
                />

                <DataTable
                    rows={members}
                    columns={memberColumns((member) =>
                        member.user_id === auth.user.id ? (
                            <p className="text-muted-foreground">You</p>
                        ) : (
                            <MemberActions
                                member={member}
                                roles={roles}
                                outletOptions={outletOptions}
                            />
                        ),
                    )}
                    rowKey={(member) => member.id}
                    searchPlaceholder="Search name or email"
                    filters={[
                        {
                            name: 'role',
                            label: 'Roles',
                            options: roles.map((role) => ({
                                value: role,
                                label: humanize(role),
                            })),
                        },
                        {
                            name: 'status',
                            label: 'Statuses',
                            options: [
                                { value: 'active', label: 'Active' },
                                { value: 'suspended', label: 'Suspended' },
                            ],
                        },
                    ]}
                    emptyTitle="No members yet"
                    emptyIcon={Users}
                />

                {invitations.length > 0 && (
                    <section className="space-y-3">
                        <Heading variant="small" title="Open invitations" />
                        <InvitationsTable
                            invitations={invitations}
                            resend={resend}
                            cancel={destroy}
                            showOutlets
                        />
                    </section>
                )}
            </div>
        </>
    );
}

StaffIndex.layout = {
    breadcrumbs: [{ title: 'Staff', href: index() }],
};
