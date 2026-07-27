import { createInertiaApp } from '@inertiajs/react';
import type { ComponentType } from 'react';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import SettingsLayout from '@/layouts/settings/layout';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const corePages = import.meta.glob('./pages/**/*.tsx');
const pluginPages = import.meta.glob('/packages/*/resources/js/pages/**/*.tsx');

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    /** `bookmarks/read` from the app, `ai::summary` from the `ai` package. */
    resolve: async (name: string) => {
        const [plugin, page] = name.includes('::')
            ? name.split('::')
            : [null, name];

        const loader = plugin
            ? pluginPages[`/packages/${plugin}/resources/js/pages/${page}.tsx`]
            : corePages[`./pages/${page}.tsx`];

        if (!loader) {
            throw new Error(`Page not found: ${name}`);
        }

        return ((await loader()) as { default: ComponentType }).default;
    },
    layout: (name) => {
        switch (true) {
            case name === 'welcome':
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
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
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
