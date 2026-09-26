import type { Auth } from '@/types/auth';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            workspace: App.Data.CurrentWorkspaceData | null;
            workspaces: App.Data.WorkspaceOptionData[];
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
