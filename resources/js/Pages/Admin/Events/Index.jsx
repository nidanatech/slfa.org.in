import { Head, Link, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Calendar, Plus, Trash2, Edit, Users } from 'lucide-react';
import dayjs from 'dayjs';
import toast, { Toaster } from 'react-hot-toast';

export default function Index({ auth, events }) {
    const { delete: destroy } = useForm();

    const handleDelete = (id) => {
        if (confirm("Are you sure you want to delete this event?")) {
            destroy(route('admin.events.destroy', id), {
                onSuccess: () => toast.success('Event deleted successfully.'),
                onError: (errs) => toast.error(Object.values(errs)[0] || 'Error deleting event.')
            });
        }
    };

    return (
        <AdminLayout user={auth.user}>
            <Head title="Manage Events" />
            <Toaster position="top-right" />

            <div className="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-normal text-gray-900 flex items-center">
                        <Calendar className="w-6 h-6 mr-2 text-[#1B7A4A]" />
                        Events
                    </h1>
                    <p className="text-sm text-gray-500 mt-1">Manage events, admissions, and RSVPs.</p>
                </div>
                <div>
                    <Link href={route('admin.events.create')} className="bg-[#1B7A4A] text-white px-4 py-2 rounded-md font-normal text-sm hover:bg-[#1B7A4A] transition flex items-center">
                        <Plus className="w-4 h-4 mr-1" /> Create Event
                    </Link>
                </div>
            </div>

            <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="w-full text-left border-collapse">
                        <thead>
                            <tr className="bg-gray-50 border-b border-gray-100">
                                <th className="py-3 px-6 text-xs font-normal text-gray-500 uppercase tracking-wider">Title</th>
                                <th className="py-3 px-6 text-xs font-normal text-gray-500 uppercase tracking-wider">Date & Time</th>
                                <th className="py-3 px-6 text-xs font-normal text-gray-500 uppercase tracking-wider">Location</th>
                                <th className="py-3 px-6 text-xs font-normal text-gray-500 uppercase tracking-wider text-center">Requests / RSVPs</th>
                                <th className="py-3 px-6 text-xs font-normal text-gray-500 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {events.data.length === 0 ? (
                                <tr>
                                    <td colSpan="5" className="py-8 px-6 text-center text-gray-500">
                                        No events found.
                                    </td>
                                </tr>
                            ) : (
                                events.data.map(event => (
                                    <tr key={event.id} className="hover:bg-gray-50/50 transition-colors">
                                        <td className="py-4 px-6 font-normal text-gray-900">{event.title}</td>
                                        <td className="py-4 px-6 text-sm text-gray-600">
                                            {dayjs(event.start_at).format('MMM D, YYYY h:mm A')}
                                        </td>
                                        <td className="py-4 px-6 text-sm text-gray-600">{event.location || '-'}</td>
                                        <td className="py-4 px-6 text-center">
                                            <Link href={route('admin.events.attendees', event.id)} className="inline-flex items-center text-[#1B7A4A] hover:underline font-normal">
                                                <Users className="w-4 h-4 mr-1" />
                                                {event.admissions_count || 0} / {event.rsvps_count || 0}
                                            </Link>
                                        </td>
                                        <td className="py-4 px-6 text-right space-x-3">
                                            <Link
                                                href={route('admin.events.edit', event.id)}
                                                className="text-blue-500 hover:text-blue-700 transition-colors p-1"
                                            >
                                                <Edit className="w-4 h-4 inline" />
                                            </Link>
                                            <button
                                                onClick={() => handleDelete(event.id)}
                                                className="text-red-500 hover:text-red-700 transition-colors p-1"
                                                title="Delete"
                                            >
                                                <Trash2 className="w-4 h-4 inline" />
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
