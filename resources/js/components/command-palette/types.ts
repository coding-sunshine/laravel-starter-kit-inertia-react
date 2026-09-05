export interface RakeHit {
    id: number;
    rake_number: string;
    siding_name: string | null;
    status: string | null;
}

export interface IndentHit {
    id: number;
    indent_number: string;
    e_demand_number: string | null;
}

export interface RrHit {
    id: number;
    rr_number: string;
    rake_id: number | null;
}

export interface SearchResponse {
    rakes: RakeHit[];
    indents: IndentHit[];
    rrs: RrHit[];
}

export interface StaticAction {
    id: string;
    label: string;
    keywords?: string[];
    href: string;
    hint?: string;
}
