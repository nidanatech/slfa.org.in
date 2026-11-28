import { useEffect, useRef, useState } from 'react';
import { router } from '@inertiajs/react';
import { Music2, Pause, Volume2, VolumeX } from 'lucide-react';

export default function BackgroundMusic({ initialPage }) {
    const [page, setPage] = useState(initialPage);
    const [playing, setPlaying] = useState(false);
    const [muted, setMuted] = useState(false);
    const [notice, setNotice] = useState('');
    const audio = useRef(null);
    const pausedByVisitor = useRef(false);
    const pausedForVideo = useRef(false);
    const publicPage = page.startsWith('SLFA/');

    useEffect(() => router.on('navigate', event => setPage(event.detail.page.component)), []);
    useEffect(() => {
        const player = audio.current;
        if (!player) return;
        if (!publicPage) { player.pause(); return; }
        if (pausedByVisitor.current || pausedForVideo.current) return;
        let cancelled = false;
        player.volume = 0.15;
        if (!player.getAttribute('src')) player.src = '/audio/srilankan-airlines-music.mp3';
        player.play().catch(error => {
            if (!cancelled && error.name !== 'AbortError') setNotice('Tap Play music to start.');
        });
        return () => { cancelled = true; };
    }, [publicPage]);
    useEffect(() => {
        const pauseForVideo = event => {
            if (event.target instanceof HTMLVideoElement && !event.target.muted && audio.current && !audio.current.paused) {
                pausedForVideo.current = true;
                audio.current.pause();
            }
        };
        document.addEventListener('play', pauseForVideo, true);
        return () => document.removeEventListener('play', pauseForVideo, true);
    }, []);

    const togglePlayback = async () => {
        const player = audio.current;
        if (!player) return;
        if (!player.paused) { pausedByVisitor.current = true; player.pause(); return; }
        pausedByVisitor.current = false;
        pausedForVideo.current = false;
        setNotice('');
        player.volume = 0.15;
        if (!player.getAttribute('src')) player.src = '/audio/srilankan-airlines-music.mp3';
        try { await player.play(); } catch { setNotice('Music could not be played. Please try again.'); }
    };
    const toggleMute = () => {
        if (!audio.current) return;
        audio.current.muted = !audio.current.muted;
        setMuted(audio.current.muted);
    };

    return <aside className="site-music" aria-label="Website music" hidden={!publicPage}>
        <audio ref={audio} preload="none" loop onPlay={() => setPlaying(true)} onPause={() => setPlaying(false)} onError={() => setNotice('Music could not be loaded.')} />
        <div className="site-music-controls">
            <button type="button" className="site-music-trigger" onClick={togglePlayback} aria-label={playing ? 'Pause background music' : 'Play background music'}>{playing ? <Pause size={17} aria-hidden="true" /> : <Music2 size={17} aria-hidden="true" />}{playing ? 'Pause music' : 'Play music'}</button>
            {playing && <button type="button" className="site-music-mute" onClick={toggleMute} aria-label={muted ? 'Unmute background music' : 'Mute background music'}>{muted ? <VolumeX size={18} aria-hidden="true" /> : <Volume2 size={18} aria-hidden="true" />}</button>}
        </div>
        {notice && <p className="site-music-notice" role="status">{notice}</p>}
    </aside>;
}
