import { Head, Link, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Shield, Trash2, Plus } from 'lucide-react';
import dayjs from 'dayjs';
import toast, { Toaster } from 'react-hot-toast';

export default function Index({ auth, admins }) {
    const { delete: destroy } = useForm();

    const handleDelete = (id) => {
        if (confirm("Are you sure you want to delete this admin?")) {
            destroy(route('admin.users.destroy', id), {
                onSuccess: () => toast.success('Admin deleted successfully.'),
                onError: (errs) => toast.error(Object.values(errs)[0] || 'Error deleting admin.')
            });
        }
    };

    return (
        <AdminLayout user={auth.user}>
            <Head title="Manage Admins" />
            <Toaster position="top-right" />

            <div className="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-normal text-gray-900 flex items-center">
                        <Shield className="w-6 h-6 mr-2 text-[#1B7A4A]" />
                        Admin Users
                    </h1>
                    <p className="text-sm text-gray-500 mt-1">Manage system administrators.</p>
                </div>
                <div>
                    <Link href={route('admin.users.create')} className="bg-[#1B7A4A] text-white px-4 py-2 rounded-md font-normal text-sm hover:bg-[#1B7A4A] transition flex items-center">
                        <Plus className="w-4 h-4 mr-1" /> Add Admin
                    </Link>
                </div>
            </div>

            <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="w-full text-left border-collapse">
                        <thead>
                            <tr className="bg-gray-50 border-b border-gray-100">
                                <th className="py-3 px-6 text-xs font-normal text-gray-500 uppercase tracking-wider">Name</th>
                                <th className="py-3 px-6 text-xs font-normal text-gray-500 uppercase tracking-wider">Email</th>
                                <th className="py-3 px-6 text-xs font-normal text-gray-500 uppercase tracking-wider">Joined</th>
                                <th className="py-3 px-6 text-xs font-normal text-gray-500 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {admins.data.length === 0 ? (
                                <tr>
                                    <td colSpan="4" className="py-8 px-6 text-center text-gray-500">
                                        No admins found.
                                    </td>
                                </tr>
                            ) : (
                                admins.data.map(admin => (
                                    <tr key={admin.id} className="hover:bg-gray-50/50 transition-colors">
                                        <td className="py-4 px-6">
                                            <div className="font-normal text-gray-900">{admin.name}</div>
                                            {admin.id === auth.user.id && (
                                                <span className="text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full ml-2">You</span>
                                            )}
                                        </td>
                                        <td className="py-4 px-6 text-sm text-gray-600">{admin.email}</td>
                                        <td className="py-4 px-6 text-sm text-gray-600">
                                            {dayjs(admin.created_at).format('MMM D, YYYY')}
                                        </td>
                                        <td className="py-4 px-6 text-right space-x-3">
                                            {admin.id !== auth.user.id && (
                                                <button
                                                    onClick={() => handleDelete(admin.id)}
                                                    className="text-red-500 hover:text-red-700 transition-colors p-1"
                                                    title="Delete"
                                                >
                                                    <Trash2 className="w-4 h-4" />
                                                </button>
                                            )}
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
