import { Head, Link, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { ArrowLeft, Save, Calendar } from 'lucide-react';
import toast, { Toaster } from 'react-hot-toast';

export default function Create({ auth, event }) {
    const { data, setData, post, put, processing, errors } = useForm({
        title: event?.title || '',
        description: event?.description || '',
        event_type: event?.event_type || 'Other',
        location: event?.location || '',
        start_at: event?.start_at?.slice(0, 16) || '',
        end_at: event?.end_at?.slice(0, 16) || '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        const submit = event ? put : post;
        submit(event ? route('admin.events.update', event.id) : route('admin.events.store'), {
            onError: (errs) => {
                const firstError = Object.values(errs)[0];
                toast.error(firstError || "Error creating event");
            },
        });
    };

    return (
        <AdminLayout user={auth.user}>
            <Head title={event ? 'Edit Event' : 'Create Event'} />
            <Toaster position="top-right" />

            <div className="mb-6 flex items-center justify-between">
                <div>
                    <Link href={route('admin.events.index')} className="text-sm text-gray-500 hover:text-slfa-maroon flex items-center mb-2 transition">
                        <ArrowLeft className="w-4 h-4 mr-1" /> Back to Events
                    </Link>
                    <h1 className="text-2xl font-bold text-gray-900 flex items-center">
                        <Calendar className="w-6 h-6 mr-2 text-slfa-maroon" />
                        {event ? 'Edit Event' : 'Create Event'}
                    </h1>
                </div>
            </div>

            <div className="bg-white rounded-lg shadow-sm border border-gray-100 p-6 md:p-8 max-w-4xl">
                <form onSubmit={handleSubmit} className="space-y-6">
                    
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div className="md:col-span-2">
                            <label className="block text-sm font-medium text-gray-700 mb-2">Event Title</label>
                            <input
                                type="text"
                                value={data.title}
                                onChange={(e) => setData('title', e.target.value)}
                                className="w-full border-gray-300 rounded-md shadow-sm focus:ring-slfa-maroon focus:border-slfa-maroon"
                                required
                            />
                            {errors.title && <p className="text-red-500 text-xs mt-1">{errors.title}</p>}
                        </div>

                        <div className="md:col-span-2">
                            <label className="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea
                                rows="4"
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                className="w-full border-gray-300 rounded-md shadow-sm focus:ring-slfa-maroon focus:border-slfa-maroon"
                            ></textarea>
                            {errors.description && <p className="text-red-500 text-xs mt-1">{errors.description}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Event Type</label>
                            <select
                                value={data.event_type}
                                onChange={(e) => setData('event_type', e.target.value)}
                                className="w-full border-gray-300 rounded-md shadow-sm focus:ring-slfa-maroon focus:border-slfa-maroon"
                            >
                                {['AGM', 'Get-Together', 'Medical Camp', 'Eye Camp', 'Health Checkup', 'Other'].map(type => (
                                    <option key={type} value={type}>{type}</option>
                                ))}
                            </select>
                            {errors.event_type && <p className="text-red-500 text-xs mt-1">{errors.event_type}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Location / Link</label>
                            <input
                                type="text"
                                value={data.location}
                                onChange={(e) => setData('location', e.target.value)}
                                className="w-full border-gray-300 rounded-md shadow-sm focus:ring-slfa-maroon focus:border-slfa-maroon"
                            />
                            {errors.location && <p className="text-red-500 text-xs mt-1">{errors.location}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Start Time</label>
                            <input
                                type="datetime-local"
                                value={data.start_at}
                                onChange={(e) => setData('start_at', e.target.value)}
                                className="w-full border-gray-300 rounded-md shadow-sm focus:ring-slfa-maroon focus:border-slfa-maroon"
                                required
                            />
                            {errors.start_at && <p className="text-red-500 text-xs mt-1">{errors.start_at}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">End Time (Optional)</label>
                            <input
                                type="datetime-local"
                                value={data.end_at}
                                onChange={(e) => setData('end_at', e.target.value)}
                                className="w-full border-gray-300 rounded-md shadow-sm focus:ring-slfa-maroon focus:border-slfa-maroon"
                            />
                            {errors.end_at && <p className="text-red-500 text-xs mt-1">{errors.end_at}</p>}
                        </div>

                    </div>

                    <div className="pt-6 flex justify-end">
                        <button
                            type="submit"
                            disabled={processing}
                            className="bg-slfa-maroon text-white px-6 py-2 rounded-md font-semibold hover:bg-slfa-navy transition-colors flex items-center"
                        >
                            <Save className="w-4 h-4 mr-2" />
                            {processing ? 'Saving...' : 'Create Event'}
                        </button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
