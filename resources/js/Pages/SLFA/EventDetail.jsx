import { Head, Link, useForm } from '@inertiajs/react';
import SLFAWebsiteLayout from '@/Layouts/SLFAWebsiteLayout';
import { Calendar, MapPin, Clock, ArrowLeft, CheckCircle, XCircle, HelpCircle } from 'lucide-react';

export default function EventDetail({ auth, event, isGoing }) {
    
    const { post, processing } = useForm({
        status: 'going'
    });

    const submitRsvp = (status) => {
        post(route('events.rsvp', event.id), {
            data: { status },
            preserveScroll: true,
        });
    };

    const isPast = new Date(event.start_at) < new Date();

    return (
        <SLFAWebsiteLayout user={auth.user}>
            <Head title={event.title} />

            {/* Event Header */}
            <div className="bg-slfa-navy text-white relative">
                {event.cover_image_path && (
                    <>
                        <div className="absolute inset-0 bg-black/60 z-10"></div>
                        <img src={`/storage/${event.cover_image_path}`} alt="" className="absolute inset-0 w-full h-full object-cover z-0 opacity-50 blur-sm" />
                    </>
                )}
                
                <div className="relative z-20 slfa-container py-12 md:py-20 animate-fade-up">
                    <Link href={route('events')} className="text-gray-300 hover:text-white transition flex items-center text-sm font-semibold mb-6">
                        <ArrowLeft className="w-4 h-4 mr-1" /> Back to Events
                    </Link>
                    <span className="inline-block bg-slfa-coral text-white text-xs font-bold px-3 py-1 rounded-full mb-4">
                        {event.event_type}
                    </span>
                    <h1 className="text-4xl md:text-5xl font-bold mb-6">{event.title}</h1>
                    
                    <div className="flex flex-col md:flex-row gap-6 text-gray-200">
                        <div className="flex items-center"><Calendar className="w-5 h-5 mr-2 text-slfa-coral" /> <span className="font-medium">{new Date(event.start_at).toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</span></div>
                        <div className="flex items-center"><Clock className="w-5 h-5 mr-2 text-slfa-coral" /> <span className="font-medium">{new Date(event.start_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span></div>
                        {event.location && <div className="flex items-center"><MapPin className="w-5 h-5 mr-2 text-slfa-coral" /> <span className="font-medium">{event.location}</span></div>}
                    </div>
                </div>
            </div>

            <div className="slfa-section bg-slfa-cream min-h-screen">
                <div className="slfa-container">
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        
                        {/* Main Content */}
                        <div className="lg:col-span-2">
                            <div className="bg-white p-8 rounded-xl shadow-sm border border-gray-100 animate-fade-up">
                                <h2 className="text-2xl font-bold text-slfa-navy mb-6">Event Details</h2>
                                
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
                                                <Link key={album.id} href={route('gallery.show', album.id)} className="block bg-slfa-peach rounded-lg overflow-hidden group hover:shadow-md transition">
                                                    <div className="h-32 bg-gray-200 relative">
                                                        {album.cover_image_path ? (
                                                            <img src={`/storage/${album.cover_image_path}`} className="w-full h-full object-cover group-hover:scale-105 transition" />
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
                            </div>
                        </div>

                        {/* Sidebar / RSVP */}
                        <div className="space-y-6 animate-fade-up-delay-1">
                            
                            {event.rsvp_enabled && !isPast && (
                                <div className="bg-white p-6 rounded-xl shadow-md border-t-4 border-slfa-coral">
                                    <h3 className="text-xl font-bold text-slfa-navy mb-4">Are you attending?</h3>
                                    
                                    {!auth.user ? (
                                        <div className="bg-gray-50 p-4 rounded-md text-center">
                                            <p className="text-sm text-gray-600 mb-3">Please log in to RSVP for this event.</p>
                                            <Link href={route('login')} className="bg-slfa-coral text-white px-4 py-2 rounded text-sm font-bold inline-block hover:bg-slfa-coral-dark">Log In</Link>
                                        </div>
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
                                                    className="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded flex items-center justify-center transition disabled:opacity-50"
                                                >
                                                    <CheckCircle className="w-4 h-4 mr-2" /> Yes, I'm Going
                                                </button>
                                                <button 
                                                    onClick={() => submitRsvp('maybe')}
                                                    disabled={processing}
                                                    className="w-full bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded flex items-center justify-center transition disabled:opacity-50"
                                                >
                                                    <HelpCircle className="w-4 h-4 mr-2" /> Maybe
                                                </button>
                                                <button 
                                                    onClick={() => submitRsvp('declined')}
                                                    disabled={processing}
                                                    className="w-full bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-4 rounded flex items-center justify-center transition disabled:opacity-50"
                                                >
                                                    <XCircle className="w-4 h-4 mr-2" /> Can't Make It
                                                </button>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            )}

                            {isPast && (
                                <div className="bg-gray-100 p-6 rounded-xl border border-gray-200 text-center">
                                    <h3 className="font-bold text-gray-600 mb-2">Event Concluded</h3>
                                    <p className="text-sm text-gray-500">This event has already taken place.</p>
                                </div>
                            )}

                            <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                                <h3 className="text-lg font-bold text-slfa-navy mb-4 border-b pb-2">Location Map</h3>
                                <div className="aspect-video bg-gray-200 rounded-lg flex items-center justify-center text-gray-400">
                                    {event.location ? (
                                        <MapPin className="w-8 h-8" />
                                    ) : (
                                        <span>No location set</span>
                                    )}
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </SLFAWebsiteLayout>
    );
}
