import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Index({ auth, payments }) {
    return (
        <AdminLayout user={auth.user}>
            <Head title="Payment records" />
            <h1 className="mb-6 text-2xl font-normal">Payment records</h1>
            <div className="overflow-x-auto rounded-lg bg-white shadow">
                <table className="w-full text-left text-sm">
                    <thead className="border-b bg-gray-50 text-gray-600"><tr>
                        <th className="p-4">Member</th><th className="p-4">Purpose</th>
                        <th className="p-4">Amount</th><th className="p-4">Status</th><th className="p-4">Date</th>
                    </tr></thead>
                    <tbody>{payments.data.map(payment => <tr key={payment.id} className="border-b">
                        <td className="p-4">{payment.member?.full_name || 'Unknown member'}</td>
                        <td className="p-4">{payment.purpose}</td><td className="p-4">₹{payment.amount}</td>
                        <td className="p-4">{payment.status}</td><td className="p-4">{new Date(payment.created_at).toLocaleDateString()}</td>
                    </tr>)}</tbody>
                </table>
                {payments.data.length === 0 && <p className="p-6 text-gray-600">No payment records yet.</p>}
            </div>
            <div className="mt-5 flex gap-4">
                {payments.prev_page_url && <a href={payments.prev_page_url}>Previous</a>}
                {payments.next_page_url && <a href={payments.next_page_url}>Next</a>}
            </div>
        </AdminLayout>
    );
}
