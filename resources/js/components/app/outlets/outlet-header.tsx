import { usePage } from '@inertiajs/react';
import ActionButton from '@/components/action-button';
import ButtonLink from '@/components/button-link';
import Notice from '@/components/notice';
import StatusBadge, { recordStatus } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { formatDateTime } from '@/lib/format';
import { cn } from '@/lib/utils';
import { archive, edit, restore, submit } from '@/routes/outlets';
import { edit as editPublic } from '@/routes/outlets/public';
import type { NavItem } from '@/types';

type Props = {
    outlet: App.Data.OutletData;
    can: { archive: boolean; submit: boolean };
};

/**
 * The top of every outlet tab: its name and state, archive and review actions, and the tabs. Each tab saves one form,
 * so no field is edited in two places.
 */
export default function OutletHeader({ outlet, can }: Props) {
    const { workspace } = usePage().props;
    const { isCurrentUrl } = useCurrentUrl();

    const canManagePublicContent =
        workspace?.abilities.includes('manage_public_content') ?? false;

    const tabs: NavItem[] = [
        { title: 'Details', href: edit(outlet.id) },
        ...(canManagePublicContent
            ? [{ title: 'Public page', href: editPublic(outlet.id) }]
            : []),
    ];

    return (
        <div className="space-y-6">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div className="space-y-1">
                    <div className="flex items-center gap-2">
                        <h1 className="text-xl font-semibold tracking-tight">
                            {outlet.name}
                        </h1>
                        <StatusBadge status={recordStatus(outlet)} />
                        {outlet.is_public && (
                            <Badge variant="secondary">Listed</Badge>
                        )}
                    </div>
                    <p className="text-sm text-muted-foreground">
                        {outlet.host_outlet
                            ? `Inside ${outlet.host_outlet.name}`
                            : 'Changes go live when you save them.'}
                    </p>
                </div>

                {can.archive && (
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
                )}
            </div>

            <ReviewBanner outlet={outlet} canSubmit={can.submit} />

            {tabs.length > 1 && (
                <nav
                    className="flex gap-1 overflow-x-auto border-b"
                    aria-label="Outlet sections"
                >
                    {tabs.map((tab) => (
                        <ButtonLink
                            key={tab.title}
                            href={tab.href}
                            variant="ghost"
                            size="sm"
                            className={cn(
                                '-mb-px rounded-b-none border-b-2 border-transparent',
                                isCurrentUrl(tab.href) &&
                                    'border-primary text-foreground',
                            )}
                        >
                            {tab.title}
                        </ButtonLink>
                    ))}
                </nav>
            )}
        </div>
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
