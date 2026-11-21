import React, { useEffect, useState } from 'react';
import { Link, Head } from '@inertiajs/react';
import { ArrowRight, CalendarDays, MapPin, ChevronLeft, ChevronRight, Pause, Play } from 'lucide-react';
import { AnimatePresence, motion, useReducedMotion } from 'motion/react';
import SLFAWebsiteLayout from '@/Layouts/SLFAWebsiteLayout';
import CommunityWord from '@/Components/CommunityWord';
import CoastlineMap from '@/Components/CoastlineMap';
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
    { title: 'Shared roots', description: 'Meet people who share your connection to Sri Lanka and South India.', image: '/images/themes/shared-roots.webp' },
    { title: 'Shared celebrations', description: 'Come together for cultural celebrations, family gatherings, and community events.', image: '/images/themes/shared-celebrations.webp' },
    { title: 'Shared possibilities', description: 'Build friendships, exchange ideas, and grow your professional connections.', image: '/images/themes/shared-possibilities.webp' },
];

export default function Home({ auth, latestNews = [], upcomingEvents = [] }) {
    const event = upcomingEvents?.[0];
    const reducedMotion = useReducedMotion();
    const [imageIndex, setImageIndex] = useState(0);
    const [paused, setPaused] = useState(false);
    useEffect(() => {
        if (reducedMotion || paused) return undefined;
        const timer = window.setInterval(() => setImageIndex(index => (index + 1) % cultureImages.length), 6500);
        return () => window.clearInterval(timer);
    }, [reducedMotion, paused]);

    return <SLFAWebsiteLayout user={auth.user} cream>
        <Head title="Sri Lankan Friendship Association of South India">
            <link rel="preload" as="image" href="/images/south-india-sri-lanka-light.webp" />
        </Head>
        <div className="cream-home">
            <div className="cream-hero-surface">
            <section className="cream-hero cream-container" aria-labelledby="home-title">
                <div className="cream-hero-copy">
                    <p className="cream-eyebrow">Sri Lankan Friendship Association of South India</p>
                    <h1 id="home-title">Shared roots.<span>New friendships.</span></h1>
                    <p className="cream-intro">Connecting Sri Lankans in South India. A community to call your own.</p>
                    <div className="cream-actions">
                        <Link href={auth.user ? route('dashboard') : route('register')} className="cream-button">{auth.user ? 'Your membership' : 'Apply for membership'} <ArrowRight size={17} /></Link>
                        <Link href={route('about')} className="cream-link">Explore the association <ArrowRight size={17} /></Link>
                    </div>

                </div>
                <div className="cream-map">
                    <CoastlineMap />
                    <svg className="cream-map-thread" viewBox="0 0 500 600" fill="none" aria-hidden="true"><ellipse cx="250" cy="300" rx="225" ry="250" stroke="#1b7a4a" strokeOpacity=".15" strokeDasharray="3 8" /><path className="cream-connection-line" d="M95 155 C440 50 65 440 360 465" pathLength="1" stroke="#ce8c36" strokeWidth="1.5" /><circle cx="95" cy="155" r="5" fill="#1b7a4a" /><circle cx="360" cy="465" r="5" fill="#e67e22" /></svg>
                    <figure className="cream-map-portrait cream-map-portrait-one"><img src="/images/cta/gallery15.jpg" alt="Members sharing time together at an SLFA gathering" width="1024" height="768" /><figcaption><Link href={route('membership')}>Find your community <ArrowRight size={14} /></Link></figcaption></figure>
                    <figure className="cream-map-portrait cream-map-portrait-two"><img src="/images/carousel/kandyan-light.webp" alt="Sri Lankan dancers in traditional dress" width="1554" height="1012" /><figcaption><Link href={route('happenings')}>Celebrate together <ArrowRight size={14} /></Link></figcaption></figure>
                    <p>Two places. A shared sense of belonging.</p>
                </div>
            </section>
            <div className="cream-hero-event-band">
                <div className="cream-container">
                    <div className="cream-event">
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
            </div>
            <section className="cream-section cream-container" aria-label="Ways to connect with the community">
                <div className="cream-connections cream-connections-pillars">
                    {connections.map(({ title, description, image }, index) => <motion.article key={title} initial={reducedMotion ? false : { opacity: 0, y: 18 }} whileInView={{ opacity: 1, y: 0 }} viewport={{ once: true, amount: 0.15 }} transition={{ duration: 0.65, delay: index * 0.1 }}>
                        <img className="cream-pillar-background" src={image} alt="" aria-hidden="true" loading="lazy" width="1200" height="800" />
                        <div className="cream-pillar-content">
                            <span className="cream-story-number">0{index + 1}</span>
                            <h3>{title}</h3>
                            <p>{description}</p>
                        </div>
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
                        <h2 id="community-title" className="cream-title"><span className="sr-only">A community built on connection</span><span aria-hidden="true">A community<br />built on <em>connection.</em></span></h2>
                        <CommunityWord controls />
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
                    <div className="cream-news-grid cream-news-editorial">
                        {latestNews?.length ? latestNews.slice(0, 3).map(post => <article key={post.id}>
                            <Link href={route('news.show', post.public_id)} className="cream-news-link">
                                {post.cover_image_path ? <div className="cream-photo"><img src={coverImageUrl(post.cover_image_path)} alt="" loading="lazy" /></div> : <div className="cream-photo cream-photo-placeholder" aria-hidden="true">SLFA</div>}
                                <div className="cream-news-copy"><time dateTime={post.published_at}>{dateLabel(post.published_at)}</time>
                                <h3>{post.title}</h3>
                                <span className="cream-link">Read story <ArrowRight size={15} /></span></div>
                            </Link>
                        </article>) : <p>Stories from the community will appear here as they are published.</p>}
                    </div>
                </div>
            </section>

            <section className="cream-section cream-container" aria-labelledby="membership-title">
                <div className="cream-membership">
                    <div>
                        <p className="cream-eyebrow">A place for you</p>
                        <h2 id="membership-title" className="cream-title">Make yourself <em>at home.</em></h2>
                        <p>Connect with fellow members, celebrate our heritage, and take part in community life across South India.</p>
                        <div className="cream-membership-photo"><img src="/images/cta/gallery27.jpg" alt="SLFA members gathered for a community celebration" loading="lazy" width="1024" height="768" /><span>There is a place here for you.</span></div>
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
