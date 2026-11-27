import React, { useState } from 'react';
import { Link } from '@inertiajs/react';
import { ArrowRight, ChevronLeft, ChevronRight } from 'lucide-react';
import { motion, useReducedMotion } from 'motion/react';
import { coverImageUrl } from '@/lib/coverImageUrl';

const dateLabel = (value) =>
    new Intl.DateTimeFormat('en-IN', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        timeZone: 'Asia/Kolkata',
    }).format(new Date(value));

export default function StoriesCarousel({ items = [] }) {
    const [activeIndex, setActiveIndex] = useState(0);
    const reducedMotion = useReducedMotion();

    if (!items?.length) {
        return <p>Stories from the community will appear here as they are published.</p>;
    }

    const total = items.length;
    const prev = () => setActiveIndex((i) => (i - 1 + total) % total);
    const next = () => setActiveIndex((i) => (i + 1) % total);

    const getOffset = (index) => {
        let diff = index - activeIndex;
        if (diff > total / 2) diff -= total;
        if (diff < -total / 2) diff += total;
        return diff;
    };

    return (
        <div className="stories-circular-carousel" role="region" aria-roledescription="carousel" aria-label="Stories behind the moments">
            <div className="stories-carousel-stage">
                {items.map((post, index) => {
                    const offset = getOffset(index);
                    const absOffset = Math.abs(offset);
                    const isActive = offset === 0;
                    const isVisible = absOffset <= 2;

                    if (!isVisible) return null;

                    return (
                        <motion.article
                            key={post.id}
                            className={`stories-carousel-card ${isActive ? 'is-active' : ''}`}
                            initial={false}
                            animate={{
                                x: `${offset * 62}%`,
                                scale: isActive ? 1 : absOffset === 1 ? 0.84 : 0.7,
                                rotate: reducedMotion ? 0 : offset * 5,
                                y: absOffset * 18,
                                opacity: isActive ? 1 : absOffset === 1 ? 0.72 : 0.35,
                                zIndex: 10 - absOffset,
                            }}
                            transition={
                                reducedMotion
                                    ? { duration: 0 }
                                    : { type: 'spring', stiffness: 260, damping: 26 }
                            }
                            onClick={!isActive ? () => setActiveIndex(index) : undefined}
                        >
                            {isActive ? (
                                <Link href={route('news.show', post.public_slug)} className="stories-carousel-link">
                                    <div className="brand-photo-frame">
                                        {post.cover_image_path ? (
                                            <div className="cream-photo">
                                                <img src={coverImageUrl(post.cover_image_path)} alt="" loading="lazy" />
                                            </div>
                                        ) : (
                                            <div className="cream-photo cream-photo-placeholder" aria-hidden="true">SLFA</div>
                                        )}
                                    </div>
                                    <div className="stories-carousel-copy">
                                        <time dateTime={post.published_at}>{dateLabel(post.published_at)}</time>
                                        <h3>{post.title}</h3>
                                        <span className="cream-link">
                                            Read story <ArrowRight size={15} />
                                        </span>
                                    </div>
                                </Link>
                            ) : (
                                <button
                                    type="button"
                                    className="stories-carousel-preview"
                                    onClick={() => setActiveIndex(index)}
                                    aria-label={`View story: ${post.title}`}
                                >
                                    <div className="brand-photo-frame">
                                        {post.cover_image_path ? (
                                            <div className="cream-photo">
                                                <img src={coverImageUrl(post.cover_image_path)} alt="" loading="lazy" />
                                            </div>
                                        ) : (
                                            <div className="cream-photo cream-photo-placeholder" aria-hidden="true">SLFA</div>
                                        )}
                                    </div>
                                    <div className="stories-carousel-copy">
                                        <time dateTime={post.published_at}>{dateLabel(post.published_at)}</time>
                                        <h3>{post.title}</h3>
                                    </div>
                                </button>
                            )}
                        </motion.article>
                    );
                })}
            </div>

            {total > 1 && (
                <div className="stories-carousel-controls">
                    <button type="button" onClick={prev} aria-label="Previous story">
                        <ChevronLeft size={18} />
                    </button>
                    <div className="stories-carousel-dots" role="tablist" aria-label="Choose a story">
                        {items.map((post, index) => (
                            <button
                                key={post.id}
                                type="button"
                                role="tab"
                                aria-selected={index === activeIndex}
                                aria-label={`Story ${index + 1}: ${post.title}`}
                                className={index === activeIndex ? 'is-active' : ''}
                                onClick={() => setActiveIndex(index)}
                            />
                        ))}
                    </div>
                    <button type="button" onClick={next} aria-label="Next story">
                        <ChevronRight size={18} />
                    </button>
                </div>
            )}
        </div>
    );
}

