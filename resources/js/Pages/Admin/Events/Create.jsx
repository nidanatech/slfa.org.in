import { Head, Link, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { ArrowLeft, Plus, Save, Trash2 } from 'lucide-react';
import toast, { Toaster } from 'react-hot-toast';
import { coverImageUrl } from '@/lib/coverImageUrl';

const fieldClass = 'mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-slfa-maroon focus:ring-slfa-maroon';
const localDateTime = (value, zone = 'Asia/Kolkata') => value ? new Intl.DateTimeFormat('sv-SE', { timeZone: zone, year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false }).format(new Date(value)).replace(' ', 'T') : '';

export default function Create({ auth, event }) {
    const zone = event?.timezone || 'Asia/Kolkata';
    const { data, setData, post, processing, errors } = useForm({
        ...(event ? { _method: 'put' } : {}),
        title: event?.title || '',
        description: event?.description || '',
        event_type: event?.event_type || 'Other',
        location: event?.location || '',
        venue_name: event?.venue_name || '',
        venue_address: event?.venue_address || '',
        timezone: zone,
        start_at: localDateTime(event?.start_at, zone),
        end_at: localDateTime(event?.end_at, zone),
        dress_code: event?.dress_code || '',
        adult_price: event?.adult_price ?? '',
        child_price: event?.child_price ?? '',
        child_age_range: event?.child_age_range || '',
        contact_details: event?.contact_details || '',
        rsvp_enabled: event?.rsvp_enabled ?? false,
        admission_enabled: event?.admission_enabled ?? false,
        admission_closes_at: localDateTime(event?.admission_closes_at, zone),
        meta_title: event?.meta_title || '',
        meta_description: event?.meta_description || '',
        og_image: null,
        cover_image: null,
        programme: (event?.programme_items || []).map(item => ({ id: item.id, time: item.time, title: item.title, details: item.details || '' })),
        sponsors: (event?.sponsors || []).map(item => ({ name: item.name, website_url: item.website_url || '', tier: item.tier || '' })),
    });

    const updateRow = (field, index, key, value) => setData(field, data[field].map((row, rowIndex) => rowIndex === index ? { ...row, [key]: value } : row));
    const removeRow = (field, index) => setData(field, data[field].filter((_, rowIndex) => rowIndex !== index));

    const submit = (e) => {
        e.preventDefault();
        post(event ? route('admin.events.update', event.id) : route('admin.events.store'), {
            forceFormData: true,
            onError: (issues) => toast.error(Object.values(issues)[0] || 'Please check the event details.'),
        });
    };

    return <AdminLayout user={auth.user}>
        <Head title={event ? `Edit ${event.title}` : 'Create Event'} />
        <Toaster position="top-right" />
        <div className="mb-6"><Link href={route('admin.events.index')} className="inline-flex items-center gap-1 text-sm text-gray-600 hover:text-slfa-maroon"><ArrowLeft size={16} aria-hidden="true" /> Back to events</Link><h1 className="mt-3 text-2xl font-bold text-gray-900">{event ? 'Edit event' : 'Create event'}</h1></div>

        <form onSubmit={submit} className="max-w-5xl space-y-10 rounded-xl border border-gray-100 bg-white p-6 shadow-sm md:p-8">
            <section className="space-y-5"><h2 className="border-b pb-3 text-lg font-bold">Event details</h2>
                <label className="block text-sm font-medium">Title<input name="title" className={fieldClass} value={data.title} onChange={e => setData('title', e.target.value)} required />{errors.title && <span className="text-red-700">{errors.title}</span>}</label>
                <label className="block text-sm font-medium">Description<textarea name="description" rows="4" className={fieldClass} value={data.description} onChange={e => setData('description', e.target.value)} /></label>
                <div className="grid gap-5 md:grid-cols-2">
                    <label className="block text-sm font-medium">Type<select name="event_type" className={fieldClass} value={data.event_type} onChange={e => setData('event_type', e.target.value)}>{['AGM', 'Get-Together', 'Medical Camp', 'Eye Camp', 'Health Checkup', 'Other'].map(type => <option key={type}>{type}</option>)}</select></label>
                    <label className="block text-sm font-medium">Time zone<input name="timezone" className={fieldClass} value={data.timezone} onChange={e => setData('timezone', e.target.value)} required />{errors.timezone && <span className="text-red-700">{errors.timezone}</span>}</label>
                    <label className="block text-sm font-medium">Event starts<input name="start_at" type="datetime-local" className={fieldClass} value={data.start_at} onChange={e => setData('start_at', e.target.value)} required />{errors.start_at && <span className="text-red-700">{errors.start_at}</span>}</label>
                    <label className="block text-sm font-medium">Event ends (optional)<input name="end_at" type="datetime-local" className={fieldClass} value={data.end_at} onChange={e => setData('end_at', e.target.value)} />{errors.end_at && <span className="text-red-700">{errors.end_at}</span>}</label>
                </div>
                {event?.cover_image_path && <img src={coverImageUrl(event.cover_image_path)} alt="Current event cover" className="h-40 w-auto object-contain" />}
                <label className="block text-sm font-medium">Event cover or poster<input name="cover_image" type="file" accept="image/jpeg,image/png,image/webp,image/avif" className={fieldClass} onChange={e => setData('cover_image', e.target.files[0] || null)} />{errors.cover_image && <span className="text-red-700">{errors.cover_image}</span>}</label>
            </section>

            <section className="space-y-5"><h2 className="border-b pb-3 text-lg font-bold">Venue, tickets, and contact</h2>
                <div className="grid gap-5 md:grid-cols-2">
                    <label className="block text-sm font-medium">Venue name<input name="venue_name" className={fieldClass} value={data.venue_name} onChange={e => setData('venue_name', e.target.value)} /></label>
                    <label className="block text-sm font-medium">Location summary<input name="location" className={fieldClass} value={data.location} onChange={e => setData('location', e.target.value)} /></label>
                    <label className="block text-sm font-medium md:col-span-2">Venue address<textarea name="venue_address" rows="2" className={fieldClass} value={data.venue_address} onChange={e => setData('venue_address', e.target.value)} /></label>
                    <label className="block text-sm font-medium">Dress code<input name="dress_code" className={fieldClass} value={data.dress_code} onChange={e => setData('dress_code', e.target.value)} /></label>
                    <label className="block text-sm font-medium">Contact details<input name="contact_details" className={fieldClass} value={data.contact_details} onChange={e => setData('contact_details', e.target.value)} /></label>
                    <label className="block text-sm font-medium">Adult price (INR)<input name="adult_price" type="number" min="0" className={fieldClass} value={data.adult_price} onChange={e => setData('adult_price', e.target.value)} /></label>
                    <label className="block text-sm font-medium">Child price (INR)<input name="child_price" type="number" min="0" className={fieldClass} value={data.child_price} onChange={e => setData('child_price', e.target.value)} /></label>
                    <label className="block text-sm font-medium">Child age range<input name="child_age_range" className={fieldClass} value={data.child_age_range} onChange={e => setData('child_age_range', e.target.value)} /></label>
                </div>
                <label className="flex items-center gap-2 text-sm font-medium"><input name="rsvp_enabled" type="checkbox" checked={Boolean(data.rsvp_enabled)} onChange={e => setData('rsvp_enabled', e.target.checked)} /> Allow member RSVPs</label>
                <div className="border-t pt-5"><label className="flex items-center gap-2 text-sm font-medium"><input name="admission_enabled" type="checkbox" checked={Boolean(data.admission_enabled)} onChange={e => setData('admission_enabled', e.target.checked)} /> Accept online admission requests</label><p className="mt-2 text-xs text-gray-600">Requests collect contact details and party size. Payment is arranged separately.</p></div>
                <label className="block text-sm font-medium">Admission closes (event time zone)<input name="admission_closes_at" type="datetime-local" className={fieldClass} value={data.admission_closes_at} onChange={e => setData('admission_closes_at', e.target.value)} />{errors.admission_closes_at && <span className="text-red-700">{errors.admission_closes_at}</span>}</label>
            </section>

            <section className="space-y-5"><h2 className="border-b pb-3 text-lg font-bold">Search and sharing</h2><p className="text-xs text-gray-600">Leave fields blank to use the event title, summary, and cover image.</p><label className="block text-sm font-medium">Meta title<input className={fieldClass} maxLength={255} value={data.meta_title} onChange={e => setData('meta_title', e.target.value)} /></label><label className="block text-sm font-medium">Meta description<textarea className={fieldClass} maxLength={320} rows={3} value={data.meta_description} onChange={e => setData('meta_description', e.target.value)} /></label><label className="block text-sm font-medium">Social share image<input type="file" accept="image/jpeg,image/png,image/webp,image/avif" className={fieldClass} onChange={e => setData('og_image', e.target.files[0] || null)} />{errors.og_image && <span className="text-red-700">{errors.og_image}</span>}</label></section>

            <section><div className="flex flex-wrap items-center justify-between gap-3 border-b pb-3"><h2 className="text-lg font-bold">Programme</h2><button type="button" onClick={() => setData('programme', [...data.programme, { time: '', title: '', details: '' }])} className="inline-flex items-center gap-1 text-sm font-bold text-slfa-maroon"><Plus size={16} /> Add item</button></div><p className="mt-2 text-xs text-gray-500">Times use the event’s time zone. Entries appear in this order.</p>
                <div className="mt-4 space-y-4">{data.programme.map((item, index) => <div key={index} className="grid gap-3 border-b pb-4 md:grid-cols-[8rem_1fr_1fr_auto]"><label className="text-sm">Time<input type="time" className={fieldClass} value={item.time} onChange={e => updateRow('programme', index, 'time', e.target.value)} required /></label><label className="text-sm">Item<input className={fieldClass} value={item.title} onChange={e => updateRow('programme', index, 'title', e.target.value)} required /></label><label className="text-sm">Speaker or details<input className={fieldClass} value={item.details} onChange={e => updateRow('programme', index, 'details', e.target.value)} /></label><button type="button" aria-label={`Remove programme item ${index + 1}`} onClick={() => removeRow('programme', index)} className="self-end p-2 text-red-700"><Trash2 size={18} /></button></div>)}</div>
            </section>

            <section><div className="flex flex-wrap items-center justify-between gap-3 border-b pb-3"><h2 className="text-lg font-bold">Sponsors</h2><button type="button" onClick={() => setData('sponsors', [...data.sponsors, { name: '', website_url: '', tier: '' }])} className="inline-flex items-center gap-1 text-sm font-bold text-slfa-maroon"><Plus size={16} /> Add sponsor</button></div><p className="mt-2 text-xs text-gray-500">Add only verified official HTTPS websites. Sponsor links are marked as sponsored for search engines.</p>
                <div className="mt-4 space-y-4">{data.sponsors.map((item, index) => <div key={index} className="grid gap-3 border-b pb-4 md:grid-cols-[1fr_1.4fr_0.7fr_auto]"><label className="text-sm">Name<input className={fieldClass} value={item.name} onChange={e => updateRow('sponsors', index, 'name', e.target.value)} required /></label><label className="text-sm">Official website<input type="url" placeholder="https://example.org" className={fieldClass} value={item.website_url} onChange={e => updateRow('sponsors', index, 'website_url', e.target.value)} /></label><label className="text-sm">Tier (optional)<input className={fieldClass} value={item.tier} onChange={e => updateRow('sponsors', index, 'tier', e.target.value)} /></label><button type="button" aria-label={`Remove sponsor ${index + 1}`} onClick={() => removeRow('sponsors', index)} className="self-end p-2 text-red-700"><Trash2 size={18} /></button></div>)}</div>
            </section>

            <div className="flex justify-end border-t pt-6"><button type="submit" disabled={processing} className="inline-flex items-center gap-2 rounded-md bg-slfa-maroon px-6 py-3 font-bold text-white disabled:opacity-50"><Save size={17} aria-hidden="true" />{processing ? 'Saving…' : 'Save event'}</button></div>
        </form>
    </AdminLayout>;
}
