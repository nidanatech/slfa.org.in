import React from 'react';

export default function ApplicationLogo({ className = "", imgClassName = "h-8", textClassName = "text-2xl font-medium tracking-tight", text = "SLFA", isLight = false }) {
    return (
        <div className={`flex flex-col items-center gap-1.5 ${className}`}>
            <div className="flex items-center gap-2">
                <img
                    src="/storage/legacy/logo.png"
                    alt="SLFA Logo"
                    className={imgClassName}
                    onError={(e) => e.target.style.display = 'none'}
                />
                {text && <span className={textClassName}>{text}</span>}
            </div>
        </div>
    );
}
