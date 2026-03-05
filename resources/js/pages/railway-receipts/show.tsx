import { RRHeader } from '@/components/RR/RRHeader';
import { RRTabs } from '@/components/RR/RRTabs';
import type {
    ChargeRow,
    OverviewData,
    PenaltyRow,
    WagonRow,
} from '@/components/RR/types';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

interface Rake {
    id: number;
    rake_number: string;
    siding?: { id: number; name: string; code: string };
}

interface RrDocument {
    id: number;
    rr_number: string;
    rr_received_date: string;
    rr_weight_mt: string | null;
    document_status: string;
    fnr: string | null;
    freight_total: string | null;
    rr_details: Record<string, unknown> | null;
    rake?: Rake;
}

interface Props {
    rrDocument: RrDocument;
}

function mapStatus(
    status: string,
): 'parsed' | 'pending' | 'error' {
    const s = status.toLowerCase();
    if (s === 'verified' || s === 'parsed') return 'parsed';
    if (s === 'discrepancy' || s === 'error') return 'error';
    return 'pending';
}

function buildHeaderData(doc: RrDocument) {
    const date = doc.rr_received_date
        ? new Date(doc.rr_received_date).toLocaleDateString('en-GB', {
              day: '2-digit',
              month: 'short',
              year: 'numeric',
          })
        : '-';
    const rrDetails = doc.rr_details as Record<string, unknown> | null;
    const powerPlant =
        (rrDetails?.destination_siding as string) ??
        (rrDetails?.coal_grade as string) ??
        '-';

    return {
        rrNumber: doc.rr_number,
        siding: doc.rake?.siding?.name ?? '-',
        powerPlant: String(powerPlant),
        rrDate: date,
        totalWeightMt: doc.rr_weight_mt ? `${doc.rr_weight_mt} MT` : '-',
        status: mapStatus(doc.document_status),
    };
}

function buildOverviewData(doc: RrDocument): OverviewData {
    const rrDetails = doc.rr_details as Record<string, unknown> | null;
    const wagons = (rrDetails?.wagons as unknown[] | null) ?? [];
    const freight = doc.freight_total
        ? `₹${Number(doc.freight_total).toLocaleString('en-IN')}`
        : '-';

    return {
        rrNumber: doc.rr_number,
        fnr: doc.fnr ?? '-',
        distanceKm: (rrDetails?.distance_km as string) ?? '-',
        commodity: (rrDetails?.coal_grade as string) ?? '-',
        totalWagons: Array.isArray(wagons) ? wagons.length : 0,
        totalWeight: doc.rr_weight_mt ? `${doc.rr_weight_mt} MT` : '-',
        freightTotal: freight,
    };
}

function buildWagonsData(doc: RrDocument): WagonRow[] {
    const rrDetails = doc.rr_details as Record<string, unknown> | null;
    const wagons = (rrDetails?.wagons as Record<string, unknown>[] | null) ?? [];

    return wagons.map((w, i) => ({
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

function buildChargesData(doc: RrDocument): ChargeRow[] {
    const rrDetails = doc.rr_details as Record<string, unknown> | null;
    const charges = rrDetails?.charges as Record<string, number> | null;

    if (!charges || typeof charges !== 'object') {
        return [];
    }

    const labels: Record<string, string> = {
        POL1: 'POL1',
        OTC: 'OTC',
        GST: 'GST',
        FRT: 'Freight',
    };

    return Object.entries(charges).map(([code, amount]) => ({
        chargeCode: code,
        chargeName: labels[code] ?? code,
        amount: `₹${Number(amount).toLocaleString('en-IN')}`,
    }));
}

function buildPenaltiesData(doc: RrDocument): PenaltyRow[] {
    const rrDetails = doc.rr_details as Record<string, unknown> | null;
    const penalties = rrDetails?.penalties as Record<string, unknown>[] | null;

    if (!penalties || !Array.isArray(penalties)) {
        return [];
    }

    return penalties.map((p) => ({
        penaltyCode: (p.penalty_code ?? p.code ?? '-') as string,
        penaltyName: (p.penalty_name ?? p.name ?? '-') as string,
        calculationType: (p.calculation_type ?? '-') as string,
        amount: `₹${Number(p.amount ?? 0).toLocaleString('en-IN')}`,
        wagonReference: p.wagon_reference as string | undefined,
    }));
}

const MOCK_HEADER = {
    rrNumber: '461003908',
    siding: 'Dumka',
    powerPlant: 'BTPC',
    rrDate: '03 Nov 2025',
    totalWeightMt: '3691 MT',
    status: 'parsed' as const,
};

const MOCK_OVERVIEW: OverviewData = {
    rrNumber: '461003908',
    fnr: 'FNR-2025-001',
    distanceKm: '156',
    commodity: 'Coal',
    totalWagons: 58,
    totalWeight: '3691 MT',
    freightTotal: '₹4,52,890',
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

export default function RailwayReceiptShow({ rrDocument }: Props) {
    const hasData = rrDocument?.id;

    const headerData = hasData ? buildHeaderData(rrDocument) : MOCK_HEADER;
    const overviewData = hasData
        ? buildOverviewData(rrDocument)
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
        { title: 'Dashboard', href: '/dashboard' },
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
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Railway Receipt Details
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        View RR document details, wagons, charges, and penalties
                    </p>
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
