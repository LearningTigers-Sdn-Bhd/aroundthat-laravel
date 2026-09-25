import { Form } from '@inertiajs/react';
import ConfirmDialog from '@/components/confirm-dialog';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { destroy, store } from '@/routes/admin/offers/outlets';

type Props = {
    offer: App.Data.OfferData;
    candidates: App.Data.Admin.HostOutletOptionData[];
};

/**
 * Where the offer's vouchers can be used. The owner picks their own outlets; an admin adds other businesses' outlets.
 */
export default function OfferOutlets({ offer, candidates }: Props) {
    return (
        <section className="space-y-3">
            <Heading
                variant="small"
                title="Where vouchers can be used"
                description="Adding another business's outlet sponsors the offer there: its cashiers can redeem the vouchers at once."
            />

            {offer.outlets.length === 0 ? (
                <p className="text-sm text-muted-foreground">No outlets yet.</p>
            ) : (
                <ul className="divide-y rounded-md border text-sm">
                    {offer.outlets.map((outlet) => (
                        <li
                            key={outlet.id}
                            className="flex items-center justify-between gap-4 px-3 py-2"
                        >
                            <div className="flex items-center gap-2">
                                {outlet.name}
                                {outlet.is_sponsored && (
                                    <Badge variant="outline">
                                        Sponsored · {outlet.business_name}
                                    </Badge>
                                )}
                            </div>
                            {outlet.is_sponsored && (
                                <ConfirmDialog
                                    trigger={
                                        <Button variant="ghost" size="sm">
                                            Remove
                                        </Button>
                                    }
                                    title={`Remove ${outlet.name}?`}
                                    description="Its cashiers can no longer redeem this offer's vouchers."
                                    form={destroy.form([offer.id, outlet.id])}
                                    confirmLabel="Remove"
                                    destructive
                                />
                            )}
                        </li>
                    ))}
                </ul>
            )}

            <Form
                {...store.form(offer.id)}
                options={{ preserveScroll: true }}
                resetOnSuccess
                className="flex flex-wrap items-start gap-2"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-1">
                            <Select
                                name="outlet_id"
                                items={[
                                    {
                                        value: null,
                                        label: 'Choose an outlet to sponsor',
                                    },
                                    ...candidates.map((candidate) => ({
                                        value: candidate.id,
                                        label: `${candidate.name} (${candidate.business_name})`,
                                    })),
                                ]}
                            >
                                <SelectTrigger
                                    className="w-72"
                                    aria-label="Outlet to sponsor"
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {candidates.map((candidate) => (
                                        <SelectItem
                                            key={candidate.id}
                                            value={candidate.id}
                                        >
                                            {candidate.name} (
                                            {candidate.business_name})
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.outlet_id} />
                        </div>
                        <Button
                            type="submit"
                            variant="outline"
                            disabled={processing}
                        >
                            {processing && <Spinner />}
                            Add outlet
                        </Button>
                    </>
                )}
            </Form>
        </section>
    );
}
