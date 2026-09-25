import { useState } from 'react';
import InputError from '@/components/input-error';
import TextField from '@/components/text-field';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

export const integrationTypes: Record<App.Enums.IntegrationType, string> = {
    pms: 'PMS',
    travel_agency: 'Travel agency',
    internal: 'Internal',
};

export const capabilities: Record<App.Enums.IntegrationCapability, string> = {
    'places:read': 'Read public places',
    'engagement:write': 'Send engagement events',
};

/**
 * The Malaysian date of a moment, as YYYY-MM-DD for a date input.
 */
export function toMalaysianDate(value: string | null): string | undefined {
    return value
        ? new Intl.DateTimeFormat('en-CA', {
              timeZone: 'Asia/Kuala_Lumpur',
          }).format(new Date(value))
        : undefined;
}

/**
 * An integration's name, type, capabilities and access period.
 */
export default function IntegrationFields({
    errors,
    integration,
}: {
    errors: Record<string, string>;
    integration?: App.Data.Admin.IntegrationData;
}) {
    const [type, setType] = useState<App.Enums.IntegrationType>(
        integration?.type ?? 'pms',
    );

    return (
        <>
            <TextField
                name="name"
                label="Name"
                defaultValue={integration?.name}
                maxLength={120}
                error={errors.name}
                required
            />

            <div className="grid gap-2">
                <Label htmlFor="type">Type</Label>
                <Select
                    name="type"
                    items={Object.entries(integrationTypes).map(
                        ([value, label]) => ({ value, label }),
                    )}
                    value={type}
                    onValueChange={(value) => {
                        if (value) {
                            setType(value);
                        }
                    }}
                >
                    <SelectTrigger id="type">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {Object.entries(integrationTypes).map(
                            ([value, label]) => (
                                <SelectItem key={value} value={value}>
                                    {label}
                                </SelectItem>
                            ),
                        )}
                    </SelectContent>
                </Select>
                <InputError message={errors.type} />
            </div>

            <fieldset className="grid gap-2">
                <legend className="mb-2 text-sm font-medium">
                    Capabilities
                </legend>
                {Object.entries(capabilities).map(([value, label]) => (
                    <div key={value} className="flex items-center gap-3">
                        <Checkbox
                            id={`capability-${value}`}
                            name="capabilities[]"
                            value={value}
                            defaultChecked={
                                integration
                                    ? integration.capabilities.includes(value)
                                    : value === 'places:read'
                            }
                        />
                        <Label htmlFor={`capability-${value}`}>{label}</Label>
                    </div>
                ))}
                <InputError message={errors.capabilities} />
            </fieldset>

            <div className="grid gap-4 sm:grid-cols-2">
                <TextField
                    name="starts_on"
                    label="Keys work from"
                    type="date"
                    defaultValue={toMalaysianDate(
                        integration?.starts_at ?? null,
                    )}
                    error={errors.starts_on}
                />
                <TextField
                    name="ends_on"
                    label="Keys stop on"
                    type="date"
                    defaultValue={toMalaysianDate(
                        integration?.expires_at ?? null,
                    )}
                    error={errors.ends_on}
                />
            </div>
            <p className="-mt-2 text-sm text-muted-foreground">
                Malaysian dates. Leave empty for no limit.
            </p>
        </>
    );
}
