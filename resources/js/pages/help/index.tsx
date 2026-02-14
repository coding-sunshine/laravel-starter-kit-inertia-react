import AppLogoIcon from '@/components/app-logo-icon';
import TextLink from '@/components/text-link';
import { home } from '@/routes';
import { show as helpShow } from '@/routes/help';
import { Head, Link } from '@inertiajs/react';

interface HelpArticle {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    category: string;
}

interface Props {
    featured: HelpArticle[];
    byCategory: Record<string, HelpArticle[]>;
}

export default function HelpIndex({ featured, byCategory }: Props) {
    return (
        <>
            <Head title="Help Center" />
            <div className="min-h-svh bg-background">
                <header className="sticky top-0 z-10 border-b bg-background/95 px-4 py-3 backdrop-blur supports-[backdrop-filter]:bg-background/60">
                    <div className="mx-auto flex max-w-4xl items-center justify-between">
                        <Link
                            href={home().url}
                            className="flex items-center gap-2 font-medium text-foreground"
                        >
                            <AppLogoIcon className="size-8 fill-current" />
                            <span className="sr-only">Home</span>
                        </Link>
                        <TextLink href={home().url}>Back to home</TextLink>
                    </div>
                </header>
                <main className="mx-auto max-w-4xl px-4 py-8">
                    <h1 className="mb-6 text-2xl font-semibold">Help Center</h1>
                    {featured.length === 0 &&
                        Object.keys(byCategory).length === 0 && (
                            <div className="flex flex-col items-center justify-center rounded-lg border border-dashed py-16 text-center">
                                <p className="text-sm font-medium text-muted-foreground">
                                    No help articles yet
                                </p>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    Help articles and guides will appear here
                                    once published.
                                </p>
                            </div>
                        )}
                    {featured.length > 0 && (
                        <section className="mb-8">
                            <h2 className="mb-3 text-lg font-medium">
                                Featured articles
                            </h2>
                            <ul className="grid gap-3 sm:grid-cols-2">
                                {featured.map((article) => (
                                    <li key={article.id}>
                                        <Link
                                            href={
                                                helpShow({
                                                    helpArticle: article.slug,
                                                }).url
                                            }
                                            className="block rounded-lg border bg-card p-4 transition-colors hover:bg-muted/50"
                                        >
                                            <span className="font-medium text-foreground">
                                                {article.title}
                                            </span>
                                            {article.excerpt && (
                                                <p className="mt-1 line-clamp-2 text-sm text-muted-foreground">
                                                    {article.excerpt}
                                                </p>
                                            )}
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </section>
                    )}
                    <section>
                        {Object.entries(byCategory).map(
                            ([category, articles]) =>
                                articles.length > 0 && (
                                    <div key={category} className="mb-8">
                                        <h2 className="mb-3 text-lg font-medium capitalize">
                                            {category}
                                        </h2>
                                        <ul className="space-y-2">
                                            {articles.map((article) => (
                                                <li key={article.id}>
                                                    <Link
                                                        href={
                                                            helpShow({
                                                                helpArticle:
                                                                    article.slug,
                                                            }).url
                                                        }
                                                        className="block rounded-md py-2 text-foreground underline underline-offset-4 hover:no-underline"
                                                    >
                                                        {article.title}
                                                    </Link>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                ),
                        )}
                    </section>
                </main>
            </div>
        </>
    );
}
