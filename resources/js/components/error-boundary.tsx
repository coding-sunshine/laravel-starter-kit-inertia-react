import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/react';
import { AlertTriangle } from 'lucide-react';
import type { ReactNode } from 'react';
import { Component } from 'react';

interface Props {
    children: ReactNode;
}

interface State {
    hasError: boolean;
    error?: Error;
}

/**
 * Catches React render errors and shows a retry UI instead of a blank screen.
 */
export class ErrorBoundary extends Component<Props, State> {
    constructor(props: Props) {
        super(props);
        this.state = { hasError: false };
    }

    static getDerivedStateFromError(error: Error): State {
        return { hasError: true, error };
    }

    componentDidCatch(error: Error, errorInfo: React.ErrorInfo): void {
        console.error('ErrorBoundary caught:', error, errorInfo);
    }

    handleRetry = (): void => {
        this.setState({ hasError: false, error: undefined });
        router.reload();
    };

    render(): ReactNode {
        if (this.state.hasError) {
            return (
                <div className="flex min-h-[50vh] flex-col items-center justify-center gap-4 p-8 text-center">
                    <div className="flex size-16 items-center justify-center rounded-full bg-destructive/10 text-destructive">
                        <AlertTriangle className="size-8" />
                    </div>
                    <h2 className="text-lg font-semibold">
                        Something went wrong
                    </h2>
                    <p className="max-w-md text-sm text-muted-foreground">
                        An error occurred while loading this page. You can try
                        again or go back.
                    </p>
                    <div className="flex gap-3">
                        <Button onClick={this.handleRetry}>Try again</Button>
                        <Button
                            variant="outline"
                            onClick={() => router.visit('/')}
                        >
                            Go home
                        </Button>
                    </div>
                </div>
            );
        }

        return this.props.children;
    }
}
