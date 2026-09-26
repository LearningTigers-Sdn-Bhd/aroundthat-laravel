import { Form } from '@inertiajs/react';
import ActionButton from '@/components/action-button';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import {
    destroy as clearHost,
    update as setHost,
} from '@/routes/admin/outlets/host';

type Props = {
    outlet: App.Data.Admin.OutletData;
    candidates: App.Data.Admin.HostOutletOptionData[];
};

/**
 * Sets or clears the outlet this one sits inside, such as a mall.
 */
export default function HostOutlet({ outlet, candidates }: Props) {
    return (
        <section className="space-y-3">
            <Heading
                variant="small"
                title="Inside another outlet"
                description="For example a restaurant inside a mall. This gives the host no access to this outlet."
            />

            {outlet.archived_at ? (
                <p className="text-sm text-muted-foreground">
                    {outlet.host_outlet?.name ?? 'No host.'} Archived outlets
                    cannot change their host.
                </p>
            ) : (
                <div className="flex flex-wrap items-start gap-2">
                    <Form
                        {...setHost.form(outlet.id)}
                        options={{ preserveScroll: true }}
                        className="flex flex-wrap items-start gap-2"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-1">
                                    <Select
                                        key={outlet.host_outlet?.id ?? 'none'}
                                        name="host_outlet_id"
                                        items={[
                                            {
                                                value: null,
                                                label: 'Choose the host outlet',
                                            },
                                            ...candidates.map((candidate) => ({
                                                value: candidate.id,
                                                label: `${candidate.name} (${candidate.business_name})`,
                                            })),
                                        ]}
                                        defaultValue={outlet.host_outlet?.id}
                                    >
                                        <SelectTrigger
                                            className="w-72"
                                            aria-label="Host outlet"
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
                                    <InputError
                                        message={errors.host_outlet_id}
                                    />
                                </div>
                                <Button
                                    type="submit"
                                    variant="outline"
                                    disabled={processing}
                                >
                                    {processing && <Spinner />}
                                    Save host
                                </Button>
                            </>
                        )}
                    </Form>

                    {outlet.host_outlet && (
                        <ActionButton
                            form={clearHost.form(outlet.id)}
                            variant="ghost"
                        >
                            Clear host
                        </ActionButton>
                    )}
                </div>
            )}
        </section>
    );
}
