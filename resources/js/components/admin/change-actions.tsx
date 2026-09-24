import ActionButton from '@/components/action-button';
import { review } from '@/routes/admin/changes';

/**
 * What an admin can do with an owner's content change. Other activity gets no buttons.
 */
export default function ChangeActions({
    activity,
}: {
    activity: App.Data.Admin.ActivityData;
}) {
    if (activity.log_name !== 'content' || activity.reviewed_at) {
        return null;
    }

    return (
        <ActionButton
            form={review.form(activity.id)}
            size="sm"
            variant="outline"
        >
            Mark reviewed
        </ActionButton>
    );
}
