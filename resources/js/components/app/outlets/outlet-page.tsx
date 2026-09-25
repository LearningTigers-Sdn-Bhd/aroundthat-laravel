import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import ActionButton from '@/components/action-button';
import RevertNotices from '@/components/app/revert-notices';
import Notice from '@/components/notice';
import StatusBadge, { recordStatus } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import HeaderTabLayout from '@/layouts/header-tab-layout';
import { formatDateTime } from '@/lib/format';
import { archive, edit, preview, restore, submit } from '@/routes/outlets';
import { edit as editHours } from '@/routes/outlets/hours';
import { edit as editLinks } from '@/routes/outlets/links';
import { edit as editLocation } from '@/routes/outlets/location';
import { index as photos } from '@/routes/outlets/photos';
import { edit as editPublic } from '@/routes/outlets/public';
import type { NavItem } from '@/types';

type Props = {
    outlet: App.Data.OutletData;
    can: { archive: boolean; submit: boolean };
    recentReverts: App.Data.RevertNoticeData[];
    children: ReactNode;
};

/**
 * Every outlet tab: its name and state, archive and review actions, and the tabs. Each tab saves one form, so no
 * field is edited in two places.
 */
export default function OutletPage({
    outlet,
    can,
    recentReverts,
    children,
}: Props) {
    const { workspace } = usePage().props;

    const canManagePublicContent =
        workspace?.abilities.includes('manage_public_content') ?? false;

    const tabs: NavItem[] = [
        { title: 'Details', href: edit(outlet.id) },
        ...(canManagePublicContent
            ? [
                  { title: 'Public page', href: editPublic(outlet.id) },
                  { title: 'Location', href: editLocation(outlet.id) },
                  { title: 'Social links', href: editLinks(outlet.id) },
                  { title: 'Hours', href: editHours(outlet.id) },
                  { title: 'Photos', href: photos(outlet.id) },
                  { title: 'Preview', href: preview(outlet.id) },
              ]
            : []),
    ];

    return (
        <HeaderTabLayout
            title={outlet.name}
            badges={
                <>
                    <StatusBadge status={recordStatus(outlet)} />
                    {outlet.is_public && (
                        <Badge variant="secondary">Listed</Badge>
                    )}
                </>
            }
            description={
                outlet.host_outlet
                    ? `Inside ${outlet.host_outlet.name}`
                    : 'Changes go live when you save them.'
            }
            actions={
                can.archive && (
                    <ActionButton
                        form={
                            outlet.archived_at
                                ? restore.form(outlet.id)
                                : archive.form(outlet.id)
                        }
                        variant="outline"
                    >
                        {outlet.archived_at ? 'Restore' : 'Archive'}
                    </ActionButton>
                )
            }
            tabs={tabs}
            tabsLabel="Outlet sections"
        >
            <ReviewBanner outlet={outlet} canSubmit={can.submit} />

            {outlet.hidden_reason && (
                <Notice title="Hidden by an admin">
                    {outlet.hidden_reason} The outlet keeps trading, but
                    visitors cannot find it until an admin shows it again.
                </Notice>
            )}

            <RevertNotices reverts={recentReverts} />

            {children}
        </HeaderTabLayout>
    );
}

/**
 * Why the outlet cannot be changed, or where it is in admin review and the button to send it there.
 */
function ReviewBanner({
    outlet,
    canSubmit,
}: {
    outlet: App.Data.OutletData;
    canSubmit: boolean;
}) {
    if (outlet.is_suspended) {
        return (
            <Notice title="Suspended">
                An admin suspended this outlet. Contact support to have it
                reactivated.
            </Notice>
        );
    }

    if (outlet.archived_at) {
        return (
            <p className="rounded-md border p-4 text-sm text-muted-foreground">
                Archived {formatDateTime(outlet.archived_at)}. Restore it to
                make changes.
            </p>
        );
    }

    if (outlet.onboarding_status === 'pending') {
        return (
            <p className="rounded-md border p-4 text-sm text-muted-foreground">
                Submitted for review {formatDateTime(outlet.submitted_at)}. You
                can change the details again once an admin has reviewed them.
            </p>
        );
    }

    if (outlet.onboarding_status === 'approved') {
        return null;
    }

    return (
        <>
            {outlet.onboarding_status === 'rejected' && (
                <Notice title="Rejected">{outlet.rejection_reason}</Notice>
            )}
            <div className="flex flex-wrap items-center justify-between gap-4 rounded-md border p-4">
                <p className="text-sm text-muted-foreground">
                    {canSubmit
                        ? 'When the details are complete, submit the outlet for an admin to review.'
                        : 'You can submit outlets for review once an admin has approved your business.'}
                </p>
                {canSubmit && (
                    <ActionButton form={submit.form(outlet.id)}>
                        Submit for review
                    </ActionButton>
                )}
            </div>
        </>
    );
}
