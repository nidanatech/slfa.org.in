import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

export default function SLFAPageHero({ eyebrow, title, description, backHref, backLabel, image, images, children, compact = false }) {
    const photo = image || images?.[0];
    return <section className={`brand-page-hero ${compact ? 'brand-page-hero-compact' : ''}`}>
        <div className={`brand-page-hero-inner ${photo ? 'brand-page-hero-with-image' : ''}`}>
            <div>
                {backHref && <Link href={backHref} className="brand-back"><ArrowLeft size={16} />{backLabel}</Link>}
                {eyebrow && <p className="brand-eyebrow">{eyebrow}</p>}
                <h1>{title}</h1>
                {description && <p className="brand-hero-description">{description}</p>}
                {children && <div className="brand-hero-actions">{children}</div>}
            </div>
            {photo && <div className="brand-photo-frame brand-hero-photo"><img src={photo} alt="" width="960" height="720" /></div>}
        </div>
    </section>;
}
