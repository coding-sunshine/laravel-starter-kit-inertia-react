import { edit as editPersonalDataExport } from '@/routes/personal-data-export';
import { store as storePersonalDataExport } from '@/routes/personal-data-export';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Form, Head, usePage } from '@inertiajs/react';

import HeadingSmall from '@/components/heading-small';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { Button } from '@/components/ui/button';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Data export',
        href: editPersonalDataExport().url,
    },
];

export default function PersonalDataExport() {
    const { flash } = usePage<SharedData & { flash?: { status?: string } }>().props;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Data export" />
            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title="Download your data"
                        description="Request a copy of your personal data. We will prepare a zip file and send you an email with a download link when it is ready. The link will expire after a few days."
                    />
                    {flash?.status && (
                        <p className="text-sm text-muted-foreground">
                            {flash.status}
                        </p>
                    )}
                    <Form
                        action={storePersonalDataExport().url}
                        method="post"
                        className="flex items-center gap-4"
                    >
                        {({ processing }) => (
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Requesting…' : 'Request data export'}
                            </Button>
                        )}
                    </Form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
