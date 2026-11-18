import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { ArrowLeft, Check, X, FileText, Download } from 'lucide-react';
import dayjs from 'dayjs';
import toast, { Toaster } from 'react-hot-toast';

export default function Show({ auth, member }) {
    const { patch } = useForm();
    const profile = member.profile || {};

    const adminForm = useForm({
        date_of_application: profile.date_of_application ? dayjs(profile.date_of_application).format('YYYY-MM-DD') : '',
        entrance_fee_received: profile.entrance_fee_received ? dayjs(profile.entrance_fee_received).format('YYYY-MM-DD') : '',
        membership_fee_received: profile.membership_fee_received ? dayjs(profile.membership_fee_received).format('YYYY-MM-DD') : '',
        mode_of_payment: profile.mode_of_payment || '',
        payment_reference_number: profile.payment_reference_number || '',
        receipt_number: profile.receipt_number || '',
        payment_date: profile.payment_date ? dayjs(profile.payment_date).format('YYYY-MM-DD') : '',
        proposed_by: profile.proposed_by || '',
        seconded_by: profile.seconded_by || '',
        approved_by: profile.approved_by || '',
        date_of_enrollment: profile.date_of_enrollment ? dayjs(profile.date_of_enrollment).format('YYYY-MM-DD') : '',
        membership_number: profile.membership_number || '',
        admin_remarks: profile.admin_remarks || '',
    });

    const handleAdminSubmit = (e) => {
        e.preventDefault();
        adminForm.put(route('admin.members.update', member.id), {
            onSuccess: () => toast.success('Admin records updated successfully.')
        });
    };

    const handleApprove = () => {
        if (confirm("Are you sure you want to approve this member?")) {
            patch(route('admin.members.approve', member.id), {
                onSuccess: () => toast.success('Member approved successfully.'),
            });
        }
    };

    const handleReject = () => {
        if (confirm("Are you sure you want to reject this member?")) {
            patch(route('admin.members.reject', member.id), {
                onSuccess: () => toast.success('Member rejected successfully.'),
            });
        }
    };

    return (
        <AdminLayout user={auth.user}>
            <Head title={`Member Details - ${member.name}`} />
            <Toaster position="top-right" />

            <div className="mb-6">
                <Link href={route('admin.members.index')} className="text-sm font-medium text-gray-500 hover:text-slfa-primary flex items-center gap-1 mb-4">
                    <ArrowLeft className="w-4 h-4" /> Back to Members
                </Link>
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">{member.name}</h1>
                        <p className="text-gray-500 text-sm mt-1">Application received on {dayjs(member.created_at).format('MMMM D, YYYY')}</p>
                    </div>
                    
                    {member.account_status === 'pending' && (
                        <div className="flex items-center gap-3">
                            <button 
                                onClick={handleReject}
                                className="flex items-center gap-2 px-4 py-2 bg-white border border-red-200 text-red-600 rounded-lg text-sm font-bold hover:bg-red-50 transition-colors"
                            >
                                <X className="w-4 h-4" /> Reject
                            </button>
                            <button 
                                onClick={handleApprove}
                                className="flex items-center gap-2 px-4 py-2 bg-green-500 text-white rounded-lg text-sm font-bold hover:bg-green-600 transition-colors"
                            >
                                <Check className="w-4 h-4" /> Approve Member
                            </button>
                        </div>
                    )}
                </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                {/* Main Content */}
                <div className="lg:col-span-2 space-y-6">
                    
                    {/* Basic Info */}
                    <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div className="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                            <h2 className="font-bold text-gray-900">Application Details</h2>
                        </div>
                        <div className="p-6">
                            <dl className="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-6">
                                <div>
                                    <dt className="text-xs font-bold text-gray-500 uppercase tracking-wide">Membership Type</dt>
                                    <dd className="mt-1 text-sm text-gray-900 capitalize font-medium">{profile.membership_type}</dd>
                                </div>
                                <div>
                                    <dt className="text-xs font-bold text-gray-500 uppercase tracking-wide">Email Address</dt>
                                    <dd className="mt-1 text-sm text-gray-900">{member.email}</dd>
                                </div>
                                <div>
                                    <dt className="text-xs font-bold text-gray-500 uppercase tracking-wide">Mobile Number</dt>
                                    <dd className="mt-1 text-sm text-gray-900">{profile.mobile || '-'}</dd>
                                </div>
                                <div>
                                    <dt className="text-xs font-bold text-gray-500 uppercase tracking-wide">WhatsApp</dt>
                                    <dd className="mt-1 text-sm text-gray-900">{profile.whatsapp || '-'}</dd>
                                </div>
                                <div className="sm:col-span-2">
                                    <dt className="text-xs font-bold text-gray-500 uppercase tracking-wide">Address</dt>
                                    <dd className="mt-1 text-sm text-gray-900">{profile.address || '-'}</dd>
                                </div>
                                
                                {profile.membership_type === 'individual' && (
                                    <>
                                        <div>
                                            <dt className="text-xs font-bold text-gray-500 uppercase tracking-wide">Date of Birth</dt>
                                            <dd className="mt-1 text-sm text-gray-900">{profile.date_of_birth ? dayjs(profile.date_of_birth).format('MMM D, YYYY') : '-'}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-xs font-bold text-gray-500 uppercase tracking-wide">Gender</dt>
                                            <dd className="mt-1 text-sm text-gray-900">{profile.gender || '-'}</dd>
                                        </div>
                                        <div className="sm:col-span-2">
                                            <dt className="text-xs font-bold text-gray-500 uppercase tracking-wide">Occupation</dt>
                                            <dd className="mt-1 text-sm text-gray-900">{profile.occupation || '-'}</dd>
                                        </div>
                                    </>
                                )}

                                {profile.membership_type === 'business' && (
                                    <>
                                        <div className="sm:col-span-2">
                                            <dt className="text-xs font-bold text-gray-500 uppercase tracking-wide">Business Name</dt>
                                            <dd className="mt-1 text-sm text-gray-900 font-bold">{profile.business_name || '-'}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-xs font-bold text-gray-500 uppercase tracking-wide">Nature of Business</dt>
                                            <dd className="mt-1 text-sm text-gray-900">{profile.nature_of_business || '-'}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-xs font-bold text-gray-500 uppercase tracking-wide">Date of Incorporation</dt>
                                            <dd className="mt-1 text-sm text-gray-900">{profile.date_of_incorporation ? dayjs(profile.date_of_incorporation).format('MMM D, YYYY') : '-'}</dd>
                                        </div>
                                    </>
                                )}
                            </dl>
                        </div>
                    </div>

                    {/* Family / Business Reps */}
                    {(member.family_members?.length > 0 || member.business_reps?.length > 0) && (
                        <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <div className="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                                <h2 className="font-bold text-gray-900">
                                    {profile.membership_type === 'individual' ? 'Family Members' : 'Business Representatives'}
                                </h2>
                            </div>
                            <div className="divide-y divide-gray-100">
                                {(profile.membership_type === 'individual' ? member.family_members : member.business_reps)?.map((rep, idx) => (
                                    <div key={idx} className="p-6">
                                        <p className="font-bold text-gray-900">{rep.name}</p>
                                        <div className="flex gap-4 mt-1 text-sm text-gray-500">
                                            {rep.relationship && <span>Rel: {rep.relationship}</span>}
                                            {rep.designation && <span>Role: {rep.designation}</span>}
                                            <span>Gender: {rep.gender}</span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Admin Fields */}
                    <div className="bg-white rounded-xl shadow-sm border border-slfa-gold/30 overflow-hidden">
                        <div className="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                            <h2 className="font-bold text-slfa-navy flex items-center">
                                <FileText className="w-5 h-5 mr-2 text-slfa-maroon" />
                                Official Admin Records
                            </h2>
                        </div>
                        <div className="p-6">
                            <form onSubmit={handleAdminSubmit} className="space-y-6">
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    <div>
                                        <label className="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Date of Application</label>
                                        <input type="date" value={adminForm.data.date_of_application} onChange={e => adminForm.setData('date_of_application', e.target.value)} className="w-full text-sm border-gray-300 rounded-md focus:ring-slfa-maroon focus:border-slfa-maroon" />
                                    </div>
                                    <div>
                                        <label className="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Date of Enrollment</label>
                                        <input type="date" value={adminForm.data.date_of_enrollment} onChange={e => adminForm.setData('date_of_enrollment', e.target.value)} className="w-full text-sm border-gray-300 rounded-md focus:ring-slfa-maroon focus:border-slfa-maroon" />
                                    </div>
                                    <div>
                                        <label className="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Membership Number</label>
                                        <input type="text" value={adminForm.data.membership_number} onChange={e => adminForm.setData('membership_number', e.target.value)} className="w-full text-sm border-gray-300 rounded-md focus:ring-slfa-maroon focus:border-slfa-maroon" />
                                    </div>
                                    
                                    <div>
                                        <label className="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Entrance Fee Received</label>
                                        <input type="date" value={adminForm.data.entrance_fee_received} onChange={e => adminForm.setData('entrance_fee_received', e.target.value)} className="w-full text-sm border-gray-300 rounded-md focus:ring-slfa-maroon focus:border-slfa-maroon" />
                                    </div>
                                    <div>
                                        <label className="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Membership Fee Received</label>
                                        <input type="date" value={adminForm.data.membership_fee_received} onChange={e => adminForm.setData('membership_fee_received', e.target.value)} className="w-full text-sm border-gray-300 rounded-md focus:ring-slfa-maroon focus:border-slfa-maroon" />
                                    </div>
                                    <div>
                                        <label className="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Mode of Payment</label>
                                        <input type="text" value={adminForm.data.mode_of_payment} onChange={e => adminForm.setData('mode_of_payment', e.target.value)} className="w-full text-sm border-gray-300 rounded-md focus:ring-slfa-maroon focus:border-slfa-maroon" />
                                    </div>

                                    <div>
                                        <label className="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Payment Reference</label>
                                        <input type="text" value={adminForm.data.payment_reference_number} onChange={e => adminForm.setData('payment_reference_number', e.target.value)} className="w-full text-sm border-gray-300 rounded-md focus:ring-slfa-maroon focus:border-slfa-maroon" />
                                    </div>
                                    <div>
                                        <label className="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Receipt Number</label>
                                        <input type="text" value={adminForm.data.receipt_number} onChange={e => adminForm.setData('receipt_number', e.target.value)} className="w-full text-sm border-gray-300 rounded-md focus:ring-slfa-maroon focus:border-slfa-maroon" />
                                    </div>
                                    <div>
                                        <label className="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Payment Date</label>
                                        <input type="date" value={adminForm.data.payment_date} onChange={e => adminForm.setData('payment_date', e.target.value)} className="w-full text-sm border-gray-300 rounded-md focus:ring-slfa-maroon focus:border-slfa-maroon" />
                                    </div>

                                    <div>
                                        <label className="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Proposed By</label>
                                        <input type="text" value={adminForm.data.proposed_by} onChange={e => adminForm.setData('proposed_by', e.target.value)} className="w-full text-sm border-gray-300 rounded-md focus:ring-slfa-maroon focus:border-slfa-maroon" />
                                    </div>
                                    <div>
                                        <label className="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Seconded By</label>
                                        <input type="text" value={adminForm.data.seconded_by} onChange={e => adminForm.setData('seconded_by', e.target.value)} className="w-full text-sm border-gray-300 rounded-md focus:ring-slfa-maroon focus:border-slfa-maroon" />
                                    </div>
                                    <div>
                                        <label className="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Approved By</label>
                                        <input type="text" value={adminForm.data.approved_by} onChange={e => adminForm.setData('approved_by', e.target.value)} className="w-full text-sm border-gray-300 rounded-md focus:ring-slfa-maroon focus:border-slfa-maroon" />
                                    </div>
                                </div>
                                
                                <div>
                                    <label className="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Remarks</label>
                                    <textarea rows="3" value={adminForm.data.admin_remarks} onChange={e => adminForm.setData('admin_remarks', e.target.value)} className="w-full text-sm border-gray-300 rounded-md focus:ring-slfa-maroon focus:border-slfa-maroon"></textarea>
                                </div>

                                <div className="flex justify-end pt-4">
                                    <button 
                                        type="submit" 
                                        disabled={adminForm.processing}
                                        className="bg-slfa-maroon text-white px-6 py-2 rounded-lg font-bold hover:bg-slfa-navy transition-colors shadow-sm disabled:opacity-50"
                                    >
                                        {adminForm.processing ? 'Saving...' : 'Save Admin Records'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                {/* Sidebar */}
                <div className="space-y-6">
                    {/* Status Card */}
                    <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 text-center">
                        <div className="w-16 h-16 rounded-full bg-slfa-peach mx-auto flex items-center justify-center font-bold text-2xl text-slfa-coral mb-4">
                            {member.name.charAt(0)}
                        </div>
                        <h3 className="font-bold text-gray-900 text-lg">{member.name}</h3>
                        <p className="text-sm text-gray-500 mb-4 capitalize">{profile.membership_type}</p>
                        <span className={`inline-flex items-center px-3 py-1 rounded-full text-xs font-bold capitalize ${
                            member.account_status === 'active' ? 'bg-green-100 text-green-800' :
                            member.account_status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                            'bg-red-100 text-red-800'
                        }`}>
                            Status: {member.account_status}
                        </span>
                    </div>

                    {/* Sri Lanka Connection */}
                    <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div className="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                            <h3 className="font-bold text-gray-900 text-sm">Sri Lankan Connection</h3>
                        </div>
                        <div className="p-6">
                            {profile.connection_with_srilanka ? (
                                <ul className="list-disc list-inside text-sm text-gray-700 space-y-1">
                                    {profile.connection_with_srilanka.map((c, i) => (
                                        <li key={i}>{c}</li>
                                    ))}
                                </ul>
                            ) : (
                                <p className="text-sm text-gray-500">Not provided</p>
                            )}
                        </div>
                    </div>

                    {/* Document Uploads */}
                    <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div className="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                            <h3 className="font-bold text-gray-900 text-sm">Uploaded Documents</h3>
                        </div>
                        <div className="p-6">
                            {profile.id_proof_path ? (
                                <a 
                                    href={`/storage/${profile.id_proof_path}`} 
                                    target="_blank" 
                                    rel="noreferrer"
                                    className="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:border-slfa-primary transition-colors group"
                                >
                                    <div className="flex items-center gap-3">
                                        <FileText className="w-5 h-5 text-gray-400 group-hover:text-slfa-primary" />
                                        <div>
                                            <p className="text-sm font-medium text-gray-900">ID Proof</p>
                                            <p className="text-xs text-gray-500">View Document</p>
                                        </div>
                                    </div>
                                    <Download className="w-4 h-4 text-gray-400 group-hover:text-slfa-primary" />
                                </a>
                            ) : (
                                <p className="text-sm text-gray-500">No documents uploaded.</p>
                            )}
                        </div>
                    </div>

                </div>

            </div>
        </AdminLayout>
    );
}
