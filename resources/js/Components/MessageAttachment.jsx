import { useState } from 'react';
import { ImageIcon, FileIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

export default function MessageAttachment({ attachment, direction }) {
    const [imageError, setImageError] = useState(false);
    const [imageLoaded, setImageLoaded] = useState(false);

    if (!attachment || !attachment.url) {
        return null;
    }

    const isImage = attachment.type === 'image' || attachment.sticker_id;

    if (!isImage) {
        // For non-image attachments, show a simple file indicator
        return (
            <div className={cn(
                "flex items-center gap-2 p-2 rounded-lg border",
                direction === 'outbound'
                    ? "bg-primary-foreground/10 border-primary-foreground/20"
                    : "bg-muted border-border"
            )}>
                <FileIcon className="h-4 w-4" />
                <span className="text-xs">Attachment</span>
            </div>
        );
    }

    return (
        <div className="relative max-w-[280px] rounded-lg overflow-hidden">
            {!imageLoaded && !imageError && (
                <div className={cn(
                    "flex items-center justify-center h-32 rounded-lg",
                    direction === 'outbound'
                        ? "bg-primary-foreground/10"
                        : "bg-muted"
                )}>
                    <ImageIcon className="h-8 w-8 text-muted-foreground animate-pulse" />
                </div>
            )}

            {imageError ? (
                <div className={cn(
                    "flex items-center justify-center h-32 rounded-lg border",
                    direction === 'outbound'
                        ? "bg-primary-foreground/10 border-primary-foreground/20"
                        : "bg-muted border-border"
                )}>
                    <div className="text-center p-4">
                        <ImageIcon className="h-6 w-6 text-muted-foreground mx-auto mb-2" />
                        <p className="text-xs text-muted-foreground">Failed to load image</p>
                    </div>
                </div>
            ) : (
                <img
                    src={attachment.url}
                    alt={attachment.sticker_id ? "Sticker" : "Image"}
                    className={cn(
                        "rounded-lg max-w-full h-auto cursor-pointer hover:opacity-90 transition-opacity",
                        !imageLoaded && "hidden"
                    )}
                    onLoad={() => setImageLoaded(true)}
                    onError={() => setImageError(true)}
                    onClick={() => window.open(attachment.url, '_blank')}
                    loading="lazy"
                />
            )}
        </div>
    );
}
