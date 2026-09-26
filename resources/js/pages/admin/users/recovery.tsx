import { Head, setLayoutProps, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import UserPage from '@/components/admin/users/user-page';
import ConfirmDialog from '@/components/confirm-dialog';
import FormDialog from '@/components/form-dialog';
import Heading from '@/components/heading';
import Notice from '@/components/notice';
import TextField from '@/components/text-field';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes/admin';
import { index, show } from '@/routes/admin/users';
import {
    index as recoveryIndex,
    passwordReset,
    temporaryPassword,
    twoFactorReset,
} from '@/routes/admin/users/recovery';

type Props = {
    user: App.Data.Admin.UserData;
};

/**
 * One recovery step: what it does, and the button that starts it.
 */
function RecoveryStep({
    title,
    description,
    action,
}: {
    title: string;
    description: string;
    action: ReactNode;
}) {
    return (
        <div className="flex flex-wrap items-center justify-between gap-4 p-4">
            <div className="max-w-xl space-y-1">
                <p className="text-sm font-medium">{title}</p>
                <p className="text-sm text-muted-foreground">{description}</p>
            </div>
            {action}
        </div>
    );
}

export default function UserRecovery({ user }: Props) {
    const { auth } = usePage().props;

    setLayoutProps({
        breadcrumbs: [
            { title: 'Admin', href: dashboard() },
            { title: 'Users', href: index() },
            { title: user.name, href: show(user.id) },
            { title: 'Recovery', href: recoveryIndex(user.id) },
        ],
    });

    const isOwnLogin = auth.user?.id === user.id;
    const isLocked = isOwnLogin || user.suspended_at !== null;

    return (
        <>
            <Head title={`${user.name} recovery`} />

            <UserPage user={user} errorsShownInForms={['password']}>
                <Heading
                    variant="small"
                    title="Recovery"
                    description="Help this user back into their login when they are locked out. Each step is logged in Activity."
                />

                {isOwnLogin && (
                    <Notice title="This is your login">
                        Change your own password and two-factor authentication
                        from Settings.
                    </Notice>
                )}
                {!isOwnLogin && user.suspended_at && (
                    <Notice title="Suspended">
                        Reactivate this login before recovering it.
                    </Notice>
                )}

                <div className="divide-y rounded-md border">
                    <RecoveryStep
                        title="Send a password reset email"
                        description={`Emails ${user.email} a link to choose a new password. Use this first when they can still read that inbox.`}
                        action={
                            <ConfirmDialog
                                trigger={
                                    <Button
                                        variant="outline"
                                        disabled={isLocked}
                                    >
                                        Send email
                                    </Button>
                                }
                                title={`Email ${user.name} a reset link?`}
                                description={`The link goes to ${user.email} and expires after a while. Their current password keeps working until they use it.`}
                                form={passwordReset.form(user.id)}
                                confirmLabel="Send email"
                            />
                        }
                    />

                    <RecoveryStep
                        title="Set a temporary password"
                        description="Replaces their password with one you pass on to them, such as by phone. They are signed out everywhere and must choose a new password at their next login."
                        action={
                            <FormDialog
                                trigger={
                                    <Button
                                        variant="outline"
                                        disabled={isLocked}
                                    >
                                        Set password
                                    </Button>
                                }
                                title={`Set a temporary password for ${user.name}?`}
                                description="Their current password stops working now. Share the new one privately; it is never shown again."
                                form={temporaryPassword.form(user.id)}
                                submitLabel="Set password"
                                destructive
                            >
                                {(errors) => (
                                    <TextField
                                        name="password"
                                        label="Temporary password"
                                        type="text"
                                        autoComplete="off"
                                        error={errors.password}
                                        required
                                    />
                                )}
                            </FormDialog>
                        }
                    />

                    <RecoveryStep
                        title="Turn off two-factor authentication"
                        description={
                            user.has_two_factor
                                ? 'For a user who lost their authenticator app and recovery codes. They can log in with their password alone and set it up again from Settings.'
                                : 'Two-factor authentication is off for this login.'
                        }
                        action={
                            <ConfirmDialog
                                trigger={
                                    <Button
                                        variant="outline"
                                        disabled={
                                            isLocked || !user.has_two_factor
                                        }
                                    >
                                        Turn off
                                    </Button>
                                }
                                title={`Turn off two-factor authentication for ${user.name}?`}
                                description="Only do this after confirming who is asking. Their login is then protected by the password alone."
                                form={twoFactorReset.form(user.id)}
                                confirmLabel="Turn off"
                                destructive
                            />
                        }
                    />
                </div>
            </UserPage>
        </>
    );
}
