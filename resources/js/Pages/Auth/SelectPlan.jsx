import { Head, router } from '@inertiajs/react';
import { motion } from 'motion/react';
import { useState } from 'react';
import {
    Check,
    Zap,
    Shield,
    Rocket,
    Crown,
    ArrowRight
} from 'lucide-react';


export default function SelectPlan({ plans }) {
    const [billingCycle, setBillingCycle] = useState('monthly');

    const handleSelectPlan = (plan) => {
        router.post(route('checkout.select'), {
            plan_slug: plan.slug,
            billing_cycle: billingCycle
        });
    };

    // Map backend plans to UI display data (icons, colors etc)
    const getPlanDisplay = (slug) => {
        const displayData = {
            'rookie-sidekick': {
                icon: <Rocket className="w-6 h-6" />,
                color: 'emerald',
                tagline: 'Every hero starts somewhere.',
                popular: false
            },
            'trusted-sidekick': {
                icon: <Shield className="w-6 h-6" />,
                color: 'blue',
                tagline: "Your hero's right hand.",
                popular: true
            },
            'elite-sidekick': {
                icon: <Zap className="w-6 h-6" />,
                color: 'purple',
                tagline: 'A force multiplier.',
                popular: false
            },
            'co-hero': {
                icon: <Crown className="w-6 h-6" />,
                color: 'pink',
                tagline: 'Fights alongside you.',
                popular: false
            }
        };
        return displayData[slug] || displayData['rookie-sidekick'];
    };

    return (
        <div className="min-h-screen bg-[#0F4C81] relative overflow-hidden flex flex-col items-center justify-center py-20">
            <Head title="Select Your Plan - SLFA" />

            <div className="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div className="text-center max-w-3xl mx-auto mb-16">
                    <h1 className="text-4xl md:text-5xl font-black text-white mb-6 font-outfit tracking-tighter">
                        Complete Your <span className="text-[#14B8A6]">Hero Setup</span>
                    </h1>
                    <p className="text-white/60 text-lg font-inter">
                        Choose the sidekick that best fits your business needs.
                    </p>

                    {/* Toggle */}
                    <div className="mt-8 flex items-center justify-center gap-4">
                        <span className={`text-sm font-bold ${billingCycle === 'monthly' ? 'text-white' : 'text-white/40'}`}>Monthly</span>
                        <button
                            onClick={() => setBillingCycle(prev => prev === 'monthly' ? 'yearly' : 'monthly')}
                            className="w-16 h-8 rounded-full bg-white/10 border border-white/20 p-1 flex items-center transition-all"
                        >
                            <div className={`w-6 h-6 rounded-full bg-[#14B8A6] shadow-lg transition-all ${billingCycle === 'yearly' ? 'translate-x-8' : 'translate-x-0'}`} />
                        </button>
                        <div className="flex items-center gap-2">
                            <span className={`text-sm font-bold ${billingCycle === 'yearly' ? 'text-white' : 'text-white/40'}`}>Yearly</span>
                            <span className="px-2 py-0.5 rounded-md bg-emerald-500/20 border border-emerald-500/20 text-emerald-400 text-[10px] font-black uppercase tracking-widest">Save 20%</span>
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-6xl mx-auto">
                    {plans.map((plan, idx) => {
                        const display = getPlanDisplay(plan.slug);
                        const price = billingCycle === 'monthly' ? plan.price_monthly : plan.price_yearly;

                        return (
                            <motion.div
                                key={plan.id}
                                initial={{ opacity: 0, y: 20 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ delay: idx * 0.1 }}
                                className={`relative p-8 rounded-[40px] border flex flex-col transition-all duration-500 group
                                    ${display.popular
                                        ? 'bg-[#0F4C81] border-[#14B8A6] shadow-[0_0_50px_-12px_rgba(31,191,143,0.3)] ring-2 ring-[#14B8A6]/20'
                                        : 'bg-white/5 border-white/10 hover:bg-white/[0.07] hover:border-white/20'}`}
                            >
                                {display.popular && (
                                    <div className="absolute -top-4 left-1/2 -translate-x-1/2 px-4 py-1 bg-[#14B8A6] text-white text-[10px] font-black uppercase tracking-widest rounded-full shadow-lg">
                                        Best Value
                                    </div>
                                )}

                                <div className={`w-12 h-12 rounded-2xl flex items-center justify-center mb-6 
                                    ${display.color === 'emerald' ? 'bg-emerald-500/10 text-emerald-400' :
                                        display.color === 'blue' ? 'bg-blue-500/10 text-blue-400' :
                                            display.color === 'purple' ? 'bg-purple-500/10 text-purple-400' :
                                                'bg-pink-500/10 text-pink-400'}`}>
                                    {display.icon}
                                </div>

                                <h3 className="text-2xl font-bold text-white mb-2 font-outfit">{plan.name}</h3>
                                <p className="text-white/50 text-xs font-inter mb-6 leading-relaxed">{display.tagline}</p>

                                <div className="mb-8 h-12 flex items-baseline gap-1">
                                    <span className="text-4xl font-black text-white">
                                        ${billingCycle === 'monthly' ? Math.round(plan.price_monthly) : Math.round(plan.price_yearly / 12)}
                                    </span>
                                    <span className="text-white/40 text-sm font-bold">/mo</span>
                                </div>

                                <div className="space-y-4 mb-10 flex-1">
                                    {Array.isArray(plan.features) && plan.features.map((feature, i) => (
                                        <div key={i} className="flex items-center gap-3">
                                            <div className="w-5 h-5 rounded-full bg-white/5 flex items-center justify-center shrink-0">
                                                <Check className={`w-3 h-3 ${display.popular ? 'text-[#14B8A6]' : 'text-white/60'}`} />
                                            </div>
                                            <span className="text-white/70 text-sm font-medium font-inter">{feature}</span>
                                        </div>
                                    ))}
                                </div>

                                <button
                                    onClick={() => handleSelectPlan(plan)}
                                    className={`w-full py-4 rounded-2xl font-black text-sm transition-all flex items-center justify-center gap-2 group/btn
                                    ${display.popular
                                            ? 'bg-[#14B8A6] text-[#0F4C81] hover:bg-[#0f9687] shadow-lg shadow-[#14B8A6]/20'
                                            : 'bg-white/10 text-white hover:bg-white/20 border border-white/10 hover:border-white/30'}`}>
                                    {plan.price_monthly === 0 ? 'Start Free' : 'Choose Plan'}
                                    <ArrowRight className="w-4 h-4 group-hover/btn:translate-x-1 transition-transform" />
                                </button>
                            </motion.div>
                        );
                    })}
                </div>
            </div>
        </div>
    );
}
