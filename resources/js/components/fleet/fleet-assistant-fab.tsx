import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Link, router, usePage } from '@inertiajs/react';
import { Bot, MessageSquare } from 'lucide-react';
import { useEffect, useState } from 'react';

const SUGGESTIONS = [
    'What needs attention?',
    'Show driver safety trends',
    'Predict maintenance costs',
    'Fleet health summary',
];

const PULSE_SEEN_KEY = 'fleet-assistant-fab-pulse-seen';

export function FleetAssistantFab() {
    const page = usePage();
    const url = page.url;
    const isFleet = url.startsWith('/fleet');
    const isAssistantPage = url.startsWith('/fleet/assistant');
    const [open, setOpen] = useState(false);
    const [showPulse, setShowPulse] = useState(false);

    useEffect(() => {
        if (!isFleet || isAssistantPage) return;
        try {
            if (!localStorage.getItem(PULSE_SEEN_KEY)) {
                setShowPulse(true);
                localStorage.setItem(PULSE_SEEN_KEY, '1');
                const timer = setTimeout(() => setShowPulse(false), 3000);
                return () => clearTimeout(timer);
            }
        } catch {
            // localStorage unavailable
        }
    }, [isFleet, isAssistantPage]);

    if (!isFleet || isAssistantPage) return null;

    const handleSuggestion = (text: string) => {
        setOpen(false);
        router.visit(`/fleet/assistant?prompt=${encodeURIComponent(text)}`);
    };

    return (
        <>
            <Button
                size="icon"
                className="fleet-assistant-fab fixed right-6 bottom-6 z-50 size-14 rounded-full shadow-lg focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                onClick={() => setOpen(true)}
                title="Open Fleet Assistant"
                aria-label="Open Fleet Assistant"
            >
                {showPulse && (
                    <span className="absolute inset-0 animate-ping rounded-full bg-primary/40" />
                )}
                <span className="fleet-assistant-fab-inner relative flex size-full items-center justify-center rounded-full bg-primary text-primary-foreground shadow-md transition-shadow duration-200">
                    <Bot className="size-7" />
                </span>
            </Button>

            <Sheet open={open} onOpenChange={setOpen}>
                <SheetContent side="right" className="w-full sm:max-w-md">
                    <SheetHeader className="pb-4">
                        <div className="flex items-center gap-3">
                            <div className="flex size-10 items-center justify-center rounded-full bg-primary/10 text-primary">
                                <Bot className="size-5" />
                            </div>
                            <div>
                                <SheetTitle className="text-lg">
                                    Fleet Assistant
                                </SheetTitle>
                                <SheetDescription>
                                    Ask about vehicles, drivers, trips, and
                                    more.
                                </SheetDescription>
                            </div>
                        </div>
                    </SheetHeader>
                    <div className="flex flex-1 flex-col gap-4 px-1">
                        <p className="text-sm font-medium text-foreground">
                            Try asking:
                        </p>
                        <div className="flex flex-col gap-2">
                            {SUGGESTIONS.map((text, i) => (
                                <button
                                    key={text}
                                    type="button"
                                    onClick={() => handleSuggestion(text)}
                                    className="flex items-center gap-2 rounded-lg border border-border bg-muted/30 px-4 py-3 text-left text-sm transition-colors hover:border-primary/40 hover:bg-muted/60 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                    style={{
                                        animation:
                                            'fadeInUp 0.35s ease-out forwards',
                                        opacity: 0,
                                        animationDelay: `${i * 0.06}s`,
                                    }}
                                >
                                    <MessageSquare className="size-4 shrink-0 text-muted-foreground" />
                                    <span>{text}</span>
                                </button>
                            ))}
                        </div>
                        <div className="mt-4 border-t border-border pt-4">
                            <Button asChild className="w-full" size="sm">
                                <Link href="/fleet/assistant">
                                    <MessageSquare className="mr-2 size-4" />
                                    Open full assistant
                                </Link>
                            </Button>
                        </div>
                    </div>
                </SheetContent>
            </Sheet>
            <style>{`
                .fleet-assistant-fab {
                    animation: fleetFabIn 0.4s ease-out;
                    transition: transform 0.25s ease-out, box-shadow 0.25s ease-out;
                }
                .fleet-assistant-fab:hover {
                    transform: scale(1.12);
                    box-shadow: 0 12px 28px -8px rgb(0 0 0 / 0.25), 0 0 0 1px hsl(var(--primary) / 0.3);
                }
                .fleet-assistant-fab:hover .fleet-assistant-fab-inner {
                    box-shadow: 0 4px 14px -2px hsl(var(--primary) / 0.4);
                }
                .fleet-assistant-fab:active {
                    transform: scale(1.02);
                }
                @keyframes fleetFabIn {
                    from {
                        opacity: 0;
                        transform: scale(0.8);
                    }
                    to {
                        opacity: 1;
                        transform: scale(1);
                    }
                }
                @keyframes fadeInUp {
                    from {
                        opacity: 0;
                        transform: translateY(8px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
            `}</style>
        </>
    );
}
