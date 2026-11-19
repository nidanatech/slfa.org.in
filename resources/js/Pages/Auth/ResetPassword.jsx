import { useForm } from '@inertiajs/react';
import { ArrowRight, LockKeyhole, Mail } from 'lucide-react';
import AuthSplitLayout from '@/Layouts/AuthSplitLayout';
import InputError from '@/Components/InputError';

const fieldClass = 'block w-full rounded-none border border-[#cbbb93] bg-white/70 py-3.5 pl-12 pr-4 text-gray-950 placeholder:text-gray-500 focus:border-[#800000] focus:ring-1 focus:ring-[#800000]';

export default function ResetPassword({ token, email }) {
    const { data, setData, post, processing, errors, reset } = useForm({ token, email: email || '', password: '', password_confirmation: '' });

    const submit = (event) => {
        event.preventDefault();
        post(route('password.store'), { onFinish: () => reset('password', 'password_confirmation') });
    };

    return <AuthSplitLayout
        headTitle="Choose a new password"
        eyebrow="Account access"
        headline={<>A fresh <em className="font-normal text-[#D4AF37]">start.</em></>}
        story="Choose a strong password to keep your membership and community details secure."
        title="Set a new password"
        description="Use the email address from your reset link and enter a new password."
        backHref={route('login')}
        backLabel="Back to login"
    >
        <form onSubmit={submit} className="space-y-6">
            <div>
                <label htmlFor="email" className="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-gray-800">Email address</label>
                <div className="relative"><Mail className="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-500" aria-hidden="true" /><input id="email" type="email" name="email" autoComplete="username" required value={data.email} onChange={event => setData('email', event.target.value)} className={fieldClass} /></div>
                <InputError message={errors.email} className="mt-2" />
            </div>
            {[["password", "New password"], ["password_confirmation", "Confirm new password"]].map(([key, label]) => <div key={key}>
                <label htmlFor={key} className="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-gray-800">{label}</label>
                <div className="relative"><LockKeyhole className="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-500" aria-hidden="true" /><input id={key} type="password" name={key} autoComplete="new-password" required value={data[key]} onChange={event => setData(key, event.target.value)} className={fieldClass} /></div>
                <InputError message={errors[key]} className="mt-2" />
            </div>)}
            <button type="submit" disabled={processing} className="group flex w-full items-center justify-between bg-gray-950 px-6 py-4 text-sm font-bold uppercase tracking-[0.13em] text-white transition-colors hover:bg-[#800000] disabled:cursor-wait disabled:opacity-60"><span>{processing ? 'Saving…' : 'Save new password'}</span><ArrowRight size={19} aria-hidden="true" className="text-[#D4AF37] transition-transform group-hover:translate-x-1" /></button>
        </form>
    </AuthSplitLayout>;
}
