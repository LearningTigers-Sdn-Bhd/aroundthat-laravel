import { Link } from '@inertiajs/react';
import ConfirmLink from '@/components/confirm-link';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import type { NavItem } from '@/types';

export function NavMain({
    items,
    label = 'Platform',
}: {
    items: NavItem[];
    label?: string;
}) {
    const { isCurrentUrl } = useCurrentUrl();

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel>{label}</SidebarGroupLabel>
            <SidebarMenu>
                {items.map((item) => (
                    <SidebarMenuItem key={item.title}>
                        <SidebarMenuButton
                            isActive={isCurrentUrl(
                                item.href,
                                undefined,
                                item.matchChildren,
                            )}
                            tooltip={{ children: item.title }}
                            render={
                                item.confirm ? (
                                    <ConfirmLink
                                        href={item.href}
                                        confirmation={item.confirm}
                                        prefetch
                                    />
                                ) : (
                                    <Link href={item.href} prefetch />
                                )
                            }
                        >
                            {item.icon && <item.icon />}
                            <span>{item.title}</span>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                ))}
            </SidebarMenu>
        </SidebarGroup>
    );
}
