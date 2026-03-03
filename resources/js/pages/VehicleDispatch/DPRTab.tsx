import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { FileBarChart } from 'lucide-react';

export default function DPRTab() {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <FileBarChart className="h-5 w-5" />
                    Dispatch Report (DPR)
                </CardTitle>
                <CardDescription>
                    DPR content will be displayed here. Coming soon.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div className="flex flex-col items-center justify-center py-12 text-center text-muted-foreground">
                    <FileBarChart className="h-12 w-12 mb-4 opacity-50" />
                    <p className="text-sm">DPR data and reports will appear in this tab.</p>
                    <p className="text-xs mt-2">Configure this section when ready.</p>
                </div>
            </CardContent>
        </Card>
    );
}
