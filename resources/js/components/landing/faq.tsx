import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import { accessRequestEmail, accessRequestHref } from '@/lib/brand';

const questions = [
    {
        question: 'Who is AroundThat for?',
        answer: 'Local businesses that want travellers to find them: cafés, restaurants, attractions, tour operators, spas, and shops near hotels.',
    },
    {
        question: 'Do guests need to download an app?',
        answer: 'No. Guests see your business and offers on the concierge page of a partner hotel or travel service. They show a code at your counter.',
    },
    {
        question: 'Can I manage more than one outlet?',
        answer: 'Yes. One business can have many outlets, and each offer can apply to the outlets you choose.',
    },
    {
        question: 'Can my staff use it?',
        answer: 'Yes. Invite staff to your business and give them only the access they need, for example scanning vouchers at the counter.',
    },
    {
        question: "Can I see my guests' personal data?",
        answer: 'No. You see totals such as views, claims, and redemptions. Guest details stay private.',
    },
];

export function Faq() {
    return (
        <section
            id="faq"
            className="mx-auto max-w-6xl scroll-mt-20 px-4 py-20 sm:px-6 lg:py-28"
        >
            <div className="max-w-2xl">
                <p className="text-xs font-semibold tracking-[0.18em] text-primary uppercase">
                    FAQ
                </p>
                <h2 className="mt-2 font-heading text-3xl font-semibold tracking-tight sm:text-4xl">
                    Questions businesses ask us
                </h2>
            </div>

            <Accordion className="mt-10 max-w-3xl border-t">
                {questions.map((item) => (
                    <AccordionItem key={item.question} value={item.question}>
                        <AccordionTrigger className="text-base">
                            {item.question}
                        </AccordionTrigger>
                        <AccordionContent className="text-muted-foreground">
                            {item.answer}
                        </AccordionContent>
                    </AccordionItem>
                ))}
                <AccordionItem value="join">
                    <AccordionTrigger className="text-base">
                        How do I join?
                    </AccordionTrigger>
                    <AccordionContent className="text-muted-foreground">
                        Joining is by invitation. Email us at{' '}
                        <a href={accessRequestHref}>{accessRequestEmail}</a>{' '}
                        with your business name and location, and we will set up
                        your account.
                    </AccordionContent>
                </AccordionItem>
            </Accordion>
        </section>
    );
}
