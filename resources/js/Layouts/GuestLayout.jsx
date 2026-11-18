import { useState, useEffect } from 'react';
import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    const [deferredPrompt, setDeferredPrompt] = useState(null);
    const [showInstallBanner, setShowInstallBanner] = useState(false);

    useEffect(() => {
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            setDeferredPrompt(e);
            setShowInstallBanner(true);
        });

        window.addEventListener('appinstalled', () => {
            setShowInstallBanner(false);
        });
    }, []);

    const handleInstallClick = async () => {
        if (!deferredPrompt) return;
        deferredPrompt.prompt();
        const { outcome } = await deferredPrompt.userChoice;
        if (outcome === 'accepted') setShowInstallBanner(false);
        setDeferredPrompt(null);
    };
    return (
        <div className="flex min-h-screen flex-col items-center bg-gray-100 pt-4 sm:justify-center sm:pt-0 dark:bg-gray-900">
            {showInstallBanner && (
                <div className="fixed top-0 left-0 right-0 z-[100] bg-indigo-600 text-white p-3 flex items-center justify-between shadow-lg">
                    <div className="flex items-center gap-2">
                        <div className="bg-white/20 p-1.5 rounded-lg">
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        </div>
                        <span className="text-xs font-bold">Try our mobile app!</span>
                    </div>
                    <button
                        onClick={handleInstallClick}
                        className="bg-white text-indigo-600 px-3 py-1 rounded-full text-[10px] font-bold"
                    >
                        Install
                    </button>
                </div>
            )}
            <div>
                <Link href="/">
                    <ApplicationLogo isLight={true} text="SLFA" className="h-20 w-auto" />
                </Link>
            </div>

            <div className="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg dark:bg-gray-800">
                {children}
            </div>
        </div>
    );
}
