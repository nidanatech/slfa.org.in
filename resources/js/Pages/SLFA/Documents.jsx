import { Head, Link } from '@inertiajs/react';
import SLFAWebsiteLayout from '@/Layouts/SLFAWebsiteLayout';
import SLFAPageHero from '@/Components/SLFAPageHero';
import { FileText, Download, FileArchive, File, Lock } from 'lucide-react';

export default function Documents({ auth, documents }) {

    const categoryIcons = {
        'bylaws': <FileText className="w-5 h-5" />,
        'minutes': <FileArchive className="w-5 h-5" />,
        'registration': <File className="w-5 h-5" />,
        'other': <File className="w-5 h-5" />
    };

    const categoryNames = {
        'bylaws': 'Bylaws & Constitution',
        'minutes': 'Meeting Minutes',
        'registration': 'Registration Certificates',
        'other': 'Other Documents'
    };

    return (
        <SLFAWebsiteLayout user={auth.user} cream>
            <Head title="Document Library" />

            <SLFAPageHero compact eyebrow="Members only" title={<>Document <span className="italic font-light text-[#1B7A4A]">library</span></>} description="Access the association's documents and forms in one place.">
                <span className="inline-flex items-center gap-2"><Lock className="h-4 w-4 text-[#1B7A4A]" />Available to approved members</span>
            </SLFAPageHero>

            <section className="min-h-[40vh] bg-[#FAF7F2] py-16 sm:py-20">
                <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">

                    {Object.keys(documents).length > 0 ? (
                        <div className="space-y-10">
                            {Object.entries(documents).map(([category, docs], index) => (
                                <div key={category} className="editorial-card animate-fade-up" style={{ animationDelay: `${index * 0.1}s` }}>
                                    <h2 className="mb-5 flex items-center font-lato text-2xl font-normal text-gray-950">
                                        <span className="mr-3 text-[#a45116]">
                                            {categoryIcons[category] || <File className="w-5 h-5" />}
                                        </span>
                                        {categoryNames[category] || category}
                                    </h2>

                                    <div className="divide-y divide-[#d8ded2] border-y border-[#d8ded2]">
                                        {docs.map(doc => (
                                            <div key={doc.id} className="group flex items-center justify-between gap-4 py-4 transition hover:bg-white/60">
                                                <div className="flex items-center">
                                                    <div className="mr-4 flex h-10 w-10 shrink-0 items-center justify-center text-[#a45116]">
                                                        <FileText className="w-5 h-5" />
                                                    </div>
                                                    <div>
                                                        <h3 className="font-normal text-[#1f2937] text-sm md:text-base group-hover:text-[#1B7A4A] transition">{doc.title}</h3>
                                                        <p className="text-xs text-gray-500 mt-1">Uploaded: {new Date(doc.uploaded_at).toLocaleDateString()}</p>
                                                    </div>
                                                </div>
                                                <a
                                                    href={route('documents.download', doc.id)}
                                                    className="flex items-center rounded-full p-2 text-gray-500 transition hover:bg-[#1B7A4A]/15 hover:text-[#1B7A4A]"
                                                    title="Download"
                                                    aria-label={`Download ${doc.title}`}
                                                >
                                                    <Download className="w-5 h-5" />
                                                </a>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="border-t border-[#d8ded2] py-12 text-center">
                            <FileArchive className="w-16 h-16 text-gray-300 mx-auto mb-4" />
                            <h3 className="text-xl font-normal text-[#1f2937] mb-2">No documents available</h3>
                            <p className="text-gray-500">Official documents will appear here once uploaded by the committee.</p>
                        </div>
                    )}
                </div>
            </section>
        </SLFAWebsiteLayout>
    );
}
