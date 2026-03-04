import TextLink from '@/components/text-link';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import {
    index as helpIndex,
    rate as helpRate,
    show as helpShow,
} from '@/routes/help';
import { type BreadcrumbItem } from '@/types';
import { Form, Head, Link, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

interface HelpArticle {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    content: string;
    category: string;
}

interface RelatedArticle {
    id: number;
    title: string;
    slug: string;
}

interface Props {
    article: HelpArticle;
    related: RelatedArticle[];
    summary?: string | null;
    peopleAlsoAsked?: string[] | null;
}

function slugify(text: string): string {
    return text
        .toLowerCase()
        .replace(/\s+/g, '-')
        .replace(/[^a-z0-9-]/g, '');
}

export default function HelpShow({ article, related, summary }: Props) {
    const { flash } = usePage<{ flash?: { status?: string } }>().props;
    const contentRef = useRef<HTMLDivElement>(null);
    const [toc, setToc] = useState<
        { level: number; id: string; text: string }[]
    >([]);

    useEffect(() => {
        const el = contentRef.current;
        if (!el) return;
        const headings = el.querySelectorAll('h2, h3');
        const entries: { level: number; id: string; text: string }[] = [];
        headings.forEach((node, i) => {
            const level = node.tagName === 'H2' ? 2 : 3;
            const text = node.textContent?.trim() ?? '';
            const id = node.id || `section-${i}-${slugify(text)}`;
            if (!node.id) node.id = id;
            entries.push({ level, id, text });
        });
        setToc(entries);
    }, [article.content]);

    const showToc = toc.length >= 2;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Help', href: helpIndex().url },
        {
            title: article.title,
            href: helpShow({ helpArticle: article.slug }).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={article.title} />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <p className="text-sm text-muted-foreground">
                    <TextLink href={helpIndex().url}>Back to help</TextLink>
                </p>
                <article aria-labelledby="help-article-title">
                    <h1
                        id="help-article-title"
                        className="heading-2 mb-2 text-foreground"
                    >
                        {article.title}
                    </h1>
                    {(summary === undefined || summary || article.excerpt) && (
                        <div className="mb-6 rounded-lg border border-border bg-muted/30 p-4">
                            <p className="body-sm font-medium text-muted-foreground">
                                {summary === undefined ? (
                                    <span className="animate-pulse">
                                        Loading summary…
                                    </span>
                                ) : (
                                    (summary ?? article.excerpt ?? '')
                                )}
                            </p>
                        </div>
                    )}
                    <p className="mb-6">
                        <Link
                            href={`/chat?prompt=${encodeURIComponent(`I'm reading the help article "${article.title}". Can you explain the key points or answer questions about it?`)}`}
                            className="inline-flex items-center gap-2 rounded-md border bg-muted/30 px-3 py-2 text-sm font-medium text-foreground transition-colors hover:bg-muted/50"
                            data-pan="help-ask-about-article"
                        >
                            Ask about this article
                        </Link>
                    </p>
                    {peopleAlsoAsked !== undefined &&
                        peopleAlsoAsked &&
                        peopleAlsoAsked.length > 0 && (
                            <section
                                className="mb-6 rounded-lg border border-border bg-muted/20 p-4"
                                aria-labelledby="people-also-asked"
                            >
                                <h2
                                    id="people-also-asked"
                                    className="heading-6 mb-3 text-foreground"
                                >
                                    People also asked
                                </h2>
                                <ul className="space-y-2">
                                    {peopleAlsoAsked.map((question, i) => (
                                        <li key={i}>
                                            <Link
                                                href={`/chat?prompt=${encodeURIComponent(question)}`}
                                                className="text-sm text-foreground underline underline-offset-4 hover:no-underline"
                                            >
                                                {question}
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                            </section>
                        )}
                    <div className="flex flex-col gap-6 lg:flex-row lg:gap-10">
                        {showToc && (
                            <nav
                                className="order-2 shrink-0 lg:order-1 lg:w-52"
                                aria-label="Table of contents"
                            >
                                <h2 className="mb-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                    On this page
                                </h2>
                                <ul className="space-y-1.5 text-sm">
                                    {toc.map((item) => (
                                        <li
                                            key={item.id}
                                            className={
                                                item.level === 3
                                                    ? 'pl-3'
                                                    : undefined
                                            }
                                        >
                                            <a
                                                href={`#${item.id}`}
                                                className="text-muted-foreground hover:text-foreground hover:underline"
                                            >
                                                {item.text}
                                            </a>
                                        </li>
                                    ))}
                                </ul>
                            </nav>
                        )}
                        <div
                            ref={contentRef}
                            className={showToc ? 'min-w-0 flex-1' : undefined}
                        >
                            <div
                                className="prose prose-neutral dark:prose-invert prose-headings:scroll-mt-20 prose-p:leading-relaxed prose-li:leading-relaxed max-w-none"
                                // eslint-disable-next-line @eslint-react/dom/no-dangerously-set-innerhtml -- server-rendered article content
                                dangerouslySetInnerHTML={{
                                    __html: article.content,
                                }}
                            />
                        </div>
                    </div>
                </article>
                <section className="mt-8 border-t pt-6">
                    <h2 className="mb-3 text-sm font-medium">
                        Was this helpful?
                    </h2>
                    {flash?.status ? (
                        <p className="text-sm text-muted-foreground">
                            {flash.status}
                        </p>
                    ) : (
                        <div className="flex flex-wrap items-center gap-3">
                            <Form
                                action={
                                    helpRate({ helpArticle: article.slug }).url
                                }
                                method="post"
                                className="inline-block"
                            >
                                <input
                                    type="hidden"
                                    name="is_helpful"
                                    value="1"
                                />
                                <button
                                    type="submit"
                                    className="rounded-md border bg-background px-4 py-2 text-sm font-medium hover:bg-muted"
                                >
                                    Yes
                                </button>
                            </Form>
                            <Form
                                action={
                                    helpRate({ helpArticle: article.slug }).url
                                }
                                method="post"
                                className="inline-block"
                            >
                                <input
                                    type="hidden"
                                    name="is_helpful"
                                    value="0"
                                />
                                <button
                                    type="submit"
                                    className="text-sm text-muted-foreground underline underline-offset-4 hover:text-foreground"
                                >
                                    No
                                </button>
                            </Form>
                        </div>
                    )}
                </section>
                {related.length > 0 && (
                    <section className="mt-8 border-t pt-6">
                        <h2 className="mb-3 text-lg font-medium">
                            Related articles
                        </h2>
                        <ul className="space-y-2">
                            {related.map((item) => (
                                <li key={item.id}>
                                    <Link
                                        href={
                                            helpShow({
                                                helpArticle: item.slug,
                                            }).url
                                        }
                                        className="text-foreground underline underline-offset-4 hover:no-underline"
                                    >
                                        {item.title}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}
            </div>
        </AppLayout>
    );
}
