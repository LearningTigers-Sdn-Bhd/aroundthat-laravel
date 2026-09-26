import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn } from '@/lib/utils';
import type { NavItem } from '@/types';

type Props = {
    title: string;
    /** Shown beside the title, such as status badges. */
    badges?: ReactNode;
    description?: ReactNode;
    /** Buttons on the right of the header. */
    actions?: ReactNode;
    tabs?: NavItem[];
    tabsLabel?: string;
    /** Classes for the content wrapper, such as `lg:max-w-none` for content that sets its own width. */
    contentClassName?: string;
    children: ReactNode;
};

/**
 * A full-width page with a title, header buttons, and an optional row of tabs that each link to their own page. The
 * tabs show only when there is more than one.
 */
export default function HeaderTabLayout({
    title,
    badges,
    description,
    actions,
    tabs = [],
    tabsLabel,
    contentClassName,
    children,
}: Props) {
    const { isCurrentUrl } = useCurrentUrl();

    const currentTab =
        tabs.find((tab) => isCurrentUrl(tab.href))?.title ?? null;

    return (
        <div className="flex flex-1 flex-col gap-6 p-4">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div className="space-y-1">
                    <div className="flex items-center gap-2">
                        <h1 className="text-xl font-semibold tracking-tight">
                            {title}
                        </h1>
                        {badges}
                    </div>
                    {description && (
                        <p className="text-sm text-muted-foreground">
                            {description}
                        </p>
                    )}
                </div>

                {actions && (
                    <div className="flex flex-wrap items-center gap-2">
                        {actions}
                    </div>
                )}
            </div>

            {tabs.length > 1 && (
                <Tabs value={currentTab}>
                    <TabsList variant="line" aria-label={tabsLabel}>
                        {tabs.map((tab) => (
                            <TabsTrigger
                                key={tab.title}
                                value={tab.title}
                                nativeButton={false}
                                render={<Link href={tab.href} prefetch />}
                            >
                                {tab.icon && <tab.icon />}
                                {tab.title}
                            </TabsTrigger>
                        ))}
                    </TabsList>
                </Tabs>
            )}

            <div
                className={cn(
                    'flex w-full flex-col gap-6 lg:max-w-[80%]',
                    contentClassName,
                )}
            >
                {children}
            </div>
        </div>
    );
}
