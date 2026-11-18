import { Head, Link, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { ArrowLeft, Save, ImageIcon } from 'lucide-react';
import toast, { Toaster } from 'react-hot-toast';

export default function Create({ auth }) {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        description: '',
        date: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('admin.albums.store'), {
            onError: (errs) => {
                const firstError = Object.values(errs)[0];
                toast.error(firstError || "Error creating album");
            },
        });
    };

    return (
        <AdminLayout user={auth.user}>
            <Head title="Create Album" />
            <Toaster position="top-right" />

            <div className="mb-6 flex items-center justify-between">
                <div>
                    <Link href={route('admin.albums.index')} className="text-sm text-gray-500 hover:text-slfa-maroon flex items-center mb-2 transition">
                        <ArrowLeft className="w-4 h-4 mr-1" /> Back to Gallery
                    </Link>
                    <h1 className="text-2xl font-bold text-gray-900 flex items-center">
                        <ImageIcon className="w-6 h-6 mr-2 text-slfa-maroon" />
                        Create Album
                    </h1>
                </div>
            </div>

            <div className="bg-white rounded-lg shadow-sm border border-gray-100 p-6 md:p-8 max-w-2xl">
                <form onSubmit={handleSubmit} className="space-y-6">
                    
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">Album Title</label>
                        <input
                            type="text"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            className="w-full border-gray-300 rounded-md shadow-sm focus:ring-slfa-maroon focus:border-slfa-maroon"
                            required
                        />
                        {errors.title && <p className="text-red-500 text-xs mt-1">{errors.title}</p>}
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea
                            rows="3"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            className="w-full border-gray-300 rounded-md shadow-sm focus:ring-slfa-maroon focus:border-slfa-maroon"
                        ></textarea>
                        {errors.description && <p className="text-red-500 text-xs mt-1">{errors.description}</p>}
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">Event Date (Optional)</label>
                        <input
                            type="date"
                            value={data.date}
                            onChange={(e) => setData('date', e.target.value)}
                            className="w-full border-gray-300 rounded-md shadow-sm focus:ring-slfa-maroon focus:border-slfa-maroon"
                        />
                        {errors.date && <p className="text-red-500 text-xs mt-1">{errors.date}</p>}
                    </div>

                    <div className="pt-6 flex justify-end">
                        <button
                            type="submit"
                            disabled={processing}
                            className="bg-slfa-maroon text-white px-6 py-2 rounded-md font-semibold hover:bg-slfa-navy transition-colors flex items-center"
                        >
                            <Save className="w-4 h-4 mr-2" />
                            {processing ? 'Saving...' : 'Create Album'}
                        </button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
