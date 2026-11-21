import { Head, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Index({ auth, messages }) {
    return (
        <AdminLayout user={auth.user}>
            <Head title="Contact messages" />
            <h1 className="mb-6 text-2xl font-normal">Contact messages</h1>
            <div className="space-y-4">
                {messages.data.map(message => <article key={message.id} className="rounded-lg bg-white p-5 shadow">
                    <div className="flex justify-between gap-4">
                        <div><strong>{message.name}</strong> · <a href={`mailto:${message.email}`}>{message.email}</a></div>
                        <span className="text-sm text-gray-500">{new Date(message.created_at).toLocaleDateString()}</span>
                    </div>
                    <p className="mt-3 whitespace-pre-wrap text-gray-700">{message.message}</p>
                    {!message.read && <button className="mt-3 text-sm text-[#1B7A4A]"
                        onClick={() => router.patch(route('admin.messages.read', message.id))}>Mark as read</button>}
                </article>)}
                {messages.data.length === 0 && <p className="rounded-lg bg-white p-6 text-gray-600">No messages yet.</p>}
            </div>
            <div className="mt-5 flex gap-4">
                {messages.prev_page_url && <a href={messages.prev_page_url}>Previous</a>}
                {messages.next_page_url && <a href={messages.next_page_url}>Next</a>}
            </div>
        </AdminLayout>
    );
}
