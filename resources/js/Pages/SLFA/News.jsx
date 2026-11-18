import { Link, Head, router } from '@inertiajs/react';
import SLFAWebsiteLayout from '@/Layouts/SLFAWebsiteLayout';
import { Calendar, ChevronRight, ArrowRight } from 'lucide-react';
import { motion } from 'motion/react';

export default function News({ auth, posts, categories, currentCategory }) {

    const handleCategory = (cat) => {
        if (cat) {
            router.get(route('news'), { category: cat }, { preserveState: true });
        } else {
            router.get(route('news'), {}, { preserveState: true });
        }
    };

    const milestones = [
        {
            title: 'Annual General Meetings',
            items: ['Fourth AGM: 30th September 2023', 'Third AGM: 24th September 2022', 'Second AGM: 11th December 2021']
        },
        {
            title: 'Annual Get Togethers',
            items: ['16 Nov 2019: Dinner at Hotel GRT Grand, Chennai', '23 Mar 2019: Free health check-up at Dr. Rela Institute', '15 Dec 2018: Dinner at Hotel Accord Metropolitan']
        }
    ];

    return (
        <SLFAWebsiteLayout user={auth.user} darkNav={true}>
            <Head title="News & Activities" />

            {/* ── Dark Hero Banner ── */}
            <section className="relative pt-36 pb-24 bg-gray-950 overflow-hidden">
                <motion.div animate={{ scale: [1, 1.2, 1], opacity: [0.06, 0.15, 0.06] }} transition={{ duration: 12, repeat: Infinity }} className="absolute top-0 right-0 w-[500px] h-[500px] bg-[#D4AF37] rounded-full blur-[180px] pointer-events-none mix-blend-screen" />
                <motion.div animate={{ scale: [1, 1.15, 1], opacity: [0.08, 0.18, 0.08] }} transition={{ duration: 10, repeat: Infinity, delay: 1 }} className="absolute bottom-0 left-0 w-[400px] h-[400px] bg-[#800000] rounded-full blur-[150px] pointer-events-none mix-blend-screen" />

                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
                    <motion.div initial={{ opacity: 0, y: 30 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.8 }} className="flex flex-col items-center gap-5">
                        <span className="uppercase tracking-[0.4em] text-xs font-bold text-[#D4AF37] font-lato">Latest from SLFA</span>
                        <div className="w-16 h-[1px] bg-gradient-to-r from-transparent via-[#D4AF37]/60 to-transparent" />
                        <h1 className="text-5xl sm:text-6xl md:text-7xl font-abhaya font-extrabold text-white leading-tight tracking-tight">
                            News &amp; <span className="italic font-light text-[#D4AF37]">Activities</span>
                        </h1>
                        <p className="text-gray-400 font-lato font-light text-lg max-w-xl">Stay updated with the latest happenings at SLFA</p>
                    </motion.div>
                </div>
            </section>

            {/* ── Filter & Grid ── */}
            <div className="bg-[#FAF8F4] min-h-screen py-20">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

                    {/* Pill filter tabs */}
                    <div className="mb-12 flex flex-col sm:flex-row justify-between items-center gap-6">
                        <p className="text-sm text-gray-400 font-lato">
                            Showing <span className="text-gray-700 font-semibold">{posts.data.length}</span> of <span className="text-gray-700 font-semibold">{posts.total}</span> posts
                        </p>
                        <div className="flex flex-wrap gap-2">
                            <button
                                onClick={() => handleCategory('')}
                                className={`px-5 py-2 rounded-full text-xs font-bold uppercase tracking-widest font-lato transition-all duration-300 ${!currentCategory ? 'bg-[#D4AF37] text-gray-900 shadow-[0_0_15px_rgba(212,175,55,0.3)]' : 'bg-white border border-gray-200 text-gray-500 hover:border-[#D4AF37]/50 hover:text-gray-800'}`}
                            >All</button>
                            {categories.map(cat => (
                                <button
                                    key={cat}
                                    onClick={() => handleCategory(cat)}
                                    className={`px-5 py-2 rounded-full text-xs font-bold uppercase tracking-widest font-lato transition-all duration-300 ${currentCategory === cat ? 'bg-[#D4AF37] text-gray-900 shadow-[0_0_15px_rgba(212,175,55,0.3)]' : 'bg-white border border-gray-200 text-gray-500 hover:border-[#D4AF37]/50 hover:text-gray-800'}`}
                                >{cat}</button>
                            ))}
                        </div>
                    </div>

                    {/* Posts Grid */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
                        {posts.data.length > 0 ? posts.data.map((post, idx) => (
                            <motion.div
                                key={post.id}
                                initial={{ opacity: 0, y: 20 }} whileInView={{ opacity: 1, y: 0 }} viewport={{ once: true }} transition={{ duration: 0.5, delay: idx * 0.07 }}
                                className="group bg-white rounded-3xl overflow-hidden border border-gray-100 hover:shadow-xl hover:border-[#D4AF37]/20 transition-all duration-500 flex flex-col"
                            >
                                <div className="relative overflow-hidden h-52">
                                    {post.cover_image_path ? (
                                        <img src={`/storage/${post.cover_image_path}`} alt={post.title} loading="lazy" decoding="async" className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" />
                                    ) : (
                                        <div className="w-full h-full bg-gray-950 flex items-center justify-center">
                                            <span className="text-[#D4AF37]/30 font-abhaya font-black text-6xl">SLFA</span>
                                        </div>
                                    )}
                                    {/* Gold top stripe on hover */}
                                    <div className="absolute top-0 left-0 right-0 h-[3px] bg-gradient-to-r from-[#D4AF37] to-[#800000] transform -translate-y-full group-hover:translate-y-0 transition-transform duration-500" />
                                </div>

                                <div className="p-7 flex flex-col flex-grow">
                                    <div className="flex justify-between items-center mb-4">
                                        <span className="text-[#D4AF37] text-[10px] font-bold uppercase tracking-widest font-lato bg-[#D4AF37]/10 px-3 py-1 rounded-full">
                                            {post.category || 'General'}
                                        </span>
                                        <span className="text-gray-400 text-xs font-lato flex items-center gap-1">
                                            <Calendar className="w-3 h-3" /> {new Date(post.published_at).toLocaleDateString()}
                                        </span>
                                    </div>
                                    <h2 className="text-xl font-abhaya font-bold text-gray-900 mb-3 leading-snug group-hover:text-[#800000] transition-colors duration-300">{post.title}</h2>
                                    <p className="text-gray-500 font-lato text-sm leading-relaxed line-clamp-3 mb-6 flex-grow">
                                        {post.body.replace(/(<([^>]+)>)/gi, "").substring(0, 150)}...
                                    </p>
                                    <Link href={route('news.show', post.id)} className="inline-flex items-center text-[#800000] font-bold font-lato text-sm hover:gap-2 transition-all duration-200 group/link mt-auto">
                                        Read More <ChevronRight className="w-4 h-4 ml-1 group-hover/link:translate-x-1 transition-transform" />
                                    </Link>
                                </div>
                            </motion.div>
                        )) : (
                            <div className="col-span-full text-center py-24 bg-white rounded-3xl border border-gray-100">
                                <p className="text-gray-400 font-lato text-lg mb-4">No posts found for this category.</p>
                                <button onClick={() => router.get(route('news'))} className="text-[#D4AF37] font-bold font-lato text-sm hover:underline">Clear Filters</button>
                            </div>
                        )}
                    </div>

                    {/* Pagination */}
                    {posts.links.length > 3 && (
                        <div className="flex justify-center gap-2 mb-20">
                            {posts.links.map((link, i) => (
                                <Link
                                    key={i}
                                    href={link.url || '#'}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                    className={`px-4 py-2 rounded-full text-sm font-lato font-bold transition-all duration-300 ${
                                        link.active
                                            ? 'bg-[#D4AF37] text-gray-900 shadow-[0_0_15px_rgba(212,175,55,0.25)]'
                                            : !link.url
                                                ? 'text-gray-300 cursor-not-allowed'
                                                : 'bg-white text-gray-500 border border-gray-200 hover:border-[#D4AF37]/40'
                                    }`}
                                />
                            ))}
                        </div>
                    )}

                    {/* Past Milestones */}
                    <div className="border-t border-gray-200 pt-16">
                        <div className="text-center mb-14">
                            <span className="uppercase tracking-[0.4em] text-xs font-bold text-[#D4AF37] font-lato block mb-4">Looking Back</span>
                            <h2 className="text-4xl font-abhaya font-extrabold text-gray-900">Past Milestone Activities</h2>
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                            {milestones.map((m, idx) => (
                                <motion.div
                                    key={idx}
                                    initial={{ opacity: 0, y: 20 }} whileInView={{ opacity: 1, y: 0 }} viewport={{ once: true }} transition={{ delay: idx * 0.15 }}
                                    className="bg-white border border-gray-100 p-8 rounded-3xl hover:shadow-lg hover:border-[#D4AF37]/20 transition-all duration-500"
                                >
                                    <h3 className="text-lg font-abhaya font-bold text-gray-900 mb-6 flex items-center gap-3">
                                        <span className="w-2 h-2 rounded-full bg-[#D4AF37] inline-block"></span>
                                        {m.title}
                                    </h3>
                                    <ul className="space-y-3">
                                        {m.items.map((item, i) => (
                                            <li key={i} className="text-gray-500 font-lato text-sm flex items-start gap-3">
                                                <span className="text-[#D4AF37] font-bold mt-0.5">—</span>
                                                {item}
                                            </li>
                                        ))}
                                    </ul>
                                </motion.div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </SLFAWebsiteLayout>
    );
}
