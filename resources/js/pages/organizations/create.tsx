import OrganizationController from '@/actions/App/Http/Controllers/OrganizationController';
import AppLayout from '@/layouts/app-layout';
import organizations from '@/routes/organizations';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Form, Head, Link, usePage } from '@inertiajs/react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Organizations', href: organizations.index.url() },
    { title: 'Create', href: OrganizationController.create.url() },
];

interface Props {
    organizations?: { id: number; name: string }[];
}

export default function OrganizationsCreate({ organizations = [] }: Props) {
    const { flash } = usePage<
        {
            flash?: { status?: string };
            errors?: Record<string, string>;
        } & SharedData
    >().props;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create organization" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <h2 className="text-lg font-medium">Create organization</h2>

                {flash?.status && (
                    <p className="text-sm text-muted-foreground">
                        {flash.status}
                    </p>
                )}

                <Form
                    action={OrganizationController.store.url()}
                    method="post"
                    disableWhileProcessing
                    className="max-w-md space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    type="text"
                                    required
                                    autoFocus
                                    placeholder="Acme Inc."
                                />
                                <InputError message={errors.name} />
                            </div>
                            {organizations.length > 0 && (
                                <div className="grid gap-2">
                                    <Label htmlFor="parent_id">Parent organization</Label>
                                    <select
                                        id="parent_id"
                                        name="parent_id"
                                        className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                                    >
                                        <option value="">None</option>
                                        {organizations.map((org) => (
                                            <option key={org.id} value={org.id}>{org.name}</option>
                                        ))}
                                    </select>
                                    <InputError message={errors.parent_id} />
                                </div>
                            )}
                            <div className="flex gap-2">
                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Creating…' : 'Create'}
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href={organizations.index.url()}>
                                        Cancel
                                    </Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
