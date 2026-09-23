import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
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

                <Button variant="outline" className="w-full" asChild>
                    <Link href={logout()} as="button">
                        Log out
                    </Link>
                </Button>
            </div>
        </>
    );
}

NoWorkspace.layout = {
    title: 'No business yet',
    description: 'Your account is not linked to a business.',
};
