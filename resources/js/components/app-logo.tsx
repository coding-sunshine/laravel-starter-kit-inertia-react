import { cn } from '@/lib/utils';

interface AppLogoProps {
    className?: string;
    wordmarkClassName?: string;
    showWordmark?: boolean;
}

export default function AppLogo({
    className,
    wordmarkClassName,
    showWordmark = true,
}: AppLogoProps) {
    return (
        <div
            className={cn(
                'flex min-w-0 flex-1 items-center gap-2 text-left text-sm',
                className,
            )}
        >
            {/* BGR mountain/triangle SVG mark */}
            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 32 32"
                className="h-8 w-8 shrink-0 group-data-[collapsible=icon]:h-4 group-data-[collapsible=icon]:w-4"
                aria-hidden="true"
            >
                <rect width="32" height="32" rx="6" fill="rgba(200,168,75,0.15)" />
                <polygon
                    points="16,4 29,26 3,26"
                    fill="none"
                    stroke="#C8A84B"
                    strokeWidth="2"
                    strokeLinejoin="round"
                />
                <polygon
                    points="16,10 25,26 7,26"
                    fill="#C8A84B"
                    opacity="0.4"
                />
                <circle cx="16" cy="23" r="2" fill="#C8A84B" />
            </svg>

            {showWordmark ? (
                <div className={cn('flex flex-col group-data-[collapsible=icon]:hidden', wordmarkClassName)}>
                    <span className="truncate font-bold leading-tight text-sidebar-foreground">
                        SHAReReport
                    </span>
                    <span className="truncate text-[9px] font-medium uppercase tracking-widest text-sidebar-foreground/40">
                        BGR Mining &amp; Infra
                    </span>
                </div>
            ) : null}
        </div>
    );
}
