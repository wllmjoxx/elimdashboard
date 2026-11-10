import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    BookOpen,
    HandHeart,
    Home,
    LayoutGrid,
    Quote,
    UserRound,
} from 'lucide-react';
import Heading from '@/components/heading';
import { moduleIcon } from '@/lib/module-icons';
import { cn } from '@/lib/utils';
import { dashboard, home } from '@/routes';

type Card = {
    module: string;
    key: string;
    title: string;
    description: string | null;
    value: number;
    href: string;
    icon: string | null;
    highlight: boolean;
};

const shortcuts = [
    { title: 'Profil Saya', href: '/profil', icon: UserRound },
    { title: 'Kirim pokok doa', href: '/doa', icon: HandHeart },
    { title: 'Renungan hari ini', href: '/renungan', icon: BookOpen },
    { title: 'Bagikan kesaksian', href: '/kesaksian#bagikan', icon: Quote },
    { title: 'Ke halaman Home', href: home.url(), icon: Home },
];

const number = new Intl.NumberFormat('id-ID');

function StatCard({ card }: { card: Card }) {
    const Icon = moduleIcon(card.module, card.icon) ?? LayoutGrid;

    return (
        <Link
            href={card.href}
            className={cn(
                'group flex flex-col gap-3 rounded-xl border p-5 transition-colors hover:border-primary focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none',
                card.highlight && 'border-flame/50 bg-flame/5',
            )}
        >
            <div className="flex items-center justify-between gap-3">
                <span className="text-sm font-medium text-muted-foreground">
                    {card.title}
                </span>
                <span
                    className={cn(
                        'grid size-9 place-items-center rounded-full bg-muted text-muted-foreground',
                        card.highlight && 'bg-flame/15 text-flame-strong',
                    )}
                >
                    <Icon className="size-4" />
                </span>
            </div>
            <span className="text-3xl font-semibold tabular-nums">
                {number.format(card.value)}
            </span>
            <span className="flex items-center justify-between gap-2 text-sm text-muted-foreground">
                {card.description}
                <ArrowRight className="size-4 shrink-0 opacity-0 transition-opacity group-hover:opacity-100" />
            </span>
        </Link>
    );
}

/** Dashboard (PRD 4.8): number cards from the modules the user may see. */
export default function Dashboard({ cards }: { cards: Card[] }) {
    const { auth } = usePage().props;
    const needsAction = cards.filter((card) => card.highlight);
    const firstName = auth.user.name.split(' ')[0];

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex flex-1 flex-col gap-8 p-4">
                <Heading
                    title={`Shalom, ${firstName}!`}
                    description={
                        needsAction.length > 0
                            ? `Ada ${needsAction.length} hal yang menunggu tindakan Anda.`
                            : 'Semua beres. Tuhan Yesus memberkati pelayanan Anda hari ini.'
                    }
                />

                {cards.length > 0 && (
                    <section
                        aria-label="Ringkasan"
                        className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
                    >
                        {cards.map((card) => (
                            <StatCard key={card.key} card={card} />
                        ))}
                    </section>
                )}

                <section
                    aria-labelledby="pintasan"
                    className="flex flex-col gap-3"
                >
                    <h2
                        id="pintasan"
                        className="text-sm font-medium text-muted-foreground"
                    >
                        Pintasan
                    </h2>
                    <div className="flex flex-wrap gap-2">
                        {shortcuts.map(({ title, href, icon: Icon }) => (
                            <Link
                                key={href}
                                href={href}
                                className="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-medium transition-colors hover:bg-muted"
                            >
                                <Icon className="size-4" />
                                {title}
                            </Link>
                        ))}
                    </div>
                </section>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
