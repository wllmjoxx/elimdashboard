import type { LucideIcon } from 'lucide-react';
import { UserRound, Users } from 'lucide-react';

// Sidebar icons of the Jemaat module, keyed by the 'icon' value used in
// MembersServiceProvider::navigation().
export const icons: Record<string, LucideIcon> = {
    index: Users,
    profile: UserRound,
};
