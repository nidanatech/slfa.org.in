import { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, Check } from 'lucide-react';
import BrandMark from '@/Components/BrandMark';

const steps = ['Membership', 'Your details', 'People', 'Review'];
const connections = ['Origin', 'Ancestral', 'Trade / Employment', 'Education', 'Spouse is Sri Lankan'];
const newRepresentative = () => ({ name: '', designation: '', email: '', contact_number: '', date_of_birth: '', gender: '' });
const inputClass = 'mt-2 block w-full rounded-none border border-[#d8ded2] bg-white/70 px-4 py-3 text-gray-950 placeholder:text-gray-500 focus:border-[#1B7A4A] focus:ring-1 focus:ring-[#1B7A4A]';
const phonePattern = /^\+?[0-9][0-9\s().-]{6,24}$/;

function Field({ label, error, required = false, children }) {
    return <div><label className="block text-sm font-normal text-gray-950">{label}{required && <span className="ml-1 text-[#1B7A4A]" aria-label="required">*</span>}{children}</label>{error && <p role="alert" className="mt-1.5 text-sm text-red-700">{error}</p>}</div>;
}

export default function Register() {
    const [step, setStep] = useState(0);
    const [clientErrors, setClientErrors] = useState({});
    const { data, setData, post, processing, errors, clearErrors, reset } = useForm({
        membership_type: '', name: '', email: '', password: '', password_confirmation: '',
        profile: {
            address: '', city: '', postcode: '', mobile: '', whatsapp: '', landline: '', personal_email: '',
            date_of_birth: '', gender: '', occupation: '', business_name: '',
            nature_of_business: 'Company', date_of_incorporation: '', connection_with_srilanka: [],
            referred_by: '', referrer_contact: '',
        },
        family_members: [], business_reps: [], photograph: null, id_proof: null,
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
            setData({
                ...data,
                membership_type: type,
                name: type === 'business' ? '' : data.name,
                photograph: type === 'business' ? null : data.photograph,
                profile: { ...data.profile, connection_with_srilanka: type === 'business' ? [] : data.profile.connection_with_srilanka },
                business_reps: type === 'business' ? [newRepresentative(), newRepresentative()] : [],
            });
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
            if (data.membership_type === 'individual' && data.name.trim().length < 2) found.name = 'Enter the applicant’s full name.';
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email.trim())) found.email = 'Enter a valid email address.';
            if (data.password.length < 10 || !/[a-z]/.test(data.password) || !/[A-Z]/.test(data.password) || !/[0-9]/.test(data.password)) found.password = 'Use at least 10 characters with upper and lower case letters and a number.';
            if (data.password !== data.password_confirmation) found.password_confirmation = 'The passwords do not match.';
            if (data.profile.address.trim().length < 8) found['profile.address'] = 'Enter a complete address.';
            if (!phonePattern.test(data.profile.mobile.trim())) found['profile.mobile'] = 'Enter a valid phone number with country code if needed.';
            for (const key of ['whatsapp', 'landline']) if (data.profile[key] && !phonePattern.test(data.profile[key].trim())) found[`profile.${key}`] = 'Enter a valid phone number.';
            if (data.profile.referrer_contact && !phonePattern.test(data.profile.referrer_contact.trim())) found['profile.referrer_contact'] = 'Enter a valid phone number.';
            if (data.membership_type === 'individual') {
                const cutoff = new Date(); cutoff.setFullYear(cutoff.getFullYear() - 18);
                if (!data.profile.date_of_birth || new Date(`${data.profile.date_of_birth}T12:00:00`) > cutoff) found['profile.date_of_birth'] = 'Applicants must be at least 18 years old.';
                if (!data.profile.gender) found['profile.gender'] = 'Select a gender.';
                if (!data.profile.occupation.trim()) found['profile.occupation'] = 'Enter your occupation.';
                if (!data.profile.city.trim()) found['profile.city'] = 'Enter your city.';
                if (!data.profile.postcode.trim()) found['profile.postcode'] = 'Enter your postcode.';
            } else {
                if (!data.profile.business_name.trim()) found['profile.business_name'] = 'Enter the registered business name.';
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
                    if (!person.date_of_birth) found[`business_reps.${index}.date_of_birth`] = 'Enter the representative’s date of birth.';
                    if (!person.gender) found[`business_reps.${index}.gender`] = 'Select the representative’s gender.';
                });
            } else if (!data.photograph) found.photograph = 'Upload a passport-size photograph.';
            else if (!['image/jpeg', 'image/png', 'image/webp'].includes(data.photograph.type) || data.photograph.size > 3 * 1024 * 1024) found.photograph = 'Upload a JPG, PNG, or WebP image up to 3 MB.';
        }
        if (number === 3) {
            if (data.membership_type === 'individual' && !data.profile.connection_with_srilanka.length) found['profile.connection_with_srilanka'] = 'Select at least one connection.';
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
                setStep(first === 'membership_type' ? 0 : /^(name|email|password|profile\.(address|city|postcode|mobile|whatsapp|landline|personal_email|date_of_birth|gender|occupation|business_name|nature_of_business|date_of_incorporation|referred_by|referrer_contact))/.test(first) ? 1 : /^(family_members|business_reps)/.test(first) ? 2 : 3);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    const textInput = (key, value, onChange, type = 'text', extra = {}) => <input type={type} value={value} onChange={event => onChange(event.target.value)} aria-invalid={Boolean(errorFor(key))} className={inputClass} {...extra} />;
    const profileInput = (key, type = 'text', extra = {}) => textInput(`profile.${key}`, data.profile[key], value => changeProfile(key, value), type, extra);
    const personInput = (kind, index, key, type = 'text') => textInput(`${kind}.${index}.${key}`, data[kind][index][key], value => changePerson(kind, index, key, value), type);

    return <main className="slfa-brand brand-auth grid min-h-screen bg-[#FAF7F2] font-lato lg:grid-cols-[minmax(0,0.78fr)_minmax(0,1.22fr)]">
        <Head title="Membership application" />
        <aside className="relative hidden min-h-screen flex-col justify-between brand-auth-story overflow-hidden bg-[#f3eddf] px-10 py-10 text-[#1f2937] lg:flex xl:px-16 xl:py-14">


            <Link href={route('home')} className="relative z-10 w-fit"><BrandMark /></Link>
            <div className="relative z-10 max-w-lg">
                <p className="text-xs font-normal uppercase tracking-[0.28em] text-[#1B7A4A]">Membership</p>
                <h1 className="mt-6 font-lato text-6xl font-normal leading-[1.04]">Find your place in the <em className="font-normal text-[#1B7A4A]">community.</em></h1>
                <div className="mt-8 h-px w-20 bg-[#1B7A4A]" aria-hidden="true" />
                <p className="mt-7 text-lg leading-relaxed text-gray-600">For adults living in South India with a connection to Sri Lanka. Applications are reviewed by the SLFA Executive Committee.</p>
                <p className="mt-5 text-sm leading-relaxed text-gray-600">This form does not collect payment. Fees are due only as directed by the association.</p>
            </div>
            <img src="/images/hero/community.webp" alt="" width="640" height="640" className="brand-auth-illustration" />
            <p className="relative z-10 text-xs uppercase tracking-[0.17em] text-gray-400">Sri Lankan Friendship Association of South India</p>
        </aside>

        <div className="min-w-0 px-5 py-7 sm:px-10 sm:py-10 xl:px-20 xl:py-14">
            <div className="flex items-center justify-between gap-4"><Link href={route('home')} className="inline-flex items-center gap-2 text-xs font-normal uppercase tracking-[0.15em] text-gray-600 hover:text-[#1B7A4A]"><ArrowLeft size={17} /> Back to website</Link><div className="lg:hidden"><BrandMark compact /></div></div>
            <div className="mx-auto mt-12 max-w-2xl pb-12 sm:mt-16">
                <p className="text-xs font-normal uppercase tracking-[0.25em] text-[#a45116]">Apply to join SLFA</p>
                <h2 className="mt-3 font-lato text-4xl font-normal text-gray-950 sm:text-5xl">Membership application</h2>
                <p className="mt-3 text-sm leading-relaxed text-gray-600">Complete four short steps. Fields marked * are required.</p>
                <ol aria-label="Application progress" className="mt-9 grid grid-cols-4 gap-2 border-b border-[#d8ded2] pb-4">{steps.map((label, index) => <li key={label} aria-current={step === index ? 'step' : undefined} className={`text-xs font-normal ${step === index ? 'text-[#1B7A4A]' : index < step ? 'text-[#a45116]' : 'text-gray-500'}`}><span className="mb-1 block font-lato text-xl">{index < step ? <Check size={18} /> : `0${index + 1}`}</span>{index === 2 && data.membership_type === 'individual' ? 'Photograph' : label}</li>)}</ol>
                {Object.keys(errors).length > 0 && <p role="alert" className="mt-5 border-l-2 border-red-700 bg-red-50 p-3 text-sm text-red-800">Please correct the highlighted fields before submitting.</p>}

                <form onSubmit={submit} className="mt-8" noValidate>
                    {step === 0 && <section aria-labelledby="step-title" className="space-y-6"><div><h3 id="step-title" className="font-lato text-3xl font-normal text-gray-950">Choose your membership</h3><p className="mt-2 text-sm text-gray-600">Select the option that describes your application.</p></div><div className="grid gap-4 sm:grid-cols-2">{[['individual', 'Individual', 'For an adult with a Sri Lankan connection living in South India.', '₹5,000 registration · ₹4,000 yearly'], ['business', 'Business', 'For an organisation represented by two authorised people.', '₹5,000 registration · ₹4,000 per person yearly']].map(([type, title, description, fee]) => <button key={type} type="button" onClick={() => chooseType(type)} aria-pressed={data.membership_type === type} className={`border-t p-5 text-left transition-colors hover:bg-white/60 ${data.membership_type === type ? 'border-[#1B7A4A] bg-white/60' : 'border-[#d8ded2]'}`}><span className="font-lato text-2xl font-normal text-gray-950">{title}</span><span className="mt-3 block text-sm leading-relaxed text-gray-600">{description}</span><span className="mt-5 block text-xs font-normal text-[#a45116]">{fee}</span></button>)}</div>{errorFor('membership_type') && <p role="alert" className="text-sm text-red-700">{errorFor('membership_type')}</p>}</section>}

                    {step === 1 && <section aria-labelledby="step-title" className="space-y-6"><div><h3 id="step-title" className="font-lato text-3xl font-normal text-gray-950">{data.membership_type === 'business' ? 'Business & contact details' : 'Your details'}</h3><p className="mt-2 text-sm text-gray-600">Your email will be used to sign in and receive application updates.</p></div>
                        {data.membership_type === 'business' && <div className="grid gap-5 sm:grid-cols-2"><Field label="Business name" required error={errorFor('profile.business_name')}>{profileInput('business_name', 'text', { autoComplete: 'organization' })}</Field><Field label="Nature of business" required error={errorFor('profile.nature_of_business')}><select value={data.profile.nature_of_business} onChange={event => changeProfile('nature_of_business', event.target.value)} className={inputClass}>{['Company', 'LLP', 'Society', 'Firm', 'Others'].map(option => <option key={option}>{option}</option>)}</select></Field></div>}
                        {data.membership_type === 'individual' && <><Field label="Full name" required error={errorFor('name')}>{textInput('name', data.name, value => change('name', value), 'text', { autoComplete: 'name' })}</Field><div className="grid gap-5 sm:grid-cols-2"><Field label="Date of birth" required error={errorFor('profile.date_of_birth')}>{profileInput('date_of_birth', 'date')}</Field><Field label="Gender" required error={errorFor('profile.gender')}><select value={data.profile.gender} onChange={event => changeProfile('gender', event.target.value)} className={inputClass}><option value="">Choose</option>{['Male', 'Female', 'Others'].map(option => <option key={option}>{option}</option>)}</select></Field><Field label="Occupation" required error={errorFor('profile.occupation')}>{profileInput('occupation')}</Field></div></>}
                        <Field label={data.membership_type === 'business' ? 'Registered office address' : 'Residential address'} required error={errorFor('profile.address')}><textarea value={data.profile.address} onChange={event => changeProfile('address', event.target.value)} aria-invalid={Boolean(errorFor('profile.address'))} rows={3} autoComplete="street-address" className={inputClass} /></Field>
                        {data.membership_type === 'individual' && <div className="grid gap-5 sm:grid-cols-2"><Field label="City" required error={errorFor('profile.city')}>{profileInput('city')}</Field><Field label="Postcode" required error={errorFor('profile.postcode')}>{profileInput('postcode')}</Field></div>}
                        <div className="grid gap-5 sm:grid-cols-2"><Field label="Landline number" error={errorFor('profile.landline')}>{profileInput('landline', 'tel', { placeholder: 'Optional' })}</Field><Field label="Mobile number" required error={errorFor('profile.mobile')}>{profileInput('mobile', 'tel', { autoComplete: 'tel', placeholder: '+91 98765 43210' })}</Field>{data.membership_type === 'individual' && <Field label="WhatsApp number" error={errorFor('profile.whatsapp')}>{profileInput('whatsapp', 'tel', { placeholder: 'Optional' })}</Field>}</div>
                        <Field label="Email address" required error={errorFor('email')}>{textInput('email', data.email, value => change('email', value), 'email', { autoComplete: 'email' })}</Field>
                        {data.membership_type === 'individual' && <div className="grid gap-5 sm:grid-cols-2"><Field label="Referred by" error={errorFor('profile.referred_by')}>{profileInput('referred_by', 'text', { placeholder: 'Optional' })}</Field><Field label="Referrer contact number" error={errorFor('profile.referrer_contact')}>{profileInput('referrer_contact', 'tel', { placeholder: 'Optional' })}</Field></div>}
                        <div className="grid gap-5 sm:grid-cols-2"><Field label="Create password" required error={errorFor('password')}>{textInput('password', data.password, value => change('password', value), 'password', { autoComplete: 'new-password' })}</Field><Field label="Confirm password" required error={errorFor('password_confirmation')}>{textInput('password_confirmation', data.password_confirmation, value => change('password_confirmation', value), 'password', { autoComplete: 'new-password' })}</Field></div><p className="text-xs text-gray-600">Use at least 10 characters, including upper and lower case letters and a number.</p>
                    </section>}

                    {step === 2 && <section aria-labelledby="step-title" className="space-y-6"><div><h3 id="step-title" className="font-lato text-3xl font-normal text-gray-950">{data.membership_type === 'business' ? 'Authorised representatives' : 'Applicant photograph'}</h3><p className="mt-2 text-sm text-gray-600">{data.membership_type === 'business' ? 'Provide the two people authorised to represent this organisation.' : 'Upload a recent passport-size photograph for committee review.'}</p></div>
                        {data.membership_type === 'business' ? data.business_reps.map((person, index) => <div key={index} className="space-y-4 border-t border-[#d8ded2] pt-5"><h4 className="font-lato text-2xl font-normal text-gray-950">Representative {index + 1}</h4><div className="grid gap-5 sm:grid-cols-2"><Field label="Full name" required error={errorFor(`business_reps.${index}.name`)}>{personInput('business_reps', index, 'name')}</Field><Field label="Date of birth" required error={errorFor(`business_reps.${index}.date_of_birth`)}>{personInput('business_reps', index, 'date_of_birth', 'date')}</Field><Field label="Gender" required error={errorFor(`business_reps.${index}.gender`)}><select value={person.gender} onChange={event => changePerson('business_reps', index, 'gender', event.target.value)} className={inputClass}><option value="">Choose</option>{['Male', 'Female', 'Others'].map(option => <option key={option}>{option}</option>)}</select></Field><Field label="Designation" required error={errorFor(`business_reps.${index}.designation`)}>{personInput('business_reps', index, 'designation')}</Field><Field label="Contact number" required error={errorFor(`business_reps.${index}.contact_number`)}>{personInput('business_reps', index, 'contact_number', 'tel')}</Field><Field label="Email address" required error={errorFor(`business_reps.${index}.email`)}>{personInput('business_reps', index, 'email', 'email')}</Field></div></div>) : <Field label="Passport-size photograph (JPG, PNG or WebP; up to 3 MB)" required error={errorFor('photograph')}><input type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" onChange={event => change('photograph', event.target.files?.[0] || null)} aria-invalid={Boolean(errorFor('photograph'))} className={`${inputClass} file:mr-4 file:border-0 file:bg-[#1B7A4A] file:px-3 file:py-2 file:text-white`} /></Field>}
                    </section>}

                    {step === 3 && <section aria-labelledby="step-title" className="space-y-7"><div><h3 id="step-title" className="font-lato text-3xl font-normal text-gray-950">Review & declaration</h3><p className="mt-2 text-sm text-gray-600">Upload the documents required for committee review and confirm your declaration.</p></div>
                        <div className="border-y border-[#d8ded2] py-5 text-sm text-gray-700"><p><strong>Membership:</strong> {data.membership_type === 'business' ? 'Business' : 'Individual'}</p><p className="mt-1"><strong>Applicant:</strong> {data.membership_type === 'business' ? data.profile.business_name : data.name}</p><p className="mt-1"><strong>Email:</strong> {data.email}</p><p className="mt-3"><strong>Fees:</strong> {data.membership_type === 'business' ? '₹5,000 one-time registration and ₹4,000 annual membership per representative' : '₹5,000 one-time registration and ₹4,000 annual membership'}. The annual fee is due on or before 30 June. No payment is collected with this form.</p></div>
                        {data.membership_type === 'individual' && <div><p className="text-sm font-normal text-gray-950">Your connection with Sri Lanka <span className="text-[#1B7A4A]">*</span></p><div className="mt-3 grid gap-2 sm:grid-cols-2">{connections.map(option => <label key={option} className="flex cursor-pointer items-center gap-3 border border-[#d8ded2] px-4 py-3 text-sm text-gray-800"><input type="checkbox" checked={data.profile.connection_with_srilanka.includes(option)} onChange={() => changeProfile('connection_with_srilanka', data.profile.connection_with_srilanka.includes(option) ? data.profile.connection_with_srilanka.filter(value => value !== option) : [...data.profile.connection_with_srilanka, option])} className="border-[#a99a79] text-[#1B7A4A] focus:ring-[#1B7A4A]" />{option}</label>)}</div>{errorFor('profile.connection_with_srilanka') && <p role="alert" className="mt-2 text-sm text-red-700">{errorFor('profile.connection_with_srilanka')}</p>}</div>}
                        <Field label="Identity document (PDF, JPG or PNG; up to 5 MB)" required error={errorFor('id_proof')}><input type="file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" onChange={event => change('id_proof', event.target.files?.[0] || null)} aria-invalid={Boolean(errorFor('id_proof'))} className={`${inputClass} file:mr-4 file:border-0 file:bg-[#1B7A4A] file:px-3 file:py-2 file:text-white`} /><span className="mt-2 block text-xs font-normal text-gray-600">Stored privately for committee review.</span></Field>
                        <label className="flex items-start gap-3 border-t border-[#d8ded2] pt-5 text-sm text-gray-700"><input type="checkbox" checked={data.directory_consent} onChange={event => change('directory_consent', event.target.checked)} className="mt-0.5 border-[#a99a79] text-[#1B7A4A] focus:ring-[#1B7A4A]" /><span>After approval, show my profile in the private member directory. I can change this later.</span></label>
                        <div><label className="flex items-start gap-3 border-t border-[#d8ded2] pt-5 text-sm text-gray-700"><input type="checkbox" checked={data.accept_declaration} onChange={event => change('accept_declaration', event.target.checked)} aria-invalid={Boolean(errorFor('accept_declaration'))} className="mt-0.5 border-[#a99a79] text-[#1B7A4A] focus:ring-[#1B7A4A]" /><span>I declare that the information given is true and correct to the best of my knowledge. I understand that membership is subject to Executive Committee review and approval in accordance with the Society’s Memorandum and Bye-laws. <span className="text-[#1B7A4A]">*</span></span></label>{errorFor('accept_declaration') && <p role="alert" className="mt-2 text-sm text-red-700">{errorFor('accept_declaration')}</p>}</div>
                    </section>}

                    <div className="mt-10 flex items-center justify-between gap-4 border-t border-[#d8ded2] pt-6">{step > 0 ? <button type="button" onClick={() => { setClientErrors({}); setStep(step - 1); }} className="inline-flex items-center gap-2 text-sm font-normal text-gray-700 hover:text-[#1B7A4A]"><ArrowLeft size={17} /> Back</button> : <span />}{step < 3 ? <button type="button" onClick={() => validateStep(step) && setStep(step + 1)} className="inline-flex items-center gap-2 bg-[#1B7A4A] px-6 py-3 text-sm font-normal text-white hover:bg-[#1B7A4A]">Continue <ArrowRight size={17} /></button> : <button type="submit" disabled={processing} className="inline-flex items-center gap-2 bg-[#1B7A4A] px-6 py-3 text-sm font-normal text-white hover:bg-[#1B7A4A] disabled:opacity-50">{processing ? 'Submitting…' : 'Submit application'} <ArrowRight size={17} /></button>}</div>
                </form>
                <p className="mt-8 text-sm text-gray-600">Already a member? <Link href={route('login')} className="font-normal text-[#1B7A4A] underline decoration-[#d8ded2] underline-offset-4">Sign in</Link></p>
            </div>
        </div>
    </main>;
}
