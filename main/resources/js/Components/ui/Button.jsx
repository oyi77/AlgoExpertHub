import React from 'react';

export const Button = ({ 
    children, 
    variant = 'primary', 
    size = 'md', 
    className = '', 
    ...props 
}) => {
    const baseStyles = 'inline-flex items-center justify-center font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-[#0b0e11] disabled:opacity-50 disabled:pointer-events-none rounded-lg';
    
    const variants = {
        primary: 'bg-[#0ecb81] text-[#0b0e11] hover:bg-[#0ecb81]/90 focus:ring-[#0ecb81]',
        secondary: 'bg-[#848e9c]/20 text-[#eaecef] hover:bg-[#848e9c]/30 focus:ring-[#848e9c]',
        danger: 'bg-[#f6465d] text-white hover:bg-[#f6465d]/90 focus:ring-[#f6465d]',
        ghost: 'text-[#848e9c] hover:text-[#eaecef] hover:bg-[#848e9c]/10',
        outline: 'border border-[#848e9c] text-[#eaecef] hover:bg-[#848e9c]/10'
    };

    const sizes = {
        sm: 'h-8 px-3 text-xs',
        md: 'h-10 px-4 text-sm',
        lg: 'h-12 px-6 text-base',
        icon: 'h-10 w-10'
    };

    return (
        <button 
            className={`${baseStyles} ${variants[variant]} ${sizes[size]} ${className}`}
            {...props}
        >
            {children}
        </button>
    );
};
