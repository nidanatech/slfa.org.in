import { Head, Link } from '@inertiajs/react';
import SLFAWebsiteLayout from '@/Layouts/SLFAWebsiteLayout';
import { ArrowLeft, Calendar } from 'lucide-react';
import { useState } from 'react';

export default function GalleryAlbumDetail({ auth, album }) {
    const [selectedMedia, setSelectedMedia] = useState(null);

    return (
        <SLFAWebsiteLayout user={auth.user}>
            <Head title={`${album.title} - Gallery`} />

            <div className="bg-slfa-navy text-white py-12">
                <div className="slfa-container animate-fade-up">
                    <Link href={route('gallery')} className="text-slfa-coral hover:text-white transition flex items-center text-sm font-semibold mb-6">
                        <ArrowLeft className="w-4 h-4 mr-1" /> Back to Gallery
                    </Link>
                    <h1 className="text-3xl md:text-4xl font-bold mb-2">{album.title}</h1>
                    {album.event && (
                        <div className="flex items-center text-gray-300">
                            <Calendar className="w-4 h-4 mr-2" />
                            <span>Event: {album.event.title}</span>
                        </div>
                    )}
                </div>
            </div>

            <div className="slfa-section bg-slfa-cream min-h-screen">
                <div className="slfa-container">
                    <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        {album.media.map((item, index) => (
                            <div 
                                key={item.id} 
                                className="aspect-square bg-white rounded-lg shadow-sm overflow-hidden cursor-pointer hover:shadow-md transition animate-fade-up"
                                style={{ animationDelay: `${(index % 10) * 0.05}s` }}
                                onClick={() => setSelectedMedia(item)}
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
                            </div>
                        ))}

                        {album.media.length === 0 && (
                            <div className="col-span-full text-center py-20 bg-white rounded-lg shadow-sm">
                                <p className="text-gray-500">No media available in this album.</p>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* Lightbox Modal */}
            {selectedMedia && (
                <div className="fixed inset-0 z-[100] bg-black/90 flex items-center justify-center p-4 backdrop-blur-sm" onClick={() => setSelectedMedia(null)}>
                    <button className="absolute top-4 right-4 text-white hover:text-slfa-coral transition bg-white/10 rounded-full p-2">
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
