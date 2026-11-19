import { useEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { ChevronLeft, ChevronRight, Pause, Play, X } from 'lucide-react';

export default function MediaSlideshow({ items, initialIndex, onClose, title = 'Gallery', srcForItem }) {
    const dialogRef = useRef(null);
    const touchStartRef = useRef(null);
    const thumbnailRefs = useRef([]);
    const [index, setIndex] = useState(initialIndex);
    const [playing, setPlaying] = useState(false);
    const item = items[index];

    useEffect(() => {
        const dialog = dialogRef.current;
        dialog?.showModal();
        return () => dialog?.close();
    }, []);

    useEffect(() => {
        if (!playing || items.length < 2 || item?.type === 'video') return;
        const timer = window.setTimeout(() => setIndex(current => (current + 1) % items.length), 5000);
        return () => window.clearTimeout(timer);
    }, [playing, index, item?.type, items.length]);

    useEffect(() => {
        thumbnailRefs.current[index]?.scrollIntoView({ block: 'nearest', inline: 'nearest' });
    }, [index]);

    if (!item) return null;

    const go = offset => setIndex(current => (current + offset + items.length) % items.length);
    const close = () => dialogRef.current?.close();
    const onKeyDown = event => {
        if (event.key === 'ArrowRight') { event.preventDefault(); go(1); }
        if (event.key === 'ArrowLeft') { event.preventDefault(); go(-1); }
    };

    return createPortal(
        <dialog
            ref={dialogRef}
            onClose={onClose}
            onKeyDown={onKeyDown}
            onClick={event => { if (event.target === dialogRef.current) close(); }}
            onTouchStart={event => { touchStartRef.current = event.touches[0]?.clientX ?? null; }}
            onTouchEnd={event => {
                const end = event.changedTouches[0]?.clientX;
                if (touchStartRef.current !== null && end !== undefined && Math.abs(end - touchStartRef.current) > 60) go(end < touchStartRef.current ? 1 : -1);
                touchStartRef.current = null;
            }}
            aria-label={`${title} slideshow`}
            className="fixed inset-0 m-0 h-dvh max-h-none w-screen max-w-none overflow-hidden border-0 bg-[#080c16] p-0 text-white backdrop:bg-black/90"
        >
            <div className="flex h-full flex-col">
                <header className="flex min-h-16 shrink-0 items-center justify-between gap-4 border-b border-white/15 px-4 sm:px-8">
                    <div className="min-w-0"><p className="truncate font-abhaya text-lg font-bold sm:text-xl">{title}</p><p className="text-xs text-white/65" aria-live="polite">{index + 1} of {items.length}</p></div>
                    <div className="flex items-center gap-2">
                        {items.length > 1 && <button type="button" onClick={() => setPlaying(value => !value)} aria-label={playing ? 'Pause slideshow' : 'Play slideshow'} className="rounded-full p-3 text-white hover:bg-white/15 focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#D4AF37]">{playing ? <Pause size={21} /> : <Play size={21} />}</button>}
                        <button type="button" onClick={close} aria-label="Close slideshow" className="rounded-full p-3 text-white hover:bg-white/15 focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#D4AF37]"><X size={24} /></button>
                    </div>
                </header>

                <div className="relative flex min-h-0 flex-1 items-center justify-center px-14 py-4 sm:px-20">
                    {items.length > 1 && <button type="button" onClick={() => go(-1)} aria-label="Previous item" className="absolute left-2 z-10 rounded-full bg-black/50 p-3 text-white hover:bg-[#D4AF37] hover:text-black focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#D4AF37] sm:left-6"><ChevronLeft size={25} /></button>}
                    {item.type === 'video'
                        ? <video key={item.id} src={srcForItem(item)} controls autoPlay className="max-h-full max-w-full object-contain" />
                        : <img key={item.id} src={srcForItem(item)} alt={item.caption || `${title}, image ${index + 1}`} className="max-h-full max-w-full object-contain" />}
                    {items.length > 1 && <button type="button" onClick={() => go(1)} aria-label="Next item" className="absolute right-2 z-10 rounded-full bg-black/50 p-3 text-white hover:bg-[#D4AF37] hover:text-black focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#D4AF37] sm:right-6"><ChevronRight size={25} /></button>}
                </div>

                {(item.caption || items.length > 1) && <footer className="shrink-0 border-t border-white/15 px-4 py-3 sm:px-8">
                    {item.caption && <p className="mb-3 text-center text-sm text-white/90">{item.caption}</p>}
                    {items.length > 1 && <div className="overflow-x-auto pb-1" aria-label="Choose item"><div className="mx-auto flex w-max min-w-full justify-center gap-2">{items.map((thumbnail, position) => <button key={thumbnail.id} ref={element => { thumbnailRefs.current[position] = element; }} type="button" onClick={() => setIndex(position)} aria-label={`View ${thumbnail.caption || `item ${position + 1}`}`} aria-current={position === index ? 'true' : undefined} className={`h-12 w-12 shrink-0 overflow-hidden border-2 sm:h-14 sm:w-14 ${position === index ? 'border-[#D4AF37]' : 'border-transparent opacity-60 hover:opacity-100'} focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#D4AF37]`}>{thumbnail.type === 'video' ? <span className="flex h-full items-center justify-center bg-gray-800"><Play size={17} /></span> : <img src={srcForItem(thumbnail)} alt="" loading="lazy" className="h-full w-full object-cover" />}</button>)}</div></div>}
                </footer>}
            </div>
        </dialog>,
        document.body,
    );
}
