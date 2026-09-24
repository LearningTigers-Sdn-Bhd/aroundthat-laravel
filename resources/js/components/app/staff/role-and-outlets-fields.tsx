import { useState } from 'react';
import InputError from '@/components/input-error';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type Props = {
    roles: App.Enums.MembershipRole[];
    outletOptions: App.Data.OutletOptionData[];
    defaultRole: App.Enums.MembershipRole;
    defaultOutletIds: string[];
    errors: Record<string, string>;
};

const roleHelp: Record<App.Enums.MembershipRole, string> = {
    owner: 'Runs the business: details, outlets, staff, offers and reports at every outlet.',
    manager: 'Runs offers, scans vouchers and sees reports at their outlets.',
    cashier: 'Scans vouchers at their outlets.',
};

/**
 * A role, and for managers and cashiers the outlets they work at.
 */
export default function RoleAndOutletsFields({
    roles,
    outletOptions,
    defaultRole,
    defaultOutletIds,
    errors,
}: Props) {
    const [role, setRole] = useState(defaultRole);

    return (
        <>
            <div className="grid gap-2">
                <Label htmlFor="role">Role</Label>
                <Select
                    name="role"
                    value={role}
                    onValueChange={(value) =>
                        setRole(value as App.Enums.MembershipRole)
                    }
                >
                    <SelectTrigger id="role" className="capitalize">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {roles.map((option) => (
                            <SelectItem
                                key={option}
                                value={option}
                                className="capitalize"
                            >
                                {option}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <p className="text-sm text-muted-foreground">
                    {roleHelp[role]}
                </p>
                <InputError message={errors.role} />
            </div>

            {role !== 'owner' && (
                <fieldset className="grid gap-2">
                    <legend className="mb-2 text-sm font-medium">
                        Outlets
                    </legend>
                    {outletOptions.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No approved, active outlets yet.
                        </p>
                    ) : (
                        outletOptions.map((outlet) => (
                            <div
                                key={outlet.id}
                                className="flex items-center gap-3"
                            >
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
                    <InputError message={errors.outlet_ids} />
                </fieldset>
            )}
        </>
    );
}
