import { Head, router } from '@inertiajs/react';
import SLFAWebsiteLayout from '@/Layouts/SLFAWebsiteLayout';
import { CreditCard, CheckCircle, Shield } from 'lucide-react';
import { useState } from 'react';

export default function Payment({ auth, member, purpose, amount, razorpayKey }) {
    
    const [processing, setProcessing] = useState(false);

    const handlePayment = async () => {
        setProcessing(true);
        
        try {
            // 1. Create order on server
            const response = await fetch(route('payment.create-order'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: JSON.stringify({ amount, purpose, member_id: member.id })
            });
            
            const data = await response.json();
            
            // For now, bypass Razorpay actual UI and simulate success 
            // since we are using PLACEHOLDER keys as requested
            setTimeout(() => {
                router.post(route('payment.verify'), {
                    member_id: member.id,
                    amount: amount,
                    purpose: purpose,
                    razorpay_order_id: data.order_id,
                    razorpay_payment_id: 'pay_' + Math.random().toString(36).substring(7),
                });
            }, 1500);

            /* Real Razorpay Integration would go here:
            const options = {
                key: razorpayKey,
                amount: data.amount,
                currency: "INR",
                name: "SLFA",
                description: purpose === 'admission' ? "Membership Admission Fee" : "Annual Renewal Fee",
                order_id: data.order_id,
                handler: function (response) {
                    router.post(route('payment.verify'), {
                        member_id: member.id,
                        amount: amount,
                        purpose: purpose,
                        razorpay_order_id: response.razorpay_order_id,
                        razorpay_payment_id: response.razorpay_payment_id,
                        razorpay_signature: response.razorpay_signature
                    });
                },
                prefill: {
                    name: member.full_name,
                    email: member.email,
                    contact: member.phone || ""
                },
                theme: {
                    color: "#F26E34"
                }
            };
            const rzp = new window.Razorpay(options);
            rzp.open();
            */
            
        } catch (error) {
            console.error(error);
            setProcessing(false);
            alert("Error initiating payment. Please try again.");
        }
    };

    const purposeTitle = purpose === 'admission' ? 'Membership Admission' : 'Annual Renewal';
    const description = purpose === 'admission' 
        ? `Admission fee for ${member.membership_type} membership.`
        : `Annual renewal fee for ${new Date().getFullYear()}.`;

    return (
        <SLFAWebsiteLayout user={auth.user}>
            <Head title="Secure Payment" />

            <div className="bg-slfa-navy text-white py-12">
                <div className="slfa-container text-center animate-fade-up">
                    <h1 className="text-3xl font-bold mb-2">Secure Checkout</h1>
                </div>
            </div>

            <div className="slfa-section bg-slfa-cream min-h-screen">
                <div className="slfa-container max-w-2xl">
                    
                    <div className="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden animate-fade-up">
                        
                        <div className="bg-gray-50 p-6 border-b border-gray-100">
                            <div className="flex justify-between items-center mb-2">
                                <h2 className="text-xl font-bold text-slfa-navy">{purposeTitle}</h2>
                                <span className="text-2xl font-bold text-slfa-coral">₹{amount}</span>
                            </div>
                            <p className="text-sm text-gray-500">{description}</p>
                        </div>

                        <div className="p-6 md:p-8 space-y-6">
                            
                            <div>
                                <h3 className="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Member Details</h3>
                                <div className="bg-gray-50 p-4 rounded-md text-sm border border-gray-100">
                                    <div className="grid grid-cols-2 gap-2 mb-2">
                                        <span className="text-gray-500">Name:</span>
                                        <span className="font-semibold text-gray-800">{member.full_name}</span>
                                    </div>
                                    <div className="grid grid-cols-2 gap-2 mb-2">
                                        <span className="text-gray-500">Email:</span>
                                        <span className="font-semibold text-gray-800">{member.email}</span>
                                    </div>
                                    <div className="grid grid-cols-2 gap-2">
                                        <span className="text-gray-500">Type:</span>
                                        <span className="font-semibold text-gray-800 capitalize">{member.membership_type}</span>
                                    </div>
                                </div>
                            </div>

                            <div className="flex items-center text-sm text-gray-500 bg-blue-50 p-3 rounded text-blue-800">
                                <Shield className="w-5 h-5 mr-2 shrink-0" />
                                Payments are securely processed via Razorpay. Your card details are not stored on our servers.
                            </div>

                            <button 
                                onClick={handlePayment}
                                disabled={processing}
                                className="w-full bg-slfa-coral hover:bg-slfa-coral-dark text-white font-bold py-4 px-6 rounded-lg shadow-md transition disabled:opacity-70 flex justify-center items-center text-lg"
                            >
                                {processing ? (
                                    <>Processing Payment...</>
                                ) : (
                                    <>
                                        <CreditCard className="w-6 h-6 mr-2" /> Pay ₹{amount} Now
                                    </>
                                )}
                            </button>
                            
                            <p className="text-xs text-center text-gray-400">
                                (Simulation mode active - actual Razorpay keys not provided)
                            </p>
                        </div>
                    </div>

                </div>
            </div>
        </SLFAWebsiteLayout>
    );
}
