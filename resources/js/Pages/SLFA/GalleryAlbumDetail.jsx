import { Head } from '@inertiajs/react';
import SLFAWebsiteLayout from '@/Layouts/SLFAWebsiteLayout';
import SLFAPageHero from '@/Components/SLFAPageHero';
import { Calendar } from 'lucide-react';
import { useState } from 'react';
import MediaSlideshow from '@/Components/MediaSlideshow';

export default function GalleryAlbumDetail({ auth, album }) {
    const [slideIndex, setSlideIndex] = useState(null);

    return (
        <SLFAWebsiteLayout user={auth.user} darkNav={true}>
            <Head title={`${album.title} - Gallery`} />

            <SLFAPageHero eyebrow="Community memories" title={album.title} description={album.description} backHref={`${route('happenings')}#news`} backLabel="Back to news & events" image={album.cover_image_path ? `/storage/${album.cover_image_path}` : null}>
                {album.event && <span className="inline-flex items-center gap-2"><Calendar className="h-4 w-4 text-[#1B7A4A]" />{album.event.title}</span>}
            </SLFAPageHero>

            <section className="min-h-[40vh] bg-[#FAF7F2] py-16 sm:py-20">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4 sm:gap-6">
                        {album.media.map((item, index) => (
                            <button
                                type="button"
                                key={item.id}
                                className="brand-photo-frame aspect-square overflow-hidden bg-[#1B7A4A] text-left transition-transform duration-300 hover:-translate-y-1 focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#1B7A4A]"
                                style={{ animationDelay: `${(index % 10) * 0.05}s` }}
                                onClick={() => setSlideIndex(index)}
                                aria-label={`View ${item.caption || `${item.type} ${index + 1}`}`}
                            >
                                {item.type === 'video' ? (
                                    <div className="relative w-full h-full bg-gray-900 flex items-center justify-center">
                                        <div className="absolute inset-0 bg-black/40 z-10 flex items-center justify-center">
                                            <div className="w-12 h-12 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center text-white">
                                                <svg className="w-6 h-6 ml-1" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z" /></svg>
                                            </div>
                                        </div>
                                        <video src={`/storage/${item.path}`} className="w-full h-full object-cover opacity-80"></video>
                                    </div>
                                ) : (
                                    <img src={`/storage/${item.path}`} alt="" className="w-full h-full object-cover hover:scale-105 transition duration-500" loading="lazy" />
                                )}
                            </button>
                        ))}

                        {album.media.length === 0 && (
                            <div className="col-span-full border-t border-[#d8ded2] py-20 text-center">
                                <p className="text-gray-500">No media available in this album.</p>
                            </div>
                        )}
                    </div>
                </div>
            </section>

            {slideIndex !== null && <MediaSlideshow items={album.media} initialIndex={slideIndex} onClose={() => setSlideIndex(null)} title={album.title} srcForItem={item => `/storage/${item.path}`} />}
        </SLFAWebsiteLayout>
    );
}
