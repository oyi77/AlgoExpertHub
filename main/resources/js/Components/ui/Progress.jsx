import React from 'react';

export const Progress = ({ value = 0, max = 100, className = '' }) => {
    const percentage = Math.min(100, Math.max(0, (value / max) * 100));

    return (
        <div className={`w-full bg-[#0b0e11] rounded-full h-2 overflow-hidden ${className}`}>
            <div
                className="bg-[#0ecb81] h-full rounded-full transition-all duration-300"
                style={{ width: `${percentage}%` }}
            ></div>
        </div>
    );
};
