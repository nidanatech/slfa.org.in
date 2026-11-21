import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Attendees({ auth, event }) {
    return (
        <AdminLayout user={auth.user}>
            <Head title={`Admissions for ${event.title}`} />
            <Link href={route('admin.events.index')} className="text-sm text-[#1B7A4A]">Back to events</Link>
            <h1 className="my-6 text-2xl font-normal">Admissions for {event.title}</h1>
            <h2 className="mb-3 text-lg font-normal">Online requests</h2>
            <div className="mb-8 divide-y rounded-lg bg-white shadow">{event.admissions?.length ? event.admissions.map(request => <div key={request.id} className="grid gap-2 p-4 text-sm sm:grid-cols-[1fr_1fr_auto]"><div><strong>{request.name}</strong><p>{request.email}</p><p>{request.phone}</p></div><div><p>Adults: {request.adult_count} · Children: {request.child_count}</p>{request.notes && <p className="mt-1 text-gray-600">{request.notes}</p>}</div><label className="text-xs font-normal text-gray-600">Status<select aria-label={`Status for ${request.name}`} className="mt-1 block rounded border-gray-300 text-sm" value={request.status} onChange={e => router.patch(route('admin.events.admissions.update', [event.id, request.id]), { status: e.target.value }, { preserveScroll: true })}>{['requested', 'confirmed', 'declined', 'cancelled'].map(status => <option key={status} value={status}>{status[0].toUpperCase() + status.slice(1)}</option>)}</select></label></div>) : <p className="p-4 text-gray-600">No admission requests yet.</p>}</div>
            <h2 className="mb-3 text-lg font-normal">Member RSVPs</h2>
            <div className="rounded-lg bg-white shadow">
                {event.rsvps.map(rsvp => <div key={rsvp.id} className="flex justify-between border-b p-4">
                    <span>{rsvp.member?.full_name || 'Unknown member'}</span><span>{rsvp.status}</span>
                </div>)}
                {event.rsvps.length === 0 && <p className="p-6 text-gray-600">No RSVPs yet.</p>}
            </div>
        </AdminLayout>
    );
}
