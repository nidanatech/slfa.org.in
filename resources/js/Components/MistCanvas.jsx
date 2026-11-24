import React, { useEffect, useRef } from 'react';
import { useReducedMotion } from 'motion/react';

export default function MistCanvas() {
    const canvasRef = useRef(null);
    const pointerRef = useRef({ x: -9999, y: -9999, active: false });
    const reducedMotion = useReducedMotion();

    useEffect(() => {
        const canvas = canvasRef.current;
        if (!canvas) return undefined;

        const parent = canvas.parentElement;
        const ctx = canvas.getContext('2d');
        let rafId = null;
        let width = 0;
        let height = 0;

        const particles = Array.from({ length: 42 }, () => ({
            x: Math.random(),
            y: Math.random(),
            r: 34 + Math.random() * 68,
            vx: 0.00018 + Math.random() * 0.00032,
            vy: (Math.random() - 0.5) * 0.00012,
            alpha: 0.14 + Math.random() * 0.2,
            ox: 0,
            oy: 0,
        }));

        const resize = () => {
            const rect = parent.getBoundingClientRect();
            const dpr = Math.min(window.devicePixelRatio || 1, 2);
            width = rect.width;
            height = rect.height;
            canvas.width = Math.max(1, Math.floor(width * dpr));
            canvas.height = Math.max(1, Math.floor(height * dpr));
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        };

        const onMove = (e) => {
            const rect = canvas.getBoundingClientRect();
            pointerRef.current = {
                x: e.clientX - rect.left,
                y: e.clientY - rect.top,
                active: true,
            };
        };

        const onLeave = () => {
            pointerRef.current.active = false;
        };

        const draw = () => {
            ctx.clearRect(0, 0, width, height);
            const ptr = pointerRef.current;

            for (const p of particles) {
                if (!reducedMotion) {
                    p.x += p.vx;
                    p.y += p.vy;
                    if (p.x > 1.18) p.x = -0.18;
                    if (p.y < -0.15) p.y = 1.15;
                    if (p.y > 1.15) p.y = -0.15;
                }

                const px = p.x * width;
                const py = p.y * height;

                if (ptr.active && !reducedMotion) {
                    const dx = px + p.ox - ptr.x;
                    const dy = py + p.oy - ptr.y;
                    const dist = Math.hypot(dx, dy);
                    const radius = 120;
                    if (dist < radius && dist > 0.01) {
                        const force = (1 - dist / radius) * 38;
                        p.ox += ((dx / dist) * force - p.ox) * 0.18;
                        p.oy += ((dy / dist) * force - p.oy) * 0.18;
                    } else {
                        p.ox *= 0.94;
                        p.oy *= 0.94;
                    }
                } else {
                    p.ox *= 0.94;
                    p.oy *= 0.94;
                }

                const cx = px + p.ox;
                const cy = py + p.oy;
                const grad = ctx.createRadialGradient(cx, cy, 0, cx, cy, p.r);
                grad.addColorStop(0, `rgba(255, 253, 249, ${p.alpha})`);
                grad.addColorStop(0.55, `rgba(255, 253, 249, ${p.alpha * 0.45})`);
                grad.addColorStop(1, 'rgba(255, 253, 249, 0)');

                ctx.fillStyle = grad;
                ctx.beginPath();
                ctx.arc(cx, cy, p.r, 0, Math.PI * 2);
                ctx.fill();
            }

            if (!reducedMotion) {
                rafId = requestAnimationFrame(draw);
            }
        };

        resize();
        draw();

        window.addEventListener('resize', resize, { passive: true });
        parent.addEventListener('pointermove', onMove, { passive: true });
        parent.addEventListener('pointerleave', onLeave);

        return () => {
            window.removeEventListener('resize', resize);
            parent.removeEventListener('pointermove', onMove);
            parent.removeEventListener('pointerleave', onLeave);
            if (rafId) cancelAnimationFrame(rafId);
        };
    }, [reducedMotion]);

    return <canvas ref={canvasRef} className="mist-canvas" aria-hidden="true" />;
}

