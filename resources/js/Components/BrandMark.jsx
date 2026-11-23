import React from 'react';

export default function BrandMark({ compact = false }) {
    return (
        <span className="inline-flex items-center gap-2.5" aria-label="SLFA, Sri Lankan Friendship Association of South India, registration number 234 of 2019">
            <img src="/images/slfa-crest.webp" alt="" width="160" height="160" className={`${compact ? 'h-9 w-9' : 'h-12 w-12'} shrink-0 object-contain`} fetchPriority="high" />
            <span className="flex flex-col leading-none">
                <span className={`font-lato ${compact ? 'text-xl' : 'text-[1.65rem]'} font-normal tracking-tight text-[#F79236]`}>SLFA</span>
                <span className={`${compact ? 'mt-0.5 text-[5px] tracking-[0.1em]' : 'mt-1 text-[7px] tracking-[0.16em]'} font-normal uppercase text-gray-300`}>of South India</span>
                <span className={`${compact ? 'mt-0.5 text-[5px] tracking-[0.06em]' : 'mt-1 text-[6px] tracking-[0.11em]'} font-normal uppercase text-gray-400`}>Reg. No. 234/2019</span>
            </span>
        </span>
    );
}
