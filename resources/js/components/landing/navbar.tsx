import { Link, usePage } from '@inertiajs/react';

import AppLogoIcon from '@/components/app-logo-icon';
import { appName } from '@/lib/brand';
import { dashboard, login } from '@/routes';

import { RequestAccessLink } from './request-access-link';

const sectionLinks = [
    { label: 'How it works', href: '#how-it-works' },
    { label: 'Features', href: '#features' },
    { label: 'FAQ', href: '#faq' },
];

export function Navbar() {
    const { auth } = usePage().props;

    return (
        <header className="sticky top-0 z-40 border-b border-white/10 bg-landing-inverse text-landing-inverse-foreground">
            <nav className="mx-auto flex h-16 max-w-6xl items-center gap-6 px-4 sm:px-6">
                <a
                    href="#top"
                    className="flex items-center gap-2 font-heading font-semibold"
                >
                    <AppLogoIcon alt="" className="size-8 rounded-lg" />
                    <span className="text-lg">{appName}</span>
                </a>

                <ul className="ml-4 hidden items-center gap-6 text-sm text-landing-inverse-muted md:flex">
                    {sectionLinks.map((link) => (
                        <li key={link.href}>
                            <a
                                href={link.href}
                                className="transition-colors hover:text-landing-inverse-foreground"
                            >
                                {link.label}
                            </a>
                        </li>
                    ))}
                </ul>

                <div className="ml-auto flex items-center gap-3">
                    {auth.user ? (
                        <Link
                            href={dashboard()}
                            className="text-sm font-medium text-landing-inverse-foreground transition-colors hover:opacity-80 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            Dashboard
                        </Link>
                    ) : (
                        <Link
                            href={login()}
                            className="text-sm font-medium text-landing-inverse-foreground transition-colors hover:opacity-80 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            Log in
                        </Link>
                    )}
                    <RequestAccessLink className="hidden px-4 py-2 sm:inline-flex" />
                </div>
            </nav>
        </header>
    );
}
