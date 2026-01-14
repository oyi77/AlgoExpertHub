import React, { forwardRef } from 'react';

export const Input = forwardRef(({ className = '', ...props }, ref) => {
    return (
        <input
            className={`flex h-10 w-full rounded-lg border border-[#2b3139] bg-[#0b0e11] px-3 py-2 text-sm text-[#eaecef] ring-offset-[#0b0e11] file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-[#848e9c] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0ecb81] focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 ${className}`}
            ref={ref}
            {...props}
        />
    );
});

Input.displayName = "Input";
