import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Users, FileText, Calendar, CreditCard, ChevronRight, Activity, Clock } from 'lucide-react';

export default function Dashboard({ auth, stats, recent_applications, recent_payments }) {

    const StatCard = ({ title, value, icon: Icon, colorClass, link }) => (
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-start justify-between group">
            <div>
                <p className="text-sm font-medium text-gray-500 mb-1">{title}</p>
                <h3 className="text-3xl font-normal text-gray-900">{value}</h3>
            </div>
            <div className={`p-3 rounded-lg ${colorClass}`}>
                <Icon className="w-6 h-6" />
            </div>
        </div>
    );

    return (
        <AdminLayout user={auth.user}>
            <Head title="Admin Dashboard" />

            <div className="mb-8">
                <h1 className="text-2xl font-normal text-gray-900">Dashboard</h1>
                <p className="text-gray-500 text-sm mt-1">Overview of the SLFA platform.</p>
            </div>

            {/* Stats Grid */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <StatCard
                    title="Active Members"
                    value={stats.total_members}
                    icon={Users}
                    colorClass="bg-blue-50 text-blue-600"
                />
                <StatCard
                    title="Pending Approvals"
                    value={stats.pending_approvals}
                    icon={Clock}
                    colorClass="bg-yellow-50 text-yellow-600"
                />
                <StatCard
                    title="Upcoming Events"
                    value={stats.upcoming_events}
                    icon={Calendar}
                    colorClass="bg-purple-50 text-purple-600"
                />
                <StatCard
                    title="Total Revenue"
                    value={`₹${stats.total_revenue.toLocaleString()}`}
                    icon={CreditCard}
                    colorClass="bg-green-50 text-green-600"
                />
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">

                {/* Recent Applications */}
                <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div className="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                        <h2 className="font-normal text-gray-900 flex items-center">
                            <Activity className="w-4 h-4 mr-2 text-[#1B7A4A]" />
                            Recent Applications
                        </h2>
                        <Link href={route('admin.members.index')} className="text-sm text-[#1B7A4A] font-medium hover:underline">View All</Link>
                    </div>
                    <div className="divide-y divide-gray-100">
                        {recent_applications.length > 0 ? recent_applications.map(member => (
                            <div key={member.id} className="p-6 flex items-center justify-between">
                                <div className="flex items-center gap-4">
                                    <div className="w-10 h-10 rounded-full bg-[#f3eddf] text-[#1B7A4A] flex items-center justify-center font-normal shrink-0">
                                        {member.name.charAt(0)}
                                    </div>
                                    <div>
                                        <p className="text-sm font-normal text-gray-900">{member.name}</p>
                                        <p className="text-xs text-gray-500">{member.email}</p>
                                        <span className="inline-block mt-1 text-[10px] font-normal bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded-full">
                                            Pending Review
                                        </span>
                                    </div>
                                </div>
                                <Link
                                    href={route('admin.members.show', member.id)}
                                    className="p-2 text-gray-400 hover:text-[#1B7A4A] hover:bg-[#f3eddf] rounded-full transition"
                                >
                                    <ChevronRight className="w-5 h-5" />
                                </Link>
                            </div>
                        )) : (
                            <div className="p-8 text-center text-gray-500 text-sm">
                                No pending applications found.
                            </div>
                        )}
                    </div>
                </div>

                {/* Recent Payments */}
                <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div className="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                        <h2 className="font-normal text-gray-900 flex items-center">
                            <CreditCard className="w-4 h-4 mr-2 text-green-600" />
                            Recent Payments
                        </h2>
                        <Link href={route('admin.payments.index')} className="text-sm text-[#1B7A4A] font-medium hover:underline">View All</Link>
                    </div>
                    <div className="divide-y divide-gray-100">
                        {recent_payments.length > 0 ? recent_payments.map(payment => (
                            <div key={payment.id} className="p-6 flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-normal text-gray-900 mb-1">₹{payment.amount}</p>
                                    <p className="text-xs text-gray-500">{payment.member.full_name} • <span className="capitalize">{payment.purpose.replace('_', ' ')}</span></p>
                                </div>
                                <div className="text-right">
                                    <span className="inline-block text-[10px] font-normal bg-green-100 text-green-800 px-2 py-0.5 rounded-full mb-1">
                                        Paid
                                    </span>
                                    <p className="text-xs text-gray-400">{new Date(payment.created_at).toLocaleDateString()}</p>
                                </div>
                            </div>
                        )) : (
                            <div className="p-8 text-center text-gray-500 text-sm">
                                No recent payments.
                            </div>
                        )}
                    </div>
                </div>

            </div>
        </AdminLayout>
    );
}
