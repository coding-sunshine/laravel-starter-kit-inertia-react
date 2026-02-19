import { RrmcsGuidance } from '@/components/rrmcs-guidance';
import Heading from '@/components/heading';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { FileText } from 'lucide-react';

export default function IndentsIndex() {
    return (
        <AppLayout>
            <Head title="Indents" />

            <div className="space-y-6">
                <Heading
                    title="Rake Indents"
                    description="Manage rake orders and requests for the RRMCS system"
                />

                <RrmcsGuidance
                    title="What this section is for"
                    before="Indent requests raised on paper or Excel; 30+ minutes to prepare and submit; stock and allocations tracked manually."
                    after="Create indents in the app with target quantity and date; system validates stock; quick submit. Track status: pending → allocated → completed."
                />

                <div className="grid gap-4">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <FileText className="h-5 w-5" />
                                Indents Management
                            </CardTitle>
                            <CardDescription>
                                View and manage all rake indents (orders) in the system
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="rounded-lg border border-dashed p-8 text-center">
                                <FileText className="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
                                <p className="text-sm text-muted-foreground">
                                    Indent management features coming soon
                                </p>
                                <p className="mt-2 text-xs text-muted-foreground">
                                    Features include: Create indents, Track requests, Manage allocations, Approve orders
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
