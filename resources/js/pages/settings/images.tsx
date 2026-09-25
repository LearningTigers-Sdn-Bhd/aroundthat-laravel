import { Form, Head } from '@inertiajs/react';
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
import { edit, update as updateMedia } from '@/routes/images';

type Props = {
    mediaDisk: App.Enums.MediaDisk;
    mediaDisks: {
        value: App.Enums.MediaDisk;
        label: string;
        configured: boolean;
    }[];
};

export default function Images({ mediaDisk, mediaDisks }: Props) {
    return (
        <>
            <Head title="Image configuration" />

            <h1 className="sr-only">Image configuration</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Image storage"
                    description="Applies to the whole app. Changes apply at once."
                />

                <Form
                    {...updateMedia.form()}
                    options={{ preserveScroll: true }}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
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

Images.layout = {
    breadcrumbs: [
        {
            title: 'Image configuration',
            href: edit(),
        },
    ],
};
