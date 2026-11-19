import { useForm } from '@inertiajs/react';
import { ArrowRight, LockKeyhole } from 'lucide-react';
import AuthSplitLayout from '@/Layouts/AuthSplitLayout';
import InputError from '@/Components/InputError';

export default function ConfirmPassword() {
    const { data, setData, post, processing, errors, reset } = useForm({ password: '' });

    const submit = (event) => {
        event.preventDefault();
        post(route('password.confirm'), { onFinish: () => reset('password') });
    };

    return <AuthSplitLayout
        headTitle="Confirm your password"
        eyebrow="Account security"
        headline={<>Your details, <em className="font-normal text-[#D4AF37]">protected.</em></>}
        story="We ask you to confirm your password before opening sensitive account settings."
        title="Confirm your password"
        description="Enter your current password to continue."
    >
        <form onSubmit={submit} className="space-y-6">
            <div>
                <label htmlFor="password" className="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-gray-800">Current password</label>
                <div className="relative"><LockKeyhole className="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-500" aria-hidden="true" /><input id="password" type="password" name="password" autoComplete="current-password" autoFocus required value={data.password} onChange={event => setData('password', event.target.value)} className="block w-full rounded-none border border-[#cbbb93] bg-white/70 py-3.5 pl-12 pr-4 text-gray-950 focus:border-[#800000] focus:ring-1 focus:ring-[#800000]" /></div>
                <InputError message={errors.password} className="mt-2" />
            </div>
            <button type="submit" disabled={processing} className="group flex w-full items-center justify-between bg-gray-950 px-6 py-4 text-sm font-bold uppercase tracking-[0.13em] text-white transition-colors hover:bg-[#800000] disabled:cursor-wait disabled:opacity-60"><span>{processing ? 'Checking…' : 'Continue'}</span><ArrowRight size={19} aria-hidden="true" className="text-[#D4AF37] transition-transform group-hover:translate-x-1" /></button>
        </form>
    </AuthSplitLayout>;
}
