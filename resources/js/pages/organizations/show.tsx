import AppLayout from '@/layouts/app-layout';
import organizations from '@/routes/organizations';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Form, Head, Link, usePage } from '@inertiajs/react';
import { Building2, Users } from 'lucide-react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Organization {
    id: number;
    name: string;
    slug: string;
    parent_id?: number | null;
    owner?: { id: number; name: string; email: string } | null;
    parent?: { id: number; name: string } | null;
}

interface Props {
    organization: Organization;
    organizations?: { id: number; name: string }[];
}

export default function OrganizationsShow() {
    const { organization, organizations = [], flash, errors } = usePage<
        Props & {
            flash?: { status?: string };
            errors?: Record<string, string>;
        } & SharedData
    >().props;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Organizations', href: organizations.index.url() },
        {
            title: organization.name,
            href: organizations.show.url({ organization: organization.slug }),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={organization.name} />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <h2 className="text-lg font-medium">{organization.name}</h2>
                    <Button variant="outline" asChild>
                        <Link
                            href={`/organizations/${organization.slug}/members`}
                        >
                            <Users className="mr-2 size-4" />
                            Members
                        </Link>
                    </Button>
                </div>

                {flash?.status && (
                    <p className="text-sm text-muted-foreground">
                        {flash.status}
                    </p>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Building2 className="size-5" />
                            Settings
                        </CardTitle>
                        <CardDescription>Organization details</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        <Form
                            action={organizations.update.url({
                                organization: organization.slug,
                            })}
                            method="put"
                            disableWhileProcessing
                            className="max-w-md space-y-4"
                        >
                            {({ processing }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">Name</Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            type="text"
                                            required
                                            defaultValue={organization.name}
                                        />
                                        <InputError message={errors?.name} />
                                    </div>
                                    {organizations.length > 0 && (
                                        <div className="grid gap-2">
                                            <Label htmlFor="parent_id">Parent organization</Label>
                                            <select
                                                id="parent_id"
                                                name="parent_id"
                                                defaultValue={organization.parent_id ?? ''}
                                                className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                                            >
                                                <option value="">None</option>
                                                {organizations.map((org) => (
                                                    <option key={org.id} value={org.id}>{org.name}</option>
                                                ))}
                                            </select>
                                            <InputError message={errors?.parent_id} />
                                        </div>
                                    )}
                                    <Button type="submit" disabled={processing}>
                                        {processing ? 'Saving…' : 'Save'}
                                    </Button>
                                </>
                            )}
                        </Form>
                        {organization.owner && (
                            <p className="text-sm text-muted-foreground">
                                Owner: {organization.owner.name} (
                                {organization.owner.email})
                            </p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
