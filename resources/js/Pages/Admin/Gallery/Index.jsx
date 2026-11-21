import { Head, Link, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Image as ImageIcon, Plus, Trash2, FolderOpen } from 'lucide-react';
import toast, { Toaster } from 'react-hot-toast';

export default function Index({ auth, albums }) {
    const { delete: destroy } = useForm();

    const handleDelete = (id) => {
        if (confirm("Are you sure you want to delete this album and all its images?")) {
            destroy(route('admin.albums.destroy', id), {
                onSuccess: () => toast.success('Album deleted successfully.'),
                onError: (errs) => toast.error(Object.values(errs)[0] || 'Error deleting album.')
            });
        }
    };

    return (
        <AdminLayout user={auth.user}>
            <Head title="Manage Event Photos" />
            <Toaster position="top-right" />

            <div className="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-normal text-gray-900 flex items-center">
                        <ImageIcon className="w-6 h-6 mr-2 text-[#1B7A4A]" />
                        Event Photo Albums
                    </h1>
                    <p className="text-sm text-gray-500 mt-1">Manage photos within each event.</p>
                </div>
                <div>
                    <Link href={route('admin.albums.create')} className="bg-[#1B7A4A] text-white px-4 py-2 rounded-md font-normal text-sm hover:bg-[#1B7A4A] transition flex items-center">
                        <Plus className="w-4 h-4 mr-1" /> Create Album
                    </Link>
                </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {albums.data.length === 0 ? (
                    <div className="col-span-full py-12 text-center text-gray-500 bg-white rounded-xl shadow-sm border border-gray-100">
                        No albums found.
                    </div>
                ) : (
                    albums.data.map(album => (
                        <div key={album.id} className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden group">
                            <div className="h-48 bg-gray-100 relative">
                                {album.cover_image_path ? (
                                    <img src={`/storage/${album.cover_image_path}`} alt={album.title} className="w-full h-full object-cover" />
                                ) : (
                                    <div className="flex items-center justify-center w-full h-full text-gray-400">
                                        <FolderOpen className="w-12 h-12" />
                                    </div>
                                )}
                                <div className="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-4">
                                    <Link href={route('admin.albums.show', album.id)} className="bg-white text-[#1f2937] px-4 py-2 rounded font-normal text-sm hover:bg-gray-100">
                                        Manage
                                    </Link>
                                </div>
                            </div>
                            <div className="p-4 flex justify-between items-center">
                                <div>
                                    <h3 className="font-normal text-gray-900">{album.title}</h3>
                                    <p className="text-xs text-gray-500">{album.media_count} items</p>
                                </div>
                                <button
                                    onClick={() => handleDelete(album.id)}
                                    className="text-red-500 hover:text-red-700 p-2"
                                >
                                    <Trash2 className="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    ))
                )}
            </div>
        </AdminLayout>
    );
}
