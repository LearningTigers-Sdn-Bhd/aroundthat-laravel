import { useCallback, useEffect, useState } from 'react';

/**
 * Bridges an InertiaUI modal (`HeadlessModal`) onto a Base UI overlay
 * (Sheet / Dialog / AlertDialog).
 *
 * `HeadlessModal` renders no DOM of its own — it resolves the modal off the
 * stack and hands us its state — so the overlay chrome is entirely ours while
 * the routing (history, base URL, redirects, partial reloads) stays with the
 * package.
 */

/**
 * The shape `HeadlessModal` passes to its render prop. The package doesn't
 * export this type, so it's mirrored here.
 */
export interface InertiaModalRenderProps {
    afterLeave: () => void;
    close: () => void;
    config: {
        closeButton: boolean;
        closeExplicitly: boolean;
        closeOnClickOutside: boolean;
        maxWidth: string;
        paddingClasses: string;
        panelClasses: string;
        position: string;
        slideover: boolean;
    };
    id: string;
    index: number;
    isOpen: boolean;
    onTopOfStack: boolean;
    shouldRender: boolean;
    [key: string]: unknown;
}

/**
 * Structural stand-in for `Dialog.Root.ChangeEventDetails`. Kept minimal so one
 * handler satisfies Dialog, Sheet and AlertDialog, whose `reason` unions differ.
 */
interface ChangeEventDetails {
    reason: string;
    cancel: () => void;
}

export function useInertiaModal({
    afterLeave,
    close,
    config,
    index,
    isOpen,
    onTopOfStack,
}: InertiaModalRenderProps) {
    // InertiaUI mounts the panel already open, and Base UI skips the enter
    // transition of a dialog that mounts open. Holding it closed for the first
    // frame turns the mount into an ordinary open, which does animate.
    const [hasMounted, setHasMounted] = useState(false);

    useEffect(() => {
        const frame = requestAnimationFrame(() => setHasMounted(true));

        return () => cancelAnimationFrame(frame);
    }, []);

    const onOpenChange = useCallback(
        (open: boolean, details: ChangeEventDetails) => {
            // Stacked Inertia modals are siblings in the React tree, never nested, so
            // Base UI treats each one as topmost and would let a single Escape close the
            // whole stack. Only the real top of the stack may respond.
            if (!onTopOfStack) {
                details.cancel();
                return;
            }

            if (config.closeExplicitly && details.reason === 'escapeKey') {
                details.cancel();
                return;
            }

            if (!open) {
                close();
            }
        },
        [close, config.closeExplicitly, onTopOfStack],
    );

    // Load-bearing: `shouldRender` stays true until `afterLeave` runs, so without
    // this the modal never leaves the stack and the URL never returns to its base.
    // `Modal.afterLeave` guards itself with `if (this.isOpen) return`, so an early
    // call while still open is harmless.
    const onOpenChangeComplete = useCallback(
        (open: boolean) => {
            if (!open) {
                afterLeave();
            }
        },
        [afterLeave],
    );

    return {
        /** Accepted by Dialog, Sheet and AlertDialog roots alike. */
        rootProps: {
            open: isOpen && hasMounted,
            onOpenChange,
            onOpenChangeComplete,
        },
        /** Dialog and Sheet only — AlertDialog omits both by design. */
        dismissProps: {
            // Base UI 1.8 has no `dismissible` prop.
            disablePointerDismissal:
                config.closeExplicitly || !config.closeOnClickOutside,
            // A parent that stays modal would aria-hide the child modal's own portal
            // and fight it for focus, so it relinquishes once a child opens.
            modal: onTopOfStack,
        },
        contentProps: {
            // Base UI hides nested backdrops for us, but only for true React nesting.
            // Mirror the package's own rule instead: one backdrop, for the first modal.
            showOverlay: index === 0,
            'data-inactive': !onTopOfStack || undefined,
            'aria-hidden': !onTopOfStack || undefined,
        },
        showCloseButton: config.closeButton,
    };
}
