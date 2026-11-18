import { Head, Link, router } from '@inertiajs/react';
import SLFAWebsiteLayout from '@/Layouts/SLFAWebsiteLayout';
import { Search, MapPin, Briefcase, ChevronRight } from 'lucide-react';
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
        <SLFAWebsiteLayout user={auth.user}>
            <Head title="Member Directory" />

            <div className="bg-slfa-navy text-white py-12">
                <div className="slfa-container text-center animate-fade-up">
                    <h1 className="text-4xl font-bold mb-4">Member Directory</h1>
                    <p className="text-gray-300">Connect with other SLFA members</p>
                </div>
            </div>

            <div className="slfa-section bg-slfa-cream min-h-screen">
                <div className="slfa-container">
                    
                    {/* Filters & Search */}
                    <div className="bg-white p-4 md:p-6 rounded-lg shadow-sm border border-gray-100 mb-8 animate-fade-up">
                        <form onSubmit={handleSearch} className="flex flex-col lg:flex-row gap-4">
                            
                            <div className="flex-1 relative">
                                <Search className="absolute left-3 top-3 w-5 h-5 text-gray-400" />
                                <input 
                                    type="text" 
                                    placeholder="Search by name, company, or profession..."
                                    className="w-full pl-10 border-gray-300 rounded-md shadow-sm focus:ring-slfa-coral focus:border-slfa-coral"
                                    value={searchParams.search}
                                    onChange={(e) => setSearchParams({...searchParams, search: e.target.value})}
                                />
                            </div>

                            <select 
                                className="border-gray-300 rounded-md shadow-sm focus:ring-slfa-coral focus:border-slfa-coral"
                                value={searchParams.city}
                                onChange={(e) => handleParamChange('city', e.target.value)}
                            >
                                <option value="">All Cities</option>
                                {cities.map(city => (
                                    <option key={city} value={city}>{city}</option>
                                ))}
                            </select>

                            <select 
                                className="border-gray-300 rounded-md shadow-sm focus:ring-slfa-coral focus:border-slfa-coral"
                                value={searchParams.type}
                                onChange={(e) => handleParamChange('type', e.target.value)}
                            >
                                <option value="">All Types</option>
                                <option value="individual">Individual</option>
                                <option value="business">Business</option>
                            </select>

                            <select 
                                className="border-gray-300 rounded-md shadow-sm focus:ring-slfa-coral focus:border-slfa-coral bg-gray-50"
                                value={searchParams.sort}
                                onChange={(e) => handleParamChange('sort', e.target.value)}
                            >
                                <option value="newest">Newest Members</option>
                                <option value="alphabetical">Alphabetical A-Z</option>
                            </select>

                            <button type="submit" className="bg-slfa-coral text-white px-6 py-2 rounded-md font-semibold hover:bg-slfa-coral-dark transition shrink-0">
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
                                className={`bg-white rounded-lg shadow-sm border ${member.featured ? 'border-slfa-coral/50 ring-1 ring-slfa-coral/20' : 'border-gray-100'} p-5 hover:shadow-md transition animate-fade-up flex flex-col h-full`}
                                style={{ animationDelay: `${(index % 8) * 0.05}s` }}
                            >
                                <div className="flex items-center gap-4 mb-4">
                                    <div className="w-16 h-16 rounded-full bg-slfa-peach flex items-center justify-center text-slfa-coral font-bold text-xl shrink-0 overflow-hidden">
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

                                <div className="border-t border-gray-100 pt-3 mt-auto flex justify-between items-center text-sm font-semibold text-slfa-coral">
                                    View Profile <ChevronRight className="w-4 h-4" />
                                </div>
                            </Link>
                        ))}
                    </div>

                    {members.data.length === 0 && (
                        <div className="bg-white p-12 rounded-lg shadow-sm text-center border border-gray-100">
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
                                    className={`px-4 py-2 rounded-md ${
                                        link.active 
                                            ? 'bg-slfa-coral text-white font-bold' 
                                            : !link.url 
                                                ? 'text-gray-400 cursor-not-allowed'
                                                : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-200'
                                    }`}
                                />
                            ))}
                        </div>
                    )}

                </div>
            </div>
        </SLFAWebsiteLayout>
    );
}
