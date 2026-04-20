import { RRHeader } from '@/components/RR/RRHeader';
import { RRTabs } from '@/components/RR/RRTabs';
import type {
    ChargeRow,
    OverviewData,
    PenaltyRow,
    WagonRow,
} from '@/components/RR/types';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { Download, Trash2 } from 'lucide-react';
import type { ReactNode } from 'react';

interface Wagon {
    id: number;
    wagon_sequence: number;
    wagon_number: string;
    wagon_type: string | null;
    pcc_weight_mt: string | number | null;
    loaded_weight_mt: string | number | null;
    permissible_weight_mt: string | number | null;
    overload_weight_mt: string | number | null;
}

interface RrCharge {
    id: number;
    charge_code: string;
    charge_name: string | null;
    amount: string | number;
}

interface Rake {
    id: number;
    rake_number: string;
    rake_serial_number: string | null;
    wagon_count?: number;
    loaded_weight_mt?: string | number | null;
    siding?: { id: number; name: string; code: string };
    wagons?: Wagon[];
}

interface RrDocument {
    id: number;
    rr_number: string;
    rr_received_date: string;
    rr_weight_mt: string | null;
    document_status: string;
    fnr: string | null;
    freight_total: string | null;
    distance_km?: string | number | null;
    commodity_code?: string | null;
    commodity_description?: string | null;
    rate?: string | number | null;
    class?: string | null;
    from_station_code?: string | null;
    to_station_code?: string | null;
    rr_details: Record<string, unknown> | null;
    rake?: Rake;
    rr_charges?: RrCharge[];
    rr_pdf_download_url?: string | null;
    // Snapshot relations from backend (snake_case when serialized)
    wagon_snapshots?: Array<{
        id: number;
        wagon_sequence: number | null;
        wagon_number: string | null;
        wagon_type: string | null;
        pcc_weight_mt: string | number | null;
        loaded_weight_mt: string | number | null;
        permissible_weight_mt: string | number | null;
        overload_weight_mt: string | number | null;
    }>;
    penalty_snapshots?: Array<{
        id: number;
        penalty_code: string;
        amount: string | number;
        wagon_number?: string | null;
        wagon_sequence?: number | null;
    }>;
}

interface FromSiding {
    id: number;
    name: string;
    code: string;
}

interface ToPowerPlant {
    id: number;
    name: string;
    code: string;
}

interface Props {
    rrDocument: RrDocument;
    fromSiding?: FromSiding | null;
    toPowerPlant?: ToPowerPlant | null;
    can_download_rr?: boolean;
    can_delete_rr?: boolean;
}

function mapStatus(
    status: string,
): 'parsed' | 'pending' | 'error' {
    const s = status.toLowerCase();
    if (s === 'verified' || s === 'parsed') return 'parsed';
    if (s === 'discrepancy' || s === 'error') return 'error';
    return 'pending';
}

function buildHeaderData(
    doc: RrDocument,
    fromSiding?: FromSiding | null,
    toPowerPlant?: ToPowerPlant | null,
) {
    const date = doc.rr_received_date
        ? new Date(doc.rr_received_date).toLocaleDateString('en-GB', {
              day: '2-digit',
              month: 'short',
              year: 'numeric',
          })
        : '-';

    const rakeNumberDisplay: ReactNode = doc.rake?.rake_serial_number ? (
        doc.rake.rake_serial_number
    ) : doc.rake?.rake_number ? (
        <span className="text-amber-600 dark:text-amber-400">
            {doc.rake.rake_number}
        </span>
    ) : (
        '—'
    );

    return {
        rrNumber: doc.rr_number,
        rakeNumber: rakeNumberDisplay,
        siding:
            fromSiding?.name ??
            doc.rake?.siding?.name ??
            doc.from_station_code ??
            '-',
        powerPlant:
            toPowerPlant?.name ?? doc.to_station_code ?? '-',
        rrDate: date,
        totalWeightMt: doc.rr_weight_mt ? `${doc.rr_weight_mt} MT` : '-',
        status: mapStatus(doc.document_status),
    };
}

function buildOverviewData(
    doc: RrDocument,
    fromSiding?: FromSiding | null,
    toPowerPlant?: ToPowerPlant | null,
): OverviewData {
    const rrDetails = doc.rr_details as Record<string, unknown> | null;
    const snapshotWagons =
        (doc as Record<string, unknown>).wagon_snapshots as Wagon[] | undefined;
    const rakeWagons = doc.rake?.wagons ?? [];
    const legacyWagons = (rrDetails?.wagons as unknown[] | null) ?? [];
    const totalWagons =
        (snapshotWagons && snapshotWagons.length > 0)
            ? snapshotWagons.length
            : rakeWagons.length > 0
            ? rakeWagons.length
            : Array.isArray(legacyWagons)
              ? legacyWagons.length
              : doc.rake?.wagon_count ?? 0;
    const freight = doc.freight_total
        ? `₹${Number(doc.freight_total).toLocaleString('en-IN')}`
        : '-';

    const rakeNumberDisplay: ReactNode = doc.rake?.rake_serial_number ? (
        doc.rake.rake_serial_number
    ) : doc.rake?.rake_number ? (
        <span className="text-amber-600 dark:text-amber-400">
            {doc.rake.rake_number}
        </span>
    ) : (
        '—'
    );

    return {
        rrNumber: doc.rr_number,
        rakeNumber: rakeNumberDisplay,
        fnr: doc.fnr ?? '-',
        fromStation:
            fromSiding?.name ?? doc.from_station_code ?? '-',
        toStation:
            toPowerPlant?.name ?? doc.to_station_code ?? '-',
        distanceKm:
            doc.distance_km != null
                ? String(doc.distance_km)
                : (rrDetails?.distance_km as string) ?? '-',
        commodity:
            doc.commodity_description ??
            doc.commodity_code ??
            (rrDetails?.coal_grade as string) ??
            '-',
        totalWagons,
        totalWeight: doc.rr_weight_mt ? `${doc.rr_weight_mt} MT` : '-',
        freightTotal: freight,
        rate: doc.rate != null ? String(doc.rate) : '-',
        class: doc.class ?? '-',
    };
}

function buildWagonsData(doc: RrDocument): WagonRow[] {
    // 1) Prefer RR wagon snapshots (what the RR says)
    const snapshotWagons =
        (doc as Record<string, unknown>).wagon_snapshots as
            | Wagon[]
            | undefined;
    if (snapshotWagons && snapshotWagons.length > 0) {
        return snapshotWagons.map((w, index) => ({
            sequence: w.wagon_sequence ?? index + 1,
            wagonNumber: w.wagon_number ?? '-',
            wagonType: w.wagon_type ?? '-',
            pccWeight: String(w.pcc_weight_mt ?? '-'),
            loadedWeight: String(w.loaded_weight_mt ?? '-'),
            permissibleWeight: String(w.permissible_weight_mt ?? '-'),
            overloadWeight: String(w.overload_weight_mt ?? '0'),
            status:
                Number(w.overload_weight_mt) > 0 ? 'Overload' : 'Loaded',
        }));
    }

    // 2) Fallback: rake wagons (operational view)
    const rakeWagons = doc.rake?.wagons ?? [];
    if (rakeWagons.length > 0) {
        return rakeWagons.map((w) => ({
            sequence: w.wagon_sequence,
            wagonNumber: w.wagon_number ?? '-',
            wagonType: w.wagon_type ?? '-',
            pccWeight: String(w.pcc_weight_mt ?? '-'),
            loadedWeight: String(w.loaded_weight_mt ?? '-'),
            permissibleWeight: String(w.permissible_weight_mt ?? '-'),
            overloadWeight: String(w.overload_weight_mt ?? '0'),
            status:
                Number(w.overload_weight_mt) > 0 ? 'Overload' : 'Loaded',
        }));
    }

    // 3) Legacy: wagons embedded in rr_details
    const rrDetails = doc.rr_details as Record<string, unknown> | null;
    const legacyWagons =
        (rrDetails?.wagons as Record<string, unknown>[] | null) ?? [];

    return legacyWagons.map((w, i) => ({
        sequence: i + 1,
        wagonNumber: (w.wagon_number ?? w.wagonNumber ?? '-') as string,
        wagonType: (w.wagon_type ?? w.wagonType ?? '-') as string,
        pccWeight: String(w.cc_mt ?? w.chargeable_mt ?? '-'),
        loadedWeight: String(w.actual_mt ?? w.gross_mt ?? '-'),
        permissibleWeight: String(w.permissible_mt ?? '-'),
        overloadWeight: String(w.over_weight_mt ?? '-'),
        status: (w.over_weight_mt as number) > 0 ? 'Overload' : 'Loaded',
    }));
}

/** Charges stored on this RR document only (`rr_charges` + parsed PDF legacy), not rake ledger rows. */
function buildChargesData(doc: RrDocument): ChargeRow[] {
    const rrCharges =
        (doc as Record<string, unknown>).rr_charges ??
        (doc as Record<string, unknown>).rrCharges;

    if (Array.isArray(rrCharges) && rrCharges.length > 0) {
        return rrCharges.map((c: Record<string, unknown>) => ({
            chargeCode: String(c.charge_code ?? c.chargeCode ?? ''),
            chargeName: String(c.charge_name ?? c.chargeName ?? c.charge_code ?? c.chargeCode ?? ''),
            amount: `₹${Number(c.amount ?? 0).toLocaleString('en-IN')}`,
        }));
    }

    const rrDetails = doc.rr_details as Record<string, unknown> | null;
    const legacyCharges = rrDetails?.charges as Record<string, number> | null;

    if (!legacyCharges || typeof legacyCharges !== 'object') {
        return [];
    }

    const labels: Record<string, string> = {
        POL1: 'POL1',
        OTC: 'OTC',
        GST: 'GST',
        FRT: 'Freight',
    };

    return Object.entries(legacyCharges).map(([code, amount]) => ({
        chargeCode: code,
        chargeName: labels[code] ?? code,
        amount: `₹${Number(amount).toLocaleString('en-IN')}`,
    }));
}

/** Penalties stored as snapshots on this RR only (`rr_penalty_snapshots`), not operational `applied_penalties`. */
function buildPenaltiesData(doc: RrDocument): PenaltyRow[] {
    const penaltySnapshots =
        (doc as Record<string, unknown>).penalty_snapshots as
            | {
                  penalty_code: string;
                  amount: string | number;
                  wagon_number?: string | null;
                  wagon_sequence?: number | null;
              }[]
            | undefined;
    if (!penaltySnapshots || penaltySnapshots.length === 0) {
        return [];
    }

    return penaltySnapshots.map((p) => ({
        penaltyCode: p.penalty_code,
        penaltyName: p.penalty_code,
        calculationType: '-',
        amount: `₹${Number(p.amount ?? 0).toLocaleString('en-IN')}`,
        wagonReference: p.wagon_number ?? undefined,
        overloadWeight: undefined,
    }));
}

const MOCK_HEADER = {
    rrNumber: '461003908',
    rakeNumber: 'RK-1001',
    siding: 'Dumka',
    powerPlant: 'BTPC',
    rrDate: '03 Nov 2025',
    totalWeightMt: '3691 MT',
    status: 'parsed' as const,
};

const MOCK_OVERVIEW: OverviewData = {
    rrNumber: '461003908',
    rakeNumber: 'RK-1001',
    fnr: 'FNR-2025-001',
    fromStation: 'BMGK',
    toStation: 'PSPM',
    distanceKm: '156',
    commodity: 'Coal',
    totalWagons: 58,
    totalWeight: '3691 MT',
    freightTotal: '₹4,52,890',
    rate: '448.9',
    class: '145A',
};

const MOCK_WAGONS: WagonRow[] = [
    {
        sequence: 1,
        wagonNumber: 'BCN-12345',
        wagonType: 'BCN',
        pccWeight: '60.5',
        loadedWeight: '63.2',
        permissibleWeight: '63.0',
        overloadWeight: '0.2',
        status: 'Loaded',
    },
    {
        sequence: 2,
        wagonNumber: 'BCN-12346',
        wagonType: 'BCN',
        pccWeight: '61.0',
        loadedWeight: '64.1',
        permissibleWeight: '63.0',
        overloadWeight: '1.1',
        status: 'Overload',
    },
];

const MOCK_CHARGES: ChargeRow[] = [
    { chargeCode: 'FRT', chargeName: 'Freight', amount: '₹4,20,000' },
    { chargeCode: 'DST', chargeName: 'Development Surcharge', amount: '₹18,450' },
];

const MOCK_PENALTIES: PenaltyRow[] = [
    {
        penaltyCode: 'OL',
        penaltyName: 'Overload',
        calculationType: 'Per Ton',
        amount: '₹2,500',
        wagonReference: 'BCN-12346',
    },
];

export default function RailwayReceiptShow({
    rrDocument,
    fromSiding,
    toPowerPlant,
    can_download_rr = false,
    can_delete_rr = false,
}: Props) {
    const hasData = rrDocument?.id;

    const headerData = hasData
        ? buildHeaderData(rrDocument, fromSiding, toPowerPlant)
        : MOCK_HEADER;
    const overviewData = hasData
        ? buildOverviewData(rrDocument, fromSiding, toPowerPlant)
        : MOCK_OVERVIEW;
    const wagons = hasData ? buildWagonsData(rrDocument) : MOCK_WAGONS;
    const charges = hasData ? buildChargesData(rrDocument) : MOCK_CHARGES;
    const penalties = hasData ? buildPenaltiesData(rrDocument) : MOCK_PENALTIES;
    const rawData = hasData
        ? (rrDocument.rr_details ?? { id: rrDocument.id, rr_number: rrDocument.rr_number })
        : {
              rr_number: '461003908',
              parsed_at: '2025-11-03T10:30:00Z',
              wagons_count: 58,
              metadata: { source: 'pdf', version: '1.0' },
          };

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Railway Receipts', href: '/railway-receipts' },
        {
            title: headerData.rrNumber,
            href: `/railway-receipts/${rrDocument?.id ?? 1}`,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Railway Receipt Details" />
            <div className="space-y-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Railway Receipt Details
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            View RR document details, wagons, charges, and penalties
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        {can_download_rr && hasData && rrDocument.rr_pdf_download_url && (
                            <a
                                href={rrDocument.rr_pdf_download_url}
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <Button variant="outline" size="sm" type="button">
                                    <Download className="mr-2 h-4 w-4" />
                                    Download PDF
                                </Button>
                            </a>
                        )}
                        {can_delete_rr && hasData && (
                            <Button
                                variant="destructive"
                                size="sm"
                                type="button"
                                onClick={() => {
                                    if (
                                        !window.confirm(
                                            'Delete this railway receipt? Related charges and snapshot rows will be removed. This cannot be undone.',
                                        )
                                    ) {
                                        return;
                                    }
                                    router.delete(
                                        `/railway-receipts/${rrDocument.id}`,
                                    );
                                }}
                            >
                                <Trash2 className="mr-2 h-4 w-4" />
                                Delete receipt
                            </Button>
                        )}
                    </div>
                </div>

                <RRHeader {...headerData} />

                <RRTabs
                    overviewData={overviewData}
                    wagons={wagons}
                    charges={charges}
                    penalties={penalties}
                    rawData={rawData}
                />
            </div>
        </AppLayout>
    );
}
