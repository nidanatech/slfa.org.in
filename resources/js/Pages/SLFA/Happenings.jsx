import { Head, Link } from '@inertiajs/react';
import { ArrowRight, CalendarDays, MapPin } from 'lucide-react';
import SLFAWebsiteLayout from '@/Layouts/SLFAWebsiteLayout';

const eventDate = (value) => new Intl.DateTimeFormat('en-IN', { day: 'numeric', month: 'long', year: 'numeric', timeZone: 'Asia/Kolkata' }).format(new Date(value));
const newsDate = (value) => new Intl.DateTimeFormat('en-IN', { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'Asia/Kolkata' }).format(new Date(value));
const summary = (body = '') => body.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();

export default function Happenings({ auth, posts, upcomingEvents = [], pastEvents = [] }) {
    const featured = upcomingEvents[0];

    return (
        <SLFAWebsiteLayout user={auth.user} darkNav>
            <Head title="News & Events" />
            <section className="relative overflow-hidden bg-gray-950 px-4 pb-20 pt-36 text-white sm:px-6 lg:px-8">
                <div className="absolute inset-0 opacity-25" style={{ backgroundImage: "url('/images/hero-ocean-bg-slfa.jpg')", backgroundSize: 'cover', backgroundPosition: 'center' }} aria-hidden="true" />
                <div className="absolute inset-0 bg-gradient-to-r from-gray-950 via-gray-950/85 to-gray-950/45" aria-hidden="true" />
                <div className="relative mx-auto max-w-7xl">
                    <p className="font-lato text-xs font-bold uppercase tracking-[0.28em] text-[#D4AF37]">The community, in motion</p>
                    <h1 className="mt-5 max-w-3xl font-abhaya text-5xl font-bold leading-[1.05] text-balance sm:text-7xl">News <span className="italic font-light text-[#D4AF37]">& Events</span></h1>
                    <p className="mt-5 max-w-xl font-lato text-lg leading-relaxed text-gray-300">Gatherings ahead, stories from our members, and moments worth remembering.</p>
                    <div className="mt-8 flex flex-wrap gap-3">
                        <a href="#events" className="rounded-full bg-[#D4AF37] px-6 py-3 font-lato text-sm font-bold text-gray-950 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white">Explore events</a>
                        <a href="#news" className="rounded-full border border-white/30 px-6 py-3 font-lato text-sm font-bold text-white hover:bg-white/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white">Read the news</a>
                    </div>
                </div>
            </section>

            <section id="events" className="scroll-mt-24 bg-[#0b101c] px-4 py-16 text-white sm:px-6 lg:px-8">
                <div className="mx-auto max-w-7xl">
                    <div className="mb-8 flex flex-wrap items-end justify-between gap-4">
                        <div><p className="font-lato text-xs font-bold uppercase tracking-[0.25em] text-[#D4AF37]">Come together</p><h2 className="mt-2 font-abhaya text-4xl font-bold sm:text-5xl">Upcoming events</h2></div>
                        <p className="max-w-md text-sm leading-relaxed text-gray-400">See what is planned and open each event for details and RSVP information.</p>
                    </div>
                    {featured ? (
                        <div className="grid gap-4 lg:grid-cols-[1.25fr_1fr]">
                            <Link href={route('events.show', featured.id)} className="group relative min-h-80 overflow-hidden rounded-[2rem] border border-[#D4AF37]/50 bg-[#33200f] p-8 shadow-[0_20px_70px_rgba(212,175,55,0.12)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#D4AF37] sm:p-10">
                                {featured.cover_image_path && <img src={`/storage/${featured.cover_image_path}`} alt="" className="absolute inset-0 h-full w-full object-cover opacity-40" />}
                                <div className="absolute inset-0 bg-gradient-to-t from-gray-950 via-gray-950/70 to-transparent" aria-hidden="true" />
                                <div className="relative flex h-full flex-col justify-end"><span className="mb-4 w-fit rounded-full bg-[#D4AF37] px-4 py-1.5 font-lato text-[11px] font-bold uppercase tracking-[0.16em] text-gray-950">Next gathering</span><h3 className="max-w-xl font-abhaya text-3xl font-bold leading-tight text-white sm:text-4xl">{featured.title}</h3><p className="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-sm text-gray-200"><span className="inline-flex items-center gap-2"><CalendarDays size={16} aria-hidden="true" />{eventDate(featured.start_at)}</span>{featured.location && <span className="inline-flex items-center gap-2"><MapPin size={16} aria-hidden="true" />{featured.location}</span>}</p><span className="mt-6 inline-flex items-center gap-2 font-lato text-sm font-bold text-[#F4D66E]">View event <ArrowRight size={17} aria-hidden="true" /></span></div>
                            </Link>
                            <div className="grid gap-4">
                                {upcomingEvents.slice(1, 3).map(event => <Link href={route('events.show', event.id)} key={event.id} className="group rounded-3xl border border-white/10 bg-white/[0.04] p-7 hover:border-[#D4AF37]/50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#D4AF37]"><span className="font-lato text-xs font-bold uppercase tracking-widest text-[#D4AF37]">{eventDate(event.start_at)}</span><h3 className="mt-3 font-abhaya text-2xl font-bold text-white">{event.title}</h3>{event.location && <p className="mt-2 text-sm text-gray-400">{event.location}</p>}<span className="mt-5 inline-flex items-center gap-2 text-sm font-bold text-[#D4AF37]">View event <ArrowRight size={16} aria-hidden="true" /></span></Link>)}
                                {upcomingEvents.length < 2 && <div className="flex items-center rounded-3xl border border-white/10 bg-white/[0.04] p-7"><p className="text-gray-300">More gathering details will appear here as they are announced.</p></div>}
                            </div>
                        </div>
                    ) : <div className="rounded-[2rem] border border-[#D4AF37]/30 bg-gradient-to-br from-[#292012] to-gray-950 p-10 sm:p-14"><CalendarDays className="text-[#D4AF37]" size={34} aria-hidden="true" /><h3 className="mt-5 font-abhaya text-3xl font-bold">The next gathering is being planned</h3><p className="mt-3 max-w-xl text-gray-300">Event dates and RSVP details will appear here as soon as they are announced. Follow our news for community updates.</p><a href="#news" className="mt-6 inline-flex items-center gap-2 font-bold text-[#D4AF37]">Read updates <ArrowRight size={17} aria-hidden="true" /></a></div>}
                </div>
            </section>

            <section id="news" className="scroll-mt-24 bg-[#f5f1e9] px-4 py-16 sm:px-6 lg:px-8">
                <div className="mx-auto max-w-7xl">
                    <div className="mb-8 flex flex-wrap items-end justify-between gap-4"><div><p className="font-lato text-xs font-bold uppercase tracking-[0.25em] text-[#9a7714]">From the association</p><h2 className="mt-2 font-abhaya text-4xl font-bold text-gray-950 sm:text-5xl">Latest news</h2></div><p className="text-sm text-gray-600">Stories, announcements, and community highlights.</p></div>
                    {posts.data.length ? <div className="grid gap-5 md:grid-cols-2 lg:grid-cols-3">{posts.data.map(post => <article key={post.id} className="group flex flex-col overflow-hidden rounded-3xl border border-[#d8d0c3] bg-white shadow-[0_12px_35px_rgba(20,24,34,0.04)]"><div className="h-48 overflow-hidden bg-gray-950">{post.cover_image_path ? <img src={`/storage/${post.cover_image_path}`} alt="" loading="lazy" className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" /> : <div className="flex h-full items-center justify-center bg-gradient-to-br from-[#332124] to-gray-950 font-abhaya text-5xl font-bold text-[#D4AF37]/50">SLFA</div>}</div><div className="flex flex-1 flex-col p-7"><p className="font-lato text-xs font-bold uppercase tracking-wider text-[#8b6915]">{post.category || 'Community'} · {newsDate(post.published_at)}</p><h3 className="mt-3 font-abhaya text-2xl font-bold leading-tight text-gray-950">{post.title}</h3><p className="mt-3 line-clamp-3 text-sm leading-relaxed text-gray-600">{summary(post.body)}</p><Link href={route('news.show', post.id)} className="mt-6 inline-flex items-center gap-2 self-start font-lato text-sm font-bold text-[#800000] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#800000]">Read story <ArrowRight size={16} aria-hidden="true" /></Link></div></article>)}</div> : <div className="rounded-3xl border border-[#d8d0c3] bg-white p-10 text-gray-600">News will appear here as it is published.</div>}
                    {posts.links?.length > 3 && <nav aria-label="News pages" className="mt-9 flex flex-wrap justify-center gap-2">{posts.links.map((link, index) => link.url ? <Link key={index} href={`${link.url}#news`} aria-current={link.active ? 'page' : undefined} className={`rounded-full px-4 py-2 text-sm font-bold ${link.active ? 'bg-gray-950 text-white' : 'bg-white text-gray-800 hover:bg-[#D4AF37]'}`}>{index === 0 ? 'Previous' : index === posts.links.length - 1 ? 'Next' : link.label}</Link> : <span key={index} className="rounded-full px-4 py-2 text-sm text-gray-400">{index === 0 ? 'Previous' : 'Next'}</span>)}</nav>}
                </div>
            </section>

            {pastEvents.length > 0 && <section className="bg-gray-950 px-4 py-16 text-white sm:px-6 lg:px-8"><div className="mx-auto max-w-7xl"><p className="font-lato text-xs font-bold uppercase tracking-[0.25em] text-[#D4AF37]">Looking back</p><h2 className="mt-2 font-abhaya text-4xl font-bold">Past events</h2><div className="mt-8 grid gap-3 md:grid-cols-2 lg:grid-cols-3">{pastEvents.map(event => <Link key={event.id} href={route('events.show', event.id)} className="rounded-2xl border border-white/10 bg-white/[0.04] p-6 hover:border-[#D4AF37]/50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#D4AF37]"><span className="font-lato text-xs font-bold uppercase tracking-wider text-[#D4AF37]">{eventDate(event.start_at)}</span><h3 className="mt-2 font-abhaya text-xl font-bold">{event.title}</h3><span className="mt-4 inline-flex items-center gap-2 text-sm text-gray-300">See highlights <ArrowRight size={15} aria-hidden="true" /></span></Link>)}</div></div></section>}
        </SLFAWebsiteLayout>
    );
}
