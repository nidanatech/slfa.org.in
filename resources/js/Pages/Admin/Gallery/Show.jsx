import { Head, Link, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Show({ auth, album }) {
    const details = useForm({ title: album.title, description: album.description || '', date: album.date || '', event_id: album.event_id || '' });
    const upload = useForm({ file: null });
    const remove = useForm({});

    return (
        <AdminLayout user={auth.user}>
            <Head title={`Manage ${album.title}`} />
            <Link href={route('admin.albums.index')} className="text-sm text-slfa-maroon">Back to gallery</Link>
            <h1 className="my-6 text-2xl font-bold">{album.title}</h1>
            <form className="mb-8 max-w-xl space-y-4" onSubmit={event => {
                event.preventDefault();
                details.put(route('admin.albums.update', album.id));
            }}>
                <label className="flex-1">Album title
                    <input className="mt-1 w-full rounded-md border-gray-300" value={details.data.title}
                        onChange={event => details.setData('title', event.target.value)} required />
                    {details.errors.title && <span className="text-red-600">{details.errors.title}</span>}
                </label>
                <label className="block">Description
                    <textarea className="mt-1 w-full rounded-md border-gray-300" value={details.data.description}
                        onChange={event => details.setData('description', event.target.value)} />
                </label>
                <label className="block">Event date
                    <input type="date" className="mt-1 block rounded-md border-gray-300" value={details.data.date}
                        onChange={event => details.setData('date', event.target.value)} />
                </label>
                <button disabled={details.processing} className="rounded-md bg-slfa-maroon px-4 py-2 text-white">Save</button>
            </form>
            <form className="mb-8 flex flex-wrap items-end gap-3" onSubmit={event => {
                event.preventDefault();
                upload.post(route('admin.albums.upload', album.id), { onSuccess: () => upload.reset() });
            }}>
                <label>Upload photo or video
                    <input type="file" accept="image/jpeg,image/png,image/gif,video/mp4" className="block mt-1"
                        onChange={event => upload.setData('file', event.target.files[0])} required />
                    {upload.errors.file && <span className="text-red-600">{upload.errors.file}</span>}
                </label>
                <button disabled={upload.processing} className="rounded-md bg-slfa-maroon px-4 py-2 text-white">Upload</button>
            </form>
            <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                {album.media.map(item => (
                    <div key={item.id} className="rounded-lg bg-white p-3 shadow">
                        {item.type === 'video' ? <video controls src={`/storage/${item.path}`} className="aspect-square w-full object-cover" /> :
                            <img src={`/storage/${item.path}`} alt={album.title} loading="lazy" className="aspect-square w-full object-cover" />}
                        <button className="mt-3 text-sm text-red-700" disabled={remove.processing}
                            onClick={() => { if (confirm('Delete this media item?')) remove.delete(route('admin.media.destroy', item.id)); }}>
                            Delete
                        </button>
                    </div>
                ))}
            </div>
        </AdminLayout>
    );
}
