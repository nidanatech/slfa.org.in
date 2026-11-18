import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Check, X, Search, Filter, Eye } from 'lucide-react';
import dayjs from 'dayjs';
import toast, { Toaster } from 'react-hot-toast';

export default function Index({ auth, members, filters }) {
    const { patch } = useForm();

    const handleApprove = (id) => {
        if (confirm("Are you sure you want to approve this member?")) {
            patch(route('admin.members.approve', id), {
                onSuccess: () => toast.success('Member approved successfully.'),
            });
        }
    };

    const handleReject = (id) => {
        if (confirm("Are you sure you want to reject this member?")) {
            patch(route('admin.members.reject', id), {
                onSuccess: () => toast.success('Member rejected successfully.'),
            });
        }
    };

    return (
        <AdminLayout user={auth.user}>
            <Head title="Manage Members" />
            <Toaster position="top-right" />

            <div className="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Members</h1>
                    <p className="text-gray-500 text-sm mt-1">Manage membership applications and active users.</p>
                </div>
                
                <div className="flex items-center gap-3">
                    <div className="relative">
                        <Search className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                        <input 
                            type="text" 
                            placeholder="Search members..." 
                            className="pl-9 pr-4 py-2 border border-gray-200 rounded-lg text-sm focus:ring-slfa-primary focus:border-slfa-primary w-full sm:w-64"
                        />
                    </div>
                    <button className="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <Filter className="w-4 h-4" />
                        Filters
                    </button>
                    <Link href={route('admin.members.create')} className="flex items-center gap-2 px-4 py-2 bg-slfa-maroon text-white rounded-lg text-sm font-semibold hover:bg-slfa-navy transition-colors">
                        Add Member
                    </Link>
                </div>
            </div>

            <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Member</th>
                                <th className="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Type</th>
                                <th className="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Applied On</th>
                                <th className="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                <th className="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {members.data.length > 0 ? members.data.map(member => (
                                <tr key={member.id} className="hover:bg-gray-50 transition-colors">
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <div className="flex items-center">
                                            <div className="w-10 h-10 rounded-full bg-slfa-peach text-slfa-coral flex items-center justify-center font-bold">
                                                {member.name.charAt(0)}
                                            </div>
                                            <div className="ml-4">
                                                <div className="text-sm font-bold text-gray-900">{member.name}</div>
                                                <div className="text-sm text-gray-500">{member.email}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <div className="text-sm text-gray-900 capitalize">{member.profile?.membership_type || 'Unknown'}</div>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {dayjs(member.created_at).format('MMM D, YYYY')}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize ${
                                            member.account_status === 'active' ? 'bg-green-100 text-green-800' :
                                            member.account_status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                            'bg-red-100 text-red-800'
                                        }`}>
                                            {member.account_status}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div className="flex justify-end gap-2">
                                            <Link 
                                                href={route('admin.members.show', member.id)}
                                                className="text-gray-400 hover:text-slfa-primary p-2 rounded-full hover:bg-gray-100 transition-colors"
                                                title="View Details"
                                            >
                                                <Eye className="w-5 h-5" />
                                            </Link>
                                            
                                            {member.account_status === 'pending' && (
                                                <>
                                                    <button 
                                                        onClick={() => handleApprove(member.id)}
                                                        className="text-green-500 hover:bg-green-50 p-2 rounded-full transition-colors"
                                                        title="Approve"
                                                    >
                                                        <Check className="w-5 h-5" />
                                                    </button>
                                                    <button 
                                                        onClick={() => handleReject(member.id)}
                                                        className="text-red-500 hover:bg-red-50 p-2 rounded-full transition-colors"
                                                        title="Reject"
                                                    >
                                                        <X className="w-5 h-5" />
                                                    </button>
                                                </>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            )) : (
                                <tr>
                                    <td colSpan="5" className="px-6 py-8 text-center text-gray-500">
                                        No members found.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
            
        </AdminLayout>
    );
}
