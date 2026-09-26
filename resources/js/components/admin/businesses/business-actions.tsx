import ActionButton from '@/components/action-button';
import ReasonDialog from '@/components/reason-dialog';
import { Button } from '@/components/ui/button';
import {
    approve,
    reactivate,
    reject,
    suspend,
} from '@/routes/admin/businesses';

type Props = {
    business: App.Data.Admin.BusinessData;
};

/**
 * Approve or reject a pending business, and suspend or reactivate it.
 */
export default function BusinessActions({ business }: Props) {
    return (
        <div className="flex flex-wrap gap-2">
            {business.onboarding_status === 'pending' && (
                <>
                    <ActionButton form={approve.form(business.id)}>
                        Approve
                    </ActionButton>
                    <ReasonDialog
                        trigger={<Button variant="outline">Reject</Button>}
                        title={`Reject ${business.name}?`}
                        description="The owner sees this reason, fixes the details and submits again."
                        form={reject.form(business.id)}
                        submitLabel="Reject"
                        destructive
                    />
                </>
            )}

            {business.suspended_at ? (
                <ActionButton
                    form={reactivate.form(business.id)}
                    variant="outline"
                >
                    Reactivate
                </ActionButton>
            ) : (
                <ReasonDialog
                    trigger={<Button variant="destructive">Suspend</Button>}
                    title={`Suspend ${business.name}?`}
                    description="Members keep their logins but cannot change the business or its outlets while it is suspended."
                    form={suspend.form(business.id)}
                    submitLabel="Suspend"
                    destructive
                />
            )}
        </div>
    );
}
