import React from 'react';

export const Alert = ({ type = 'info', children, className = '' }) => {
    const types = {
        info: 'bg-[#3b82f6]/10 border-[#3b82f6]/50 text-[#93c5fd]',
        success: 'bg-[#0ecb81]/10 border-[#0ecb81]/50 text-[#6ee7b7]',
        warning: 'bg-[#f59e0b]/10 border-[#f59e0b]/50 text-[#fcd34d]',
        error: 'bg-[#f6465d]/10 border-[#f6465d]/50 text-[#fca5a5]',
    };

    return (
        <div className={`p-4 rounded-lg border ${types[type]} ${className}`}>
            {children}
        </div>
    );
};
