import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import ApplicationLogo from '@/Components/ApplicationLogo';
import { Head, Link, useForm } from '@inertiajs/react';
import { Lock, Mail, ArrowRight, Sparkles, ArrowLeft } from 'lucide-react';
import { motion } from 'motion/react';

export default function ResetPassword({ token, email }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        token: token,
        email: email,
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('password.store'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <div className="flex min-h-screen w-full font-inter antialiased bg-[#071826]">
            <Head title="Reset Password - SLFA" />

            {/* Left Side - Info & Branding */}
            <div className="hidden w-1/2 flex-col justify-between bg-[#0F4C81] p-16 text-white lg:flex relative overflow-hidden border-r border-white/5">

                <div className="relative z-10">
                    <Link href="/" className="flex items-center group">
                        <ApplicationLogo
                            text="SLFA"
                            textClassName="text-2xl font-bold font-outfit text-white"
                            imgClassName="h-7 group-hover:scale-110 transition-transform"
                        />
                    </Link>
                </div>

                <div className="relative z-10 max-w-xl">
                    <motion.div
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.8 }}
                    >
                        <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#14B8A6]/10 border border-[#14B8A6]/20 text-[#14B8A6] text-xs font-semibold mb-8 uppercase tracking-widest">
                            <Sparkles className="w-3 h-3" />
                            <span>Security Vault</span>
                        </div>
                        <h2 className="text-6xl font-black leading-[0.9] tracking-tighter mb-8 font-outfit">
                            Reset Your<br />
                            <span className="text-transparent bg-clip-text bg-gradient-to-r from-[#14B8A6] via-[#4DA3FF] to-[#14B8A6]">
                                Credentials.
                            </span>
                        </h2>
                        <p className="text-xl text-white/50 leading-relaxed font-inter">
                            Secure your account by choosing a new password. Enter your email and new credentials to finalize the process.
                        </p>
                    </motion.div>
                </div>

                <div className="relative z-10 text-xs text-white/20 font-mono tracking-widest uppercase">
                    &copy; {new Date().getFullYear()} Sri Lankan Forum Association.
                </div>
            </div>

            {/* Right Side - Form */}
            <div className="flex w-full flex-col justify-center bg-[#071826] px-8 py-12 lg:w-1/2 lg:px-24 relative">
                <div className="absolute top-8 right-8 lg:left-24 lg:right-auto">
                    <Link
                        href={route('login')}
                        className="flex items-center gap-2 text-white/40 hover:text-white transition-colors text-sm font-medium group"
                    >
                        <ArrowLeft className="w-4 h-4 group-hover:-translate-x-1 transition-transform" />
                        <span>Back to login</span>
                    </Link>
                </div>

                <div className="mx-auto w-full max-w-md">
                    <div className="mb-12">
                        <motion.div
                            initial={{ opacity: 0, x: -20 }}
                            animate={{ opacity: 1, x: 0 }}
                            transition={{ duration: 0.5, delay: 0.2 }}
                        >
                            <h2 className="text-4xl font-black tracking-tighter text-white mb-4 font-outfit">
                                Reset Password
                            </h2>
                            <p className="text-white/50 font-inter">
                                Define your new security credentials.
                            </p>
                        </motion.div>
                    </div>

                    <form onSubmit={submit} className="space-y-6">
                        <div>
                            <InputLabel htmlFor="email" value="Email Address" className="text-white/70 text-xs uppercase tracking-widest mb-2" />
                            <div className="relative">
                                <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                    <Mail className="h-5 w-5 text-white/30" />
                                </div>
                                <TextInput
                                    id="email"
                                    type="email"
                                    name="email"
                                    value={data.email}
                                    className="block w-full rounded-2xl border-white/10 bg-white/5 py-4 pl-12 pr-4 text-white placeholder-white/20 shadow-sm transition-all duration-200 focus:border-[#14B8A6]/50 focus:ring-0 sm:text-sm"
                                    autoComplete="username"
                                    onChange={(e) => setData('email', e.target.value)}
                                    placeholder="your@email.com"
                                    required
                                />
                            </div>
                            <InputError message={errors.email} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="password" value="New Password" className="text-white/70 text-xs uppercase tracking-widest mb-2" />
                            <div className="relative">
                                <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                    <Lock className="h-5 w-5 text-white/30" />
                                </div>
                                <TextInput
                                    id="password"
                                    type="password"
                                    name="password"
                                    value={data.password}
                                    className="block w-full rounded-2xl border-white/10 bg-white/5 py-4 pl-12 pr-4 text-white placeholder-white/20 shadow-sm transition-all duration-200 focus:border-[#14B8A6]/50 focus:ring-0 sm:text-sm"
                                    autoComplete="new-password"
                                    isFocused={true}
                                    onChange={(e) => setData('password', e.target.value)}
                                    placeholder="••••••••"
                                    required
                                />
                            </div>
                            <InputError message={errors.password} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="password_confirmation" value="Confirm New Password" className="text-white/70 text-xs uppercase tracking-widest mb-2" />
                            <div className="relative">
                                <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                    <Lock className="h-5 w-5 text-white/30" />
                                </div>
                                <TextInput
                                    type="password"
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    value={data.password_confirmation}
                                    className="block w-full rounded-2xl border-white/10 bg-white/5 py-4 pl-12 pr-4 text-white placeholder-white/20 shadow-sm transition-all duration-200 focus:border-[#14B8A6]/50 focus:ring-0 sm:text-sm"
                                    autoComplete="new-password"
                                    onChange={(e) =>
                                        setData('password_confirmation', e.target.value)
                                    }
                                    placeholder="••••••••"
                                    required
                                />
                            </div>
                            <InputError
                                message={errors.password_confirmation}
                                className="mt-2"
                            />
                        </div>

                        <button
                            type="submit"
                            className="group w-full py-4 rounded-2xl bg-[#14B8A6] text-white font-bold font-outfit hover:bg-[#0f9687] transition-all shadow-lg shadow-[#14B8A6]/20 hover:shadow-xl hover:-translate-y-0.5 disabled:opacity-50 disabled:translate-y-0 flex items-center justify-center gap-2 tracking-wide mt-4"
                            disabled={processing}
                        >
                            <span>Reset Password</span>
                            <ArrowRight className="h-5 w-5 transition-transform group-hover:translate-x-1" />
                        </button>
                    </form>
                </div>
            </div>
        </div>
    );
}

