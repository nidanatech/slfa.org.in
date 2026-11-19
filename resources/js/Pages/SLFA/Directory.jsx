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

            <section className="min-h-[40vh] bg-[#f5f1e9] py-16 sm:py-20">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    
                    {/* Filters & Search */}
                    <div className="mb-10 border-y border-[#b7a676] py-6">
                        <form onSubmit={handleSearch} className="flex flex-col lg:flex-row gap-4">
                            
                            <div className="flex-1 relative">
                                <Search className="absolute left-3 top-3 w-5 h-5 text-gray-400" />
                                <input 
                                    type="text" 
                                    placeholder="Search by name, company, or profession..."
                                    className="w-full rounded-none border-[#cbbb93] bg-white/70 pl-10 focus:border-[#800000] focus:ring-[#800000]"
                                    value={searchParams.search}
                                    onChange={(e) => setSearchParams({...searchParams, search: e.target.value})}
                                />
                            </div>

                            <select 
                                className="rounded-none border-[#cbbb93] bg-white/70 focus:border-[#800000] focus:ring-[#800000]"
                                value={searchParams.city}
                                onChange={(e) => handleParamChange('city', e.target.value)}
                            >
                                <option value="">All Cities</option>
                                {cities.map(city => (
                                    <option key={city} value={city}>{city}</option>
                                ))}
                            </select>

                            <select 
                                className="rounded-none border-[#cbbb93] bg-white/70 focus:border-[#800000] focus:ring-[#800000]"
                                value={searchParams.type}
                                onChange={(e) => handleParamChange('type', e.target.value)}
                            >
                                <option value="">All Types</option>
                                <option value="individual">Individual</option>
                                <option value="business">Business</option>
                            </select>

                            <select 
                                className="rounded-none border-[#cbbb93] bg-white/70 focus:border-[#800000] focus:ring-[#800000]"
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
                                className={`flex h-full flex-col border-t p-5 transition-colors hover:bg-white/60 ${member.featured ? 'border-[#9a7714]' : 'border-[#b7a676]'}`}
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
                                        <span className="mt-1 inline-block text-xs font-semibold uppercase tracking-wide text-[#8b6915]">
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

                                <div className="mt-auto flex items-center justify-between border-t border-[#d5c8a6] pt-3 text-sm font-semibold text-[#800000]">
                                    View Profile <ChevronRight className="w-4 h-4" />
                                </div>
                            </Link>
                        ))}
                    </div>

                    {members.data.length === 0 && (
                        <div className="border-t border-[#b7a676] py-12 text-center">
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
                            {members.links.map((link, i) => {
                                const label = link.label
                                    .replace('&laquo;', '«')
                                    .replace('&raquo;', '»');

                                if (!link.url) {
                                    return (
                                        <span
                                            key={i}
                                            className="rounded-full px-4 py-2 text-gray-400 cursor-not-allowed"
                                        >
                                            {label}
                                        </span>
                                    );
                                }

                                return (
                                    <Link
                                        key={i}
                                        href={link.url}
                                        className={`rounded-full px-4 py-2 ${
                                            link.active
                                                ? 'bg-gray-950 text-white font-bold'
                                                : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-200'
                                        }`}
                                    >
                                        {label}
                                    </Link>
                                );
                            })}
                        </div>
                    )}

                </div>
            </section>
        </SLFAWebsiteLayout>
    );
}
