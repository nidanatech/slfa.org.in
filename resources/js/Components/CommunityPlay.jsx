import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { ArrowRight, Expand } from 'lucide-react';
import { useReducedMotion } from 'motion/react';
import MediaSlideshow from './MediaSlideshow';

const moments = [
    { id: 'together', path: '/images/cta/gallery27.jpg', caption: 'A night to remember', type: 'image' },
    { id: 'friends', path: '/images/cta/gallery15.jpg', caption: 'Familiar faces, new friendships', type: 'image' },
    { id: 'gather', path: '/images/cta/gallery4.jpg', caption: 'Time together, memories to keep', type: 'image' },
];
const interests = [
    { name: 'Culture & celebrations', image: '/images/carousel/kandyan-light.webp', title: 'A little rhythm. A lot of belonging.', text: 'Discover the traditions, music, and celebrations that connect Sri Lanka and South India.', link: 'Explore gatherings', destination: 'happenings' },
    { name: 'Meet people', image: '/images/cta/gallery15.jpg', title: 'Your next friendship starts with hello.', text: 'Meet people with shared roots, exchange stories, and find your place in the SLFA community.', link: 'Get to know SLFA', destination: 'about' },
    { name: 'Give back', image: '/images/themes/shared-roots.webp', title: 'Small acts. Shared possibilities.', text: 'Learn about our community support and medical camps, and contact us to explore ways to contribute.', link: 'Talk to the association', destination: 'contact' },
];

export function InterestExplorer() {
    const [selected, setSelected] = useState(0);
    const item = interests[selected];
    return <section className="cream-section cream-container interest-explorer" aria-labelledby="interests-title">
        <p className="cream-eyebrow">Follow your curiosity</p><h2 id="interests-title" className="cream-title">What brings <em>you here?</em></h2>
        <div className="interest-choices" role="group" aria-label="Choose your interest">{interests.map((interest, index) => <button type="button" key={interest.name} aria-pressed={selected === index} aria-controls="interest-content" onClick={() => setSelected(index)}><span>0{index + 1}</span>{interest.name}<ArrowRight size={18} /></button>)}</div>
        <div id="interest-content" className="interest-content" aria-live="polite">
            <img key={item.image} src={item.image} alt={selected === 2 ? '' : selected === 0 ? 'Traditional Sri Lankan dancers' : 'Members at an SLFA gathering'} loading="lazy" width="1024" height="768" />
            <div><p className="cream-eyebrow">{item.name}</p><h3>{item.title}</h3><p>{item.text}</p><Link className="cream-link" href={route(item.destination)}>{item.link}<ArrowRight size={17} /></Link></div>
        </div>
    </section>;
}

export function PhotoJournal() {
    const [slide, setSlide] = useState(null);
    return <><div className="community-journal">{moments.map((item, index) => <button type="button" key={item.id} onClick={() => setSlide(index)} aria-label={`Open ${item.caption} in slideshow`}><div className="journal-photo"><img src={item.path} alt={item.caption} loading="lazy" width="1024" height="768" /><span><Expand size={18} /> View moment</span></div><span className="journal-caption"><small>0{index + 1}</small>{item.caption}</span></button>)}</div>{slide !== null && <MediaSlideshow items={moments} initialIndex={slide} onClose={() => setSlide(null)} title="Life at SLFA" srcForItem={item => item.path} />}</>;
}

export function EventCountdown({ start }) {
    const [now, setNow] = useState(() => Date.now());
    useEffect(() => { const timer = setInterval(() => setNow(Date.now()), 60000); return () => clearInterval(timer); }, []);
    const remaining = new Date(start).getTime() - now;
    if (!Number.isFinite(remaining) || remaining <= 0) return null;
    const minutes = Math.ceil(remaining / 60000);
    return <p className="event-countdown" aria-label={`Starts in ${Math.floor(minutes / 1440)} days, ${Math.floor(minutes / 60) % 24} hours and ${minutes % 60} minutes`}><span>See you in</span>{[[Math.floor(minutes / 1440), 'days'], [Math.floor(minutes / 60) % 24, 'hrs'], [minutes % 60, 'mins']].map(([value, label]) => <span key={label}><b>{String(value).padStart(2, '0')}</b><small>{label}</small></span>)}</p>;
}

export function AdmissionCelebration() {
    const reduced = useReducedMotion();
    const [visible, setVisible] = useState(true);
    useEffect(() => { const timer = setTimeout(() => setVisible(false), 1800); return () => clearTimeout(timer); }, []);
    if (reduced || !visible) return null;
    return <span className="admission-celebration" aria-hidden="true">{Array.from({ length: 12 }, (_, index) => <i key={index} style={{ '--angle': `${index * 30}deg`, '--distance': `${45 + index % 3 * 20}px`, background: ['#1b7a4a', '#e67e22', '#d9ad55'][index % 3] }} />)}</span>;
}
