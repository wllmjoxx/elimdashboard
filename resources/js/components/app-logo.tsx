import AppLogoIcon from '@/components/app-logo-icon';

/** Sidebar wordmark, matching the public site. */
export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center rounded-full border border-foreground/20">
                <AppLogoIcon className="size-5 fill-flame" />
            </div>
            <div className="ml-1 grid flex-1 text-left leading-none">
                <span className="truncate font-display text-sm font-extrabold tracking-wide">
                    GGP ELIM
                </span>
                <span className="mt-1 truncate font-condensed text-[10px] tracking-[0.24em] text-muted-foreground">
                    RUMAH KASIH KARUNIA
                </span>
            </div>
        </>
    );
}
