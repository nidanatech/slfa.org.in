import { Link, useForm } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import AuthSplitLayout from '@/Layouts/AuthSplitLayout';

export default function VerifyEmail({ status }) {
    const { post, processing } = useForm({});

    const submit = (event) => {
        event.preventDefault();
        post(route('verification.send'));
    };

    return <AuthSplitLayout
        headTitle="Verify your email"
        eyebrow="Account security"
        headline={<>One more <em className="font-normal text-[#1B7A4A]">step.</em></>}
        story="Verify your email address so we can keep your SLFA account connected to you."
        title="Check your inbox"
        description="Open the verification link sent to your email. You can request another link if it has not arrived."
        footer={<Link href={route('logout')} method="post" as="button" className="font-normal text-[#1B7A4A] underline decoration-[#d8ded2] underline-offset-4 hover:decoration-[#1B7A4A]">Log out</Link>}
    >
        {status === 'verification-link-sent' && <p role="status" className="mb-6 border-l-2 border-[#a45116] bg-white/60 px-4 py-3 text-sm text-gray-800">A new verification link has been sent.</p>}
        <form onSubmit={submit}><button type="submit" disabled={processing} className="group flex w-full items-center justify-between bg-[#1B7A4A] px-6 py-4 text-sm font-normal uppercase tracking-[0.13em] text-white transition-colors hover:bg-[#1B7A4A] disabled:cursor-wait disabled:opacity-60"><span>{processing ? 'Sending…' : 'Resend verification email'}</span><ArrowRight size={19} aria-hidden="true" className="text-[#1B7A4A] transition-transform group-hover:translate-x-1" /></button></form>
    </AuthSplitLayout>;
}
