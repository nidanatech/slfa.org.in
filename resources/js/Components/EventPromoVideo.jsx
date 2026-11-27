import { useState } from 'react';
import { Play } from 'lucide-react';
import { coverImageUrl } from '@/lib/coverImageUrl';

export default function EventPromoVideo({ event }) {
    const [playing, setPlaying] = useState(false);
    if (!event?.promo_video_path) return null;
    const poster = coverImageUrl(event.promo_poster_path || event.cover_image_path);

    return <div className="event-promo">
        <div className="event-promo-screen">
            {playing ? <video controls autoPlay playsInline preload="metadata" poster={poster || undefined} aria-label={`Promotional video for ${event.title}`}>
                <source src={coverImageUrl(event.promo_video_path)} type="video/mp4" />
                Your browser does not support video playback.
            </video> : <button type="button" className="event-promo-play" onClick={() => setPlaying(true)} aria-label={`Play promotional video for ${event.title}`}>
                {poster && <img src={poster} alt="" loading="lazy" />}
                <span><Play size={18} fill="currentColor" aria-hidden="true" /> Play event video</span>
            </button>}
        </div>
        {event.promo_transcript && <details className="event-promo-transcript"><summary>Read video transcript</summary><p>{event.promo_transcript}</p></details>}
    </div>;
}
