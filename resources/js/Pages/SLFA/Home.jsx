import React, { useState, useEffect, useRef } from 'react';
import { Link, Head } from '@inertiajs/react';
import SLFAWebsiteLayout from '@/Layouts/SLFAWebsiteLayout';
import { MapPin, ArrowRight } from 'lucide-react';
import { motion, AnimatePresence, useReducedMotion } from 'motion/react';
import CanvasFlag from '@/Components/CanvasFlag';
import PhotoBackdrop from '@/Components/PhotoBackdrop';

const happeningsImages = ['/images/cta/gallery27.jpg', '/images/cta/gallery15.jpg', '/images/cta/gallery4.jpg'];

export default function Home({ auth, latestNews, upcomingEvents }) {
    const featuredEvent = upcomingEvents?.[0];
    const reducedMotion = useReducedMotion();
    const oceanRef = useRef(null);
    const jqueryRef = useRef(null);
    const rippleReadyRef = useRef(false);
    const lastRippleRef = useRef(0);

    useEffect(() => {
        if (reducedMotion || !oceanRef.current) return undefined;
        let active = true;
        let $;

        async function startRipples() {
            try {
                const jquery = await import('jquery');
                await import('jquery.ripples');
                if (!active || !oceanRef.current) return;
                $ = jquery.default;
                $(oceanRef.current).ripples({ resolution: 256, dropRadius: 16, perturbance: 0.025, interactive: false });
                jqueryRef.current = $;
                rippleReadyRef.current = true;
            } catch (error) {
                // WebGL is optional; keep the ocean image visible on unsupported devices.
                rippleReadyRef.current = false;
            }
        }

        startRipples();
        return () => {
            active = false;
            if (rippleReadyRef.current && oceanRef.current && $) {
                $(oceanRef.current).ripples('destroy');
            }
            rippleReadyRef.current = false;
            jqueryRef.current = null;
        };
    }, [reducedMotion]);

    const moveOcean = (event) => {
        if (reducedMotion || !rippleReadyRef.current || event.pointerType !== 'mouse' || !oceanRef.current) return;
        const now = performance.now();
        if (now - lastRippleRef.current < 90) return;
        lastRippleRef.current = now;
        const bounds = oceanRef.current.getBoundingClientRect();
        jqueryRef.current?.(oceanRef.current).ripples('drop', event.clientX - bounds.left, event.clientY - bounds.top, 14, 0.045);
    };
    const fadeUp = {
        hidden: { opacity: 0, y: 30 },
        visible: { opacity: 1, y: 0, transition: { duration: 0.8, ease: [0.22, 1, 0.36, 1] } }
    };

    const carouselImages = [
        "/images/sigiriya-rock.jpg",
        "/images/carousel/kandyan-light.webp",
        "/images/carousel/kalaripayuttu-light.webp",
        "/images/carousel/sri-lanka-culture-light.webp",
        "/images/carousel/bharatanatyam-light.webp",
        "/images/carousel/kathakali-light.webp"
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
        <SLFAWebsiteLayout user={auth.user} darkNav lightOnScroll>
            <Head title="Sri Lankan Friendship Association of South India" />

            {/* Hero */}
            <section onPointerMove={moveOcean} className="relative min-h-[100svh] overflow-hidden flex items-center bg-[#0d5079] pt-28 pb-24 sm:pt-32 sm:pb-28">
                <div ref={oceanRef} className="absolute inset-0 bg-cover bg-center" style={{ backgroundImage: 'url(/images/hero-ocean-bg-slfa.jpg)' }} aria-hidden="true" />
                <div className="absolute inset-0 bg-gradient-to-b from-[#073a60]/45 via-[#0b4f79]/50 to-[#dcecf2]/55" aria-hidden="true" />
                <div className="absolute inset-0 bg-gradient-to-r from-[#063650]/30 via-transparent to-[#073b5a]/20" aria-hidden="true" />
                <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_center,transparent_42%,rgba(255,255,255,0.12)_75%,rgba(255,255,255,0.32)_100%)]" aria-hidden="true" />

                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 w-full">
                    <motion.div
                        initial="hidden" animate="visible" variants={staggerContainer}
                        className="flex flex-col items-center justify-center text-center h-full"
                    >
                        <motion.div variants={fadeUp} className="mb-3 sm:mb-5 flex flex-col items-center justify-center gap-3">
                            <span className="uppercase tracking-[0.14em] sm:tracking-[0.24em] text-[10px] sm:text-xs font-bold text-[#D4AF37] font-lato">Sri Lankan Friendship Association of South India</span>
                            <div className="w-16 h-[1px] bg-gradient-to-r from-transparent via-[#D4AF37]/60 to-transparent"></div>
                        </motion.div>

                        {/* Animated wordmark supports the heading below */}
                        <motion.div
                            initial={{ scale: 0.9, opacity: 0 }}
                            animate={{ scale: 1, opacity: 1 }}
                            transition={{ duration: 1.5, ease: [0.22, 1, 0.36, 1] }}
                            className="relative w-full overflow-visible flex justify-center items-center drop-shadow-[0_15px_35px_rgba(0,0,0,0.8)] z-10"
                            aria-hidden="true"
                        >
                            <div className="w-full max-w-[840px]">
                                <CanvasFlag src="/images/slfa-bg.png" animate={!reducedMotion} />
                            </div>
                        </motion.div>

                        <motion.div variants={fadeUp} className="max-w-2xl mx-auto mt-3 sm:mt-5 relative z-20">
                            <h1 className="text-4xl sm:text-5xl md:text-6xl font-abhaya font-bold text-white mb-4 sm:mb-5 leading-[1.05] drop-shadow-lg">
                                Connecting Sri Lankans<br />in South India
                            </h1>
                            <p className="text-base sm:text-lg text-gray-300 font-lato font-light leading-relaxed mb-7 sm:mb-8">
                                A community for cultural connections, events, and professional relationships.
                            </p>

                            <div className="flex flex-col sm:flex-row gap-3 sm:gap-5 justify-center items-center">
                                {!auth.user && (
                                    <Link href={route('register')} className="group flex items-center justify-center bg-[#D4AF37] text-gray-900 px-7 py-3.5 rounded-full font-lato text-xs sm:text-sm uppercase tracking-wider hover:bg-white transition-all duration-500 hover:shadow-[0_0_40px_rgba(212,175,55,0.4)] font-bold border border-transparent">
                                        Apply for membership
                                        <ArrowRight className="ml-3 w-4 h-4 transform group-hover:translate-x-1 transition-transform" />
                                    </Link>
                                )}
                                <Link href={route('about')} className="group flex items-center justify-center px-7 py-3.5 rounded-full font-lato text-xs sm:text-sm uppercase tracking-wider text-white border border-white/30 hover:bg-white/10 transition-all duration-300">
                                    Explore the association
                                </Link>
                            </div>
                        </motion.div>

                        <motion.div variants={fadeUp} className="relative z-20 mt-9 w-full max-w-3xl border-t border-white/70 bg-white/90 px-5 py-5 text-left shadow-[0_18px_50px_-28px_rgba(0,40,65,0.5)] backdrop-blur-md sm:mt-11 sm:px-7">
                            <Link href={featuredEvent ? route('events.show', featuredEvent.public_id) : route('happenings')} className="group grid gap-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#D4AF37]">
                                <div className="min-w-0">
                                    <span className="font-lato text-[11px] font-bold uppercase tracking-[0.22em] text-[#0b5a82]">Upcoming event</span>
                                    <h2 className="mt-2 font-abhaya text-2xl font-bold leading-tight text-[#102f46] transition-colors group-hover:text-[#0b648e] sm:text-3xl">{featuredEvent ? featuredEvent.title : 'Find your next SLFA gathering'}</h2>
                                    {featuredEvent ? <span className="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 font-lato text-sm text-[#34576b]">
                                        <span>{new Date(featuredEvent.start_at).toLocaleDateString('en-IN', { day: 'numeric', month: 'long', year: 'numeric', timeZone: 'Asia/Kolkata' })}</span>
                                        {featuredEvent.location && <span className="inline-flex items-center gap-1.5"><MapPin className="h-3.5 w-3.5 shrink-0 text-[#0b6b96]" aria-hidden="true" />{featuredEvent.location}</span>}
                                    </span> : <p className="mt-2 font-lato text-sm text-[#34576b]">See what is coming up in the community.</p>}
                                </div>
                                <span className="inline-flex items-center gap-2 border-b border-[#0b6b96]/70 pb-1 font-lato text-xs font-bold uppercase tracking-wider text-[#0b5a82] sm:mb-1">{featuredEvent ? 'Event details' : 'Explore events'} <ArrowRight className="h-4 w-4 transition-transform group-hover:translate-x-1" aria-hidden="true" /></span>
                            </Link>
                        </motion.div>
                    </motion.div>
                </div>

                {/* Seamless blend into the next section */}
                <div className="absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-[#f7f8f5] to-transparent pointer-events-none z-20"></div>
            </section>



            {/* About / Story Section */}
            <section className="relative py-20 md:py-28 overflow-hidden bg-[#f7f8f5]">
                {/* Background Carousel */}
                <div className="absolute inset-0 z-0 bg-[#f7f8f5]">
                    <AnimatePresence>
                        <motion.img
                            key={currentImageIdx}
                            src={carouselImages[currentImageIdx]}
                            initial={{ opacity: 0, scale: 1.05 }}
                            animate={{ opacity: 1, scale: 1 }}
                            exit={{ opacity: 0 }}
                            transition={{ duration: 1.5, ease: "easeInOut" }}
                            className="absolute inset-y-0 right-0 w-full md:w-[60%] h-full object-cover opacity-70"
                            alt=""
                        />
                    </AnimatePresence>
                    <div className="absolute inset-0 bg-gradient-to-r from-[#f7f8f5] via-[#f7f8f5]/95 to-[#f7f8f5]/30"></div>
                    <div className="absolute inset-0 bg-gradient-to-t from-[#f7f8f5]/85 via-transparent to-[#f7f8f5]/30"></div>
                </div>

                <div className="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
                    <motion.div
                        initial={{ opacity: 0, x: -30 }}
                        whileInView={{ opacity: 1, x: 0 }}
                        viewport={{ once: true }}
                        transition={{ duration: 1 }}
                        className="max-w-xl"
                    >
                        <span className="uppercase tracking-[0.24em] text-xs font-bold text-[#0c6c97] font-lato block mb-4">Who we are · Est. 2011</span>
                        <h2 className="text-4xl sm:text-5xl font-abhaya font-extrabold text-[#102e43] mb-5 leading-tight">
                            A community built on <span className="italic font-light text-[#0c6c97]">connection</span>
                        </h2>
                        <div className="space-y-4 text-lg md:text-xl text-[#355166] font-abhaya font-normal leading-relaxed mb-7">
                            <p>
                                SLFA brings together people of Sri Lankan origin living in South India through friendship, cultural gatherings, and mutual support.
                            </p>
                            <p>
                                Our activities include community events, medical camps, and opportunities to build professional relationships.
                            </p>
                        </div>
                        <Link href={route('about')} className="inline-flex items-center text-[#0c638c] font-bold uppercase tracking-wider text-xs sm:text-sm hover:text-[#073b5a] transition-colors group">
                            Meet the association
                            <ArrowRight className="ml-3 w-4 h-4 transform group-hover:translate-x-2 transition-transform" />
                        </Link>
                    </motion.div>
                </div>
            </section>

            {/* Three ways members connect */}
            <section className="py-12 md:py-16 bg-white relative z-20 border-t border-[#d8e5eb]">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8 md:gap-12">
                        {[
                            {
                                num: "I",
                                title: "Community",
                                desc: "Meet people who share a connection with Sri Lanka and South India."
                            },
                            {
                                num: "II",
                                title: "Gatherings",
                                desc: "Join family get-togethers, cultural celebrations, and association events."
                            },
                            {
                                num: "III",
                                title: "Network",
                                desc: "Build professional relationships across the community."
                            }
                        ].map((item, idx) => (
                            <motion.div
                                key={idx}
                                initial={{ opacity: 0, y: 30 }}
                                whileInView={{ opacity: 1, y: 0 }}
                                viewport={{ once: true, margin: "-50px" }}
                                transition={{ duration: 0.6, delay: idx * 0.15, ease: "easeOut" }}
                                className="text-left border-t border-[#92b3c4] pt-5"
                            >
                                <div className="text-[#0c6c97] font-abhaya italic font-light text-3xl mb-2 leading-none">
                                    {item.num}.
                                </div>
                                <h3 className="text-2xl font-abhaya font-bold text-[#102e43] mb-2">{item.title}</h3>
                                <p className="text-[#506879] leading-relaxed font-abhaya text-lg font-light">
                                    {item.desc}
                                </p>
                            </motion.div>
                        ))}
                    </div>
                </div>
            </section>

            {/* Community updates */}
            <section className="relative overflow-hidden bg-[#eaf3f6] py-20 text-[#102e43] md:py-28">
                <PhotoBackdrop images={happeningsImages} className="opacity-[0.12]" />
                <div className="absolute inset-0 bg-gradient-to-r from-[#eaf3f6]/95 via-[#eaf3f6]/90 to-[#eaf3f6]/70" aria-hidden="true" />
                <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <span className="font-lato text-xs font-bold uppercase tracking-[0.27em] text-[#0c6c97]">From the community</span>
                    <div className="mt-4 flex flex-col justify-between gap-7 md:flex-row md:items-end">
                        <h2 className="font-abhaya text-4xl font-bold leading-tight sm:text-6xl">Community <em className="font-light text-[#0c6c97]">happenings</em></h2>
                        <Link href={route('happenings')} className="inline-flex items-center gap-2 self-start border-b border-[#0c6c97] pb-2 text-sm font-bold text-[#0c638c] md:self-auto">All news & events <ArrowRight className="h-4 w-4" aria-hidden="true" /></Link>
                    </div>
                    <div className="mt-12 grid gap-12 lg:grid-cols-2 lg:gap-20">
                        <div>
                            <h3 className="border-b border-[#a8c5d2] pb-4 font-lato text-xs font-bold uppercase tracking-[0.23em] text-[#0c6c97]">Latest news</h3>
                            {latestNews?.length ? latestNews.map(post => <Link key={post.id} href={route('news.show', post.public_id)} className="group flex gap-5 border-b border-[#c6dbe3] py-5">
                                <time className="w-20 shrink-0 font-lato text-xs text-[#0c638c]">{new Intl.DateTimeFormat('en-IN', { day: 'numeric', month: 'short', timeZone: 'Asia/Kolkata' }).format(new Date(post.published_at))}</time>
                                <span className="font-abhaya text-xl font-bold leading-tight text-[#102e43] group-hover:text-[#0c638c]">{post.title}</span>
                            </Link>) : <p className="py-6 text-[#506879]">News will appear here as it is published.</p>}
                        </div>
                        <div>
                            <h3 className="border-b border-[#a8c5d2] pb-4 font-lato text-xs font-bold uppercase tracking-[0.23em] text-[#0c6c97]">Upcoming events</h3>
                            {upcomingEvents?.length ? upcomingEvents.map(event => <Link key={event.id} href={route('events.show', event.public_id)} className="group flex gap-5 border-b border-[#c6dbe3] py-5">
                                <time className="w-20 shrink-0 font-lato text-xs text-[#0c638c]">{new Intl.DateTimeFormat('en-IN', { day: 'numeric', month: 'short', timeZone: 'Asia/Kolkata' }).format(new Date(event.start_at))}</time>
                                <span className="font-abhaya text-xl font-bold leading-tight text-[#102e43] group-hover:text-[#0c638c]">{event.title}</span>
                            </Link>) : <p className="py-6 text-[#506879]">The next gathering is being planned.</p>}
                        </div>
                    </div>
                </div>
            </section>

            <section className="bg-[#f5f1e9] py-20 md:py-28 border-t border-[#D4AF37]/20">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-20 items-start">
                    <div>
                        <span className="uppercase tracking-[0.3em] text-xs font-bold text-[#9A7714] font-lato">Get involved</span>
                        <h2 className="mt-4 text-4xl sm:text-5xl font-abhaya font-bold text-gray-950 leading-tight">Find your place in the community</h2>
                        <p className="mt-5 text-lg text-gray-700 font-lato leading-relaxed">Come to a gathering, learn about our work, or apply to become a member. There is a clear next step wherever you start.</p>
                        <div className="mt-8 flex flex-wrap gap-3">
                            <Link href={route('happenings')} className="inline-flex items-center gap-2 border-b border-[#800000] pb-2 text-sm font-bold text-[#800000]">News & events <ArrowRight className="h-4 w-4" /></Link>
                            <Link href={`${route('happenings')}#news`} className="inline-flex items-center gap-2 border-b border-gray-500 pb-2 text-sm font-bold text-gray-900">See our activities <ArrowRight className="h-4 w-4" /></Link>
                        </div>
                    </div>
                    <div className="border-t border-[#b7a676] pt-6">
                        <h3 className="text-2xl font-abhaya font-bold text-gray-950">Membership at a glance</h3>
                        <ol className="mt-6 space-y-6">
                            {[
                                ['Check eligibility', 'Adults in South India with a connection to Sri Lanka can apply.'],
                                ['Apply online', 'Create an account and submit your membership details for review.'],
                                ['Stay connected', 'Sign in to see your application status and update your profile.'],
                            ].map(([title, description], index) => (
                                <li key={title} className="flex gap-4 border-b border-[#d8d0c3] pb-5">
                                    <span className="w-8 shrink-0 text-sm font-bold text-[#9a7714]">0{index + 1}</span>
                                    <div><h4 className="font-bold text-gray-950">{title}</h4><p className="mt-1 text-sm text-gray-700 leading-relaxed">{description}</p></div>
                                </li>
                            ))}
                        </ol>
                        <Link href={route('membership')} className="mt-7 inline-flex items-center gap-2 text-sm font-bold text-[#800000] hover:underline">Read membership details <ArrowRight className="h-4 w-4" /></Link>
                    </div>
                </div>
            </section>

            {/* Downloads Call to Action */}
            <section className="bg-[#f7f8f5] py-20 md:py-28 text-[#102e43] overflow-hidden relative flex flex-col items-center justify-center">
                {/* Background Carousel */}
                <div className="absolute inset-0 z-0 bg-[#f7f8f5]">
                    <AnimatePresence>
                        <motion.img
                            key={ctaImageIdx}
                            src={ctaImages[ctaImageIdx]}
                            initial={{ opacity: 0, scale: 1.05 }}
                            animate={{ opacity: 1, scale: 1 }}
                            exit={{ opacity: 0 }}
                            transition={{ duration: 1.5, ease: "easeInOut" }}
                            className="absolute inset-0 w-full h-full object-cover opacity-20"
                            alt=""
                        />
                    </AnimatePresence>
                    <div className="absolute inset-0 bg-[#f7f8f5]/70"></div>
                    <div className="absolute inset-0 bg-gradient-to-b from-[#f7f8f5] via-transparent to-[#f7f8f5]"></div>
                </div>

                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10 w-full">
                    <motion.div
                        initial={{ opacity: 0, y: 30 }}
                        whileInView={{ opacity: 1, y: 0 }}
                        viewport={{ once: true }}
                        transition={{ duration: 1 }}
                        className="flex flex-col items-center"
                    >
                        <h2 className="text-5xl md:text-6xl font-abhaya font-extrabold mb-8 leading-tight">
                            Be a part of <br /><span className="italic font-light text-[#0c6c97]">SLFA</span>
                        </h2>
                        <p className="text-xl md:text-2xl text-[#466173] font-abhaya font-light mb-12 leading-relaxed max-w-2xl mx-auto">
                            Whether you are from Sri Lanka or trace your ancestral roots to the island, we invite you to join our community. Connect, celebrate, and preserve our rich heritage together in South India.
                        </p>
                        
                        <div className="flex flex-col items-center gap-8 w-full">
                            {/* Primary Online Action */}
                            <Link href={route('register')} className="group bg-[#0d5a83] text-white hover:bg-[#083b5b] px-12 py-5 rounded-full font-lato text-sm uppercase tracking-widest font-bold transition-all duration-300 flex items-center justify-center hover:-translate-y-1">
                                Apply Online Now
                            </Link>

                            <Link href={route('membership')} className="text-sm font-bold text-[#0c638c] hover:text-[#083b5b] transition-colors">Learn how membership works</Link>
                        </div>
                    </motion.div>
                </div>
            </section>
        </SLFAWebsiteLayout>
    );
}
