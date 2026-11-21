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

export default function CommunityWord() {
    const [index, setIndex] = useState(0);
    const reducedMotion = useReducedMotion();

    useEffect(() => {
        if (reducedMotion) return undefined;
        const timer = window.setInterval(() => setIndex(value => (value + 1) % words.length), 5500);
        return () => window.clearInterval(timer);
    }, [reducedMotion]);

    const word = words[reducedMotion ? 0 : index];
    return <span aria-hidden="true" className="relative inline-grid align-middle text-[0.88em] leading-[1.65]">
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
    </span>;
}
