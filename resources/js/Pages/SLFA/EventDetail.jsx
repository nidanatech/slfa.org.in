import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import SLFAWebsiteLayout from '@/Layouts/SLFAWebsiteLayout';
import SLFAPageHero from '@/Components/SLFAPageHero';
import SLFAPageCard from '@/Components/SLFAPageCard';
import { Calendar, MapPin, Clock, CheckCircle, XCircle, HelpCircle } from 'lucide-react';

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

    return (
        <SLFAWebsiteLayout user={auth.user} darkNav={true}>
            <Head title={event.title} />

            <SLFAPageHero eyebrow={event.event_type || 'SLFA event'} title={event.title} backHref={route('happenings')} backLabel="Back to events" image={event.cover_image_path ? `/storage/${event.cover_image_path}` : null}>
                <span className="inline-flex items-center gap-2"><Calendar className="h-4 w-4 text-[#D4AF37]" />{new Date(event.start_at).toLocaleDateString('en-IN', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', timeZone: 'Asia/Kolkata' })}</span>
                <span className="inline-flex items-center gap-2"><Clock className="h-4 w-4 text-[#D4AF37]" />{new Date(event.start_at).toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Kolkata' })}</span>
                {event.location && <span className="inline-flex items-center gap-2"><MapPin className="h-4 w-4 text-[#D4AF37]" />{event.location}</span>}
            </SLFAPageHero>

            <section className="min-h-[40vh] bg-[#FAF8F4] py-16 sm:py-20">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        
                        {/* Main Content */}
                        <div className="lg:col-span-2">
                            <SLFAPageCard as="article" className="border-t-4 border-t-[#D4AF37]">
                                <h2 className="mb-6 font-abhaya text-3xl font-bold text-gray-950">About this event</h2>
                                
                                {event.description ? (
                                    <div className="prose prose-lg max-w-none text-gray-600 whitespace-pre-wrap">
                                        {event.description}
                                    </div>
                                ) : (
                                    <p className="text-gray-500 italic">No additional details provided.</p>
                                )}

                                {/* Related Gallery Albums */}
                                {event.albums && event.albums.length > 0 && (
                                    <div className="mt-12 pt-8 border-t border-gray-100">
                                        <h3 className="text-xl font-bold text-slfa-navy mb-6">Event Gallery</h3>
                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            {event.albums.map(album => (
                                                <Link key={album.id} href={route('gallery.show', album.id)} className="group block overflow-hidden rounded-2xl bg-[#FAF8F4] transition hover:shadow-md">
                                                    <div className="h-32 bg-gray-200 relative">
                                                        {album.cover_image_path ? (
                                                            <img src={`/storage/${album.cover_image_path}`} alt={album.title} className="w-full h-full object-cover group-hover:scale-105 transition" />
                                                        ) : (
                                                            <div className="w-full h-full bg-slfa-navy flex items-center justify-center"><span className="text-white text-xs">No Cover</span></div>
                                                        )}
                                                    </div>
                                                    <div className="p-3">
                                                        <h4 className="font-bold text-sm text-slfa-navy group-hover:text-slfa-coral">{album.title}</h4>
                                                    </div>
                                                </Link>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </SLFAPageCard>
                        </div>

                        {/* Sidebar / RSVP */}
                        <div className="space-y-6 animate-fade-up-delay-1">
                            
                            {event.rsvp_enabled && !isPast && (
                                <SLFAPageCard className="border-t-4 border-t-[#D4AF37]">
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
                                </SLFAPageCard>
                            )}

                            {isPast && (
                                <div className="bg-gray-100 p-6 rounded-xl border border-gray-200 text-center">
                                    <h3 className="font-bold text-gray-600 mb-2">Event Concluded</h3>
                                    <p className="text-sm text-gray-500">This event has already taken place.</p>
                                </div>
                            )}

                            <SLFAPageCard>
                                <h3 className="font-abhaya text-xl font-bold text-gray-950">Location</h3>
                                <p className="mt-3 flex items-start gap-2 text-sm text-gray-600"><MapPin className="h-5 w-5 shrink-0 text-[#B99425]" />{event.location || 'Location to be announced'}</p>
                            </SLFAPageCard>
                        </div>

                    </div>
                </div>
            </section>
        </SLFAWebsiteLayout>
    );
}
