import React from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { ArrowLeft, Save } from 'lucide-react';
import dayjs from 'dayjs';
import { coverImageUrl } from '@/lib/coverImageUrl';

export default function Edit({ auth, post }) {
    const { data, setData, post: submitPost, processing, errors } = useForm({
        title: post.title || '',
        category: post.category || '',
        body: post.body || '',
        published_at: post.published_at ? dayjs(post.published_at).format('YYYY-MM-DD') : '',
        cover_image: null,
        meta_title: post.meta_title || '',
        meta_description: post.meta_description || '',
        og_image: null,
        gallery_images: [],
        _method: 'put',
    });

    const submit = (e) => {
        e.preventDefault();
        submitPost(route('admin.news.update', post.id), { forceFormData: true });
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
                        <label htmlFor="news-cover" className="block text-sm font-medium text-gray-700 mb-1">Replace cover image (optional)</label>
                        {post.cover_image_path && <img src={coverImageUrl(post.cover_image_path)} alt="Current cover" className="mb-3 h-32 w-auto object-cover" />}
                        <input id="news-cover" name="cover_image" type="file" accept="image/jpeg,image/png,image/webp,image/avif" onChange={e => setData('cover_image', e.target.files[0] || null)} className="w-full border border-gray-300 rounded-lg p-2.5 text-sm" />
                        {errors.cover_image && <p className="text-sm text-red-600 mt-1">{errors.cover_image}</p>}
                    </div>

                    <div>
                        <label htmlFor="news-photos" className="block text-sm font-medium text-gray-700 mb-1">Add gallery photos</label>
                        <input id="news-photos" name="gallery_images" type="file" multiple accept="image/jpeg,image/png,image/webp,image/avif" onChange={e => setData('gallery_images', Array.from(e.target.files))} className="w-full border border-gray-300 rounded-lg p-2.5 text-sm" />
                        <p className="mt-1 text-xs text-gray-500">Up to 12 images per upload, 5 MB each.</p>
                        {errors.gallery_images && <p className="text-sm text-red-600 mt-1">{errors.gallery_images}</p>}
                        {post.photos?.length > 0 && <div className="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">{post.photos.map(photo => <div key={photo.id}><img src={coverImageUrl(photo.path)} alt={photo.caption || post.title} className="aspect-square w-full object-cover" /><button type="button" onClick={() => { if (window.confirm('Remove this photo?')) router.delete(route('admin.news.photos.destroy', [post.id, photo.id]), { preserveScroll: true }); }} className="mt-1 text-xs font-bold text-red-700 hover:underline">Remove photo</button></div>)}</div>}
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

                    <div className="space-y-4 border-t pt-5"><h2 className="font-bold">Search and sharing</h2><p className="text-xs text-gray-600">Leave blank to use the story title, excerpt, and cover image.</p><label className="block text-sm">Meta title<input className="mt-1 w-full rounded-md border-gray-300" maxLength={255} value={data.meta_title} onChange={e => setData('meta_title', e.target.value)} /></label><label className="block text-sm">Meta description<textarea className="mt-1 w-full rounded-md border-gray-300" maxLength={320} rows={3} value={data.meta_description} onChange={e => setData('meta_description', e.target.value)} /></label><label className="block text-sm">Social share image<input type="file" accept="image/jpeg,image/png,image/webp,image/avif" className="mt-1 block w-full" onChange={e => setData('og_image', e.target.files[0] || null)} />{errors.og_image && <span className="text-red-700">{errors.og_image}</span>}</label></div>

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
