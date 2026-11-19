import { Head, Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import SLFAWebsiteLayout from '@/Layouts/SLFAWebsiteLayout';
import PhotoBackdrop from '@/Components/PhotoBackdrop';

const heroImages = ['/images/sigiriya-rock.jpg', '/images/kandyan-srilankandance.jpg', '/images/Sri-Lanka-Culture.webp'];
const cultureImages = ['/images/kandyan-srilankandance.jpg', '/images/barathanatyam.avif', '/images/kathakali-kerala.jpg'];

const connections = [
    ['01', 'Belonging', 'A welcoming place for people with Sri Lankan roots to meet and stay connected in South India.'],
    ['02', 'Culture', 'Gatherings and shared experiences that keep traditions present for every generation.'],
    ['03', 'Support', 'A community that looks out for one another and creates lasting relationships.'],
];

function CommitteeList({ title, members }) {
    return <div className="mt-12">
        <h3 className="border-b border-white/20 pb-4 font-lato text-xs font-bold uppercase tracking-[0.24em] text-[#D4AF37]">{title}</h3>
        {members.length ? <div className="grid gap-x-12 md:grid-cols-2">
            {members.map(member => <div key={member.id} className="flex min-w-0 items-center gap-5 border-b border-white/10 py-5">
                <div className="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-full bg-white/10 font-abhaya text-2xl text-[#D4AF37]">
                    {member.photo_path ? <img src={`/storage/${member.photo_path}`} alt="" loading="lazy" width="64" height="64" className="h-full w-full object-cover" /> : member.name?.charAt(0)}
                </div>
                <div className="min-w-0"><h4 className="font-abhaya text-xl font-bold text-white">{member.name}</h4><p className="mt-1 font-lato text-xs uppercase tracking-wider text-[#D4AF37]">{member.role}</p></div>
            </div>)}
        </div> : <p className="border-b border-white/10 py-6 text-sm text-gray-300">Profiles will appear here once published by SLFA.</p>}
    </div>;
}

export default function About({ auth, leadershipSetup = [], executiveCommittee = [] }) {
    return <SLFAWebsiteLayout user={auth.user} darkNav>
        <Head title="About SLFA" />

        <section className="relative flex min-h-[78svh] items-end overflow-hidden bg-gray-950 px-4 pb-20 pt-36 text-white sm:px-6 lg:px-8">
            <PhotoBackdrop images={heroImages} />
            <div className="absolute inset-0 bg-gradient-to-r from-gray-950 via-gray-950/75 to-gray-950/25" aria-hidden="true" />
            <div className="absolute inset-0 bg-gradient-to-t from-gray-950 via-transparent to-gray-950/40" aria-hidden="true" />
            <div className="relative mx-auto w-full max-w-7xl">
                <p className="font-lato text-xs font-bold uppercase tracking-[0.28em] text-[#D4AF37]">Sri Lankan Friendship Association of South India</p>
                <h1 className="mt-6 max-w-4xl font-abhaya text-5xl font-bold leading-[1.02] text-balance sm:text-7xl lg:text-8xl">A community built on <em className="font-light text-[#D4AF37]">connection</em></h1>
                <p className="mt-7 max-w-2xl font-lato text-lg leading-relaxed text-gray-200">We bring together people of Sri Lankan origin living in South India through friendship, cultural gatherings, and mutual support.</p>
                <div className="mt-10 flex flex-wrap gap-x-10 gap-y-5"><a href="#our-purpose" className="inline-flex items-center gap-3 border-b border-[#D4AF37] pb-2 font-lato text-xs font-bold uppercase tracking-[0.16em] text-[#D4AF37] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#D4AF37]">Discover our purpose <ArrowRight size={16} aria-hidden="true" /></a><a href="#committee" className="inline-flex items-center gap-3 border-b border-white/60 pb-2 font-lato text-xs font-bold uppercase tracking-[0.16em] text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white">Meet our committee <ArrowRight size={16} aria-hidden="true" /></a></div>
            </div>
        </section>

        <section id="our-purpose" className="scroll-mt-20 bg-[#f5f1e9] px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
            <div className="mx-auto grid max-w-7xl gap-12 lg:grid-cols-[0.85fr_1.15fr] lg:gap-24">
                <div><p className="font-lato text-xs font-bold uppercase tracking-[0.27em] text-[#9a7714]">Why we exist</p><h2 className="mt-5 font-abhaya text-4xl font-bold leading-tight text-gray-950 sm:text-6xl">Friendship with <em className="font-light text-[#800000]">purpose</em></h2></div>
                <div className="space-y-12 lg:pt-2">
                    <div className="border-t border-[#b7a676] pt-6"><span className="font-lato text-xs font-bold uppercase tracking-[0.22em] text-[#8b6915]">Our mission</span><h3 className="mt-4 font-abhaya text-3xl font-bold text-gray-950">Bring people together</h3><p className="mt-4 max-w-xl text-lg leading-relaxed text-gray-700">Create opportunities for Sri Lankans in South India to meet, preserve cultural ties, and support one another.</p></div>
                    <div className="border-t border-[#b7a676] pt-6"><span className="font-lato text-xs font-bold uppercase tracking-[0.22em] text-[#8b6915]">Our vision</span><h3 className="mt-4 font-abhaya text-3xl font-bold text-gray-950">Keep connections growing</h3><p className="mt-4 max-w-xl text-lg leading-relaxed text-gray-700">Build lasting social, cultural, and professional relationships that strengthen the community across generations.</p></div>
                </div>
            </div>
        </section>

        <section className="relative overflow-hidden bg-gray-950 px-4 py-20 text-white sm:px-6 lg:px-8 lg:py-28">
            <PhotoBackdrop images={cultureImages} position="center 38%" />
            <div className="absolute inset-0 bg-gradient-to-r from-gray-950 via-gray-950/85 to-gray-950/40" aria-hidden="true" />
            <div className="relative mx-auto max-w-7xl">
                <p className="font-lato text-xs font-bold uppercase tracking-[0.27em] text-[#D4AF37]">The ways we connect</p>
                <h2 className="mt-5 max-w-2xl font-abhaya text-4xl font-bold leading-tight sm:text-6xl">A place for everyone to <em className="font-light text-[#D4AF37]">belong</em></h2>
                <div className="mt-14 max-w-4xl border-t border-white/30">
                    {connections.map(([number, title, description]) => <div key={number} className="grid gap-2 border-b border-white/30 py-6 sm:grid-cols-[5rem_13rem_1fr] sm:items-start sm:gap-5"><span className="font-lato text-xs font-bold tracking-widest text-[#D4AF37]">{number}</span><h3 className="font-abhaya text-2xl font-bold">{title}</h3><p className="max-w-lg leading-relaxed text-gray-200">{description}</p></div>)}
                </div>
            </div>
        </section>

        <section id="committee" className="scroll-mt-20 bg-[#101723] px-4 py-20 text-white sm:px-6 lg:px-8"><div className="mx-auto max-w-7xl"><p className="font-lato text-xs font-bold uppercase tracking-[0.27em] text-[#D4AF37]">The people behind the association</p><h2 className="mt-5 font-abhaya text-4xl font-bold sm:text-6xl">Our committee</h2><CommitteeList title="Management" members={leadershipSetup} /><CommitteeList title="Executive Committee" members={executiveCommittee} /></div></section>

        <section className="relative overflow-hidden bg-gray-950 px-4 py-20 text-white sm:px-6 lg:px-8 lg:py-28"><PhotoBackdrop images={['/images/Sri-Lanka-Culture.webp', '/images/sigiriya-rock.jpg']} /><div className="absolute inset-0 bg-gray-950/75" aria-hidden="true" /><div className="relative mx-auto flex max-w-7xl flex-col items-start justify-between gap-10 lg:flex-row lg:items-end"><div><p className="font-lato text-xs font-bold uppercase tracking-[0.27em] text-[#D4AF37]">Your next chapter</p><h2 className="mt-5 max-w-2xl font-abhaya text-4xl font-bold leading-tight sm:text-6xl">Find your place in the community</h2><p className="mt-5 max-w-xl text-lg leading-relaxed text-gray-200">Explore membership, meet the community, and apply online when you are ready.</p></div><Link href={route('membership')} className="inline-flex shrink-0 items-center gap-3 border-b border-[#D4AF37] pb-2 font-lato text-sm font-bold uppercase tracking-wider text-[#D4AF37] hover:text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#D4AF37]">Explore membership <ArrowRight size={17} aria-hidden="true" /></Link></div></section>
    </SLFAWebsiteLayout>;
}
