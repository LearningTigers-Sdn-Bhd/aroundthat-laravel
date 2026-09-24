import ActionButton from '@/components/action-button';
import RoleAndOutletsFields from '@/components/app/staff/role-and-outlets-fields';
import FormDialog from '@/components/form-dialog';
import ReasonDialog from '@/components/reason-dialog';
import { Button } from '@/components/ui/button';
import { destroy, reactivate, suspend, update } from '@/routes/staff';

type Props = {
    member: App.Data.MemberData;
    roles: App.Enums.MembershipRole[];
    outletOptions: App.Data.OutletOptionData[];
};

/**
 * Change access, suspend or reactivate, and remove one member.
 */
export default function MemberActions({ member, roles, outletOptions }: Props) {
    return (
        <div className="flex justify-end gap-2">
            <FormDialog
                trigger={
                    <Button variant="outline" size="sm">
                        Change access
                    </Button>
                }
                title={`Change ${member.name}'s access`}
                form={update.form(member.id)}
                submitLabel="Save"
            >
                {(errors) => (
                    <RoleAndOutletsFields
                        roles={roles}
                        outletOptions={outletOptions}
                        defaultRole={member.role}
                        defaultOutletIds={member.outlets.map(
                            (outlet) => outlet.id,
                        )}
                        errors={errors}
                    />
                )}
            </FormDialog>

            {member.suspended_at ? (
                <ActionButton
                    form={reactivate.form(member.id)}
                    variant="outline"
                    size="sm"
                >
                    Reactivate
                </ActionButton>
            ) : (
                <ReasonDialog
                    trigger={
                        <Button variant="outline" size="sm">
                            Suspend
                        </Button>
                    }
                    title={`Suspend ${member.name}?`}
                    description="They keep their login but cannot work in this business until you reactivate them."
                    form={suspend.form(member.id)}
                    submitLabel="Suspend"
                    destructive
                />
            )}

            <FormDialog
                trigger={
                    <Button variant="ghost" size="sm">
                        Remove
                    </Button>
                }
                title={`Remove ${member.name}?`}
                description="They lose access to this business. Invite them again to bring them back."
                form={destroy.form(member.id)}
                submitLabel="Remove"
                destructive
            />
        </div>
    );
}
