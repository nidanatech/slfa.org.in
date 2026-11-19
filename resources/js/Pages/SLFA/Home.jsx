import React, { useState, useEffect, useRef } from 'react';
import { Link, Head } from '@inertiajs/react';
import SLFAWebsiteLayout from '@/Layouts/SLFAWebsiteLayout';
import { MapPin, ArrowRight } from 'lucide-react';
import { motion, AnimatePresence, useReducedMotion } from 'motion/react';
import CanvasFlag from '@/Components/CanvasFlag';
import { coverImageUrl } from '@/lib/coverImageUrl';

const formatDate = value => new Intl.DateTimeFormat('en-IN', { day: 'numeric', month: 'long', year: 'numeric', timeZone: 'Asia/Kolkata' }).format(new Date(value));

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

    useEffect(() => {
        if (reducedMotion) return undefined;
        const timer = setInterval(() => {
            setCurrentImageIdx((prev) => (prev + 1) % carouselImages.length);
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
        <SLFAWebsiteLayout user={auth.user} darkNav lightOnScroll coastal>
            <Head title="Sri Lankan Friendship Association of South India" />

            {/* Hero */}
            <section onPointerMove={moveOcean} className="relative min-h-[85svh] overflow-hidden flex items-center bg-[#0d5079] pt-28 pb-20 sm:pt-32 sm:pb-24">
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
                            <div className="w-full max-w-[720px]">
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

                        <motion.div variants={fadeUp} className="relative z-20 mt-9 w-full max-w-3xl border-t border-white/70 bg-white/85 px-5 py-4 text-left shadow-[0_18px_50px_-28px_rgba(0,40,65,0.5)] backdrop-blur-md sm:mt-11 sm:px-7">
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
                <div className="absolute inset-x-0 bottom-0 h-32 bg-gradient-to-t from-[#f7f8f5] to-transparent pointer-events-none z-20"></div>
            </section>



            <div className="bg-[#f7f8f5] text-[#102e43]">
                <section className="relative overflow-hidden pt-12 pb-8 md:pt-16 md:pb-12" aria-labelledby="community-heading">
                    <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <div className="grid items-center gap-10 lg:grid-cols-2 lg:gap-16">
                            <div className="relative z-10">
                                <span className="font-lato text-xs font-bold uppercase tracking-[0.24em] text-[#0c6c97]">Who we are · Est. 2011</span>
                                <h2 id="community-heading" className="mt-4 font-abhaya text-4xl font-bold leading-tight sm:text-5xl">A community built on <em className="font-light text-[#0c6c97]">connection</em></h2>
                                <div className="mt-5 space-y-4 font-lato text-base leading-relaxed text-[#466173] sm:text-lg">
                                    <p>SLFA brings together people of Sri Lankan origin living in South India through friendship, cultural gatherings, and mutual support.</p>
                                    <p>From celebrating our heritage to helping one another, there is a place here to belong, contribute, and grow.</p>
                                </div>
                                <Link href={route('about')} className="mt-7 inline-flex items-center gap-2 border-b border-[#0c638c]/40 pb-2 text-sm font-bold text-[#0c638c] hover:text-[#102e43]">Meet the association <ArrowRight size={16} /></Link>
                            </div>
                            <div className="relative aspect-[4/3] overflow-hidden lg:aspect-[5/4]" aria-hidden="true">
                                <AnimatePresence>
                                    <motion.img key={currentImageIdx} src={carouselImages[currentImageIdx]} alt="" loading="lazy"
                                        initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} transition={{ duration: reducedMotion ? 0 : 1.5 }}
                                        className="absolute inset-0 h-full w-full object-cover" />
                                </AnimatePresence>
                                <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_center,transparent_40%,#f7f8f5_100%)]" />
                                <div className="absolute inset-0 bg-gradient-to-t from-[#f7f8f5] via-transparent to-transparent" />
                            </div>
                        </div>
                        <div className="mt-10 grid gap-8 border-t border-[#bfd1d9] pt-8 md:grid-cols-3 md:gap-12">
                            {[
                                ['Community', 'Meet people who share a connection with Sri Lanka and South India.'],
                                ['Gatherings', 'Join family get-togethers, cultural celebrations, and association events.'],
                                ['Network', 'Build friendships and professional relationships across the community.'],
                            ].map(([title, description], index) => <div key={title}>
                                <span className="font-lato text-xs tracking-widest text-[#0c6c97]">0{index + 1}</span>
                                <h3 className="mt-2 font-abhaya text-2xl font-bold">{title}</h3>
                                <p className="mt-2 font-lato text-sm leading-relaxed text-[#466173]">{description}</p>
                            </div>)}
                        </div>
                    </div>
                </section>

                <section className="relative py-12 md:py-16" aria-labelledby="happenings-heading">
                    <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_right,rgba(201,227,237,0.45),transparent_70%)]" aria-hidden="true" />
                    <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <div className="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
                            <div><span className="font-lato text-xs font-bold uppercase tracking-[0.24em] text-[#0c6c97]">Life at SLFA</span>
                                <h2 id="happenings-heading" className="mt-4 font-abhaya text-4xl font-bold leading-tight sm:text-5xl">Together, <em className="font-light text-[#0c6c97]">more happens</em></h2>
                            </div>
                            <Link href={route('happenings')} className="inline-flex items-center gap-2 self-start border-b border-[#0c638c]/40 pb-2 text-sm font-bold text-[#0c638c] sm:self-auto">All news & events <ArrowRight size={16} /></Link>
                        </div>
                        <div className="mt-10 grid gap-10 lg:grid-cols-2 lg:gap-16">
                            <article className="border-t border-[#9ab9c9] pt-6">
                                <span className="font-lato text-xs font-bold uppercase tracking-[0.2em] text-[#0c6c97]">{featuredEvent ? 'Our next gathering' : 'Come together'}</span>
                                {featuredEvent?.cover_image_path && <img src={coverImageUrl(featuredEvent.cover_image_path)} alt="" loading="lazy" className="mt-5 max-h-72 w-full object-contain object-left" />}
                                <h3 className="mt-5 font-abhaya text-3xl font-bold leading-tight sm:text-4xl">{featuredEvent?.title || 'Your next connection starts here'}</h3>
                                {featuredEvent ? <>
                                    <time dateTime={featuredEvent.start_at} className="mt-6 block font-lato text-lg text-[#0c638c]">{formatDate(featuredEvent.start_at)}</time>
                                    {featuredEvent.location && <p className="mt-3 flex items-start gap-2 font-lato text-base leading-relaxed text-[#466173]"><MapPin className="mt-1 h-4 w-4 shrink-0" />{featuredEvent.location}</p>}
                                </> : <p className="mt-5 font-lato leading-relaxed text-[#466173]">Explore our community gatherings, cultural celebrations, and activities. New dates will appear here when announced.</p>}
                                <Link href={featuredEvent ? route('events.show', featuredEvent.public_id) : route('happenings')} className="mt-8 inline-flex items-center gap-3 rounded-full bg-[#0d5a83] px-6 py-3 font-lato text-sm font-bold text-white transition-colors hover:bg-[#083b5b]">{featuredEvent ? 'Explore the event' : 'Explore our activities'} <ArrowRight size={16} /></Link>
                            </article>
                            <div className="border-t border-[#9ab9c9] pt-6">
                                <h3 className="font-lato text-xs font-bold uppercase tracking-[0.2em] text-[#0c6c97]">Stories from the community</h3>
                                {latestNews?.length ? latestNews.slice(0, 2).map(post => <Link key={post.id} href={route('news.show', post.public_id)} className="group mt-6 grid grid-cols-[96px_minmax(0,1fr)] items-center gap-5 border-b border-[#bfd1d9] pb-6 sm:grid-cols-[144px_minmax(0,1fr)]">
                                    {post.cover_image_path ? <img src={coverImageUrl(post.cover_image_path)} alt="" loading="lazy" className="aspect-[4/3] w-full object-cover" /> : <div className="flex aspect-[4/3] items-center justify-center border-y border-[#bfd1d9] font-abhaya text-2xl text-[#0c6c97]" aria-hidden="true">SLFA</div>}
                                    <div><time dateTime={post.published_at} className="font-lato text-xs text-[#466173]">{formatDate(post.published_at)}</time><h4 className="mt-2 font-abhaya text-xl font-bold leading-tight group-hover:text-[#0c638c] sm:text-2xl">{post.title}</h4><span className="mt-3 inline-flex items-center gap-2 text-xs font-bold text-[#0c638c]">Read story <ArrowRight size={14} /></span></div>
                                </Link>) : <p className="mt-6 font-lato text-[#466173]">News will appear here as it is published.</p>}
                            </div>
                        </div>
                    </div>
                </section>

                <section className="pt-4 pb-16 md:pt-8 md:pb-20" aria-labelledby="membership-heading">
                    <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <div className="grid gap-10 border-t border-[#bfd1d9] pt-12 lg:grid-cols-2 lg:gap-16">
                            <div>
                                <span className="font-lato text-xs font-bold uppercase tracking-[0.24em] text-[#0c6c97]">A place to belong</span>
                                <h2 id="membership-heading" className="mt-4 font-abhaya text-4xl font-bold leading-tight sm:text-5xl">Be a part of <em className="font-light text-[#0c6c97]">SLFA</em></h2>
                                <p className="mt-5 font-lato text-base leading-relaxed text-[#466173] sm:text-lg">Connect with fellow members, celebrate our heritage, and take part in community life across South India.</p>
                                <img src="/images/cta/gallery27.jpg" alt="Members of the SLFA community gathered together" loading="lazy" className="mt-8 aspect-[16/9] w-full object-cover" />
                            </div>
                            <div className="lg:pt-9">
                                <h3 className="font-abhaya text-2xl font-bold">Your membership journey</h3>
                                <ol className="mt-6">
                                    {[
                                        ['Check eligibility', 'Adults in South India with a connection to Sri Lanka can apply.'],
                                        ['Apply online', 'Create an account and submit your membership details for review.'],
                                        ['Stay connected', 'Follow your application status and manage your member profile online.'],
                                    ].map(([title, description], index) => <li key={title} className="flex gap-5 border-b border-[#bfd1d9] py-5 first:pt-0">
                                        <span className="font-lato text-sm text-[#0c6c97]">0{index + 1}</span>
                                        <div><h4 className="font-lato font-bold">{title}</h4><p className="mt-2 font-lato text-sm leading-relaxed text-[#466173]">{description}</p></div>
                                    </li>)}
                                </ol>
                                <div className="mt-8 flex flex-wrap items-center gap-5">
                                    <Link href={auth.user ? route('dashboard') : route('register')} className="inline-flex items-center gap-3 rounded-full bg-[#0d5a83] px-7 py-3.5 font-lato text-sm font-bold text-white transition-colors hover:bg-[#083b5b]">{auth.user ? 'Your membership' : 'Apply for membership'} <ArrowRight size={16} /></Link>
                                    <Link href={route('membership')} className="border-b border-[#0c638c]/40 pb-1 text-sm font-bold text-[#0c638c]">Membership details</Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </SLFAWebsiteLayout>
    );
}
