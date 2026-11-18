import React, { useState, useEffect } from 'react';
import { Link, Head } from '@inertiajs/react';
import SLFAWebsiteLayout from '@/Layouts/SLFAWebsiteLayout';
import { Calendar, Users, Globe2, ArrowRight, Download } from 'lucide-react';
import { motion, AnimatePresence } from 'motion/react';
import HeroMap from '@/Components/HeroMap';
import CanvasFlag from '@/Components/CanvasFlag';

export default function Home({ auth, latestNews, upcomingEvents }) {
    const fadeUp = {
        hidden: { opacity: 0, y: 30 },
        visible: { opacity: 1, y: 0, transition: { duration: 0.8, ease: [0.22, 1, 0.36, 1] } }
    };

    const carouselImages = [
        "/images/sigiriya-rock.jpg",
        "/images/kandyan-srilankandance.jpg",
        "/images/kalaripayuttu.webp",
        "/images/Sri-Lanka-Culture.webp",
        "/images/barathanatyam.avif",
        "/images/kathakali-kerala.jpg"
    ];
    const [currentImageIdx, setCurrentImageIdx] = useState(0);

    const ctaImages = [
        "/images/cta/gallery15.jpg",
        "/images/cta/gallery27.jpg",
        "/images/cta/gallery4.jpg",
        "/images/cta/gallery9.jpg"
    ];
    const [ctaImageIdx, setCtaImageIdx] = useState(0);

    const legacyWords = ['Legacy', 'උරුමය', 'மரபு', 'వారసత్వం', 'ಪರಂಪರೆ', 'പാരമ്പര്യം'];
    const [legacyWordIdx, setLegacyWordIdx] = useState(0);

    useEffect(() => {
        const timer = setInterval(() => {
            setCurrentImageIdx((prev) => (prev + 1) % carouselImages.length);
            setCtaImageIdx((prev) => (prev + 1) % ctaImages.length);
        }, 6000); // 6 seconds per slide

        const wordTimer = setInterval(() => {
            setLegacyWordIdx((prev) => (prev + 1) % legacyWords.length);
        }, 3000); // 3 seconds per word

        return () => {
            clearInterval(timer);
            clearInterval(wordTimer);
        };
    }, []);

    const staggerContainer = {
        hidden: { opacity: 0 },
        visible: {
            opacity: 1,
            transition: {
                staggerChildren: 0.15
            }
        }
    };

    return (
        <SLFAWebsiteLayout user={auth.user} darkNav={true}>
            <Head title="Home" />

            {/* Vibrant Text-Mask Hero Section */}
            <section className="relative min-h-screen bg-gray-950 overflow-hidden flex items-center pt-10">
                {/* Animated Glowing Orbs (Saffron, Maroon, Teal/Green) */}
                <motion.div
                    animate={{ scale: [1, 1.2, 1], opacity: [0.1, 0.2, 0.1], x: [0, 50, 0] }}
                    transition={{ duration: 10, repeat: Infinity, ease: "easeInOut" }}
                    className="absolute top-[-10%] left-[-10%] w-[600px] h-[600px] bg-[#FF9933] rounded-full blur-[150px] pointer-events-none mix-blend-screen"
                ></motion.div>
                <motion.div
                    animate={{ scale: [1, 1.1, 1], opacity: [0.15, 0.3, 0.15], x: [0, -50, 0] }}
                    transition={{ duration: 12, repeat: Infinity, ease: "easeInOut", delay: 1 }}
                    className="absolute bottom-[-10%] right-[-10%] w-[800px] h-[800px] bg-[#800000] rounded-full blur-[150px] pointer-events-none mix-blend-screen"
                ></motion.div>
                <motion.div
                    animate={{ scale: [1, 1.3, 1], opacity: [0.05, 0.15, 0.05], y: [0, -50, 0] }}
                    transition={{ duration: 14, repeat: Infinity, ease: "easeInOut", delay: 2 }}
                    className="absolute top-[40%] left-[60%] w-[500px] h-[500px] bg-[#138808] rounded-full blur-[120px] pointer-events-none mix-blend-screen"
                ></motion.div>

                {/* Subtle South India/Sri Lanka Map Background */}
                <div className="absolute inset-x-0 top-0 h-[120vh] z-0 opacity-20 mix-blend-screen pointer-events-none flex justify-center items-start overflow-hidden blur-[1px]">
                    <div className="scale-150 sm:scale-100 transform origin-top w-full max-w-4xl flex justify-center">
                        <HeroMap />
                    </div>
                </div>

                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 w-full pt-10">
                    <motion.div
                        initial="hidden" animate="visible" variants={staggerContainer}
                        className="flex flex-col items-center justify-center text-center h-full"
                    >
                        <motion.div variants={fadeUp} className="mb-4 sm:mb-6 flex flex-col items-center justify-center gap-3">
                            <span className="uppercase tracking-[0.4em] text-xs font-bold text-[#D4AF37]">The Official Forum</span>
                            <div className="w-16 h-[1px] bg-gradient-to-r from-transparent via-[#D4AF37]/60 to-transparent"></div>
                        </motion.div>

                        {/* THE GIANT TEXT MASK using Canvas */}
                        <motion.div
                            initial={{ scale: 0.9, opacity: 0 }}
                            animate={{ scale: 1, opacity: 1 }}
                            transition={{ duration: 1.5, ease: [0.22, 1, 0.36, 1] }}
                            className="relative w-full overflow-visible flex justify-center items-center drop-shadow-[0_15px_35px_rgba(0,0,0,0.8)] z-10"
                        >
                            <span className="sr-only">SLFA</span>
                            <div className="w-full max-w-[1200px]" aria-hidden="true">
                                <CanvasFlag src="/images/slfa-bg.png" />
                            </div>
                        </motion.div>

                        <motion.div variants={fadeUp} className="max-w-2xl mx-auto mt-6 sm:mt-10 relative z-20">
                            <h2 className="text-3xl sm:text-4xl md:text-5xl font-abhaya font-bold text-white mb-6 leading-tight drop-shadow-lg">
                                Connecting Sri Lankans<br />in South India
                            </h2>
                            <p className="text-lg text-gray-300 font-lato font-light leading-relaxed mb-10">
                                A prestigious community fostering mutual acquaintance, cultural preservation, and professional relationships across borders.
                            </p>

                            <div className="flex flex-col sm:flex-row gap-6 justify-center items-center">
                                {!auth.user && (
                                    <Link href={route('register')} className="group flex items-center justify-center bg-[#D4AF37] text-gray-900 px-8 py-4 rounded-full font-lato text-sm uppercase tracking-widest hover:bg-white transition-all duration-500 hover:shadow-[0_0_40px_rgba(212,175,55,0.4)] font-bold border border-transparent">
                                        Become a Member
                                        <ArrowRight className="ml-3 w-4 h-4 transform group-hover:translate-x-1 transition-transform" />
                                    </Link>
                                )}
                                <Link href={route('about')} className="group flex items-center justify-center px-8 py-4 rounded-full font-lato text-sm uppercase tracking-widest text-white border border-white/20 hover:bg-white/10 transition-all duration-300">
                                    Discover Our Heritage
                                </Link>
                            </div>
                        </motion.div>
                    </motion.div>
                </div>

                {/* Seamless blend into the next section */}
                <div className="absolute inset-x-0 bottom-0 h-32 bg-gradient-to-t from-gray-950 to-transparent pointer-events-none z-20"></div>
            </section>



            {/* About / Story Section - Minimalist Carousel */}
            <section className="relative py-32 md:py-48 overflow-hidden flex items-center justify-center min-h-[700px] bg-gray-950">
                {/* Background Carousel */}
                <div className="absolute inset-0 z-0 bg-gray-950">
                    <AnimatePresence>
                        <motion.img
                            key={currentImageIdx}
                            src={carouselImages[currentImageIdx]}
                            initial={{ opacity: 0, scale: 1.05 }}
                            animate={{ opacity: 1, scale: 1 }}
                            exit={{ opacity: 0 }}
                            transition={{ duration: 1.5, ease: "easeInOut" }}
                            className="absolute inset-0 w-full h-full object-cover"
                            alt="Sri Lanka Heritage"
                        />
                    </AnimatePresence>
                    {/* Dark gradient overlay for text legibility and seamless section blending */}
                    <div className="absolute inset-0 bg-gray-950/50 mix-blend-multiply"></div>
                    <div className="absolute inset-0 bg-gradient-to-r from-gray-950 via-gray-950/80 to-transparent"></div>
                    <div className="absolute inset-0 bg-gradient-to-t from-gray-950 via-gray-950/20 to-transparent"></div>
                    <div className="absolute inset-0 bg-gradient-to-b from-gray-950 via-gray-950/40 to-transparent"></div>
                </div>

                <div className="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
                    <motion.div
                        initial={{ opacity: 0, x: -30 }}
                        whileInView={{ opacity: 1, x: 0 }}
                        viewport={{ once: true }}
                        transition={{ duration: 1 }}
                        className="max-w-2xl"
                    >
                        <span className="uppercase tracking-[0.4em] text-xs font-bold text-[#D4AF37] font-lato block mb-6">Est. 2011</span>
                        <h2 className="text-6xl md:text-7xl font-abhaya font-extrabold text-white mb-10 leading-tight">
                            A <span className="inline-flex relative">
                                <AnimatePresence mode="popLayout">
                                    <motion.span
                                        key={legacyWordIdx}
                                        initial={{ opacity: 0, y: 15 }}
                                        animate={{ opacity: 1, y: 0 }}
                                        exit={{ opacity: 0, y: -15 }}
                                        transition={{ duration: 0.4 }}
                                        className="whitespace-nowrap px-2"
                                    >
                                        {legacyWords[legacyWordIdx]}
                                    </motion.span>
                                </AnimatePresence>
                            </span> of <br /><span className="italic font-light text-[#D4AF37]">Connection</span>
                        </h2>
                        <div className="space-y-8 text-xl md:text-2xl text-gray-300 font-abhaya font-normal leading-relaxed mb-12">
                            <p>
                                The SriLankan Friendship Association of South India (SLFA) was formed to build a cohesive community among people of Sri Lankan origin living in South India.
                            </p>
                            <p>
                                Our goal is to promote mutual acquaintance, help, and foster business and professional relationships. We regularly organize cultural gatherings, medical camps, and act as a representative body for our members.
                            </p>
                        </div>
                        <Link href={route('about')} className="inline-flex items-center text-[#D4AF37] font-bold uppercase tracking-widest text-sm hover:text-white transition-colors group">
                            Read our full history
                            <ArrowRight className="ml-3 w-4 h-4 transform group-hover:translate-x-2 transition-transform" />
                        </Link>
                    </motion.div>
                </div>
            </section>

            {/* Features/Pillars Section - Book Style */}
            <section className="py-24 bg-gray-950 relative z-20">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-16 md:gap-24 pt-10">
                        {[
                            {
                                num: "I",
                                title: "Community",
                                desc: "Connecting hundreds of families across South India, providing a home away from home."
                            },
                            {
                                num: "II",
                                title: "Gatherings",
                                desc: "From Annual General Meetings to family get-togethers and cultural celebrations."
                            },
                            {
                                num: "III",
                                title: "Network",
                                desc: "Fostering cross-border trade, professional collaboration, and entrepreneurship."
                            }
                        ].map((item, idx) => (
                            <motion.div
                                key={idx}
                                initial={{ opacity: 0, y: 30 }}
                                whileInView={{ opacity: 1, y: 0 }}
                                viewport={{ once: true, margin: "-50px" }}
                                transition={{ duration: 0.6, delay: idx * 0.15, ease: "easeOut" }}
                                className="text-left"
                            >
                                <div className="text-[#D4AF37] font-abhaya italic font-light text-6xl mb-6 leading-none">
                                    {item.num}.
                                </div>
                                <h3 className="text-3xl font-abhaya font-bold text-white mb-6">{item.title}</h3>
                                <p className="text-gray-300 leading-relaxed font-abhaya text-xl md:text-2xl font-light">
                                    {item.desc}
                                </p>
                            </motion.div>
                        ))}
                    </div>
                </div>
            </section>

            {/* Minimalist News & Events Grid */}
            <section className="bg-[#faf9f7] py-32 border-t border-gray-100">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex flex-col md:flex-row justify-between items-end mb-16">
                        <div>
                            <span className="uppercase tracking-[0.4em] text-xs font-bold text-[#D4AF37] font-lato block mb-6">Updates</span>
                            <h2 className="text-6xl md:text-7xl font-abhaya font-extrabold text-gray-950 mb-10 leading-tight">
                                Community <br /><span className="italic font-light text-[#D4AF37]">Happenings</span>
                            </h2>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-16">
                        {/* News Column */}
                        <div className="space-y-8">
                            <h3 className="text-xl font-bold uppercase tracking-[0.2em] text-[#D4AF37] font-lato mb-8 border-b border-gray-200 pb-4">Latest News</h3>
                            {latestNews && latestNews.length > 0 ? latestNews.map((post, idx) => (
                                <motion.div
                                    key={post.id}
                                    initial={{ opacity: 0, y: 20 }} whileInView={{ opacity: 1, y: 0 }} viewport={{ once: true }} transition={{ delay: idx * 0.1 }}
                                    className="group cursor-pointer flex gap-8 items-start"
                                >
                                    <div className="text-center w-16 pt-1">
                                        <div className="text-2xl font-abhaya font-bold text-slfa-navy group-hover:text-slfa-maroon transition-colors">{new Date(post.published_at).getDate()}</div>
                                        <div className="text-xs font-bold uppercase tracking-widest text-gray-400">{new Date(post.published_at).toLocaleString('default', { month: 'short' })}</div>
                                    </div>
                                    <div className="flex-1">
                                        <h4 className="text-2xl font-abhaya font-bold text-slfa-navy group-hover:text-slfa-maroon transition-colors mb-3 leading-tight">
                                            <Link href={route('news')}>{post.title}</Link>
                                        </h4>
                                        <p className="text-gray-500 font-lato font-light line-clamp-2">{post.body.substring(0, 150)}...</p>
                                    </div>
                                </motion.div>
                            )) : (
                                <p className="text-gray-500 font-abhaya text-xl italic font-light">No updates available at this time.</p>
                            )}
                        </div>

                        {/* Events Column */}
                        <div className="space-y-8">
                            <h3 className="text-xl font-bold uppercase tracking-[0.2em] text-[#D4AF37] font-lato mb-8 border-b border-gray-200 pb-4">Upcoming Events</h3>
                            {upcomingEvents && upcomingEvents.length > 0 ? upcomingEvents.map((event, idx) => (
                                <motion.div
                                    key={event.id}
                                    initial={{ opacity: 0, y: 20 }} whileInView={{ opacity: 1, y: 0 }} viewport={{ once: true }} transition={{ delay: idx * 0.1 }}
                                    className="group relative overflow-hidden rounded-[2rem] bg-white shadow-[0_8px_30px_rgba(0,0,0,0.02)] hover:shadow-[0_20px_60px_rgba(0,0,0,0.06)] transition-all duration-500 p-8"
                                >
                                    <div className="absolute top-0 right-0 w-24 h-24 bg-slfa-teal/5 rounded-bl-[4rem] group-hover:bg-slfa-teal/10 transition-colors duration-500"></div>
                                    <h4 className="text-2xl font-abhaya font-bold text-slfa-navy mb-4 leading-tight">
                                        <Link href={route('events.show', event.id)} className="before:absolute before:inset-0">
                                            {event.title}
                                        </Link>
                                    </h4>
                                    <div className="flex flex-col gap-2 text-sm text-gray-500 font-lato">
                                        <span className="flex items-center"><Calendar className="w-4 h-4 mr-3 opacity-50" /> {new Date(event.start_at).toLocaleDateString()}</span>
                                        {event.location && <span className="flex items-center"><Globe2 className="w-4 h-4 mr-3 opacity-50" /> {event.location}</span>}
                                    </div>
                                </motion.div>
                            )) : (
                                <p className="text-gray-500 font-abhaya text-xl italic font-light">No upcoming events right now.</p>
                            )}
                        </div>
                    </div>
                </div>
            </section>

            {/* Downloads Call to Action */}
            <section className="bg-gray-950 py-32 md:py-48 text-white overflow-hidden relative min-h-[700px] flex flex-col items-center justify-center">
                {/* Background Carousel */}
                <div className="absolute inset-0 z-0 bg-gray-950">
                    <AnimatePresence>
                        <motion.img
                            key={ctaImageIdx}
                            src={ctaImages[ctaImageIdx]}
                            initial={{ opacity: 0, scale: 1.05 }}
                            animate={{ opacity: 1, scale: 1 }}
                            exit={{ opacity: 0 }}
                            transition={{ duration: 1.5, ease: "easeInOut" }}
                            className="absolute inset-0 w-full h-full object-cover"
                            alt="SLFA Event Gallery"
                        />
                    </AnimatePresence>
                    {/* Dark gradient overlay for text legibility */}
                    <div className="absolute inset-0 bg-gray-950/70 mix-blend-multiply"></div>
                    <div className="absolute inset-0 bg-gradient-to-t from-gray-950 via-gray-950/40 to-transparent"></div>
                    <div className="absolute inset-0 bg-gradient-to-b from-gray-950 via-gray-950/40 to-transparent"></div>
                </div>

                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10 w-full">
                    <motion.div
                        initial={{ opacity: 0, y: 30 }}
                        whileInView={{ opacity: 1, y: 0 }}
                        viewport={{ once: true }}
                        transition={{ duration: 1 }}
                        className="flex flex-col items-center"
                    >
                        <h2 className="text-5xl md:text-6xl font-abhaya font-extrabold mb-8 leading-tight drop-shadow-lg">
                            Be a part of <br /><span className="italic font-light text-[#D4AF37]">SLFA</span>
                        </h2>
                        <p className="text-xl md:text-2xl text-gray-300 font-abhaya font-light mb-16 leading-relaxed max-w-2xl mx-auto drop-shadow-md">
                            Whether you are from Sri Lanka or trace your ancestral roots to the island, we invite you to join our community. Connect, celebrate, and preserve our rich heritage together in South India.
                        </p>
                        
                        <div className="flex flex-col items-center gap-8 w-full">
                            {/* Primary Online Action */}
                            <Link href={route('register')} className="group bg-[#D4AF37] text-gray-950 hover:bg-white px-12 py-5 rounded-full font-lato text-sm uppercase tracking-widest font-bold transition-all duration-300 flex items-center justify-center hover:shadow-[0_0_30px_rgba(212,175,55,0.4)] hover:-translate-y-1">
                                Apply Online Now
                            </Link>

                            {/* Secondary Offline Actions */}
                            <div className="flex flex-col items-center mt-4 w-full">
                                <div className="flex items-center gap-4 text-xs text-gray-300 font-lato uppercase tracking-[0.2em] mb-6 w-full max-w-md opacity-80">
                                    <span className="h-px bg-white/20 flex-1"></span>
                                    <span>Or download offline forms</span>
                                    <span className="h-px bg-white/20 flex-1"></span>
                                </div>
                                <div className="flex flex-col sm:flex-row gap-4 justify-center">
                                    <a href="/storage/legacy/Individual-Membership-form.png" download className="group bg-black/30 backdrop-blur-sm border border-white/20 text-gray-200 hover:text-white hover:bg-white/10 hover:border-white/40 px-8 py-3 rounded-full font-lato text-xs uppercase tracking-widest font-bold transition-all duration-300 flex items-center justify-center">
                                        <Download className="w-3 h-3 mr-2" /> Individual Form
                                    </a>
                                    <a href="/storage/legacy/Business-Membership-form.png" download className="group bg-black/30 backdrop-blur-sm border border-white/20 text-gray-200 hover:text-white hover:bg-white/10 hover:border-white/40 px-8 py-3 rounded-full font-lato text-xs uppercase tracking-widest font-bold transition-all duration-300 flex items-center justify-center">
                                        <Download className="w-3 h-3 mr-2" /> Business Form
                                    </a>
                                </div>
                            </div>
                        </div>
                    </motion.div>
                </div>
            </section>
        </SLFAWebsiteLayout>
    );
}
