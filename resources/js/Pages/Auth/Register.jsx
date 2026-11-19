import { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import { Mail, Lock, User, ArrowRight, ArrowLeft, Building2, Users, MapPin, Phone, Briefcase, Calendar, Link as LinkIcon, CheckCircle2, Info, CreditCard } from 'lucide-react';
import { motion, AnimatePresence } from 'motion/react';
import toast, { Toaster } from 'react-hot-toast';
import BrandMark from '@/Components/BrandMark';

export default function Register() {
    const [step, setStep] = useState(1);
    const totalSteps = 4;

    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        membership_type: 'individual',
        profile: {
            address: '',
            landline: '',
            mobile: '',
            whatsapp: '',
            personal_email: '',
            date_of_birth: '',
            gender: 'Male',
            occupation: '',
            business_name: '',
            nature_of_business: 'Company',
            date_of_incorporation: '',
            connection_with_srilanka: []
        },
        family_members: [], // { name, relationship, gender, date_of_birth }
        business_reps: [], // { name, date_of_birth, gender, designation, email, contact_number }
        id_proof: null,
        directory_consent: false,
    });

    const updateProfile = (key, value) => {
        setData('profile', { ...data.profile, [key]: value });
    };

    const handleConnectionChange = (connection) => {
        const current = [...data.profile.connection_with_srilanka];
        if (current.includes(connection)) {
            updateProfile('connection_with_srilanka', current.filter(c => c !== connection));
        } else {
            updateProfile('connection_with_srilanka', [...current, connection]);
        }
    };

    const addFamilyMember = () => {
        setData('family_members', [...data.family_members, { name: '', relationship: '', gender: 'Male', date_of_birth: '' }]);
    };
    const updateFamilyMember = (index, key, value) => {
        const updated = [...data.family_members];
        updated[index][key] = value;
        setData('family_members', updated);
    };
    const removeFamilyMember = (index) => {
        setData('family_members', data.family_members.filter((_, i) => i !== index));
    };

    const addBusinessRep = () => {
        if (data.business_reps.length >= 3) return;
        setData('business_reps', [...data.business_reps, { name: '', date_of_birth: '', gender: 'Male', designation: '', email: '', contact_number: '' }]);
    };
    const updateBusinessRep = (index, key, value) => {
        const updated = [...data.business_reps];
        updated[index][key] = value;
        setData('business_reps', updated);
    };
    const removeBusinessRep = (index) => {
        setData('business_reps', data.business_reps.filter((_, i) => i !== index));
    };

    const nextStep = () => {
        // Validation logic per step
        if (step === 2) {
            if (!data.name || !data.email || !data.password || !data.password_confirmation) {
                return toast.error("Please fill in all required account details.");
            }
            if (data.password !== data.password_confirmation) {
                return toast.error("Passwords do not match.");
            }
            if (!data.profile.address || !data.profile.mobile) {
                return toast.error("Please provide your address and mobile number.");
            }
            if (data.membership_type === 'business' && (!data.profile.business_name || !data.profile.date_of_incorporation)) {
                return toast.error("Please provide the Business Name and Date of Incorporation.");
            }
        }
        setStep(s => Math.min(s + 1, totalSteps));
    };
    const prevStep = () => setStep(s => Math.max(s - 1, 1));

    const submit = (e) => {
        e.preventDefault();
        
        if (data.profile.connection_with_srilanka.length === 0) {
            return toast.error("Please declare your connection with Sri Lanka.");
        }
        if (!data.id_proof) {
            return toast.error("Identity Proof is required.");
        }

        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
            onError: (errs) => {
                const firstError = Object.values(errs)[0];
                toast.error(firstError || "Something went wrong. Please check your inputs.");
            },
            onSuccess: () => {
                toast.success("Application Submitted successfully!");
            }
        });
    };

    const renderStepIndicators = () => (
        <div className="flex justify-between items-center mb-10 relative">
            <div className="absolute left-0 top-1/2 -translate-y-1/2 w-full h-1 bg-gray-100 rounded-full z-0"></div>
            <div 
                className="absolute left-0 top-1/2 -translate-y-1/2 h-1 bg-gradient-to-r from-slfa-primary to-slfa-accent rounded-full z-0 transition-all duration-500"
                style={{ width: `${((step - 1) / (totalSteps - 1)) * 100}%` }}
            ></div>
            
            {[1, 2, 3, 4].map(num => (
                <div key={num} className={`relative z-10 w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm transition-all duration-300 ${
                    step >= num 
                        ? 'bg-slfa-primary text-white shadow-[0_0_15px_rgba(245,130,32,0.4)]' 
                        : 'bg-white text-gray-400 border-2 border-gray-200'
                }`}>
                    {step > num ? <CheckCircle2 className="w-5 h-5" /> : num}
                </div>
            ))}
        </div>
    );

    const connections = ["Origin", "Ancestral", "Trade / Employment", "Education", "Spouse is a SriLankan"];

    return (
        <div className="flex min-h-screen w-full font-lato antialiased bg-slfa-cream">
            <Head title="Membership Application - SLFA" />
            <Toaster position="top-right" />

            {/* Left Side - Info & Branding */}
            <div 
                className="hidden w-1/3 flex-col justify-between p-12 text-white lg:flex relative overflow-hidden shadow-2xl z-10"
                style={{ background: 'linear-gradient(135deg, #0d1e3d 0%, #1a365d 100%)' }}
            >
                <div className="absolute inset-0 bg-slfa-navy mix-blend-multiply opacity-50"></div>
                <div className="absolute inset-0 flex items-center justify-center opacity-10 pointer-events-none font-abhaya text-[10rem] font-bold" aria-hidden="true">SLFA</div>
                
                <div className="relative z-10">
                    <Link href="/" className="flex items-center group">
                        <BrandMark />
                    </Link>
                </div>

                <div className="relative z-10">
                    <h2 className="text-4xl font-abhaya font-extrabold text-white leading-tight tracking-tight mb-4">
                        Join the <span className="text-transparent bg-clip-text bg-gradient-to-r from-slfa-primary to-slfa-accent">Community</span>
                    </h2>
                    <div className="kolam-divider text-slfa-primary mb-6 justify-start"><span className="kolam-dot"></span></div>
                    <p className="text-white/80 font-lato text-base font-light leading-relaxed mb-8">
                        Apply for SLFA membership to connect with fellow Sri Lankans, discover business networking opportunities, and participate in our cultural events across South India.
                    </p>

                    <div className="mt-8 bg-white/5 backdrop-blur-md rounded-xl p-6 border border-white/10 shadow-inner overflow-y-auto max-h-[45vh] custom-scrollbar">
                        <h3 className="text-xl font-abhaya font-bold text-slfa-primary mb-3 flex items-center gap-2">
                            <Info className="w-5 h-5" /> Eligibility
                        </h3>
                        <p className="text-white/80 text-sm font-light leading-relaxed mb-4">
                            A person who has attained the age of majority (18 years) and has any connection with Sri Lanka, including but not limited to origin, ancestral, trade/employment, education, either of the spouse being a Sri Lankan, and provided such person is a resident of South India, can be admitted as a member, subject to the approval of the Executive Committee of the Society and on paying the required membership fees.
                        </p>
                        <p className="text-white/80 text-sm font-light leading-relaxed mb-6">
                            The Company or a Body Corporate can also be admitted as a member in this association. The admission of the Company or Body Corporate shall be subject to the approval of the majority of the Executive Committee members. The said company or Body Corporate on admission as member shall be represented by two authorized persons of its Board resolved in this regard as members in this association.
                        </p>
                        
                        <h3 className="text-xl font-abhaya font-bold text-slfa-primary mb-3 flex items-center gap-2">
                            <CreditCard className="w-5 h-5" /> Admission and Subscription Fees
                        </h3>
                        <p className="text-white/80 text-sm font-light leading-relaxed">
                            Every individual member other than the company or a Body Corporate applying for membership shall pay an admission Fee of Rs. 5000/- and an Annual Subscription Fee of Rs. 3000/- within 31st of March every year. The admission fee and annual fee for the company or a Body Corporate will be double of the fee payable by the individual member.
                        </p>
                    </div>
                </div>

                <div className="relative z-10 text-xs text-white/40 font-lato tracking-widest uppercase mt-8">
                    &copy; {new Date().getFullYear()} SLFA.
                </div>
            </div>

            {/* Right Side - Form Wizard */}
            <div className="flex w-full flex-col bg-white px-6 py-10 lg:w-2/3 lg:px-16 relative shadow-[-20px_0_40px_rgba(0,0,0,0.05)] z-20 overflow-y-auto">
                <div className="absolute top-6 right-8">
                    <Link href="/" className="flex items-center gap-2 text-slfa-navy/60 hover:text-slfa-primary transition-colors text-sm font-bold tracking-wide uppercase font-lato group">
                        <ArrowLeft className="w-4 h-4 group-hover:-translate-x-1 transition-transform" />
                        <span>Back</span>
                    </Link>
                </div>

                <div className="mx-auto w-full max-w-2xl mt-10">
                    <div className="mb-10 text-center">
                        <h2 className="text-3xl font-abhaya font-extrabold text-slfa-navy mb-2 tracking-tight">Membership Application</h2>
                        <p className="text-gray-500 font-lato">Complete the form below to apply for membership.</p>
                    </div>

                    {renderStepIndicators()}

                    <form onSubmit={submit}>
                        <AnimatePresence mode="wait">
                            {/* STEP 1: Membership Type */}
                            {step === 1 && (
                                <motion.div key="step1" initial={{ opacity: 0, x: 20 }} animate={{ opacity: 1, x: 0 }} exit={{ opacity: 0, x: -20 }} className="space-y-6">
                                    <h3 className="text-xl font-abhaya font-bold text-slfa-navy border-b pb-2">1. Select Membership Type</h3>
                                    
                                    <div className="grid grid-cols-2 gap-6 mt-8">
                                        <button type="button" onClick={() => { setData('membership_type', 'individual'); nextStep(); }} className={`p-8 rounded-2xl border-2 transition-all flex flex-col items-center gap-4 ${data.membership_type === 'individual' ? 'border-slfa-primary bg-slfa-primary/5 text-slfa-primary shadow-lg shadow-slfa-primary/10' : 'border-gray-200 text-gray-500 hover:border-slfa-primary/50 hover:bg-gray-50'}`}>
                                            <Users className="w-12 h-12" />
                                            <span className="font-bold text-lg">Individual Membership</span>
                                        </button>
                                        <button type="button" onClick={() => { setData('membership_type', 'business'); nextStep(); }} className={`p-8 rounded-2xl border-2 transition-all flex flex-col items-center gap-4 ${data.membership_type === 'business' ? 'border-slfa-accent bg-slfa-accent/5 text-slfa-accent shadow-lg shadow-slfa-accent/10' : 'border-gray-200 text-gray-500 hover:border-slfa-accent/50 hover:bg-gray-50'}`}>
                                            <Building2 className="w-12 h-12" />
                                            <span className="font-bold text-lg">Business Membership</span>
                                        </button>
                                    </div>
                                </motion.div>
                            )}

                            {/* STEP 2: Basic Information */}
                            {step === 2 && (
                                <motion.div key="step2" initial={{ opacity: 0, x: 20 }} animate={{ opacity: 1, x: 0 }} exit={{ opacity: 0, x: -20 }} className="space-y-6">
                                    <h3 className="text-xl font-abhaya font-bold text-slfa-navy border-b pb-2">2. Basic Information</h3>
                                    
                                    {data.membership_type === 'business' && (
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div>
                                                <InputLabel value="Business Name" />
                                                <TextInput value={data.profile.business_name} className="mt-1 block w-full rounded-xl bg-gray-50 border-gray-300 focus:border-slfa-primary focus:ring-slfa-primary" onChange={(e) => updateProfile('business_name', e.target.value)} />
                                            </div>
                                            <div>
                                                <InputLabel value="Nature of Business" />
                                                <select className="mt-1 block w-full border-gray-300 focus:border-slfa-primary focus:ring-slfa-primary rounded-xl shadow-sm bg-gray-50 py-2.5" value={data.profile.nature_of_business} onChange={e => updateProfile('nature_of_business', e.target.value)}>
                                                    <option>Company</option>
                                                    <option>LLP</option>
                                                    <option>Society</option>
                                                    <option>Firm</option>
                                                    <option>Others</option>
                                                </select>
                                            </div>
                                            <div>
                                                <InputLabel value="Date of Incorporation" />
                                                <TextInput type="date" value={data.profile.date_of_incorporation} className="mt-1 block w-full rounded-xl bg-gray-50 border-gray-300 focus:border-slfa-primary focus:ring-slfa-primary" onChange={(e) => updateProfile('date_of_incorporation', e.target.value)} />
                                            </div>
                                        </div>
                                    )}

                                    {data.membership_type === 'individual' && (
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div className="md:col-span-2">
                                                <InputLabel value="Full Name" />
                                                <TextInput value={data.name} className="mt-1 block w-full rounded-xl bg-gray-50 border-gray-300 focus:border-slfa-primary focus:ring-slfa-primary" onChange={(e) => setData('name', e.target.value)} required />
                                            </div>
                                            <div>
                                                <InputLabel value="Date of Birth" />
                                                <TextInput type="date" value={data.profile.date_of_birth} className="mt-1 block w-full rounded-xl bg-gray-50 border-gray-300 focus:border-slfa-primary focus:ring-slfa-primary" onChange={(e) => updateProfile('date_of_birth', e.target.value)} />
                                            </div>
                                            <div>
                                                <InputLabel value="Gender" />
                                                <select className="mt-1 block w-full border-gray-300 focus:border-slfa-primary focus:ring-slfa-primary rounded-xl shadow-sm bg-gray-50 py-2.5" value={data.profile.gender} onChange={e => updateProfile('gender', e.target.value)}>
                                                    <option>Male</option>
                                                    <option>Female</option>
                                                    <option>Others</option>
                                                </select>
                                            </div>
                                            <div className="md:col-span-2">
                                                <InputLabel value="Occupation / Profession" />
                                                <TextInput value={data.profile.occupation} className="mt-1 block w-full rounded-xl bg-gray-50 border-gray-300 focus:border-slfa-primary focus:ring-slfa-primary" onChange={(e) => updateProfile('occupation', e.target.value)} />
                                            </div>
                                        </div>
                                    )}

                                    <div>
                                        <InputLabel value={data.membership_type === 'business' ? "Registered Office Address" : "Residential Address"} />
                                        <textarea 
                                            value={data.profile.address}
                                            onChange={e => updateProfile('address', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 focus:border-slfa-primary focus:ring-slfa-primary rounded-xl shadow-sm bg-gray-50 min-h-[80px] resize-none"
                                        ></textarea>
                                    </div>

                                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                        <div>
                                            <InputLabel value="Mobile Number" />
                                            <TextInput value={data.profile.mobile} className="mt-1 block w-full rounded-xl bg-gray-50 border-gray-300 focus:border-slfa-primary focus:ring-slfa-primary" onChange={(e) => updateProfile('mobile', e.target.value)} />
                                        </div>
                                        <div>
                                            <InputLabel value="WhatsApp" />
                                            <TextInput value={data.profile.whatsapp} className="mt-1 block w-full rounded-xl bg-gray-50 border-gray-300 focus:border-slfa-primary focus:ring-slfa-primary" onChange={(e) => updateProfile('whatsapp', e.target.value)} />
                                        </div>
                                        <div>
                                            <InputLabel value="Landline" />
                                            <TextInput value={data.profile.landline} className="mt-1 block w-full rounded-xl bg-gray-50 border-gray-300 focus:border-slfa-primary focus:ring-slfa-primary" onChange={(e) => updateProfile('landline', e.target.value)} />
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <InputLabel value={data.membership_type === 'business' ? "Contact Email" : "Official / Primary Email"} />
                                            <TextInput type="email" value={data.email} className="mt-1 block w-full rounded-xl bg-gray-50 border-gray-300 focus:border-slfa-primary focus:ring-slfa-primary" onChange={(e) => setData('email', e.target.value)} required />
                                            {data.membership_type === 'business' && (
                                                <p className="text-xs text-gray-500 mt-1">This will be used for your account login.</p>
                                            )}
                                        </div>
                                        {data.membership_type === 'individual' && (
                                            <div>
                                                <InputLabel value="Personal Email" />
                                                <TextInput type="email" value={data.profile.personal_email} className="mt-1 block w-full rounded-xl bg-gray-50 border-gray-300 focus:border-slfa-primary focus:ring-slfa-primary" onChange={(e) => updateProfile('personal_email', e.target.value)} />
                                            </div>
                                        )}
                                    </div>

                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-gray-100">
                                        <div>
                                            <InputLabel value="Create Password" />
                                            <TextInput type="password" value={data.password} className="mt-1 block w-full rounded-xl bg-gray-50 border-gray-300 focus:border-slfa-primary focus:ring-slfa-primary" onChange={(e) => setData('password', e.target.value)} required />
                                        </div>
                                        <div>
                                            <InputLabel value="Confirm Password" />
                                            <TextInput type="password" value={data.password_confirmation} className="mt-1 block w-full rounded-xl bg-gray-50 border-gray-300 focus:border-slfa-primary focus:ring-slfa-primary" onChange={(e) => setData('password_confirmation', e.target.value)} required />
                                        </div>
                                    </div>
                                </motion.div>
                            )}

                            {/* STEP 3: Family / Representatives */}
                            {step === 3 && (
                                <motion.div key="step3" initial={{ opacity: 0, x: 20 }} animate={{ opacity: 1, x: 0 }} exit={{ opacity: 0, x: -20 }} className="space-y-6">
                                    <h3 className="text-xl font-abhaya font-bold text-slfa-navy border-b pb-2">
                                        3. {data.membership_type === 'business' ? 'Business Representatives' : 'Family Details'}
                                    </h3>
                                    
                                    <p className="text-sm text-gray-500">
                                        {data.membership_type === 'business' 
                                            ? 'Provide details for up to 3 representatives for your business.' 
                                            : 'Add details for family members residing with you (Optional).'}
                                    </p>

                                    {data.membership_type === 'business' ? (
                                        <div className="space-y-6">
                                            {data.business_reps.map((rep, idx) => (
                                                <div key={idx} className="p-5 bg-gray-50 rounded-xl border border-gray-200 relative">
                                                    <button type="button" onClick={() => removeBusinessRep(idx)} className="absolute top-4 right-4 text-red-500 hover:text-red-700 text-sm font-bold">Remove</button>
                                                    <h4 className="font-bold mb-4">Representative {idx + 1}</h4>
                                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                        <div><InputLabel value="Name" /><TextInput value={rep.name} className="mt-1 w-full bg-white rounded-lg border-gray-300 focus:border-slfa-primary" onChange={e => updateBusinessRep(idx, 'name', e.target.value)} /></div>
                                                        <div><InputLabel value="Date of Birth" /><TextInput type="date" value={rep.date_of_birth} className="mt-1 w-full bg-white rounded-lg border-gray-300 focus:border-slfa-primary" onChange={e => updateBusinessRep(idx, 'date_of_birth', e.target.value)} /></div>
                                                        <div>
                                                            <InputLabel value="Gender" />
                                                            <select className="mt-1 w-full border-gray-300 bg-white focus:border-slfa-primary rounded-lg py-2.5" value={rep.gender} onChange={e => updateBusinessRep(idx, 'gender', e.target.value)}>
                                                                <option>Male</option><option>Female</option><option>Others</option>
                                                            </select>
                                                        </div>
                                                        <div><InputLabel value="Designation" /><TextInput value={rep.designation} className="mt-1 w-full bg-white rounded-lg border-gray-300 focus:border-slfa-primary" onChange={e => updateBusinessRep(idx, 'designation', e.target.value)} /></div>
                                                        <div><InputLabel value="Email" /><TextInput type="email" value={rep.email} className="mt-1 w-full bg-white rounded-lg border-gray-300 focus:border-slfa-primary" onChange={e => updateBusinessRep(idx, 'email', e.target.value)} /></div>
                                                        <div><InputLabel value="Contact Number" /><TextInput value={rep.contact_number} className="mt-1 w-full bg-white rounded-lg border-gray-300 focus:border-slfa-primary" onChange={e => updateBusinessRep(idx, 'contact_number', e.target.value)} /></div>
                                                    </div>
                                                </div>
                                            ))}
                                            {data.business_reps.length < 3 && (
                                                <button type="button" onClick={addBusinessRep} className="w-full py-4 border-2 border-dashed border-slfa-primary/30 rounded-xl text-slfa-primary font-bold hover:bg-slfa-primary/5 transition-colors">
                                                    + Add Representative
                                                </button>
                                            )}
                                        </div>
                                    ) : (
                                        <div className="space-y-6">
                                            {data.family_members.map((fam, idx) => (
                                                <div key={idx} className="p-5 bg-gray-50 rounded-xl border border-gray-200 relative">
                                                    <button type="button" onClick={() => removeFamilyMember(idx)} className="absolute top-4 right-4 text-red-500 hover:text-red-700 text-sm font-bold">Remove</button>
                                                    <h4 className="font-bold mb-4">Family Member {idx + 1}</h4>
                                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                        <div><InputLabel value="Name" /><TextInput value={fam.name} className="mt-1 w-full bg-white rounded-lg border-gray-300 focus:border-slfa-primary" onChange={e => updateFamilyMember(idx, 'name', e.target.value)} /></div>
                                                        <div><InputLabel value="Relationship" /><TextInput value={fam.relationship} className="mt-1 w-full bg-white rounded-lg border-gray-300 focus:border-slfa-primary" onChange={e => updateFamilyMember(idx, 'relationship', e.target.value)} /></div>
                                                        <div>
                                                            <InputLabel value="Gender" />
                                                            <select className="mt-1 w-full border-gray-300 bg-white focus:border-slfa-primary rounded-lg py-2.5" value={fam.gender} onChange={e => updateFamilyMember(idx, 'gender', e.target.value)}>
                                                                <option>Male</option><option>Female</option><option>Others</option>
                                                            </select>
                                                        </div>
                                                        <div><InputLabel value="Date of Birth" /><TextInput type="date" value={fam.date_of_birth} className="mt-1 w-full bg-white rounded-lg border-gray-300 focus:border-slfa-primary" onChange={e => updateFamilyMember(idx, 'date_of_birth', e.target.value)} /></div>
                                                    </div>
                                                </div>
                                            ))}
                                            <button type="button" onClick={addFamilyMember} className="w-full py-4 border-2 border-dashed border-slfa-primary/30 rounded-xl text-slfa-primary font-bold hover:bg-slfa-primary/5 transition-colors">
                                                + Add Family Member
                                            </button>
                                        </div>
                                    )}
                                </motion.div>
                            )}

                            {/* STEP 4: SL Connection */}
                            {step === 4 && (
                                <motion.div key="step4" initial={{ opacity: 0, x: 20 }} animate={{ opacity: 1, x: 0 }} exit={{ opacity: 0, x: -20 }} className="space-y-6">
                                    <h3 className="text-xl font-abhaya font-bold text-slfa-navy border-b pb-2">4. Connection with Sri Lanka</h3>
                                    
                                    <p className="text-sm text-gray-500 mb-4">
                                        Please declare the nature of your connection with Sri Lanka (Select all that apply).
                                    </p>

                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        {connections.map(conn => (
                                            <label key={conn} className={`flex items-center p-4 border rounded-xl cursor-pointer transition-all ${data.profile.connection_with_srilanka.includes(conn) ? 'border-slfa-primary bg-slfa-primary/5 shadow-sm' : 'border-gray-200 bg-gray-50 hover:border-slfa-primary/40'}`}>
                                                <input 
                                                    type="checkbox" 
                                                    className="w-5 h-5 text-slfa-primary rounded border-gray-300 focus:ring-slfa-primary"
                                                    checked={data.profile.connection_with_srilanka.includes(conn)}
                                                    onChange={() => handleConnectionChange(conn)}
                                                />
                                                <span className="ml-3 font-bold text-slfa-navy">{conn}</span>
                                            </label>
                                        ))}
                                    </div>

                                    <div className="mt-8 border-t border-gray-100 pt-6">
                                        <h4 className="font-abhaya font-bold text-xl text-slfa-navy mb-4">Identity Proof</h4>
                                        <p className="text-sm text-gray-500 mb-4">
                                            Please upload a valid ID proof (Aadhaar, Passport, Driving License, etc.)
                                        </p>
                                        <input 
                                            type="file" 
                                            accept="image/*,.pdf"
                                            onChange={(e) => setData('id_proof', e.target.files[0])}
                                            className="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-slfa-primary/10 file:text-slfa-primary hover:file:bg-slfa-primary/20 transition-all border border-gray-200 rounded-xl p-2 bg-gray-50 cursor-pointer focus:outline-none"
                                        />
                                        <InputError message={errors.id_proof} className="mt-2" />
                                    </div>

                                    <div className="mt-8 p-6 bg-slfa-navy text-white rounded-2xl shadow-xl">
                                        <h4 className="font-abhaya font-bold text-xl text-slfa-gold mb-2">Declaration & Fees</h4>
                                        <ul className="text-sm font-light text-white/80 space-y-2 list-disc pl-4">
                                            <li>One time Enrolment / Registration Fee Rs 5,000/-</li>
                                            <li>Yearly Membership Fee of Rs 3,000/-</li>
                                            <li>All applicants are required to provide at least one ID Proof.</li>
                                            <li>Membership application is subject to Executive Committee review.</li>
                                        </ul>
                                        <p className="mt-4 text-sm text-white/80">This form submits your application for review. It does not collect payment.</p>
                                    </div>
                                    <label className="mt-6 flex items-start gap-3 text-sm text-gray-700">
                                        <input type="checkbox" checked={data.directory_consent}
                                            onChange={(e) => setData('directory_consent', e.target.checked)} />
                                        Show my profile in the private member directory after approval. I can change this in my profile.
                                    </label>
                                </motion.div>
                            )}
                        </AnimatePresence>

                        {/* Navigation Buttons */}
                        <div className="flex items-center justify-between mt-10 pt-6 border-t border-gray-100">
                            {step > 1 ? (
                                <button type="button" onClick={prevStep} className="px-6 py-3 rounded-xl border border-gray-200 text-gray-600 font-bold hover:bg-gray-50 transition-colors flex items-center gap-2">
                                    <ArrowLeft className="w-4 h-4" /> Back
                                </button>
                            ) : (
                                <div></div>
                            )}

                            {step < totalSteps ? (
                                <button type="button" onClick={nextStep} className="px-8 py-3 rounded-xl bg-slfa-navy text-white font-bold hover:bg-slfa-navy/90 shadow-lg shadow-slfa-navy/20 transition-all flex items-center gap-2">
                                    Continue <ArrowRight className="w-4 h-4" />
                                </button>
                            ) : (
                                <button type="submit" disabled={processing} className="px-8 py-3 rounded-xl bg-gradient-to-r from-slfa-primary to-slfa-accent text-white font-bold uppercase tracking-wider hover:shadow-lg transition-all disabled:opacity-50 flex items-center gap-2">
                                    Submit Application <CheckCircle2 className="w-5 h-5" />
                                </button>
                            )}
                        </div>
                    </form>

                    <div className="mt-8 text-center">
                        <p className="text-sm text-gray-500 font-lato">
                            Already have an account?{' '}
                            <Link href={route('login')} className="font-bold text-slfa-navy hover:text-slfa-primary border-b border-slfa-primary/30 pb-0.5">
                                Login here
                            </Link>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    );
}
