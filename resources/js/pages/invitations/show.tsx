import { Form, Head, usePage } from '@inertiajs/react';
import AlertError from '@/components/alert-error';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import ButtonLink from '@/components/button-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { home, login, logout } from '@/routes';
import { accept, decline } from '@/routes/invitations';

type Props = {
    token: string;
    invitation: App.Data.InvitationPreviewData;
    hasAccount: boolean;
    signedInEmail: string | null;
};

const closedMessages: Record<
    Exclude<App.Enums.InvitationStatus, 'pending'>,
    string
> = {
    accepted: 'This invitation has already been accepted.',
    declined: 'This invitation was declined.',
    cancelled: 'The business cancelled this invitation.',
    expired: 'This invitation has expired. Ask the business to send a new one.',
};

export default function ShowInvitation({
    token,
    invitation,
    hasAccount,
    signedInEmail,
}: Props) {
    const { errors } = usePage().props;
    const invitationError = (errors as Record<string, string>).invitation;

    return (
        <>
            <Head title={`Join ${invitation.business_name}`} />

            <div className="flex flex-col gap-6">
                <InvitationSummary invitation={invitation} />

                {invitationError && <AlertError errors={[invitationError]} />}

                {invitation.status !== 'pending' ? (
                    <ClosedInvitation
                        message={closedMessages[invitation.status]}
                    />
                ) : signedInEmail && signedInEmail !== invitation.email ? (
                    <WrongAccount
                        signedInEmail={signedInEmail}
                        invitedEmail={invitation.email}
                    />
                ) : hasAccount && !signedInEmail ? (
                    <LogInFirst token={token} email={invitation.email} />
                ) : signedInEmail ? (
                    <AcceptOrDecline token={token} />
                ) : (
                    <CreateAccountAndAccept token={token} />
                )}
            </div>
        </>
    );
}

function InvitationSummary({
    invitation,
}: {
    invitation: App.Data.InvitationPreviewData;
}) {
    return (
        <div className="space-y-2 text-center text-sm text-muted-foreground">
            <p>
                {invitation.inviter_name ?? 'The business'} invited{' '}
                <span className="font-medium text-foreground">
                    {invitation.email}
                </span>{' '}
                to join{' '}
                <span className="font-medium text-foreground">
                    {invitation.business_name}
                </span>{' '}
                as{' '}
                <span className="font-medium text-foreground capitalize">
                    {invitation.role}
                </span>
                .
            </p>
            {invitation.outlet_names.length > 0 && (
                <p>Outlets: {invitation.outlet_names.join(', ')}</p>
            )}
        </div>
    );
}

function ClosedInvitation({ message }: { message: string }) {
    return (
        <div className="space-y-6 text-center text-sm text-muted-foreground">
            <p>{message}</p>
            <ButtonLink variant="outline" className="w-full" href={home()}>
                Go to home page
            </ButtonLink>
        </div>
    );
}

function WrongAccount({
    signedInEmail,
    invitedEmail,
}: {
    signedInEmail: string;
    invitedEmail: string;
}) {
    return (
        <div className="space-y-6 text-center text-sm text-muted-foreground">
            <p>
                You are logged in as {signedInEmail}, but this invitation is for{' '}
                {invitedEmail}. Log out, then open the link again.
            </p>
            <ButtonLink
                variant="outline"
                className="w-full"
                href={logout()}
                as="button"
            >
                Log out
            </ButtonLink>
        </div>
    );
}

function LogInFirst({ token, email }: { token: string; email: string }) {
    return (
        <div className="flex flex-col gap-3">
            <p className="text-center text-sm text-muted-foreground">
                {email} already has a login. Log in with it to accept.
            </p>
            <ButtonLink className="w-full" href={login()}>
                Log in to accept
            </ButtonLink>
            <DeclineButton token={token} />
        </div>
    );
}

function AcceptOrDecline({ token }: { token: string }) {
    return (
        <div className="flex flex-col gap-3">
            <Form {...accept.form(token)}>
                {({ processing }) => (
                    <Button
                        type="submit"
                        className="w-full"
                        disabled={processing}
                    >
                        {processing && <Spinner />}
                        Accept invitation
                    </Button>
                )}
            </Form>
            <DeclineButton token={token} />
        </div>
    );
}

function CreateAccountAndAccept({ token }: { token: string }) {
    return (
        <div className="flex flex-col gap-3">
            <Form
                {...accept.form(token)}
                resetOnSuccess={['password', 'password_confirmation']}
                className="grid gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="name">Your name</Label>
                            <Input
                                id="name"
                                name="name"
                                required
                                autoFocus
                                autoComplete="name"
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password">Password</Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                required
                                autoComplete="new-password"
                            />
                            <InputError message={errors.password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password_confirmation">
                                Confirm password
                            </Label>
                            <PasswordInput
                                id="password_confirmation"
                                name="password_confirmation"
                                required
                                autoComplete="new-password"
                            />
                        </div>

                        <Button
                            type="submit"
                            className="w-full"
                            disabled={processing}
                        >
                            {processing && <Spinner />}
                            Create login and accept
                        </Button>
                    </>
                )}
            </Form>
            <DeclineButton token={token} />
        </div>
    );
}

function DeclineButton({ token }: { token: string }) {
    return (
        <Form {...decline.form(token)}>
            {({ processing }) => (
                <Button
                    type="submit"
                    variant="ghost"
                    className="w-full"
                    disabled={processing}
                >
                    Decline
                </Button>
            )}
        </Form>
    );
}

ShowInvitation.layout = {
    title: 'You are invited',
    description: 'Review the invitation below.',
};
