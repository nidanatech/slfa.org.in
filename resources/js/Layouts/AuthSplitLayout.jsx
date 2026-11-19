import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import BrandMark from '@/Components/BrandMark';

export default function AuthSplitLayout({ headTitle, eyebrow, headline, story, title, description, backHref, backLabel, children, footer }) {
    return <main className="grid min-h-screen bg-[#f5f1e9] font-lato lg:grid-cols-[minmax(0,1fr)_minmax(0,0.9fr)]">
        <Head title={headTitle} />
        <div className="relative hidden min-h-screen flex-col justify-between overflow-hidden bg-gray-950 px-10 py-10 text-white lg:flex xl:px-16 xl:py-14">
            <img src="/images/sigiriya-rock.jpg" alt="" className="absolute inset-0 h-full w-full object-cover object-center opacity-45" />
            <div className="absolute inset-0 bg-gradient-to-r from-gray-950/85 via-gray-950/65 to-gray-950/40" aria-hidden="true" />
            <div className="absolute inset-0 bg-gradient-to-t from-gray-950 via-transparent to-gray-950/60" aria-hidden="true" />
            <Link href={route('home')} className="relative z-10 w-fit focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#D4AF37]"><BrandMark /></Link>
            <div className="relative z-10 max-w-xl pb-12">
                <p className="text-xs font-bold uppercase tracking-[0.28em] text-[#D4AF37]">{eyebrow}</p>
                <h1 className="mt-6 font-abhaya text-6xl font-bold leading-[1.02] xl:text-7xl">{headline}</h1>
                <div className="mt-8 h-px w-20 bg-[#D4AF37]" aria-hidden="true" />
                <p className="mt-8 max-w-md text-lg leading-relaxed text-gray-200">{story}</p>
            </div>
            <p className="relative z-10 text-xs uppercase tracking-[0.17em] text-gray-400">Sri Lankan Friendship Association of South India</p>
        </div>
        <div className="flex min-h-screen flex-col px-5 py-7 sm:px-10 sm:py-10 xl:px-20 xl:py-14">
            <div className="flex items-center justify-between gap-4">
                <Link href={backHref || route('home')} className="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-[0.15em] text-gray-600 transition-colors hover:text-[#800000] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#800000]"><ArrowLeft size={17} aria-hidden="true" /> {backLabel || 'Back to website'}</Link>
                <div className="lg:hidden"><BrandMark compact /></div>
            </div>
            <div className="mx-auto flex w-full max-w-[29rem] flex-1 flex-col justify-center py-14">
                <p className="text-xs font-bold uppercase tracking-[0.25em] text-[#9a7714]">{eyebrow}</p>
                <h2 className="mt-4 font-abhaya text-5xl font-bold leading-tight text-gray-950 sm:text-6xl">{title}</h2>
                <p className="mt-3 text-base leading-relaxed text-gray-600">{description}</p>
                <div className="mt-8 h-px w-full bg-[#cbbb93]" aria-hidden="true" />
                <div className="mt-8">{children}</div>
                {footer && <div className="mt-9 text-sm text-gray-600">{footer}</div>}
            </div>
        </div>
    </main>;
}
