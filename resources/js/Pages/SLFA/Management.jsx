import { Head } from '@inertiajs/react';
import SLFAWebsiteLayout from '@/Layouts/SLFAWebsiteLayout';

export default function Management({ auth, presentSetup, executiveCommittee }) {

    const renderMemberCard = (member) => (
        <div key={member.id} className="bg-white rounded-lg shadow-md overflow-hidden text-center hover:shadow-lg transition transform hover:-translate-y-1 animate-fade-up">
            <div className="h-48 bg-slfa-peach relative overflow-hidden">
                {member.photo_path ? (
                    <img src={`/storage/${member.photo_path}`} alt={member.name} className="w-full h-full object-cover object-top" />
                ) : (
                    <div className="w-full h-full flex flex-col items-center justify-center text-slfa-coral/40 bg-slfa-peach">
                        <svg className="w-16 h-16" fill="currentColor" viewBox="0 0 24 24"><path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                    </div>
                )}
                <div className="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent p-4">
                    <p className="text-white font-semibold">{member.role}</p>
                </div>
            </div>
            <div className="p-4 border-t-4 border-slfa-coral">
                <h3 className="font-bold text-lg text-slfa-navy">{member.name}</h3>
                {member.company && <p className="text-sm text-gray-500 mt-1">{member.company}</p>}
                {member.designation && <p className="text-sm text-gray-400">{member.designation}</p>}
            </div>
        </div>
    );

    return (
        <SLFAWebsiteLayout user={auth.user}>
            <Head title="Management" />

            <div className="bg-slfa-navy text-white py-16">
                <div className="slfa-container text-center animate-fade-up">
                    <h1 className="text-4xl font-bold mb-4">Management Committee</h1>
                    <p className="text-gray-300">Meet the dedicated team leading SLFA</p>
                </div>
            </div>

            <div className="slfa-section bg-slfa-cream">
                <div className="slfa-container">

                    {/* Present Setup */}
                    <div className="mb-16">

                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                            {presentSetup.map(renderMemberCard)}
                        </div>
                    </div>

                    {/* Executive Committee */}
                    <div>
                        <h2 className="text-3xl font-bold text-center text-slfa-navy mb-10 border-b-2 border-slfa-coral pb-4 inline-block mx-auto">Executive Committee</h2>

                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                            {executiveCommittee.map(renderMemberCard)}
                        </div>
                    </div>

                </div>
            </div>
        </SLFAWebsiteLayout>
    );
}
