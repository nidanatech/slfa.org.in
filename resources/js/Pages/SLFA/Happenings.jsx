import { Head, Link } from '@inertiajs/react';
import { ArrowRight, MapPin } from 'lucide-react';
import SLFAWebsiteLayout from '@/Layouts/SLFAWebsiteLayout';
import PhotoBackdrop from '@/Components/PhotoBackdrop';
import { coverImageUrl } from '@/lib/coverImageUrl';

const heroImages = ['/images/kandyan-srilankandance.jpg', '/images/Sri-Lanka-Culture.webp'];
const formatDate = (value, month = 'long') => new Intl.DateTimeFormat('en-IN', { day: 'numeric', month, year: 'numeric', timeZone: 'Asia/Kolkata' }).format(new Date(value));
const summary = (body = '') => body.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();

export default function Happenings({ auth, posts, upcomingEvents = [], pastEvents = [] }) {
    const featured = upcomingEvents[0];

    return <SLFAWebsiteLayout user={auth.user} darkNav>
        <Head title="News & Events" />
        <section className="relative flex min-h-[58svh] items-end overflow-hidden bg-gray-950 px-4 pb-14 pt-36 text-white sm:px-6 lg:px-8">
            <PhotoBackdrop images={heroImages} />
            <div className="absolute inset-0 bg-gradient-to-r from-gray-950 via-gray-950/75 to-gray-950/25" aria-hidden="true" />
            <div className="absolute inset-0 bg-gradient-to-t from-gray-950 via-gray-950/10 to-gray-950/30" aria-hidden="true" />
            <div className="relative mx-auto w-full max-w-7xl">
                <p className="font-lato text-xs font-bold uppercase tracking-[0.28em] text-[#D4AF37]">The community, in motion</p>
                <h1 className="mt-6 font-abhaya text-5xl font-bold leading-[1.05] sm:text-7xl lg:text-8xl">News <em className="font-light text-[#D4AF37]">& Events</em></h1>
                <p className="mt-6 max-w-xl font-lato text-lg leading-relaxed text-gray-200">Gatherings ahead, stories from the association, and moments worth remembering.</p>
                <div className="mt-10 flex flex-wrap gap-8 font-lato text-xs font-bold uppercase tracking-wider"><a href="#events" className="border-b border-[#D4AF37] pb-2 text-[#D4AF37] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#D4AF37]">Upcoming events ↓</a><a href="#news" className="border-b border-white/50 pb-2 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white">Latest news ↓</a></div>
            </div>
        </section>

        <section id="events" className="scroll-mt-24 bg-gray-950 px-4 pb-20 pt-5 text-white sm:px-6 lg:px-8 lg:pb-28">
            <div className="mx-auto max-w-7xl">
                <p className="font-lato text-xs font-bold uppercase tracking-[0.27em] text-[#D4AF37]">Come together</p>
                <h2 className="mt-5 font-abhaya text-4xl font-bold sm:text-6xl">Upcoming events</h2>
                {featured ? <div className="mt-8 grid gap-8 border-t border-white/20 pt-8 lg:grid-cols-[1fr_auto] lg:items-end lg:gap-16">
                    <div>
                        <p className="font-lato text-xs font-bold uppercase tracking-[0.2em] text-[#D4AF37]">Next gathering · {formatDate(featured.start_at)}</p>
                        <h3 className="mt-5 max-w-3xl font-abhaya text-3xl font-bold leading-tight sm:text-5xl">{featured.title}</h3>
                        {featured.location && <p className="mt-5 flex items-start gap-2 text-gray-300"><MapPin size={17} aria-hidden="true" className="mt-1 shrink-0" />{featured.location}</p>}
                        <Link href={route('events.show', featured.public_id)} className="mt-8 inline-flex items-center gap-2 border-b border-[#D4AF37] pb-2 text-sm font-bold text-[#D4AF37] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#D4AF37]">See event details <ArrowRight size={17} aria-hidden="true" /></Link>
                    </div>
                    {featured.cover_image_path ? <img src={coverImageUrl(featured.cover_image_path)} alt="" className="max-h-72 w-full object-contain object-right lg:w-80" /> : <div className="hidden select-none text-right lg:block" aria-hidden="true"><span className="block font-abhaya text-[7rem] leading-none text-[#D4AF37]/70">{new Intl.DateTimeFormat('en-IN', { day: '2-digit', timeZone: 'Asia/Kolkata' }).format(new Date(featured.start_at))}</span><span className="font-lato text-sm font-bold uppercase tracking-[0.4em] text-gray-500">{new Intl.DateTimeFormat('en-IN', { month: 'long', timeZone: 'Asia/Kolkata' }).format(new Date(featured.start_at))}</span></div>}
                </div> : <div className="mt-8 border-t border-white/20 py-8"><h3 className="font-abhaya text-3xl font-bold sm:text-5xl">The next gathering is being planned</h3><p className="mt-4 text-gray-300">Dates and RSVP details will appear here when announced.</p></div>}
                {upcomingEvents.slice(1).map(event => <Link key={event.id} href={route('events.show', event.public_id)} className="group grid gap-3 border-t border-white/20 py-5 sm:grid-cols-[11rem_1fr_auto] sm:items-center"><time className="text-sm text-[#D4AF37]">{formatDate(event.start_at)}</time><span className="font-abhaya text-2xl font-bold">{event.title}</span><ArrowRight size={18} aria-hidden="true" className="text-[#D4AF37] transition-transform group-hover:translate-x-1" /></Link>)}
            </div>
        </section>

        <section id="news" className="scroll-mt-24 bg-[#f5f1e9] px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
            <div className="mx-auto max-w-7xl"><p className="font-lato text-xs font-bold uppercase tracking-[0.27em] text-[#9a7714]">From the association</p><h2 className="mt-5 font-abhaya text-4xl font-bold text-gray-950 sm:text-6xl">News & activities</h2><p className="mt-4 max-w-xl text-lg text-gray-600">An archive of our meetings, gatherings, and community initiatives.</p>
                {posts.data.length ? <div className="mt-12 grid gap-x-9 gap-y-12 md:grid-cols-2 lg:grid-cols-3">{posts.data.map(post => <article key={post.id} className="group min-w-0 border-t border-[#b7a676] pt-5"><div className="relative mb-5 h-44 overflow-hidden bg-[#1c2430]">{post.cover_image_path ? <img src={coverImageUrl(post.cover_image_path)} alt="" loading="lazy" className="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105" /> : <div className="flex h-full items-center justify-center bg-gradient-to-br from-[#4d3333] to-gray-950 font-abhaya text-5xl font-bold text-[#D4AF37]/60">SLFA</div>}</div><p className="font-lato text-xs font-bold uppercase tracking-[0.14em] text-[#8b6915]">{post.category || 'Community'} · {formatDate(post.published_at, 'short')}</p><h3 className="mt-3 font-abhaya text-2xl font-bold leading-tight text-gray-950">{post.title}</h3><p className="mt-3 line-clamp-3 text-sm leading-relaxed text-gray-700">{summary(post.body)}</p><Link href={route('news.show', post.public_id)} className="mt-5 inline-flex items-center gap-2 border-b border-[#800000] pb-1 font-lato text-xs font-bold uppercase tracking-wider text-[#800000] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#800000]">Read story <ArrowRight size={16} aria-hidden="true" /></Link></article>)}</div> : <p className="mt-12 border-t border-[#b7a676] pt-6 text-gray-700">News will appear here as it is published.</p>}
                {posts.links?.length > 3 && <nav aria-label="News pages" className="mt-12 flex flex-wrap gap-4 border-t border-[#b7a676] pt-6">{posts.links.map((link, index) => link.url ? <Link key={index} href={`${link.url}#news`} aria-current={link.active ? 'page' : undefined} className={`text-sm font-bold underline-offset-4 hover:underline ${link.active ? 'text-[#800000]' : 'text-gray-700'}`}>{index === 0 ? 'Previous' : index === posts.links.length - 1 ? 'Next' : link.label}</Link> : <span key={index} className="text-sm text-gray-400">{index === 0 ? 'Previous' : 'Next'}</span>)}</nav>}
            </div>
        </section>

        {pastEvents.length > 0 && <section className="bg-gray-950 px-4 py-20 text-white sm:px-6 lg:px-8"><div className="mx-auto max-w-7xl"><p className="font-lato text-xs font-bold uppercase tracking-[0.27em] text-[#D4AF37]">Looking back</p><h2 className="mt-5 font-abhaya text-4xl font-bold sm:text-6xl">Past events</h2><div className="mt-10 border-t border-white/30">{pastEvents.map(event => <Link key={event.id} href={route('events.show', event.public_id)} className="group grid gap-3 border-b border-white/20 py-5 sm:grid-cols-[11rem_1fr_auto] sm:items-center"><time className="text-sm text-[#D4AF37]">{formatDate(event.start_at)}</time><span className="font-abhaya text-2xl font-bold">{event.title}</span><ArrowRight size={18} aria-hidden="true" className="text-[#D4AF37] transition-transform group-hover:translate-x-1" /></Link>)}</div></div></section>}
    </SLFAWebsiteLayout>;
}
