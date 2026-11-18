import { Head, Link, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { ArrowLeft, Save, UserCheck } from 'lucide-react';
import toast, { Toaster } from 'react-hot-toast';
import { useState } from 'react';

export default function Create({ auth }) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        role: '',
        company: '',
        designation: '',
        type: 'Executive',
        show_in_website: false,
        display_order: 0,
        photo: null,
    });

    const [preview, setPreview] = useState(null);

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('admin.committee.store'), {
            onError: (errs) => {
                const firstError = Object.values(errs)[0];
                toast.error(firstError || "Error creating member");
            },
        });
    };

    const handlePhotoChange = (e) => {
        const file = e.target.files[0];
        setData('photo', file);
        if (file) {
            setPreview(URL.createObjectURL(file));
        } else {
            setPreview(null);
        }
    };

    return (
        <AdminLayout user={auth.user}>
            <Head title="Add Committee Member" />
            <Toaster position="top-right" />

            <div className="mb-6 flex items-center justify-between">
                <div>
                    <Link href={route('admin.committee.index')} className="text-sm text-gray-500 hover:text-slfa-maroon flex items-center mb-2 transition">
                        <ArrowLeft className="w-4 h-4 mr-1" /> Back to Committee
                    </Link>
                    <h1 className="text-2xl font-bold text-gray-900 flex items-center">
                        <UserCheck className="w-6 h-6 mr-2 text-slfa-maroon" />
                        Add Committee Member
                    </h1>
                </div>
            </div>

            <div className="bg-white rounded-lg shadow-sm border border-gray-100 p-6 md:p-8 max-w-3xl">
                <form onSubmit={handleSubmit} className="space-y-6">
                    
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Name</label>
                            <input
                                type="text"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                className="w-full border-gray-300 rounded-md shadow-sm focus:ring-slfa-maroon focus:border-slfa-maroon"
                                required
                            />
                            {errors.name && <p className="text-red-500 text-xs mt-1">{errors.name}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Role (e.g., President, Member)</label>
                            <input
                                type="text"
                                value={data.role}
                                onChange={(e) => setData('role', e.target.value)}
                                className="w-full border-gray-300 rounded-md shadow-sm focus:ring-slfa-maroon focus:border-slfa-maroon"
                                required
                            />
                            {errors.role && <p className="text-red-500 text-xs mt-1">{errors.role}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Company Name (Occupation)</label>
                            <input
                                type="text"
                                value={data.company}
                                onChange={(e) => setData('company', e.target.value)}
                                className="w-full border-gray-300 rounded-md shadow-sm focus:ring-slfa-maroon focus:border-slfa-maroon"
                            />
                            {errors.company && <p className="text-red-500 text-xs mt-1">{errors.company}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Designation / Position</label>
                            <input
                                type="text"
                                value={data.designation}
                                onChange={(e) => setData('designation', e.target.value)}
                                className="w-full border-gray-300 rounded-md shadow-sm focus:ring-slfa-maroon focus:border-slfa-maroon"
                            />
                            {errors.designation && <p className="text-red-500 text-xs mt-1">{errors.designation}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Committee Type</label>
                            <select
                                value={data.type}
                                onChange={(e) => setData('type', e.target.value)}
                                className="w-full border-gray-300 rounded-md shadow-sm focus:ring-slfa-maroon focus:border-slfa-maroon"
                            >
                                <option value="Executive">Executive</option>
                                <option value="Leadership">Leadership</option>
                            </select>
                            {errors.type && <p className="text-red-500 text-xs mt-1">{errors.type}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Display Order (0 is first)</label>
                            <input
                                type="number"
                                value={data.display_order}
                                onChange={(e) => setData('display_order', e.target.value)}
                                className="w-full border-gray-300 rounded-md shadow-sm focus:ring-slfa-maroon focus:border-slfa-maroon"
                            />
                            {errors.display_order && <p className="text-red-500 text-xs mt-1">{errors.display_order}</p>}
                        </div>

                        <div className="md:col-span-2">
                            <label className="flex items-center space-x-3 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={data.show_in_website}
                                    onChange={(e) => setData('show_in_website', e.target.checked)}
                                    className="rounded border-gray-300 text-slfa-maroon focus:ring-slfa-maroon w-5 h-5"
                                />
                                <span className="text-sm font-medium text-gray-700">Show in Public Website</span>
                            </label>
                        </div>
                        
                        <div className="md:col-span-2">
                            <label className="block text-sm font-medium text-gray-700 mb-2">Photo</label>
                            <div className="flex items-center space-x-4">
                                {preview ? (
                                    <img src={preview} alt="Preview" className="w-16 h-16 object-cover rounded-full shadow-sm" />
                                ) : (
                                    <div className="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center text-gray-400 shadow-sm text-xs">No img</div>
                                )}
                                <input
                                    type="file"
                                    accept="image/*"
                                    onChange={handlePhotoChange}
                                    className="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-slfa-maroon/10 file:text-slfa-maroon hover:file:bg-slfa-maroon/20"
                                />
                            </div>
                            {errors.photo && <p className="text-red-500 text-xs mt-1">{errors.photo}</p>}
                        </div>

                    </div>

                    <div className="pt-6 flex justify-end">
                        <button
                            type="submit"
                            disabled={processing}
                            className="bg-slfa-maroon text-white px-6 py-2 rounded-md font-semibold hover:bg-slfa-navy transition-colors flex items-center"
                        >
                            <Save className="w-4 h-4 mr-2" />
                            {processing ? 'Saving...' : 'Save Member'}
                        </button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
