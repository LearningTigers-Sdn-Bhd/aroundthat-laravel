import { type ReactNode, useEffect } from 'react';

/**
 * The counter surface: full screen, no sidebar, always dark in the `.counter` palette. The classes also go on the
 * html element while the counter is open, because dialogs and sheets portal out to the body and would otherwise come
 * back in the app's own theme.
 */
export default function CounterLayout({ children }: { children: ReactNode }) {
    useEffect(() => {
        const root = document.documentElement;
        const wasDark = root.classList.contains('dark');
        root.classList.add('counter', 'dark');

        return () => {
            root.classList.remove('counter');

            if (!wasDark) {
                root.classList.remove('dark');
            }
        };
    }, []);

    return (
        <div className="counter dark min-h-dvh bg-background text-foreground selection:bg-primary selection:text-primary-foreground">
            {children}
        </div>
    );
}
