import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Attendees({ auth, event }) {
    return (
        <AdminLayout user={auth.user}>
            <Head title={`RSVPs for ${event.title}`} />
            <Link href={route('admin.events.index')} className="text-sm text-slfa-maroon">Back to events</Link>
            <h1 className="my-6 text-2xl font-bold">RSVPs for {event.title}</h1>
            <div className="rounded-lg bg-white shadow">
                {event.rsvps.map(rsvp => <div key={rsvp.id} className="flex justify-between border-b p-4">
                    <span>{rsvp.member?.full_name || 'Unknown member'}</span><span>{rsvp.status}</span>
                </div>)}
                {event.rsvps.length === 0 && <p className="p-6 text-gray-600">No RSVPs yet.</p>}
            </div>
        </AdminLayout>
    );
}
