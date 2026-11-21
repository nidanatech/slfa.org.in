import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, LockKeyhole, Mail } from 'lucide-react';
import BrandMark from '@/Components/BrandMark';
import InputError from '@/Components/InputError';

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (event) => {
        event.preventDefault();
        post(route('login'), { onFinish: () => reset('password') });
    };

    return (
        <main className="slfa-brand brand-auth grid min-h-screen bg-[#FAF7F2] font-lato lg:grid-cols-[minmax(0,1fr)_minmax(0,0.9fr)]">
            <Head title="Member login" />

            <div className="relative hidden min-h-screen flex-col justify-between brand-auth-story overflow-hidden bg-[#f3eddf] px-10 py-10 text-[#1f2937] lg:flex xl:px-16 xl:py-14">



                <Link href={route('home')} className="relative z-10 w-fit focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#1B7A4A]"><BrandMark /></Link>
                <div className="relative z-10 max-w-xl pb-12">
                    <p className="text-xs font-normal uppercase tracking-[0.28em] text-[#1B7A4A]">The SLFA community</p>
                    <h1 className="mt-6 font-lato text-6xl font-normal leading-[1.02] xl:text-7xl">Your place in the <em className="font-normal text-[#1B7A4A]">community.</em></h1>
                    <div className="mt-8 h-px w-20 bg-[#1B7A4A]" aria-hidden="true" />
                    <p className="mt-8 max-w-md text-lg leading-relaxed text-gray-600">Sign in to manage your membership, update your profile, and stay connected with SLFA.</p>
                </div>
                <img src="/images/hero/community.webp" alt="" width="640" height="640" className="brand-auth-illustration" />
            <p className="relative z-10 text-xs uppercase tracking-[0.17em] text-gray-400">Sri Lankan Friendship Association of South India</p>
            </div>

            <div className="flex min-h-screen flex-col px-5 py-7 sm:px-10 sm:py-10 xl:px-20 xl:py-14">
                <div className="flex items-center justify-between gap-4">
                    <Link href={route('home')} className="inline-flex items-center gap-2 text-xs font-normal uppercase tracking-[0.15em] text-gray-600 transition-colors hover:text-[#1B7A4A] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#1B7A4A]"><ArrowLeft size={17} aria-hidden="true" /> Back to website</Link>
                    <div className="lg:hidden"><BrandMark compact /></div>
                </div>

                <div className="mx-auto flex w-full max-w-[29rem] flex-1 flex-col justify-center py-14">
                    <p className="text-xs font-normal uppercase tracking-[0.25em] text-[#a45116]">Member portal</p>
                    <h2 className="mt-4 font-lato text-5xl font-normal leading-tight text-gray-950 sm:text-6xl">Welcome back</h2>
                    <p className="mt-3 text-base leading-relaxed text-gray-600">Enter your details to continue to your account.</p>
                    <div className="mt-8 h-px w-full bg-[#d8ded2]" aria-hidden="true" />

                    {status && <div role="status" className="mt-7 border-l-2 border-[#a45116] bg-white/60 px-4 py-3 text-sm text-gray-800">{status}</div>}

                    <form onSubmit={submit} className="mt-8 space-y-6">
                        <div>
                            <label htmlFor="email" className="mb-2 block text-xs font-normal uppercase tracking-[0.16em] text-gray-800">Email address</label>
                            <div className="relative">
                                <Mail className="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-500" aria-hidden="true" />
                                <input id="email" name="email" type="email" autoComplete="username" autoFocus required value={data.email} onChange={event => setData('email', event.target.value)} placeholder="you@example.com" className="block w-full rounded-none border border-[#d8ded2] bg-white/70 py-3.5 pl-12 pr-4 text-gray-950 placeholder:text-gray-500 focus:border-[#1B7A4A] focus:ring-1 focus:ring-[#1B7A4A]" />
                            </div>
                            <InputError message={errors.email} className="mt-2" />
                        </div>

                        <div>
                            <div className="mb-2 flex items-center justify-between gap-4">
                                <label htmlFor="password" className="text-xs font-normal uppercase tracking-[0.16em] text-gray-800">Password</label>
                                {canResetPassword && <Link href={route('password.request')} className="text-sm font-normal text-[#1B7A4A] underline-offset-4 hover:underline">Forgot password?</Link>}
                            </div>
                            <div className="relative">
                                <LockKeyhole className="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-500" aria-hidden="true" />
                                <input id="password" name="password" type="password" autoComplete="current-password" required value={data.password} onChange={event => setData('password', event.target.value)} placeholder="Enter your password" className="block w-full rounded-none border border-[#d8ded2] bg-white/70 py-3.5 pl-12 pr-4 text-gray-950 placeholder:text-gray-500 focus:border-[#1B7A4A] focus:ring-1 focus:ring-[#1B7A4A]" />
                            </div>
                            <InputError message={errors.password} className="mt-2" />
                        </div>

                        <label className="inline-flex cursor-pointer items-center gap-3 text-sm text-gray-700"><input type="checkbox" name="remember" checked={data.remember} onChange={event => setData('remember', event.target.checked)} className="rounded border-[#a99a79] text-[#1B7A4A] focus:ring-[#1B7A4A]" /> Keep me signed in</label>

                        <button type="submit" disabled={processing} className="group flex w-full items-center justify-between bg-[#1B7A4A] px-6 py-4 text-sm font-normal uppercase tracking-[0.13em] text-white transition-colors hover:bg-[#1B7A4A] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#1B7A4A] disabled:cursor-wait disabled:opacity-60"><span>{processing ? 'Signing in…' : 'Sign in'}</span><ArrowRight size={19} aria-hidden="true" className="text-[#1B7A4A] transition-transform group-hover:translate-x-1" /></button>
                    </form>

                    <p className="mt-9 text-sm text-gray-600">New to SLFA? <Link href={route('register')} className="font-normal text-[#1B7A4A] underline decoration-[#d8ded2] underline-offset-4 hover:decoration-[#1B7A4A]">Apply for membership</Link></p>
                </div>
            </div>
        </main>
    );
}
