import { Head, Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { useReducedMotion } from 'motion/react';
import SLFAWebsiteLayout from '@/Layouts/SLFAWebsiteLayout';
import SLFAPageHero from '@/Components/SLFAPageHero';
import { useState } from 'react';


const connections = [
    ['01', 'Belonging', 'A welcoming place for people with Sri Lankan roots to meet and stay connected in South India.'],
    ['02', 'Culture', 'Gatherings and shared experiences that keep traditions present for every generation.'],
    ['03', 'Support', 'A community that looks out for one another and creates lasting relationships.'],
];

function CommitteePortrait({ member, columns }) {
    const [photoFailed, setPhotoFailed] = useState(false);
    const initials = member.name?.split(/\s+/).filter(Boolean).slice(0, 2).map(part => part[0]).join('').toUpperCase();

    return <article className={`group w-full max-w-[17rem] text-center sm:max-w-none sm:w-[calc(50%-0.75rem)] lg:w-[calc(33.333%-1.75rem)] ${columns === 4 ? 'xl:w-[calc(25%-2rem)]' : ''}`}>
        <div className="relative mx-auto aspect-[4/5] w-44 sm:w-48 lg:w-52">
            <span className="absolute -left-2 -top-2 h-12 w-px bg-[#1B7A4A] transition-all duration-500 group-hover:h-20" aria-hidden="true" />
            <span className="absolute -left-2 -top-2 h-px w-12 bg-[#1B7A4A] transition-all duration-500 group-hover:w-20" aria-hidden="true" />
            <span className="absolute -bottom-2 -right-2 h-16 w-px bg-[#d47a1f] transition-all duration-500 group-hover:h-24" aria-hidden="true" />
            <span className="absolute -bottom-2 -right-2 h-px w-16 bg-[#d47a1f] transition-all duration-500 group-hover:w-24" aria-hidden="true" />
            <div className="relative h-full overflow-hidden bg-[#eee9df] shadow-[0_16px_38px_-28px_rgba(31,41,55,0.55)]">
                {member.photo_path && !photoFailed
                    ? <img src={member.photo_path.startsWith('/') ? member.photo_path : `/storage/${member.photo_path}`} alt="" loading="lazy" width="208" height="260" onError={() => setPhotoFailed(true)} className="h-full w-full object-cover object-top transition-transform duration-700 ease-out group-hover:scale-[1.025]" />
                    : <div className="flex h-full w-full items-center justify-center font-lato text-6xl text-[#d6bb70]" aria-hidden="true">{initials}</div>}
            </div>
        </div>
        <h4 className="mt-5 font-lato text-[1.65rem] font-normal leading-[1.05] text-[#101723]">{member.name}</h4>
        <p className="mt-3 font-lato text-xs font-normal uppercase tracking-[0.14em] text-[#9d531b]">{member.role}</p>
        {member.company && <p className="mt-2 font-lato text-sm text-gray-600">{member.company}</p>}
        {member.designation && <p className="mt-1 font-lato text-xs text-gray-500">{member.designation}</p>}
    </article>;
}

function CommitteeList({ title, members, columns }) {
    return <div className="mt-16 border-t border-[#d8ded2] pt-10 sm:mt-20">
        <h3 className="text-center font-lato text-3xl font-normal text-[#101723] sm:text-4xl">{title}</h3>
        <div className="mx-auto mt-4 h-px w-20 bg-[#a67725]" aria-hidden="true" />
        {members.length
            ? <div className="mx-auto mt-10 flex max-w-6xl flex-wrap justify-center gap-x-6 gap-y-14 lg:gap-x-10">
                {members.map(member => <CommitteePortrait key={member.id} member={member} columns={columns} />)}
            </div>
            : <p className="mt-10 text-center text-gray-600">Profiles will appear here once published by SLFA.</p>}
    </div>;
}

export default function About({ auth, leadershipSetup = [], executiveCommittee = [] }) {
    const reducedMotion = useReducedMotion();
    const [videoFailed, setVideoFailed] = useState(false);

    return <SLFAWebsiteLayout user={auth.user} cream>
        <Head title="About SLFA" />

        <SLFAPageHero eyebrow="Meet SLFA" title={<>A community built on <em>connection.</em></>} description="We bring together people of Sri Lankan origin living in South India through friendship, cultural gatherings, and mutual support." image="/images/about-hero.jpg"><a href="#our-purpose" className="cream-link">Discover our purpose <ArrowRight size={16} /></a><a href="#committee" className="cream-link">Meet our committee <ArrowRight size={16} /></a></SLFAPageHero>

        <section id="our-purpose" className="scroll-mt-20 bg-[#FAF7F2] px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
            <div className="mx-auto grid max-w-7xl gap-12 lg:grid-cols-[0.85fr_1.15fr] lg:gap-24">
                <div><p className="font-lato text-xs font-normal uppercase tracking-[0.27em] text-[#a45116]">Why we exist</p><h2 className="mt-5 font-lato text-4xl font-normal leading-tight text-gray-950 sm:text-6xl">Friendship with <em className="font-light text-[#1B7A4A]">purpose</em></h2></div>
                <div className="space-y-12 lg:pt-2">
                    <div className="editorial-line-block border-t border-[#d8ded2] pt-6"><span className="font-lato text-xs font-normal uppercase tracking-[0.22em] text-[#a45116]">Our mission</span><h3 className="mt-4 font-lato text-3xl font-normal text-gray-950">Bring people together</h3><p className="mt-4 max-w-xl text-lg leading-relaxed text-gray-700">Create opportunities for Sri Lankans in South India to meet, preserve cultural ties, and support one another.</p></div>
                    <div className="editorial-line-block border-t border-[#d8ded2] pt-6"><span className="font-lato text-xs font-normal uppercase tracking-[0.22em] text-[#a45116]">Our vision</span><h3 className="mt-4 font-lato text-3xl font-normal text-gray-950">Keep connections growing</h3><p className="mt-4 max-w-xl text-lg leading-relaxed text-gray-700">Build lasting social, cultural, and professional relationships that strengthen the community across generations.</p></div>
                </div>
            </div>
        </section>

        <section className="relative overflow-hidden bg-[#f3eddf] px-4 py-20 text-[#1f2937] sm:px-6 lg:px-8 lg:py-28">
            {!reducedMotion && !videoFailed && (
                <video
                    autoPlay
                    muted
                    loop
                    playsInline
                    preload="metadata"
                    poster="/images/hero/srilanka-poster.webp"
                    onError={() => setVideoFailed(true)}
                    aria-hidden="true"
                    className="pointer-events-none absolute inset-0 h-full w-full object-cover opacity-55"
                >
                    <source src="/images/hero/srilanka.mp4" type="video/mp4" />
                </video>
            )}
            <div
                aria-hidden="true"
                className="pointer-events-none absolute inset-0"
                style={{ background: 'linear-gradient(105deg, rgba(243, 237, 223, 0.95) 0%, rgba(243, 237, 223, 0.86) 55%, rgba(243, 237, 223, 0.65) 100%)' }}
            />

            <div className="relative z-10 mx-auto max-w-7xl">
                <p className="font-lato text-xs font-normal uppercase tracking-[0.27em] text-[#1B7A4A]">The ways we connect</p>
                <h2 className="mt-5 max-w-2xl font-lato text-4xl font-normal leading-tight sm:text-6xl">A place for everyone to <em className="font-light text-[#1B7A4A]">belong</em></h2>
                <div className="mt-14 max-w-4xl border-t border-[#d8ded2]">
                    {connections.map(([number, title, description]) => <div key={number} className="grid gap-2 border-b border-[#d8ded2] py-6 sm:grid-cols-[5rem_13rem_1fr] sm:items-start sm:gap-5"><span className="font-lato text-xs font-normal tracking-widest text-[#1B7A4A]">{number}</span><h3 className="font-lato text-2xl font-normal">{title}</h3><p className="max-w-lg leading-relaxed text-gray-600">{description}</p></div>)}
                </div>
            </div>
        </section>

        <section id="committee" className="scroll-mt-20 bg-[#FAF7F2] px-4 py-20 sm:px-6 lg:px-8 lg:py-28"><div className="mx-auto max-w-7xl"><div className="text-center"><p className="font-lato text-xs font-normal uppercase tracking-[0.27em] text-[#a45116]">The people behind the association</p><h2 className="mt-4 font-lato text-5xl font-normal text-[#101723] sm:text-7xl">Our committee</h2><p className="mx-auto mt-4 max-w-2xl font-lato text-base leading-relaxed text-gray-600">Meet the people guiding SLFA and bringing our community together.</p></div><CommitteeList title="Management" members={leadershipSetup} columns={3} /><CommitteeList title="Executive Committee" members={executiveCommittee} columns={4} /></div></section>

        <section className="relative overflow-hidden bg-[#f3eddf] px-4 py-20 text-[#1f2937] sm:px-6 lg:px-8 lg:py-28"><div className="relative mx-auto flex max-w-7xl flex-col items-start justify-between gap-10 lg:flex-row lg:items-end"><div><p className="font-lato text-xs font-normal uppercase tracking-[0.27em] text-[#1B7A4A]">Your next chapter</p><h2 className="mt-5 max-w-2xl font-lato text-4xl font-normal leading-tight sm:text-6xl">Find your place in the community</h2><p className="mt-5 max-w-xl text-lg leading-relaxed text-gray-600">Explore membership, meet the community, and apply online when you are ready.</p></div><Link href={route('membership')} className="inline-flex shrink-0 items-center gap-3 border-b border-[#1B7A4A] pb-2 font-lato text-sm font-normal uppercase tracking-wider text-[#1B7A4A] hover:text-[#1f2937] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#1B7A4A]">Explore membership <ArrowRight size={17} aria-hidden="true" /></Link></div></section>
    </SLFAWebsiteLayout>;
}
