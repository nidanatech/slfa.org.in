import React from 'react';
import SouvenirCard from './SouvenirCard';

export default function SouvenirSection() {
    return (
        <div className="relative pt-24 pb-48 px-4 sm:px-6 lg:px-8 overflow-visible" style={{ minHeight: '300vh' }}>
            <div className="max-w-7xl mx-auto mb-16 text-center">
                <span className="inline-block px-3 py-1 rounded-full bg-slfa-maroon/10 text-xs font-bold text-slfa-maroon tracking-widest uppercase mb-4 border border-slfa-maroon/20">
                    SLFA Souvenir
                </span>
                <h1 className="text-5xl md:text-6xl font-abhaya font-extrabold text-[#800000] mb-6">
                    A Journey of Impact
                </h1>
                <p className="text-xl text-gray-600 max-w-3xl mx-auto font-lato font-light">
                    Reflecting on our milestones, cultural bonds, and the vibrant community we have built together over the years.
                </p>
            </div>

            {/* Card 1: Dignitaries */}
            <SouvenirCard index={0} title="Messages of Support" theme="darkBlue">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-8 md:gap-12">
                    <div className="flex flex-col gap-4 border border-white/10 p-6 rounded-2xl bg-white/5 backdrop-blur-sm">
                        <h3 className="text-slfa-gold font-bold text-xl font-abhaya tracking-wide">Dr. D. Venkateshwaran</h3>
                        <p className="text-xs uppercase tracking-widest text-gray-400 font-bold mb-2">Deputy High Commissioner of Sri Lanka</p>
                        <p className="text-gray-300 font-light italic leading-relaxed text-sm">
                            "It is a privilege to note that the SLFA is a unifying force. It has brought the community under one umbrella. As a forum, it creates an opportunity for mutual acquaintance..."
                        </p>
                    </div>
                    <div className="flex flex-col gap-4 border border-white/10 p-6 rounded-2xl bg-white/5 backdrop-blur-sm">
                        <h3 className="text-slfa-gold font-bold text-xl font-abhaya tracking-wide">Mr. Bose Dharmalingam</h3>
                        <p className="text-xs uppercase tracking-widest text-gray-400 font-bold mb-2">President, SLFA</p>
                        <p className="text-gray-300 font-light italic leading-relaxed text-sm">
                            "Looking ahead, I am filled with optimism. We will continue to build on our strong foundation to foster professional relationships, promote cultural exchange, and represent our collective interests."
                        </p>
                    </div>
                </div>
            </SouvenirCard>

            {/* Card 2: Cultural Bond */}
            <SouvenirCard index={1} title="Sri Lankan and Indian Culture: A Bond Beyond Borders" theme="parchment">
                <p className="text-center text-lg md:text-xl font-lato font-light text-gray-700 max-w-4xl mx-auto mb-12">
                    Culture transcends political boundaries. The profound connection between Sri Lanka and India is deeply rooted in shared traditions, art forms, and histories.
                </p>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-8 h-[400px]">
                    <div className="relative rounded-2xl overflow-hidden group shadow-lg">
                        <div className="absolute inset-0 bg-black/40 group-hover:bg-black/20 transition-colors z-10"></div>
                        <img src="/storage/legacy/gallery/gallery2.jpg" alt="Kandyan Dance" className="absolute inset-0 w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-700" />
                        <div className="absolute bottom-0 left-0 right-0 p-6 z-20 bg-gradient-to-t from-black/80 to-transparent">
                            <h3 className="text-2xl font-bold text-white mb-2 font-abhaya">Kandyan Dance</h3>
                            <p className="text-gray-200 text-sm font-light">The majestic traditional dance form of Sri Lanka, symbolizing vibrant storytelling and rhythmic grace.</p>
                        </div>
                    </div>
                    <div className="relative rounded-2xl overflow-hidden group shadow-lg">
                        <div className="absolute inset-0 bg-black/40 group-hover:bg-black/20 transition-colors z-10"></div>
                        <img src="/storage/legacy/gallery/gallery6.jpg" alt="Bharatanatyam" className="absolute inset-0 w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-700" />
                        <div className="absolute bottom-0 left-0 right-0 p-6 z-20 bg-gradient-to-t from-black/80 to-transparent">
                            <h3 className="text-2xl font-bold text-white mb-2 font-abhaya">Bharatanatyam</h3>
                            <p className="text-gray-200 text-sm font-light">The classical dance of South India, mirroring the shared expressive arts and spiritual depth.</p>
                        </div>
                    </div>
                </div>
            </SouvenirCard>

            {/* Card 3: Landmarks */}
            <SouvenirCard index={2} title="Iconic Landmarks" theme="parchment">
                <div className="flex flex-col gap-8">
                    {/* Sigiriya */}
                    <div className="flex flex-col md:flex-row items-center gap-8 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                        <div className="w-full md:w-1/2 h-64 rounded-xl overflow-hidden">
                            <img src="/storage/legacy/gallery/gallery4.jpg" alt="Sigiriya Rock" className="w-full h-full object-cover" />
                        </div>
                        <div className="w-full md:w-1/2">
                            <h3 className="text-3xl font-bold text-[#800000] mb-4 font-abhaya">Sigiriya Rock Fortress</h3>
                            <p className="text-gray-600 leading-relaxed font-light">
                                An ancient rock fortress and palace ruin in central Sri Lanka, rising 200 meters above the surrounding plains. A UNESCO World Heritage site and a testament to ancient engineering and artistry.
                            </p>
                        </div>
                    </div>
                    {/* Mahabalipuram */}
                    <div className="flex flex-col md:flex-row-reverse items-center gap-8 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                        <div className="w-full md:w-1/2 h-64 rounded-xl overflow-hidden">
                            <img src="/storage/legacy/gallery/gallery5.jpg" alt="Mahabalipuram" className="w-full h-full object-cover" />
                        </div>
                        <div className="w-full md:w-1/2 text-left md:text-right">
                            <h3 className="text-3xl font-bold text-[#800000] mb-4 font-abhaya">Mahabalipuram</h3>
                            <p className="text-gray-600 leading-relaxed font-light">
                                Renowned for its intricate rock-cut temples and shore monuments, this historic town on the Coromandel Coast reflects the monumental architectural shared heritage across the ocean.
                            </p>
                        </div>
                    </div>
                </div>
            </SouvenirCard>

            {/* Card 4: Timeline */}
            <SouvenirCard index={3} title="Our Journey" theme="darkBlue">
                <div className="relative py-8">
                    {/* Vertical Line */}
                    <div className="absolute left-4 md:left-1/2 top-0 bottom-0 w-0.5 bg-slfa-gold/30"></div>
                    
                    <div className="space-y-12">
                        {/* 2012-2017 */}
                        <div className="relative flex flex-col md:flex-row items-center">
                            <div className="hidden md:flex w-1/2 justify-end pr-12 text-right">
                                <div>
                                    <h4 className="text-2xl font-bold text-slfa-gold mb-2 font-abhaya">2012 - 2017</h4>
                                    <p className="text-gray-300 font-light text-sm leading-relaxed">Formation and early growth. Establishing the foundation for a united community in South India.</p>
                                </div>
                            </div>
                            <div className="absolute left-4 md:left-1/2 transform -translate-x-1/2 w-4 h-4 rounded-full bg-slfa-gold border-4 border-slfa-navy shadow-[0_0_0_4px_rgba(212,175,55,0.2)]"></div>
                            <div className="flex md:hidden ml-12 pb-4">
                                <div>
                                    <h4 className="text-2xl font-bold text-slfa-gold mb-2 font-abhaya">2012 - 2017</h4>
                                    <p className="text-gray-300 font-light text-sm leading-relaxed">Formation and early growth. Establishing the foundation for a united community in South India.</p>
                                </div>
                            </div>
                        </div>

                        {/* 2018-2021 */}
                        <div className="relative flex flex-col md:flex-row items-center">
                            <div className="absolute left-4 md:left-1/2 transform -translate-x-1/2 w-4 h-4 rounded-full bg-slfa-gold border-4 border-slfa-navy shadow-[0_0_0_4px_rgba(212,175,55,0.2)]"></div>
                            <div className="flex w-full md:w-1/2 ml-12 md:ml-[50%] md:pl-12">
                                <div>
                                    <h4 className="text-2xl font-bold text-slfa-gold mb-2 font-abhaya">2018 - 2021</h4>
                                    <p className="text-gray-300 font-light text-sm leading-relaxed">Growth, recognition, and our first annual dinners. The community expanded significantly, hosting regular events despite global challenges.</p>
                                </div>
                            </div>
                        </div>

                        {/* 2022-2023 */}
                        <div className="relative flex flex-col md:flex-row items-center">
                            <div className="hidden md:flex w-1/2 justify-end pr-12 text-right">
                                <div>
                                    <h4 className="text-2xl font-bold text-slfa-gold mb-2 font-abhaya">2022 - 2023</h4>
                                    <p className="text-gray-300 font-light text-sm leading-relaxed">Rebuilding and strong momentum. Strengthening bilateral trade relations and reinforcing the cultural bridge between our nations.</p>
                                </div>
                            </div>
                            <div className="absolute left-4 md:left-1/2 transform -translate-x-1/2 w-4 h-4 rounded-full bg-slfa-gold border-4 border-slfa-navy shadow-[0_0_0_4px_rgba(212,175,55,0.2)]"></div>
                            <div className="flex md:hidden ml-12 pb-4">
                                <div>
                                    <h4 className="text-2xl font-bold text-slfa-gold mb-2 font-abhaya">2022 - 2023</h4>
                                    <p className="text-gray-300 font-light text-sm leading-relaxed">Rebuilding and strong momentum. Strengthening bilateral trade relations and reinforcing the cultural bridge between our nations.</p>
                                </div>
                            </div>
                        </div>

                        {/* 2024 & Beyond */}
                        <div className="relative flex flex-col md:flex-row items-center">
                            <div className="absolute left-4 md:left-1/2 transform -translate-x-1/2 w-4 h-4 rounded-full bg-[#800000] border-4 border-slfa-gold shadow-[0_0_0_4px_rgba(128,0,0,0.4)]"></div>
                            <div className="flex w-full md:w-1/2 ml-12 md:ml-[50%] md:pl-12">
                                <div>
                                    <h4 className="text-2xl font-bold text-white mb-2 font-abhaya">2024 & Beyond</h4>
                                    <p className="text-gray-300 font-light text-sm leading-relaxed">A vision for the future. Continuing our impact and extending our heartfelt thanks to all members for making this journey possible.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </SouvenirCard>
        </div>
    );
}
