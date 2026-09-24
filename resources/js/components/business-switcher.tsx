import { router, usePage } from '@inertiajs/react';
import { Building2, Check, ChevronsUpDown } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { update } from '@/routes/workspace';

export function BusinessSwitcher() {
    const { workspace, workspaces } = usePage().props;
    const { isMobile } = useSidebar();

    if (!workspace) {
        return null;
    }

    const canSwitch = workspaces.length > 1;

    const trigger = (
        <SidebarMenuButton
            size="lg"
            className="data-popup-open:bg-sidebar-accent"
        >
            <div className="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
                <Building2 className="size-4" />
            </div>
            <div className="grid flex-1 text-left text-sm leading-tight">
                <span className="truncate font-semibold">
                    {workspace.business_name}
                </span>
                <span className="truncate text-xs text-muted-foreground capitalize">
                    {workspace.role}
                </span>
            </div>
            {canSwitch && <ChevronsUpDown className="ml-auto size-4" />}
        </SidebarMenuButton>
    );

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                {canSwitch ? (
                    <DropdownMenu>
                        <DropdownMenuTrigger render={trigger} />
                        <DropdownMenuContent
                            className="w-(--anchor-width) min-w-56"
                            align="start"
                            side={isMobile ? 'bottom' : 'right'}
                        >
                            <DropdownMenuLabel className="text-xs text-muted-foreground">
                                Businesses
                            </DropdownMenuLabel>
                            {workspaces.map((option) => (
                                <DropdownMenuItem
                                    key={option.business_id}
                                    onClick={() =>
                                        router.visit(update(), {
                                            data: {
                                                business_id: option.business_id,
                                            },
                                        })
                                    }
                                >
                                    <span className="flex-1 truncate">
                                        {option.business_name}
                                    </span>
                                    {option.business_id ===
                                        workspace.business_id && (
                                        <Check className="size-4" />
                                    )}
                                </DropdownMenuItem>
                            ))}
                        </DropdownMenuContent>
                    </DropdownMenu>
                ) : (
                    trigger
                )}
            </SidebarMenuItem>
        </SidebarMenu>
    );
}
