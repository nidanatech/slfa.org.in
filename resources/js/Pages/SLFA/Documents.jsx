import { Head, Link } from '@inertiajs/react';
import SLFAWebsiteLayout from '@/Layouts/SLFAWebsiteLayout';
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
        <SLFAWebsiteLayout user={auth.user}>
            <Head title="Document Library" />

            <div className="bg-slfa-navy text-white py-16">
                <div className="slfa-container text-center animate-fade-up">
                    <div className="w-16 h-16 bg-white/10 rounded-full flex items-center justify-center mx-auto mb-4 backdrop-blur-sm">
                        <Lock className="w-8 h-8 text-slfa-coral" />
                    </div>
                    <h1 className="text-4xl font-bold mb-4">Members Only Library</h1>
                    <p className="text-gray-300">Access official SLFA documents and forms</p>
                </div>
            </div>

            <div className="slfa-section bg-slfa-cream min-h-screen">
                <div className="slfa-container max-w-5xl">
                    
                    {Object.keys(documents).length > 0 ? (
                        <div className="space-y-12">
                            {Object.entries(documents).map(([category, docs], index) => (
                                <div key={category} className="animate-fade-up" style={{ animationDelay: `${index * 0.1}s` }}>
                                    <h2 className="text-2xl font-bold text-slfa-navy mb-6 flex items-center border-b pb-3">
                                        <span className="text-slfa-coral mr-3 bg-slfa-peach p-2 rounded-lg">
                                            {categoryIcons[category] || <File className="w-5 h-5" />}
                                        </span>
                                        {categoryNames[category] || category}
                                    </h2>
                                    
                                    <div className="bg-white rounded-lg shadow-sm border border-gray-100 divide-y divide-gray-100 overflow-hidden">
                                        {docs.map(doc => (
                                            <div key={doc.id} className="p-4 hover:bg-gray-50 transition flex items-center justify-between group">
                                                <div className="flex items-center">
                                                    <div className="w-10 h-10 bg-slfa-peach rounded flex items-center justify-center text-slfa-coral mr-4 shrink-0">
                                                        <FileText className="w-5 h-5" />
                                                    </div>
                                                    <div>
                                                        <h3 className="font-bold text-slfa-navy text-sm md:text-base group-hover:text-slfa-coral transition">{doc.title}</h3>
                                                        <p className="text-xs text-gray-500 mt-1">Uploaded: {new Date(doc.uploaded_at).toLocaleDateString()}</p>
                                                    </div>
                                                </div>
                                                <a 
                                                    href={route('documents.download', doc.id)}
                                                    className="text-gray-500 hover:text-slfa-coral p-2 rounded-full hover:bg-slfa-peach transition flex items-center"
                                                    title="Download"
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
                        <div className="bg-white p-12 rounded-lg shadow-sm text-center border border-gray-100">
                            <FileArchive className="w-16 h-16 text-gray-300 mx-auto mb-4" />
                            <h3 className="text-xl font-bold text-slfa-navy mb-2">No documents available</h3>
                            <p className="text-gray-500">Official documents will appear here once uploaded by the committee.</p>
                        </div>
                    )}
                </div>
            </div>
        </SLFAWebsiteLayout>
    );
}
