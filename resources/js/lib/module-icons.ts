import type { LucideIcon } from 'lucide-react';

type IconsFile = { icons: Record<string, LucideIcon> };

// Every module ships its own resources/js/modules/<key>/icons.ts, so adding a
// sidebar item never edits a shared icon list.
const iconFiles = import.meta.glob<IconsFile>('../modules/*/icons.ts', {
    eager: true,
});

export function moduleIcon(
    module: string,
    name: string | null,
): LucideIcon | null {
    if (!name) {
        return null;
    }

    return iconFiles[`../modules/${module}/icons.ts`]?.icons[name] ?? null;
}
