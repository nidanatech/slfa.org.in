import React, { useState, useEffect } from 'react';
import { Link, Head } from '@inertiajs/react';
import SLFAWebsiteLayout from '@/Layouts/SLFAWebsiteLayout';
import { MapPin, ArrowRight } from 'lucide-react';
import { motion, AnimatePresence, useReducedMotion } from 'motion/react';
import HeroMap from '@/Components/HeroMap';
import CanvasFlag from '@/Components/CanvasFlag';
import CommunityWord from '@/Components/CommunityWord';
import PhotoBackdrop from '@/Components/PhotoBackdrop';
import { coverImageUrl } from '@/lib/coverImageUrl';

const happeningsImages = ['/images/cta/gallery27.jpg', '/images/cta/gallery15.jpg', '/images/cta/gallery4.jpg'];

export default function Home({ auth, latestNews, upcomingEvents }) {
    const featuredEvent = upcomingEvents?.[0];
    const reducedMotion = useReducedMotion();
    const fadeUp = {
        hidden: { opacity: 0, y: 30 },
        visible: { opacity: 1, y: 0, transition: { duration: 0.8, ease: [0.22, 1, 0.36, 1] } }
    };

    const carouselImages = [
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

    useEffect(() => {
        if (reducedMotion) return undefined;
        const timer = setInterval(() => {
            setCurrentImageIdx((prev) => (prev + 1) % carouselImages.length);
            setCtaImageIdx((prev) => (prev + 1) % ctaImages.length);
        }, 6000); // 6 seconds per slide

        return () => {
            clearInterval(timer);
        };
    }, [reducedMotion]);

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
        <SLFAWebsiteLayout user={auth.user} darkNav={true} vibrant>
            <Head title="Sri Lankan Friendship Association of South India" />

            {/* Hero */}
            <section className="relative min-h-[100svh] bg-[#091724] overflow-hidden flex items-center pt-24 pb-14 sm:pt-32 sm:pb-20">
                {/* Animated Glowing Orbs (Saffron, Maroon, Teal/Green) */}
                <motion.div
                    initial={{ opacity: 0.12 }}
                    animate={reducedMotion ? undefined : { scale: [1, 1.2, 1], opacity: [0.1, 0.2, 0.1], x: [0, 50, 0] }}
                    transition={{ duration: 10, repeat: Infinity, ease: "easeInOut" }}
                    className="absolute top-[-10%] left-[-10%] w-[600px] h-[600px] bg-[#0891B2] rounded-full blur-[150px] pointer-events-none mix-blend-screen"
                ></motion.div>
                <motion.div
                    initial={{ opacity: 0.12 }}
                    animate={reducedMotion ? undefined : { scale: [1, 1.1, 1], opacity: [0.15, 0.3, 0.15], x: [0, -50, 0] }}
                    transition={{ duration: 12, repeat: Infinity, ease: "easeInOut", delay: 1 }}
                    className="absolute bottom-[-10%] right-[-10%] w-[800px] h-[800px] bg-[#0D9488] rounded-full blur-[150px] pointer-events-none mix-blend-screen"
                ></motion.div>
                <motion.div
                    initial={{ opacity: 0.12 }}
                    animate={reducedMotion ? undefined : { scale: [1, 1.3, 1], opacity: [0.05, 0.15, 0.05], y: [0, -50, 0] }}
                    transition={{ duration: 14, repeat: Infinity, ease: "easeInOut", delay: 2 }}
                    className="absolute top-[40%] left-[60%] w-[500px] h-[500px] bg-[#2563EB] rounded-full blur-[120px] pointer-events-none mix-blend-screen"
                ></motion.div>

                {/* Subtle South India/Sri Lanka Map Background */}
                <div className="absolute inset-x-0 top-0 h-[120vh] z-0 opacity-[0.12] grayscale mix-blend-screen pointer-events-none flex justify-center items-start overflow-hidden blur-[1px]" aria-hidden="true">
                    <div className="scale-100 transform origin-top w-full max-w-4xl flex justify-center">
                        <HeroMap />
                    </div>
                </div>

                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 w-full">
                    <motion.div
                        initial="hidden" animate="visible" variants={staggerContainer}
                        className="flex flex-col items-center justify-center text-center h-full"
                    >
                        <motion.div variants={fadeUp} className="mb-3 sm:mb-5 flex flex-col items-center justify-center gap-3">
                            <span className="uppercase tracking-[0.14em] sm:tracking-[0.24em] text-[10px] sm:text-xs font-bold text-[#5EEAD4] font-sans">Sri Lankan Friendship Association of South India</span>
                            <div className="w-16 h-[1px] bg-gradient-to-r from-transparent via-[#5EEAD4]/60 to-transparent"></div>
                        </motion.div>

                        {/* Animated wordmark supports the heading below */}
                        <motion.div
                            initial={{ scale: 0.9, opacity: 0 }}
                            animate={{ scale: 1, opacity: 1 }}
                            transition={{ duration: 1.5, ease: [0.22, 1, 0.36, 1] }}
                            className="relative w-full overflow-visible flex justify-center items-center drop-shadow-[0_15px_35px_rgba(0,0,0,0.8)] z-10"
                            aria-hidden="true"
                        >
                            <div className="w-full max-w-[700px]">
                                <CanvasFlag src="/images/slfa-bg.png" animate={!reducedMotion} />
                            </div>
                        </motion.div>

                        <motion.div variants={fadeUp} className="max-w-2xl mx-auto mt-3 sm:mt-5 relative z-20">
                            <h1 className="text-4xl sm:text-5xl md:text-6xl font-sans font-bold text-white mb-4 sm:mb-5 leading-[1.05] drop-shadow-lg">
                                Connecting Sri Lankans<br />in South India
                            </h1>
                            <p className="text-base sm:text-lg text-gray-300 font-sans font-normal leading-relaxed mb-7 sm:mb-8">
                                Shared roots. New friendships. A community to call your own.
                            </p>

                            <div className="flex flex-col sm:flex-row gap-3 sm:gap-5 justify-center items-center">
                                {!auth.user && (
                                    <Link href={route('register')} className="group flex items-center justify-center bg-[#FF977D] text-[#09202b] px-7 py-3.5 rounded-full font-sans text-sm sm:text-base tracking-normal hover:bg-white transition-all duration-500 hover:shadow-[0_0_40px_rgba(255,151,125,0.25)] font-bold border border-transparent">
                                        Apply for membership
                                        <ArrowRight className="ml-3 w-4 h-4 transform group-hover:translate-x-1 transition-transform" />
                                    </Link>
                                )}
                                <Link href={route('about')} className="group flex items-center justify-center px-7 py-3.5 rounded-full font-sans text-sm sm:text-base tracking-normal text-white border border-white/30 hover:bg-white/10 transition-all duration-300">
                                    Explore the association
                                </Link>
                            </div>
                        </motion.div>

                        <motion.div variants={fadeUp} className="relative z-20 mt-9 w-full max-w-3xl border-t border-[#5EEAD4]/45 pt-5 text-left sm:mt-11">
                            <Link href={featuredEvent ? route('events.show', featuredEvent.public_id) : route('happenings')} className="group grid gap-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#5EEAD4]">
                                <div className="min-w-0">
                                    <span className="font-sans text-[11px] font-bold uppercase tracking-[0.22em] text-[#5EEAD4]">Upcoming event</span>
                                    <h2 className="mt-2 font-sans text-xl font-bold leading-snug text-white transition-colors group-hover:text-[#99F6E4] sm:text-2xl">{featuredEvent ? featuredEvent.title : 'Find your next SLFA gathering'}</h2>
                                    {featuredEvent ? <span className="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 font-sans text-sm text-gray-300">
                                        <span>{new Date(featuredEvent.start_at).toLocaleDateString('en-IN', { day: 'numeric', month: 'long', year: 'numeric', timeZone: 'Asia/Kolkata' })}</span>
                                        {featuredEvent.location && <span className="inline-flex items-center gap-1.5"><MapPin className="h-3.5 w-3.5 shrink-0 text-[#5EEAD4]" aria-hidden="true" />{featuredEvent.location}</span>}
                                    </span> : <p className="mt-2 font-sans text-sm text-gray-300">See what is coming up in the community.</p>}
                                </div>
                                <span className="inline-flex items-center gap-2 border-b border-[#5EEAD4]/70 pb-1 font-sans text-xs font-bold uppercase tracking-wider text-[#5EEAD4] sm:mb-1">{featuredEvent ? 'Event details' : 'Explore events'} <ArrowRight className="h-4 w-4 transition-transform group-hover:translate-x-1" aria-hidden="true" /></span>
                            </Link>
                        </motion.div>
                    </motion.div>
                </div>

                {/* Seamless blend into the next section */}
                <div className="absolute inset-x-0 bottom-0 h-32 bg-gradient-to-t from-[#091724] to-transparent pointer-events-none z-[1]"></div>
            </section>



            {/* About / Story Section */}
            <section className="relative py-16 md:py-24 overflow-hidden bg-[#091724]">
                {/* Background Carousel */}
                <div className="absolute inset-0 z-0 bg-[#091724]">
                    <AnimatePresence>
                        <motion.img
                            key={currentImageIdx}
                            src={carouselImages[currentImageIdx]}
                            initial={{ opacity: 0, scale: 1.05 }}
                            animate={{ opacity: 1, scale: 1 }}
                            exit={{ opacity: 0 }}
                            transition={{ duration: 1.5, ease: "easeInOut" }}
                            className="absolute inset-y-0 right-0 w-full md:w-[65%] h-full object-cover"
                            alt=""
                        />
                    </AnimatePresence>
                    {/* Dark gradient overlay for text legibility and seamless section blending */}
                    <div className="absolute inset-0 bg-[#091724]/10"></div>
                    <div className="absolute inset-0 bg-gradient-to-r from-[#091724] via-[#091724]/80 to-transparent"></div>
                    <div className="absolute inset-0 bg-gradient-to-t from-[#091724]/80 via-transparent to-[#091724]/30"></div>
                </div>

                <div className="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
                    <motion.div
                        initial={{ opacity: 0, x: -30 }}
                        whileInView={{ opacity: 1, x: 0 }}
                        viewport={{ once: true }}
                        transition={{ duration: 1 }}
                        className="max-w-xl"
                    >
                        <span className="uppercase tracking-[0.24em] text-xs font-bold text-[#5EEAD4] font-sans block mb-4">Who we are · Est. 2011</span>
                        <h2 className="text-3xl sm:text-5xl font-sans font-extrabold text-white mb-5 leading-tight">
                            <span className="sr-only">A community built on connection</span>
                            <span aria-hidden="true">A <CommunityWord /><br />built on <span className="not-italic font-bold text-[#5EEAD4]">connection</span></span>
                        </h2>
                        <div className="space-y-4 text-base md:text-lg text-gray-200 font-sans font-normal leading-relaxed mb-7">
                            <p>
                                SLFA brings together people of Sri Lankan origin living in South India through friendship, cultural gatherings, and mutual support.
                            </p>
                            <p>
                                Our activities include community events, medical camps, and opportunities to build professional relationships.
                            </p>
                        </div>
                        <Link href={route('about')} className="inline-flex items-center text-[#5EEAD4] font-bold uppercase tracking-wider text-xs sm:text-sm hover:text-white transition-colors group">
                            Meet the association
                            <ArrowRight className="ml-3 w-4 h-4 transform group-hover:translate-x-2 transition-transform" />
                        </Link>
                    </motion.div>
                </div>
            </section>

            {/* Three ways members connect */}
            <section className="py-12 md:py-16 bg-[#091724] relative z-20">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8 md:gap-12">
                        {[
                            {
                                num: "I",
                                image: "/images/cta/gallery15.jpg",
                                alt: "SLFA members spending time together at a community gathering",
                                title: "Community",
                                desc: "Meet people who share a connection with Sri Lanka and South India."
                            },
                            {
                                num: "II",
                                image: "/images/cta/gallery27.jpg",
                                alt: "Families and members gathered for an SLFA event",
                                title: "Gatherings",
                                desc: "Join family get-togethers, cultural celebrations, and association events."
                            },
                            {
                                num: "III",
                                image: "/images/cta/gallery4.jpg",
                                alt: "SLFA members meeting around a conference table",
                                title: "Network",
                                desc: "Build professional relationships across the community."
                            }
                        ].map((item, idx) => (
                            <motion.div
                                key={idx}
                                initial={reducedMotion ? false : { opacity: 0, y: 30 }}
                                whileInView={{ opacity: 1, y: 0 }}
                                viewport={{ once: true, margin: "-50px" }}
                                transition={{ duration: 0.6, delay: idx * 0.15, ease: "easeOut" }}
                                className="group text-left"
                            >
                                <div className="mb-5 aspect-[4/3] overflow-hidden">
                                    <img src={item.image} alt={item.alt} loading="lazy" className="h-full w-full object-cover transition-transform duration-700 motion-safe:group-hover:scale-105" />
                                </div>
                                <div className="mb-3 h-px w-12 bg-[#FFAC94]" aria-hidden="true" />
                                <h3 className="text-2xl font-sans font-bold text-white mb-2">{item.title}</h3>
                                <p className="text-gray-300 leading-relaxed font-sans text-base font-normal">
                                    {item.desc}
                                </p>
                            </motion.div>
                        ))}
                    </div>
                </div>
            </section>

            {/* Community updates */}
            <section className="relative overflow-hidden bg-[#091724] py-12 text-white md:py-20">
                <PhotoBackdrop images={happeningsImages} className="opacity-25" />
                <div className="absolute inset-0 bg-gradient-to-r from-[#091724] via-[#101e27]/90 to-[#091724]/70" aria-hidden="true" />
                <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <span className="font-sans text-xs font-bold uppercase tracking-[0.27em] text-[#5EEAD4]">From the community</span>
                    <div className="mt-4 flex flex-col justify-between gap-7 md:flex-row md:items-end">
                        <h2 className="font-sans text-3xl font-bold leading-tight sm:text-5xl">Community <em className="font-normal text-[#5EEAD4]">happenings</em></h2>
                        <Link href={route('happenings')} className="inline-flex items-center gap-2 self-start border-b border-[#5EEAD4] pb-2 text-sm font-bold text-[#5EEAD4] md:self-auto">All news & events <ArrowRight className="h-4 w-4" aria-hidden="true" /></Link>
                    </div>
                    <div className="mt-12 grid gap-12 lg:grid-cols-2 lg:gap-20">
                        <div>
                            <h3 className="border-b border-white/30 pb-4 font-sans text-xs font-bold uppercase tracking-[0.23em] text-[#5EEAD4]">Latest news</h3>
                            {latestNews?.length ? latestNews.slice(0, 3).map(post => <Link key={post.id} href={route('news.show', post.public_id)} className="group flex items-center gap-5 border-b border-white/15 py-5">
                                {post.cover_image_path && <div className="h-20 w-24 shrink-0 overflow-hidden sm:w-32"><img src={coverImageUrl(post.cover_image_path)} alt="" loading="lazy" className="h-full w-full object-cover transition-transform duration-500 motion-safe:group-hover:scale-105" /></div>}
                                <div><time dateTime={post.published_at} className="font-sans text-xs text-[#FFAC94]">{new Intl.DateTimeFormat('en-IN', { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'Asia/Kolkata' }).format(new Date(post.published_at))}</time>
                                    <h4 className="mt-2 font-sans text-xl font-bold leading-tight text-white group-hover:text-[#FFAC94]">{post.title}</h4>
                                    <span className="mt-3 inline-flex items-center gap-2 text-xs text-gray-300">Read story <ArrowRight size={14} /></span>
                                </div>
                            </Link>) : <p className="py-6 text-gray-300">News will appear here as it is published.</p>}
                        </div>
                        <div>
                            <h3 className="border-b border-white/30 pb-4 font-sans text-xs font-bold uppercase tracking-[0.23em] text-[#5EEAD4]">Upcoming events</h3>
                            {upcomingEvents?.length ? upcomingEvents.map(event => <Link key={event.id} href={route('events.show', event.public_id)} className="group block border-b border-white/15 py-7">
                                <div className="flex items-start gap-6">
                                    <div className="shrink-0 border-l-2 border-[#FFAC94] pl-4 text-[#FFAC94]" aria-hidden="true">
                                        <span className="block font-sans text-5xl leading-none">{new Intl.DateTimeFormat('en-IN', { day: '2-digit', timeZone: 'Asia/Kolkata' }).format(new Date(event.start_at))}</span>
                                        <span className="mt-1 block text-xs uppercase tracking-widest">{new Intl.DateTimeFormat('en-IN', { month: 'short', timeZone: 'Asia/Kolkata' }).format(new Date(event.start_at))}</span>
                                    </div>
                                    <div><h4 className="font-sans text-xl font-bold leading-snug text-white group-hover:text-[#FFAC94] sm:text-3xl">{event.title}</h4>
                                        <time dateTime={event.start_at} className="mt-3 block text-sm text-gray-300">{new Intl.DateTimeFormat('en-IN', { day: 'numeric', month: 'long', year: 'numeric', timeZone: 'Asia/Kolkata' }).format(new Date(event.start_at))}</time>
                                        {event.location && <p className="mt-3 flex items-start gap-2 text-sm leading-relaxed text-gray-300"><MapPin className="mt-0.5 h-4 w-4 shrink-0 text-[#FFAC94]" />{event.location}</p>}
                                        <span className="mt-5 inline-flex items-center gap-2 border-b border-[#FFAC94]/50 pb-1 text-sm font-bold text-[#FFAC94]">Discover the programme <ArrowRight size={16} /></span>
                                    </div>
                                </div>
                            </Link>) : <p className="py-6 text-gray-300">The next gathering is being planned.</p>}
                        </div>
                    </div>
                </div>
            </section>

            <section className="bg-[#EFF8F5] py-14 md:py-20 border-t border-[#5EEAD4]/20">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-20 items-start">
                    <div>
                        <span className="uppercase tracking-[0.3em] text-xs font-bold text-[#086F68] font-sans">Get involved</span>
                        <h2 className="mt-4 text-3xl sm:text-5xl font-sans font-bold text-[#091724] leading-tight">Find your place in the community</h2>
                        <p className="mt-5 text-lg text-gray-700 font-sans leading-relaxed">Come to a gathering, learn about our work, or apply to become a member. There is a clear next step wherever you start.</p>
                        <div className="mt-8 flex flex-wrap gap-3">
                            <Link href={route('happenings')} className="inline-flex items-center gap-2 border-b border-[#086F68] pb-2 text-sm font-bold text-[#086F68]">News & events <ArrowRight className="h-4 w-4" /></Link>
                            <Link href={`${route('happenings')}#news`} className="inline-flex items-center gap-2 border-b border-gray-500 pb-2 text-sm font-bold text-gray-900">See our activities <ArrowRight className="h-4 w-4" /></Link>
                        </div>
                    </div>
                    <div className="border-t border-[#b7a676] pt-6">
                        <h3 className="text-2xl font-sans font-bold text-[#091724]">Membership at a glance</h3>
                        <ol className="mt-6 space-y-6">
                            {[
                                ['Check eligibility', 'Adults in South India with a connection to Sri Lanka can apply.'],
                                ['Apply online', 'Create an account and submit your membership details for review.'],
                                ['Stay connected', 'Sign in to see your application status and update your profile.'],
                            ].map(([title, description], index) => (
                                <li key={title} className="flex gap-4 border-b border-[#d8d0c3] pb-5">
                                    <span className="w-8 shrink-0 text-sm font-bold text-[#086F68]">0{index + 1}</span>
                                    <div><h4 className="font-bold text-[#091724]">{title}</h4><p className="mt-1 text-sm text-gray-700 leading-relaxed">{description}</p></div>
                                </li>
                            ))}
                        </ol>
                        <Link href={route('membership')} className="mt-7 inline-flex items-center gap-2 text-sm font-bold text-[#086F68] hover:underline">Read membership details <ArrowRight className="h-4 w-4" /></Link>
                    </div>
                </div>
            </section>

            {/* Downloads Call to Action */}
            <section className="bg-[#091724] py-14 md:py-20 text-white overflow-hidden relative flex flex-col items-center justify-center">
                {/* Background Carousel */}
                <div className="absolute inset-0 z-0 bg-[#091724]">
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
                    <div className="absolute inset-0 bg-[#091724]/70 mix-blend-multiply"></div>
                    <div className="absolute inset-0 bg-gradient-to-t from-[#091724] via-[#091724]/40 to-transparent"></div>
                    <div className="absolute inset-0 bg-gradient-to-b from-[#091724] via-[#091724]/40 to-transparent"></div>
                </div>

                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10 w-full">
                    <motion.div
                        initial={{ opacity: 0, y: 30 }}
                        whileInView={{ opacity: 1, y: 0 }}
                        viewport={{ once: true }}
                        transition={{ duration: 1 }}
                        className="flex flex-col items-center"
                    >
                        <h2 className="text-5xl md:text-6xl font-sans font-extrabold mb-8 leading-tight drop-shadow-lg">
                            Be a part of <br /><span className="not-italic font-bold text-[#5EEAD4]">SLFA</span>
                        </h2>
                        <p className="text-xl md:text-2xl text-gray-300 font-sans font-normal mb-16 leading-relaxed max-w-2xl mx-auto drop-shadow-md">
                            Whether you are from Sri Lanka or trace your ancestral roots to the island, we invite you to join our community. Connect, celebrate, and preserve our rich heritage together in South India.
                        </p>

                        <div className="flex flex-col items-center gap-8 w-full">
                            {/* Primary Online Action */}
                            <Link href={route('register')} className="group bg-[#5EEAD4] text-[#091724] hover:bg-white px-12 py-5 rounded-full font-sans text-sm uppercase tracking-widest font-bold transition-all duration-300 flex items-center justify-center hover:shadow-[0_0_30px_rgba(255,151,125,0.25)] hover:-translate-y-1">
                                Apply Online Now
                            </Link>

                            <Link href={route('membership')} className="text-sm font-bold text-[#5EEAD4] hover:text-white transition-colors">Learn how membership works</Link>
                        </div>
                    </motion.div>
                </div>
            </section>
        </SLFAWebsiteLayout>
    );
}
