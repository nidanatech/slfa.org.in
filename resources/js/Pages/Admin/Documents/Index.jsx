import { Head, Link, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { FileText, Plus, Trash2, Download } from 'lucide-react';
import dayjs from 'dayjs';
import toast, { Toaster } from 'react-hot-toast';

export default function Index({ auth, documents }) {
    const { delete: destroy } = useForm();

    const handleDelete = (id) => {
        if (confirm("Are you sure you want to delete this document?")) {
            destroy(route('admin.documents.destroy', id), {
                onSuccess: () => toast.success('Document deleted successfully.'),
                onError: (errs) => toast.error(Object.values(errs)[0] || 'Error deleting document.')
            });
        }
    };

    return (
        <AdminLayout user={auth.user}>
            <Head title="Manage Documents" />
            <Toaster position="top-right" />

            <div className="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-normal text-gray-900 flex items-center">
                        <FileText className="w-6 h-6 mr-2 text-[#1B7A4A]" />
                        Documents
                    </h1>
                    <p className="text-sm text-gray-500 mt-1">Manage public and members-only documents.</p>
                </div>
                <div>
                    <Link href={route('admin.documents.create')} className="bg-[#1B7A4A] text-white px-4 py-2 rounded-md font-normal text-sm hover:bg-[#1B7A4A] transition flex items-center">
                        <Plus className="w-4 h-4 mr-1" /> Upload Document
                    </Link>
                </div>
            </div>

            <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="w-full text-left border-collapse">
                        <thead>
                            <tr className="bg-gray-50 border-b border-gray-100">
                                <th className="py-3 px-6 text-xs font-normal text-gray-500 uppercase tracking-wider">Title</th>
                                <th className="py-3 px-6 text-xs font-normal text-gray-500 uppercase tracking-wider">Access</th>
                                <th className="py-3 px-6 text-xs font-normal text-gray-500 uppercase tracking-wider">Uploaded</th>
                                <th className="py-3 px-6 text-xs font-normal text-gray-500 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {documents.data.length === 0 ? (
                                <tr>
                                    <td colSpan="4" className="py-8 px-6 text-center text-gray-500">
                                        No documents found.
                                    </td>
                                </tr>
                            ) : (
                                documents.data.map(doc => (
                                    <tr key={doc.id} className="hover:bg-gray-50/50 transition-colors">
                                        <td className="py-4 px-6 font-normal text-gray-900">{doc.title}</td>
                                        <td className="py-4 px-6">
                                            {doc.is_public ? (
                                                <span className="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full font-normal">Public</span>
                                            ) : (
                                                <span className="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full font-normal">Members Only</span>
                                            )}
                                        </td>
                                        <td className="py-4 px-6 text-sm text-gray-600">
                                            {dayjs(doc.created_at).format('MMM D, YYYY')}
                                        </td>
                                        <td className="py-4 px-6 text-right space-x-3">
                                            <a
                                                href={route('documents.download', doc.id)}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="text-[#1B7A4A] hover:text-[#1f2937] transition-colors inline-block p-1"
                                                title="Download"
                                            >
                                                <Download className="w-4 h-4" />
                                            </a>
                                            <button
                                                onClick={() => handleDelete(doc.id)}
                                                className="text-red-500 hover:text-red-700 transition-colors inline-block p-1"
                                                title="Delete"
                                            >
                                                <Trash2 className="w-4 h-4" />
                                            </button>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </AdminLayout>
    );
}
