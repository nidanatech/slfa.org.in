import { Link } from '@inertiajs/react';
import React, { useState, useEffect } from 'react';
import { Menu, X } from 'lucide-react';
import BrandMark from '@/Components/BrandMark';

export default function SLFAWebsiteLayout({ children, user, darkNav = false, lightOnScroll = false, coastal = false }) {
    const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
    const [scrolled, setScrolled] = useState(false);

    useEffect(() => {
        const handleScroll = () => {
            setScrolled(window.scrollY > 20);
        };
        window.addEventListener('scroll', handleScroll, { passive: true });
        return () => window.removeEventListener('scroll', handleScroll);
    }, []);

    const navLinks = [
        { name: 'Home', href: route('home') },
        { name: 'About', href: route('about') },
        { name: 'Membership', href: route('membership') },
        { name: 'News & Events', href: route('happenings') },
        { name: 'Contact', href: route('contact') },
    ];

    // If darkNav, start transparent over dark bg; link colour starts white
    const lightNav = (scrolled && lightOnScroll) || (!darkNav && !scrolled);
    const navBg = scrolled
        ? lightOnScroll ? 'bg-[#f8fbfc]/95 backdrop-blur-xl shadow-[0_4px_24px_rgba(10,50,75,0.1)] py-4' : 'bg-gray-950/95 backdrop-blur-xl shadow-[0_4px_30px_rgba(0,0,0,0.3)] py-4'
        : darkNav
            ? 'bg-transparent py-8'
            : 'bg-white/95 backdrop-blur-xl shadow-[0_4px_30px_rgba(0,0,0,0.04)] py-4';

    const linkBase = 'text-xs font-bold tracking-[0.2em] uppercase font-lato transition-all duration-300';
    const linkColor = lightNav ? 'text-[#27465d] hover:text-[#08618c]' : 'text-white/75 hover:text-white';
    const activeColor = lightNav ? 'text-[#08618c]' : 'text-[#D4AF37]';

    return (
        <div className="min-h-screen bg-white text-slfa-navy font-sans selection:bg-slfa-maroon selection:text-white">
            <a href="#main-content" className="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[60] focus:rounded-full focus:bg-[#D4AF37] focus:px-5 focus:py-3 focus:font-bold focus:text-gray-950">Skip to content</a>

            {/* Navigation */}
            <nav className={`fixed top-0 w-full z-50 transition-all duration-500 ${navBg}`}>
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex justify-between items-center">
                        {/* Logo */}
                        <div className="flex-shrink-0 flex items-center">
                            <Link href="/" aria-label="SLFA home" className="rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#D4AF37]">
                                <BrandMark lightText={lightNav} />
                            </Link>
                        </div>

                        {/* Desktop Menu */}
                        <div className="hidden xl:flex items-center space-x-6">
                            {navLinks.map((link) => (
                                <Link
                                    key={link.name}
                                    href={link.href}
                                    className={`${linkBase} ${activeColor} rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#D4AF37]`}
                                >
                                    <span className={route().current(link.href === route('happenings') ? 'happenings' : link.href === route('home') ? 'home' : link.href.split('/').pop()) ? activeColor : linkColor}>
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
                                        <Link href={user?.role === 'admin' ? route('admin.dashboard') : route('dashboard')} className={`${linkBase} ${activeColor} transition-colors`}>
                                            Dashboard
                                        </Link>
                                    </>
                                ) : (
                                    <>
                                        <Link href={route('login')} className={`${linkBase} ${linkColor}`}>
                                            Login
                                        </Link>
                                        <Link href={route('register')} className={`${linkBase} ${lightNav ? 'bg-[#0d5a83] text-white hover:bg-[#083b5b]' : 'bg-[#D4AF37] text-gray-900 hover:bg-white'} px-5 py-3 rounded-full transition-all duration-300`}>
                                            Apply for membership
                                        </Link>
                                    </>
                                )}
                            </div>
                        </div>

                        {/* Mobile menu button */}
                        <div className="xl:hidden flex items-center">
                            <button onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)} aria-label={isMobileMenuOpen ? 'Close menu' : 'Open menu'} aria-expanded={isMobileMenuOpen} className={`${lightNav ? 'text-[#27465d] hover:text-[#08618c]' : 'text-white/80 hover:text-white'} transition-colors`}>
                                {isMobileMenuOpen ? <X size={28} strokeWidth={1.5} /> : <Menu size={28} strokeWidth={1.5} />}
                            </button>
                        </div>
                    </div>
                </div>

                {/* Mobile Menu */}
                <div className={`xl:hidden absolute top-full left-0 w-full backdrop-blur-xl transition-all duration-500 overflow-hidden ${lightNav ? 'bg-[#f8fbfc]/98' : 'bg-gray-950/98'} ${isMobileMenuOpen ? 'max-h-[500px] border-t border-white/10' : 'max-h-0'}`}>
                    <div className="px-6 py-8 space-y-5">
                        {navLinks.map((link) => (
                            <Link key={link.name} href={link.href} onClick={() => setIsMobileMenuOpen(false)} className={`block text-sm font-bold uppercase tracking-widest transition-colors font-lato focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#D4AF37] ${linkColor}`}>
                                {link.name}
                            </Link>
                        ))}
                        <div className="border-t border-white/10 pt-5 space-y-4">
                            {user ? (
                                <>
                                    {(user.role === 'admin' || user.account_status === 'active') && (
                                        <Link href={route('directory')} className={`block text-sm font-bold uppercase tracking-widest font-lato ${linkColor}`}>Directory</Link>
                                    )}
                                    <Link href={user?.role === 'admin' ? route('admin.dashboard') : route('dashboard')} className={`block text-sm font-bold uppercase tracking-widest font-lato ${activeColor}`}>Dashboard</Link>
                                </>
                            ) : (
                                <>
                                    <Link href={route('login')} className={`block text-sm font-bold uppercase tracking-widest font-lato ${linkColor}`}>Login</Link>
                                    <Link href={route('register')} className={`inline-block text-sm font-bold uppercase tracking-wider px-6 py-3 rounded-full font-lato ${lightNav ? 'bg-[#0d5a83] text-white' : 'bg-[#D4AF37] text-gray-900'}`}>Apply for membership</Link>
                                </>
                            )}
                        </div>
                    </div>
                </div>
            </nav>

            {/* Main Content */}
            <main id="main-content">
                {children}
            </main>

            {/* Dark Footer */}
            <footer className={`${coastal ? 'bg-[#103f57] pt-16 pb-8 [&_.text-gray-400]:text-slate-200 [&_.text-gray-500]:text-slate-300' : 'bg-gray-950 pt-24 pb-12'} border-t border-[#D4AF37]/20 relative overflow-hidden`}>
                {/* Subtle rangoli background and gradient for footer */}
                <div className="absolute inset-0 rangoli-bg opacity-30 pointer-events-none"></div>
                <div className="absolute bottom-0 right-0 w-[400px] h-[400px] bg-gradient-to-tl from-[#D4AF37]/5 to-transparent rounded-full blur-3xl pointer-events-none"></div>
                
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                    <div className={`grid grid-cols-1 md:grid-cols-12 ${coastal ? 'gap-10 mb-12' : 'gap-16 mb-20'}`}>

                        <div className="md:col-span-5">
                            <Link href="/" aria-label="SLFA home" className="inline-block mb-8">
                                <BrandMark />
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
                                <li><a href="https://www.facebook.com/slfaorg" target="_blank" rel="noopener noreferrer" className="hover:text-[#D4AF37] transition-colors">Facebook</a></li>
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
