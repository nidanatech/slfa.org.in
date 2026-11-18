import { Head, Link, router } from '@inertiajs/react';
import SLFAWebsiteLayout from '@/Layouts/SLFAWebsiteLayout';
import SLFAPageHero from '@/Components/SLFAPageHero';
import { Search, MapPin, Briefcase, ChevronRight, Users } from 'lucide-react';
import { useState } from 'react';

export default function Directory({ auth, members, filters, cities }) {
    
    const [searchParams, setSearchParams] = useState({
        search: filters.search || '',
        city: filters.city || '',
        type: filters.type || '',
        sort: filters.sort || 'newest',
    });

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(route('directory'), searchParams, { preserveState: true });
    };

    const handleParamChange = (key, value) => {
        const newParams = { ...searchParams, [key]: value };
        setSearchParams(newParams);
        if (key !== 'search') {
            router.get(route('directory'), newParams, { preserveState: true });
        }
    };

    return (
        <SLFAWebsiteLayout user={auth.user} darkNav={true}>
            <Head title="Member Directory" />

            <SLFAPageHero eyebrow="Members only" title={<>Your community <span className="italic font-light text-[#D4AF37]">directory</span></>} description="Find and connect with SLFA members who have chosen to appear in the directory." />

            <section className="min-h-[40vh] bg-[#FAF8F4] py-16 sm:py-20">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    
                    {/* Filters & Search */}
                    <div className="mb-8 rounded-3xl border border-gray-100 bg-white p-5 shadow-[0_12px_40px_rgba(15,23,42,0.04)] md:p-7">
                        <form onSubmit={handleSearch} className="flex flex-col lg:flex-row gap-4">
                            
                            <div className="flex-1 relative">
                                <Search className="absolute left-3 top-3 w-5 h-5 text-gray-400" />
                                <input 
                                    type="text" 
                                    placeholder="Search by name, company, or profession..."
                                    className="w-full rounded-xl border-gray-200 bg-[#FAF8F4] pl-10 shadow-sm focus:border-[#D4AF37] focus:ring-[#D4AF37]"
                                    value={searchParams.search}
                                    onChange={(e) => setSearchParams({...searchParams, search: e.target.value})}
                                />
                            </div>

                            <select 
                                className="rounded-xl border-gray-200 bg-[#FAF8F4] shadow-sm focus:border-[#D4AF37] focus:ring-[#D4AF37]"
                                value={searchParams.city}
                                onChange={(e) => handleParamChange('city', e.target.value)}
                            >
                                <option value="">All Cities</option>
                                {cities.map(city => (
                                    <option key={city} value={city}>{city}</option>
                                ))}
                            </select>

                            <select 
                                className="rounded-xl border-gray-200 bg-[#FAF8F4] shadow-sm focus:border-[#D4AF37] focus:ring-[#D4AF37]"
                                value={searchParams.type}
                                onChange={(e) => handleParamChange('type', e.target.value)}
                            >
                                <option value="">All Types</option>
                                <option value="individual">Individual</option>
                                <option value="business">Business</option>
                            </select>

                            <select 
                                className="rounded-xl border-gray-200 bg-[#FAF8F4] shadow-sm focus:border-[#D4AF37] focus:ring-[#D4AF37]"
                                value={searchParams.sort}
                                onChange={(e) => handleParamChange('sort', e.target.value)}
                            >
                                <option value="newest">Newest Members</option>
                                <option value="alphabetical">Alphabetical A-Z</option>
                            </select>

                            <button type="submit" className="shrink-0 rounded-full bg-gray-950 px-6 py-2 font-semibold text-white transition hover:bg-[#800000]">
                                Search
                            </button>
                        </form>
                    </div>

                    {/* Directory Grid */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        {members.data.map((member, index) => (
                            <Link 
                                key={member.id} 
                                href={route('directory.show', member.id)}
                                className={`flex h-full flex-col rounded-3xl border bg-white p-6 shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl ${member.featured ? 'border-[#D4AF37] ring-1 ring-[#D4AF37]/30' : 'border-gray-100'}`}
                                style={{ animationDelay: `${(index % 8) * 0.05}s` }}
                            >
                                <div className="flex items-center gap-4 mb-4">
                                    <div className="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-full bg-[#D4AF37]/15 text-xl font-bold text-[#9A7714]">
                                        {member.photo_path ? (
                                            <img src={`/storage/${member.photo_path}`} alt="" className="w-full h-full object-cover" />
                                        ) : (
                                            member.full_name.charAt(0)
                                        )}
                                    </div>
                                    <div>
                                        <h3 className="font-bold text-slfa-navy text-lg leading-tight">{member.full_name}</h3>
                                        <span className={`text-xs font-semibold px-2 py-0.5 rounded-full mt-1 inline-block ${member.membership_type === 'business' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'}`}>
                                            {member.membership_type}
                                        </span>
                                    </div>
                                </div>
                                
                                <div className="space-y-2 text-sm text-gray-600 mb-4 flex-grow">
                                    {member.occupation && (
                                        <div className="flex items-start">
                                            <Briefcase className="w-4 h-4 mr-2 mt-0.5 shrink-0 text-gray-400" />
                                            <span className="line-clamp-2">{member.designation ? `${member.designation} at ` : ''}{member.company_name || member.occupation}</span>
                                        </div>
                                    )}
                                    {member.city && (
                                        <div className="flex items-center">
                                            <MapPin className="w-4 h-4 mr-2 shrink-0 text-gray-400" />
                                            <span>{member.city}</span>
                                        </div>
                                    )}
                                </div>

                                <div className="mt-auto flex items-center justify-between border-t border-gray-100 pt-3 text-sm font-semibold text-[#800000]">
                                    View Profile <ChevronRight className="w-4 h-4" />
                                </div>
                            </Link>
                        ))}
                    </div>

                    {members.data.length === 0 && (
                        <div className="rounded-3xl border border-gray-100 bg-white p-12 text-center shadow-sm">
                            <Users className="w-16 h-16 text-gray-300 mx-auto mb-4" />
                            <h3 className="text-xl font-bold text-slfa-navy mb-2">No members found</h3>
                            <p className="text-gray-500">Try adjusting your search or filters.</p>
                            <button onClick={() => { setSearchParams({search:'', city:'', type:'', sort:'newest'}); router.get(route('directory')); }} className="mt-4 text-slfa-coral font-medium hover:underline">
                                Clear all filters
                            </button>
                        </div>
                    )}

                    {/* Pagination */}
                    {members.links.length > 3 && (
                        <div className="mt-12 flex justify-center gap-2">
                            {members.links.map((link, i) => (
                                <Link
                                    key={i}
                                    href={link.url}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                    className={`rounded-full px-4 py-2 ${
                                        link.active 
                                            ? 'bg-gray-950 text-white font-bold'
                                            : !link.url 
                                                ? 'text-gray-400 cursor-not-allowed'
                                                : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-200'
                                    }`}
                                />
                            ))}
                        </div>
                    )}

                </div>
            </section>
        </SLFAWebsiteLayout>
    );
}
