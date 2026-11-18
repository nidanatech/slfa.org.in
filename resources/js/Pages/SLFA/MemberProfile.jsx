import { Head } from '@inertiajs/react';
import SLFAWebsiteLayout from '@/Layouts/SLFAWebsiteLayout';
import SLFAPageHero from '@/Components/SLFAPageHero';
import { MapPin, Briefcase, Mail, Phone, Calendar } from 'lucide-react';

export default function MemberProfile({ auth, member }) {
    return (
        <SLFAWebsiteLayout user={auth.user} darkNav={true}>
            <Head title={`${member.full_name} - Directory`} />

            <SLFAPageHero eyebrow="Member directory" title={member.full_name} description={member.membership_type === 'business' ? 'Business member profile' : 'Individual member profile'} backHref={route('directory')} backLabel="Back to directory" />

            <section className="min-h-[40vh] bg-[#FAF8F4] py-16 sm:py-20">
                <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    
                    <div className="overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-[0_12px_40px_rgba(15,23,42,0.04)]">
                        <div className="p-8 md:p-12">
                            <div className="flex flex-col md:flex-row gap-8 items-start">
                                
                                {/* Avatar */}
                                <div className="flex h-28 w-28 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-[#D4AF37]/15 text-5xl font-bold text-[#9A7714] shadow-inner md:h-36 md:w-36">
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
                                            <h2 className="font-abhaya text-3xl font-bold text-gray-950">{member.full_name}</h2>
                                            <div className="flex flex-wrap gap-2 mt-2">
                                                <span className={`text-xs font-semibold px-3 py-1 rounded-full ${member.membership_type === 'business' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'}`}>
                                                    {member.membership_type === 'business' ? 'Business Member' : 'Individual Member'}
                                                </span>
                                                {member.member_id_string && (
                                                    <span className="text-xs font-semibold px-3 py-1 rounded-full bg-[#D4AF37]/15 text-[#9A7714]">
                                                        ID: {member.member_id_string}
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                        
                                    </div>

                                    {/* Info Grid */}
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-8 mt-8 border-t border-gray-100 pt-8">
                                        
                                        {member.occupation && (
                                            <div className="flex items-start">
                                                <Briefcase className="w-5 h-5 mr-3 text-[#9A7714] shrink-0 mt-0.5" />
                                                <div>
                                                    <p className="text-xs text-gray-500 font-semibold uppercase tracking-wider">Profession</p>
                                                    <p className="font-medium text-gray-950">{member.designation ? `${member.designation}, ` : ''}{member.company_name || member.occupation}</p>
                                                </div>
                                            </div>
                                        )}

                                        {member.city && (
                                            <div className="flex items-start">
                                                <MapPin className="w-5 h-5 mr-3 text-[#9A7714] shrink-0 mt-0.5" />
                                                <div>
                                                    <p className="text-xs text-gray-500 font-semibold uppercase tracking-wider">Location</p>
                                                    <p className="font-medium text-gray-950">{member.city}</p>
                                                </div>
                                            </div>
                                        )}

                                        {member.sri_lanka_connection && (
                                            <div className="flex items-start md:col-span-2">
                                                <svg className="w-5 h-5 mr-3 text-[#9A7714] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                <div>
                                                    <p className="text-xs text-gray-500 font-semibold uppercase tracking-wider">Connection to Sri Lanka</p>
                                                    <p className="font-medium text-gray-950">{member.sri_lanka_connection}</p>
                                                </div>
                                            </div>
                                        )}
                                        
                                        <div className="flex items-start">
                                            <Calendar className="w-5 h-5 mr-3 text-[#9A7714] shrink-0 mt-0.5" />
                                            <div>
                                                <p className="text-xs text-gray-500 font-semibold uppercase tracking-wider">Member Since</p>
                                                <p className="font-medium text-gray-950">{member.joined_at ? new Date(member.joined_at).getFullYear() : 'Unknown'}</p>
                                            </div>
                                        </div>

                                        {member.show_contact_info && (
                                            <>
                                                {member.email && (
                                                    <div className="flex items-start">
                                                        <Mail className="w-5 h-5 mr-3 text-[#9A7714] shrink-0 mt-0.5" />
                                                        <div>
                                                            <p className="text-xs text-gray-500 font-semibold uppercase tracking-wider">Email</p>
                                                            <a href={`mailto:${member.email}`} className="font-medium text-[#9A7714] hover:underline">{member.email}</a>
                                                        </div>
                                                    </div>
                                                )}
                                                {member.phone && (
                                                    <div className="flex items-start">
                                                        <Phone className="w-5 h-5 mr-3 text-[#9A7714] shrink-0 mt-0.5" />
                                                        <div>
                                                            <p className="text-xs text-gray-500 font-semibold uppercase tracking-wider">Phone</p>
                                                            <a href={`tel:${member.phone}`} className="font-medium text-[#9A7714] hover:underline">{member.phone}</a>
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


                </div>
            </section>
        </SLFAWebsiteLayout>
    );
}
