import React, { useRef } from 'react';
import { motion, useScroll, useTransform, useSpring } from 'motion/react';
import { Plane } from 'lucide-react';

export default function JourneyTimeline() {
    const targetRef = useRef(null);
    
    // Track scroll progress over this 400vh container
    const { scrollYProgress } = useScroll({
        target: targetRef,
    });

    const smoothProgress = useSpring(scrollYProgress, {
        stiffness: 100,
        damping: 30,
        restDelta: 0.001
    });

    // The timeline track translates left as we scroll down
    const x = useTransform(smoothProgress, [0, 1], ["0%", "-75%"]);
    
    // The background image pans slightly for parallax
    const bgX = useTransform(smoothProgress, [0, 1], ["0%", "-20%"]);

    return (
        <section ref={targetRef} className="relative h-[400vh] bg-[#0b0c10]">
            
            {/* Sticky viewport container */}
            <div className="sticky top-0 h-screen w-full overflow-hidden flex items-center">
                
                {/* Parallax Realistic Background */}
                <motion.div 
                    className="absolute inset-0 z-0 opacity-60"
                    style={{
                        backgroundImage: 'url(/images/above-clouds-bg.png)',
                        backgroundSize: 'cover',
                        backgroundPosition: 'center',
                        x: bgX,
                        scale: 1.05 // Prevent edges showing during parallax
                    }}
                />

                {/* Dark Vignette Overlay for readability */}
                <div className="absolute inset-0 bg-gradient-to-r from-[#0b0c10]/90 via-[#0b0c10]/40 to-[#0b0c10]/90 z-0 pointer-events-none"></div>
                <div className="absolute inset-0 bg-gradient-to-b from-[#0b0c10]/90 via-transparent to-[#0b0c10]/90 z-0 pointer-events-none"></div>

                {/* Static Header Section */}
                <div className="absolute top-20 md:top-32 left-8 md:left-24 z-20 pointer-events-none max-w-sm md:max-w-md">
                    <span className="inline-block px-4 py-1 rounded-full bg-[#D4AF37]/20 border border-[#D4AF37]/50 text-xs font-bold text-[#D4AF37] tracking-widest uppercase mb-4 font-lato shadow-[0_0_15px_rgba(212,175,55,0.2)] backdrop-blur-md">
                        Our Journey
                    </span>
                    <h2 className="text-4xl md:text-6xl font-abhaya font-extrabold text-white mb-6 drop-shadow-xl leading-tight">Taking Flight <br className="hidden md:block"/>Together</h2>
                    <div className="h-[2px] w-24 bg-[#D4AF37] mb-6 shadow-[0_0_10px_#D4AF37]"></div>
                    <p className="text-gray-300 font-lato font-light text-lg md:text-xl drop-shadow-md">A brief history of our impact, resilience, and community building.</p>
                </div>

                {/* Scrolling Horizontal Track */}
                <motion.div 
                    style={{ x }}
                    className="relative flex items-center h-full w-[400vw] shrink-0 z-10"
                >
                    {/* The Flight Path (Horizontal Line) */}
                    <div className="absolute left-[5vw] right-[10vw] h-[2px] top-1/2 -translate-y-1/2 -z-10">
                        {/* Dashed Background Path */}
                        <div className="absolute inset-0 border-t-[2px] border-dashed border-white/20 w-full"></div>
                        
                        {/* Animated Glowing Fill Path */}
                        <motion.div 
                            className="absolute top-[-1px] left-0 h-[4px] bg-gradient-to-r from-[#D4AF37] via-[#D4AF37] to-[#800000] rounded-full shadow-[0_0_20px_rgba(212,175,55,0.8)]"
                            style={{ 
                                width: useTransform(smoothProgress, [0, 1], ["0%", "100%"]) 
                            }}
                        >
                            {/* Airplane Marker at the leading edge */}
                            <div className="absolute right-0 top-1/2 translate-x-1/2 -translate-y-1/2 text-gray-900 rotate-90 z-20 drop-shadow-[0_10px_20px_rgba(0,0,0,0.6)] bg-white rounded-full p-2.5 border-[3px] border-[#D4AF37]">
                                <Plane fill="#D4AF37" stroke="#800000" strokeWidth={1.5} className="w-5 h-5 md:w-6 md:h-6" />
                            </div>
                        </motion.div>
                    </div>

                    {/* Timeline Items Container */}
                    <div className="flex w-full pl-[50vw] md:pl-[45vw] pr-[20vw] justify-between items-center h-0 relative">
                        
                        {/* 2012-2017 */}
                        <div className="relative group w-0 flex justify-center">
                            {/* Point on line */}
                            <div className="absolute top-1/2 -translate-y-1/2 w-4 h-4 rounded-full bg-[#0b0c10] border-[3px] border-white/40 z-10 transition-all duration-300 group-hover:border-[#D4AF37] group-hover:scale-150 group-hover:shadow-[0_0_20px_rgba(212,175,55,0.6)]"></div>
                            
                            {/* Card - Above Line */}
                            <div className="absolute bottom-[50%] mb-12 w-[300px] md:w-[400px] transform transition-all duration-500 group-hover:-translate-y-2 opacity-90 group-hover:opacity-100">
                                <div className="bg-[#0b0c10]/80 backdrop-blur-md p-8 rounded-2xl shadow-[0_10px_30px_rgba(0,0,0,0.5)] border border-white/10 group-hover:border-[#D4AF37]/50 transition-colors duration-500 relative">
                                    <div className="absolute bottom-[-10px] left-1/2 -translate-x-1/2 border-l-[10px] border-l-transparent border-r-[10px] border-r-transparent border-t-[10px] border-t-[#0b0c10]/80 group-hover:border-t-[#D4AF37]/50 transition-colors duration-500"></div>
                                    <h4 className="text-3xl md:text-4xl font-extrabold text-white mb-3 font-abhaya drop-shadow-md">2012 - 2017</h4>
                                    <p className="text-gray-300 font-lato font-light text-base md:text-lg leading-relaxed">Formation and early growth. Establishing the foundation for a united community in South India.</p>
                                </div>
                            </div>
                        </div>

                        {/* 2018-2021 */}
                        <div className="relative group w-0 flex justify-center">
                            {/* Point on line */}
                            <div className="absolute top-1/2 -translate-y-1/2 w-4 h-4 rounded-full bg-[#0b0c10] border-[3px] border-white/40 z-10 transition-all duration-300 group-hover:border-[#D4AF37] group-hover:scale-150 group-hover:shadow-[0_0_20px_rgba(212,175,55,0.6)]"></div>
                            
                            {/* Card - Below Line */}
                            <div className="absolute top-[50%] mt-12 w-[300px] md:w-[400px] transform transition-all duration-500 group-hover:translate-y-2 opacity-90 group-hover:opacity-100">
                                <div className="bg-[#0b0c10]/80 backdrop-blur-md p-8 rounded-2xl shadow-[0_10px_30px_rgba(0,0,0,0.5)] border border-white/10 group-hover:border-[#D4AF37]/50 transition-colors duration-500 relative">
                                    <div className="absolute top-[-10px] left-1/2 -translate-x-1/2 border-l-[10px] border-l-transparent border-r-[10px] border-r-transparent border-b-[10px] border-b-[#0b0c10]/80 group-hover:border-b-[#D4AF37]/50 transition-colors duration-500"></div>
                                    <h4 className="text-3xl md:text-4xl font-extrabold text-white mb-3 font-abhaya drop-shadow-md">2018 - 2021</h4>
                                    <p className="text-gray-300 font-lato font-light text-base md:text-lg leading-relaxed">Growth, recognition, and our first annual dinners. The community expanded significantly, hosting regular events despite global challenges.</p>
                                </div>
                            </div>
                        </div>

                        {/* 2022-2023 */}
                        <div className="relative group w-0 flex justify-center">
                            {/* Point on line */}
                            <div className="absolute top-1/2 -translate-y-1/2 w-4 h-4 rounded-full bg-[#0b0c10] border-[3px] border-white/40 z-10 transition-all duration-300 group-hover:border-[#D4AF37] group-hover:scale-150 group-hover:shadow-[0_0_20px_rgba(212,175,55,0.6)]"></div>
                            
                            {/* Card - Above Line */}
                            <div className="absolute bottom-[50%] mb-12 w-[300px] md:w-[400px] transform transition-all duration-500 group-hover:-translate-y-2 opacity-90 group-hover:opacity-100">
                                <div className="bg-[#0b0c10]/80 backdrop-blur-md p-8 rounded-2xl shadow-[0_10px_30px_rgba(0,0,0,0.5)] border border-white/10 group-hover:border-[#D4AF37]/50 transition-colors duration-500 relative">
                                    <div className="absolute bottom-[-10px] left-1/2 -translate-x-1/2 border-l-[10px] border-l-transparent border-r-[10px] border-r-transparent border-t-[10px] border-t-[#0b0c10]/80 group-hover:border-t-[#D4AF37]/50 transition-colors duration-500"></div>
                                    <h4 className="text-3xl md:text-4xl font-extrabold text-white mb-3 font-abhaya drop-shadow-md">2022 - 2023</h4>
                                    <p className="text-gray-300 font-lato font-light text-base md:text-lg leading-relaxed">Rebuilding and strong momentum. Strengthening bilateral trade relations and reinforcing the cultural bridge between our nations.</p>
                                </div>
                            </div>
                        </div>

                        {/* 2024 & Beyond */}
                        <div className="relative group w-0 flex justify-center">
                            {/* Point on line */}
                            <div className="absolute top-1/2 -translate-y-1/2 w-4 h-4 rounded-full bg-[#0b0c10] border-[3px] border-[#D4AF37]/50 z-10 transition-all duration-300 group-hover:border-[#D4AF37] group-hover:scale-150 group-hover:shadow-[0_0_20px_rgba(212,175,55,0.6)]"></div>
                            
                            {/* Card - Below Line */}
                            <div className="absolute top-[50%] mt-12 w-[300px] md:w-[400px] transform transition-all duration-500 group-hover:translate-y-2 opacity-95 group-hover:opacity-100">
                                <div className="bg-[#D4AF37]/10 backdrop-blur-md p-8 rounded-2xl shadow-[0_10px_30px_rgba(0,0,0,0.5)] border border-[#D4AF37]/30 group-hover:border-[#D4AF37]/60 transition-colors duration-500 relative">
                                    <div className="absolute top-[-10px] left-1/2 -translate-x-1/2 border-l-[10px] border-l-transparent border-r-[10px] border-r-transparent border-b-[10px] border-b-[#D4AF37]/30 group-hover:border-b-[#D4AF37]/60 transition-colors duration-500"></div>
                                    <h4 className="text-3xl md:text-4xl font-extrabold text-[#D4AF37] mb-3 font-abhaya drop-shadow-md">2024 & Beyond</h4>
                                    <p className="text-gray-200 font-lato font-light text-base md:text-lg leading-relaxed">A vision for the future. Continuing our impact and extending our heartfelt thanks to all members for making this journey possible.</p>
                                </div>
                            </div>
                        </div>

                    </div>
                </motion.div>
            </div>
        </section>
    );
}
