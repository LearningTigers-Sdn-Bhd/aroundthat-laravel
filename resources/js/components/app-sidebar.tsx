import { Link, usePage } from '@inertiajs/react';
import {
    Briefcase,
    Building2,
    ChartNoAxesColumn,
    History,
    House,
    LayoutGrid,
    MapPinned,
    Plug,
    ScanLine,
    Settings,
    Shapes,
    Ticket,
    ShieldCheck,
    Store,
    Tags,
    TicketCheck,
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
import { dashboard, home } from '@/routes';
import { show as counter } from '@/routes/counter';
import { dashboard as adminDashboard } from '@/routes/admin';
import { edit as businessDetails } from '@/routes/business';
import { index as offers } from '@/routes/offers';
import { index as outlets } from '@/routes/outlets';
import { show as report } from '@/routes/reports';
import { index as staff } from '@/routes/staff';
import { index as adminBusinesses } from '@/routes/admin/businesses';
import { index as adminCategories } from '@/routes/admin/categories';
import { index as adminChanges } from '@/routes/admin/changes';
import { index as adminIntegrations } from '@/routes/admin/integrations';
import { index as adminOffers } from '@/routes/admin/offers';
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
              ...(can('scan')
                  ? [{ title: 'Counter', href: counter(), icon: ScanLine }]
                  : []),
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
              ...(can('manage_offers')
                  ? [
                        {
                            title: 'Offers',
                            href: offers(),
                            icon: Ticket,
                            matchChildren: true,
                        },
                    ]
                  : []),
              ...(can('manage_staff')
                  ? [{ title: 'Staff', href: staff(), icon: UserCog }]
                  : []),
          ]
        : [];

    const reportNavItems: NavItem[] = can('view_reports')
        ? [
              {
                  title: 'Vouchers used',
                  href: report('redemptions'),
                  icon: TicketCheck,
              },
              {
                  title: 'Offer results',
                  href: report('offers'),
                  icon: ChartNoAxesColumn,
              },
              {
                  title: 'Place visits',
                  href: report('place-visits'),
                  icon: MapPinned,
              },
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
                  title: 'Offers',
                  href: adminOffers(),
                  icon: Ticket,
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
              {
                  title: 'Integrations',
                  href: adminIntegrations(),
                  icon: Plug,
              },
              { title: 'Settings', href: adminSettings(), icon: Settings },
          ]
        : [];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                {workspace ? (
                    <BusinessSwitcher />
                ) : (
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton
                                size="lg"
                                render={<Link href={home()} />}
                            >
                                <AppLogo />
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                )}
            </SidebarHeader>

            <SidebarContent>
                {mainNavItems.length > 0 && <NavMain items={mainNavItems} />}
                {reportNavItems.length > 0 && (
                    <NavMain items={reportNavItems} label="Reports" />
                )}
                {adminNavItems.length > 0 && (
                    <NavMain items={adminNavItems} label="Admin" />
                )}
            </SidebarContent>

            <SidebarFooter>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            tooltip={{ children: 'Go to landing page' }}
                            render={<Link href={home()} />}
                        >
                            <House />
                            <span>Go to landing page</span>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
