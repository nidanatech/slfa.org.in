import React, { useEffect, useState } from 'react';
import { Link, Head } from '@inertiajs/react';
import { ArrowRight, CalendarDays, MapPin } from 'lucide-react';
import { AnimatePresence, motion, useReducedMotion } from 'motion/react';
import SLFAWebsiteLayout from '@/Layouts/SLFAWebsiteLayout';
import CommunityWord from '@/Components/CommunityWord';
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
    { title: 'Shared roots', description: 'Meet people who share your connection to Sri Lanka and South India.', image: '/images/cta/gallery15.jpg', alt: 'SLFA members spending time together at a gathering' },
    { title: 'Shared celebrations', description: 'Come together for cultural celebrations, family gatherings, and community events.', image: '/images/cta/gallery27.jpg', alt: 'Members gathered at an SLFA community event' },
    { title: 'Shared possibilities', description: 'Build friendships, exchange ideas, and grow your professional connections.', image: '/images/cta/gallery4.jpg', alt: 'Members taking part in an association meeting' },
];

export default function Home({ auth, latestNews = [], upcomingEvents = [] }) {
    const event = upcomingEvents?.[0];
    const reducedMotion = useReducedMotion();
    const [imageIndex, setImageIndex] = useState(0);
    useEffect(() => {
        if (reducedMotion) return undefined;
        const timer = window.setInterval(() => setImageIndex(index => (index + 1) % cultureImages.length), 6500);
        return () => window.clearInterval(timer);
    }, [reducedMotion]);

    return <SLFAWebsiteLayout user={auth.user} cream>
        <Head title="Sri Lankan Friendship Association of South India">
            <link rel="preload" as="image" href="/images/south-india-sri-lanka-light.webp" />
        </Head>
        <div className="cream-home">
            <section className="cream-hero cream-container" aria-labelledby="home-title">
                <div className="cream-hero-copy">
                    <p className="cream-eyebrow">Sri Lankan Friendship Association of South India</p>
                    <h1 id="home-title">Connecting Sri Lankans <span>in South India.</span></h1>
                    <p className="cream-intro">Shared roots. New friendships. A community to call your own.</p>
                    <div className="cream-actions">
                        <Link href={auth.user ? route('dashboard') : route('register')} className="cream-button">{auth.user ? 'Your membership' : 'Apply for membership'} <ArrowRight size={17} /></Link>
                        <Link href={route('about')} className="cream-link">Explore the association <ArrowRight size={17} /></Link>
                    </div>
                    <div className="cream-event">
                        <CalendarDays className="cream-event-icon" size={26} aria-hidden="true" />
                        <div>
                            <p className="cream-eyebrow">Upcoming event</p>
                            <h2>{event?.title || 'Find your next SLFA gathering'}</h2>
                            {event ? <>
                                <time dateTime={event.start_at}>{dateLabel(event.start_at)}</time>
                                {event.location && <p className="cream-location"><MapPin size={15} aria-hidden="true" />{event.location}</p>}
                            </> : <p>Connect, celebrate, and build meaningful relationships.</p>}
                            <Link href={event ? route('events.show', event.public_id) : route('happenings')} className="cream-link">{event ? 'View event & programme' : 'Explore events'} <ArrowRight size={15} /></Link>
                        </div>
                    </div>
                </div>
                <div className="cream-map">
                    <img src="/images/south-india-sri-lanka-light.webp" alt="Watercolour map of South India in green and Sri Lanka in gold" width="1024" height="1280" fetchPriority="high" />
                    <p>Two places. A shared sense of belonging.</p>
                </div>
            </section>

            <section className="cream-section cream-container" aria-labelledby="community-title">
                <div className="cream-story">
                    <div>
                        <p className="cream-eyebrow">Friendship across borders</p>
                        <h2 id="community-title" className="cream-title"><span className="sr-only">A community built on connection</span><span aria-hidden="true">A <CommunityWord /><br />built on <em>connection.</em></span></h2>
                        <p>SLFA brings together people of Sri Lankan origin living in South India through friendship, cultural gatherings, and mutual support.</p>
                        <p>From celebrating our heritage to helping one another, there is a place here to belong, contribute, and grow.</p>
                        <Link href={route('about')} className="cream-link">Meet the association <ArrowRight size={17} /></Link>
                    </div>
                    <div className="cream-culture" aria-hidden="true">
                        <AnimatePresence initial={false}>
                            <motion.img key={imageIndex} src={cultureImages[imageIndex]} alt="" loading="lazy" initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} transition={{ duration: reducedMotion ? 0 : 1.4 }} />
                        </AnimatePresence>
                    </div>
                </div>
                <div className="cream-connections">
                    {connections.map(item => <article key={item.title}>
                        <div className="cream-photo"><img src={item.image} alt={item.alt} loading="lazy" width="1024" height="768" /></div>
                        <h3>{item.title}</h3><p>{item.description}</p>
                    </article>)}
                </div>
            </section>

            <section className="cream-section cream-updates" aria-labelledby="updates-title">
                <div className="cream-container">
                    <div className="cream-section-heading">
                        <div><p className="cream-eyebrow">Life at SLFA</p><h2 id="updates-title" className="cream-title">Our community, <em>in motion.</em></h2></div>
                        <Link href={route('happenings')} className="cream-link">All news & events <ArrowRight size={17} /></Link>
                    </div>
                    <div className="cream-news-grid">
                        {latestNews?.length ? latestNews.slice(0, 3).map(post => <article key={post.id}>
                            <Link href={route('news.show', post.public_id)} className="cream-news-link">
                                {post.cover_image_path ? <div className="cream-photo"><img src={coverImageUrl(post.cover_image_path)} alt="" loading="lazy" /></div> : <div className="cream-photo cream-photo-placeholder" aria-hidden="true">SLFA</div>}
                                <time dateTime={post.published_at}>{dateLabel(post.published_at)}</time>
                                <h3>{post.title}</h3>
                                <span className="cream-link">Read story <ArrowRight size={15} /></span>
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
