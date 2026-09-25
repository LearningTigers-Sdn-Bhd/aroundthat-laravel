import { createInertiaApp } from '@inertiajs/react';
import { ModalStackProvider } from '@inertiaui/modal-react';
import { Toaster } from '@/components/ui/toast';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import ModalLayout from '@/layouts/modal-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { configureEcho } from '@laravel/echo-react';

configureEcho({
    broadcaster: 'reverb',
});

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'welcome':
            case name.startsWith('places/'):
                return ModalLayout;
            case name.startsWith('auth/'):
            case name.startsWith('workspace/'):
            case name.startsWith('invitations/'):
                return [ModalLayout, AuthLayout];
            case name.startsWith('settings/'):
                return [ModalLayout, AppLayout, SettingsLayout];
            default:
                return [ModalLayout, AppLayout];
        }
    },
    strictMode: true,
    withApp(app) {
        return (
            <ModalStackProvider>
                <TooltipProvider>
                    {app}
                    <Toaster />
                </TooltipProvider>
            </ModalStackProvider>
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
