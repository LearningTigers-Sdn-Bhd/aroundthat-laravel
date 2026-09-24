import { Form, Head, setLayoutProps } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes/admin';
import { edit } from '@/routes/admin/settings';
import { update as updateMedia } from '@/routes/admin/settings/media';

type Props = {
    mediaDisk: App.Enums.MediaDisk;
    mediaDisks: {
        value: App.Enums.MediaDisk;
        label: string;
        configured: boolean;
    }[];
};

export default function Settings({ mediaDisk, mediaDisks }: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Admin', href: dashboard() },
            { title: 'Settings', href: edit() },
        ],
    });

    return (
        <>
            <Head title="Settings" />

            <div className="flex max-w-2xl flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Settings"
                    description="App-wide settings. Changes apply at once."
                />

                <Form
                    {...updateMedia.form()}
                    options={{ preserveScroll: true }}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <Heading variant="small" title="Image storage" />
                            <div className="grid gap-2">
                                <Label htmlFor="media_disk">
                                    Store new images on
                                </Label>
                                <Select
                                    name="media_disk"
                                    items={mediaDisks.map((disk) => ({
                                        value: disk.value,
                                        label: disk.label,
                                    }))}
                                    defaultValue={mediaDisk}
                                    required
                                >
                                    <SelectTrigger
                                        id="media_disk"
                                        aria-invalid={!!errors.media_disk}
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {mediaDisks.map((disk) => (
                                            <SelectItem
                                                key={disk.value}
                                                value={disk.value}
                                                disabled={!disk.configured}
                                            >
                                                {disk.label}
                                                {!disk.configured &&
                                                    ' (not set up)'}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <p className="text-sm text-muted-foreground">
                                    Images already uploaded stay where they are.
                                    Only new uploads use this choice.
                                </p>
                                <InputError message={errors.media_disk} />
                            </div>

                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner />}
                                Save
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}
