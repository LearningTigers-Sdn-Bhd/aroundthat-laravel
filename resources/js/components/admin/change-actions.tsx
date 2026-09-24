import ActionButton from '@/components/action-button';
import { eventLabel } from '@/components/activity-timeline';
import ReasonDialog from '@/components/reason-dialog';
import { Button } from '@/components/ui/button';
import { review, revert } from '@/routes/admin/changes';

/**
 * What an admin can do with an owner's content change: mark it reviewed, or revert it with a reason.
 * Other activity, and the reverts themselves, get no buttons. Pass `conflicts` when they are known, so a
 * change that cannot be reverted says why instead of offering the button.
 */
export default function ChangeActions({
    activity,
    conflicts = [],
}: {
    activity: App.Data.Admin.ActivityData;
    conflicts?: string[];
}) {
    if (
        activity.log_name !== 'content' ||
        activity.event === 'reverted' ||
        activity.reverted_at
    ) {
        return null;
    }

    return (
        <div className="space-y-1">
            <div className="flex flex-wrap items-center gap-1">
                {!activity.reviewed_at && (
                    <ActionButton
                        form={review.form(activity.id)}
                        size="sm"
                        variant="outline"
                    >
                        Mark reviewed
                    </ActionButton>
                )}
                <ReasonDialog
                    trigger={
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={conflicts.length > 0}
                        >
                            Revert
                        </Button>
                    }
                    title={`Revert “${eventLabel(activity.event)}”?`}
                    description="The old values go live again at once. The owners get an email with your reason."
                    form={revert.form(activity.id)}
                    submitLabel="Revert"
                    destructive
                />
            </div>
            {conflicts.length > 0 && (
                <ul className="max-w-64 text-xs text-muted-foreground">
                    {conflicts.map((conflict) => (
                        <li key={conflict}>{conflict}</li>
                    ))}
                </ul>
            )}
        </div>
    );
}
