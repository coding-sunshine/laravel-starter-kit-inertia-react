import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface Weighment {
    id: number;
    weighment_type: 'GROSS' | 'TARE';
    weighment_status: string;
    weighment_time: string;
    gross_weight_mt?: number;
    tare_weight_mt?: number;
    net_weight_mt?: number;
}

interface Props {
    weighments: Weighment[];
}

export default function WeighmentHistory({ weighments }: Props) {
    const formatTime = (date: string) =>
        new Date(date).toLocaleString();

    // Debug: Log the weighments data
    console.log('WeighmentHistory received:', weighments);

    const sortedWeighments = [...weighments].sort(
        (a, b) => new Date(b.weighment_time).getTime() - new Date(a.weighment_time).getTime()
    );

    const grossWeighments = sortedWeighments.filter(w => w.weighment_type === 'GROSS');
    const tareWeighments = sortedWeighments.filter(w => w.weighment_type === 'TARE');

    if (weighments.length === 0) {
        console.log('No weighments to display');
        return null;
    }

    console.log('Displaying weighments:', { total: weighments.length, gross: grossWeighments.length, tare: tareWeighments.length });

    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">Weighment History</CardTitle>
            </CardHeader>
            <CardContent>
                <div className="space-y-6">
                    {/* Gross Weighments */}
                    {grossWeighments.length > 0 && (
                        <div>
                            <h4 className="text-sm font-medium mb-3">Gross Weighments</h4>
                            <div className="space-y-2">
                                {grossWeighments.map(w => (
                                    <div
                                        key={w.id}
                                        className={`p-3 rounded-lg border ${
                                            w.weighment_status === 'FAIL'
                                                ? 'bg-red-50 border-red-200'
                                                : w.weighment_status === 'PASS'
                                                ? 'bg-green-50 border-green-200'
                                                : 'bg-gray-50 border-gray-200'
                                        }`}
                                    >
                                        <div className="flex justify-between items-start">
                                            <div>
                                                <div className={`font-medium text-sm ${
                                                    w.weighment_status === 'FAIL'
                                                        ? 'text-red-700'
                                                        : w.weighment_status === 'PASS'
                                                        ? 'text-green-700'
                                                        : 'text-gray-600'
                                                }`}>
                                                    {w.weighment_status}
                                                </div>
                                                {w.gross_weight_mt && (
                                                    <div className="text-sm mt-1">
                                                        Weight: <span className="font-medium">{w.gross_weight_mt} MT</span>
                                                    </div>
                                                )}
                                            </div>
                                            <div className="text-xs text-gray-500">
                                                {formatTime(w.weighment_time)}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Tare Weighments */}
                    {tareWeighments.length > 0 && (
                        <div>
                            <h4 className="text-sm font-medium mb-3">Tare Weighments</h4>
                            <div className="space-y-2">
                                {tareWeighments.map(w => (
                                    <div
                                        key={w.id}
                                        className={`p-3 rounded-lg border ${
                                            w.weighment_status === 'FAIL'
                                                ? 'bg-red-50 border-red-200'
                                                : w.weighment_status === 'PASS'
                                                ? 'bg-green-50 border-green-200'
                                                : 'bg-gray-50 border-gray-200'
                                        }`}
                                    >
                                        <div className="flex justify-between items-start">
                                            <div>
                                                <div className={`font-medium text-sm ${
                                                    w.weighment_status === 'FAIL'
                                                        ? 'text-red-700'
                                                        : w.weighment_status === 'PASS'
                                                        ? 'text-green-700'
                                                        : 'text-gray-600'
                                                }`}>
                                                    {w.weighment_status}
                                                </div>
                                                <div className="text-sm mt-1">
                                                    {w.gross_weight_mt && (
                                                        <span>Gross: <span className="font-medium">{w.gross_weight_mt} MT</span></span>
                                                    )}
                                                    {w.tare_weight_mt && (
                                                        <span className="ml-3">Tare: <span className="font-medium">{w.tare_weight_mt} MT</span></span>
                                                    )}
                                                    {w.net_weight_mt && (
                                                        <span className="ml-3">Net: <span className="font-medium">{w.net_weight_mt} MT</span></span>
                                                    )}
                                                </div>
                                            </div>
                                            <div className="text-xs text-gray-500">
                                                {formatTime(w.weighment_time)}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}
