import React, { useEffect, useRef, useState, useMemo } from 'react';
import Globe from 'react-globe.gl';

export default function HeroGlobe() {
    const globeEl = useRef();
    const [dimensions, setDimensions] = useState({ width: 500, height: 500 });
    const [countries, setCountries] = useState([]);
    const containerRef = useRef();

    useEffect(() => {
        // Fetch geojson for minimalist hex globe
        fetch('https://raw.githubusercontent.com/vasturiano/react-globe.gl/master/example/datasets/ne_110m_admin_0_countries.geojson')
            .then(res => res.json())
            .then(data => setCountries(data.features));

        const updateDimensions = () => {
            if (containerRef.current) {
                setDimensions({
                    width: containerRef.current.offsetWidth,
                    height: containerRef.current.offsetHeight || containerRef.current.offsetWidth
                });
            }
        };

        window.addEventListener('resize', updateDimensions);
        updateDimensions();

        if (globeEl.current) {
            globeEl.current.pointOfView({ lat: 15, lng: 80, altitude: 2 }, 0);
            
            // Auto rotate very slowly
            globeEl.current.controls().autoRotate = true;
            globeEl.current.controls().autoRotateSpeed = 0.5;
            globeEl.current.controls().enableZoom = false;
        }

        return () => window.removeEventListener('resize', updateDimensions);
    }, []);

    const arcsData = useMemo(() => [
        {
            startLat: 7.8731, // Sri Lanka
            startLng: 80.7718,
            endLat: 13.0827, // Chennai
            endLng: 80.2707,
            color: ['rgba(242, 110, 52, 0.2)', 'rgba(242, 110, 52, 1)']
        },
        {
            startLat: 7.8731, // Sri Lanka
            startLng: 80.7718,
            endLat: 12.9716, // Bangalore
            endLng: 77.5946,
            color: ['rgba(242, 110, 52, 0.2)', 'rgba(242, 110, 52, 0.8)']
        },
        {
            startLat: 7.8731, // Sri Lanka
            startLng: 80.7718,
            endLat: 9.9252, // Madurai
            endLng: 78.1198,
            color: ['rgba(242, 110, 52, 0.2)', 'rgba(242, 110, 52, 0.9)']
        }
    ], []);

    const ringsData = useMemo(() => [
        { lat: 7.8731, lng: 80.7718, name: 'Sri Lanka', radius: 1.5 },
        { lat: 13.0827, lng: 80.2707, name: 'Chennai', radius: 1 },
        { lat: 12.9716, lng: 77.5946, name: 'Bangalore', radius: 0.8 },
    ], []);

    return (
        <div ref={containerRef} className="w-full aspect-square max-w-[550px] mx-auto flex items-center justify-center relative cursor-move">
            {/* Soft backdrop glow */}
            <div className="absolute inset-0 bg-[#1E3A5F]/20 blur-[100px] rounded-full w-full h-full transform scale-90"></div>
            
            <Globe
                ref={globeEl}
                width={dimensions.width}
                height={dimensions.height}
                backgroundColor="rgba(0,0,0,0)"
                showGlobe={false}
                showAtmosphere={true}
                atmosphereColor="#F26E34"
                atmosphereAltitude={0.15}
                
                // Hex Polygons for Minimalist aesthetic
                hexPolygonsData={countries}
                hexPolygonResolution={3}
                hexPolygonMargin={0.6}
                hexPolygonColor={() => '#1E3A5F'} // SLFA Navy dots
                
                // Arcs
                arcsData={arcsData}
                arcColor="color"
                arcDashLength={0.4}
                arcDashGap={0.2}
                arcDashInitialGap={() => Math.random()}
                arcDashAnimateTime={3000}
                arcStroke={1.2}
                
                // Rings for cities
                ringsData={ringsData}
                ringColor={() => '#F26E34'} // SLFA Coral rings
                ringMaxRadius="radius"
                ringPropagationSpeed={1}
                ringRepeatPeriod={1200}
            />

        </div>
    );
}

