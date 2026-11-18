import { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { CreditCard, CheckCircle2, ShieldCheck, ArrowRight } from 'lucide-react';

export default function PaymentGateway() {
    const { post, processing } = useForm({});
    
    const submit = (e) => {
        e.preventDefault();
        post(route('payment.process'));
    };

    return (
        <div className="min-h-screen bg-slfa-cream font-lato py-12 flex flex-col justify-center sm:px-6 lg:px-8">
            <Head title="Complete Payment - SLFA" />

            <div className="sm:mx-auto sm:w-full sm:max-w-md text-center">
                <img src="/storage/legacy/srilankan-friendship-logo.png" alt="SLFA" className="mx-auto h-20 w-auto" />
                <h2 className="mt-6 text-3xl font-extrabold text-slfa-navy font-abhaya tracking-tight">Complete Membership Payment</h2>
                <p className="mt-2 text-sm text-gray-600">
                    Secure payment gateway for SLFA Registration.
                </p>
            </div>

            <div className="mt-8 sm:mx-auto sm:w-full sm:max-w-xl">
                <div className="bg-white py-8 px-4 shadow-xl sm:rounded-2xl sm:px-10 border border-gray-100">
                    <div className="mb-8 p-6 bg-gray-50 rounded-xl border border-gray-100">
                        <div className="flex justify-between items-center mb-2">
                            <span className="text-gray-500">Registration Fee (One-time)</span>
                            <span className="font-bold text-slfa-navy">Rs 5,000.00</span>
                        </div>
                        <div className="flex justify-between items-center pb-4 border-b border-gray-200">
                            <span className="text-gray-500">Annual Membership Fee</span>
                            <span className="font-bold text-slfa-navy">Rs 3,000.00</span>
                        </div>
                        <div className="flex justify-between items-center mt-4">
                            <span className="text-lg font-bold text-slfa-navy">Total to Pay</span>
                            <span className="text-2xl font-extrabold text-slfa-primary font-abhaya">Rs 8,000.00</span>
                        </div>
                    </div>

                    <form onSubmit={submit} className="space-y-6">
                        {/* Dummy Credit Card UI */}
                        <div className="space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Cardholder Name</label>
                                <input type="text" className="mt-1 block w-full rounded-xl bg-gray-50 border-gray-300 focus:border-slfa-primary focus:ring-slfa-primary" placeholder="Name on card" required />
                            </div>
                            
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Card Number</label>
                                <div className="mt-1 relative rounded-xl shadow-sm">
                                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <CreditCard className="h-5 w-5 text-gray-400" />
                                    </div>
                                    <input type="text" className="focus:ring-slfa-primary focus:border-slfa-primary block w-full pl-10 sm:text-sm border-gray-300 rounded-xl bg-gray-50" placeholder="0000 0000 0000 0000" required />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Expiry Date</label>
                                    <input type="text" className="mt-1 block w-full rounded-xl bg-gray-50 border-gray-300 focus:border-slfa-primary focus:ring-slfa-primary" placeholder="MM/YY" required />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">CVC</label>
                                    <input type="text" className="mt-1 block w-full rounded-xl bg-gray-50 border-gray-300 focus:border-slfa-primary focus:ring-slfa-primary" placeholder="123" required />
                                </div>
                            </div>
                        </div>

                        <div className="bg-blue-50 border border-blue-100 p-4 rounded-xl flex items-start gap-3 mt-6">
                            <ShieldCheck className="w-5 h-5 text-blue-500 mt-0.5 shrink-0" />
                            <p className="text-sm text-blue-800 font-light">
                                <strong>Note:</strong> This is a dummy payment gateway for demonstration purposes. No actual transaction will occur.
                            </p>
                        </div>

                        <div>
                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full flex justify-center py-4 px-4 border border-transparent rounded-xl shadow-sm text-lg font-bold text-white bg-gradient-to-r from-slfa-primary to-slfa-accent hover:from-slfa-primary hover:to-slfa-primary hover:shadow-lg transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slfa-primary mt-8 disabled:opacity-50 group gap-2"
                            >
                                Pay Rs 8,000.00
                                <ArrowRight className="w-5 h-5 group-hover:translate-x-1 transition-transform" />
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
}
