import { Head, Link, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { ArrowLeft, Save, Calendar } from 'lucide-react';
import toast, { Toaster } from 'react-hot-toast';

export default function Create({ auth }) {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        description: '',
        event_type: 'In-Person',
        location: '',
        start_at: '',
        end_at: '',
        registration_deadline: '',
        max_attendees: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('admin.events.store'), {
            onError: (errs) => {
                const firstError = Object.values(errs)[0];
                toast.error(firstError || "Error creating event");
            },
        });
    };

    return (
        <AdminLayout user={auth.user}>
            <Head title="Create Event" />
            <Toaster position="top-right" />

            <div className="mb-6 flex items-center justify-between">
                <div>
                    <Link href={route('admin.events.index')} className="text-sm text-gray-500 hover:text-slfa-maroon flex items-center mb-2 transition">
                        <ArrowLeft className="w-4 h-4 mr-1" /> Back to Events
                    </Link>
                    <h1 className="text-2xl font-bold text-gray-900 flex items-center">
                        <Calendar className="w-6 h-6 mr-2 text-slfa-maroon" />
                        Create Event
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
                                <option value="In-Person">In-Person</option>
                                <option value="Virtual">Virtual</option>
                                <option value="Hybrid">Hybrid</option>
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

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Max Attendees (Leave blank for unlimited)</label>
                            <input
                                type="number"
                                value={data.max_attendees}
                                onChange={(e) => setData('max_attendees', e.target.value)}
                                className="w-full border-gray-300 rounded-md shadow-sm focus:ring-slfa-maroon focus:border-slfa-maroon"
                            />
                            {errors.max_attendees && <p className="text-red-500 text-xs mt-1">{errors.max_attendees}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Registration Deadline</label>
                            <input
                                type="datetime-local"
                                value={data.registration_deadline}
                                onChange={(e) => setData('registration_deadline', e.target.value)}
                                className="w-full border-gray-300 rounded-md shadow-sm focus:ring-slfa-maroon focus:border-slfa-maroon"
                            />
                            {errors.registration_deadline && <p className="text-red-500 text-xs mt-1">{errors.registration_deadline}</p>}
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
