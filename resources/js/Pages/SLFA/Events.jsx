import { Head, Link } from '@inertiajs/react';
import SLFAWebsiteLayout from '@/Layouts/SLFAWebsiteLayout';
import { Calendar, MapPin, Clock, ArrowRight } from 'lucide-react';
import { motion } from 'motion/react';

export default function Events({ auth, upcomingEvents, pastEvents }) {

    const d = (dateStr) => new Date(dateStr);

    const EventCard = ({ event, isUpcoming }) => (
        <motion.div
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.5 }}
            className={`group bg-white rounded-3xl overflow-hidden border flex flex-col md:flex-row transition-all duration-500 hover:shadow-xl ${
                isUpcoming
                    ? 'border-l-4 border-l-[#D4AF37] border-gray-100 hover:border-[#D4AF37]/40'
                    : 'border-gray-100 opacity-70 hover:opacity-90'
            }`}
        >
            {/* Date / Cover panel */}
            <div className="md:w-44 shrink-0 relative overflow-hidden">
                {event.cover_image_path ? (
                    <img src={`/storage/${event.cover_image_path}`} alt={event.title} loading="lazy" decoding="async" className={`w-full h-44 md:h-full object-cover group-hover:scale-105 transition-transform duration-700 ${!isUpcoming ? 'grayscale' : ''}`} />
                ) : (
                    <div className={`w-full h-44 md:h-full flex flex-col items-center justify-center ${isUpcoming ? 'bg-gray-950' : 'bg-gray-700'}`}>
                        <span className={`text-5xl font-abhaya font-black ${isUpcoming ? 'text-[#D4AF37]' : 'text-gray-400'}`}>{d(event.start_at).getDate()}</span>
                        <span className={`text-sm uppercase tracking-widest font-lato font-bold ${isUpcoming ? 'text-white/60' : 'text-gray-500'}`}>{d(event.start_at).toLocaleString('default', { month: 'short' })}</span>
                    </div>
                )}
                {event.event_type && (
                    <div className="absolute top-3 left-3 bg-black/60 backdrop-blur-sm text-white text-[10px] font-bold px-2 py-1 rounded-full font-lato uppercase tracking-wide">
                        {event.event_type}
                    </div>
                )}
            </div>

            {/* Content */}
            <div className="p-7 flex flex-col flex-grow">
                <h3 className="text-xl font-abhaya font-bold text-gray-900 mb-4 group-hover:text-[#800000] transition-colors leading-snug">
                    <Link href={route('events.show', event.id)}>{event.title}</Link>
                </h3>

                <div className="space-y-2 mb-5 text-sm text-gray-500 font-lato">
                    <div className="flex items-center gap-2"><Calendar className="w-4 h-4 text-[#D4AF37] shrink-0" /> {d(event.start_at).toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</div>
                    <div className="flex items-center gap-2"><Clock className="w-4 h-4 text-[#D4AF37] shrink-0" /> {d(event.start_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
                    {event.location && <div className="flex items-center gap-2"><MapPin className="w-4 h-4 text-[#D4AF37] shrink-0" /> {event.location}</div>}
                </div>

                <div className="mt-auto pt-4 border-t border-gray-100 flex justify-between items-center">
                    <Link href={route('events.show', event.id)} className="text-[#800000] font-bold font-lato text-sm inline-flex items-center gap-1 hover:gap-2 transition-all duration-200">
                        View Details <ArrowRight className="w-4 h-4" />
                    </Link>
                    {isUpcoming && event.rsvp_enabled && (
                        <span className="bg-[#800000] text-white text-[10px] font-bold px-3 py-1 rounded-full font-lato uppercase tracking-wide">RSVP Open</span>
                    )}
                </div>
            </div>
        </motion.div>
    );

    return (
        <SLFAWebsiteLayout user={auth.user} darkNav={true}>
            <Head title="Events" />

            {/* ── Dark Hero Banner ── */}
            <section className="relative pt-36 pb-24 bg-gray-950 overflow-hidden">
                <motion.div animate={{ scale: [1, 1.2, 1], opacity: [0.06, 0.15, 0.06] }} transition={{ duration: 12, repeat: Infinity }} className="absolute top-0 left-0 w-[400px] h-[400px] bg-[#FF9933] rounded-full blur-[150px] pointer-events-none mix-blend-screen" />
                <motion.div animate={{ scale: [1, 1.15, 1], opacity: [0.08, 0.2, 0.08] }} transition={{ duration: 10, repeat: Infinity, delay: 1 }} className="absolute bottom-0 right-0 w-[500px] h-[500px] bg-[#800000] rounded-full blur-[150px] pointer-events-none mix-blend-screen" />

                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
                    <motion.div initial={{ opacity: 0, y: 30 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.8 }} className="flex flex-col items-center gap-5">
                        <span className="uppercase tracking-[0.4em] text-xs font-bold text-[#D4AF37] font-lato">Upcoming Gatherings</span>
                        <div className="w-16 h-[1px] bg-gradient-to-r from-transparent via-[#D4AF37]/60 to-transparent" />
                        <h1 className="text-5xl sm:text-6xl md:text-7xl font-abhaya font-extrabold text-white leading-tight tracking-tight">
                            Our <span className="italic font-light text-[#D4AF37]">Events</span>
                        </h1>
                        <p className="text-gray-400 font-lato font-light text-lg">Join our upcoming gatherings and view past events</p>
                    </motion.div>
                </div>
            </section>

            {/* ── Events Content ── */}
            <div className="bg-[#FAF8F4] min-h-screen py-20">
                <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

                    {/* Upcoming */}
                    <div className="mb-20">
                        <div className="flex items-center gap-4 mb-10">
                            <div className="w-2 h-2 rounded-full bg-[#D4AF37]"></div>
                            <span className="uppercase tracking-[0.3em] text-xs font-bold text-[#D4AF37] font-lato">Upcoming</span>
                            <div className="flex-1 h-[1px] bg-gradient-to-r from-[#D4AF37]/30 to-transparent"></div>
                        </div>
                        <h2 className="text-4xl font-abhaya font-extrabold text-gray-900 mb-10">Upcoming Events</h2>

                        {upcomingEvents.length > 0 ? (
                            <div className="grid grid-cols-1 gap-6">
                                {upcomingEvents.map(event => <EventCard key={event.id} event={event} isUpcoming={true} />)}
                            </div>
                        ) : (
                            <div className="bg-white border border-gray-100 p-12 rounded-3xl text-center">
                                <p className="text-gray-400 font-lato">No upcoming events scheduled. Please check back soon.</p>
                            </div>
                        )}
                    </div>

                    {/* Past */}
                    <div>
                        <div className="flex items-center gap-4 mb-10">
                            <div className="w-2 h-2 rounded-full bg-gray-400"></div>
                            <span className="uppercase tracking-[0.3em] text-xs font-bold text-gray-400 font-lato">Past</span>
                            <div className="flex-1 h-[1px] bg-gradient-to-r from-gray-300/50 to-transparent"></div>
                        </div>
                        <h2 className="text-4xl font-abhaya font-extrabold text-gray-900 mb-10">Past Events</h2>

                        {pastEvents.length > 0 ? (
                            <div className="grid grid-cols-1 gap-6">
                                {pastEvents.map(event => <EventCard key={event.id} event={event} isUpcoming={false} />)}
                            </div>
                        ) : (
                            <div className="bg-white border border-gray-100 p-12 rounded-3xl text-center">
                                <p className="text-gray-400 font-lato">No past events found.</p>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </SLFAWebsiteLayout>
    );
}
