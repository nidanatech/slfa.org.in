import React from 'react';

export default function BrandMark({ compact = false }) {
    return (
        <span className="inline-flex items-center gap-2.5" aria-label="SLFA, Sri Lankan Friendship Association of South India">
            <span className={`${compact ? 'h-8 w-8' : 'h-11 w-11'} shrink-0 rounded-full border-2 border-[#D4AF37] bg-gray-950 p-0.5 shadow-[0_0_0_2px_#800000]`}>
                <span className="block h-full w-full rounded-full bg-cover bg-center" style={{ backgroundImage: "url('/images/slfa-bg.png')" }} aria-hidden="true" />
            </span>
            <span className="flex flex-col leading-none">
                <span className={`font-lato ${compact ? 'text-xl' : 'text-[1.65rem]'} font-black tracking-tight text-[#F79236]`}>SLFA</span>
                {!compact && <span className="mt-1 text-[7px] font-bold uppercase tracking-[0.16em] text-gray-300">Sri Lankan Friendship Association</span>}
            </span>
        </span>
    );
}
