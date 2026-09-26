import { Form, Link } from '@inertiajs/react';
import { Store } from 'lucide-react';
import ConfirmDialog from '@/components/confirm-dialog';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Empty,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { show as showOutlet } from '@/routes/admin/outlets';
import { destroy, store } from '@/routes/admin/offers/outlets';

type Props = {
    offer: App.Data.OfferData;
    /** The business that runs the offer, shown against its own outlets. */
    businessName: string;
    candidates: App.Data.Admin.HostOutletOptionData[];
};

/**
 * Where the offer's vouchers can be used. The owner picks their own outlets; an admin adds other businesses' outlets.
 */
export default function OfferOutlets({
    offer,
    businessName,
    candidates,
}: Props) {
    return (
        <section className="space-y-3">
            <Heading
                variant="small"
                title="Where vouchers can be used"
                description="Adding another business's outlet sponsors the offer there: its cashiers can redeem the vouchers at once."
            />

            {offer.outlets.length === 0 ? (
                <Empty className="border p-6">
                    <EmptyHeader>
                        <EmptyMedia variant="icon">
                            <Store />
                        </EmptyMedia>
                        <EmptyTitle>No outlets yet</EmptyTitle>
                    </EmptyHeader>
                </Empty>
            ) : (
                <div className="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Outlet</TableHead>
                                <TableHead>Business</TableHead>
                                <TableHead>Type</TableHead>
                                <TableHead className="w-24">
                                    <span className="sr-only">Actions</span>
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {offer.outlets.map((outlet) => (
                                <TableRow key={outlet.id}>
                                    <TableCell className="font-medium">
                                        <Link
                                            href={showOutlet(outlet.id)}
                                            className="hover:underline"
                                        >
                                            {outlet.name}
                                        </Link>
                                    </TableCell>
                                    <TableCell>
                                        {outlet.business_name ?? businessName}
                                    </TableCell>
                                    <TableCell>
                                        {outlet.is_sponsored ? (
                                            <Badge variant="outline">
                                                Sponsored
                                            </Badge>
                                        ) : (
                                            <Badge variant="secondary">
                                                Own
                                            </Badge>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {outlet.is_sponsored && (
                                            <ConfirmDialog
                                                trigger={
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                    >
                                                        Remove
                                                    </Button>
                                                }
                                                title={`Remove ${outlet.name}?`}
                                                description="Its cashiers can no longer redeem this offer's vouchers."
                                                form={destroy.form([
                                                    offer.id,
                                                    outlet.id,
                                                ])}
                                                confirmLabel="Remove"
                                                destructive
                                            />
                                        )}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            )}

            {candidates.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    No other business has a trading outlet left to sponsor.
                </p>
            ) : (
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
            )}
        </section>
    );
}
