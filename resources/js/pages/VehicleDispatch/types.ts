export interface VehicleDispatch {
    id: number;
    siding_id: number;
    serial_no: number | null;
    ref_no: number | null;
    permit_no: string;
    pass_no: string;
    stack_do_no: string | null;
    issued_on: string | null;
    truck_regd_no: string;
    mineral: string;
    mineral_type: string | null;
    mineral_weight: number;
    source: string | null;
    destination: string | null;
    consignee: string | null;
    check_gate: string | null;
    distance_km: number | null;
    shift: string | null;
    created_at: string;
    updated_at: string;
    siding: {
        id: number;
        name: string;
        code: string;
    };
    creator: {
        id: number;
        name: string;
        email: string;
    } | null;
}

export interface Filters {
    date_from?: string;
    date_to?: string;
    date?: string;
    permit_no?: string;
    truck_regd_no?: string;
}

export interface VehicleDispatchPagination {
    data: VehicleDispatch[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}
