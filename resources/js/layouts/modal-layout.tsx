import { ModalRoot } from '@inertiaui/modal-react';
import type { ReactNode } from 'react';

/**
 * The outermost layout of every page. It renders the routed InertiaUI modals
 * above the page, so a modal survives navigation between pages that share it.
 */
export default function ModalLayout({ children }: { children: ReactNode }) {
    return (
        <>
            {children}
            <ModalRoot />
        </>
    );
}
