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
            <img
                src="/logo.png"
                alt=""
                className="h-8 w-8 shrink-0 rounded-md object-contain group-data-[collapsible=icon]:h-4 group-data-[collapsible=icon]:w-4"
                width={32}
                height={32}
            />
            {showWordmark ? (
                <span
                    className={cn(
                        'truncate font-semibold leading-tight group-data-[collapsible=icon]:hidden',
                        wordmarkClassName,
                    )}
                >
                    RMMS
                </span>
            ) : null}
        </div>
    );
}
