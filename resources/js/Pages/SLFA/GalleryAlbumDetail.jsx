import { Head } from '@inertiajs/react';
import SLFAWebsiteLayout from '@/Layouts/SLFAWebsiteLayout';
import SLFAPageHero from '@/Components/SLFAPageHero';
import { Calendar } from 'lucide-react';
import { useState } from 'react';

export default function GalleryAlbumDetail({ auth, album }) {
    const [selectedMedia, setSelectedMedia] = useState(null);

    return (
        <SLFAWebsiteLayout user={auth.user} darkNav={true}>
            <Head title={`${album.title} - Gallery`} />

            <SLFAPageHero eyebrow="Community memories" title={album.title} description={album.description} backHref={route('gallery')} backLabel="Back to gallery" image={album.cover_image_path ? `/storage/${album.cover_image_path}` : null}>
                {album.event && <span className="inline-flex items-center gap-2"><Calendar className="h-4 w-4 text-[#D4AF37]" />{album.event.title}</span>}
            </SLFAPageHero>

            <section className="min-h-[40vh] bg-[#FAF8F4] py-16 sm:py-20">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4 sm:gap-6">
                        {album.media.map((item, index) => (
                            <button
                                type="button"
                                key={item.id} 
                                className="aspect-square overflow-hidden rounded-2xl border border-gray-100 bg-white text-left shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#D4AF37]"
                                style={{ animationDelay: `${(index % 10) * 0.05}s` }}
                                onClick={() => setSelectedMedia(item)}
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
                            <div className="col-span-full rounded-3xl border border-gray-100 bg-white py-20 text-center shadow-sm">
                                <p className="text-gray-500">No media available in this album.</p>
                            </div>
                        )}
                    </div>
                </div>
            </section>

            {/* Lightbox Modal */}
            {selectedMedia && (
                <div className="fixed inset-0 z-[100] bg-black/90 flex items-center justify-center p-4 backdrop-blur-sm" onClick={() => setSelectedMedia(null)}>
                    <button type="button" aria-label="Close image" className="absolute top-4 right-4 text-white hover:text-[#D4AF37] transition bg-white/10 rounded-full p-2">
                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                    
                    <div className="max-w-5xl w-full max-h-[90vh] flex flex-col items-center justify-center" onClick={e => e.stopPropagation()}>
                        {selectedMedia.type === 'video' ? (
                            <video src={`/storage/${selectedMedia.path}`} controls autoPlay className="max-w-full max-h-[80vh] rounded shadow-2xl"></video>
                        ) : (
                            <img src={`/storage/${selectedMedia.path}`} alt="" className="max-w-full max-h-[80vh] object-contain rounded shadow-2xl" />
                        )}
                        {selectedMedia.caption && (
                            <p className="text-white mt-4 text-center">{selectedMedia.caption}</p>
                        )}
                    </div>
                </div>
            )}
        </SLFAWebsiteLayout>
    );
}
