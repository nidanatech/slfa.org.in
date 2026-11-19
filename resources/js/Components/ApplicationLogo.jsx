import React from 'react';
import BrandMark from '@/Components/BrandMark';

export default function ApplicationLogo({ className = "", imgClassName = "h-8", textClassName = "text-2xl font-medium tracking-tight", text = "SLFA", isLight = false }) {
    return (
        <div className={`flex flex-col items-center gap-1.5 ${className}`}>
            <div className="flex items-center gap-2">
                <BrandMark compact />
            </div>
        </div>
    );
}
