import React from 'react';
import { Head, Link } from '@inertiajs/react';
import SLFAWebsiteLayout from '@/Layouts/SLFAWebsiteLayout';
import { ArrowLeft } from 'lucide-react';

export default function Terms({ auth }) {
    return (
        <SLFAWebsiteLayout user={auth?.user} cream>
            <Head title="Terms & Conditions" />

            <section className="cream-section cream-container pt-32 pb-20">
                <div className="max-w-3xl mx-auto">
                    <Link href={route('home')} className="cream-link mb-8 inline-flex items-center gap-2">
                        <ArrowLeft size={16} /> Back to home
                    </Link>

                    <p className="cream-eyebrow">Legal & Governance</p>
                    <h1 className="cream-title mb-4">Terms &amp; <em>Conditions</em></h1>
                    <p className="text-sm text-[#566158] mb-10">
                        Last updated: November 2026 · Sri Lankan Friendship Association of South India (Reg. No. 234/2019)
                    </p>

                    <div className="space-y-8 text-[#2f3b33] leading-relaxed text-[16px] font-light">
                        <section>
                            <h2 className="text-xl font-normal text-[#172130] mb-3">1. Acceptance of Terms</h2>
                            <p>
                                By accessing <strong>slfa.org.in</strong> or applying for membership with the Sri Lankan Friendship
                                Association of South India (&ldquo;SLFA&rdquo;), you agree to abide by these Terms &amp; Conditions and
                                the association&rsquo;s rules and bylaws.
                            </p>
                        </section>

                        <section>
                            <h2 className="text-xl font-normal text-[#172130] mb-3">2. Membership Eligibility &amp; Approval</h2>
                            <p>
                                Membership is open to eligible adults residing in South India with a connection to Sri Lanka. All
                                applications are subject to review and approval by the SLFA Executive Committee, which reserves the right
                                to approve, defer, or revoke membership in accordance with association bylaws.
                            </p>
                        </section>

                        <section>
                            <h2 className="text-xl font-normal text-[#172130] mb-3">3. Member Directory &amp; Conduct</h2>
                            <ul className="list-disc pl-5 space-y-2">
                                <li>The Member Directory is provided exclusively for personal fellowship and mutual support among approved members.</li>
                                <li>You must not scrape, harvest, redistribute, or use directory contact details for unsolicited commercial marketing.</li>
                                <li>Members are expected to uphold respectful, inclusive conduct at all SLFA gatherings and on digital platforms.</li>
                            </ul>
                        </section>

                        <section>
                            <h2 className="text-xl font-normal text-[#172130] mb-3">4. Events &amp; Payments</h2>
                            <p>
                                Event registrations, admissions, and membership subscriptions paid via Razorpay are non-transferable unless
                                explicitly approved by the organizing committee. Refunds, if applicable for cancelled events, are processed
                                in accordance with the specific event notice.
                            </p>
                        </section>

                        <section>
                            <h2 className="text-xl font-normal text-[#172130] mb-3">5. Governing Law &amp; Contact</h2>
                            <p>
                                These terms are governed by the laws of India, with jurisdiction in Chennai, Tamil Nadu. For any questions,
                                contact us at{' '}
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

