import { Head, Link, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { ArrowLeft, Save, FileText } from 'lucide-react';
import toast, { Toaster } from 'react-hot-toast';

export default function Create({ auth }) {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        description: '',
        is_public: true,
        document: null,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('admin.documents.store'), {
            onError: (errs) => {
                const firstError = Object.values(errs)[0];
                toast.error(firstError || "Error uploading document");
            },
        });
    };

    return (
        <AdminLayout user={auth.user}>
            <Head title="Upload Document" />
            <Toaster position="top-right" />

            <div className="mb-6 flex items-center justify-between">
                <div>
                    <Link href={route('admin.documents.index')} className="text-sm text-gray-500 hover:text-slfa-maroon flex items-center mb-2 transition">
                        <ArrowLeft className="w-4 h-4 mr-1" /> Back to Documents
                    </Link>
                    <h1 className="text-2xl font-bold text-gray-900 flex items-center">
                        <FileText className="w-6 h-6 mr-2 text-slfa-maroon" />
                        Upload Document
                    </h1>
                </div>
            </div>

            <div className="bg-white rounded-lg shadow-sm border border-gray-100 p-6 md:p-8 max-w-2xl">
                <form onSubmit={handleSubmit} className="space-y-6">
                    
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">Document Title</label>
                        <input
                            type="text"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            className="w-full border-gray-300 rounded-md shadow-sm focus:ring-slfa-maroon focus:border-slfa-maroon"
                            required
                        />
                        {errors.title && <p className="text-red-500 text-xs mt-1">{errors.title}</p>}
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea
                            rows="3"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            className="w-full border-gray-300 rounded-md shadow-sm focus:ring-slfa-maroon focus:border-slfa-maroon"
                        ></textarea>
                        {errors.description && <p className="text-red-500 text-xs mt-1">{errors.description}</p>}
                    </div>

                    <div>
                        <label className="flex items-center space-x-3 cursor-pointer">
                            <input
                                type="checkbox"
                                checked={data.is_public}
                                onChange={(e) => setData('is_public', e.target.checked)}
                                className="rounded border-gray-300 text-slfa-maroon focus:ring-slfa-maroon w-5 h-5"
                            />
                            <span className="text-sm font-medium text-gray-700">Publicly Accessible</span>
                        </label>
                        <p className="text-xs text-gray-500 mt-1 ml-8">If unchecked, only logged-in members can download this document.</p>
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">File</label>
                        <input
                            type="file"
                            accept=".pdf,.doc,.docx,.xls,.xlsx"
                            onChange={(e) => setData('document', e.target.files[0])}
                            className="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-slfa-maroon/10 file:text-slfa-maroon hover:file:bg-slfa-maroon/20"
                            required
                        />
                        {errors.document && <p className="text-red-500 text-xs mt-1">{errors.document}</p>}
                    </div>

                    <div className="pt-6 flex justify-end">
                        <button
                            type="submit"
                            disabled={processing}
                            className="bg-slfa-maroon text-white px-6 py-2 rounded-md font-semibold hover:bg-slfa-navy transition-colors flex items-center"
                        >
                            <Save className="w-4 h-4 mr-2" />
                            {processing ? 'Uploading...' : 'Upload Document'}
                        </button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
