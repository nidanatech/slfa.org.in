import { Head, Link } from '@inertiajs/react';
import SLFAWebsiteLayout from '@/Layouts/SLFAWebsiteLayout';
import { Calendar, ArrowLeft } from 'lucide-react';
import dayjs from 'dayjs';

export default function NewsDetail({ auth, post }) {
    return (
        <SLFAWebsiteLayout user={auth.user}>
            <Head title={post.title} />

            <div className="bg-slfa-navy text-white relative py-12 md:py-20">
                {post.cover_image_path && (
                    <>
                        <div className="absolute inset-0 bg-black/60 z-10"></div>
                        <img src={`/storage/${post.cover_image_path}`} alt="" className="absolute inset-0 w-full h-full object-cover z-0 opacity-50 blur-sm" />
                    </>
                )}
                
                <div className="relative z-20 slfa-container animate-fade-up max-w-4xl">
                    <Link href={route('news')} className="text-gray-300 hover:text-white transition flex items-center text-sm font-semibold mb-6">
                        <ArrowLeft className="w-4 h-4 mr-1" /> Back to News
                    </Link>
                    <span className="inline-block bg-slfa-coral text-white text-xs font-bold px-3 py-1 rounded-full mb-4">
                        {post.category || 'General'}
                    </span>
                    <h1 className="text-3xl md:text-5xl font-bold mb-6 leading-tight">{post.title}</h1>
                    
                    <div className="flex items-center text-gray-300 text-sm">
                        <Calendar className="w-4 h-4 mr-2 text-slfa-coral" /> 
                        <span className="font-medium">
                            Published on {post.published_at ? dayjs(post.published_at).format('MMMM D, YYYY') : dayjs(post.created_at).format('MMMM D, YYYY')}
                        </span>
                    </div>
                </div>
            </div>

            <div className="slfa-section bg-slfa-cream min-h-screen">
                <div className="slfa-container max-w-4xl">
                    <div className="bg-white p-8 md:p-12 rounded-xl shadow-sm border border-gray-100 animate-fade-up">
                        <div 
                            className="prose prose-lg max-w-none text-gray-700 whitespace-pre-wrap"
                            dangerouslySetInnerHTML={{ __html: post.body }}
                        />
                    </div>
                </div>
            </div>
        </SLFAWebsiteLayout>
    );
}
