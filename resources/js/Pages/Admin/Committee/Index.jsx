import { Head, Link, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Settings, Plus, Trash2, Edit } from 'lucide-react';
import toast, { Toaster } from 'react-hot-toast';

export default function Index({ auth, leadershipSetup, executiveCommittee }) {
    const { delete: destroy } = useForm();

    const handleDelete = (id) => {
        if (confirm("Are you sure you want to remove this committee member?")) {
            destroy(route('admin.committee.destroy', id), {
                onSuccess: () => toast.success('Member removed.'),
            });
        }
    };

    const renderTable = (members, title) => (
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
            <div className="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                <h2 className="font-normal text-gray-900">{title}</h2>
            </div>
            <div className="overflow-x-auto">
                <table className="w-full text-left border-collapse">
                    <thead>
                        <tr className="bg-white border-b border-gray-100">
                            <th className="py-3 px-6 text-xs font-normal text-gray-500 uppercase tracking-wider">Image</th>
                            <th className="py-3 px-6 text-xs font-normal text-gray-500 uppercase tracking-wider">Name & Role</th>
                            <th className="py-3 px-6 text-xs font-normal text-gray-500 uppercase tracking-wider">Company</th>
                            <th className="py-3 px-6 text-xs font-normal text-gray-500 uppercase tracking-wider">Visible</th>
                            <th className="py-3 px-6 text-xs font-normal text-gray-500 uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100">
                        {members.length === 0 ? (
                            <tr>
                                <td colSpan="5" className="py-8 px-6 text-center text-gray-500">
                                    No members found in this group.
                                </td>
                            </tr>
                        ) : (
                            members.map(member => (
                                <tr key={member.id} className="hover:bg-gray-50/50 transition-colors">
                                    <td className="py-3 px-6 w-16">
                                        <div className="w-10 h-10 rounded-full overflow-hidden bg-gray-200">
                                            {member.photo_path ? (
                                                <img src={member.photo_path.startsWith('/') ? member.photo_path : `/storage/${member.photo_path}`} alt={member.name} className="w-full h-full object-cover" />
                                            ) : (
                                                <div className="w-full h-full flex items-center justify-center text-gray-400">?</div>
                                            )}
                                        </div>
                                    </td>
                                    <td className="py-4 px-6">
                                        <div className="font-normal text-gray-900">{member.name}</div>
                                        <div className="text-sm text-gray-500">{member.role}</div>
                                    </td>
                                    <td className="py-4 px-6">
                                        <div className="text-sm text-gray-900">{member.company || '-'}</div>
                                        <div className="text-xs text-gray-500">{member.designation}</div>
                                    </td>
                                    <td className="py-4 px-6">
                                        {member.show_in_website ? (
                                            <span className="px-2 inline-flex text-xs leading-5 font-normal rounded-full bg-green-100 text-green-800">Yes</span>
                                        ) : (
                                            <span className="px-2 inline-flex text-xs leading-5 font-normal rounded-full bg-gray-100 text-gray-800">No</span>
                                        )}
                                    </td>
                                    <td className="py-4 px-6 text-right space-x-3">
                                        <Link
                                            href={route('admin.committee.edit', member.id)}
                                            className="text-blue-500 hover:text-blue-700 transition-colors inline-block p-1"
                                            title="Edit"
                                        >
                                            <Edit className="w-4 h-4" />
                                        </Link>
                                        <button
                                            onClick={() => handleDelete(member.id)}
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
    );

    return (
        <AdminLayout user={auth.user}>
            <Head title="Manage Committee" />
            <Toaster position="top-right" />

            <div className="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-normal text-gray-900 flex items-center">
                        <Settings className="w-6 h-6 mr-2 text-[#1B7A4A]" />
                        Committee Members
                    </h1>
                    <p className="text-sm text-gray-500 mt-1">Manage the leadership and executive committees.</p>
                </div>
                <div>
                    <Link href={route('admin.committee.create')} className="bg-[#1B7A4A] text-white px-4 py-2 rounded-md font-normal text-sm hover:bg-[#1B7A4A] transition flex items-center">
                        <Plus className="w-4 h-4 mr-1" /> Add Member
                    </Link>
                </div>
            </div>

            {renderTable(leadershipSetup, "Leadership")}
            {renderTable(executiveCommittee, "Executive Committee")}

        </AdminLayout>
    );
}
