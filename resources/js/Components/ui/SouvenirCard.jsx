import React from 'react';
import { motion } from 'motion/react';

export default function SouvenirCard({ index, title, children, theme = 'parchment' }) {
    // Theme classes
    const themes = {
        parchment: 'bg-[#fdfbf7] text-[#2c2c2c]',
        darkBlue: 'bg-slfa-navy text-white',
        maroon: 'bg-[#800000] text-white',
    };
    
    // Sticky offset calculation
    const topOffset = `calc(10vh + ${index * 1.5}rem)`;
    
    return (
        <motion.div
            className={`sticky w-full max-w-5xl mx-auto rounded-3xl shadow-[0_-15px_40px_rgba(0,0,0,0.12)] overflow-hidden border border-black/5 ${themes[theme]}`}
            style={{ top: topOffset, marginBottom: '20vh' }}
            initial={{ opacity: 0, y: 50 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true, margin: "-100px" }}
            transition={{ duration: 0.6, ease: "easeOut" }}
        >
            <div className="p-8 md:p-12 lg:p-16 relative">
                {/* Decorative Elements */}
                {theme === 'parchment' && (
                    <>
                        <div className="absolute top-0 left-0 w-full h-full opacity-[0.03] pointer-events-none bg-[url('data:image/svg+xml,%3Csvg width=\\'20\\' height=\\'20\\' viewBox=\\'0 0 20 20\\' xmlns=\\'http://www.w3.org/2000/svg\\'%3E%3Cg fill=\\'%23000000\\' fill-opacity=\\'1\\' fill-rule=\\'evenodd\\'%3E%3Ccircle cx=\\'3\\' cy=\\'3\\' r=\\'1\\'/%3E%3Ccircle cx=\\'13\\' cy=\\'13\\' r=\\'1\\'/%3E%3C/g%3E%3C/svg%3E')]"></div>
                        <div className="absolute top-6 left-6 w-16 h-16 border-t border-l border-[#D4AF37]/60 pointer-events-none rounded-tl-2xl"></div>
                        <div className="absolute bottom-6 right-6 w-16 h-16 border-b border-r border-[#D4AF37]/60 pointer-events-none rounded-br-2xl"></div>
                    </>
                )}
                {theme === 'darkBlue' && (
                    <>
                        <div className="absolute top-0 right-0 w-[500px] h-[500px] bg-slfa-teal/5 rounded-full blur-[100px] opacity-40 pointer-events-none"></div>
                        <div className="absolute bottom-0 left-0 w-[500px] h-[500px] bg-slfa-saffron/5 rounded-full blur-[100px] opacity-40 pointer-events-none"></div>
                    </>
                )}

                <div className="relative z-10">
                    {title && (
                        <div className="mb-10 text-center">
                            <h2 className={`font-abhaya font-extrabold text-4xl md:text-5xl mb-4 ${theme === 'parchment' ? 'text-[#800000]' : 'text-[#D4AF37]'}`}>
                                {title}
                            </h2>
                            <div className="flex items-center justify-center gap-4">
                                <div className={`h-[1px] w-12 ${theme === 'parchment' ? 'bg-[#D4AF37]' : 'bg-white/20'}`}></div>
                                <div className={`w-2 h-2 rotate-45 ${theme === 'parchment' ? 'bg-[#D4AF37]' : 'bg-[#D4AF37]'}`}></div>
                                <div className={`h-[1px] w-12 ${theme === 'parchment' ? 'bg-[#D4AF37]' : 'bg-white/20'}`}></div>
                            </div>
                        </div>
                    )}
                    {children}
                </div>
            </div>
        </motion.div>
    );
}
