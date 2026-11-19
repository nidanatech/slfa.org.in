import { Head, Link } from '@inertiajs/react';
import SLFAWebsiteLayout from '@/Layouts/SLFAWebsiteLayout';
import SLFAPageHero from '@/Components/SLFAPageHero';
import { Calendar } from 'lucide-react';
import dayjs from 'dayjs';
import { coverImageUrl } from '@/lib/coverImageUrl';
import { useState } from 'react';
import MediaSlideshow from '@/Components/MediaSlideshow';

export default function NewsDetail({ auth, post }) {
    const [slideIndex, setSlideIndex] = useState(null);
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
                    {post.photos?.length > 0 && <section className="mt-12 border-t border-[#b7a676] pt-8" aria-labelledby="story-photos-heading">
                        <h2 id="story-photos-heading" className="font-abhaya text-3xl font-bold text-gray-950">Photos from this story</h2>
                        <div className="mt-6 grid gap-4 sm:grid-cols-2 md:grid-cols-3">
                            {post.photos.map((photo, index) => <figure key={photo.id}>
                                <button type="button" onClick={() => setSlideIndex(index)} aria-label={`Open ${photo.caption || `photo ${index + 1}`} in slideshow`} className="block w-full overflow-hidden text-left focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#800000]"><img src={coverImageUrl(photo.path)} alt={photo.caption || `Photo from ${post.title}`} loading="lazy" className="aspect-[4/3] w-full object-cover transition-transform duration-500 hover:scale-105" /></button>
                                {photo.caption && <figcaption className="mt-2 text-sm text-gray-600">{photo.caption}</figcaption>}
                            </figure>)}
                        </div>
                    </section>}
                    <Link href={route('happenings')} className="mt-8 inline-flex items-center font-bold text-[#800000] hover:underline">Explore more news</Link>
                </div>
            </section>
            {slideIndex !== null && <MediaSlideshow items={post.photos} initialIndex={slideIndex} onClose={() => setSlideIndex(null)} title={post.title} srcForItem={item => coverImageUrl(item.path)} />}
        </SLFAWebsiteLayout>
    );
}
