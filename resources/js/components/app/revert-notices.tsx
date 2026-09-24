import { eventLabel } from '@/components/activity-timeline';
import Notice from '@/components/notice';
import { formatDateTime } from '@/lib/format';

/**
 * Changes an admin reverted lately, with their reason, so owners know why a value went back.
 */
export default function RevertNotices({
    reverts,
}: {
    reverts: App.Data.RevertNoticeData[];
}) {
    return reverts.map((revert, index) => (
        <Notice
            key={`${revert.event}-${revert.reverted_at ?? index}`}
            title={`An admin reverted a change: ${eventLabel(revert.event).toLowerCase()}`}
            tone="info"
        >
            {revert.reverted_at && `${formatDateTime(revert.reverted_at)}. `}
            {revert.reason}
        </Notice>
    ));
}
