import React from 'react';

export const Card = ({ children, className = '', ...props }) => {
    return (
        <div 
            className={`bg-[#181a20] border border-[#2b3139] rounded-xl text-[#eaecef] ${className}`}
            {...props}
        >
            {children}
        </div>
    );
};

export const CardHeader = ({ children, className = '', ...props }) => {
    return (
        <div 
            className={`px-6 py-4 border-b border-[#2b3139] ${className}`}
            {...props}
        >
            {children}
        </div>
    );
};

export const CardTitle = ({ children, className = '', ...props }) => {
    return (
        <h3 
            className={`text-lg font-semibold text-[#eaecef] ${className}`}
            {...props}
        >
            {children}
        </h3>
    );
};

export const CardContent = ({ children, className = '', ...props }) => {
    return (
        <div 
            className={`p-6 ${className}`}
            {...props}
        >
            {children}
        </div>
    );
};
