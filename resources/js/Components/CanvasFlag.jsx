import React, { useRef, useEffect, useState } from 'react';

export default function CanvasFlag({ 
    src = '/images/slfa-bg.png', 
    className = '' 
}) {
    const canvasRef = useRef(null);
    const containerRef = useRef(null);
    const [imageLoaded, setImageLoaded] = useState(false);
    const imageRef = useRef(null);
    const requestRef = useRef(null);
    
    // Animation state
    const timeRef = useRef(0);

    useEffect(() => {
        const img = new Image();
        img.crossOrigin = "Anonymous"; // In case it's an external URL
        img.src = src;
        img.onload = () => {
            imageRef.current = img;
            setImageLoaded(true);
        };
    }, [src]);

    useEffect(() => {
        if (!imageLoaded || !canvasRef.current || !containerRef.current) return;

        const canvas = canvasRef.current;
        const ctx = canvas.getContext('2d');
        const img = imageRef.current;

        // Configuration
        const numStrips = 120;
        
        // Physics / Wave Configuration
        // Base wave
        const freq1 = 0.05;
        const speed1 = 0.08;
        const amp1 = 20;
        
        // Secondary ripple
        const freq2 = 0.15;
        const speed2 = 0.12;
        const amp2 = 6;

        let width = 0;
        let height = 0;

        const resize = () => {
            if (!containerRef.current) return;
            // Get dimensions from container
            width = containerRef.current.clientWidth;
            
            // Use a fixed wide aspect ratio specifically for the "SLFA" wordmark
            // This prevents huge vertical gaps if the source image is tall.
            const aspect = 0.26; 
            height = width * aspect;
            
            // Add padding for the wave amplitude so it doesn't clip
            const maxAmplitude = amp1 + amp2;
            
            canvas.width = width;
            canvas.height = height + (maxAmplitude * 2);
            canvas.style.width = `${width}px`;
            canvas.style.height = `${height + (maxAmplitude * 2)}px`;
        };

        // Initial resize
        resize();
        
        // Handle window resize
        window.addEventListener('resize', resize);

        const render = () => {
            timeRef.current += 1;
            const t = timeRef.current;
            
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            const maxAmplitude = amp1 + amp2;
            const stripWidth = width / numStrips;
            const imgStripWidth = img.width / numStrips;

            // 1. Draw the waving flag
            for (let i = 0; i < numStrips; i++) {
                const progress = i / numStrips;
                const w1 = Math.sin((i * freq1) + (t * speed1)) * amp1;
                const w2 = Math.sin((i * freq2) + (t * speed2)) * amp2;
                const offset = (w1 + w2) * progress;

                ctx.drawImage(
                    img,
                    i * imgStripWidth, 0, imgStripWidth, img.height,
                    i * stripWidth, maxAmplitude + offset, stripWidth + 1, height
                );
            }
            
            // 2. Clip the canvas to the text "SLFA"
            ctx.globalCompositeOperation = 'destination-in';
            
            // Responsive font size based on canvas width to match Tailwind scaling
            const fontSize = width * 0.28; 
            ctx.font = `900 ${fontSize}px system-ui, -apple-system, sans-serif`;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            // Slight letter-spacing reduction matching 'tracking-tighter'
            ctx.letterSpacing = '-0.05em';
            
            ctx.fillText("SLFA", canvas.width / 2, canvas.height / 2);
            
            // 3. Add Inner Shadow / Perspective
            ctx.globalCompositeOperation = 'source-atop';
            
            // Top inner shadow (darkness falling from top)
            ctx.shadowColor = 'rgba(0, 0, 0, 0.95)';
            ctx.shadowBlur = 25;
            ctx.shadowOffsetX = 0;
            ctx.shadowOffsetY = 20;
            ctx.lineWidth = 8;
            ctx.strokeStyle = 'rgba(0,0,0,0.6)';
            ctx.strokeText("SLFA", canvas.width / 2, canvas.height / 2);

            // Bottom subtle highlight for 3D bevel effect
            ctx.shadowColor = 'rgba(255, 255, 255, 0.3)';
            ctx.shadowBlur = 10;
            ctx.shadowOffsetX = 0;
            ctx.shadowOffsetY = -5;
            ctx.lineWidth = 3;
            ctx.strokeStyle = 'rgba(255,255,255,0.2)';
            ctx.strokeText("SLFA", canvas.width / 2, canvas.height / 2);
            
            // Restore default blending and remove shadows for next frame
            ctx.globalCompositeOperation = 'source-over';
            ctx.shadowColor = 'transparent';
            ctx.shadowBlur = 0;
            ctx.shadowOffsetX = 0;
            ctx.shadowOffsetY = 0;
            
            requestRef.current = requestAnimationFrame(render);
        };

        requestRef.current = requestAnimationFrame(render);

        return () => {
            window.removeEventListener('resize', resize);
            if (requestRef.current) {
                cancelAnimationFrame(requestRef.current);
            }
        };
    }, [imageLoaded, src]);

    return (
        <div ref={containerRef} className={`w-full flex justify-center items-center ${className}`}>
            <canvas ref={canvasRef} className="max-w-full" />
        </div>
    );
}
