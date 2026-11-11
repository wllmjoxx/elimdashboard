import { Verse } from '@/modules/landing/components';
import type { PublicDevotional } from './types';
import { paragraphs } from './types';

/**
 * A full devotional: date and title (unless the page hero shows them),
 * the verse, then the body in a comfortable reading column.
 */
export function DevotionalArticle({
    devotional,
    headingLevel = 'h1',
    showHeader = true,
}: {
    devotional: PublicDevotional;
    headingLevel?: 'h1' | 'h2';
    showHeader?: boolean;
}) {
    const Heading = headingLevel;

    return (
        <article className="space-y-10">
            {showHeader && (
                <header>
                    <p className="mb-3 font-condensed text-sm tracking-[0.3em] text-muted-foreground uppercase">
                        <time dateTime={devotional.date}>
                            {devotional.dateLabel}
                        </time>
                    </p>
                    <Heading className="font-display text-4xl leading-[0.95] font-black tracking-tight text-balance sm:text-6xl">
                        {devotional.title}
                    </Heading>
                </header>
            )}

            {devotional.verse ? (
                <Verse
                    text={devotional.verse}
                    reference={devotional.reference}
                    className="[&_blockquote]:text-2xl"
                />
            ) : (
                <p className="font-condensed text-sm tracking-[0.2em] text-flame-strong uppercase">
                    {devotional.reference}
                </p>
            )}

            {devotional.body && (
                <div className="space-y-5 text-lg leading-relaxed">
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
