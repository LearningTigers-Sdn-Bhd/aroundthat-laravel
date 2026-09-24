import { useMemo, useState } from 'react';
import ComboboxField from '@/components/combobox-field';
import InputError from '@/components/input-error';
import TextField from '@/components/text-field';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { countryOptions, timezoneOptions } from '@/lib/locations';

type OutletValues = Pick<
    App.Data.OutletData,
    | 'name'
    | 'contact_email'
    | 'contact_phone'
    | 'address_line_1'
    | 'address_line_2'
    | 'city'
    | 'state'
    | 'postcode'
    | 'country_code'
    | 'timezone'
>;

/** The inputs OutletFields renders, so a page can leave their errors to it. */
export const outletFieldNames = [
    'name',
    'contact_email',
    'contact_phone',
    'address_line_1',
    'address_line_2',
    'city',
    'state',
    'postcode',
    'country_code',
    'timezone',
];

/**
 * The editable outlet details, for use inside an Inertia `<Form>`. Pass `outlet` to prefill an edit form.
 */
export default function OutletFields({
    errors,
    outlet,
    locationOptions,
    defaultTimezone = 'Asia/Kuala_Lumpur',
}: {
    errors: Record<string, string>;
    outlet?: OutletValues;
    locationOptions: App.Data.LocationOptionsData;
    defaultTimezone?: string;
}) {
    const [countryCode, setCountryCode] = useState(
        outlet?.country_code ?? 'MY',
    );
    const countries = useMemo(
        () => countryOptions(locationOptions.country_codes),
        [locationOptions.country_codes],
    );
    const timezones = useMemo(
        () => timezoneOptions(locationOptions.timezones),
        [locationOptions.timezones],
    );

    return (
        <>
            <TextField
                name="name"
                label="Name"
                defaultValue={outlet?.name}
                error={errors.name}
                required
            />
            <div className="grid gap-4 sm:grid-cols-2">
                <TextField
                    name="contact_email"
                    label="Contact email"
                    type="email"
                    defaultValue={outlet?.contact_email ?? ''}
                    error={errors.contact_email}
                />
                <TextField
                    name="contact_phone"
                    label="Contact phone"
                    defaultValue={outlet?.contact_phone ?? ''}
                    error={errors.contact_phone}
                />
            </div>
            <TextField
                name="address_line_1"
                label="Address line 1"
                defaultValue={outlet?.address_line_1}
                error={errors.address_line_1}
                required
            />
            <TextField
                name="address_line_2"
                label="Address line 2"
                defaultValue={outlet?.address_line_2 ?? ''}
                error={errors.address_line_2}
            />
            <div className="grid gap-4 sm:grid-cols-3">
                <TextField
                    name="city"
                    label="City"
                    defaultValue={outlet?.city}
                    error={errors.city}
                    required
                />
                {countryCode === 'MY' ? (
                    <div className="grid gap-2">
                        <Label htmlFor="state">State</Label>
                        <Select
                            name="state"
                            items={[
                                { value: null, label: 'Choose a state' },
                                ...locationOptions.malaysian_states.map(
                                    (state) => ({ value: state, label: state }),
                                ),
                            ]}
                            defaultValue={outlet?.state}
                            required
                        >
                            <SelectTrigger
                                id="state"
                                aria-invalid={!!errors.state}
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {locationOptions.malaysian_states.map(
                                    (state) => (
                                        <SelectItem key={state} value={state}>
                                            {state}
                                        </SelectItem>
                                    ),
                                )}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.state} />
                    </div>
                ) : (
                    <TextField
                        name="state"
                        label="State"
                        defaultValue={outlet?.state}
                        error={errors.state}
                        required
                    />
                )}
                <TextField
                    name="postcode"
                    label="Postcode"
                    defaultValue={outlet?.postcode}
                    error={errors.postcode}
                    required
                />
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
                <ComboboxField
                    name="country_code"
                    label="Country"
                    options={countries}
                    defaultValue={countryCode}
                    onValueChange={(value) => setCountryCode(value ?? '')}
                    error={errors.country_code}
                    required
                />
                <ComboboxField
                    name="timezone"
                    label="Timezone"
                    options={timezones}
                    defaultValue={outlet?.timezone ?? defaultTimezone}
                    error={errors.timezone}
                    required
                />
            </div>
        </>
    );
}
