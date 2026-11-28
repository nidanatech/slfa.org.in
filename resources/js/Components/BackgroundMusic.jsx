import { useEffect, useRef, useState } from 'react';
import { router } from '@inertiajs/react';
import { Music2, Volume2, VolumeX, X } from 'lucide-react';

const videoId = 'Vzbs4E9Ok2Q';

export default function BackgroundMusic({ initialPage }) {
    const [page, setPage] = useState(initialPage);
    const [open, setOpen] = useState(false);
    const [ready, setReady] = useState(false);
    const [muted, setMuted] = useState(false);
    const [notice, setNotice] = useState('');
    const player = useRef(null);
    const mount = useRef(null);
    const publicPage = page.startsWith('SLFA/');

    useEffect(() => router.on('navigate', event => setPage(event.detail.page.component)), []);
    useEffect(() => { if (!publicPage) setOpen(false); }, [publicPage]);

    useEffect(() => {
        if (!open || !publicPage) return undefined;
        let disposed = false;
        const previousReady = window.onYouTubeIframeAPIReady;
        const createPlayer = () => {
            if (disposed || !mount.current || !window.YT?.Player) return;
            const target = document.createElement('div');
            mount.current.replaceChildren(target);
            player.current = new window.YT.Player(target, {
                width: '240', height: '200', videoId,
                host: 'https://www.youtube-nocookie.com',
                playerVars: { playsinline: 1, rel: 0, loop: 1, playlist: videoId, origin: window.location.origin },
                events: {
                    onReady: event => { event.target.setVolume(15); setReady(true); event.target.playVideo(); },
                    onAutoplayBlocked: () => setNotice('Tap play in the video to start the music.'),
                    onError: () => setNotice('This video cannot be played here. You can open it on YouTube.'),
                },
            });
        };
        if (window.YT?.Player) createPlayer();
        else {
            window.onYouTubeIframeAPIReady = () => { previousReady?.(); createPlayer(); };
            if (!document.querySelector('script[data-slfa-youtube-api]')) {
                const script = document.createElement('script');
                script.src = 'https://www.youtube.com/iframe_api';
                script.dataset.slfaYoutubeApi = 'true';
                script.onerror = () => { script.remove(); if (!disposed) setNotice('Unable to load the player. You can open the track on YouTube.'); };
                document.head.appendChild(script);
            }
        }
        const pauseForVideo = event => {
            if (event.target instanceof HTMLVideoElement && !event.target.muted) player.current?.pauseVideo?.();
        };
        document.addEventListener('play', pauseForVideo, true);
        return () => {
            disposed = true;
            document.removeEventListener('play', pauseForVideo, true);
            player.current?.destroy?.();
            player.current = null;
            setReady(false);
            setMuted(false);
            if (window.onYouTubeIframeAPIReady && !window.YT?.Player) window.onYouTubeIframeAPIReady = previousReady;
        };
    }, [open, publicPage]);

    if (!publicPage) return null;
    const toggleMute = () => {
        if (!player.current || !ready) return;
        if (muted) player.current.unMute(); else player.current.mute();
        setMuted(!muted);
    };

    return <aside className="site-music" aria-label="Website music">
        {!open ? <button type="button" className="site-music-trigger" onClick={() => { setNotice(''); setOpen(true); }}><Music2 size={17} aria-hidden="true" /> Play music</button> :
            <div className="site-music-panel">
                <div className="site-music-header"><span>Music for your visit</span><div><button type="button" onClick={toggleMute} disabled={!ready} aria-label={muted ? 'Unmute music' : 'Mute music'}>{muted ? <VolumeX size={18} /> : <Volume2 size={18} />}</button><button type="button" onClick={() => setOpen(false)} aria-label="Stop music and close player"><X size={18} /></button></div></div>
                <div ref={mount} className="site-music-video" />
                {notice && <p className="site-music-notice" role="status">{notice} <a href={`https://www.youtube.com/watch?v=${videoId}`} target="_blank" rel="noopener noreferrer">Open on YouTube</a></p>}
            </div>}
    </aside>;
}
