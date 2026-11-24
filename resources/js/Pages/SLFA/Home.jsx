import React, { useEffect, useState } from 'react';
import { Link, Head } from '@inertiajs/react';
import { ArrowRight, CalendarDays, MapPin, ChevronLeft, ChevronRight, Pause, Play } from 'lucide-react';
import { AnimatePresence, motion, useReducedMotion } from 'motion/react';
import SLFAWebsiteLayout from '@/Layouts/SLFAWebsiteLayout';
import CommunityWord from '@/Components/CommunityWord';
import StoriesCarousel from '@/Components/StoriesCarousel';
import MistCanvas from '@/Components/MistCanvas';
import { InterestExplorer, PhotoJournal, EventCountdown } from '@/Components/CommunityPlay';
import { coverImageUrl } from '@/lib/coverImageUrl';

const cultureImages = [
    '/images/carousel/kandyan-light.webp',
    '/images/carousel/kalaripayuttu-light.webp',
    '/images/carousel/sri-lanka-culture-light.webp',
    '/images/carousel/bharatanatyam-light.webp',
    '/images/carousel/kathakali-light.webp',
];
const dateLabel = value => new Intl.DateTimeFormat('en-IN', { day: 'numeric', month: 'long', year: 'numeric', timeZone: 'Asia/Kolkata' }).format(new Date(value));
const connections = [
    { title: 'Shared roots', kicker: 'Stories that brought us here', description: 'From Sri Lanka to the vibrant neighbourhoods of South India, our community carries forward shared memories, enduring friendships, and cultural traditions that connect generations across borders.', image: '/images/themes/shared-roots.png', routeName: 'about', cta: 'Our story' },
    { title: 'Shared celebrations', kicker: 'Culture, lived together', description: 'Come together for cultural celebrations, family gatherings, and community events.', image: '/images/night-to-remember.jpg', routeName: 'happenings', cta: 'See gatherings' },
    { title: 'Shared possibilities', kicker: 'Ideas become friendships', description: 'Build friendships, exchange ideas, and grow your professional connections.', image: '/images/meet-people.jpg', routeName: 'membership', cta: 'Find your place' },
];

export default function Home({ auth, latestNews = [], upcomingEvents = [] }) {
    const event = upcomingEvents?.[0];
    const reducedMotion = useReducedMotion();
    const [imageIndex, setImageIndex] = useState(0);
    const [paused, setPaused] = useState(false);
    const [heroVideoFailed, setHeroVideoFailed] = useState(false);
    useEffect(() => {
        if (reducedMotion || paused) return undefined;
        const timer = window.setInterval(() => setImageIndex(index => (index + 1) % cultureImages.length), 6500);
        return () => window.clearInterval(timer);
    }, [reducedMotion, paused]);

    return <SLFAWebsiteLayout user={auth.user} cream transparentNav>
        <Head title="Sri Lankan Friendship Association of South India">
            <link rel="preload" as="image" href="/images/hero/srilanka-poster.webp" />
        </Head>
        <div className="cream-home">
            <div className="cream-hero-surface">
            {!reducedMotion && !heroVideoFailed && <div className="cream-hero-video" aria-hidden="true"><video autoPlay muted loop playsInline preload="metadata" poster="/images/hero/srilanka-poster.webp" onError={() => setHeroVideoFailed(true)}><source src="/images/hero/srilanka.mp4" type="video/mp4" /></video></div>}
            <section className="cream-hero cream-container" aria-labelledby="home-title">
                <div className="cream-hero-copy">
                    <h1 id="home-title">Connecting Sri Lankans<span>in South India.</span></h1>
                    <p className="cream-intro">Shared roots. New friendships. A community to call your own.</p>
                    <div className="cream-actions">
                        <Link href={route('about')} className="cream-hero-link">Discover SLFA <ArrowRight size={17} /></Link>
                    </div>
                </div>
                <a href="#upcoming-event" className="hero-scroll-cue" aria-label="View the upcoming event"><i aria-hidden="true" /></a>
            </section>
            </div>
            <div id="upcoming-event" className="cream-hero-event-band scroll-mt-24">
                <div className="cream-container">
                    <div className="cream-event">
                        {event?.cover_image_path && <Link href={route('events.show', event.public_id)} className="cream-event-poster" aria-label={`View ${event.title}`}><img src={coverImageUrl(event.cover_image_path)} alt={`${event.title} flyer`} width="648" height="900" /></Link>}
                        {event ? <div className="cream-date-stamp" aria-hidden="true"><span>{new Intl.DateTimeFormat('en-IN', { day: '2-digit', timeZone: 'Asia/Kolkata' }).format(new Date(event.start_at))}</span><small>{new Intl.DateTimeFormat('en-IN', { month: 'short', timeZone: 'Asia/Kolkata' }).format(new Date(event.start_at))}</small></div> : <CalendarDays className="cream-event-icon" size={26} aria-hidden="true" />}
                        <div>
                            <p className="cream-eyebrow">Upcoming event</p>
                            <h2>{event?.title || 'Find your next SLFA gathering'}</h2>
                            {event ? <>
                                {/gala/i.test(event.title) && <p className="event-invitation">An evening of music, friendship & celebration.</p>}
                                <EventCountdown start={event.start_at} />
                                <time dateTime={event.start_at}>{dateLabel(event.start_at)}</time>
                                {event.location && <p className="cream-location"><MapPin size={15} aria-hidden="true" />{event.location}</p>}
                            </> : <p>Connect, celebrate, and build meaningful relationships.</p>}
                            <div className="event-actions"><Link href={event ? route('events.show', event.public_id) : route('happenings')} className="cream-link">{event ? 'View event & programme' : 'Explore events'} <ArrowRight size={15} /></Link>{event?.admission_enabled && (!event.admission_closes_at || new Date(event.admission_closes_at) > new Date()) && <Link className="cream-link" href={route('events.show', event.public_id) + '#admission'}>Request admission <ArrowRight size={15} /></Link>}</div>
                        </div>
                    </div>
                </div>
            </div>
            <section id="ways-to-connect" className="cream-section cream-container cream-stories" aria-labelledby="shared-stories-title">
                <div className="cream-stories-heading">
                    <div><p className="cream-eyebrow">Ways we connect</p><h2 id="shared-stories-title" className="cream-title">Our shared <em>stories.</em></h2></div>
                    <p>Roots remembered, celebrations shared, and new possibilities made together.</p>
                </div>
                <div className="cream-stories-grid">
                    {connections.map(({ title, kicker, description, image, routeName, cta }, index) => <motion.article className={`cream-story cream-story-${index + 1}`} key={title} initial={reducedMotion ? false : { opacity: 0, y: 22 }} whileInView={{ opacity: 1, y: 0 }} viewport={{ once: true, amount: 0.15 }} transition={{ duration: 0.7, delay: index * 0.1 }}>
                        <Link href={route(routeName)} className="cream-story-link">
                            <div className="cream-story-media"><img src={image} alt="" loading="lazy" width={index ? 900 : 1200} height={index ? 1200 : 1080} />{index === 0 && <MistCanvas />}</div>
                            <div className="cream-story-copy">
                                <p className="cream-story-kicker">{kicker}</p>
                                <h3>{title}</h3>
                                <p>{description}</p>
                                <span>{cta} <ArrowRight size={16} aria-hidden="true" /></span>
                            </div>
                        </Link>
                    </motion.article>)}
                </div>
            </section>
            <section className="cream-community-background" aria-labelledby="community-title">
                <div className="cream-community-slides" aria-hidden="true">
                    <AnimatePresence initial={false}>
                        <motion.img key={imageIndex} src={cultureImages[imageIndex]} alt="" loading="lazy" initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} transition={{ duration: reducedMotion ? 0 : 1.4 }} />
                    </AnimatePresence>
                </div>
                <div className="cream-community-veil" aria-hidden="true" />
                <div className="cream-container cream-community-content">
                    <div className="cream-community-copy">
                        <p className="cream-eyebrow">Friendship across borders</p>
                        <h2 id="community-title" className="cream-title"><span className="sr-only">A community built on connection</span><span aria-hidden="true">A <CommunityWord /><br />built on <em>connection.</em></span></h2>
                        <p>SLFA brings together people of Sri Lankan origin living in South India through friendship, cultural gatherings, and mutual support.</p>
                        <p>From celebrating our heritage to helping one another, there is a place here to belong, contribute, and grow.</p>
                        <Link href={route('about')} className="cream-link">Meet the association <ArrowRight size={17} /></Link>
                    <div className="cream-culture-controls" role="group" aria-label="Cultural photographs">
                        <span>Culture that connects us <small>{imageIndex + 1} / {cultureImages.length}</small></span>
                        <button type="button" aria-label="Previous cultural photograph" onClick={() => { setPaused(true); setImageIndex(index => (index + cultureImages.length - 1) % cultureImages.length); }}><ChevronLeft size={18} /></button>
                        {!reducedMotion && <button type="button" aria-label={paused ? 'Play cultural slideshow' : 'Pause cultural slideshow'} onClick={() => setPaused(value => !value)}>{paused ? <Play size={15} /> : <Pause size={15} />}</button>}
                        <button type="button" aria-label="Next cultural photograph" onClick={() => { setPaused(true); setImageIndex(index => (index + 1) % cultureImages.length); }}><ChevronRight size={18} /></button>
                    </div>
                    </div>
                </div>
            </section>

            <InterestExplorer />
            <section className="cream-section cream-updates" aria-labelledby="updates-title">
                <div className="cream-container">
                    <div className="cream-section-heading">
                        <div><p className="cream-eyebrow">News & community stories</p><h2 id="updates-title" className="cream-title">Life at <em>SLFA</em></h2><p className="cream-updates-intro">The people, gatherings, and shared moments that bring us together.</p></div>
                        <Link href={route('happenings')} className="cream-link">All news & events <ArrowRight size={17} /></Link>
                    </div>
                    <PhotoJournal />
                    <p className="cream-eyebrow journal-stories-label">Stories behind the moments</p>
                    <StoriesCarousel items={latestNews} />
                </div>
            </section>

            <section className="cream-section cream-container" aria-labelledby="membership-title">
                <div className="cream-membership">
                    <div>
                        <p className="cream-eyebrow">A place for you</p>
                        <h2 id="membership-title" className="cream-title">Make yourself <em>at home.</em></h2>
                        <p>Connect with fellow members, celebrate our heritage, and take part in community life across South India.</p>
                        <div className="brand-photo-frame membership-photo-frame"><div className="cream-membership-photo"><img src="/images/cta/gallery27.jpg" alt="SLFA members gathered for a community celebration" loading="lazy" width="1024" height="768" /><span>There is a place here for you.</span></div></div>
                        <div className="cream-actions"><Link href={auth.user ? route('dashboard') : route('register')} className="cream-button">{auth.user ? 'Your membership' : 'Apply for membership'} <ArrowRight size={17} /></Link><Link href={route('membership')} className="cream-link">Membership details <ArrowRight size={15} /></Link></div>
                    </div>
                    <ol>
                        {[
                            ['Check eligibility', 'Adults in South India with a connection to Sri Lanka can apply.'],
                            ['Apply online', 'Create an account and submit your membership details for review.'],
                            ['Stay connected', 'Follow your application status and manage your member profile online.'],
                        ].map(([title, description], index) => <li key={title}><span>0{index + 1}</span><div><h3>{title}</h3><p>{description}</p></div></li>)}
                    </ol>
                </div>
            </section>
        </div>
    </SLFAWebsiteLayout>;
}
