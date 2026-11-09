import type { LucideIcon } from 'lucide-react';
import { HandHeart, History } from 'lucide-react';

// Sidebar icons of the Doa Mingguan module, keyed by the 'icon' value used in
// PrayerServiceProvider::navigation().
export const icons: Record<string, LucideIcon> = {
    index: HandHeart,
    history: History,
};
