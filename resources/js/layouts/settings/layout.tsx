import { usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import Heading from '@/components/heading';
import ButtonLink from '@/components/button-link';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn, toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editImages } from '@/routes/images';
import { edit } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Profile',
        href: edit(),
        icon: null,
    },
    {
        title: 'Security',
        href: editSecurity(),
        icon: null,
    },
    {
        title: 'Appearance',
        href: editAppearance(),
        icon: null,
    },
];

const adminNavItems: NavItem[] = [
    {
        title: 'Image Configuration',
        href: editImages(),
        icon: null,
    },
];

export default function SettingsLayout({ children }: PropsWithChildren) {
    const { auth } = usePage().props;
    const { isCurrentOrParentUrl } = useCurrentUrl();

    const navItems = auth.user.is_admin
        ? [...sidebarNavItems, ...adminNavItems]
        : sidebarNavItems;

    return (
        <div className="px-4 py-6">
            <Heading
                title="Settings"
                description="Manage your profile and account settings"
            />

            <div className="flex flex-col lg:flex-row lg:space-x-12">
                <aside className="w-full max-w-xl lg:w-48">
                    <nav
                        className="flex flex-col space-y-1 space-x-0"
                        aria-label="Settings"
                    >
                        {navItems.map((item, index) => (
                            <ButtonLink
                                key={`${toUrl(item.href)}-${index}`}
                                size="sm"
                                variant="ghost"
                                className={cn('w-full justify-start', {
                                    'bg-muted': isCurrentOrParentUrl(item.href),
                                })}
                                href={item.href}
                            >
                                {item.icon && <item.icon className="h-4 w-4" />}
                                {item.title}
                            </ButtonLink>
                        ))}
                    </nav>
                </aside>

                <Separator className="my-6 lg:hidden" />

                <div className="flex-1 md:max-w-2xl">
                    <section className="max-w-xl space-y-12">
                        {children}
                    </section>
                </div>
            </div>
        </div>
    );
}
