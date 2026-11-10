import type { LucideIcon } from 'lucide-react';
import { Building2, ScrollText } from 'lucide-react';

// Sidebar icons of the Cabang & Akses module, keyed by the 'icon' value used in
// CoreServiceProvider::navigation().
export const icons: Record<string, LucideIcon> = {
    index: Building2,
    audit: ScrollText,
};
