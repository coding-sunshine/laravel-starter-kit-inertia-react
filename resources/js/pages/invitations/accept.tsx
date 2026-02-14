import { accept as invitationsAccept } from '@/routes/invitations';
import { type SharedData } from '@/types';
import { Form, Head, Link, usePage } from '@inertiajs/react';
import { Building2 } from 'lucide-react';

import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/auth-layout';

interface InvitationData {
    token: string;
    email: string;
    role: string;
    organization: { id: number; name: string; slug: string };
    inviter: { name: string };
    expires_at: string;
}

interface Props {
    invitation: InvitationData;
}

export default function InvitationsAccept() {
    const { invitation, auth } = usePage<
        Props & { errors?: Record<string, string> } & SharedData
    >().props;

    const isLoggedIn = !!auth.user;
    const emailMatches =
        isLoggedIn &&
        auth.user &&
        auth.user.email.toLowerCase() === invitation.email.toLowerCase();

    return (
        <AuthLayout
            title="Organization invitation"
            description={`You have been invited to join ${invitation.organization.name} as ${invitation.role}.`}
        >
            <Head title="Accept invitation" />
            <div className="space-y-6">
                <div className="flex items-center gap-3 rounded-lg border p-4">
                    <Building2 className="size-10 text-muted-foreground" />
                    <div>
                        <p className="font-medium">
                            {invitation.organization.name}
                        </p>
                        <p className="text-sm text-muted-foreground">
                            {invitation.inviter.name} invited you as{' '}
                            <span className="capitalize">
                                {invitation.role}
                            </span>
                        </p>
                        <p className="text-xs text-muted-foreground">
                            Invitation sent to {invitation.email} · Expires{' '}
                            {new Date(
                                invitation.expires_at,
                            ).toLocaleDateString()}
                        </p>
                    </div>
                </div>

                {!isLoggedIn ? (
                    <p className="text-center text-sm text-muted-foreground">
                        <Link
                            href="/login"
                            className="font-medium text-primary underline-offset-4 hover:underline"
                        >
                            Log in
                        </Link>
                        {' or '}
                        <Link
                            href="/register"
                            className="font-medium text-primary underline-offset-4 hover:underline"
                        >
                            create an account
                        </Link>
                        {' to accept this invitation.'}
                    </p>
                ) : !emailMatches ? (
                    <p className="text-center text-sm text-muted-foreground">
                        This invitation was sent to{' '}
                        <strong>{invitation.email}</strong>. You are logged in
                        as <strong>{auth.user?.email}</strong>. Please log in
                        with the invited email to accept.
                    </p>
                ) : (
                    <Form
                        action={invitationsAccept.url(invitation.token)}
                        method="post"
                        disableWhileProcessing
                        className="flex justify-center"
                    >
                        <Button type="submit">Accept invitation</Button>
                    </Form>
                )}
            </div>
        </AuthLayout>
    );
}
