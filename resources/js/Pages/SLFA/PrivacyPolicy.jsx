import React from 'react';
import { Head, Link } from '@inertiajs/react';
import SLFAWebsiteLayout from '@/Layouts/SLFAWebsiteLayout';
import { ArrowLeft } from 'lucide-react';

export default function PrivacyPolicy({ auth }) {
    return (
        <SLFAWebsiteLayout user={auth?.user} cream>
            <Head title="Privacy Policy" />

            <section className="cream-section cream-container pt-32 pb-20">
                <div className="max-w-3xl mx-auto">
                    <Link href={route('home')} className="cream-link mb-8 inline-flex items-center gap-2">
                        <ArrowLeft size={16} /> Back to home
                    </Link>

                    <p className="cream-eyebrow">Legal & Governance</p>
                    <h1 className="cream-title mb-4">Privacy <em>Policy</em></h1>
                    <p className="text-sm text-[#566158] mb-10">
                        Last updated: November 2026 · Sri Lankan Friendship Association of South India (Reg. No. 234/2019)
                    </p>

                    <div className="space-y-8 text-[#2f3b33] leading-relaxed text-[16px] font-light">
                        <section>
                            <h2 className="text-xl font-normal text-[#172130] mb-3">1. Who We Are</h2>
                            <p>
                                The Sri Lankan Friendship Association of South India (&ldquo;SLFA&rdquo;, &ldquo;we&rdquo;, &ldquo;our&rdquo;)
                                operates <strong>slfa.org.in</strong> to connect members, manage community events, and administer
                                membership services from our registered office at 164/27B, Dr. P. J. Thomas Avenue, Injambakkam,
                                Chennai 600115, India.
                            </p>
                        </section>

                        <section>
                            <h2 className="text-xl font-normal text-[#172130] mb-3">2. Information We Collect</h2>
                            <ul className="list-disc pl-5 space-y-2">
                                <li><strong>Membership &amp; Profile Details:</strong> Name, email address, phone/WhatsApp number, residential address, occupation, family member details, and identity verification documents submitted during registration.</li>
                                <li><strong>Event &amp; Inquiry Records:</strong> Event RSVPs, admission requests, and messages submitted through our contact form.</li>
                                <li><strong>Payment Verification:</strong> Membership and event payments are processed via Razorpay. We store transaction references and payment status, and never store your full card or UPI credentials.</li>
                                <li><strong>Technical &amp; Session Data:</strong> Essential session cookies required for secure authentication and CSRF protection.</li>
                            </ul>
                        </section>

                        <section>
                            <h2 className="text-xl font-normal text-[#172130] mb-3">3. How We Use Your Information</h2>
                            <p>
                                We use your information solely to review and administer SLFA memberships, coordinate cultural and community
                                events, maintain the private Member Directory (accessible only to approved active members), and send official
                                association notices. We do not sell, rent, or trade member data to third parties.
                            </p>
                        </section>

                        <section>
                            <h2 className="text-xl font-normal text-[#172130] mb-3">4. Data Security &amp; Retention</h2>
                            <p>
                                Member records and uploaded documents are stored on access-controlled servers. We retain membership records
                                for as long as your account remains active or as required for association governance and statutory compliance.
                            </p>
                        </section>

                        <section>
                            <h2 className="text-xl font-normal text-[#172130] mb-3">5. Your Rights &amp; Contact</h2>
                            <p>
                                You may review, update, or request deletion of your personal profile at any time through your account
                                settings or by writing to{' '}
                                <a href="mailto:info@slfa.org.in" className="text-[#167747] underline underline-offset-4">
                                    info@slfa.org.in
                                </a>.
                            </p>
                        </section>
                    </div>
                </div>
            </section>
        </SLFAWebsiteLayout>
    );
}

