import { Head, Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import SLFAWebsiteLayout from '@/Layouts/SLFAWebsiteLayout';
import SLFAPageHero from '@/Components/SLFAPageHero';

const membershipImages = ['/images/cta/gallery27.jpg', '/images/kandyan-srilankandance.jpg', '/images/cta/gallery15.jpg'];

const steps = [
    { number: '01', title: 'Tell us about yourself', description: 'Choose individual or business membership and complete the online application.' },
    { number: '02', title: 'Committee review', description: 'The SLFA Executive Committee reviews your application. You can sign in to check its status.' },
    { number: '03', title: 'Stay connected', description: 'After approval, update your profile and take part in member activities.' },
];

const questions = [
    { question: 'Who can apply for membership?', answer: 'Adults aged 18 or over who live in South India and have a connection with Sri Lanka can apply. Connections may include family, work, education or a spouse.' },
    { question: 'How is an application reviewed?', answer: 'Applications are reviewed by the SLFA Executive Committee. You can sign in to see whether your application is pending or active.' },
    { question: 'Can I update my details after applying?', answer: 'Yes. Sign in and open your profile to update your account and membership contact details.' },
    { question: 'Will other members see my contact details?', answer: 'Your directory listing is optional. Contact details are shown only when you have separately enabled contact information sharing.' },
];

export default function Membership({ auth }) {
    return (
        <SLFAWebsiteLayout user={auth.user} darkNav={true}>
            <Head title="Membership" />
            <SLFAPageHero eyebrow="Belong to SLFA" title={<>Connect, contribute, <span className="italic font-light text-[#D4AF37]">belong.</span></>} description="Join a community for Sri Lankan heritage, shared experiences, and lasting connections across South India." images={membershipImages}>
                <Link href={route('register')} className="inline-flex items-center gap-2 rounded-full bg-[#D4AF37] px-7 py-3 font-bold uppercase tracking-wider text-gray-950 transition-colors hover:bg-white">Apply for membership <ArrowRight className="h-4 w-4" /></Link>
                <Link href={route('happenings')} className="inline-flex items-center gap-2 rounded-full border border-white/30 px-7 py-3 font-bold uppercase tracking-wider text-white transition-colors hover:bg-white/10">Explore events</Link>
            </SLFAPageHero>

            <section className="bg-[#f5f1e9] py-20 sm:py-24">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="max-w-2xl">
                        <p className="text-xs font-bold uppercase tracking-[0.25em] text-[#9A7714] font-lato">Your journey</p>
                        <h2 className="mt-3 font-abhaya text-4xl font-bold text-gray-950 sm:text-5xl">Membership made clear</h2>
                        <p className="mt-4 text-lg leading-relaxed text-gray-600">The application is online, and approval is handled by the committee.</p>
                    </div>
                    <div className="mt-10 grid gap-5 md:grid-cols-3">
                        {steps.map(step => <div key={step.number} className="border-t border-[#b7a676] pt-6">
                            <span className="font-abhaya text-4xl font-bold text-[#B99425]">{step.number}</span>
                            <h3 className="mt-5 font-abhaya text-2xl font-bold text-gray-950">{step.title}</h3>
                            <p className="mt-3 leading-relaxed text-gray-600">{step.description}</p>
                        </div>)}
                    </div>
                </div>
            </section>

            <section className="bg-white py-20 sm:py-24">
                <div className="mx-auto grid max-w-7xl gap-12 px-4 sm:px-6 lg:grid-cols-[0.9fr_1.1fr] lg:gap-20 lg:px-8">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-[0.25em] text-[#9A7714] font-lato">Why join</p>
                        <h2 className="mt-3 font-abhaya text-4xl font-bold text-gray-950 sm:text-5xl">A place to connect</h2>
                        <p className="mt-5 text-lg leading-relaxed text-gray-600">SLFA brings people together through community gatherings, shared heritage, and professional relationships.</p>
                        <Link href={route('about')} className="mt-7 inline-flex items-center gap-2 font-bold text-[#800000] hover:underline">Learn about SLFA <ArrowRight className="h-4 w-4" /></Link>
                    </div>
                    <div className="grid gap-x-8 gap-y-6 sm:grid-cols-2">
                        {[
                            { title: 'Community', description: 'Meet people who share a connection with Sri Lanka.' },
                            { title: 'Gatherings', description: 'Discover cultural and association events.' },
                            { title: 'Your profile', description: 'Keep your member information up to date online.' },
                            { title: 'Your choice', description: 'Control whether you appear in the member directory.' },
                        ].map(item => <div key={item.title} className="border-t border-[#b7a676] pt-5">
                            <h3 className="font-abhaya text-xl font-bold text-gray-950">{item.title}</h3>
                            <p className="mt-2 text-sm leading-relaxed text-gray-600">{item.description}</p>
                        </div>)}
                    </div>
                </div>
            </section>

            <section className="bg-[#f5f1e9] py-20 sm:py-24">
                <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <p className="text-xs font-bold uppercase tracking-[0.25em] text-[#9A7714] font-lato">Good to know</p>
                    <h2 className="mt-3 font-abhaya text-4xl font-bold text-gray-950">Membership questions</h2>
                    <div className="mt-8 grid gap-x-12 gap-y-8 md:grid-cols-2">
                        {questions.map(item => <article key={item.question} className="border-t border-[#b7a676] pt-5">
                            <h3 className="font-abhaya text-xl font-bold text-gray-950">{item.question}</h3>
                            <p className="mt-3 leading-relaxed text-gray-600">{item.answer}</p>
                        </article>)}
                    </div>
                    <div className="mt-12 border-t border-[#b7a676] pt-8 sm:flex sm:items-center sm:justify-between sm:gap-8">
                        <div><h3 className="font-abhaya text-3xl font-bold text-gray-950">Ready to take part?</h3><p className="mt-2 text-gray-700">Start your application and follow its status online.</p></div>
                        <Link href={route('register')} className="mt-6 inline-flex shrink-0 items-center gap-2 border-b border-[#800000] pb-2 font-bold text-[#800000] sm:mt-0">Apply for membership <ArrowRight className="h-4 w-4" /></Link>
                    </div>
                </div>
            </section>
        </SLFAWebsiteLayout>
    );
}
