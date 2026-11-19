import { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, Check, Plus, Trash2 } from 'lucide-react';
import BrandMark from '@/Components/BrandMark';

const steps = ['Membership', 'Your details', 'People', 'Review'];
const connections = ['Origin', 'Ancestral', 'Trade / Employment', 'Education', 'Spouse is Sri Lankan'];
const newRepresentative = () => ({ name: '', designation: '', email: '', contact_number: '', date_of_birth: '', gender: '' });
const newFamilyMember = () => ({ name: '', relationship: '', date_of_birth: '', gender: '' });
const inputClass = 'mt-2 block w-full rounded-none border border-[#cbbb93] bg-white/70 px-4 py-3 text-gray-950 placeholder:text-gray-500 focus:border-[#800000] focus:ring-1 focus:ring-[#800000]';
const phonePattern = /^\+?[0-9][0-9\s().-]{6,24}$/;

function Field({ label, error, required = false, children }) {
    return <div><label className="block text-sm font-bold text-gray-950">{label}{required && <span className="ml-1 text-[#800000]" aria-label="required">*</span>}{children}</label>{error && <p role="alert" className="mt-1.5 text-sm text-red-700">{error}</p>}</div>;
}

export default function Register() {
    const [step, setStep] = useState(0);
    const [clientErrors, setClientErrors] = useState({});
    const { data, setData, post, processing, errors, clearErrors, reset } = useForm({
        membership_type: '', name: '', email: '', password: '', password_confirmation: '',
        profile: {
            address: '', mobile: '', whatsapp: '', landline: '', personal_email: '',
            date_of_birth: '', gender: '', occupation: '', business_name: '',
            nature_of_business: 'Company', date_of_incorporation: '', connection_with_srilanka: [],
        },
        family_members: [], business_reps: [], id_proof: null,
        directory_consent: false, accept_declaration: false,
    });

    const errorFor = (key) => clientErrors[key] || errors[key];
    const clearField = (key) => {
        setClientErrors(previous => { const next = { ...previous }; delete next[key]; return next; });
        clearErrors(key);
    };
    const change = (key, value) => { setData(key, value); clearField(key); };
    const changeProfile = (key, value) => { setData('profile', { ...data.profile, [key]: value }); clearField(`profile.${key}`); };
    const chooseType = (type) => {
        if (data.membership_type !== type) {
            setData({ ...data, membership_type: type, business_reps: type === 'business' ? [newRepresentative(), newRepresentative()] : [] });
        }
        clearField('membership_type');
    };
    const changePerson = (kind, index, key, value) => {
        const items = data[kind].map((person, position) => position === index ? { ...person, [key]: value } : person);
        setData(kind, items);
        clearField(`${kind}.${index}.${key}`);
    };

    const validateStep = (number) => {
        const found = {};
        if (number === 0 && !data.membership_type) found.membership_type = 'Choose a membership type.';
        if (number === 1) {
            if (data.name.trim().length < 2) found.name = 'Enter the applicant or primary contact’s full name.';
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email.trim())) found.email = 'Enter a valid email address.';
            if (data.password.length < 10 || !/[a-z]/.test(data.password) || !/[A-Z]/.test(data.password) || !/[0-9]/.test(data.password)) found.password = 'Use at least 10 characters with upper and lower case letters and a number.';
            if (data.password !== data.password_confirmation) found.password_confirmation = 'The passwords do not match.';
            if (data.profile.address.trim().length < 8) found['profile.address'] = 'Enter a complete address.';
            if (!phonePattern.test(data.profile.mobile.trim())) found['profile.mobile'] = 'Enter a valid phone number with country code if needed.';
            for (const key of ['whatsapp', 'landline']) if (data.profile[key] && !phonePattern.test(data.profile[key].trim())) found[`profile.${key}`] = 'Enter a valid phone number.';
            if (data.membership_type === 'individual') {
                const cutoff = new Date(); cutoff.setFullYear(cutoff.getFullYear() - 18);
                if (!data.profile.date_of_birth || new Date(`${data.profile.date_of_birth}T12:00:00`) > cutoff) found['profile.date_of_birth'] = 'Applicants must be at least 18 years old.';
            } else {
                if (!data.profile.business_name.trim()) found['profile.business_name'] = 'Enter the registered business name.';
                if (!data.profile.date_of_incorporation || new Date(`${data.profile.date_of_incorporation}T12:00:00`) > new Date()) found['profile.date_of_incorporation'] = 'Enter a valid incorporation date.';
            }
        }
        if (number === 2) {
            if (data.membership_type === 'business') {
                if (data.business_reps.length !== 2) found.business_reps = 'Provide two authorised representatives.';
                data.business_reps.forEach((person, index) => {
                    if (!person.name.trim()) found[`business_reps.${index}.name`] = 'Enter the representative’s name.';
                    if (!person.designation.trim()) found[`business_reps.${index}.designation`] = 'Enter their role or designation.';
                    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(person.email.trim())) found[`business_reps.${index}.email`] = 'Enter a valid email address.';
                    if (!phonePattern.test(person.contact_number.trim())) found[`business_reps.${index}.contact_number`] = 'Enter a valid phone number.';
                });
            } else data.family_members.forEach((person, index) => {
                if (!person.name.trim()) found[`family_members.${index}.name`] = 'Enter the family member’s name or remove this row.';
                if (!person.relationship.trim()) found[`family_members.${index}.relationship`] = 'Enter their relationship.';
            });
        }
        if (number === 3) {
            if (!data.profile.connection_with_srilanka.length) found['profile.connection_with_srilanka'] = 'Select at least one connection.';
            if (!data.id_proof) found.id_proof = 'Upload an identity document.';
            else if (!['application/pdf', 'image/jpeg', 'image/png'].includes(data.id_proof.type) || data.id_proof.size > 5 * 1024 * 1024) found.id_proof = 'Upload a PDF, JPG, or PNG file up to 5 MB.';
            if (!data.accept_declaration) found.accept_declaration = 'Confirm the declaration before submitting.';
        }
        setClientErrors(found);
        if (Object.keys(found).length) {
            window.setTimeout(() => document.querySelector('[aria-invalid="true"]')?.focus(), 0);
            return false;
        }
        return true;
    };

    const submit = (event) => {
        event.preventDefault();
        if (step < 3) {
            if (validateStep(step)) setStep(step + 1);
            return;
        }
        if (!validateStep(3)) return;
        post(route('register'), {
            onError: responseErrors => {
                const first = Object.keys(responseErrors)[0] || '';
                setStep(first === 'membership_type' ? 0 : /^(name|email|password|profile\.(address|mobile|whatsapp|landline|personal_email|date_of_birth|gender|occupation|business_name|nature_of_business|date_of_incorporation))/.test(first) ? 1 : /^(family_members|business_reps)/.test(first) ? 2 : 3);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    const textInput = (key, value, onChange, type = 'text', extra = {}) => <input type={type} value={value} onChange={event => onChange(event.target.value)} aria-invalid={Boolean(errorFor(key))} className={inputClass} {...extra} />;
    const profileInput = (key, type = 'text', extra = {}) => textInput(`profile.${key}`, data.profile[key], value => changeProfile(key, value), type, extra);
    const personInput = (kind, index, key, type = 'text') => textInput(`${kind}.${index}.${key}`, data[kind][index][key], value => changePerson(kind, index, key, value), type);

    return <main className="grid min-h-screen bg-[#f5f1e9] font-lato lg:grid-cols-[minmax(0,0.78fr)_minmax(0,1.22fr)]">
        <Head title="Membership application" />
        <aside className="relative hidden min-h-screen flex-col justify-between overflow-hidden bg-gray-950 px-10 py-10 text-white lg:flex xl:px-16 xl:py-14">
            <img src="/images/sigiriya-rock.jpg" alt="" className="absolute inset-0 h-full w-full object-cover opacity-35" />
            <div className="absolute inset-0 bg-gradient-to-r from-gray-950/90 via-gray-950/75 to-gray-950/50" aria-hidden="true" />
            <Link href={route('home')} className="relative z-10 w-fit"><BrandMark /></Link>
            <div className="relative z-10 max-w-lg">
                <p className="text-xs font-bold uppercase tracking-[0.28em] text-[#D4AF37]">Membership</p>
                <h1 className="mt-6 font-abhaya text-6xl font-bold leading-[1.04]">Find your place in the <em className="font-normal text-[#D4AF37]">community.</em></h1>
                <div className="mt-8 h-px w-20 bg-[#D4AF37]" aria-hidden="true" />
                <p className="mt-7 text-lg leading-relaxed text-gray-200">For adults living in South India with a connection to Sri Lanka. Applications are reviewed by the SLFA Executive Committee.</p>
                <p className="mt-5 text-sm leading-relaxed text-gray-300">This form does not collect payment. Fees are due only as directed by the association.</p>
            </div>
            <p className="relative z-10 text-xs uppercase tracking-[0.17em] text-gray-400">Sri Lankan Friendship Association of South India</p>
        </aside>

        <div className="min-w-0 px-5 py-7 sm:px-10 sm:py-10 xl:px-20 xl:py-14">
            <div className="flex items-center justify-between gap-4"><Link href={route('home')} className="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-[0.15em] text-gray-600 hover:text-[#800000]"><ArrowLeft size={17} /> Back to website</Link><div className="lg:hidden"><BrandMark compact /></div></div>
            <div className="mx-auto mt-12 max-w-2xl pb-12 sm:mt-16">
                <p className="text-xs font-bold uppercase tracking-[0.25em] text-[#9a7714]">Apply to join SLFA</p>
                <h2 className="mt-3 font-abhaya text-4xl font-bold text-gray-950 sm:text-5xl">Membership application</h2>
                <p className="mt-3 text-sm leading-relaxed text-gray-600">Complete four short steps. Fields marked * are required.</p>
                <ol aria-label="Application progress" className="mt-9 grid grid-cols-4 gap-2 border-b border-[#cbbb93] pb-4">{steps.map((label, index) => <li key={label} aria-current={step === index ? 'step' : undefined} className={`text-xs font-bold ${step === index ? 'text-[#800000]' : index < step ? 'text-[#8b6915]' : 'text-gray-500'}`}><span className="mb-1 block font-abhaya text-xl">{index < step ? <Check size={18} /> : `0${index + 1}`}</span>{label}</li>)}</ol>
                {Object.keys(errors).length > 0 && <p role="alert" className="mt-5 border-l-2 border-red-700 bg-red-50 p-3 text-sm text-red-800">Please correct the highlighted fields before submitting.</p>}

                <form onSubmit={submit} className="mt-8" noValidate>
                    {step === 0 && <section aria-labelledby="step-title" className="space-y-6"><div><h3 id="step-title" className="font-abhaya text-3xl font-bold text-gray-950">Choose your membership</h3><p className="mt-2 text-sm text-gray-600">Select the option that describes your application.</p></div><div className="grid gap-4 sm:grid-cols-2">{[['individual', 'Individual', 'For an adult with a Sri Lankan connection living in South India.', '₹5,000 enrolment · ₹3,000 yearly'], ['business', 'Business', 'For a company or body corporate with two authorised representatives.', 'Fees are double the individual rate']].map(([type, title, description, fee]) => <button key={type} type="button" onClick={() => chooseType(type)} aria-pressed={data.membership_type === type} className={`border-t p-5 text-left transition-colors hover:bg-white/60 ${data.membership_type === type ? 'border-[#800000] bg-white/60' : 'border-[#b7a676]'}`}><span className="font-abhaya text-2xl font-bold text-gray-950">{title}</span><span className="mt-3 block text-sm leading-relaxed text-gray-600">{description}</span><span className="mt-5 block text-xs font-bold text-[#8b6915]">{fee}</span></button>)}</div>{errorFor('membership_type') && <p role="alert" className="text-sm text-red-700">{errorFor('membership_type')}</p>}</section>}

                    {step === 1 && <section aria-labelledby="step-title" className="space-y-6"><div><h3 id="step-title" className="font-abhaya text-3xl font-bold text-gray-950">{data.membership_type === 'business' ? 'Business & contact details' : 'Your details'}</h3><p className="mt-2 text-sm text-gray-600">Your email will be used to sign in and receive application updates.</p></div>
                        {data.membership_type === 'business' && <div className="grid gap-5 sm:grid-cols-2"><Field label="Registered business name" required error={errorFor('profile.business_name')}>{profileInput('business_name', 'text', { autoComplete: 'organization' })}</Field><Field label="Organisation type" error={errorFor('profile.nature_of_business')}><select value={data.profile.nature_of_business} onChange={event => changeProfile('nature_of_business', event.target.value)} className={inputClass}>{['Company', 'LLP', 'Society', 'Firm', 'Others'].map(option => <option key={option}>{option}</option>)}</select></Field><Field label="Date of incorporation" required error={errorFor('profile.date_of_incorporation')}>{profileInput('date_of_incorporation', 'date')}</Field></div>}
                        <Field label={data.membership_type === 'business' ? 'Primary contact’s full name' : 'Full name'} required error={errorFor('name')}>{textInput('name', data.name, value => change('name', value), 'text', { autoComplete: 'name' })}</Field>
                        {data.membership_type === 'individual' && <div className="grid gap-5 sm:grid-cols-2"><Field label="Date of birth" required error={errorFor('profile.date_of_birth')}>{profileInput('date_of_birth', 'date')}</Field><Field label="Occupation or profession" error={errorFor('profile.occupation')}>{profileInput('occupation')}</Field></div>}
                        <Field label={data.membership_type === 'business' ? 'Registered office address' : 'Residential address'} required error={errorFor('profile.address')}><textarea value={data.profile.address} onChange={event => changeProfile('address', event.target.value)} aria-invalid={Boolean(errorFor('profile.address'))} rows={3} autoComplete="street-address" className={inputClass} /></Field>
                        <div className="grid gap-5 sm:grid-cols-2"><Field label="Mobile number" required error={errorFor('profile.mobile')}>{profileInput('mobile', 'tel', { autoComplete: 'tel', placeholder: '+91 98765 43210' })}</Field><Field label="WhatsApp number" error={errorFor('profile.whatsapp')}>{profileInput('whatsapp', 'tel', { placeholder: 'Optional' })}</Field></div>
                        <Field label="Email address" required error={errorFor('email')}>{textInput('email', data.email, value => change('email', value), 'email', { autoComplete: 'email' })}</Field>
                        <div className="grid gap-5 sm:grid-cols-2"><Field label="Create password" required error={errorFor('password')}>{textInput('password', data.password, value => change('password', value), 'password', { autoComplete: 'new-password' })}</Field><Field label="Confirm password" required error={errorFor('password_confirmation')}>{textInput('password_confirmation', data.password_confirmation, value => change('password_confirmation', value), 'password', { autoComplete: 'new-password' })}</Field></div><p className="text-xs text-gray-600">Use at least 10 characters, including upper and lower case letters and a number.</p>
                    </section>}

                    {step === 2 && <section aria-labelledby="step-title" className="space-y-6"><div><h3 id="step-title" className="font-abhaya text-3xl font-bold text-gray-950">{data.membership_type === 'business' ? 'Authorised representatives' : 'Family members'}</h3><p className="mt-2 text-sm text-gray-600">{data.membership_type === 'business' ? 'Provide the two people authorised to represent this organisation.' : 'Add family members living with you if you wish. This step is optional.'}</p></div>
                        {data.membership_type === 'business' ? data.business_reps.map((person, index) => <div key={index} className="space-y-4 border-t border-[#b7a676] pt-5"><h4 className="font-abhaya text-2xl font-bold text-gray-950">Representative {index + 1}</h4><div className="grid gap-5 sm:grid-cols-2"><Field label="Full name" required error={errorFor(`business_reps.${index}.name`)}>{personInput('business_reps', index, 'name')}</Field><Field label="Role or designation" required error={errorFor(`business_reps.${index}.designation`)}>{personInput('business_reps', index, 'designation')}</Field><Field label="Email address" required error={errorFor(`business_reps.${index}.email`)}>{personInput('business_reps', index, 'email', 'email')}</Field><Field label="Contact number" required error={errorFor(`business_reps.${index}.contact_number`)}>{personInput('business_reps', index, 'contact_number', 'tel')}</Field></div></div>) : <>{data.family_members.map((person, index) => <div key={index} className="space-y-4 border-t border-[#b7a676] pt-5"><div className="flex items-center justify-between"><h4 className="font-abhaya text-2xl font-bold text-gray-950">Family member {index + 1}</h4><button type="button" onClick={() => change('family_members', data.family_members.filter((_, position) => position !== index))} className="inline-flex items-center gap-1 text-xs font-bold text-[#800000]"><Trash2 size={14} /> Remove</button></div><div className="grid gap-5 sm:grid-cols-2"><Field label="Full name" required error={errorFor(`family_members.${index}.name`)}>{personInput('family_members', index, 'name')}</Field><Field label="Relationship" required error={errorFor(`family_members.${index}.relationship`)}>{personInput('family_members', index, 'relationship')}</Field></div></div>)}<button type="button" disabled={data.family_members.length >= 10} onClick={() => change('family_members', [...data.family_members, newFamilyMember()])} className="inline-flex items-center gap-2 border-b border-[#800000] pb-1 text-sm font-bold text-[#800000] disabled:opacity-50"><Plus size={16} /> Add family member</button></>}
                    </section>}

                    {step === 3 && <section aria-labelledby="step-title" className="space-y-7"><div><h3 id="step-title" className="font-abhaya text-3xl font-bold text-gray-950">Review & declaration</h3><p className="mt-2 text-sm text-gray-600">Confirm your connection and upload one identity document before submitting.</p></div>
                        <div className="border-y border-[#b7a676] py-5 text-sm text-gray-700"><p><strong>Membership:</strong> {data.membership_type === 'business' ? 'Business' : 'Individual'}</p><p className="mt-1"><strong>Applicant:</strong> {data.name}</p><p className="mt-1"><strong>Email:</strong> {data.email}</p><p className="mt-3"><strong>Fees:</strong> {data.membership_type === 'business' ? '₹10,000 enrolment and ₹6,000 annual subscription' : '₹5,000 enrolment and ₹3,000 annual subscription'}. No payment is collected with this form.</p></div>
                        <div><p className="text-sm font-bold text-gray-950">Your connection with Sri Lanka <span className="text-[#800000]">*</span></p><div className="mt-3 grid gap-2 sm:grid-cols-2">{connections.map(option => <label key={option} className="flex cursor-pointer items-center gap-3 border border-[#cbbb93] px-4 py-3 text-sm text-gray-800"><input type="checkbox" checked={data.profile.connection_with_srilanka.includes(option)} onChange={() => changeProfile('connection_with_srilanka', data.profile.connection_with_srilanka.includes(option) ? data.profile.connection_with_srilanka.filter(value => value !== option) : [...data.profile.connection_with_srilanka, option])} className="border-[#a99a79] text-[#800000] focus:ring-[#800000]" />{option}</label>)}</div>{errorFor('profile.connection_with_srilanka') && <p role="alert" className="mt-2 text-sm text-red-700">{errorFor('profile.connection_with_srilanka')}</p>}</div>
                        <Field label="Identity document (PDF, JPG or PNG; up to 5 MB)" required error={errorFor('id_proof')}><input type="file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" onChange={event => change('id_proof', event.target.files?.[0] || null)} aria-invalid={Boolean(errorFor('id_proof'))} className={`${inputClass} file:mr-4 file:border-0 file:bg-gray-950 file:px-3 file:py-2 file:text-white`} /><span className="mt-2 block text-xs font-normal text-gray-600">Stored privately for committee review.</span></Field>
                        <label className="flex items-start gap-3 border-t border-[#d5c8a6] pt-5 text-sm text-gray-700"><input type="checkbox" checked={data.directory_consent} onChange={event => change('directory_consent', event.target.checked)} className="mt-0.5 border-[#a99a79] text-[#800000] focus:ring-[#800000]" /><span>After approval, show my profile in the private member directory. I can change this later.</span></label>
                        <div><label className="flex items-start gap-3 border-t border-[#d5c8a6] pt-5 text-sm text-gray-700"><input type="checkbox" checked={data.accept_declaration} onChange={event => change('accept_declaration', event.target.checked)} aria-invalid={Boolean(errorFor('accept_declaration'))} className="mt-0.5 border-[#a99a79] text-[#800000] focus:ring-[#800000]" /><span>I confirm that the information I provided is accurate and understand that membership is subject to Executive Committee approval. <span className="text-[#800000]">*</span></span></label>{errorFor('accept_declaration') && <p role="alert" className="mt-2 text-sm text-red-700">{errorFor('accept_declaration')}</p>}</div>
                    </section>}

                    <div className="mt-10 flex items-center justify-between gap-4 border-t border-[#cbbb93] pt-6">{step > 0 ? <button type="button" onClick={() => { setClientErrors({}); setStep(step - 1); }} className="inline-flex items-center gap-2 text-sm font-bold text-gray-700 hover:text-[#800000]"><ArrowLeft size={17} /> Back</button> : <span />}{step < 3 ? <button type="button" onClick={() => validateStep(step) && setStep(step + 1)} className="inline-flex items-center gap-2 bg-gray-950 px-6 py-3 text-sm font-bold text-white hover:bg-[#800000]">Continue <ArrowRight size={17} /></button> : <button type="submit" disabled={processing} className="inline-flex items-center gap-2 bg-gray-950 px-6 py-3 text-sm font-bold text-white hover:bg-[#800000] disabled:opacity-50">{processing ? 'Submitting…' : 'Submit application'} <ArrowRight size={17} /></button>}</div>
                </form>
                <p className="mt-8 text-sm text-gray-600">Already a member? <Link href={route('login')} className="font-bold text-[#800000] underline decoration-[#b7a676] underline-offset-4">Sign in</Link></p>
            </div>
        </div>
    </main>;
}
