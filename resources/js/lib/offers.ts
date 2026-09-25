/** "12.5" or "12.50" → "12.50", for money shown next to its currency. */
export function formatMoney(amount: string, currency: string): string {
    return `${currency} ${Number(amount).toFixed(2)}`;
}

type OfferTerms = Pick<
    App.Data.OfferData,
    | 'discount_type'
    | 'discount_value'
    | 'max_discount_amount'
    | 'min_spend_amount'
    | 'free_item'
    | 'currency'
>;

/**
 * An offer's discount in a few words, such as "10% off, up to MYR 20.00".
 */
export function discountSummary(offer: OfferTerms): string {
    const parts: string[] = [];

    if (offer.discount_type === 'percentage') {
        parts.push(`${Number(offer.discount_value)}% off`);

        if (offer.max_discount_amount) {
            parts.push(
                `up to ${formatMoney(offer.max_discount_amount, offer.currency)}`,
            );
        }
    } else if (offer.discount_type === 'amount') {
        parts.push(
            `${formatMoney(offer.discount_value ?? '0', offer.currency)} off`,
        );
    } else {
        parts.push(`Free ${offer.free_item}`);
    }

    if (offer.min_spend_amount) {
        parts.push(
            `min. spend ${formatMoney(offer.min_spend_amount, offer.currency)}`,
        );
    }

    return parts.join(', ');
}
