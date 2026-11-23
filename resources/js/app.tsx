import { createInertiaApp } from '@inertiajs/react';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import AccountLayout from '@/layouts/account-layout';
import AuthLayout from '@/layouts/auth-layout';
import DashboardLayout from '@/layouts/dashboard-layout';
import PublicLayout from '@/layouts/public-layout';
import RoleLayout from '@/layouts/role-layout';
import SettingsLayout from '@/layouts/settings/layout';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name.startsWith('public/'):
                return PublicLayout;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('account/'):
                return AccountLayout;
            case name.startsWith('dashboard/'):
                return DashboardLayout;
            case name.startsWith('settings/'):
                return [RoleLayout, SettingsLayout];
            default:
                return null;
        }
    },
    strictMode: true,
    withApp(app) {
        return (
            <TooltipProvider delayDuration={0}>
                {app}
                <Toaster />
            </TooltipProvider>
        );
    },
    progress: {
        color: '#FF4D1C',
    },
});

// This will set light / dark mode on load...
initializeTheme();
