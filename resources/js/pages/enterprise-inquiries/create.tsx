import EnterpriseInquiryController from '@/actions/App/Http/Controllers/EnterpriseInquiryController';
import { home } from '@/routes';
import { Form, Head, usePage } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';

import HoneypotFields from '@/components/honeypot-fields';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';

export default function EnterpriseInquiriesCreate() {
    const { flash } = usePage<{ flash?: { status?: string } }>().props;

    return (
        <AuthLayout
            title="Enterprise inquiries"
            description="Interested in our enterprise plan? Tell us about your needs."
        >
            <Head title="Enterprise" />
            {flash?.status && (
                <p className="mb-4 text-center text-sm text-muted-foreground">
                    {flash.status}
                </p>
            )}
            <Form
                action={EnterpriseInquiryController.store.url()}
                method="post"
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <HoneypotFields />
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    type="text"
                                    required
                                    autoFocus
                                    autoComplete="name"
                                    name="name"
                                    placeholder="Your name"
                                />
                                <InputError message={errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    required
                                    autoComplete="email"
                                    name="email"
                                    placeholder="you@company.com"
                                />
                                <InputError message={errors.email} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="company">Company</Label>
                                <Input
                                    id="company"
                                    type="text"
                                    autoComplete="organization"
                                    name="company"
                                    placeholder="Your company"
                                />
                                <InputError message={errors.company} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="phone">Phone</Label>
                                <Input
                                    id="phone"
                                    type="tel"
                                    autoComplete="tel"
                                    name="phone"
                                    placeholder="+1 (555) 000-0000"
                                />
                                <InputError message={errors.phone} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="message">Message</Label>
                                <textarea
                                    id="message"
                                    name="message"
                                    required
                                    rows={5}
                                    className="flex min-h-[120px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                    placeholder="Tell us about your needs..."
                                />
                                <InputError message={errors.message} />
                            </div>
                            <Button type="submit" className="w-full">
                                {processing && (
                                    <LoaderCircle className="h-4 w-4 animate-spin" />
                                )}
                                Submit inquiry
                            </Button>
                        </div>
                        <div className="text-center text-sm text-muted-foreground">
                            <TextLink href={home()}>Back to home</TextLink>
                        </div>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}
