import { Head, Link } from '@inertiajs/react';
import SLFAWebsiteLayout from '@/Layouts/SLFAWebsiteLayout';

const questions = [
    {
        question: 'Who can apply for membership?',
        answer: 'Adults aged 18 or over who live in South India and have a connection with Sri Lanka can apply. Connections may include family, work, education or a spouse.',
    },
    {
        question: 'How is an application reviewed?',
        answer: 'Applications are reviewed by the SLFA Executive Committee. You can sign in to see whether your application is pending or active.',
    },
    {
        question: 'Can I update my details after applying?',
        answer: 'Yes. Sign in and open your profile to update your account and membership contact details.',
    },
    {
        question: 'Will other members see my contact details?',
        answer: 'Your directory listing is optional. Contact details are shown only when you have separately enabled contact information sharing.',
    },
];

export default function Membership({ auth }) {
    return (
        <SLFAWebsiteLayout user={auth.user}>
            <Head title="Membership" />
            <main className="bg-slfa-cream py-16">
                <div className="slfa-container max-w-4xl">
                    <h1 className="font-abhaya text-5xl font-bold text-slfa-navy">Membership</h1>
                    <p className="mt-5 text-lg text-gray-700">Join the SriLankan Friendship Association of South India and connect with its community.</p>
                    <Link href={route('register')} className="mt-8 inline-block rounded-full bg-slfa-primary px-7 py-3 font-bold text-white">Apply for membership</Link>
                    <h2 className="mt-16 font-abhaya text-3xl font-bold text-slfa-navy">Questions about joining</h2>
                    <div className="mt-6 space-y-4">
                        {questions.map(item => (
                            <section key={item.question} className="rounded-xl bg-white p-6 shadow-sm">
                                <h3 className="text-lg font-bold text-slfa-navy">{item.question}</h3>
                                <p className="mt-2 text-gray-700">{item.answer}</p>
                            </section>
                        ))}
                    </div>
                </div>
            </main>
        </SLFAWebsiteLayout>
    );
}
