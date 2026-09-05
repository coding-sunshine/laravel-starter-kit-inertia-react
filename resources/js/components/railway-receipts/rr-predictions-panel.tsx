import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type RrPrediction = {
    rake_id: number;
    rake_number: string | null;
    predicted_weight_mt: number;
    predicted_rr_date: string | null;
    prediction_confidence: number;
    prediction_status: string;
    variance_percent: number | null;
};

interface Props {
    rrPredictions: RrPrediction[];
}

const statusColor = (status: string) => {
    if (status === 'completed') return 'bg-green-100 text-green-700';
    if (status === 'pending') return 'bg-amber-100 text-amber-700';
    return 'bg-gray-100 text-gray-700';
};

export function RrPredictionsPanel({ rrPredictions }: Props) {
    if (rrPredictions.length === 0) return null;

    return (
        <Card>
            <CardHeader className="pb-3">
                <CardTitle className="text-sm font-semibold text-gray-900">AI Weight Predictions</CardTitle>
            </CardHeader>
            <CardContent>
                <div className="space-y-2">
                    {rrPredictions.map((p) => (
                        <div
                            key={p.rake_id}
                            className="flex items-center justify-between rounded-md border border-gray-100 bg-gray-50 px-3 py-2 text-xs"
                        >
                            <div>
                                <span className="font-medium text-gray-800">Rake #{p.rake_number ?? p.rake_id}</span>
                                {p.predicted_rr_date && (
                                    <span className="ml-2 text-gray-400">RR {p.predicted_rr_date}</span>
                                )}
                            </div>
                            <div className="flex items-center gap-2">
                                <span className="text-gray-700">{p.predicted_weight_mt.toFixed(1)} MT</span>
                                <span className="text-gray-400">{p.prediction_confidence.toFixed(0)}% conf.</span>
                                {p.prediction_status === 'completed' && p.variance_percent !== null && (
                                    <span
                                        className={
                                            Math.abs(p.variance_percent) <= 2 ? 'text-green-600' : 'text-amber-600'
                                        }
                                    >
                                        {p.variance_percent > 0 ? '+' : ''}
                                        {p.variance_percent.toFixed(1)}%
                                    </span>
                                )}
                                <Badge variant="outline" className={statusColor(p.prediction_status)}>
                                    {p.prediction_status}
                                </Badge>
                            </div>
                        </div>
                    ))}
                </div>
            </CardContent>
        </Card>
    );
}
