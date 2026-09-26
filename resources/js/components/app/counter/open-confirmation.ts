import type { LinkConfirmation } from '@/components/confirm-link';

/**
 * Asked before any link opens the counter, since it takes over the whole screen.
 */
export const openCounterConfirmation: LinkConfirmation = {
    title: 'Open the counter?',
    description:
        'The counter fills the whole screen to check and redeem guest vouchers.',
    confirmLabel: 'Open counter',
};
