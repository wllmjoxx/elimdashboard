import type { LucideIcon } from 'lucide-react';
import { CalendarDays } from 'lucide-react';

// Sidebar icons of the Kegiatan module, keyed by the 'icon' value used in
// EventsServiceProvider::navigation().
export const icons: Record<string, LucideIcon> = {
    index: CalendarDays,
};
