export default function SLFAPageCard({ children, className = '', as: Tag = 'div' }) {
    return <Tag className={`rounded-3xl border border-gray-100 bg-white p-6 shadow-[0_12px_40px_rgba(15,23,42,0.04)] sm:p-8 ${className}`}>{children}</Tag>;
}
