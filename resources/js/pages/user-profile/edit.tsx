import { useInitials } from '@/hooks/use-initials';
import { send } from '@/routes/verification';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';

import HeadingSmall from '@/components/heading-small';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import userProfile from '@/routes/user-profile';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: userProfile.edit().url,
    },
];

export default function Edit({ status }: { status?: string }) {
    const { auth } = usePage<SharedData>().props;
    const getInitials = useInitials();
    const avatarUrl = auth.user.avatar_profile ?? auth.user.avatar ?? undefined;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Profile settings" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title="Profile information"
                        description="Your name and email address"
                    />

                    <div className="space-y-6">
                        <div className="flex items-center gap-4">
                            <Avatar className="size-20 overflow-hidden rounded-full">
                                <AvatarImage
                                    alt={auth.user.name}
                                    src={avatarUrl}
                                />
                                <AvatarFallback className="rounded-full bg-neutral-200 text-2xl text-black dark:bg-neutral-700 dark:text-white">
                                    {getInitials(auth.user.name)}
                                </AvatarFallback>
                            </Avatar>
                            <div className="grid gap-1">
                                <p className="text-sm font-medium text-muted-foreground">
                                    Name
                                </p>
                                <p className="text-base">{auth.user.name}</p>
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <p className="text-sm font-medium text-muted-foreground">
                                Email address
                            </p>
                            <p className="text-base">{auth.user.email}</p>
                        </div>

                        {auth.user.email_verified_at === null && (
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Your email address is unverified.{' '}
                                    <Link
                                        href={send()}
                                        as="button"
                                        className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                    >
                                        Click here to resend the verification
                                        email.
                                    </Link>
                                </p>

                                {status === 'verification-link-sent' && (
                                    <div className="mt-2 text-sm font-medium text-green-600">
                                        A new verification link has been sent to
                                        your email address.
                                    </div>
                                )}
                            </div>
                        )}
                    </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
