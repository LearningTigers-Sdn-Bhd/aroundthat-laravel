import { Form, Head } from '@inertiajs/react';
import { Building2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { update } from '@/routes/workspace';

export default function ChooseWorkspace({
    options,
}: {
    options: App.Data.WorkspaceOptionData[];
}) {
    return (
        <>
            <Head title="Choose a business" />

            <div className="grid gap-2">
                {options.map((option) => (
                    <Form key={option.business_id} {...update.form()}>
                        {({ processing }) => (
                            <>
                                <input
                                    type="hidden"
                                    name="business_id"
                                    value={option.business_id}
                                />
                                <Button
                                    type="submit"
                                    variant="outline"
                                    className="h-auto w-full justify-start gap-3 py-3"
                                    disabled={processing}
                                >
                                    <Building2 className="size-4 shrink-0" />
                                    <span className="flex flex-col items-start">
                                        <span>{option.business_name}</span>
                                        <span className="text-xs text-muted-foreground capitalize">
                                            {option.role}
                                        </span>
                                    </span>
                                </Button>
                            </>
                        )}
                    </Form>
                ))}
            </div>
        </>
    );
}

ChooseWorkspace.layout = {
    title: 'Choose a business',
    description: 'You belong to more than one business. Pick one to work in.',
};
