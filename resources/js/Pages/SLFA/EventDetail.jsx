import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import SLFAWebsiteLayout from '@/Layouts/SLFAWebsiteLayout';
import SLFAPageHero from '@/Components/SLFAPageHero';
import { Calendar, MapPin, Clock, CheckCircle, XCircle, HelpCircle } from 'lucide-react';
import { coverImageUrl } from '@/lib/coverImageUrl';

export default function EventDetail({ auth, event, isGoing }) {
    
    const [processing, setProcessing] = useState(false);

    const submitRsvp = (status) => {
        router.post(route('events.rsvp', event.id), { status }, {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
        });
    };

    const isPast = new Date(event.start_at) < new Date();
    const zone = event.timezone || 'Asia/Kolkata';
    const price = (amount) => new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 0 }).format(amount);

    return (
        <SLFAWebsiteLayout user={auth.user} darkNav={true}>
            <Head title={event.title} />

            <SLFAPageHero eyebrow={event.event_type === 'Other' ? 'SLFA gathering' : event.event_type || 'SLFA event'} title={event.title} backHref={`${route('happenings')}#events`} backLabel="Back to events" image={coverImageUrl(event.cover_image_path)}>
                <span className="inline-flex items-center gap-2"><Calendar className="h-4 w-4 text-[#D4AF37]" />{new Date(event.start_at).toLocaleDateString('en-IN', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', timeZone: zone })}</span>
                <span className="inline-flex items-center gap-2"><Clock className="h-4 w-4 text-[#D4AF37]" />{new Date(event.start_at).toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', timeZone: zone })}</span>
                {event.location && <span className="inline-flex items-center gap-2"><MapPin className="h-4 w-4 text-[#D4AF37]" />{event.location}</span>}
            </SLFAPageHero>

            <section className="min-h-[40vh] bg-[#f5f1e9] py-16 sm:py-20">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        
                        {/* Main Content */}
                        <div className="lg:col-span-2">
                            <article className="border-t border-[#b7a676] pt-7">
                                <h2 className="mb-6 font-abhaya text-3xl font-bold text-gray-950">About this event</h2>
                                
                                {event.description ? (
                                    <div className="prose prose-lg max-w-none text-gray-600 whitespace-pre-wrap">
                                        {event.description}
                                    </div>
                                ) : (
                                    <p className="text-gray-500 italic">No additional details provided.</p>
                                )}

                                {event.programme_items?.length > 0 && <section className="mt-12 border-t border-[#b7a676] pt-8" aria-labelledby="event-programme-heading">
                                    <h3 id="event-programme-heading" className="font-abhaya text-3xl font-bold text-gray-950">Programme</h3>
                                    <ol className="mt-6 border-t border-[#d5c8a6]">{event.programme_items.map(item => <li key={item.id} className="grid gap-2 border-b border-[#d5c8a6] py-4 sm:grid-cols-[6rem_1fr]"><time className="font-lato text-sm font-bold text-[#8b6915]">{new Intl.DateTimeFormat('en-IN', { hour: 'numeric', minute: '2-digit', hour12: true, timeZone: 'UTC' }).format(new Date(`2000-01-01T${item.time}:00Z`))}</time><div><h4 className="font-abhaya text-xl font-bold text-gray-950">{item.title}</h4>{item.details && <p className="mt-1 text-sm leading-relaxed text-gray-600">{item.details}</p>}</div></li>)}</ol>
                                </section>}

                                {event.sponsors?.length > 0 && <section className="mt-12 border-t border-[#b7a676] pt-8" aria-labelledby="event-sponsors-heading"><h3 id="event-sponsors-heading" className="font-abhaya text-3xl font-bold text-gray-950">Sponsors & partners</h3><div className="mt-5 flex flex-wrap gap-x-8 gap-y-4">{event.sponsors.map(sponsor => sponsor.website_url ? <a key={sponsor.id} href={sponsor.website_url} target="_blank" rel="sponsored noopener noreferrer" className="border-b border-[#800000] pb-1 font-bold text-[#800000] hover:text-gray-950">{sponsor.name} ↗</a> : <span key={sponsor.id} className="font-bold text-gray-700">{sponsor.name}</span>)}</div></section>}

                                {/* Photos stay with their event rather than a separate gallery index. */}
                                {event.albums && event.albums.length > 0 && (
                                    <section id="photos" className="mt-12 scroll-mt-24 border-t border-[#b7a676] pt-8" aria-labelledby="event-photos-heading"><h3 id="event-photos-heading" className="font-abhaya text-3xl font-bold text-gray-950">Event photos</h3>{event.albums.map(album => <div key={album.id} className="mt-6"><h4 className="mb-3 font-abhaya text-xl font-bold text-gray-800">{album.title}</h4><div className="grid gap-4 sm:grid-cols-2">{album.media?.map(item => item.type === 'video' ? <video key={item.id} controls src={`/storage/${item.path}`} className="aspect-[4/3] w-full bg-gray-950 object-cover" /> : <a key={item.id} href={`/storage/${item.path}`} target="_blank" rel="noopener noreferrer"><img src={`/storage/${item.path}`} alt={item.caption || `Photo from ${event.title}`} loading="lazy" className="aspect-[4/3] w-full object-cover" /></a>)}</div></div>)}</section>
                                )}
                            </article>
                        </div>

                        {/* Sidebar / RSVP */}
                        <div className="space-y-6 animate-fade-up-delay-1">
                            
                            {event.rsvp_enabled && !isPast && (
                                <div className="border-t border-[#b7a676] pt-7">
                                    <h3 className="mb-4 font-abhaya text-2xl font-bold text-gray-950">Are you attending?</h3>
                                    
                                    {!auth.user ? (
                                        <div className="bg-gray-50 p-4 rounded-md text-center">
                                            <p className="text-sm text-gray-600 mb-3">Please log in to RSVP for this event.</p>
                                            <Link href={route('login')} className="inline-block rounded-full bg-[#D4AF37] px-5 py-2 text-sm font-bold text-gray-950 hover:bg-gray-950 hover:text-white">Log in</Link>
                                        </div>
                                    ) : auth.user.account_status !== 'active' ? (
                                        <p className="text-sm text-gray-600">RSVPs are available after your membership is approved.</p>
                                    ) : (
                                        <div className="space-y-4">
                                            {isGoing && (
                                                <div className="bg-green-50 border border-green-200 text-green-700 p-3 rounded-md flex items-center text-sm font-semibold mb-4">
                                                    <CheckCircle className="w-5 h-5 mr-2" /> You are going to this event!
                                                </div>
                                            )}
                                            
                                            <div className="grid grid-cols-1 gap-2">
                                                <button 
                                                    onClick={() => submitRsvp('going')}
                                                    disabled={processing}
                                                    className="flex w-full items-center justify-center rounded-full bg-gray-950 px-4 py-3 font-bold text-white transition hover:bg-[#800000] disabled:opacity-50"
                                                >
                                                    <CheckCircle className="w-4 h-4 mr-2" /> Yes, I'm Going
                                                </button>
                                                <button 
                                                    onClick={() => submitRsvp('maybe')}
                                                    disabled={processing}
                                                    className="flex w-full items-center justify-center rounded-full border border-gray-300 bg-white px-4 py-3 font-bold text-gray-950 transition hover:border-[#D4AF37] disabled:opacity-50"
                                                >
                                                    <HelpCircle className="w-4 h-4 mr-2" /> Maybe
                                                </button>
                                                <button 
                                                    onClick={() => submitRsvp('declined')}
                                                    disabled={processing}
                                                    className="flex w-full items-center justify-center rounded-full bg-gray-100 px-4 py-3 font-bold text-gray-700 transition hover:bg-gray-200 disabled:opacity-50"
                                                >
                                                    <XCircle className="w-4 h-4 mr-2" /> Can't Make It
                                                </button>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            )}

                            {isPast && (
                                <div className="border-t border-[#b7a676] py-6">
                                    <h3 className="font-bold text-gray-600 mb-2">Event Concluded</h3>
                                    <p className="text-sm text-gray-500">This event has already taken place.</p>
                                </div>
                            )}

                            <div className="border-t border-[#b7a676] pt-6">
                                <h3 className="font-abhaya text-xl font-bold text-gray-950">Location</h3>
                                <p className="mt-3 flex items-start gap-2 text-sm text-gray-600"><MapPin className="h-5 w-5 shrink-0 text-[#B99425]" />{event.venue_name || event.location || 'Location to be announced'}</p>
                                {event.venue_address && <p className="mt-2 pl-7 text-sm text-gray-600">{event.venue_address}</p>}
                                {event.dress_code && <p className="mt-5 text-sm text-gray-700"><strong>Dress code:</strong> {event.dress_code}</p>}
                                {(event.adult_price !== null || event.child_price !== null) && <div className="mt-5 border-t border-[#d5c8a6] pt-4 text-sm text-gray-700"><h4 className="font-bold">Admission</h4>{event.adult_price !== null && <p className="mt-2">Adults: {price(event.adult_price)}</p>}{event.child_price !== null && <p className="mt-1">Children{event.child_age_range ? ` (${event.child_age_range})` : ''}: {price(event.child_price)}</p>}</div>}
                                {event.contact_details && <p className="mt-5 border-t border-[#d5c8a6] pt-4 text-sm text-gray-700"><strong>For information:</strong> {event.contact_details}</p>}
                            </div>
                        </div>

                    </div>
                </div>
            </section>
        </SLFAWebsiteLayout>
    );
}
