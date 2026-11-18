import React, { useState, useEffect } from 'react';
import { Link, Head } from '@inertiajs/react';
import SLFAWebsiteLayout from '@/Layouts/SLFAWebsiteLayout';
import { Target, Flag, ArrowRight, Download } from 'lucide-react';
import { motion, AnimatePresence } from 'motion/react';
import JourneyTimeline from '@/Components/ui/JourneyTimeline';

export default function About({ auth, leadershipSetup = [], executiveCommittee = [] }) {

    const fadeUp = {
        hidden: { opacity: 0, y: 30 },
        visible: { opacity: 1, y: 0, transition: { duration: 0.8, ease: [0.22, 1, 0.36, 1] } }
    };

    const staggerContainer = {
        hidden: { opacity: 0 },
        visible: { opacity: 1, transition: { staggerChildren: 0.15 } }
    };

    const carouselImages = [
        "/images/sigiriya-rock.jpg",
        "/images/kandyan-srilankandance.jpg",
        "/images/kalaripayuttu.webp",
        "/images/Sri-Lanka-Culture.webp",
        "/images/barathanatyam.avif",
        "/images/kathakali-kerala.jpg"
    ];
    const [currentImageIdx, setCurrentImageIdx] = useState(0);

    useEffect(() => {
        const timer = setInterval(() => {
            setCurrentImageIdx((prev) => (prev + 1) % carouselImages.length);
        }, 6000);
        return () => clearInterval(timer);
    }, []);

    return (
        <SLFAWebsiteLayout user={auth.user} darkNav={true}>
            <Head title="About Us" />

            {/* ── Carousel Hero Banner with History ── */}
            <section className="relative min-h-[800px] flex items-center justify-center pt-36 pb-28 md:pt-52 md:pb-36 bg-gray-950 overflow-hidden">
                {/* Background Carousel */}
                <div className="absolute inset-0 z-0 bg-gray-950">
                    <AnimatePresence>
                        <motion.img
                            key={currentImageIdx}
                            src={carouselImages[currentImageIdx]}
                            initial={{ opacity: 0, scale: 1.05 }}
                            animate={{ opacity: 1, scale: 1 }}
                            exit={{ opacity: 0 }}
                            transition={{ duration: 1.5, ease: "easeInOut" }}
                            className="absolute inset-0 w-full h-full object-cover"
                            alt="About SLFA"
                        />
                    </AnimatePresence>
                    {/* Dark gradient overlay for text legibility */}
                    <div className="absolute inset-0 bg-gray-950/70 mix-blend-multiply"></div>
                    <div className="absolute inset-0 bg-gradient-to-t from-gray-950 via-gray-950/40 to-transparent"></div>
                    <div className="absolute inset-0 bg-gradient-to-b from-gray-950 via-gray-950/40 to-transparent"></div>
                </div>

                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-left relative z-10 w-full">
                    <motion.div initial="hidden" animate="visible" variants={staggerContainer} className="flex flex-col items-start">
                        <motion.div variants={fadeUp} className="mb-6 flex flex-col items-start gap-4">
                            <span className="uppercase tracking-[0.4em] text-xs font-bold text-[#D4AF37] font-lato">Our Heritage</span>
                            <div className="w-16 h-[1px] bg-gradient-to-r from-[#D4AF37]/60 to-transparent" />
                        </motion.div>

                        <motion.h1 variants={fadeUp} className="text-5xl sm:text-7xl md:text-[6rem] font-abhaya font-extrabold text-white leading-[1.05] mb-8 tracking-tight max-w-4xl drop-shadow-lg">
                            About <span className="italic font-light text-[#D4AF37]">SLFA</span>
                        </motion.h1>

                        <motion.div variants={fadeUp} className="space-y-6 text-gray-300 font-lato font-light leading-relaxed text-lg md:text-xl max-w-3xl drop-shadow-md">
                            <p>The <strong className="font-bold text-white">Srilankan Friendship Association of South India (SLFA)</strong> was formed in the year 2011 to establish a cohesive community among people of Sri Lankan origin living in South India.</p>
                            <p>Our foundational goal is to promote mutual acquaintance and help foster robust business and professional relationships among Sri Lankans. In 2013, the association was officially registered under the Tamil Nadu Societies Registration Act, 1975.</p>
                            <div className="mt-8 p-6 border-l-4 border-[#D4AF37] bg-white/5 backdrop-blur-sm rounded-r-xl italic text-gray-200 text-base">
                                Currently, our registered office is located at 164/27B, Dr. P. J. Thomas Avenue, Injambakkam, Chennai-600115.
                            </div>
                        </motion.div>
                    </motion.div>
                </div>
            </section>

            {/* ── Leadership Perspectives ── */}
            <section className="py-24 md:py-32 bg-[#faf9f7] relative border-b border-gray-200">
                <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="text-center mb-24">
                        <motion.div initial={{ opacity: 0, y: 20 }} whileInView={{ opacity: 1, y: 0 }} viewport={{ once: true }}>
                            <h2 className="text-4xl md:text-5xl font-abhaya font-extrabold text-gray-900 mb-6 leading-tight">
                                Leadership Perspectives
                            </h2>
                            <div className="w-24 h-[1px] bg-[#D4AF37]/60 mx-auto" />
                        </motion.div>
                    </div>

                    <div className="flex flex-col gap-20">

                        {/* Message 1 */}
                        <motion.div
                            initial={{ opacity: 0, y: 30 }} whileInView={{ opacity: 1, y: 0 }} viewport={{ once: true }} transition={{ duration: 0.8 }}
                            className="flex flex-col md:flex-row items-center md:items-start gap-12 lg:gap-16"
                        >
                            <div className="w-48 h-48 flex-shrink-0">
                                <img src="/images/d-venkateshwaran.png" alt="Dr. D. Venkateshwaran" className="w-full h-full object-contain drop-shadow-xl hover:scale-105 transition-transform duration-500" onError={(e) => { e.target.onerror = null; e.target.src = 'https://ui-avatars.com/api/?name=DV&color=D4AF37&background=FAF8F4&size=400'; }} />
                            </div>
                            <div className="flex-1 text-center md:text-left flex flex-col justify-center">
                                <h3 className="font-abhaya font-extrabold text-3xl text-gray-900">His Excellency Dr. D. Venkateshwaran</h3>
                                <h4 className="font-lato font-bold text-[#D4AF37] tracking-widest uppercase text-sm mt-2 mb-6">Deputy High Commissioner</h4>
                                <p className="text-gray-700 font-lato font-light text-lg leading-relaxed mb-6 italic">
                                    "For over six years, our Association has stood as a vibrant pillar of people-to-people connectivity, tirelessly working to strengthen the ancient bonds between Sri Lanka and Southern India. I warmly invite you to join our community and partake in the shared spirit of friendship."
                                </p>

                            </div>
                        </motion.div>

                        <div className="w-full h-[1px] bg-gray-200" />

                        {/* Message 2 */}
                        <motion.div
                            initial={{ opacity: 0, y: 30 }} whileInView={{ opacity: 1, y: 0 }} viewport={{ once: true }} transition={{ duration: 0.8 }}
                            className="flex flex-col md:flex-row-reverse items-center md:items-start gap-12 lg:gap-16"
                        >
                            <div className="w-48 h-48 flex-shrink-0">
                                <img src="/images/bose-dharmalingam.png" alt="Mr. Bose Dharmalingam" className="w-full h-full object-contain drop-shadow-xl hover:scale-105 transition-transform duration-500" onError={(e) => { e.target.onerror = null; e.target.src = 'https://ui-avatars.com/api/?name=BD&color=D4AF37&background=FAF8F4&size=400'; }} />
                            </div>
                            <div className="flex-1 text-center md:text-right flex flex-col justify-center">
                                <h3 className="font-abhaya font-extrabold text-3xl text-gray-900">Mr. Bose Dharmalingam</h3>
                                <h4 className="font-lato font-bold text-[#D4AF37] tracking-widest uppercase text-sm mt-2 mb-6">President</h4>
                                <p className="text-gray-700 font-lato font-light text-lg leading-relaxed mb-6 italic">
                                    "Since our inception in 2016, the journey of SLFA has been one of dedication and resilience. Through our CSR efforts, we positively impact many lives, reflecting our collective compassion. Join us as we continue to build this legacy and create a source of inspiration for the chapters ahead."
                                </p>
                            </div>
                        </motion.div>

                        <div className="w-full h-[1px] bg-gray-200" />

                        {/* Message 3 */}
                        <motion.div
                            initial={{ opacity: 0, y: 30 }} whileInView={{ opacity: 1, y: 0 }} viewport={{ once: true }} transition={{ duration: 0.8 }}
                            className="flex flex-col md:flex-row items-center md:items-start gap-12 lg:gap-16"
                        >
                            <div className="w-48 h-48 flex-shrink-0">
                                <img src="/images/syed-mohamed-aminuddin.png" alt="Mr. Syed Mohamed Aminuddin" className="w-full h-full object-contain drop-shadow-xl hover:scale-105 transition-transform duration-500" onError={(e) => { e.target.onerror = null; e.target.src = 'https://ui-avatars.com/api/?name=SA&color=D4AF37&background=FAF8F4&size=400'; }} />
                            </div>
                            <div className="flex-1 text-center md:text-left flex flex-col justify-center">
                                <h3 className="font-abhaya font-extrabold text-3xl text-gray-900">Mr. Syed Mohamed Aminuddin</h3>
                                <h4 className="font-lato font-bold text-[#D4AF37] tracking-widest uppercase text-sm mt-2 mb-6">Secretary</h4>
                                <p className="text-gray-700 font-lato font-light text-lg leading-relaxed mb-6 italic">
                                    "Our association is built on a beautiful tradition that allows us to celebrate our successes and strengthen the bonds within our community. The work we do is only possible because of your unwavering support. We look forward to welcoming you to share in our warm fellowship."
                                </p>
                            </div>
                        </motion.div>

                    </div>
                </div>
            </section>


            {/* ── Journey Timeline ── */}
            <JourneyTimeline />

            {/* ── Mission & Vision ── */}
            <section className="py-28 bg-[#faf9f7]">
                <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="text-center mb-16">
                        <span className="uppercase tracking-[0.4em] text-xs font-bold text-[#D4AF37] font-lato block mb-4">Why We Exist</span>
                        <h2 className="text-4xl font-abhaya font-extrabold text-gray-900 tracking-tight">Mission &amp; Vision</h2>
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                        {[
                            {
                                icon: Target,
                                color: '#800000',
                                title: 'Our Mission',
                                text: 'To serve as a unifying platform for Sri Lankans in South India, facilitating networking, cultural preservation, and mutual assistance in times of need.'
                            },
                            {
                                icon: Flag,
                                color: '#D4AF37',
                                title: 'Our Vision',
                                text: 'Anchored in community support and integrity, we envision a future of preserved cultural heritage and enduring business and social ties across the region.'
                            }
                        ].map((item, idx) => (
                            <motion.div
                                key={idx}
                                initial={{ opacity: 0, y: 30 }} whileInView={{ opacity: 1, y: 0 }} viewport={{ once: true }} transition={{ duration: 0.7, delay: idx * 0.15 }}
                                className="group bg-white/60 backdrop-blur-sm border border-gray-100 p-10 rounded-3xl hover:shadow-xl hover:border-[#D4AF37]/30 transition-all duration-500"
                            >
                                <div className="w-16 h-16 rounded-2xl flex items-center justify-center mb-8 transition-all duration-500"
                                    style={{ background: `${item.color}18`, border: `1px solid ${item.color}30` }}>
                                    <item.icon className="w-7 h-7" style={{ color: item.color }} strokeWidth={1.5} />
                                </div>
                                <h3 className="text-2xl font-abhaya font-extrabold text-gray-900 mb-4">{item.title}</h3>
                                <p className="text-gray-500 font-lato leading-relaxed">{item.text}</p>
                            </motion.div>
                        ))}
                    </div>
                </div>
            </section>

            {/* ── Leadership / Committee ── */}
            {(leadershipSetup.length > 0 || executiveCommittee.length > 0) && (
                <section className="py-28 bg-gray-950">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="text-center mb-20">
                            <span className="uppercase tracking-[0.4em] text-xs font-bold text-[#D4AF37] font-lato block mb-4">Leadership</span>
                            <h2 className="text-5xl font-abhaya font-extrabold text-white tracking-tight">Our Committee</h2>
                        </div>

                        {leadershipSetup.length > 0 && (
                            <div className="mb-24">
                                <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-8">
                                    {leadershipSetup.map((member, idx) => (
                                        <motion.div
                                            key={member.id}
                                            initial={{ opacity: 0, y: 20 }} whileInView={{ opacity: 1, y: 0 }} viewport={{ once: true }} transition={{ delay: idx * 0.1 }}
                                            className="group text-center bg-white/[0.03] border border-white/10 hover:border-[#D4AF37]/40 p-8 rounded-3xl transition-all duration-500"
                                        >
                                            <div className="w-28 h-28 mx-auto rounded-full overflow-hidden mb-6 ring-2 ring-[#D4AF37]/30 group-hover:ring-[#D4AF37]/70 transition-all duration-500">
                                                <img
                                                    src={member.photo_path ? `/storage/${member.photo_path}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(member.name)}&color=D4AF37&background=1a1a2e`}
                                                    alt={member.name}
                                                    className="w-full h-full object-cover"
                                                />
                                            </div>
                                            <h4 className="font-abhaya font-extrabold text-xl text-white mb-1">{member.name}</h4>
                                            <p className="text-[#D4AF37] font-bold text-[10px] uppercase tracking-[0.2em] font-lato">{member.role}</p>
                                        </motion.div>
                                    ))}
                                </div>
                            </div>
                        )}

                        {executiveCommittee.length > 0 && (
                            <div>
                                <h3 className="text-xl font-abhaya font-bold text-white/40 text-center mb-12 uppercase tracking-widest">Executive Members</h3>
                                <div className="grid grid-cols-2 md:grid-cols-4 gap-y-10 gap-x-8">
                                    {executiveCommittee.map((member, idx) => (
                                        <motion.div
                                            key={member.id}
                                            initial={{ opacity: 0, y: 20 }} whileInView={{ opacity: 1, y: 0 }} viewport={{ once: true }} transition={{ delay: idx * 0.07 }}
                                            className="text-center"
                                        >
                                            <h4 className="font-abhaya font-extrabold text-lg text-white mb-1">{member.name}</h4>
                                            <p className="text-gray-500 font-bold text-[10px] uppercase tracking-[0.2em] font-lato">{member.role}</p>
                                        </motion.div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                </section>
            )}

            {/* ── CTA ── */}
            <section className="bg-gray-950 py-16 lg:py-24 text-white relative overflow-hidden">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                    <div className="flex flex-col lg:flex-row items-center justify-between gap-12">
                        {/* Text */}
                        <div className="flex-1 text-center lg:text-left">
                            <h2 className="text-4xl md:text-5xl font-abhaya font-extrabold mb-6 leading-tight drop-shadow-lg">
                                Be a part of <span className="italic font-light text-[#D4AF37]">SLFA</span>
                            </h2>
                            <p className="text-lg text-gray-300 font-abhaya font-light leading-relaxed max-w-xl mx-auto lg:mx-0 drop-shadow-md">
                                Whether you are from Sri Lanka or trace your ancestral roots to the island, we invite you to join our community. Connect, celebrate, and preserve our rich heritage together in South India.
                            </p>
                        </div>
                        {/* Buttons */}
                        <div className="flex flex-col items-center lg:items-end gap-6 w-full lg:w-auto">
                            <Link href={route('register')} className="bg-[#D4AF37] text-gray-950 hover:bg-white px-10 py-4 rounded-full font-lato text-sm uppercase tracking-widest font-bold transition-all duration-300 hover:shadow-[0_0_30px_rgba(212,175,55,0.4)] hover:-translate-y-1 text-center min-w-[280px]">
                                Apply Online Now
                            </Link>

                            <div className="flex flex-col items-center lg:items-end w-full">
                                <div className="flex items-center gap-4 text-[10px] text-gray-400 font-lato uppercase tracking-[0.2em] mb-4 w-full justify-center lg:justify-end">
                                    <span className="h-px bg-white/10 w-12 hidden lg:block"></span>
                                    <span>Or download offline forms</span>
                                    <span className="h-px bg-white/10 w-12 hidden lg:block"></span>
                                </div>
                                <div className="flex flex-col sm:flex-row gap-4 w-full sm:w-auto">
                                    <a href="/storage/legacy/Individual-Membership-form.png" download className="group bg-white/5 backdrop-blur-sm border border-white/20 text-gray-300 hover:text-white hover:bg-white/10 hover:border-white/40 px-6 py-3 rounded-full font-lato text-xs uppercase tracking-widest font-bold transition-all duration-300 flex items-center justify-center">
                                        <Download className="w-3 h-3 mr-2" /> Individual Form
                                    </a>
                                    <a href="/storage/legacy/Business-Membership-form.png" download className="group bg-white/5 backdrop-blur-sm border border-white/20 text-gray-300 hover:text-white hover:bg-white/10 hover:border-white/40 px-6 py-3 rounded-full font-lato text-xs uppercase tracking-widest font-bold transition-all duration-300 flex items-center justify-center">
                                        <Download className="w-3 h-3 mr-2" /> Business Form
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                {/* Subtle background decoration */}
                <div className="absolute inset-0 z-0 opacity-[0.03] pointer-events-none" style={{ backgroundImage: "url('/images/paper-texture.avif')", backgroundSize: 'cover' }}></div>
            </section>
        </SLFAWebsiteLayout>
    );
}
