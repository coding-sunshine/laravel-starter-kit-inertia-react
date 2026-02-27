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
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Train } from 'lucide-react';

interface Siding {
    id: number;
    name: string;
    code: string;
}

interface Indent {
    id: number;
    indent_number: string | null;
    state: string;
    expected_loading_date: string | null;
    demanded_stock: string | null;
    total_units: string | null;
    siding?: Siding | null;
}

interface Props {
    indent: Indent;
    sidings: Siding[];
}

export default function CreateRakeFromIndent({ indent, sidings }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Indents', href: '/indents' },
        { title: indent.indent_number || 'N/A', href: `/indents/${indent.id}` },
        { title: 'Create Rake', href: `/indents/${indent.id}/create-rake` },
    ];

    const { data, setData, post, processing, errors, reset } = useForm({
        rake_type: indent.demanded_stock || '',
        wagon_count: indent.total_units || '',
        free_time_minutes: '180',
        rr_expected_date: '',
        placement_time: '',
        remarks: '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post(`/indents/${indent.id}/store-rake`, {
            onFinish: () => reset(),
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Create Rake for ${indent.indent_number || 'N/A'}`} />

            <div className="space-y-6">
                <div className="flex items-center gap-4">
                    <Link href={`/indents/${indent.id}`}>
                        <Button variant="outline" size="sm">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Back to Indent
                        </Button>
                    </Link>
                    <h2 className="text-lg font-medium">
                        Create Rake from Indent {indent.indent_number || 'N/A'}
                    </h2>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Train className="h-5 w-5" />
                            Rake Information
                        </CardTitle>
                        <CardDescription>
                            Create a railway rake based on the completed indent. Common fields are pre-filled from the indent.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-6">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <Label htmlFor="indent_number">Indent Number</Label>
                                    <Input
                                        id="indent_number"
                                        value={indent.indent_number || 'N/A'}
                                        disabled
                                        className="bg-muted"
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="rake_number">Rake Number</Label>
                                    <Input
                                        id="rake_number"
                                        value={indent.indent_number ? `RK-${indent.indent_number}` : 'RK-'}
                                        disabled
                                        className="bg-muted"
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="siding">Siding</Label>
                                    <Input
                                        id="siding"
                                        value={indent.siding ? `${indent.siding.name} (${indent.siding.code})` : '—'}
                                        disabled
                                        className="bg-muted"
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="expected_loading_date">Expected Loading Date</Label>
                                    <Input
                                        id="expected_loading_date"
                                        value={indent.expected_loading_date ? new Date(indent.expected_loading_date).toLocaleDateString() : '—'}
                                        disabled
                                        className="bg-muted"
                                    />
                                </div>
                            </div>

                            <div className="border-t pt-6">
                                <h3 className="text-lg font-medium mb-4">Rake Details</h3>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <Label htmlFor="rake_type">Rake Type</Label>
                                        <Input
                                            id="rake_type"
                                            value={data.rake_type}
                                            onChange={(e) => setData('rake_type', e.target.value)}
                                            placeholder={indent.demanded_stock || 'e.g., BOBRN, BOXN'}
                                        />
                                        {errors.rake_type && (
                                            <p className="text-sm text-destructive mt-1">{errors.rake_type}</p>
                                        )}
                                    </div>
                                    <div>
                                        <Label htmlFor="wagon_count">Wagon Count</Label>
                                        <Input
                                            id="wagon_count"
                                            type="number"
                                            min="0"
                                            value={data.wagon_count}
                                            onChange={(e) => setData('wagon_count', e.target.value)}
                                            placeholder={indent.total_units || 'Number of wagons'}
                                        />
                                        {errors.wagon_count && (
                                            <p className="text-sm text-destructive mt-1">{errors.wagon_count}</p>
                                        )}
                                    </div>
                                    <div>
                                        <Label htmlFor="free_time_minutes">Free Time (Minutes)</Label>
                                        <Input
                                            id="free_time_minutes"
                                            type="number"
                                            min="0"
                                            value={data.free_time_minutes}
                                            onChange={(e) => setData('free_time_minutes', e.target.value)}
                                            placeholder="Free time before demurrage"
                                        />
                                        {errors.free_time_minutes && (
                                            <p className="text-sm text-destructive mt-1">{errors.free_time_minutes}</p>
                                        )}
                                    </div>
                                </div>
                            </div>

                            <div>
                                <Label htmlFor="remarks">Remarks</Label>
                                <textarea
                                    id="remarks"
                                    value={data.remarks}
                                    onChange={(e) => setData('remarks', e.target.value)}
                                    placeholder="Additional notes about this rake"
                                    className="min-h-[100px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    maxLength={1000}
                                />
                                {errors.remarks && (
                                    <p className="text-sm text-destructive mt-1">{errors.remarks}</p>
                                )}
                            </div>

                            <div className="flex justify-end gap-4 pt-6 border-t">
                                <Link href={`/indents/${indent.id}`}>
                                    <Button type="button" variant="outline">
                                        Cancel
                                    </Button>
                                </Link>
                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Creating...' : 'Create Rake'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
