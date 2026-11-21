import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Plus, Edit2, Trash2, Eye } from 'lucide-react';
import dayjs from 'dayjs';
import toast, { Toaster } from 'react-hot-toast';

export default function Index({ auth, posts }) {
    const { delete: destroy } = useForm();

    const handleDelete = (id) => {
        if (confirm("Are you sure you want to delete this news post?")) {
            destroy(route('admin.news.destroy', id), {
                onSuccess: () => toast.success('News post deleted.'),
            });
        }
    };

    return (
        <AdminLayout user={auth.user}>
            <Head title="Manage News & Updates" />
            <Toaster position="top-right" />

            <div className="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-normal text-gray-900">News & Updates</h1>
                    <p className="text-gray-500 text-sm mt-1">Manage announcements and articles for the community.</p>
                </div>

                <Link
                    href={route('admin.news.create')}
                    className="flex items-center justify-center gap-2 px-4 py-2 bg-[#1B7A4A] text-white rounded-lg text-sm font-normal hover:bg-orange-600 transition-colors"
                >
                    <Plus className="w-4 h-4" />
                    New Post
                </Link>
            </div>

            <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-normal text-gray-500 uppercase tracking-wider">Title</th>
                                <th className="px-6 py-3 text-left text-xs font-normal text-gray-500 uppercase tracking-wider">Category</th>
                                <th className="px-6 py-3 text-left text-xs font-normal text-gray-500 uppercase tracking-wider">Status</th>
                                <th className="px-6 py-3 text-left text-xs font-normal text-gray-500 uppercase tracking-wider">Published Date</th>
                                <th className="px-6 py-3 text-right text-xs font-normal text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {posts.data.length > 0 ? posts.data.map(post => (
                                <tr key={post.id} className="hover:bg-gray-50 transition-colors">
                                    <td className="px-6 py-4">
                                        <div className="text-sm font-normal text-gray-900">{post.title}</div>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <div className="text-sm text-gray-900">{post.category || 'General'}</div>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        {post.published_at && dayjs(post.published_at).isBefore(dayjs()) ? (
                                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Published
                                            </span>
                                        ) : (
                                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                Draft
                                            </span>
                                        )}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {post.published_at ? dayjs(post.published_at).format('MMM D, YYYY') : '-'}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div className="flex justify-end gap-2">
                                            <Link
                                                href={route('admin.news.edit', post.id)}
                                                className="text-gray-400 hover:text-[#1B7A4A] p-2 rounded-full hover:bg-gray-100 transition-colors"
                                                title="Edit"
                                            >
                                                <Edit2 className="w-5 h-5" />
                                            </Link>
                                            <button
                                                onClick={() => handleDelete(post.id)}
                                                className="text-gray-400 hover:text-red-500 p-2 rounded-full hover:bg-gray-100 transition-colors"
                                                title="Delete"
                                            >
                                                <Trash2 className="w-5 h-5" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            )) : (
                                <tr>
                                    <td colSpan="5" className="px-6 py-8 text-center text-gray-500">
                                        No news posts found.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

        </AdminLayout>
    );
}
