import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import BrandMark from '@/Components/BrandMark';
import {
    LayoutDashboard, Users, FileText, Calendar,
    Image as ImageIcon, Upload, Menu, X, CreditCard,
    MessageSquare, Settings, LogOut, Bell, Shield
} from 'lucide-react';

export default function AdminLayout({ user, children }) {
    const [sidebarOpen, setSidebarOpen] = useState(false);

    const { url } = usePage();

    const navigation = [
        { name: 'Dashboard', href: route('admin.dashboard'), icon: LayoutDashboard, current: url === '/admin' },
        { name: 'Members', href: route('admin.members.index'), icon: Users, current: url.startsWith('/admin/members') },
        { name: 'Admins', href: route('admin.users.index'), icon: Shield, current: url.startsWith('/admin/users') },
        { name: 'News & Updates', href: route('admin.news.index'), icon: FileText, current: url.startsWith('/admin/news') },
        { name: 'Events', href: route('admin.events.index'), icon: Calendar, current: url.startsWith('/admin/events') },
        { name: 'Gallery', href: route('admin.albums.index'), icon: ImageIcon, current: url.startsWith('/admin/albums') },
        { name: 'Documents', href: route('admin.documents.index'), icon: Upload, current: url.startsWith('/admin/documents') },
        { name: 'Payments', href: route('admin.payments.index'), icon: CreditCard, current: url.startsWith('/admin/payments') },
        { name: 'Messages', href: route('admin.messages.index'), icon: MessageSquare, current: url.startsWith('/admin/messages') },
        { name: 'Committee', href: route('admin.committee.index'), icon: Settings, current: url.startsWith('/admin/committee') },
    ];

    return (
        <div className="flex min-h-screen bg-[#FAF8F4]">

            {/* Mobile sidebar backdrop */}
            {sidebarOpen && (
                <div
                    className="fixed inset-0 z-40 bg-gray-900/80 backdrop-blur-sm lg:hidden transition-opacity"
                    onClick={() => setSidebarOpen(false)}
                ></div>
            )}

            {/* Sidebar */}
            <div className={`
                fixed inset-y-0 left-0 z-50 w-64 bg-gray-950 text-white transition-transform duration-300 ease-in-out lg:static lg:translate-x-0
                ${sidebarOpen ? 'translate-x-0' : '-translate-x-full'}
            `}>
                <div className="flex h-16 items-center justify-between border-b border-white/10 bg-gray-950 px-4">
                    <div className="flex items-center gap-3">
                        <BrandMark compact />

                    </div>
                    <button className="lg:hidden text-gray-300 hover:text-white" onClick={() => setSidebarOpen(false)}>
                        <X className="w-6 h-6" />
                    </button>
                </div>

                <div className="flex flex-col h-[calc(100vh-4rem)]">
                    <nav className="flex-1 overflow-y-auto custom-scrollbar p-4 space-y-1">
                        {navigation.map((item) => (
                            <Link
                                key={item.name}
                                href={item.href}
                                className={`
                                    flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors text-sm font-medium
                                    ${item.current
                                        ? 'bg-[#D4AF37] text-gray-950'
                                        : 'text-gray-300 hover:bg-white/10 hover:text-white'
                                    }
                                `}
                            >
                                <item.icon className="w-5 h-5 shrink-0" />
                                {item.name}
                            </Link>
                        ))}
                    </nav>

                    <div className="p-4 border-t border-white/10">
                        <Link
                            href={route('logout')}
                            method="post"
                            as="button"
                            className="flex w-full items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 hover:bg-white/10 hover:text-white transition-colors text-sm font-medium"
                        >
                            <LogOut className="w-5 h-5 shrink-0" />
                            Log Out
                        </Link>
                    </div>
                </div>
            </div>

            {/* Main content wrapper */}
            <div className="flex-1 flex flex-col min-w-0 overflow-hidden">

                {/* Top header */}
                <header className="bg-white shadow-sm h-16 shrink-0 flex items-center justify-between px-4 sm:px-6 lg:px-8 z-30">
                    <button
                        className="lg:hidden text-gray-500 hover:text-gray-700"
                        onClick={() => setSidebarOpen(true)}
                    >
                        <Menu className="w-6 h-6" />
                    </button>

                    <div className="flex items-center justify-end flex-1 gap-4">
                        <Link href={route('home')} className="text-sm font-medium text-slfa-coral hover:underline hidden sm:block">
                            View Website
                        </Link>
                        <div className="h-8 w-px bg-gray-200 hidden sm:block"></div>
                        <div className="flex items-center gap-3">
                            <div className="text-right hidden sm:block">
                                <p className="text-sm font-semibold text-gray-900 leading-none">{user.name}</p>
                                <p className="text-xs text-gray-500 mt-1 capitalize">{user.role}</p>
                            </div>
                            <div className="w-9 h-9 rounded-full bg-slfa-peach text-slfa-coral flex items-center justify-center font-bold">
                                {user.name.charAt(0)}
                            </div>
                        </div>
                    </div>
                </header>

                {/* Main content area */}
                <main className="flex-1 overflow-y-auto bg-gray-50 p-4 sm:p-6 lg:p-8 custom-scrollbar">
                    {children}
                </main>
            </div>
        </div>
    );
}
