export function coverImageUrl(path) {
    if (!path) return null;
    if (path.startsWith('/images/')) return path;
    return `/storage/${path.replace(/^\/+/, '')}`;
}
