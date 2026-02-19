import Heading from '@/components/heading';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Receipt } from 'lucide-react';

interface Rake {
    id: number;
    rake_number: string;
}

interface PowerPlant {
    id: number;
    name: string;
    code: string;
}

interface Receipt {
    id: number;
    rake_id: number;
    power_plant_id: number;
    receipt_date: string;
    weight_mt: string;
    rr_reference: string | null;
    status: string;
    rake?: Rake | null;
    power_plant?: PowerPlant | null;
}

interface PaginatorLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Props {
    receipts: {
        data: Receipt[];
        links: PaginatorLink[];
        last_page: number;
    };
    rakes: Rake[];
    powerPlants: PowerPlant[];
}

export default function PowerPlantReceiptsIndex({
    receipts,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Reconciliation', href: '/reconciliation' },
        { title: 'Power plant receipts', href: '/reconciliation/power-plant-receipts' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Power plant receipts" />
            <div className="space-y-6">
                <Heading
                    title="Power plant receipts"
                    description="RR vs power plant receipt records"
                />
                <div className="flex flex-wrap items-center gap-2">
                    <Link href="/reconciliation/power-plant-receipts/create">
                        <Button>Add power plant receipt</Button>
                    </Link>
                    <Link href="/reconciliation">
                        <Button variant="outline">Back to reconciliation</Button>
                    </Link>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Receipts</CardTitle>
                        <CardDescription>By rake and power plant</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {receipts.data.length === 0 ? (
                            <div className="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground">
                                <Receipt className="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
                                <p>No power plant receipts yet.</p>
                            </div>
                        ) : (
                            <>
                                <div className="overflow-x-auto rounded-md border">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b bg-muted/50">
                                                <th className="px-5 py-3.5 text-left font-medium">Rake</th>
                                                <th className="px-5 py-3.5 text-left font-medium">Power plant</th>
                                                <th className="px-5 py-3.5 text-left font-medium">Date</th>
                                                <th className="px-5 py-3.5 text-right font-medium">Weight (MT)</th>
                                                <th className="px-5 py-3.5 text-left font-medium">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {receipts.data.map((r) => (
                                                <tr key={r.id} className="border-b last:border-0 hover:bg-muted/30">
                                                    <td className="px-5 py-3.5 font-medium">{r.rake?.rake_number ?? r.rake_id}</td>
                                                    <td className="px-5 py-3.5">{r.power_plant?.name ?? r.power_plant_id}</td>
                                                    <td className="px-5 py-3.5">{r.receipt_date}</td>
                                                    <td className="px-5 py-3.5 text-right">{r.weight_mt}</td>
                                                    <td className="px-5 py-3.5">{r.status}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                                {receipts.last_page > 1 && (
                                    <nav className="mt-6 flex flex-wrap items-center justify-center gap-4 pt-2">
                                        {receipts.links.map((link) => (
                                            <button
                                                key={link.label}
                                                type="button"
                                                disabled={!link.url}
                                                className="rounded-md border border-input px-4 py-2.5 text-sm disabled:opacity-50"
                                                onClick={() => link.url && router.get(link.url)}
                                            >
                                                {link.label}
                                            </button>
                                        ))}
                                    </nav>
                                )}
                            </>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
