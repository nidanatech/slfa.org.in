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
                <div key={`${item.type}-${item.id}`} className="mb-6 rounded-3xl border border-gray-100 bg-white p-6 shadow-sm transition-shadow hover:shadow-xl">
                    <div className="flex items-center gap-3 mb-4">
                        <div className="flex h-10 w-10 items-center justify-center rounded-full bg-[#D4AF37]/15">
                            <FileText className="h-5 w-5 text-[#9A7714]" />
                        </div>
                        <div>
                            <h4 className="font-bold text-slfa-navy">{item.author}</h4>
                            <span className="text-xs text-gray-500 font-lato">{dayjs(item.date).format('MMMM D, YYYY')}</span>
                        </div>
                    </div>
                    {item.image_url && (
                        <div className="mb-4 -mx-6">
                            <img src={item.image_url} alt={item.title} className="w-full h-64 object-cover" />
                        </div>
                    )}
                    <h3 className="text-xl font-abhaya font-bold text-gray-900 mb-2">{item.title}</h3>
                    <p className="text-gray-600 font-light mb-4">{item.excerpt}</p>
                    <div className="border-t border-gray-100 pt-4 flex justify-between items-center">
                        <Link href={route('news.show', item.id)} className="flex items-center gap-1 text-sm font-bold text-[#800000] transition-colors hover:text-gray-950">
                            Read Full Post <ArrowRight className="w-4 h-4" />
                        </Link>
                    </div>
                </div>
            );
        }

        if (item.type === 'event') {
            return (
                <div key={`${item.type}-${item.id}`} className="mb-6 rounded-3xl border border-gray-100 bg-white p-6 shadow-sm transition-shadow hover:shadow-xl">
                    <div className="flex items-center gap-3 mb-4">
                        <div className="flex h-10 w-10 items-center justify-center rounded-full bg-[#D4AF37]/15">
                            <Calendar className="h-5 w-5 text-[#9A7714]" />
                        </div>
                        <div>
                            <h4 className="font-bold text-slfa-navy">{item.author}</h4>
                            <span className="text-xs text-gray-500 font-lato">{dayjs(item.date).format('MMMM D, YYYY')} • Event</span>
                        </div>
                    </div>
                    {item.image_url && (
                        <div className="mb-4 -mx-6">
                            <img src={item.image_url} alt={item.title} className="w-full h-64 object-cover" />
                        </div>
                    )}
                    <h3 className="text-xl font-abhaya font-bold text-gray-900 mb-2">{item.title}</h3>
                    <p className="text-gray-600 font-light mb-4">{item.excerpt}</p>
                    
                    <div className="bg-gray-50 rounded-xl p-4 mb-4 flex flex-col sm:flex-row sm:items-center gap-4">
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

                    <div className="border-t border-gray-100 pt-4 flex justify-between items-center">
                        <Link href={route('events.show', item.id)} className="flex items-center gap-1 text-sm font-bold text-[#800000] transition-colors hover:text-gray-950">
                            View Event Details <ArrowRight className="w-4 h-4" />
                        </Link>
                    </div>
                </div>
            );
        }
    };

    return (
        <SLFAWebsiteLayout user={user} darkNav={true}>
            <Head title="Member Dashboard - SLFA" />

            <SLFAPageHero eyebrow="Member space" title={<>Welcome back, <span className="italic font-light text-[#D4AF37]">{user.name}</span></>} description="Your community updates, events, and membership links in one place." />

            <section className="min-h-[40vh] bg-[#FAF8F4] py-16 sm:py-20">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {user.account_status !== 'active' && (
                        <p className="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-900">
                            {user.account_status === 'pending'
                                ? 'Your membership application is awaiting committee review.'
                                : 'Your membership is currently inactive. Please contact SLFA for details.'}
                        </p>
                    )}
                    
                    <div className="flex flex-col lg:flex-row gap-8">
                        {/* LEFT SIDEBAR (Profile & Navigation) */}
                        <div className="w-full lg:w-1/4 flex flex-col gap-6">
                            <div className="flex flex-col items-center rounded-3xl border border-gray-100 bg-white p-6 text-center shadow-sm">
                                <div className="w-24 h-24 bg-gradient-to-tr from-slfa-primary to-slfa-accent rounded-full p-1 mb-4">
                                    <div className="w-full h-full bg-white rounded-full flex items-center justify-center overflow-hidden">
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

                            <div className="overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-sm">
                                <div className="p-4 border-b border-gray-100 bg-gray-50">
                                    <h3 className="font-bold text-slfa-navy font-lato tracking-wide text-sm uppercase">Navigation</h3>
                                </div>
                                <div className="p-2 flex flex-col">
                                    {user.account_status === 'active' && <Link href={route('directory')} className="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 transition-colors text-gray-700 font-medium">
                                        <div className="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center">
                                            <Users className="w-4 h-4 text-blue-500" />
                                        </div>
                                        Member Directory
                                    </Link>}
                                    <Link href={route('happenings')} className="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 transition-colors text-gray-700 font-medium">
                                        <div className="w-8 h-8 rounded-full bg-purple-50 flex items-center justify-center">
                                            <Calendar className="w-4 h-4 text-purple-500" />
                                        </div>
                                        Events Calendar
                                    </Link>
                                    {user.account_status === 'active' && <Link href={route('documents')} className="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 transition-colors text-gray-700 font-medium">
                                        <div className="w-8 h-8 rounded-full bg-green-50 flex items-center justify-center">
                                            <FileText className="w-4 h-4 text-green-500" />
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
                                    <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center flex flex-col items-center">
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
                            <div className="overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-sm">
                                <div className="p-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                                    <h3 className="font-bold text-slfa-navy font-lato tracking-wide text-sm uppercase">Upcoming Events</h3>
                                    <Link href={route('happenings')} className="text-xs text-slfa-primary hover:underline">View All</Link>
                                </div>
                                <div className="p-4 flex flex-col gap-4">
                                    {upcomingEvents.length > 0 ? upcomingEvents.map(event => (
                                        <div key={event.id} className="flex gap-4 items-start group">
                                            <div className="flex flex-col items-center justify-center w-12 h-12 bg-gray-50 rounded-xl border border-gray-100 shrink-0">
                                                <span className="text-xs text-slfa-accent font-bold uppercase">{dayjs(event.start_at).format('MMM')}</span>
                                                <span className="text-lg font-extrabold text-slfa-navy leading-none">{dayjs(event.start_at).format('D')}</span>
                                            </div>
                                            <div>
                                                <Link href={route('events.show', event.id)} className="font-bold text-sm text-gray-800 group-hover:text-slfa-primary transition-colors line-clamp-2">
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
