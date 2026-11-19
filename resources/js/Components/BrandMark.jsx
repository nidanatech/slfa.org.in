import React from 'react';

export default function BrandMark({ compact = false }) {
    return (
        <span className="inline-flex items-center gap-2.5" aria-label="SLFA, Sri Lankan Friendship Association of South India">
            <img src="/images/slfa-crest.webp" alt="" width="160" height="160" className={`${compact ? 'h-9 w-9' : 'h-12 w-12'} shrink-0 object-contain`} fetchPriority="high" />
            <span className="flex flex-col leading-none">
                <span className={`font-lato ${compact ? 'text-xl' : 'text-[1.65rem]'} font-black tracking-tight text-[#F79236]`}>SLFA</span>
                {!compact && <span className="mt-1 text-[7px] font-bold uppercase tracking-[0.16em] text-gray-300">of South India</span>}
            </span>
        </span>
    );
}
