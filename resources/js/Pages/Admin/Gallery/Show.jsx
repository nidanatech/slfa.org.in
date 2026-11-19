import { Head, Link, router, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { useState } from 'react';

function MediaEditor({ item, album, programmeItems, remove }) {
    const [programmeItemId, setProgrammeItemId] = useState(item.programme_item_id || '');
    const [caption, setCaption] = useState(item.caption || '');
    return <div className="bg-white p-3 shadow">
        {item.type === 'video' ? <video controls src={`/storage/${item.path}`} className="aspect-square w-full object-cover" /> : <img src={`/storage/${item.path}`} alt={caption || album.title} loading="lazy" className="aspect-square w-full object-cover" />}
        <label className="mt-3 block text-xs font-bold">Programme section<select className="mt-1 w-full rounded-md border-gray-300 text-sm" value={programmeItemId} onChange={event => setProgrammeItemId(event.target.value)}><option value="">Other moments</option>{programmeItems.map(item => <option key={item.id} value={item.id}>{item.title}</option>)}</select></label>
        <label className="mt-3 block text-xs font-bold">Caption<input className="mt-1 w-full rounded-md border-gray-300 text-sm" value={caption} onChange={event => setCaption(event.target.value)} /></label>
        <div className="mt-3 flex gap-3"><button type="button" className="text-sm font-bold text-slfa-maroon" onClick={() => router.patch(route('admin.media.update', item.id), { programme_item_id: programmeItemId || null, caption })}>Save details</button><button type="button" className="text-sm text-red-700" disabled={remove.processing} onClick={() => { if (confirm('Delete this media item?')) remove.delete(route('admin.media.destroy', item.id)); }}>Delete</button></div>
    </div>;
}

export default function Show({ auth, album, events = [] }) {
    const details = useForm({ title: album.title, description: album.description || '', date: album.date || '', event_id: album.event_id || '' });
    const upload = useForm({ file: null, programme_item_id: '', caption: '' });
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
                <label className="block">Linked event
                    <select className="mt-1 block w-full rounded-md border-gray-300" value={details.data.event_id} onChange={event => details.setData('event_id', event.target.value)} required><option value="">Choose an event</option>{events.map(event => <option key={event.id} value={event.id}>{event.title}</option>)}</select>
                </label>
                <button disabled={details.processing} className="rounded-md bg-slfa-maroon px-4 py-2 text-white">Save</button>
            </form>
            <form className="mb-8 flex flex-wrap items-end gap-3" onSubmit={event => {
                event.preventDefault();
                upload.post(route('admin.albums.upload', album.id), { onSuccess: () => upload.reset() });
            }}>
                <label>Upload photo or video
                    <input type="file" accept="image/jpeg,image/png,image/gif,image/webp,image/avif,video/mp4" className="block mt-1"
                        onChange={event => upload.setData('file', event.target.files[0])} required />
                    {upload.errors.file && <span className="text-red-600">{upload.errors.file}</span>}
                </label>
                <label>Programme section<select className="mt-1 block rounded-md border-gray-300" value={upload.data.programme_item_id} onChange={event => upload.setData('programme_item_id', event.target.value)}><option value="">Other moments</option>{(album.event?.programme_items || []).map(item => <option key={item.id} value={item.id}>{item.title}</option>)}</select></label>
                <label>Caption<input className="mt-1 block rounded-md border-gray-300" value={upload.data.caption} onChange={event => upload.setData('caption', event.target.value)} /></label>
                <button disabled={upload.processing} className="rounded-md bg-slfa-maroon px-4 py-2 text-white">Upload</button>
            </form>
            <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                {album.media.map(item => <MediaEditor key={item.id} item={item} album={album} programmeItems={album.event?.programme_items || []} remove={remove} />)}
            </div>
        </AdminLayout>
    );
}
