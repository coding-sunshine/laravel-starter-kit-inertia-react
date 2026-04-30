import { RakePerformanceSection } from '../dashboard';
import type { DashboardFilters, SidingOption } from './types';

interface Props {
    canWidget: (name: string) => boolean;
    filters: DashboardFilters;
    allSidingIds: number[];
    filteredSidings: SidingOption[];
    onRakePenaltyScopeChange: (scope: 'all' | 'with_penalties') => void;
    navigateToLoaderTrends: (loaderId: number, loaderName: string) => void;
}

export function RakePerformance({
    canWidget,
    filters,
    allSidingIds,
    filteredSidings,
    onRakePenaltyScopeChange,
    navigateToLoaderTrends,
}: Props) {
    if (!canWidget('dashboard.widgets.rake_performance')) return null;

    return (
        <RakePerformanceSection
            filters={filters}
            allSidingIds={allSidingIds}
            sidings={filteredSidings}
            rakePenaltyScope={filters.rake_penalty_scope ?? 'all'}
            onRakePenaltyScopeChange={onRakePenaltyScopeChange}
            onNavigateToLoader={navigateToLoaderTrends}
        />
    );
}
