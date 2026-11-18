import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

export default function SLFAPageHero({ eyebrow, title, description, backHref, backLabel, image, children }) {
    return (
        <section className="relative overflow-hidden bg-gray-950 pt-36 pb-20 text-white sm:pt-40 sm:pb-24">
            {image && <img src={image} alt="" className="absolute inset-0 h-full w-full object-cover opacity-25" />}
            <div className="absolute inset-0 bg-gradient-to-br from-gray-950/70 via-gray-950/85 to-[#220e13]/90" aria-hidden="true" />
            <div className="absolute -right-28 -top-32 h-96 w-96 rounded-full bg-[#D4AF37]/10 blur-3xl" aria-hidden="true" />
            <div className="absolute -bottom-40 -left-24 h-96 w-96 rounded-full bg-[#800000]/25 blur-3xl" aria-hidden="true" />
            <div className="relative z-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                {backHref && <Link href={backHref} className="mb-7 inline-flex items-center gap-2 text-sm font-bold text-gray-300 transition-colors hover:text-[#D4AF37]"><ArrowLeft className="h-4 w-4" />{backLabel}</Link>}
                {eyebrow && <p className="mb-4 text-xs font-bold uppercase tracking-[0.25em] text-[#D4AF37] font-lato">{eyebrow}</p>}
                <h1 className="max-w-4xl font-abhaya text-5xl font-extrabold leading-tight text-white sm:text-6xl lg:text-7xl">{title}</h1>
                {description && <p className="mt-5 max-w-2xl font-lato text-base leading-relaxed text-gray-300 sm:text-lg">{description}</p>}
                {children && <div className="mt-7 flex flex-wrap gap-x-6 gap-y-3 text-sm text-gray-200 font-lato">{children}</div>}
            </div>
        </section>
    );
}
