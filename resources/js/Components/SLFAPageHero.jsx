import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { motion, useReducedMotion } from 'motion/react';

export default function SLFAPageHero({ eyebrow, title, description, backHref, backLabel, image, images, children, compact = false }) {
    const photo = image || images?.[0];
    const reducedMotion = useReducedMotion();

    return (
        <section className={`brand-page-hero ${compact ? 'brand-page-hero-compact' : ''}`}>
            <div className="brand-hero-ambient" aria-hidden="true" />
            <div className={`brand-page-hero-inner ${photo ? 'brand-page-hero-with-image' : ''}`}>
                <motion.div
                    initial={reducedMotion ? false : { opacity: 0, y: 18 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.65, ease: [0.22, 1, 0.36, 1] }}
                >
                    {backHref && <Link href={backHref} className="brand-back"><ArrowLeft size={16} />{backLabel}</Link>}
                    {eyebrow && <p className="brand-eyebrow">{eyebrow}</p>}
                    <h1>{title}</h1>
                    {description && <p className="brand-hero-description">{description}</p>}
                    {children && <div className="brand-hero-actions">{children}</div>}
                </motion.div>
                {photo && (
                    <motion.div
                        className="brand-photo-frame brand-hero-photo"
                        initial={reducedMotion ? false : { opacity: 0, scale: 0.97, y: 12 }}
                        animate={{ opacity: 1, scale: 1, y: 0 }}
                        transition={{ duration: 0.75, delay: 0.1, ease: [0.22, 1, 0.36, 1] }}
                    >
                        <img src={photo} alt="" width="960" height="720" />
                    </motion.div>
                )}
            </div>
        </section>
    );
}
