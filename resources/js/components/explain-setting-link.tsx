import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import { HelpCircle } from 'lucide-react';

interface ExplainSettingLinkProps {
    /** Setting name or key to include in the prompt (e.g. "Appearance theme", "Branding logo") */
    settingName: string;
    /** Optional short context (e.g. "Controls light/dark mode") */
    context?: string;
    className?: string;
}

export function ExplainSettingLink({
    settingName,
    context,
    className,
}: ExplainSettingLinkProps) {
    const prompt = context
        ? `Explain this setting: "${settingName}". ${context}`
        : `Explain this setting: "${settingName}". What does it do and when would I change it?`;
    const href = `/chat?prompt=${encodeURIComponent(prompt)}`;

    return (
        <Button variant="ghost" size="sm" className={className} asChild>
            <Link
                href={href}
                className="inline-flex items-center gap-1.5 text-muted-foreground hover:text-foreground"
            >
                <HelpCircle className="size-3.5" aria-hidden />
                <span>Explain this</span>
            </Link>
        </Button>
    );
}
