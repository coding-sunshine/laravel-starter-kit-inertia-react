import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { Check, Copy, Twitter } from 'lucide-react';
import { useState } from 'react';

const achievementsUrl = () => '/settings/achievements';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Level & achievements',
        href: achievementsUrl(),
    },
];

interface AchievementItem {
    id: number;
    name: string;
    description: string | null;
    image: string | null;
    is_secret: boolean;
    progress: number | null;
    unlocked_at: string | null;
}

interface Props {
    level: number;
    points: number;
    next_level_percentage: number;
    achievements: AchievementItem[];
}

const shareUrl = () =>
    typeof window !== 'undefined' ? window.location.href : '';

export default function Achievements({
    level,
    points,
    next_level_percentage,
    achievements,
}: Props) {
    const [copied, setCopied] = useState(false);

    const handleCopyLink = async () => {
        await navigator.clipboard.writeText(shareUrl());
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    const twitterShareUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent('Check out my achievements!')}&url=${encodeURIComponent(shareUrl())}`;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Level & achievements" />
            <SettingsLayout>
                <div className="space-y-8">
                    <HeadingSmall
                        title="Level & achievements"
                        description="Your experience points, level, and unlocked achievements"
                    />

                    <div className="rounded-lg border bg-card p-4 text-card-foreground shadow-sm">
                        <div className="flex flex-wrap items-center gap-4">
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Level
                                </p>
                                <p className="text-2xl font-semibold">
                                    {level}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    XP
                                </p>
                                <p className="text-2xl font-semibold">
                                    {points}
                                </p>
                            </div>
                            {next_level_percentage < 100 && (
                                <div className="min-w-[120px] flex-1">
                                    <p className="mb-1 text-sm text-muted-foreground">
                                        Progress to next level
                                    </p>
                                    <div className="h-2 w-full overflow-hidden rounded-full bg-muted">
                                        <div
                                            className="h-full rounded-full bg-primary transition-all"
                                            style={{
                                                width: `${next_level_percentage}%`,
                                            }}
                                        />
                                    </div>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        {next_level_percentage}%
                                    </p>
                                </div>
                            )}
                        </div>
                    </div>

                    <div className="flex flex-wrap items-center justify-between gap-2">
                        <h3 className="text-sm font-medium">
                            Unlocked achievements
                        </h3>
                        <div className="flex items-center gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={handleCopyLink}
                                className="gap-1.5"
                            >
                                {copied ? (
                                    <Check className="size-4" />
                                ) : (
                                    <Copy className="size-4" />
                                )}
                                {copied ? 'Copied' : 'Copy link'}
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                asChild
                                className="gap-1.5"
                            >
                                <a
                                    href={twitterShareUrl}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <Twitter className="size-4" /> Share on X
                                </a>
                            </Button>
                        </div>
                    </div>
                    {achievements.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No achievements yet. Complete onboarding and other
                            actions to earn some.
                        </p>
                    ) : (
                        <ul className="space-y-3">
                            {achievements.map((a) => (
                                <li
                                    key={a.id}
                                    className="flex items-start gap-3 rounded-lg border p-3"
                                >
                                    {a.image ? (
                                        <img
                                            src={a.image}
                                            alt=""
                                            className="h-10 w-10 rounded object-cover"
                                        />
                                    ) : (
                                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded bg-muted text-lg">
                                            🏆
                                        </div>
                                    )}
                                    <div className="min-w-0 flex-1">
                                        <p className="font-medium">{a.name}</p>
                                        {a.description && (
                                            <p className="text-sm text-muted-foreground">
                                                {a.description}
                                            </p>
                                        )}
                                        {a.unlocked_at ? (
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                Unlocked{' '}
                                                {new Date(
                                                    a.unlocked_at,
                                                ).toLocaleDateString()}
                                            </p>
                                        ) : a.progress != null &&
                                          a.progress < 100 ? (
                                            <div className="mt-2">
                                                <div className="h-1.5 w-full overflow-hidden rounded-full bg-muted">
                                                    <div
                                                        className="h-full rounded-full bg-primary transition-all"
                                                        style={{
                                                            width: `${a.progress}%`,
                                                        }}
                                                    />
                                                </div>
                                                <p className="mt-1 text-xs text-muted-foreground">
                                                    {a.progress}%
                                                </p>
                                            </div>
                                        ) : null}
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
