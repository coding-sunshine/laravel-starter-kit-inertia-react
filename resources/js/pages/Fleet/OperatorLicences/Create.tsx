import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';

interface Option {
    value: string;
    name: string;
}
interface Props {
    statuses: Option[];
}

const LICENSE_TYPES = [
    { value: 'standard_national', name: 'Standard national' },
    { value: 'standard_international', name: 'Standard international' },
    { value: 'restricted', name: 'Restricted' },
];
const AREAS = [
    { value: 'north_eastern', name: 'North Eastern' },
    { value: 'north_western', name: 'North Western' },
    { value: 'west_midlands', name: 'West Midlands' },
    { value: 'eastern', name: 'Eastern' },
    { value: 'western', name: 'Western' },
    { value: 'southern', name: 'Southern' },
    { value: 'scottish', name: 'Scottish' },
];

export default function FleetOperatorLicencesCreate({ statuses }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        license_number: '',
        license_type: 'standard_national',
        traffic_commissioner_area: 'north_eastern',
        issue_date: '',
        effective_date: '',
        expiry_date: '',
        authorized_vehicles: 0,
        authorized_trailers: '' as number | '',
        operating_centres: [{ name: '', address: '' }] as {
            name: string;
            address: string;
        }[],
        status: 'active',
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/operator-licences' },
        { title: 'Operator licences', href: '/fleet/operator-licences' },
        { title: 'Create', href: '/fleet/operator-licences/create' },
    ];
    const addOperatingCentre = () =>
        setData('operating_centres', [
            ...data.operating_centres,
            { name: '', address: '' },
        ]);
    const updateOperatingCentre = (
        i: number,
        field: 'name' | 'address',
        value: string,
    ) => {
        const next = [...data.operating_centres];
        next[i] = { ...next[i], [field]: value };
        setData('operating_centres', next);
    };
    const transform = (d: typeof data) => ({
        ...d,
        operating_centres: d.operating_centres.filter(
            (o) => o.name.trim() || o.address.trim(),
        ).length
            ? d.operating_centres.filter(
                  (o) => o.name.trim() && o.address.trim(),
              )
            : [
                  {
                      name: d.operating_centres[0]?.name ?? '',
                      address: d.operating_centres[0]?.address ?? '',
                  },
              ],
        authorized_trailers:
            d.authorized_trailers === '' ? null : Number(d.authorized_trailers),
    });
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New operator licence" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">New operator licence</h1>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        post('/fleet/operator-licences', { transform });
                    }}
                    className="max-w-xl space-y-4"
                >
                    <div>
                        <Label htmlFor="license_number">License number *</Label>
                        <Input
                            id="license_number"
                            value={data.license_number}
                            onChange={(e) =>
                                setData('license_number', e.target.value)
                            }
                            className="mt-1"
                        />
                        {errors.license_number && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.license_number}
                            </p>
                        )}
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label htmlFor="license_type">License type *</Label>
                            <select
                                id="license_type"
                                value={data.license_type}
                                onChange={(e) =>
                                    setData('license_type', e.target.value)
                                }
                                className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                            >
                                {LICENSE_TYPES.map((o) => (
                                    <option key={o.value} value={o.value}>
                                        {o.name}
                                    </option>
                                ))}
                            </select>
                            {errors.license_type && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.license_type}
                                </p>
                            )}
                        </div>
                        <div>
                            <Label htmlFor="traffic_commissioner_area">
                                Traffic commissioner area *
                            </Label>
                            <select
                                id="traffic_commissioner_area"
                                value={data.traffic_commissioner_area}
                                onChange={(e) =>
                                    setData(
                                        'traffic_commissioner_area',
                                        e.target.value,
                                    )
                                }
                                className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                            >
                                {AREAS.map((o) => (
                                    <option key={o.value} value={o.value}>
                                        {o.name}
                                    </option>
                                ))}
                            </select>
                            {errors.traffic_commissioner_area && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.traffic_commissioner_area}
                                </p>
                            )}
                        </div>
                    </div>
                    <div className="grid grid-cols-3 gap-4">
                        <div>
                            <Label htmlFor="issue_date">Issue date *</Label>
                            <Input
                                id="issue_date"
                                type="date"
                                value={data.issue_date}
                                onChange={(e) =>
                                    setData('issue_date', e.target.value)
                                }
                                className="mt-1"
                            />
                            {errors.issue_date && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.issue_date}
                                </p>
                            )}
                        </div>
                        <div>
                            <Label htmlFor="effective_date">
                                Effective date *
                            </Label>
                            <Input
                                id="effective_date"
                                type="date"
                                value={data.effective_date}
                                onChange={(e) =>
                                    setData('effective_date', e.target.value)
                                }
                                className="mt-1"
                            />
                            {errors.effective_date && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.effective_date}
                                </p>
                            )}
                        </div>
                        <div>
                            <Label htmlFor="expiry_date">Expiry date *</Label>
                            <Input
                                id="expiry_date"
                                type="date"
                                value={data.expiry_date}
                                onChange={(e) =>
                                    setData('expiry_date', e.target.value)
                                }
                                className="mt-1"
                            />
                            {errors.expiry_date && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.expiry_date}
                                </p>
                            )}
                        </div>
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label htmlFor="authorized_vehicles">
                                Authorized vehicles *
                            </Label>
                            <Input
                                id="authorized_vehicles"
                                type="number"
                                min={0}
                                value={data.authorized_vehicles}
                                onChange={(e) =>
                                    setData(
                                        'authorized_vehicles',
                                        Number(e.target.value),
                                    )
                                }
                                className="mt-1"
                            />
                            {errors.authorized_vehicles && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.authorized_vehicles}
                                </p>
                            )}
                        </div>
                        <div>
                            <Label htmlFor="authorized_trailers">
                                Authorized trailers
                            </Label>
                            <Input
                                id="authorized_trailers"
                                type="number"
                                min={0}
                                value={
                                    data.authorized_trailers === ''
                                        ? ''
                                        : String(data.authorized_trailers)
                                }
                                onChange={(e) =>
                                    setData(
                                        'authorized_trailers',
                                        e.target.value === ''
                                            ? ''
                                            : Number(e.target.value),
                                    )
                                }
                                className="mt-1"
                            />
                            {errors.authorized_trailers && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.authorized_trailers}
                                </p>
                            )}
                        </div>
                    </div>
                    <div>
                        <Label>Operating centres *</Label>
                        {data.operating_centres.map((oc, i) => (
                            <div key={i} className="mt-2 flex gap-2">
                                <Input
                                    placeholder="Name"
                                    value={oc.name}
                                    onChange={(e) =>
                                        updateOperatingCentre(
                                            i,
                                            'name',
                                            e.target.value,
                                        )
                                    }
                                    className="flex-1"
                                />
                                <Input
                                    placeholder="Address"
                                    value={oc.address}
                                    onChange={(e) =>
                                        updateOperatingCentre(
                                            i,
                                            'address',
                                            e.target.value,
                                        )
                                    }
                                    className="flex-1"
                                />
                            </div>
                        ))}
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={addOperatingCentre}
                            className="mt-1"
                        >
                            Add operating centre
                        </Button>
                        {errors['operating_centres.0.name'] && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors['operating_centres.0.name']}
                            </p>
                        )}
                        {errors['operating_centres.0.address'] && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors['operating_centres.0.address']}
                            </p>
                        )}
                    </div>
                    <div>
                        <Label htmlFor="status">Status *</Label>
                        <select
                            id="status"
                            value={data.status}
                            onChange={(e) => setData('status', e.target.value)}
                            className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                        >
                            {statuses.map((o) => (
                                <option key={o.value} value={o.value}>
                                    {o.name}
                                </option>
                            ))}
                        </select>
                        {errors.status && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.status}
                            </p>
                        )}
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={processing}>
                            Create operator licence
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href="/fleet/operator-licences">
                                Back to operator licences
                            </Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
