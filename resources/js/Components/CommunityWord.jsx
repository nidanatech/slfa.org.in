import { useEffect, useState } from 'react';
import { AnimatePresence, motion, useReducedMotion } from 'motion/react';

const words = [
    { lang: 'en', text: 'community' },
    { lang: 'si', text: 'ප්‍රජාව' },
    { lang: 'ta', text: 'சமூகம்' },
    { lang: 'te', text: 'సమాజం' },
    { lang: 'kn', text: 'ಸಮುದಾಯ' },
    { lang: 'ml', text: 'സമൂഹം' },
    { lang: 'hi', text: 'समुदाय' },
];

export default function CommunityWord({ controls = false }) {
    const [index, setIndex] = useState(0);
    const [paused, setPaused] = useState(false);
    const reducedMotion = useReducedMotion();

    useEffect(() => {
        if (reducedMotion || paused) return undefined;
        const timer = window.setInterval(() => setIndex(value => (value + 1) % words.length), 5500);
        return () => window.clearInterval(timer);
    }, [reducedMotion, paused]);

    const word = words[index];
    const names = ['English', 'Sinhala', 'Tamil', 'Telugu', 'Kannada', 'Malayalam', 'Hindi'];
    return <span className={controls ? "community-language" : undefined}><span aria-hidden="true" className="relative inline-grid align-middle text-[0.88em] leading-[1.65]">
        {/* Reserve the widest and tallest translation so the heading never jumps. */}
        {words.map(item => <span key={item.lang} lang={item.lang} className={`invisible col-start-1 row-start-1 whitespace-nowrap ${item.lang === 'en' ? 'font-abhaya' : 'font-sans'}`}>{item.text}</span>)}
        <AnimatePresence mode="wait" initial={false}>
            <motion.span key={word.lang} lang={word.lang}
                initial={reducedMotion ? false : { opacity: 0, y: 5 }}
                animate={{ opacity: 1, y: 0 }}
                exit={{ opacity: 0, y: -5 }}
                transition={{ duration: reducedMotion ? 0 : 0.35 }}
                className={`absolute inset-0 flex items-center whitespace-nowrap ${word.lang === 'en' ? 'font-abhaya' : 'font-sans'}`}
            >{word.text}</motion.span>
        </AnimatePresence>
    </span>{controls && <span className="language-controls"><label>Community in <select aria-label="Language for community" value={index} onChange={event => { setIndex(Number(event.target.value)); setPaused(true); }}>{words.map((item, position) => <option key={item.lang} value={position}>{names[position]}</option>)}</select></label>{!reducedMotion && <button type="button" onClick={() => setPaused(value => !value)}>{paused ? 'Play languages' : 'Pause languages'}</button>}</span>}</span>;
}
