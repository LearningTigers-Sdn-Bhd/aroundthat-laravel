import InputError from '@/components/input-error';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

/**
 * The business's own outlets where the offer's vouchers can be used, as checkboxes named `outlet_ids[]`. Hide the
 * legend when a section heading above already says what the checkboxes are for.
 */
export default function OfferOutletsField({
    outletOptions,
    defaultOutletIds,
    error,
    hideLegend = false,
}: {
    outletOptions: App.Data.OutletOptionData[];
    defaultOutletIds: string[];
    error?: string;
    hideLegend?: boolean;
}) {
    return (
        <fieldset className="grid gap-2">
            <legend
                className={cn(
                    'mb-2 text-sm font-medium',
                    hideLegend && 'sr-only',
                )}
            >
                Where vouchers can be used
            </legend>
            {outletOptions.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    Add an outlet first.
                </p>
            ) : (
                outletOptions.map((outlet) => (
                    <div key={outlet.id} className="flex items-center gap-3">
                        <Checkbox
                            id={`outlet-${outlet.id}`}
                            name="outlet_ids[]"
                            value={outlet.id}
                            defaultChecked={defaultOutletIds.includes(
                                outlet.id,
                            )}
                        />
                        <Label htmlFor={`outlet-${outlet.id}`}>
                            {outlet.name}
                        </Label>
                    </div>
                ))
            )}
            <InputError message={error} />
        </fieldset>
    );
}
