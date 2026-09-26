export type Amounts = {
    bill_amount: string;
    discount_amount: string;
    net_amount: string;
    capped: boolean;
};

export type EligibleCheck = {
    eligible: true;
    /** Where the voucher is checked: the chosen outlet, or the one other outlet of the cashier's it works at. */
    outlet: App.Data.OutletOptionData;
    voucher: {
        code_prefix: string;
        uses_left: number;
        expires_at: string;
    };
    offer: Pick<
        App.Data.OfferData,
        | 'name'
        | 'description'
        | 'discount_type'
        | 'discount_value'
        | 'max_discount_amount'
        | 'min_spend_amount'
        | 'free_item'
        | 'currency'
    > & { business_name: string };
    amounts: Amounts | null;
};

/** What `counter.check` answers: the voucher and offer, or why the code cannot be used here. */
export type CheckResponse =
    | {
          eligible: false;
          reason: 'choose_outlet';
          message: string;
          outlets: App.Data.OutletOptionData[];
      }
    | { eligible: false; reason: string; message: string }
    | EligibleCheck;
