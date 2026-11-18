import { Head, Link, useForm } from '@inertiajs/react';
import SLFAWebsiteLayout from '@/Layouts/SLFAWebsiteLayout';
import { ArrowLeft, MapPin, Briefcase, Mail, Phone, Calendar, Send } from 'lucide-react';
import InputError from '@/Components/InputError';
import { useState } from 'react';

export default function MemberProfile({ auth, member, flash }) {
    
    const [showMessageForm, setShowMessageForm] = useState(false);
    
    const { data, setData, post, processing, errors, reset } = useForm({
        subject: '',
        message: '',
    });

    const submitMessage = (e) => {
        e.preventDefault();
        post(route('directory.message', member.id), {
            onSuccess: () => {
                reset();
                setShowMessageForm(false);
            },
        });
    };

    return (
        <SLFAWebsiteLayout user={auth.user}>
            <Head title={`${member.full_name} - Directory`} />

            <div className="bg-slfa-navy text-white pt-12 pb-32">
                <div className="slfa-container animate-fade-up">
                    <Link href={route('directory')} className="text-gray-300 hover:text-white transition flex items-center text-sm font-semibold mb-6">
                        <ArrowLeft className="w-4 h-4 mr-1" /> Back to Directory
                    </Link>
                </div>
            </div>

            <div className="slfa-section bg-slfa-cream min-h-screen pt-0 -mt-24">
                <div className="slfa-container">
                    
                    <div className="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden animate-fade-up">
                        <div className="p-8 md:p-12">
                            <div className="flex flex-col md:flex-row gap-8 items-start">
                                
                                {/* Avatar */}
                                <div className="w-32 h-32 md:w-48 md:h-48 rounded-2xl bg-slfa-peach flex items-center justify-center text-slfa-coral font-bold text-6xl shrink-0 overflow-hidden shadow-inner border-4 border-white -mt-16 md:-mt-24 relative z-10">
                                    {member.photo_path ? (
                                        <img src={`/storage/${member.photo_path}`} alt="" className="w-full h-full object-cover" />
                                    ) : (
                                        member.full_name.charAt(0)
                                    )}
                                </div>

                                {/* Main Details */}
                                <div className="flex-1 pt-2 md:pt-0">
                                    <div className="flex flex-col md:flex-row md:justify-between md:items-start gap-4 mb-4">
                                        <div>
                                            <h1 className="text-3xl font-bold text-slfa-navy">{member.full_name}</h1>
                                            <div className="flex flex-wrap gap-2 mt-2">
                                                <span className={`text-xs font-semibold px-3 py-1 rounded-full ${member.membership_type === 'business' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'}`}>
                                                    {member.membership_type === 'business' ? 'Business Member' : 'Individual Member'}
                                                </span>
                                                {member.member_id_string && (
                                                    <span className="text-xs font-semibold px-3 py-1 rounded-full bg-slfa-peach text-slfa-coral">
                                                        ID: {member.member_id_string}
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                        
                                        {auth.user.id !== member.user_id && (
                                            <button 
                                                onClick={() => setShowMessageForm(!showMessageForm)}
                                                className="bg-slfa-coral text-white px-5 py-2 rounded-md font-semibold hover:bg-slfa-coral-dark transition flex items-center justify-center"
                                            >
                                                <Mail className="w-4 h-4 mr-2" /> Message
                                            </button>
                                        )}
                                    </div>

                                    {/* Info Grid */}
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-8 mt-8 border-t border-gray-100 pt-8">
                                        
                                        {member.occupation && (
                                            <div className="flex items-start">
                                                <Briefcase className="w-5 h-5 mr-3 text-slfa-coral shrink-0 mt-0.5" />
                                                <div>
                                                    <p className="text-xs text-gray-500 font-semibold uppercase tracking-wider">Profession</p>
                                                    <p className="font-medium text-slfa-navy">{member.designation ? `${member.designation}, ` : ''}{member.company_name || member.occupation}</p>
                                                </div>
                                            </div>
                                        )}

                                        {member.city && (
                                            <div className="flex items-start">
                                                <MapPin className="w-5 h-5 mr-3 text-slfa-coral shrink-0 mt-0.5" />
                                                <div>
                                                    <p className="text-xs text-gray-500 font-semibold uppercase tracking-wider">Location</p>
                                                    <p className="font-medium text-slfa-navy">{member.city}</p>
                                                </div>
                                            </div>
                                        )}

                                        {member.sri_lanka_connection && (
                                            <div className="flex items-start md:col-span-2">
                                                <svg className="w-5 h-5 mr-3 text-slfa-coral shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                <div>
                                                    <p className="text-xs text-gray-500 font-semibold uppercase tracking-wider">Connection to Sri Lanka</p>
                                                    <p className="font-medium text-slfa-navy">{member.sri_lanka_connection}</p>
                                                </div>
                                            </div>
                                        )}
                                        
                                        <div className="flex items-start">
                                            <Calendar className="w-5 h-5 mr-3 text-slfa-coral shrink-0 mt-0.5" />
                                            <div>
                                                <p className="text-xs text-gray-500 font-semibold uppercase tracking-wider">Member Since</p>
                                                <p className="font-medium text-slfa-navy">{member.joined_at ? new Date(member.joined_at).getFullYear() : 'Unknown'}</p>
                                            </div>
                                        </div>

                                        {member.show_contact_info && (
                                            <>
                                                {member.email && (
                                                    <div className="flex items-start">
                                                        <Mail className="w-5 h-5 mr-3 text-slfa-coral shrink-0 mt-0.5" />
                                                        <div>
                                                            <p className="text-xs text-gray-500 font-semibold uppercase tracking-wider">Email</p>
                                                            <a href={`mailto:${member.email}`} className="font-medium text-slfa-coral hover:underline">{member.email}</a>
                                                        </div>
                                                    </div>
                                                )}
                                                {member.phone && (
                                                    <div className="flex items-start">
                                                        <Phone className="w-5 h-5 mr-3 text-slfa-coral shrink-0 mt-0.5" />
                                                        <div>
                                                            <p className="text-xs text-gray-500 font-semibold uppercase tracking-wider">Phone</p>
                                                            <a href={`tel:${member.phone}`} className="font-medium text-slfa-coral hover:underline">{member.phone}</a>
                                                        </div>
                                                    </div>
                                                )}
                                            </>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Message Form */}
                    {showMessageForm && (
                        <div className="mt-8 bg-white p-8 rounded-xl shadow-md border border-gray-100 animate-fade-up">
                            <h3 className="text-xl font-bold text-slfa-navy mb-4 border-b pb-2">Send a Message to {member.full_name}</h3>
                            
                            {flash?.success && (
                                <div className="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-md text-sm">
                                    {flash.success}
                                </div>
                            )}

                            <form onSubmit={submitMessage} className="space-y-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                                    <input
                                        type="text"
                                        className="w-full border-gray-300 rounded-md shadow-sm focus:ring-slfa-coral focus:border-slfa-coral"
                                        value={data.subject}
                                        onChange={e => setData('subject', e.target.value)}
                                        required
                                    />
                                    <InputError message={errors.subject} className="mt-2" />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Message</label>
                                    <textarea
                                        rows="4"
                                        className="w-full border-gray-300 rounded-md shadow-sm focus:ring-slfa-coral focus:border-slfa-coral resize-none"
                                        value={data.message}
                                        onChange={e => setData('message', e.target.value)}
                                        required
                                        placeholder="Introduce yourself and state your reason for contacting..."
                                    ></textarea>
                                    <InputError message={errors.message} className="mt-2" />
                                </div>
                                <div className="flex justify-end gap-3">
                                    <button
                                        type="button"
                                        onClick={() => setShowMessageForm(false)}
                                        className="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="bg-slfa-coral hover:bg-slfa-coral-dark text-white font-bold py-2 px-6 rounded-md transition disabled:opacity-70 flex items-center"
                                    >
                                        Send Message <Send className="w-4 h-4 ml-2" />
                                    </button>
                                </div>
                            </form>
                        </div>
                    )}

                </div>
            </div>
        </SLFAWebsiteLayout>
    );
}
