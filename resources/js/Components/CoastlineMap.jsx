import { useId, useRef, useState } from 'react';
import { useInView, useReducedMotion } from 'motion/react';

// Coast contours follow the watercolor asset's 867 × 1300 coordinate system.
const india = 'M30 140 Q34 185 45 210 Q65 250 63 292 Q78 310 77 350 Q78 375 96 390 L115 435 Q134 460 135 480 L155 540 Q159 585 180 625 L207 667 Q221 710 230 745 L249 798 Q255 865 279 892 Q297 918 330 929 Q352 916 368 903 Q377 887 376 875 Q387 854 422 851 L457 846 Q438 833 426 833 Q421 821 437 795 L448 774 Q470 771 485 774 Q497 770 488 721 Q493 700 482 684 Q487 655 508 617 Q526 580 515 555 L510 516 Q500 496 507 470 Q494 441 504 416 Q511 389 545 382 Q548 402 565 389 L579 375 Q582 346 605 351 Q622 349 649 335 Q660 321 650 312 Q648 296 672 286 Q709 273 716 251 Q725 237 747 230 Q755 215 777 208 Q799 192 813 164 L839 133';
const sriLanka = 'M486 821 Q482 808 514 800 Q528 795 541 817 L566 833 Q581 849 585 868 Q607 876 606 899 Q617 883 622 921 Q625 935 641 946 Q657 978 648 1007 Q642 1043 621 1060 Q607 1074 582 1077 L545 1090 Q522 1097 503 1077 Q485 1051 483 1006 Q485 976 479 949 L481 918 Q500 880 486 867 L470 855 Q493 849 496 861 Q513 843 503 832 Z';

export default function CoastlineMap() {
    const ref = useRef(null);
    const lastDrop = useRef(0);
    const maskId = useId().replace(/:/g, '');
    const visible = useInView(ref);
    const reduced = useReducedMotion();
    const [drops, setDrops] = useState([]);
    const ripple = event => {
        if (reduced || event.pointerType !== 'mouse' || performance.now() - lastDrop.current < 220) return;
        lastDrop.current = performance.now();
        const bounds = ref.current.getBoundingClientRect();
        const drop = { id: lastDrop.current, x: (event.clientX - bounds.left) / bounds.width * 867, y: (event.clientY - bounds.top) / bounds.height * 1300 };
        setDrops(items => [...items.slice(-5), drop]);
    };
    return <div ref={ref} className="coastline-map-art" onPointerMove={ripple}>
        <img src="/images/south-india-sri-lanka-light.webp" alt="Watercolour map of South India in green and Sri Lanka in gold" width="867" height="1300" fetchPriority="high" />
        {!reduced && visible && <svg className="coastline-waves" viewBox="0 0 867 1300" aria-hidden="true">
            <defs><mask id={maskId}><rect width="867" height="1300" fill="white" /><path d={`${india} Z`} fill="black" /><path d={sriLanka} fill="black" /></mask></defs>
            <g mask={`url(#${maskId})`} fill="none" stroke="#6e927d">
                {[0, 1, 2].map(index => <g key={index} className="coastline-wave" style={{ animationDelay: `${index * -2}s` }}><path d={india} /><path d={sriLanka} /></g>)}
                {drops.map(drop => <circle key={drop.id} cx={drop.x} cy={drop.y} className="coastline-drop" onAnimationEnd={() => setDrops(items => items.filter(item => item.id !== drop.id))} />)}
            </g>
        </svg>}
    </div>;
}
