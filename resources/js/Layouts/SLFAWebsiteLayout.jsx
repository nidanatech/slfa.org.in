import { Link } from '@inertiajs/react';
import React, { useState, useEffect } from 'react';
import { Menu, X } from 'lucide-react';

export default function SLFAWebsiteLayout({ children, user, darkNav = false }) {
    const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
    const [scrolled, setScrolled] = useState(false);

    useEffect(() => {
        const handleScroll = () => {
            setScrolled(window.scrollY > 20);
        };
        window.addEventListener('scroll', handleScroll);
        return () => window.removeEventListener('scroll', handleScroll);
    }, []);

    const navLinks = [
        { name: 'Home', href: route('home') },
        { name: 'About', href: route('about') },
        { name: 'Membership', href: route('membership') },
        { name: 'News', href: route('news') },
        { name: 'Gallery', href: route('gallery') },
        { name: 'Contact', href: route('contact') },
    ];

    // If darkNav, start transparent over dark bg; link colour starts white
    const navBg = scrolled
        ? 'bg-gray-950/95 backdrop-blur-xl shadow-[0_4px_30px_rgba(0,0,0,0.3)] py-4'
        : darkNav
            ? 'bg-transparent py-8'
            : 'bg-white/95 backdrop-blur-xl shadow-[0_4px_30px_rgba(0,0,0,0.04)] py-4';

    const linkBase = 'text-xs font-bold tracking-[0.2em] uppercase font-lato transition-all duration-300';
    const linkColor = scrolled || !darkNav
        ? 'text-gray-400 hover:text-white'
        : 'text-white/70 hover:text-white';
    const activeColor = 'text-[#D4AF37]';

    return (
        <div className="min-h-screen bg-white text-slfa-navy font-sans selection:bg-slfa-maroon selection:text-white">

            {/* Navigation */}
            <nav className={`fixed top-0 w-full z-50 transition-all duration-500 ${navBg}`}>
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex justify-between items-center">
                        {/* Logo */}
                        <div className="flex-shrink-0 flex items-center">
                            <Link href="/">
                                <img src="/storage/legacy/logo.png" alt="SLFA Logo" className="h-12 md:h-14 hover:opacity-80 transition-opacity duration-300" />
                            </Link>
                        </div>

                        {/* Desktop Menu */}
                        <div className="hidden md:flex items-center space-x-10">
                            {navLinks.map((link) => (
                                <Link
                                    key={link.name}
                                    href={link.href}
                                    className={`${linkBase} ${activeColor}`}
                                >
                                    <span className={route().current() === link.href.split('/').pop() || (link.href === route('home') && route().current('home')) ? activeColor : linkColor}>
                                        {link.name}
                                    </span>
                                </Link>
                            ))}

                            {/* Divider */}
                            <div className="h-4 w-[1px] bg-gradient-to-b from-transparent via-[#D4AF37]/40 to-transparent"></div>

                            <div className="flex items-center space-x-6">
                                {user ? (
                                    <>
                                        {(user.role === 'admin' || user.account_status === 'active') && (
                                            <Link href={route('directory')} className={`${linkBase} ${linkColor}`}>Directory</Link>
                                        )}
                                        <Link href={user?.role === 'admin' ? route('admin.dashboard') : route('dashboard')} className={`${linkBase} text-[#D4AF37] hover:text-white transition-colors`}>
                                            Dashboard
                                        </Link>
                                    </>
                                ) : (
                                    <>
                                        <Link href={route('login')} className={`${linkBase} ${linkColor}`}>
                                            Login
                                        </Link>
                                        <Link href={route('register')} className={`${linkBase} text-gray-900 bg-[#D4AF37] hover:bg-white px-6 py-3 rounded-full transition-all duration-300 hover:shadow-[0_0_20px_rgba(212,175,55,0.4)]`}>
                                            Join Now
                                        </Link>
                                    </>
                                )}
                            </div>
                        </div>

                        {/* Mobile menu button */}
                        <div className="md:hidden flex items-center">
                            <button onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)} className="text-gray-400 hover:text-white transition-colors">
                                {isMobileMenuOpen ? <X size={28} strokeWidth={1.5} /> : <Menu size={28} strokeWidth={1.5} />}
                            </button>
                        </div>
                    </div>
                </div>

                {/* Mobile Menu */}
                <div className={`md:hidden absolute top-full left-0 w-full bg-gray-950/98 backdrop-blur-xl transition-all duration-500 overflow-hidden ${isMobileMenuOpen ? 'max-h-[500px] border-t border-white/10' : 'max-h-0'}`}>
                    <div className="px-6 py-8 space-y-5">
                        {navLinks.map((link) => (
                            <Link key={link.name} href={link.href} className="block text-sm font-bold text-gray-400 uppercase tracking-widest hover:text-[#D4AF37] transition-colors font-lato">
                                {link.name}
                            </Link>
                        ))}
                        <div className="border-t border-white/10 pt-5 space-y-4">
                            {user ? (
                                <>
                                    {(user.role === 'admin' || user.account_status === 'active') && (
                                        <Link href={route('directory')} className="block text-sm font-bold text-gray-400 uppercase tracking-widest hover:text-[#D4AF37] font-lato">Directory</Link>
                                    )}
                                    <Link href={user?.role === 'admin' ? route('admin.dashboard') : route('dashboard')} className="block text-sm font-bold text-[#D4AF37] uppercase tracking-widest font-lato">Dashboard</Link>
                                </>
                            ) : (
                                <>
                                    <Link href={route('login')} className="block text-sm font-bold text-gray-400 uppercase tracking-widest hover:text-[#D4AF37] font-lato">Login</Link>
                                    <Link href={route('register')} className="inline-block text-sm font-bold text-gray-900 bg-[#D4AF37] uppercase tracking-widest px-6 py-3 rounded-full font-lato">Join Now</Link>
                                </>
                            )}
                        </div>
                    </div>
                </div>
            </nav>

            {/* Main Content */}
            <main>
                {children}
            </main>

            {/* Dark Footer */}
            <footer className="bg-gray-950 border-t border-[#D4AF37]/20 pt-24 pb-12 relative overflow-hidden">
                {/* Subtle rangoli background and gradient for footer */}
                <div className="absolute inset-0 rangoli-bg opacity-30 pointer-events-none"></div>
                <div className="absolute bottom-0 right-0 w-[400px] h-[400px] bg-gradient-to-tl from-[#D4AF37]/5 to-transparent rounded-full blur-3xl pointer-events-none"></div>
                
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                    <div className="grid grid-cols-1 md:grid-cols-12 gap-16 mb-20">

                        <div className="md:col-span-5">
                            <Link href="/" className="inline-block mb-8">
                                <img src="/storage/legacy/logo.png" alt="SLFA Logo" className="h-16 hover:opacity-80 transition-opacity duration-300" />
                            </Link>
                            <p className="text-gray-300 font-abhaya font-light text-xl leading-relaxed mb-8 pr-8">
                                The official forum congregating people of Sri Lankan origin residing in South India for mutual acquaintance, cultural preservation, and professional relationships across borders.
                            </p>
                            <div className="text-xs uppercase tracking-[0.2em] text-gray-500 font-bold font-lato">Registration No: 234/2019</div>
                        </div>

                        <div className="md:col-span-2 md:col-start-7">
                            <h4 className="text-xs font-bold uppercase tracking-[0.2em] text-[#D4AF37] mb-8 font-lato">Navigation</h4>
                            <ul className="space-y-4">
                                {navLinks.map(link => (
                                    <li key={link.name}>
                                        <Link href={link.href} className="text-gray-400 hover:text-white font-abhaya font-light text-xl transition-colors">
                                            {link.name}
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </div>

                        <div className="md:col-span-4">
                            <h4 className="text-xs font-bold uppercase tracking-[0.2em] text-[#D4AF37] mb-8 font-lato">Connect With Us</h4>
                            <ul className="space-y-4 text-gray-400 font-abhaya font-light text-xl">
                                <li className="leading-relaxed">164/27B, Dr. P. J. Thomas Avenue,<br />Injambakkam, Chennai-600115</li>
                                <li><a href="tel:+919884088808" className="hover:text-[#D4AF37] transition-colors">+91 98840 88808</a></li>
                                <li><a href="mailto:info@slfa.org.in" className="hover:text-[#D4AF37] transition-colors">info@slfa.org.in</a></li>
                            </ul>
                        </div>
                    </div>

                    <div className="border-t border-[#D4AF37]/20 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
                        <p className="text-[10px] text-gray-500 font-lato uppercase tracking-[0.3em]">
                            &copy; {new Date().getFullYear()} SLFA. All rights reserved.
                        </p>
                        <div className="flex space-x-8 text-[10px] text-gray-500 font-lato uppercase tracking-[0.3em]">
                            <Link href="#" className="hover:text-[#D4AF37] transition-colors">Privacy</Link>
                            <Link href="#" className="hover:text-[#D4AF37] transition-colors">Terms</Link>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    );
}
