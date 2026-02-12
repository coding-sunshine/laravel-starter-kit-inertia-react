import AppLogoIcon from '@/components/app-logo-icon';
import TextLink from '@/components/text-link';
import { home } from '@/routes';
import { index as blogIndex, show as blogShow } from '@/routes/blog';
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
    published_at: string | null;
    author?: Author;
}

interface PaginatorLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Props {
    posts: {
        data: Post[];
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
        links: PaginatorLink[];
    };
}

export default function BlogIndex({ posts }: Props) {
    return (
        <>
            <Head title="Blog" />
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
                    <h1 className="mb-6 text-2xl font-semibold">Blog</h1>
                    {posts.data.length === 0 ? (
                        <div className="flex flex-col items-center justify-center rounded-lg border border-dashed py-16 text-center">
                            <p className="text-sm font-medium text-muted-foreground">
                                No posts yet
                            </p>
                            <p className="mt-1 text-xs text-muted-foreground">
                                Check back soon for new articles.
                            </p>
                        </div>
                    ) : (
                    <ul className="space-y-6">
                        {posts.data.map((post) => (
                            <li key={post.id}>
                                <Link
                                    href={blogShow({ post: post.slug }).url}
                                    className="block rounded-lg border bg-card p-4 transition-colors hover:bg-muted/50"
                                >
                                    <h2 className="font-medium text-foreground">
                                        {post.title}
                                    </h2>
                                    {post.excerpt && (
                                        <p className="mt-1 text-sm text-muted-foreground line-clamp-2">
                                            {post.excerpt}
                                        </p>
                                    )}
                                    <p className="mt-2 text-xs text-muted-foreground">
                                        {post.published_at
                                            ? new Date(
                                                  post.published_at,
                                              ).toLocaleDateString('en-CA', {
                                                  year: 'numeric',
                                                  month: 'long',
                                                  day: 'numeric',
                                              })
                                            : null}
                                        {post.author
                                            ? ` · ${post.author.name}`
                                            : null}
                                    </p>
                                </Link>
                            </li>
                        ))}
                    </ul>
                    )}
                    {(posts.prev_page_url || posts.next_page_url) && (
                        <nav
                            className="mt-8 flex items-center justify-center gap-4"
                            aria-label="Pagination"
                        >
                            {posts.prev_page_url ? (
                                <Link
                                    href={posts.prev_page_url}
                                    className="text-sm font-medium text-foreground underline underline-offset-4"
                                >
                                    Previous
                                </Link>
                            ) : null}
                            <span className="text-sm text-muted-foreground">
                                Page {posts.current_page} of {posts.last_page}
                            </span>
                            {posts.next_page_url ? (
                                <Link
                                    href={posts.next_page_url}
                                    className="text-sm font-medium text-foreground underline underline-offset-4"
                                >
                                    Next
                                </Link>
                            ) : null}
                        </nav>
                    )}
                </main>
            </div>
        </>
    );
}
