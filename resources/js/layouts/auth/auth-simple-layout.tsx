import AppLogoIcon from '@/components/app-logo-icon';
import { home } from '@/routes';
import { Link } from '@inertiajs/react';
import { type PropsWithChildren } from 'react';

interface AuthLayoutProps {
    name?: string;
    title?: string;
    description?: string;
}

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: PropsWithChildren<AuthLayoutProps>) {
    return (
        <div className="flex min-h-svh flex-col items-center justify-center bg-background p-4 sm:p-6 md:p-10">
            {/* Subtle primary accent strip at top */}
            <div className="fixed left-0 right-0 top-0 h-1 bg-primary" aria-hidden />

            <div className="w-full max-w-md">
                <div className="rounded-xl border border-border bg-card px-6 py-8 shadow-sm sm:px-8 sm:py-10">
                    <div className="flex flex-col gap-8">
                        <div className="flex flex-col items-center gap-5">
                            <Link
                                href={home()}
                                className="flex flex-col items-center gap-2 font-semibold text-foreground outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 rounded-md"
                            >
                                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                    <AppLogoIcon className="size-8 fill-current" />
                                </div>
                                <span className="sr-only">{title}</span>
                            </Link>

                            <div className="space-y-1.5 text-center">
                                <h1 className="text-2xl font-semibold tracking-tight text-foreground">
                                    {title}
                                </h1>
                                {description && (
                                    <p className="text-sm text-muted-foreground">
                                        {description}
                                    </p>
                                )}
                            </div>
                        </div>
                        {children}
                    </div>
                </div>
            </div>
        </div>
    );
}
