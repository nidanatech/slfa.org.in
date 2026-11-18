import { Head, Link, useForm } from '@inertiajs/react';
import SLFAWebsiteLayout from '@/Layouts/SLFAWebsiteLayout';
import InputError from '@/Components/InputError';
import CanvasFlag from '@/Components/CanvasFlag';
import { CheckCircle2, ArrowRight } from 'lucide-react';
import { motion } from 'motion/react';

const benefits = [
    'Access to exclusive members-only directory',
    'Priority registration for SLFA events & AGMs',
    'Business networking across South India',
    'Cultural preservation & community support',
    'Annual health camps & welfare programs',
];

const inputClass = "w-full bg-[#FAF8F4] border border-gray-200 rounded-xl px-4 py-3 text-gray-800 font-lato text-sm focus:outline-none focus:ring-2 focus:ring-[#D4AF37]/40 focus:border-[#D4AF37] transition-all duration-300 placeholder-gray-400";
const labelClass = "block text-xs font-bold uppercase tracking-widest text-gray-500 font-lato mb-2";
const sectionHeadClass = "flex items-center gap-3 mb-6";

export default function Join({ auth }) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        full_name: '',
        phone: '',
        membership_type: 'individual',
        sri_lanka_connection: '',
        occupation: '',
        company_name: '',
        designation: '',
        city: '',
        date_of_birth: '',
        directory_consent: true,
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('join.store'));
    };

    return (
        <SLFAWebsiteLayout user={auth.user} darkNav={true}>
            <Head title="Become a Member" />

            <div className="min-h-screen flex flex-col lg:flex-row">

                {/* ─── Left Dark Panel ─── */}
                <div className="lg:w-[42%] bg-gray-950 relative flex flex-col justify-between p-10 md:p-16 pt-32 lg:pt-36 overflow-hidden">
                    {/* Ambient orbs */}
                    <motion.div animate={{ scale: [1, 1.2, 1], opacity: [0.05, 0.15, 0.05] }} transition={{ duration: 12, repeat: Infinity }} className="absolute top-0 left-0 w-[400px] h-[400px] bg-[#FF9933] rounded-full blur-[150px] pointer-events-none mix-blend-screen" />
                    <motion.div animate={{ scale: [1, 1.15, 1], opacity: [0.08, 0.18, 0.08] }} transition={{ duration: 10, repeat: Infinity, delay: 1 }} className="absolute bottom-0 right-0 w-[500px] h-[500px] bg-[#800000] rounded-full blur-[150px] pointer-events-none mix-blend-screen" />

                    <div className="relative z-10">
                        {/* Eyebrow */}
                        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.7 }} className="flex flex-col gap-4 mb-10">
                            <span className="uppercase tracking-[0.4em] text-xs font-bold text-[#D4AF37] font-lato">Join Our Community</span>
                            <div className="w-12 h-[1px] bg-gradient-to-r from-[#D4AF37]/60 to-transparent" />
                        </motion.div>

                        <motion.h1 initial={{ opacity: 0, y: 25 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.8, delay: 0.1 }} className="text-4xl sm:text-5xl font-abhaya font-extrabold text-white leading-tight mb-6 tracking-tight">
                            Become Part of the <span className="text-[#D4AF37] italic font-light">SLFA Family</span>
                        </motion.h1>

                        <motion.p initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.7, delay: 0.2 }} className="text-gray-400 font-lato font-light leading-relaxed mb-10 text-sm">
                            Connect with hundreds of Sri Lankan families across South India. Your application will be reviewed by our committee.
                        </motion.p>

                        {/* Benefits */}
                        <motion.ul initial={{ opacity: 0 }} animate={{ opacity: 1 }} transition={{ duration: 0.7, delay: 0.3 }} className="space-y-4 mb-12">
                            {benefits.map((benefit, i) => (
                                <motion.li key={i} initial={{ opacity: 0, x: -10 }} animate={{ opacity: 1, x: 0 }} transition={{ delay: 0.3 + i * 0.08 }} className="flex items-start gap-3 text-gray-300 font-lato text-sm">
                                    <CheckCircle2 className="w-5 h-5 text-[#D4AF37] shrink-0 mt-0.5" strokeWidth={1.5} />
                                    {benefit}
                                </motion.li>
                            ))}
                        </motion.ul>
                    </div>

                    {/* Waving flag at bottom */}
                    <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} transition={{ duration: 1, delay: 0.6 }} className="relative z-10 mt-6 opacity-80">
                        <CanvasFlag src="/images/slfa-bg.png" />
                    </motion.div>
                </div>

                {/* ─── Right Form Panel ─── */}
                <div className="lg:w-[58%] bg-[#FAF8F4] py-20 pt-24 lg:pt-32 px-8 md:px-14 lg:px-16 overflow-y-auto">
                    <div className="max-w-2xl mx-auto">
                        <h2 className="text-3xl font-abhaya font-extrabold text-gray-900 mb-2">Membership Application</h2>
                        <p className="text-gray-400 font-lato text-sm mb-10">Please fill in your details below. All fields marked * are required.</p>

                        <form onSubmit={submit} className="space-y-10">

                            {/* ① Account Info */}
                            <div>
                                <div className={sectionHeadClass}>
                                    <div className="w-8 h-8 rounded-full bg-[#D4AF37] flex items-center justify-center text-gray-900 font-bold text-sm font-lato shrink-0">1</div>
                                    <h3 className="text-xl font-abhaya font-bold text-gray-900">Account Information</h3>
                                </div>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div>
                                        <label className={labelClass}>Username *</label>
                                        <input type="text" className={inputClass} placeholder="your_username" value={data.name} onChange={e => setData('name', e.target.value)} required />
                                        <InputError message={errors.name} className="mt-2" />
                                    </div>
                                    <div>
                                        <label className={labelClass}>Email Address *</label>
                                        <input type="email" className={inputClass} placeholder="you@example.com" value={data.email} onChange={e => setData('email', e.target.value)} required />
                                        <InputError message={errors.email} className="mt-2" />
                                    </div>
                                    <div>
                                        <label className={labelClass}>Password *</label>
                                        <input type="password" className={inputClass} placeholder="Min. 8 characters" value={data.password} onChange={e => setData('password', e.target.value)} required />
                                        <InputError message={errors.password} className="mt-2" />
                                    </div>
                                    <div>
                                        <label className={labelClass}>Confirm Password *</label>
                                        <input type="password" className={inputClass} placeholder="Repeat password" value={data.password_confirmation} onChange={e => setData('password_confirmation', e.target.value)} required />
                                    </div>
                                </div>
                            </div>

                            <div className="h-[1px] bg-gray-200" />

                            {/* ② Personal Details */}
                            <div>
                                <div className={sectionHeadClass}>
                                    <div className="w-8 h-8 rounded-full bg-[#D4AF37] flex items-center justify-center text-gray-900 font-bold text-sm font-lato shrink-0">2</div>
                                    <h3 className="text-xl font-abhaya font-bold text-gray-900">Personal Details</h3>
                                </div>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div>
                                        <label className={labelClass}>Full Legal Name *</label>
                                        <input type="text" className={inputClass} placeholder="As per ID" value={data.full_name} onChange={e => setData('full_name', e.target.value)} required />
                                        <InputError message={errors.full_name} className="mt-2" />
                                    </div>
                                    <div>
                                        <label className={labelClass}>Phone Number</label>
                                        <input type="tel" className={inputClass} placeholder="+91 00000 00000" value={data.phone} onChange={e => setData('phone', e.target.value)} />
                                        <InputError message={errors.phone} className="mt-2" />
                                    </div>
                                    <div>
                                        <label className={labelClass}>Date of Birth</label>
                                        <input type="date" className={inputClass} value={data.date_of_birth} onChange={e => setData('date_of_birth', e.target.value)} />
                                        <InputError message={errors.date_of_birth} className="mt-2" />
                                    </div>
                                    <div>
                                        <label className={labelClass}>City of Residence</label>
                                        <input type="text" className={inputClass} placeholder="Chennai, Bangalore..." value={data.city} onChange={e => setData('city', e.target.value)} />
                                    </div>
                                    <div className="md:col-span-2">
                                        <label className={labelClass}>Connection to Sri Lanka</label>
                                        <textarea className={`${inputClass} resize-none`} rows="2" placeholder="E.g., Born in Colombo, spouse is Sri Lankan, etc." value={data.sri_lanka_connection} onChange={e => setData('sri_lanka_connection', e.target.value)}></textarea>
                                    </div>
                                </div>
                            </div>

                            <div className="h-[1px] bg-gray-200" />

                            {/* ③ Professional Details */}
                            <div>
                                <div className={sectionHeadClass}>
                                    <div className="w-8 h-8 rounded-full bg-[#D4AF37] flex items-center justify-center text-gray-900 font-bold text-sm font-lato shrink-0">3</div>
                                    <h3 className="text-xl font-abhaya font-bold text-gray-900">Professional Details</h3>
                                </div>

                                <div className="mb-6">
                                    <label className={labelClass}>Membership Type *</label>
                                    <div className="flex gap-6">
                                        {['individual', 'business'].map(type => (
                                            <label key={type} className={`flex items-center gap-3 cursor-pointer px-5 py-3 rounded-xl border transition-all duration-300 ${data.membership_type === type ? 'border-[#D4AF37] bg-[#D4AF37]/10 text-gray-900' : 'border-gray-200 bg-white text-gray-500 hover:border-[#D4AF37]/40'}`}>
                                                <input type="radio" className="sr-only" name="membership_type" value={type} checked={data.membership_type === type} onChange={e => setData('membership_type', e.target.value)} />
                                                <span className={`w-4 h-4 rounded-full border-2 flex items-center justify-center ${data.membership_type === type ? 'border-[#D4AF37]' : 'border-gray-300'}`}>
                                                    {data.membership_type === type && <span className="w-2 h-2 rounded-full bg-[#D4AF37]" />}
                                                </span>
                                                <span className="text-sm font-lato font-semibold capitalize">{type}</span>
                                            </label>
                                        ))}
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
                                    <div>
                                        <label className={labelClass}>Occupation</label>
                                        <input type="text" className={inputClass} placeholder="Profession / Trade" value={data.occupation} onChange={e => setData('occupation', e.target.value)} />
                                    </div>
                                    <div>
                                        <label className={labelClass}>Company Name</label>
                                        <input type="text" className={inputClass} placeholder="Employer / Business" value={data.company_name} onChange={e => setData('company_name', e.target.value)} />
                                    </div>
                                    <div>
                                        <label className={labelClass}>Designation</label>
                                        <input type="text" className={inputClass} placeholder="Role / Title" value={data.designation} onChange={e => setData('designation', e.target.value)} />
                                    </div>
                                </div>
                            </div>

                            {/* Consent */}
                            <div className="bg-white border border-gray-100 p-5 rounded-2xl">
                                <label className="flex items-start gap-4 cursor-pointer">
                                    <div
                                        onClick={() => setData('directory_consent', !data.directory_consent)}
                                        className={`mt-0.5 w-5 h-5 rounded border-2 flex items-center justify-center shrink-0 transition-all duration-200 ${data.directory_consent ? 'bg-[#D4AF37] border-[#D4AF37]' : 'border-gray-300 bg-white'}`}
                                    >
                                        {data.directory_consent && <CheckCircle2 className="w-3 h-3 text-gray-900" />}
                                    </div>
                                    <span className="text-sm text-gray-600 font-lato leading-relaxed">
                                        I consent to having my profile listed in the private members-only directory. (You can change this later in your profile settings.)
                                    </span>
                                </label>
                            </div>

                            {/* Submit */}
                            <div className="pt-2">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="w-full flex items-center justify-center gap-3 bg-[#D4AF37] hover:bg-gray-900 text-gray-900 hover:text-white font-lato font-bold py-4 px-6 rounded-full transition-all duration-500 hover:shadow-[0_0_30px_rgba(212,175,55,0.3)] disabled:opacity-60 uppercase tracking-widest text-sm"
                                >
                                    {processing ? 'Submitting...' : (<>Submit Application <ArrowRight className="w-4 h-4" /></>)}
                                </button>
                                <p className="text-center text-xs text-gray-400 font-lato mt-4">
                                    Already a member? <Link href={route('login')} className="text-[#D4AF37] hover:underline">Sign in here</Link>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </SLFAWebsiteLayout>
    );
}
