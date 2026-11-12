import type { LucideIcon } from 'lucide-react';
import { Megaphone } from 'lucide-react';

// Sidebar icons of the Pengumuman module, keyed by the 'icon' value used in
// AnnouncementsServiceProvider::navigation().
export const icons: Record<string, LucideIcon> = {
    index: Megaphone,
};
