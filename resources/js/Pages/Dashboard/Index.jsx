import React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import SLFAWebsiteLayout from '@/Layouts/SLFAWebsiteLayout';
import SLFAPageHero from '@/Components/SLFAPageHero';
import { UserCircle, Calendar, FileText, Users, ArrowRight, Clock, MapPin, Image as ImageIcon } from 'lucide-react';
import dayjs from 'dayjs';

export default function Dashboard({ feed = [], upcomingEvents = [] }) {
    const { auth } = usePage().props;
    const user = auth.user;

    const renderFeedItem = (item) => {
        if (item.type === 'news') {
            return (
                <article key={`${item.type}-${item.id}`} className="border-t border-[#b7a676] py-6">
                    <div className="flex items-center gap-3 mb-4">
                        <div className="flex h-10 w-10 items-center justify-center text-[#9A7714]">
                            <FileText className="h-5 w-5 text-[#9A7714]" />
                        </div>
                        <div>
                            <h4 className="font-bold text-slfa-navy">{item.author}</h4>
                            <span className="text-xs text-gray-500 font-lato">{dayjs(item.date).format('MMMM D, YYYY')}</span>
                        </div>
                    </div>
                    {item.image_url && (
                        <div className="mb-4">
                            <img src={item.image_url} alt={item.title} className="w-full h-64 object-cover" />
                        </div>
                    )}
                    <h3 className="text-xl font-abhaya font-bold text-gray-900 mb-2">{item.title}</h3>
                    <p className="text-gray-600 font-light mb-4">{item.excerpt}</p>
                    <div className="flex items-center justify-between border-t border-[#d5c8a6] pt-4">
                        <Link href={route('news.show', item.public_id)} className="flex items-center gap-1 text-sm font-bold text-[#800000] transition-colors hover:text-gray-950">
                            Read Full Post <ArrowRight className="w-4 h-4" />
                        </Link>
                    </div>
                </article>
            );
        }

        if (item.type === 'event') {
            return (
                <article key={`${item.type}-${item.id}`} className="border-t border-[#b7a676] py-6">
                    <div className="flex items-center gap-3 mb-4">
                        <div className="flex h-10 w-10 items-center justify-center text-[#9A7714]">
                            <Calendar className="h-5 w-5 text-[#9A7714]" />
                        </div>
                        <div>
                            <h4 className="font-bold text-slfa-navy">{item.author}</h4>
                            <span className="text-xs text-gray-500 font-lato">{dayjs(item.date).format('MMMM D, YYYY')} • Event</span>
                        </div>
                    </div>
                    {item.image_url && (
                        <div className="mb-4">
                            <img src={item.image_url} alt={item.title} className="w-full h-64 object-cover" />
                        </div>
                    )}
                    <h3 className="text-xl font-abhaya font-bold text-gray-900 mb-2">{item.title}</h3>
                    <p className="text-gray-600 font-light mb-4">{item.excerpt}</p>
                    
                    <div className="mb-4 flex flex-col gap-4 border-t border-[#d5c8a6] pt-4 sm:flex-row sm:items-center">
                        <div className="flex items-center gap-2 text-sm text-gray-700">
                            <Clock className="w-4 h-4 text-gray-400" />
                            <span>{dayjs(item.date).format('h:mm A')}</span>
                        </div>
                        {item.location && (
                            <div className="flex items-center gap-2 text-sm text-gray-700">
                                <MapPin className="w-4 h-4 text-gray-400" />
                                <span>{item.location}</span>
                            </div>
                        )}
                    </div>

                    <div className="flex items-center justify-between border-t border-[#d5c8a6] pt-4">
                        <Link href={route('events.show', item.public_id)} className="flex items-center gap-1 text-sm font-bold text-[#800000] transition-colors hover:text-gray-950">
                            View Event Details <ArrowRight className="w-4 h-4" />
                        </Link>
                    </div>
                </article>
            );
        }
    };

    return (
        <SLFAWebsiteLayout user={user} darkNav={true}>
            <Head title="Member Dashboard - SLFA" />

            <SLFAPageHero eyebrow="Member space" title={<>Welcome back, <span className="italic font-light text-[#D4AF37]">{user.name}</span></>} description="Your community updates, events, and membership links in one place." />

            <section className="min-h-[40vh] bg-[#f5f1e9] py-16 sm:py-20">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {user.account_status !== 'active' && (
                        <p className="mb-6 border-l-2 border-amber-700 bg-amber-50 p-4 text-amber-900">
                            {user.account_status === 'pending'
                                ? 'Your membership application is awaiting committee review.'
                                : 'Your membership is currently inactive. Please contact SLFA for details.'}
                        </p>
                    )}
                    
                    <div className="flex flex-col lg:flex-row gap-8">
                        {/* LEFT SIDEBAR (Profile & Navigation) */}
                        <div className="w-full lg:w-1/4 flex flex-col gap-6">
                            <div className="flex flex-col items-center border-t border-[#b7a676] py-6 text-center">
                                <div className="mb-4 h-24 w-24 border border-[#b7a676] p-1">
                                    <div className="flex h-full w-full items-center justify-center overflow-hidden">
                                        <UserCircle className="w-20 h-20 text-gray-300" />
                                    </div>
                                </div>
                                <h2 className="text-xl font-abhaya font-bold text-slfa-navy">{user.name}</h2>
                                <p className="text-sm text-gray-500 font-lato capitalize">{user.profile?.membership_type || 'Member'}</p>
                                
                                <Link 
                                    href={route('profile.edit')}
                                    className="mt-6 w-full py-2 px-4 border border-gray-200 rounded-lg text-sm font-bold text-gray-600 hover:bg-gray-50 transition-colors"
                                >
                                    Edit Profile
                                </Link>
                            </div>

                            <div className="border-t border-[#b7a676]">
                                <div className="border-b border-[#d5c8a6] py-4">
                                    <h3 className="font-bold text-slfa-navy font-lato tracking-wide text-sm uppercase">Navigation</h3>
                                </div>
                                <div className="flex flex-col">
                                    {user.account_status === 'active' && <Link href={route('directory')} className="flex items-center gap-3 border-b border-[#d5c8a6] py-3 text-gray-700 transition-colors hover:text-[#800000]">
                                        <div className="flex h-8 w-8 items-center justify-center">
                                            <Users className="h-4 w-4 text-[#9A7714]" />
                                        </div>
                                        Member Directory
                                    </Link>}
                                    <Link href={route('happenings')} className="flex items-center gap-3 border-b border-[#d5c8a6] py-3 text-gray-700 transition-colors hover:text-[#800000]">
                                        <div className="flex h-8 w-8 items-center justify-center">
                                            <Calendar className="h-4 w-4 text-[#9A7714]" />
                                        </div>
                                        Events Calendar
                                    </Link>
                                    {user.account_status === 'active' && <Link href={route('documents')} className="flex items-center gap-3 border-b border-[#d5c8a6] py-3 text-gray-700 transition-colors hover:text-[#800000]">
                                        <div className="flex h-8 w-8 items-center justify-center">
                                            <FileText className="h-4 w-4 text-[#9A7714]" />
                                        </div>
                                        Document Library
                                    </Link>}
                                </div>
                            </div>
                        </div>

                        {/* MAIN FEED (News & Events Timeline) */}
                        <div className="w-full lg:w-1/2 flex flex-col">
                            
                            <div className="mb-6">
                                <h1 className="text-3xl font-abhaya font-extrabold text-slfa-navy tracking-tight">Your Community Feed</h1>
                                <p className="text-gray-500 text-sm font-lato">Stay up to date with SLFA news and events.</p>
                            </div>

                            <div className="space-y-6">
                                {feed.length > 0 ? (
                                    feed.map(item => renderFeedItem(item))
                                ) : (
                                    <div className="flex flex-col items-center border-t border-[#b7a676] py-12 text-center">
                                        <div className="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                            <FileText className="w-8 h-8 text-gray-400" />
                                        </div>
                                        <h3 className="text-lg font-bold text-slfa-navy mb-2">No updates yet</h3>
                                        <p className="text-gray-500 text-sm">Your feed is currently empty. Check back later for news and events.</p>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* RIGHT SIDEBAR (Widgets) */}
                        <div className="w-full lg:w-1/4 flex flex-col gap-6">
                            <div className="border-t border-[#b7a676]">
                                <div className="flex items-center justify-between border-b border-[#d5c8a6] py-4">
                                    <h3 className="font-bold text-slfa-navy font-lato tracking-wide text-sm uppercase">Upcoming Events</h3>
                                    <Link href={route('happenings')} className="text-xs text-slfa-primary hover:underline">View All</Link>
                                </div>
                                <div className="flex flex-col gap-4 py-4">
                                    {upcomingEvents.length > 0 ? upcomingEvents.map(event => (
                                        <div key={event.id} className="flex gap-4 items-start group">
                                            <div className="flex h-12 w-12 shrink-0 flex-col items-center justify-center border border-[#d5c8a6]">
                                                <span className="text-xs text-slfa-accent font-bold uppercase">{dayjs(event.start_at).format('MMM')}</span>
                                                <span className="text-lg font-extrabold text-slfa-navy leading-none">{dayjs(event.start_at).format('D')}</span>
                                            </div>
                                            <div>
                                                <Link href={route('events.show', event.public_id)} className="font-bold text-sm text-gray-800 group-hover:text-slfa-primary transition-colors line-clamp-2">
                                                    {event.title}
                                                </Link>
                                                <span className="text-xs text-gray-500 block mt-1">{dayjs(event.start_at).format('h:mm A')}</span>
                                            </div>
                                        </div>
                                    )) : (
                                        <div className="text-center py-4 text-sm text-gray-500">
                                            No upcoming events.
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </section>
        </SLFAWebsiteLayout>
    );
}
