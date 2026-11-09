import { Glass } from '@/components/glass';
import { Verse } from '@/modules/landing/components';
import type { PublicDevotional } from './types';
import { paragraphs } from './types';

/**
 * A full devotional: date, title, verse on glass, and the body on a solid
 * surface for comfortable reading (PRD 4.9: long text is not on glass).
 */
export function DevotionalArticle({
    devotional,
    headingLevel = 'h1',
}: {
    devotional: PublicDevotional;
    headingLevel?: 'h1' | 'h2';
}) {
    const Heading = headingLevel;

    return (
        <article className="space-y-6">
            <header>
                <p className="mb-2 text-sm font-semibold tracking-widest text-gold uppercase">
                    <time dateTime={devotional.date}>
                        {devotional.dateLabel}
                    </time>
                </p>
                <Heading className="font-serif text-3xl leading-tight font-semibold tracking-tight text-balance sm:text-5xl">
                    {devotional.title}
                </Heading>
            </header>

            <Glass className="p-6 sm:p-8">
                {devotional.verse ? (
                    <Verse
                        text={devotional.verse}
                        reference={devotional.reference}
                    />
                ) : (
                    <p className="font-semibold tracking-wide text-gold">
                        {devotional.reference}
                    </p>
                )}
            </Glass>

            {devotional.body && (
                <div className="space-y-4 rounded-glass border bg-background/90 p-6 text-lg leading-relaxed sm:p-8">
                    {paragraphs(devotional.body).map((paragraph, index) => (
                        <p key={index} className="whitespace-pre-line">
                            {paragraph}
                        </p>
                    ))}
                </div>
            )}
        </article>
    );
}
