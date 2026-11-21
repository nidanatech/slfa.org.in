import { Link, useForm } from '@inertiajs/react';
import { ArrowRight, Mail } from 'lucide-react';
import AuthSplitLayout from '@/Layouts/AuthSplitLayout';
import InputError from '@/Components/InputError';

export default function ForgotPassword({ status }) {
    const { data, setData, post, processing, errors } = useForm({ email: '' });

    const submit = (event) => {
        event.preventDefault();
        post(route('password.email'));
    };

    return <AuthSplitLayout
        headTitle="Reset your password"
        eyebrow="Account access"
        headline={<>Find your way <em className="font-normal text-[#1B7A4A]">back.</em></>}
        story="Your place in the SLFA community is here. Request a secure link to choose a new password."
        title="Forgot your password?"
        description="Enter the email address you use for your SLFA account. We’ll send you a reset link if outgoing email is available."
        backHref={route('login')}
        backLabel="Back to login"
        footer={<>Remembered it? <Link href={route('login')} className="font-normal text-[#1B7A4A] underline decoration-[#d8ded2] underline-offset-4 hover:decoration-[#1B7A4A]">Sign in</Link></>}
    >
        {status && <p role="status" className="mb-6 border-l-2 border-[#a45116] bg-white/60 px-4 py-3 text-sm text-gray-800">{status}</p>}
        <form onSubmit={submit} className="space-y-6">
            <div>
                <label htmlFor="email" className="mb-2 block text-xs font-normal uppercase tracking-[0.16em] text-gray-800">Email address</label>
                <div className="relative">
                    <Mail className="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-500" aria-hidden="true" />
                    <input id="email" name="email" type="email" autoComplete="email" autoFocus required value={data.email} onChange={event => setData('email', event.target.value)} placeholder="you@example.com" className="block w-full rounded-none border border-[#d8ded2] bg-white/70 py-3.5 pl-12 pr-4 text-gray-950 placeholder:text-gray-500 focus:border-[#1B7A4A] focus:ring-1 focus:ring-[#1B7A4A]" />
                </div>
                <InputError message={errors.email} className="mt-2" />
            </div>
            <button type="submit" disabled={processing} className="group flex w-full items-center justify-between bg-[#1B7A4A] px-6 py-4 text-sm font-normal uppercase tracking-[0.13em] text-white transition-colors hover:bg-[#1B7A4A] disabled:cursor-wait disabled:opacity-60"><span>{processing ? 'Sending…' : 'Send reset link'}</span><ArrowRight size={19} aria-hidden="true" className="text-[#1B7A4A] transition-transform group-hover:translate-x-1" /></button>
        </form>
    </AuthSplitLayout>;
}
