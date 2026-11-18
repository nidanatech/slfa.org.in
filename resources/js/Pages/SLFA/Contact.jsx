import { Head, useForm } from '@inertiajs/react';
import SLFAWebsiteLayout from '@/Layouts/SLFAWebsiteLayout';
import { MapPin, Phone, Mail, Send } from 'lucide-react';
import InputError from '@/Components/InputError';
import { motion } from 'motion/react';

export default function Contact({ auth, flash }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        phone: '',
        message: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('contact.store'), { onSuccess: () => reset() });
    };

    const contactInfo = [
        {
            icon: MapPin,
            title: 'Registered Office',
            detail: <>164/27B, Dr. P. J. Thomas Avenue,<br />Injambakkam, Chennai-600115</>
        },
        {
            icon: Phone,
            title: 'Phone Number',
            detail: <a href="tel:+919884088808" className="hover:text-[#D4AF37] transition-colors">+91 98840 88808</a>
        },
        {
            icon: Mail,
            title: 'Email Address',
            detail: <a href="mailto:info@slfa.org.in" className="hover:text-[#D4AF37] transition-colors">info@slfa.org.in</a>
        }
    ];

    const inputClass = "w-full bg-[#FAF8F4] border border-gray-200 rounded-xl px-4 py-3 text-gray-800 font-lato text-sm focus:outline-none focus:ring-2 focus:ring-[#D4AF37]/40 focus:border-[#D4AF37] transition-all duration-300 placeholder-gray-400";

    return (
        <SLFAWebsiteLayout user={auth.user} darkNav={true}>
            <Head title="Contact Us" />

            {/* ── Dark Hero Banner ── */}
            <section className="relative pt-36 pb-24 bg-gray-950 overflow-hidden">
                <motion.div animate={{ scale: [1, 1.2, 1], opacity: [0.06, 0.14, 0.06] }} transition={{ duration: 12, repeat: Infinity }} className="absolute top-0 right-0 w-[500px] h-[500px] bg-[#D4AF37] rounded-full blur-[180px] pointer-events-none mix-blend-screen" />
                <motion.div animate={{ scale: [1, 1.15, 1], opacity: [0.07, 0.16, 0.07] }} transition={{ duration: 10, repeat: Infinity, delay: 1 }} className="absolute bottom-0 left-0 w-[400px] h-[400px] bg-[#800000] rounded-full blur-[150px] pointer-events-none mix-blend-screen" />

                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
                    <motion.div initial={{ opacity: 0, y: 30 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.8 }} className="flex flex-col items-center gap-5">
                        <span className="uppercase tracking-[0.4em] text-xs font-bold text-[#D4AF37] font-lato">Contact Us</span>
                        <div className="w-16 h-[1px] bg-gradient-to-r from-transparent via-[#D4AF37]/60 to-transparent" />
                        <h1 className="text-5xl sm:text-6xl md:text-7xl font-abhaya font-extrabold text-white leading-tight tracking-tight">
                            Get In <span className="italic font-light text-[#D4AF37]">Touch</span>
                        </h1>
                        <p className="text-gray-400 font-lato font-light text-lg">Reach out to the SLFA committee — we're happy to help.</p>
                    </motion.div>
                </div>
            </section>

            {/* ── Contact Body ── */}
            <div className="bg-[#FAF8F4] min-h-screen py-20">
                <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">

                        {/* ─ Info Cards ─ */}
                        <motion.div initial={{ opacity: 0, x: -30 }} whileInView={{ opacity: 1, x: 0 }} viewport={{ once: true }} transition={{ duration: 0.7 }}>
                            <span className="uppercase tracking-[0.4em] text-xs font-bold text-[#D4AF37] font-lato block mb-4">How to Reach Us</span>
                            <h2 className="text-4xl font-abhaya font-extrabold text-gray-900 mb-4">Let's Connect</h2>
                            <p className="text-gray-500 font-lato font-light leading-relaxed mb-10 text-sm">
                                Have questions about membership, upcoming events, or need assistance? Use the form or contact us directly below.
                            </p>

                            <div className="space-y-5">
                                {contactInfo.map((item, idx) => (
                                    <motion.div
                                        key={idx}
                                        initial={{ opacity: 0, y: 15 }} whileInView={{ opacity: 1, y: 0 }} viewport={{ once: true }} transition={{ delay: idx * 0.1 }}
                                        className="group flex items-start gap-5 bg-white border border-gray-100 hover:border-[#D4AF37]/30 p-6 rounded-2xl transition-all duration-400 hover:shadow-md"
                                    >
                                        <div className="w-12 h-12 rounded-xl bg-[#D4AF37]/10 border border-[#D4AF37]/20 flex items-center justify-center shrink-0 group-hover:bg-[#D4AF37]/20 transition-colors duration-300">
                                            <item.icon className="w-5 h-5 text-[#D4AF37]" strokeWidth={1.5} />
                                        </div>
                                        <div>
                                            <h3 className="font-bold text-gray-900 font-abhaya text-lg mb-1">{item.title}</h3>
                                            <p className="text-gray-500 font-lato text-sm leading-relaxed">{item.detail}</p>
                                        </div>
                                    </motion.div>
                                ))}
                            </div>
                        </motion.div>

                        {/* ─ Contact Form ─ */}
                        <motion.div initial={{ opacity: 0, x: 30 }} whileInView={{ opacity: 1, x: 0 }} viewport={{ once: true }} transition={{ duration: 0.7 }}>
                            <div className="bg-white border border-gray-100 p-8 md:p-10 rounded-3xl shadow-sm">
                                <h3 className="text-2xl font-abhaya font-bold text-gray-900 mb-2">Send Us a Message</h3>
                                <p className="text-gray-500 font-lato text-sm mb-8">Send your question to the SLFA committee.</p>

                                {flash?.success && (
                                    <div className="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-2xl text-sm font-lato">
                                        {flash.success}
                                    </div>
                                )}

                                <form onSubmit={submit} className="space-y-5">
                                    <div>
                                        <label htmlFor="name" className="block text-xs font-bold uppercase tracking-widest text-gray-500 font-lato mb-2">Your Name *</label>
                                        <input type="text" id="name" className={inputClass} placeholder="Full name" value={data.name} onChange={e => setData('name', e.target.value)} required />
                                        <InputError message={errors.name} className="mt-2" />
                                    </div>

                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <div>
                                            <label htmlFor="email" className="block text-xs font-bold uppercase tracking-widest text-gray-500 font-lato mb-2">Email *</label>
                                            <input type="email" id="email" className={inputClass} placeholder="you@example.com" value={data.email} onChange={e => setData('email', e.target.value)} required />
                                            <InputError message={errors.email} className="mt-2" />
                                        </div>
                                        <div>
                                            <label htmlFor="phone" className="block text-xs font-bold uppercase tracking-widest text-gray-500 font-lato mb-2">Phone</label>
                                            <input type="tel" id="phone" className={inputClass} placeholder="+91 00000 00000" value={data.phone} onChange={e => setData('phone', e.target.value)} />
                                            <InputError message={errors.phone} className="mt-2" />
                                        </div>
                                    </div>

                                    <div>
                                        <label htmlFor="message" className="block text-xs font-bold uppercase tracking-widest text-gray-500 font-lato mb-2">Message *</label>
                                        <textarea id="message" rows="5" className={`${inputClass} resize-none`} placeholder="How can we help you?" value={data.message} onChange={e => setData('message', e.target.value)} required></textarea>
                                        <InputError message={errors.message} className="mt-2" />
                                    </div>

                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="w-full flex items-center justify-center gap-3 bg-[#D4AF37] hover:bg-gray-900 text-gray-900 hover:text-white font-lato font-bold py-4 px-6 rounded-full transition-all duration-500 hover:shadow-[0_0_30px_rgba(212,175,55,0.3)] disabled:opacity-60 uppercase tracking-widest text-sm"
                                    >
                                        {processing ? 'Sending...' : (<>Send Message <Send className="w-4 h-4" /></>)}
                                    </button>
                                </form>
                            </div>
                        </motion.div>
                    </div>
                </div>
            </div>
        </SLFAWebsiteLayout>
    );
}
