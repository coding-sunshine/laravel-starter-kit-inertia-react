import { LoaderOverloadDashboardSection } from '@/components/dashboard/loader-overload-dashboard-section';

interface Props {
    canWidget: (name: string) => boolean;
    buildLoaderOverloadApiParams: (args: { page?: number; perPage?: number }) => string;
    defaultDetailUnderloadPercent: number;
    mainDateRangeLabel: string | null;
    loaderIdFromUrl: number | null;
    loaderOverloadFilterKey: string;
}

export function LoaderOverloading({
    canWidget,
    buildLoaderOverloadApiParams,
    defaultDetailUnderloadPercent,
    mainDateRangeLabel,
    loaderIdFromUrl,
    loaderOverloadFilterKey,
}: Props) {
    if (!canWidget('dashboard.widgets.loader_overload_trends')) return null;

    return (
        <LoaderOverloadDashboardSection
            buildApiSearchParams={buildLoaderOverloadApiParams}
            defaultDetailUnderloadPercent={defaultDetailUnderloadPercent}
            mainDateRangeLabel={mainDateRangeLabel}
            loaderIdFromUrl={loaderIdFromUrl}
            filterKey={loaderOverloadFilterKey}
        />
    );
}
