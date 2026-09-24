import { Link, usePage } from '@inertiajs/react';
import {
    Briefcase,
    Building2,
    History,
    LayoutGrid,
    Settings,
    Shapes,
    ShieldCheck,
    Store,
    Tags,
    UserCog,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { BusinessSwitcher } from '@/components/business-switcher';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { dashboard as adminDashboard } from '@/routes/admin';
import { edit as businessDetails } from '@/routes/business';
import { index as outlets } from '@/routes/outlets';
import { index as staff } from '@/routes/staff';
import { index as adminBusinesses } from '@/routes/admin/businesses';
import { index as adminCategories } from '@/routes/admin/categories';
import { index as adminChanges } from '@/routes/admin/changes';
import { edit as adminSettings } from '@/routes/admin/settings';
import { index as adminTags } from '@/routes/admin/tags';
import { index as adminUsers } from '@/routes/admin/users';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const { auth, workspace } = usePage().props;

    const can = (ability: App.Enums.Ability) =>
        workspace?.abilities.includes(ability) ?? false;

    const mainNavItems: NavItem[] = workspace
        ? [
              { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
              ...(can('manage_business')
                  ? [
                        {
                            title: 'Business',
                            href: businessDetails(),
                            icon: Briefcase,
                        },
                    ]
                  : []),
              ...(can('manage_outlets')
                  ? [
                        {
                            title: 'Outlets',
                            href: outlets(),
                            icon: Store,
                            matchChildren: true,
                        },
                    ]
                  : []),
              ...(can('manage_staff')
                  ? [{ title: 'Staff', href: staff(), icon: UserCog }]
                  : []),
          ]
        : [];

    const adminNavItems: NavItem[] = auth.user.is_admin
        ? [
              { title: 'Review', href: adminDashboard(), icon: ShieldCheck },
              { title: 'Changes', href: adminChanges(), icon: History },
              {
                  title: 'Businesses',
                  href: adminBusinesses(),
                  icon: Building2,
                  matchChildren: true,
              },
              {
                  title: 'Users',
                  href: adminUsers(),
                  icon: Users,
                  matchChildren: true,
              },
              {
                  title: 'Categories',
                  href: adminCategories(),
                  icon: Shapes,
              },
              { title: 'Tags', href: adminTags(), icon: Tags },
              { title: 'Settings', href: adminSettings(), icon: Settings },
          ]
        : [];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            size="lg"
                            render={<Link href={dashboard()} prefetch />}
                        >
                            <AppLogo />
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                <BusinessSwitcher />
            </SidebarHeader>

            <SidebarContent>
                {mainNavItems.length > 0 && <NavMain items={mainNavItems} />}
                {adminNavItems.length > 0 && (
                    <NavMain items={adminNavItems} label="Admin" />
                )}
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
