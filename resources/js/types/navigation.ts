import type { InertiaLinkProps } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import type { LinkConfirmation } from '@/components/confirm-link';

export type BreadcrumbItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
};

export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
    /** Also highlight the item on pages below its URL. */
    matchChildren?: boolean;
    /** Ask the user to confirm before the link visits. */
    confirm?: LinkConfirmation;
};
