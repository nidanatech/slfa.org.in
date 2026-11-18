import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { ArrowLeft, Save } from 'lucide-react';
import dayjs from 'dayjs';

export default function Edit({ auth, post }) {
    const { data, setData, put, processing, errors } = useForm({
        title: post.title || '',
        category: post.category || '',
        body: post.body || '',
        published_at: post.published_at ? dayjs(post.published_at).format('YYYY-MM-DD') : '',
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('admin.news.update', post.id));
    };

    return (
        <AdminLayout user={auth.user}>
            <Head title="Edit News Post" />

            <div className="mb-6">
                <Link href={route('admin.news.index')} className="text-sm font-medium text-gray-500 hover:text-slfa-primary flex items-center gap-1 mb-4">
                    <ArrowLeft className="w-4 h-4" /> Back to News
                </Link>
                <h1 className="text-2xl font-bold text-gray-900">Edit News Post</h1>
            </div>

            <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden max-w-4xl">
                <form onSubmit={submit} className="p-6 space-y-6">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Title</label>
                        <input 
                            type="text" 
                            value={data.title}
                            onChange={e => setData('title', e.target.value)}
                            className="w-full border border-gray-300 rounded-lg shadow-sm focus:ring-slfa-primary focus:border-slfa-primary text-sm p-2.5"
                            placeholder="Enter post title"
                        />
                        {errors.title && <p className="text-sm text-red-600 mt-1">{errors.title}</p>}
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Category</label>
                            <input 
                                type="text" 
                                value={data.category}
                                onChange={e => setData('category', e.target.value)}
                                className="w-full border border-gray-300 rounded-lg shadow-sm focus:ring-slfa-primary focus:border-slfa-primary text-sm p-2.5"
                                placeholder="e.g. AGM, Get-Together"
                            />
                            {errors.category && <p className="text-sm text-red-600 mt-1">{errors.category}</p>}
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Publish Date (Optional)</label>
                            <input 
                                type="date" 
                                value={data.published_at}
                                onChange={e => setData('published_at', e.target.value)}
                                className="w-full border border-gray-300 rounded-lg shadow-sm focus:ring-slfa-primary focus:border-slfa-primary text-sm p-2.5"
                            />
                            {errors.published_at && <p className="text-sm text-red-600 mt-1">{errors.published_at}</p>}
                        </div>
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Content</label>
                        <textarea 
                            rows="10"
                            value={data.body}
                            onChange={e => setData('body', e.target.value)}
                            className="w-full border border-gray-300 rounded-lg shadow-sm focus:ring-slfa-primary focus:border-slfa-primary text-sm p-2.5"
                            placeholder="Write your news post here..."
                        ></textarea>
                        {errors.body && <p className="text-sm text-red-600 mt-1">{errors.body}</p>}
                    </div>

                    <div className="flex justify-end pt-4 border-t border-gray-100">
                        <button 
                            type="submit" 
                            disabled={processing}
                            className="flex items-center gap-2 px-6 py-2.5 bg-slfa-navy text-white rounded-lg text-sm font-bold hover:bg-slfa-navy/90 transition-colors disabled:opacity-50"
                        >
                            <Save className="w-4 h-4" /> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
