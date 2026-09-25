import { Download, Plus, ScanLine, Store, UserPlus } from 'lucide-react';
import ButtonLink from '@/components/button-link';
import ModalButtonLink from '@/components/modal-button-link';
import { buttonVariants } from '@/components/ui/button';
import { show as counter } from '@/routes/counter';
import { create as createOffer } from '@/routes/offers';
import { create as createOutlet } from '@/routes/outlets';
import { exportMethod as exportReport } from '@/routes/reports';
import { index as staff } from '@/routes/staff';

export type QuickAction =
    | 'counter'
    | 'new_offer'
    | 'new_outlet'
    | 'invite_staff'
    | 'export_redemptions';

/**
 * The shortcuts the member may take. The counter leads, as the one most used; the rest are outlined.
 */
export default function QuickActions({ actions }: { actions: QuickAction[] }) {
    const secondary = actions[0] === 'counter' ? 'outline' : 'default';

    return (
        <div className="flex flex-wrap gap-2">
            {actions.map((action) => {
                switch (action) {
                    case 'counter':
                        return (
                            <ButtonLink key={action} href={counter()} prefetch>
                                <ScanLine data-icon="inline-start" />
                                Open counter
                            </ButtonLink>
                        );
                    case 'new_offer':
                        return (
                            <ModalButtonLink
                                key={action}
                                href={createOffer().url}
                                variant={secondary}
                            >
                                <Plus data-icon="inline-start" />
                                New offer
                            </ModalButtonLink>
                        );
                    case 'new_outlet':
                        return (
                            <ModalButtonLink
                                key={action}
                                href={createOutlet().url}
                                variant="outline"
                            >
                                <Store data-icon="inline-start" />
                                Add outlet
                            </ModalButtonLink>
                        );
                    case 'invite_staff':
                        return (
                            <ButtonLink
                                key={action}
                                href={staff()}
                                variant="outline"
                            >
                                <UserPlus data-icon="inline-start" />
                                Invite staff
                            </ButtonLink>
                        );
                    case 'export_redemptions':
                        return (
                            <a
                                key={action}
                                href={exportReport('redemptions').url}
                                download
                                data-slot="button"
                                className={buttonVariants({
                                    variant: 'outline',
                                })}
                            >
                                <Download data-icon="inline-start" />
                                Export this month
                            </a>
                        );
                }
            })}
        </div>
    );
}
