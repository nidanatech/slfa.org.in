import { Link } from '@inertiajs/react';
import { motion } from 'motion/react';
import { ArrowRight } from 'lucide-react';

const milestones = [
    {
        year: '2011',
        title: 'A community begins',
        description: 'SLFA was formed to bring together people of Sri Lankan origin living in South India.',
    },
    {
        year: '2019',
        title: 'Gathering and giving',
        description: 'Community dinners and a free health check-up brought members together.',
    },
    {
        year: '2021–2023',
        title: 'Staying connected',
        description: 'Annual general meetings helped members share the association’s progress.',
    },
    {
        year: 'Today',
        title: 'Continuing the journey',
        description: 'Cultural events and community connections remain at the heart of SLFA.',
    },
];

export default function JourneyTimeline() {
    return (
        <section className="relative overflow-hidden bg-[#0b0c10] py-20 md:py-28" aria-labelledby="journey-heading">
            <div className="absolute inset-0 bg-[url('/images/above-clouds-bg.png')] bg-cover bg-center opacity-20" aria-hidden="true" />
            <div className="absolute inset-0 bg-gradient-to-b from-[#0b0c10]/90 via-[#0b0c10]/60 to-[#0b0c10]/95" aria-hidden="true" />

            <div className="relative z-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="max-w-2xl">
                    <span className="text-xs font-bold uppercase tracking-[0.25em] text-[#D4AF37] font-lato">Our journey</span>
                    <h2 id="journey-heading" className="mt-4 text-4xl sm:text-5xl font-abhaya font-extrabold leading-tight text-white">Growing together since 2011</h2>
                    <p className="mt-4 text-base sm:text-lg leading-relaxed text-gray-300 font-lato">A few moments from SLFA’s story, from its formation to the community it serves today.</p>
                </div>

                <ol className="mt-10 grid gap-4 md:mt-12 md:grid-cols-2 xl:grid-cols-4">
                    {milestones.map((milestone, index) => (
                        <motion.li
                            key={milestone.year}
                            initial={{ opacity: 0, y: 20 }}
                            whileInView={{ opacity: 1, y: 0 }}
                            viewport={{ once: true, amount: 0.2 }}
                            transition={{ duration: 0.5, delay: index * 0.08 }}
                            className="rounded-2xl border border-white/15 border-t-2 border-t-[#D4AF37] bg-[#111319]/90 p-6 shadow-xl"
                        >
                            <span className="text-sm font-bold tracking-widest text-[#D4AF37] font-lato">{milestone.year}</span>
                            <h3 className="mt-4 text-2xl font-abhaya font-bold text-white">{milestone.title}</h3>
                            <p className="mt-3 text-sm leading-relaxed text-gray-300 font-lato">{milestone.description}</p>
                        </motion.li>
                    ))}
                </ol>

                <Link href={route('happenings')} className="mt-10 inline-flex items-center gap-2 text-sm font-bold text-[#D4AF37] hover:text-white transition-colors font-lato">
                    Explore our news and activities <ArrowRight className="h-4 w-4" aria-hidden="true" />
                </Link>
            </div>
        </section>
    );
}
