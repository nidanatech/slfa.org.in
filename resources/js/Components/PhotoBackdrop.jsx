import { useEffect, useState } from 'react';
import { AnimatePresence, motion, useReducedMotion } from 'motion/react';

export default function PhotoBackdrop({ images, className = '', position = 'center' }) {
    const [index, setIndex] = useState(0);
    const reducedMotion = useReducedMotion();

    useEffect(() => {
        if (reducedMotion || images.length < 2) return undefined;
        const timer = window.setInterval(() => setIndex((current) => (current + 1) % images.length), 6500);
        return () => window.clearInterval(timer);
    }, [images, reducedMotion]);

    return <div className={`absolute inset-0 overflow-hidden ${className}`} aria-hidden="true">
        <AnimatePresence initial={false}>
            <motion.img
                key={images[index]}
                src={images[index]}
                alt=""
                className="absolute inset-0 h-full w-full object-cover"
                style={{ objectPosition: position }}
                initial={reducedMotion ? false : { opacity: 0, scale: 1.035 }}
                animate={{ opacity: 1, scale: 1 }}
                exit={reducedMotion ? undefined : { opacity: 0 }}
                transition={{ duration: reducedMotion ? 0 : 1.5, ease: 'easeInOut' }}
            />
        </AnimatePresence>
    </div>;
}
