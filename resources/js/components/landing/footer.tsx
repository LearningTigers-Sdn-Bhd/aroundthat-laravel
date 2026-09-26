import { Link } from '@inertiajs/react';

import AppLogoIcon from '@/components/app-logo-icon';
import {
    accessRequestEmail,
    accessRequestHref,
    appName,
    appTagline,
} from '@/lib/brand';
import { login } from '@/routes';

export function Footer() {
    return (
        <footer className="border-t border-white/10 bg-landing-inverse text-landing-inverse-foreground">
            <div className="mx-auto flex max-w-6xl flex-col gap-10 px-4 py-12 sm:px-6 md:flex-row md:justify-between">
                <div className="max-w-xs">
                    <a
                        href="#top"
                        className="flex items-center gap-2 font-heading font-semibold"
                    >
                        <AppLogoIcon alt="" className="size-8 rounded-lg" />
                        <span className="text-lg">{appName}</span>
                    </a>
                    <p className="mt-3 text-sm text-landing-inverse-muted">
                        {appTagline}
                    </p>
                </div>

                <div className="grid grid-cols-2 gap-10 text-sm">
                    <div>
                        <p className="font-semibold">Product</p>
                        <ul className="mt-3 space-y-2 text-landing-inverse-muted">
                            <li>
                                <a
                                    href="#how-it-works"
                                    className="transition-colors hover:text-landing-inverse-foreground"
                                >
                                    How it works
                                </a>
                            </li>
                            <li>
                                <a
                                    href="#features"
                                    className="transition-colors hover:text-landing-inverse-foreground"
                                >
                                    Features
                                </a>
                            </li>
                            <li>
                                <a
                                    href="#faq"
                                    className="transition-colors hover:text-landing-inverse-foreground"
                                >
                                    FAQ
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div>
                        <p className="font-semibold">Contact</p>
                        <ul className="mt-3 space-y-2 text-landing-inverse-muted">
                            <li>
                                <a
                                    href={accessRequestHref}
                                    className="transition-colors hover:text-landing-inverse-foreground"
                                >
                                    {accessRequestEmail}
                                </a>
                            </li>
                            <li>
                                <Link
                                    href={login()}
                                    className="transition-colors hover:text-landing-inverse-foreground"
                                >
                                    Log in
                                </Link>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div className="border-t border-white/10 py-6 text-center text-xs text-landing-inverse-muted">
                © {new Date().getFullYear()} {appName}. All rights reserved.
            </div>
        </footer>
    );
}
