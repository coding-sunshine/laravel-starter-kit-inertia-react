import AppLogo from '@/components/app-logo';
import { type PropsWithChildren } from 'react';

interface AuthLayoutProps {
    title?: string;
    description?: string;
}

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: PropsWithChildren<AuthLayoutProps>) {
    return (
        <div className="flex min-h-svh items-center justify-center bg-[oklch(0.96_0.008_150)] p-6 md:p-10">
            <div className="flex w-full max-w-4xl overflow-hidden rounded-2xl shadow-xl">
                {/* Left: BGR identity panel */}
                <div className="hidden flex-shrink-0 flex-col bg-[oklch(0.22_0.06_150)] p-10 text-white md:flex md:w-[320px]">
                    <div className="mb-8">
                        <AppLogo showWordmark={false} className="flex-none" />
                    </div>

                    <h2 className="text-xl font-bold leading-snug">
                        Railway Rack Management System
                    </h2>
                    <p className="mt-3 text-sm leading-relaxed text-white/50">
                        BGR Mining &amp; Infra Limited — Coal logistics intelligence platform
                    </p>

                    <div className="my-8 h-px bg-white/10" />

                    <div className="space-y-5">
                        <div>
                            <div className="font-mono text-2xl font-bold text-[oklch(0.72_0.12_80)]">
                                3 Sidings
                            </div>
                            <div className="mt-1 text-xs text-white/40">
                                Dumka · Kurwa · Pakur
                            </div>
                        </div>
                        <div>
                            <div className="font-mono text-2xl font-bold text-[oklch(0.72_0.12_80)]">
                                5 Plants
                            </div>
                            <div className="mt-1 text-xs text-white/40">
                                STPS · BTPC · KPPS · PSPM · BTMT
                            </div>
                        </div>
                    </div>

                    <div className="mt-auto text-xs text-white/25">
                        © BGR Mining &amp; Infra Limited
                    </div>
                </div>

                {/* Right: form */}
                <div className="flex flex-1 flex-col justify-center bg-white p-8 md:p-12">
                    <div className="mb-8 space-y-1">
                        <h1 className="text-2xl font-bold text-[oklch(0.22_0.06_150)]">
                            {title}
                        </h1>
                        {description && (
                            <p className="text-sm text-muted-foreground">
                                {description}
                            </p>
                        )}
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
