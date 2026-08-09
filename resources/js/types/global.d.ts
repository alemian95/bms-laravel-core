import type { Auth } from '@/types/auth';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            locale: string;
            supportedLocales: string[];
            translations: Record<string, string>;
            [key: string]: unknown;
        };
    }
}
