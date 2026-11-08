import type { LucideIcon } from 'lucide-react';
import { Monitor, Moon, Sun } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { Appearance } from '@/hooks/use-appearance';
import { useAppearance } from '@/hooks/use-appearance';

const options: { value: Appearance; label: string; icon: LucideIcon }[] = [
    { value: 'light', label: 'Terang', icon: Sun },
    { value: 'dark', label: 'Gelap', icon: Moon },
    { value: 'system', label: 'Ikuti sistem', icon: Monitor },
];

/**
 * Navbar theme switch: Terang, Gelap or Ikuti sistem. The choice is saved
 * (localStorage + cookie) by useAppearance.
 */
export function AppearanceMenu() {
    const { appearance, resolvedAppearance, updateAppearance } =
        useAppearance();
    const CurrentIcon = resolvedAppearance === 'dark' ? Moon : Sun;

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="glass"
                    size="icon"
                    className="rounded-full"
                    aria-label="Ganti tema"
                >
                    <CurrentIcon />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="min-w-40">
                <DropdownMenuRadioGroup
                    value={appearance}
                    onValueChange={(value) =>
                        updateAppearance(value as Appearance)
                    }
                >
                    {options.map(({ value, label, icon: Icon }) => (
                        <DropdownMenuRadioItem key={value} value={value}>
                            <Icon className="size-4" />
                            {label}
                        </DropdownMenuRadioItem>
                    ))}
                </DropdownMenuRadioGroup>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
