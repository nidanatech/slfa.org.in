import { Head, Link } from '@inertiajs/react';
import SLFAWebsiteLayout from '@/Layouts/SLFAWebsiteLayout';
import SLFAPageHero from '@/Components/SLFAPageHero';
import { Calendar } from 'lucide-react';
import dayjs from 'dayjs';
import { coverImageUrl } from '@/lib/coverImageUrl';

export default function NewsDetail({ auth, post }) {
    return (
        <SLFAWebsiteLayout user={auth.user} darkNav={true}>
            <Head title={post.title} />
            <SLFAPageHero eyebrow={post.category || 'News & activities'} title={post.title} backHref={`${route('happenings')}#news`} backLabel="Back to news" image={coverImageUrl(post.cover_image_path)}>
                <span className="inline-flex items-center gap-2"><Calendar className="h-4 w-4 text-[#D4AF37]" />Published {dayjs(post.published_at || post.created_at).format('MMMM D, YYYY')}</span>
            </SLFAPageHero>

            <section className="min-h-[40vh] bg-[#f5f1e9] py-16 sm:py-20">
                <div className="mx-auto max-w-4xl px-4 sm:px-6">
                    <article className="border-t border-[#b7a676] pt-8">
                        <div className="prose prose-lg max-w-none whitespace-pre-wrap text-gray-700">{post.body}</div>
                    </article>
                    <Link href={route('happenings')} className="mt-8 inline-flex items-center font-bold text-[#800000] hover:underline">Explore more news</Link>
                </div>
            </section>
        </SLFAWebsiteLayout>
    );
}
