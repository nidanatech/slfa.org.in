import React, { useState } from 'react';
import { motion, AnimatePresence } from 'motion/react';

const cities = [
    { name: 'Chennai', x: 285, y: 260, info: 'SLFA Headquarters, largest community base', count: 320, isHub: true, isLK: false },
    { name: 'Madurai', x: 235, y: 420, info: 'Historical cultural connection center', count: 85, isHub: true, isLK: false },
    { name: 'Bangalore', x: 205, y: 310, info: 'IT & Professional Chapter', count: 150, isHub: true, isLK: false },
    { name: 'Kochi', x: 170, y: 430, info: 'Kerala Chapter Hub', count: 45, isHub: false, isLK: false },
    { name: 'Hyderabad', x: 215, y: 130, info: 'AP & Telangana Professional Network', count: 60, isHub: false, isLK: false },
    { name: 'Colombo', x: 335, y: 560, info: 'Capital of Sri Lanka, main business/travel terminal', isHub: true, isLK: true },
    { name: 'Jaffna', x: 340, y: 460, info: 'Historical and cultural link to Tamil Nadu', isHub: true, isLK: true },
    { name: 'Trincomalee', x: 385, y: 485, info: 'Eastern Sri Lanka maritime connection', isHub: false, isLK: true }
];

const connections = [
    { from: 'Chennai', to: 'Colombo', type: 'business', label: 'Trade & Consular Liaison' },
    { from: 'Chennai', to: 'Jaffna', type: 'cultural', label: 'Heritage & Direct Travel' },
    { from: 'Madurai', to: 'Colombo', type: 'cultural', label: 'Traditional & Trade links' },
    { from: 'Bangalore', to: 'Colombo', type: 'business', label: 'Tech & Professional Exchange' },
    { from: 'Kochi', to: 'Colombo', type: 'business', label: 'Maritime & Bilateral Trade' }
];

// Helper to draw curved arc path
const getArcPath = (cityA, cityB) => {
    const x1 = cityA.x;
    const y1 = cityA.y;
    const x2 = cityB.x;
    const y2 = cityB.y;
    
    const mx = (x1 + x2) / 2;
    const my = (y1 + y2) / 2;
    
    const dx = x2 - x1;
    const dy = y2 - y1;
    
    const factor = 0.25; 
    const px = -dy * factor;
    const py = dx * factor;
    
    const cx = mx + px;
    const cy = my + py;
    
    return `M ${x1} ${y1} Q ${cx} ${cy} ${x2} ${y2}`;
};

export default function HeroMap() {
    const [hoveredCity, setHoveredCity] = useState(null);

    return (
        <div className="w-full flex flex-col items-center select-none relative z-10 animate-in fade-in duration-700">
            {/* Visual Map Container (Transparent, cardless image) */}
            <div className="w-full max-w-[500px] relative aspect-[5/6] z-10">
                
                {/* Clean, high-performance static satellite image with radial edge fading mask */}
                <div 
                    className="absolute inset-0 pointer-events-none"
                    style={{
                        maskImage: 'radial-gradient(ellipse 75% 75% at 55% 50%, black 40%, transparent 100%)',
                        WebkitMaskImage: 'radial-gradient(ellipse 75% 75% at 55% 50%, black 40%, transparent 100%)'
                    }}
                >
                    <img 
                        src="/images/south_india_srilanka_satellite.png" 
                        alt="South India and Sri Lanka Satellite Map" 
                        className="w-full h-full object-cover"
                        style={{
                            filter: 'drop-shadow(0 4px 20px rgba(0,0,0,0.35))'
                        }}
                    />
                </div>

                {/* Cultural/Navigational Grid */}
                <div className="absolute inset-0 pointer-events-none opacity-[0.06]">
                    <svg width="100%" height="100%">
                        <defs>
                            <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                                <path d="M 40 0 L 0 0 0 40" fill="none" stroke="#C8860A" strokeWidth="0.5" strokeDasharray="3,3" />
                            </pattern>
                        </defs>
                        <rect width="100%" height="100%" fill="url(#grid)" />
                        <circle cx="70" cy="70" r="30" fill="none" stroke="#C8860A" strokeWidth="0.5" />
                        <line x1="70" y1="35" x2="70" y2="105" stroke="#C8860A" strokeWidth="0.5" />
                        <line x1="35" y1="70" x2="105" y2="70" stroke="#C8860A" strokeWidth="0.5" />
                        <text x="66" y="32" className="fill-slfa-saffron text-[9px] font-bold">N</text>
                    </svg>
                </div>


            </div>
        </div>
    );
}