import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type PenaltyPrediction = {
    siding_name: string;
    risk_level: 'high' | 'medium' | 'low';
    predicted_amount_min: number;
    predicted_amount_max: number;
    top_recommendation: string | null;
};

interface Props {
    predictions: PenaltyPrediction[];
}

const riskConfig = {
    high: { label: 'High Risk', className: 'bg-red-100 text-red-700 border-red-200' },
    medium: { label: 'Medium', className: 'bg-amber-100 text-amber-700 border-amber-200' },
    low: { label: 'Low', className: 'bg-green-100 text-green-700 border-green-200' },
};

function formatInr(amount: number): string {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 0,
    }).format(amount);
}

export function PenaltyPredictionsWidget({ predictions }: Props) {
    if (predictions.length === 0) {
        return null;
    }

    return (
        <Card>
            <CardHeader className="pb-3">
                <CardTitle className="flex items-center gap-2 text-sm font-semibold text-gray-900">
                    <span>🔮</span>
                    <span>Penalty Forecast — Next 7 Days</span>
                </CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
                {predictions.map((p, i) => {
                    const config = riskConfig[p.risk_level] ?? riskConfig.low;
                    return (
                        <div key={i} className="rounded-lg border border-gray-100 bg-gray-50 p-3">
                            <div className="mb-1 flex items-center justify-between gap-2">
                                <span className="text-sm font-medium text-gray-900">{p.siding_name}</span>
                                <Badge variant="outline" className={config.className}>
                                    {config.label}
                                </Badge>
                            </div>
                            <p className="text-xs text-gray-500">
                                Predicted:{' '}
                                <span className="font-semibold text-gray-700">
                                    {formatInr(p.predicted_amount_min)} – {formatInr(p.predicted_amount_max)}
                                </span>
                            </p>
                            {p.top_recommendation && (
                                <p className="mt-1 text-xs italic text-gray-600">💡 {p.top_recommendation}</p>
                            )}
                        </div>
                    );
                })}
            </CardContent>
        </Card>
    );
}
