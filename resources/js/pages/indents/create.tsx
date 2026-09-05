import { IndentCreateForm } from '@/components/indents/indent-create-form';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

interface Siding {
    id: number;
    name: string;
    code: string;
}

interface PowerPlant {
    name: string;
    code: string;
}

interface Props {
    sidings: Siding[];
    power_plants: PowerPlant[];
}

export default function IndentsCreate({ sidings, power_plants }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'E-Demand', href: '/indents' },
        { title: 'Create e-demand', href: '/indents/create' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create e-demand" />
            <IndentCreateForm
                sidings={sidings}
                power_plants={power_plants}
                variant="page"
            />
        </AppLayout>
    );
}
