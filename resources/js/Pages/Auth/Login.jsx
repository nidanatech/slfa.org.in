import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import ApplicationLogo from '@/Components/ApplicationLogo';
import BrandMark from '@/Components/BrandMark';

import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';
import { Mail, Lock, ArrowRight, Sparkles, ArrowLeft } from 'lucide-react';
import { motion } from 'motion/react';

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };
    return (
        <div className="flex min-h-screen w-full font-lato antialiased bg-slfa-cream">
            <Head title="Log in - SLFA" />

            {/* Left Side - Info & Branding */}
            <div 
                className="hidden w-1/2 flex-col justify-between p-16 text-white lg:flex relative overflow-hidden shadow-2xl z-10"
                style={{ background: 'linear-gradient(135deg, #0d1e3d 0%, #1a365d 100%)' }}
            >
                {/* Cultural background pattern/watermark */}
                <div className="absolute inset-0 bg-slfa-navy mix-blend-multiply opacity-50"></div>
                <div className="absolute inset-0 flex items-center justify-center opacity-10 pointer-events-none font-abhaya text-[15rem] font-bold" aria-hidden="true">SLFA</div>
                
                {/* Decorative Elements */}
                <div className="absolute top-0 right-0 w-64 h-64 bg-slfa-primary rounded-full blur-[100px] opacity-20 mix-blend-screen"></div>
                <div className="absolute bottom-0 left-0 w-64 h-64 bg-slfa-accent rounded-full blur-[100px] opacity-20 mix-blend-screen"></div>

                <div className="relative z-10">
                    <Link href="/" className="flex items-center group">
                        <BrandMark />
                    </Link>
                </div>

                <div className="relative z-10 max-w-xl mt-12">
                    <motion.div
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.8 }}
                    >
                        <div className="flex items-center gap-3 mb-6">
                            <div className="w-8 h-px bg-slfa-primary" />
                            <span className="text-slfa-primary text-xs uppercase tracking-[0.2em] font-bold">
                                Member Portal
                            </span>
                        </div>
                        <h2 className="text-5xl sm:text-6xl lg:text-[4.5rem] font-abhaya font-extrabold text-white leading-[1.08] tracking-tight mb-6">
                            Welcome back to<br />
                            <span className="text-transparent bg-clip-text bg-gradient-to-r from-slfa-primary to-slfa-accent">SLFA</span>.
                        </h2>
                        <div className="kolam-divider text-slfa-primary mb-8 justify-start lg:justify-start"><span className="kolam-dot"></span></div>
                        <p className="text-white/80 font-lato text-lg font-light leading-relaxed max-w-md mb-10">
                            Log in to access your dashboard, connect with the community, and manage your membership.
                        </p>
                    </motion.div>
                </div>

                <div className="relative z-10 text-xs text-white/40 font-lato tracking-widest uppercase mt-auto">
                    &copy; {new Date().getFullYear()} Srilankan Friendship Association of South India.
                </div>
            </div>

            {/* Right Side - Login Form */}
            <div className="flex w-full flex-col justify-center bg-white px-8 py-12 lg:w-1/2 lg:px-24 relative shadow-[-20px_0_40px_rgba(0,0,0,0.05)] z-20">
                <div className="absolute top-8 right-8 lg:left-12 lg:right-auto">
                    <Link
                        href="/"
                        className="flex items-center gap-2 text-slfa-navy/60 hover:text-slfa-primary transition-colors text-sm font-bold tracking-wide uppercase font-lato group"
                    >
                        <ArrowLeft className="w-4 h-4 group-hover:-translate-x-1 transition-transform" />
                        <span>Back to website</span>
                    </Link>
                </div>

                <div className="mx-auto w-full max-w-md mt-12 lg:mt-0">
                    <div className="mb-12">
                        <motion.div
                            initial={{ opacity: 0, x: -20 }}
                            animate={{ opacity: 1, x: 0 }}
                            transition={{ duration: 0.5, delay: 0.2 }}
                        >
                            <h2 className="text-4xl font-abhaya font-extrabold text-slfa-navy mb-3 tracking-tight">
                                Member Portal
                            </h2>
                            <p className="text-gray-500 font-lato text-lg font-light">
                                Log in to manage your account and connect.
                            </p>
                        </motion.div>
                    </div>

                    {status && (
                        <div className="mb-6 rounded-2xl bg-slfa-primary/10 p-4 text-sm font-bold font-lato text-slfa-primary border border-slfa-primary/20">
                            {status}
                        </div>
                    )}

                    <form onSubmit={submit} className="space-y-6">
                        <div>
                            <InputLabel htmlFor="email" value="Email Address" className="text-slfa-navy text-xs uppercase tracking-widest mb-2 font-bold font-lato" />
                            <div className="relative">
                                <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                    <Mail className="h-5 w-5 text-gray-400" />
                                </div>
                                <TextInput
                                    id="email"
                                    type="email"
                                    name="email"
                                    value={data.email}
                                    className="block w-full rounded-xl border-gray-200 bg-gray-50 py-4 pl-12 pr-4 text-slfa-navy placeholder-gray-400 shadow-sm transition-all duration-300 focus:border-slfa-primary focus:ring-1 focus:ring-slfa-primary font-lato"
                                    autoComplete="username"
                                    isFocused={true}
                                    onChange={(e) => setData('email', e.target.value)}
                                    placeholder="your@email.com"
                                />
                            </div>
                            <InputError message={errors.email} className="mt-2" />
                        </div>

                        <div>
                            <div className="flex items-center justify-between mb-2">
                                <InputLabel htmlFor="password" value="Password" className="text-slfa-navy text-xs uppercase tracking-widest font-bold font-lato" />
                                {canResetPassword && (
                                    <Link
                                        href={route('password.request')}
                                        className="text-xs font-bold text-slfa-primary hover:text-slfa-accent transition-colors font-lato"
                                    >
                                        Forgot?
                                    </Link>
                                )}
                            </div>
                            <div className="relative">
                                <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                    <Lock className="h-5 w-5 text-gray-400" />
                                </div>
                                <TextInput
                                    id="password"
                                    type="password"
                                    name="password"
                                    value={data.password}
                                    className="block w-full rounded-xl border-gray-200 bg-gray-50 py-4 pl-12 pr-4 text-slfa-navy placeholder-gray-400 shadow-sm transition-all duration-300 focus:border-slfa-primary focus:ring-1 focus:ring-slfa-primary font-lato"
                                    autoComplete="current-password"
                                    onChange={(e) => setData('password', e.target.value)}
                                    placeholder="••••••••"
                                />
                            </div>
                            <InputError message={errors.password} className="mt-2" />
                        </div>

                        <div className="flex items-center">
                            <label className="flex items-center cursor-pointer group">
                                <Checkbox
                                    name="remember"
                                    checked={data.remember}
                                    onChange={(e) => setData('remember', e.target.checked)}
                                    className="h-4 w-4 rounded border-gray-300 bg-white text-slfa-primary focus:ring-slfa-primary"
                                />
                                <span className="ms-3 text-sm text-gray-600 group-hover:text-slfa-navy transition-colors font-lato">Keep me signed in</span>
                            </label>
                        </div>

                        <button
                            type="submit"
                            className="group w-full py-4 rounded-xl bg-gradient-to-r from-slfa-primary to-slfa-accent text-white font-bold font-lato uppercase tracking-wider hover:shadow-lg hover:shadow-slfa-primary/30 transition-all hover:-translate-y-0.5 disabled:opacity-50 disabled:translate-y-0 flex items-center justify-center gap-2 mt-8"
                            disabled={processing}
                        >
                            <span>Sign In</span>
                            <ArrowRight className="h-5 w-5 transition-transform group-hover:translate-x-1" />
                        </button>
                    </form>

                    <p className="mt-10 text-center text-sm font-lato text-gray-500">
                        Need an account?{' '}
                        <Link href={route('register')} className="font-bold text-slfa-navy hover:text-slfa-primary transition-colors border-b border-slfa-primary/30 pb-0.5">
                            Apply for Membership
                        </Link>
                    </p>
                </div>
            </div>
        </div>
    );
}
