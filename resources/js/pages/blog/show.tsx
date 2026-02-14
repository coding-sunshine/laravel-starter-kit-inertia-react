import AppLogoIcon from '@/components/app-logo-icon';
import TextLink from '@/components/text-link';
import { home } from '@/routes';
import { index as blogIndex } from '@/routes/blog';
import { Head, Link } from '@inertiajs/react';

interface Author {
    id: number;
    name: string;
}

interface Post {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    content: string;
    published_at: string | null;
    author?: Author;
}

interface Props {
    post: Post;
}

export default function BlogShow({ post }: Props) {
    return (
        <>
            <Head title={post.title} />
            <div className="min-h-svh bg-background">
                <header className="sticky top-0 z-10 border-b bg-background/95 px-4 py-3 backdrop-blur supports-[backdrop-filter]:bg-background/60">
                    <div className="mx-auto flex max-w-3xl items-center justify-between">
                        <Link
                            href={home().url}
                            className="flex items-center gap-2 font-medium text-foreground"
                        >
                            <AppLogoIcon className="size-8 fill-current" />
                            <span className="sr-only">Home</span>
                        </Link>
                        <TextLink href={blogIndex().url}>Back to blog</TextLink>
                    </div>
                </header>
                <main className="mx-auto max-w-3xl px-4 py-8">
                    <article>
                        <h1 className="mb-2 text-2xl font-semibold">
                            {post.title}
                        </h1>
                        <p className="mb-6 text-sm text-muted-foreground">
                            {post.published_at
                                ? new Date(
                                      post.published_at,
                                  ).toLocaleDateString('en-CA', {
                                      year: 'numeric',
                                      month: 'long',
                                      day: 'numeric',
                                  })
                                : null}
                            {post.author ? ` · ${post.author.name}` : null}
                        </p>
                        <div
                            className="prose prose-neutral dark:prose-invert max-w-none"
                            dangerouslySetInnerHTML={{
                                __html: post.content,
                            }}
                        />
                    </article>
                </main>
            </div>
        </>
    );
}
