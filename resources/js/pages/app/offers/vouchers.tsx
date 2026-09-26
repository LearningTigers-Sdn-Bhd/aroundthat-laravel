import { Head, setLayoutProps, usePage } from '@inertiajs/react';
import { Ticket } from 'lucide-react';
import { useEffect, useState } from 'react';
import OfferPage from '@/components/app/offers/offer-page';
import VoucherCodeDialog, {
    type ShownVoucher,
} from '@/components/app/offers/voucher-code-dialog';
import ActionButton from '@/components/action-button';
import type { DataTableColumn } from '@/components/data-table';
import DataTable from '@/components/data-table';
import FormDialog from '@/components/form-dialog';
import Heading from '@/components/heading';
import ReasonDialog from '@/components/reason-dialog';
import StatusBadge from '@/components/status-badge';
import TextField from '@/components/text-field';
import { Button } from '@/components/ui/button';
import { formatDate, formatDateTime } from '@/lib/format';
import { edit, index } from '@/routes/offers';
import {
    index as vouchersIndex,
    reveal,
    store,
    voidMethod as voidVoucher,
} from '@/routes/offers/vouchers';

type Props = {
    offer: App.Data.OfferData;
    vouchers: Illuminate.LengthAwarePaginator<number, App.Data.VoucherData>;
    can: { update: boolean; issue: boolean; void: boolean };
};

export default function OfferVouchers({ offer, vouchers, can }: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Offers', href: index() },
            { title: offer.name, href: edit(offer.id) },
            { title: 'Vouchers', href: vouchersIndex(offer.id) },
        ],
    });

    // Flash data is gone on the next request, so keep the code in state until the dialog is closed.
    const flashedVoucher = usePage().flash.voucher as ShownVoucher | undefined;
    const [shownVoucher, setShownVoucher] = useState(flashedVoucher);

    useEffect(() => {
        if (flashedVoucher) {
            setShownVoucher(flashedVoucher);
        }
    }, [flashedVoucher]);

    const columns: DataTableColumn<App.Data.VoucherData>[] = [
        {
            key: 'code',
            header: 'Code',
            cell: (voucher) => (
                <span className="font-mono">{voucher.code_prefix}…</span>
            ),
        },
        {
            key: 'status',
            header: 'Status',
            cell: (voucher) => (
                <StatusBadge
                    status={
                        voucher.status === 'void' ? 'cancelled' : voucher.status
                    }
                />
            ),
        },
        {
            key: 'uses',
            header: 'Used',
            cell: (voucher) =>
                `${voucher.redemption_count} of ${voucher.uses_per_voucher}`,
        },
        {
            key: 'source',
            header: 'Issued by',
            cell: (voucher) => voucher.claimed_by_name ?? 'Staff',
        },
        {
            key: 'created_at',
            header: 'Issued',
            sort: 'created_at',
            cell: (voucher) => formatDateTime(voucher.created_at),
        },
        {
            key: 'expires_at',
            header: 'Expires',
            sort: 'expires_at',
            cell: (voucher) => formatDate(voucher.expires_at),
        },
        {
            key: 'actions',
            header: '',
            className: 'text-right',
            cell: (voucher) => (
                <div className="flex justify-end gap-1">
                    {can.issue && (
                        <FormDialog
                            trigger={
                                <Button variant="ghost" size="sm">
                                    Reveal
                                </Button>
                            }
                            title="Reveal this code?"
                            description="Confirm your password. The reveal is logged."
                            form={reveal.form([offer.id, voucher.id])}
                            submitLabel="Reveal"
                        >
                            {(errors) => (
                                <TextField
                                    name="password"
                                    label="Password"
                                    type="password"
                                    autoComplete="current-password"
                                    error={errors.password}
                                    required
                                />
                            )}
                        </FormDialog>
                    )}
                    {can.void && voucher.status === 'active' && (
                        <ReasonDialog
                            trigger={
                                <Button variant="ghost" size="sm">
                                    Void
                                </Button>
                            }
                            title="Void this voucher?"
                            description="It can never be used again."
                            form={voidVoucher.form([offer.id, voucher.id])}
                            submitLabel="Void"
                            destructive
                        />
                    )}
                </div>
            ),
        },
    ];

    return (
        <>
            <Head title={`Vouchers · ${offer.name}`} />

            <OfferPage offer={offer} canUpdate={can.update}>
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        variant="small"
                        title="Vouchers"
                        description="Codes issued for this offer. Find one by the first 4 characters of its code, reveal it again or void it."
                    />
                    {can.issue && (
                        <ActionButton form={store.form(offer.id)}>
                            <Ticket />
                            Issue voucher
                        </ActionButton>
                    )}
                </div>

                <VoucherCodeDialog
                    voucher={shownVoucher}
                    onClose={() => setShownVoucher(undefined)}
                />

                <DataTable
                    rows={vouchers}
                    columns={columns}
                    rowKey={(voucher) => voucher.id}
                    searchPlaceholder="First 4 characters of the code"
                    filters={[
                        {
                            name: 'status',
                            label: 'Statuses',
                            options: [
                                { value: 'active', label: 'Active' },
                                { value: 'used', label: 'Used' },
                                { value: 'expired', label: 'Expired' },
                                { value: 'void', label: 'Void' },
                            ],
                        },
                    ]}
                    emptyTitle="No vouchers yet"
                    emptyIcon={Ticket}
                />
            </OfferPage>
        </>
    );
}
