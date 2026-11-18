import { Head, Link } from '@inertiajs/react';
import SLFAWebsiteLayout from '@/Layouts/SLFAWebsiteLayout';
import { Image as ImageIcon, Camera, ArrowRight } from 'lucide-react';
import { motion } from 'motion/react';

export default function Gallery({ auth, albums }) {
    return (
        <SLFAWebsiteLayout user={auth.user} darkNav={true}>
            <Head title="Gallery" />

            {/* ── Dark Hero Banner ── */}
            <section className="relative pt-36 pb-24 bg-gray-950 overflow-hidden">
                <motion.div animate={{ scale: [1, 1.2, 1], opacity: [0.06, 0.15, 0.06] }} transition={{ duration: 12, repeat: Infinity }} className="absolute top-0 right-0 w-[500px] h-[500px] bg-[#D4AF37] rounded-full blur-[180px] pointer-events-none mix-blend-screen" />
                <motion.div animate={{ scale: [1, 1.15, 1], opacity: [0.08, 0.18, 0.08] }} transition={{ duration: 10, repeat: Infinity, delay: 1 }} className="absolute bottom-0 left-0 w-[400px] h-[400px] bg-[#800000] rounded-full blur-[150px] pointer-events-none mix-blend-screen" />

                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
                    <motion.div initial={{ opacity: 0, y: 30 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.8 }} className="flex flex-col items-center gap-5">
                        <span className="uppercase tracking-[0.4em] text-xs font-bold text-[#D4AF37] font-lato">Our Memories</span>
                        <div className="w-16 h-[1px] bg-gradient-to-r from-transparent via-[#D4AF37]/60 to-transparent" />
                        <h1 className="text-5xl sm:text-6xl md:text-7xl font-abhaya font-extrabold text-white leading-tight tracking-tight">
                            Photo <span className="italic font-light text-[#D4AF37]">Gallery</span>
                        </h1>
                        <p className="text-gray-400 font-lato font-light text-lg">Memories from our past events and gatherings</p>
                    </motion.div>
                </div>
            </section>

            {/* ── Albums Grid ── */}
            <div className="bg-[#FAF8F4] min-h-screen py-20">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {albums.length > 0 ? (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                            {albums.map((album, idx) => (
                                <motion.div
                                    key={album.id}
                                    initial={{ opacity: 0, y: 20 }}
                                    whileInView={{ opacity: 1, y: 0 }}
                                    viewport={{ once: true }}
                                    transition={{ duration: 0.5, delay: idx * 0.07 }}
                                >
                                    <Link href={route('gallery.show', album.id)} className="group block rounded-3xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-500">
                                        {/* Full-bleed image with overlay */}
                                        <div className="relative aspect-[4/3] overflow-hidden bg-gray-900">
                                            {album.cover_image_path ? (
                                                <img
                                                    src={`/storage/${album.cover_image_path}`}
                                                    alt={album.title}
                                                    className="w-full h-full object-cover group-hover:scale-[1.06] transition-transform duration-700"
                                                />
                                            ) : (
                                                <div className="w-full h-full flex items-center justify-center bg-gray-900">
                                                    <Camera className="w-16 h-16 text-gray-700" />
                                                </div>
                                            )}

                                            {/* Permanent subtle dark gradient at bottom */}
                                            <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-black/10 to-transparent" />

                                            {/* Photo count badge */}
                                            <div className="absolute top-4 right-4 bg-black/50 backdrop-blur-sm text-white text-xs px-3 py-1 rounded-full flex items-center gap-1 font-lato">
                                                <ImageIcon className="w-3 h-3 text-[#D4AF37]" />
                                                {album.media_count} items
                                            </div>

                                            {/* Title & date revealed at bottom */}
                                            <div className="absolute bottom-0 left-0 right-0 p-6">
                                                <p className="text-[#D4AF37] text-[10px] font-bold uppercase tracking-widest font-lato mb-1">
                                                    {new Date(album.created_at).toLocaleDateString(undefined, { year: 'numeric', month: 'long' })}
                                                </p>
                                                <h2 className="text-white font-abhaya font-bold text-2xl leading-snug group-hover:text-[#D4AF37] transition-colors duration-300">
                                                    {album.title}
                                                </h2>
                                                <div className="mt-3 flex items-center gap-2 text-white/60 text-sm font-lato group-hover:text-white/90 transition-colors">
                                                    View Album <ArrowRight className="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                                                </div>
                                            </div>
                                        </div>
                                    </Link>
                                </motion.div>
                            ))}
                        </div>
                    ) : (
                        <div className="text-center py-32">
                            <div className="w-20 h-20 rounded-full bg-white border-2 border-[#D4AF37]/30 flex items-center justify-center mx-auto mb-6">
                                <Camera className="w-8 h-8 text-[#D4AF37]" />
                            </div>
                            <p className="text-gray-500 font-lato text-lg mb-2">No gallery albums yet.</p>
                            <p className="text-gray-400 font-lato text-sm">Check back soon for photos from our events.</p>
                        </div>
                    )}
                </div>
            </div>
        </SLFAWebsiteLayout>
    );
}
