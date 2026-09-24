import { Head } from '@inertiajs/react';
import ButtonLink from '@/components/button-link';
import { logout } from '@/routes';

export default function NoWorkspace() {
    return (
        <>
            <Head title="No business yet" />

            <div className="space-y-6 text-center text-sm text-muted-foreground">
                <p>
                    When a business adds you as staff, or an administrator
                    finishes onboarding your business, it will appear here.
                </p>

                <ButtonLink variant="outline" className="w-full" href={logout()} as="button">
                        Log out
                    </ButtonLink>
            </div>
        </>
    );
}

NoWorkspace.layout = {
    title: 'No business yet',
    description: 'Your account is not linked to a business.',
};
