import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

export type Status =
    | App.Enums.OnboardingStatus
    | App.Enums.InvitationStatus
    | App.Enums.TagStatus
    | 'active'
    | 'suspended'
    | 'archived'
    | 'unreviewed'
    | 'reviewed'
    | 'reverted'
    | 'paused'
    | 'scheduled'
    | 'ended'
    | 'hidden';

const tones = {
    neutral: 'border-transparent bg-secondary text-secondary-foreground',
    waiting:
        'border-transparent bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
    good: 'border-transparent bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
    bad: 'border-transparent bg-red-100 text-red-800 dark:bg-red-500/15 dark:text-red-300',
    closed: 'text-muted-foreground',
} as const;

const statuses: Record<Status, { label: string; tone: keyof typeof tones }> = {
    draft: { label: 'Draft', tone: 'neutral' },
    pending: { label: 'Pending', tone: 'waiting' },
    approved: { label: 'Approved', tone: 'good' },
    rejected: { label: 'Rejected', tone: 'bad' },
    accepted: { label: 'Accepted', tone: 'good' },
    declined: { label: 'Declined', tone: 'closed' },
    cancelled: { label: 'Cancelled', tone: 'closed' },
    expired: { label: 'Expired', tone: 'closed' },
    active: { label: 'Active', tone: 'good' },
    suspended: { label: 'Suspended', tone: 'bad' },
    archived: { label: 'Archived', tone: 'closed' },
    unreviewed: { label: 'To review', tone: 'waiting' },
    reviewed: { label: 'Reviewed', tone: 'good' },
    reverted: { label: 'Reverted', tone: 'bad' },
    paused: { label: 'Paused', tone: 'waiting' },
    scheduled: { label: 'Scheduled', tone: 'waiting' },
    ended: { label: 'Ended', tone: 'closed' },
    hidden: { label: 'Hidden', tone: 'bad' },
};

/**
 * One label for an onboarding, invitation, tag review, change review, membership, suspension or offer state.
 */
export default function StatusBadge({
    status,
    className,
}: {
    status: Status;
    className?: string;
}) {
    const { label, tone } = statuses[status];

    return (
        <Badge variant="outline" className={cn(tones[tone], className)}>
            {label}
        </Badge>
    );
}

type RecordWithStatus = {
    onboarding_status: App.Enums.OnboardingStatus;
    is_suspended?: boolean;
    suspended_at?: string | null;
    archived_at?: string | null;
};

/**
 * The state that matters most for a business or outlet: suspended, then archived, then its onboarding status.
 */
export function recordStatus(record: RecordWithStatus): Status {
    if (record.is_suspended || record.suspended_at) {
        return 'suspended';
    }

    if (record.archived_at) {
        return 'archived';
    }

    return record.onboarding_status;
}
