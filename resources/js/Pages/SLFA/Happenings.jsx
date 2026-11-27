import { Head, Link } from '@inertiajs/react';
import { ArrowRight, MapPin } from 'lucide-react';
import SLFAWebsiteLayout from '@/Layouts/SLFAWebsiteLayout';
import SLFAPageHero from '@/Components/SLFAPageHero';
import { coverImageUrl } from '@/lib/coverImageUrl';

const formatDate = (value, month = 'long') => new Intl.DateTimeFormat('en-IN', { day: 'numeric', month, year: 'numeric', timeZone: 'Asia/Kolkata' }).format(new Date(value));
const summary = (body = '') => body.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();

export default function Happenings({ auth, posts, upcomingEvents = [], pastEvents = [] }) {
    const featured = upcomingEvents[0];

    return <SLFAWebsiteLayout user={auth.user} cream>
        <Head title="News & Events" />
        <SLFAPageHero eyebrow="The community, in motion" title={<>News <em>& events.</em></>} description="Gatherings ahead, stories from the association, and moments worth remembering." image="/images/culture-celebrations.jpg"><a href="#events" className="cream-link">Upcoming events ↓</a><a href="#news" className="cream-link">Latest news ↓</a><a href="#past-events" className="cream-link">Past events ↓</a></SLFAPageHero>

        <section id="events" className="scroll-mt-24 bg-[#f3eddf] px-4 pb-20 pt-5 text-[#1f2937] sm:px-6 lg:px-8 lg:pb-28">
            <div className="mx-auto max-w-7xl">
                <p className="font-lato text-xs font-normal uppercase tracking-[0.27em] text-[#1B7A4A]">Come together</p>
                <h2 className="mt-5 font-lato text-4xl font-normal sm:text-6xl">Upcoming events</h2>
                {featured ? <div className="mt-8 grid gap-8 border-t border-[#d8ded2] pt-8 lg:grid-cols-[1fr_auto] lg:items-end lg:gap-16">
                    <div>
                        <p className="font-lato text-xs font-normal uppercase tracking-[0.2em] text-[#1B7A4A]">Next gathering · {formatDate(featured.start_at)}</p>
                        <h3 className="mt-5 max-w-3xl font-lato text-3xl font-normal leading-tight sm:text-5xl">{featured.title}</h3>
                        {featured.location && <p className="mt-5 flex items-start gap-2 text-gray-600"><MapPin size={17} aria-hidden="true" className="mt-1 shrink-0" />{featured.location}</p>}
                        <Link href={route('events.show', featured.public_slug)} className="mt-8 inline-flex items-center gap-2 border-b border-[#1B7A4A] pb-2 text-sm font-normal text-[#1B7A4A] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#1B7A4A]">See event details <ArrowRight size={17} aria-hidden="true" /></Link>
                    </div>
                    {featured.cover_image_path ? <img src={coverImageUrl(featured.cover_image_path)} alt="" className="max-h-72 w-full object-contain object-right lg:w-80" /> : <div className="hidden select-none text-right lg:block" aria-hidden="true"><span className="block font-lato text-[7rem] leading-none text-[#1B7A4A]/70">{new Intl.DateTimeFormat('en-IN', { day: '2-digit', timeZone: 'Asia/Kolkata' }).format(new Date(featured.start_at))}</span><span className="font-lato text-sm font-normal uppercase tracking-[0.4em] text-gray-500">{new Intl.DateTimeFormat('en-IN', { month: 'long', timeZone: 'Asia/Kolkata' }).format(new Date(featured.start_at))}</span></div>}
                </div> : <div className="mt-8 border-t border-[#d8ded2] py-8"><h3 className="font-lato text-3xl font-normal sm:text-5xl">The next gathering is being planned</h3><p className="mt-4 text-gray-600">Dates and RSVP details will appear here when announced.</p></div>}
                {upcomingEvents.slice(1).map(event => <Link key={event.id} href={route('events.show', event.public_slug)} className="group grid gap-3 border-t border-[#d8ded2] py-5 sm:grid-cols-[11rem_1fr_auto] sm:items-center"><time className="text-sm text-[#1B7A4A]">{formatDate(event.start_at)}</time><span className="font-lato text-2xl font-normal">{event.title}</span><ArrowRight size={18} aria-hidden="true" className="text-[#1B7A4A] transition-transform group-hover:translate-x-1" /></Link>)}
            </div>
        </section>

        <section id="news" className="scroll-mt-24 bg-[#FAF7F2] px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
            <div className="mx-auto max-w-7xl"><p className="font-lato text-xs font-normal uppercase tracking-[0.27em] text-[#a45116]">From the association</p><h2 className="mt-5 font-lato text-4xl font-normal text-gray-950 sm:text-6xl">News & activities</h2><p className="mt-4 max-w-xl text-lg text-gray-600">An archive of our meetings, gatherings, and community initiatives.</p>
                {posts.data.length ? <div className="mt-12 grid gap-8 md:grid-cols-2 lg:grid-cols-3">{posts.data.map(post => <article key={post.id} className="group editorial-card min-w-0"><div className="brand-photo-frame relative mb-5 h-52 overflow-hidden">{post.cover_image_path ? <img src={coverImageUrl(post.cover_image_path)} alt="" loading="lazy" className="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105" /> : <div className="flex h-full items-center justify-center bg-[#f3eddf] font-lato text-5xl font-normal text-[#1B7A4A]/60">SLFA</div>}</div><p className="font-lato text-xs font-normal uppercase tracking-[0.14em] text-[#a45116]">{post.category || 'Community'} · {formatDate(post.published_at, 'short')}</p><h3 className="mt-3 font-lato text-2xl font-normal leading-tight text-gray-950">{post.title}</h3><p className="mt-3 line-clamp-3 text-sm leading-relaxed text-gray-700">{summary(post.body)}</p><Link href={route('news.show', post.public_slug)} className="mt-5 inline-flex items-center gap-2 border-b border-[#1B7A4A] pb-1 font-lato text-xs font-normal uppercase tracking-wider text-[#1B7A4A] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#1B7A4A]">Read story <ArrowRight size={16} aria-hidden="true" /></Link></article>)}</div> : <p className="mt-12 border-t border-[#d8ded2] pt-6 text-gray-700">News will appear here as it is published.</p>}
                {posts.links?.length > 3 && <nav aria-label="News pages" className="mt-12 flex flex-wrap gap-4 border-t border-[#d8ded2] pt-6">{posts.links.map((link, index) => link.url ? <Link key={index} href={`${link.url}#news`} aria-current={link.active ? 'page' : undefined} className={`text-sm font-normal underline-offset-4 hover:underline ${link.active ? 'text-[#1B7A4A]' : 'text-gray-700'}`}>{index === 0 ? 'Previous' : index === posts.links.length - 1 ? 'Next' : link.label}</Link> : <span key={index} className="text-sm text-gray-400">{index === 0 ? 'Previous' : 'Next'}</span>)}</nav>}
            </div>
        </section>

        {pastEvents.length > 0 && <section id="past-events" className="scroll-mt-24 bg-[#f3eddf] px-4 py-20 text-[#1f2937] sm:px-6 lg:px-8"><div className="mx-auto max-w-7xl"><p className="font-lato text-xs font-normal uppercase tracking-[0.27em] text-[#1B7A4A]">Looking back</p><h2 className="mt-5 font-lato text-4xl font-normal sm:text-6xl">Past events</h2><div className="mt-10 border-t border-[#d8ded2]">{pastEvents.map(event => <Link key={event.id} href={route('events.show', event.public_slug)} className="group grid gap-3 border-b border-[#d8ded2] py-5 sm:grid-cols-[11rem_1fr_auto] sm:items-center"><time className="text-sm text-[#1B7A4A]">{formatDate(event.start_at)}</time><span className="font-lato text-2xl font-normal">{event.title}</span><ArrowRight size={18} aria-hidden="true" className="text-[#1B7A4A] transition-transform group-hover:translate-x-1" /></Link>)}</div></div></section>}
    </SLFAWebsiteLayout>;
}
