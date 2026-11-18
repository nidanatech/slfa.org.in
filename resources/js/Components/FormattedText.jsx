import React from 'react';
import ReactMarkdown from 'react-markdown';

import remarkGfm from 'remark-gfm';

export default function FormattedText({ text }) {
    if (!text) return null;

    return (
        <div className="markdown-content text-sm [&>*:last-child]:mb-0 break-words">
            <ReactMarkdown
                remarkPlugins={[remarkGfm]}
                components={{
                    // Override default elements to fit chat bubble style
                    p: ({ node, ...props }) => <p className="mb-2 last:mb-0 leading-relaxed whitespace-pre-wrap" {...props} />,
                    a: ({ node, ...props }) => <a className="text-blue-500 underline hover:text-blue-600" target="_blank" rel="noopener noreferrer" {...props} />,
                    ul: ({ node, ...props }) => <ul className="list-disc pl-4 mb-2 space-y-1" {...props} />,
                    ol: ({ node, ...props }) => <ol className="list-decimal pl-4 mb-2 space-y-1" {...props} />,
                    li: ({ node, ...props }) => <li className="pl-1" {...props} />,
                    strong: ({ node, ...props }) => <span className="font-bold" {...props} />,
                    blockquote: ({ node, ...props }) => <blockquote className="border-l-4 border-primary/20 pl-2 italic my-2" {...props} />,
                    code: ({ node, inline, className, children, ...props }) => {
                        return inline ? (
                            <code className="bg-muted px-1 py-0.5 rounded text-xs font-mono" {...props}>{children}</code>
                        ) : (
                            <pre className="bg-muted p-2 rounded-md text-xs font-mono overflow-auto my-2" {...props}>
                                <code>{children}</code>
                            </pre>
                        )
                    }
                }}
            >
                {text}
            </ReactMarkdown>
        </div>
    );
}
