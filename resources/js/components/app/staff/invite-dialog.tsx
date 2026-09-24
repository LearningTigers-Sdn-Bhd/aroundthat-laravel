import { UserPlus } from 'lucide-react';
import RoleAndOutletsFields from '@/components/app/staff/role-and-outlets-fields';
import FormDialog from '@/components/form-dialog';
import TextField from '@/components/text-field';
import { Button } from '@/components/ui/button';
import { store as invite } from '@/routes/staff/invitations';

type Props = {
    roles: App.Enums.MembershipRole[];
    outletOptions: App.Data.OutletOptionData[];
};

/**
 * Invites someone by email to join the business with a role.
 */
export default function InviteDialog({ roles, outletOptions }: Props) {
    return (
        <FormDialog
            trigger={
                <Button>
                    <UserPlus />
                    Invite
                </Button>
            }
            title="Invite staff"
            description="We email them a link to join. It works for 14 days."
            form={invite.form()}
            submitLabel="Send invitation"
        >
            {(errors) => (
                <>
                    <TextField
                        name="email"
                        label="Email"
                        type="email"
                        error={errors.email}
                        required
                    />
                    <RoleAndOutletsFields
                        roles={roles}
                        outletOptions={outletOptions}
                        defaultRole="cashier"
                        defaultOutletIds={[]}
                        errors={errors}
                    />
                </>
            )}
        </FormDialog>
    );
}
