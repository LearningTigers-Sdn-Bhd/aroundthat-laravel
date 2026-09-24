import ActionButton from '@/components/action-button';
import ReasonDialog from '@/components/reason-dialog';
import { Button } from '@/components/ui/button';
import {
    approve,
    archive,
    hide,
    reactivate,
    reject,
    restore,
    suspend,
    unhide,
} from '@/routes/admin/outlets';

type Props = {
    outlet: App.Data.Admin.OutletData;
};

/**
 * Approve or reject a pending outlet, suspend or reactivate it, hide it from the public listing or show it again,
 * and archive or restore it.
 */
export default function OutletActions({ outlet }: Props) {
    return (
        <div className="flex flex-wrap gap-2">
            {outlet.onboarding_status === 'pending' && (
                <>
                    <ActionButton form={approve.form(outlet.id)}>
                        Approve
                    </ActionButton>
                    <ReasonDialog
                        trigger={<Button variant="outline">Reject</Button>}
                        title={`Reject ${outlet.name}?`}
                        description="The owner sees this reason, fixes the details and submits again."
                        form={reject.form(outlet.id)}
                        submitLabel="Reject"
                        destructive
                    />
                </>
            )}

            {outlet.archived_at ? (
                <ActionButton form={restore.form(outlet.id)} variant="outline">
                    Restore
                </ActionButton>
            ) : (
                <>
                    {outlet.suspended_at ? (
                        <ActionButton
                            form={reactivate.form(outlet.id)}
                            variant="outline"
                        >
                            Reactivate
                        </ActionButton>
                    ) : (
                        <ReasonDialog
                            trigger={
                                <Button variant="destructive">Suspend</Button>
                            }
                            title={`Suspend ${outlet.name}?`}
                            description="The outlet stops trading until it is reactivated."
                            form={suspend.form(outlet.id)}
                            submitLabel="Suspend"
                            destructive
                        />
                    )}
                    {outlet.hidden_at ? (
                        <ActionButton
                            form={unhide.form(outlet.id)}
                            variant="outline"
                        >
                            Unhide
                        </ActionButton>
                    ) : (
                        <ReasonDialog
                            trigger={<Button variant="outline">Hide</Button>}
                            title={`Hide ${outlet.name} from visitors?`}
                            description="The outlet keeps trading, but visitors cannot find it and the owners cannot list it again until you unhide it. The owners get an email with your reason."
                            form={hide.form(outlet.id)}
                            submitLabel="Hide"
                            destructive
                        />
                    )}
                    <ActionButton
                        form={archive.form(outlet.id)}
                        variant="ghost"
                    >
                        Archive
                    </ActionButton>
                </>
            )}
        </div>
    );
}
