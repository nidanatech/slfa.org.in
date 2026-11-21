import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import BrandMark from '@/Components/BrandMark';

export default function AuthSplitLayout({ headTitle, eyebrow, headline, story, title, description, backHref, backLabel, children, footer }) {
    return <main className="slfa-brand brand-auth grid min-h-screen bg-[#FAF7F2] font-lato lg:grid-cols-[minmax(0,1fr)_minmax(0,0.9fr)]">
        <Head title={headTitle} />
        <div className="relative hidden min-h-screen flex-col justify-between brand-auth-story overflow-hidden bg-[#f3eddf] px-10 py-10 text-[#1f2937] lg:flex xl:px-16 xl:py-14">



            <Link href={route('home')} className="relative z-10 w-fit focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#1B7A4A]"><BrandMark /></Link>
            <div className="relative z-10 max-w-xl pb-12">
                <p className="text-xs font-normal uppercase tracking-[0.28em] text-[#1B7A4A]">{eyebrow}</p>
                <h1 className="mt-6 font-lato text-6xl font-normal leading-[1.02] xl:text-7xl">{headline}</h1>
                <div className="mt-8 h-px w-20 bg-[#1B7A4A]" aria-hidden="true" />
                <p className="mt-8 max-w-md text-lg leading-relaxed text-gray-600">{story}</p>
            </div>
            <img src="/images/hero/community.webp" alt="" width="640" height="640" className="brand-auth-illustration" />
            <p className="relative z-10 text-xs uppercase tracking-[0.17em] text-gray-400">Sri Lankan Friendship Association of South India</p>
        </div>
        <div className="flex min-h-screen flex-col px-5 py-7 sm:px-10 sm:py-10 xl:px-20 xl:py-14">
            <div className="flex items-center justify-between gap-4">
                <Link href={backHref || route('home')} className="inline-flex items-center gap-2 text-xs font-normal uppercase tracking-[0.15em] text-gray-600 transition-colors hover:text-[#1B7A4A] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#1B7A4A]"><ArrowLeft size={17} aria-hidden="true" /> {backLabel || 'Back to website'}</Link>
                <div className="lg:hidden"><BrandMark compact /></div>
            </div>
            <div className="mx-auto flex w-full max-w-[29rem] flex-1 flex-col justify-center py-14">
                <p className="text-xs font-normal uppercase tracking-[0.25em] text-[#a45116]">{eyebrow}</p>
                <h2 className="mt-4 font-lato text-5xl font-normal leading-tight text-gray-950 sm:text-6xl">{title}</h2>
                <p className="mt-3 text-base leading-relaxed text-gray-600">{description}</p>
                <div className="mt-8 h-px w-full bg-[#d8ded2]" aria-hidden="true" />
                <div className="mt-8">{children}</div>
                {footer && <div className="mt-9 text-sm text-gray-600">{footer}</div>}
            </div>
        </div>
    </main>;
}
