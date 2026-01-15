import React from 'react';

export const Modal = ({ show, onClose, title, children }) => {
    if (!show) return null;

    return (
        <div className="fixed inset-0 z-50 overflow-y-auto">
            <div className="flex min-h-full items-center justify-center p-4">
                <div className="fixed inset-0 bg-black/50 transition-opacity" onClick={onClose}></div>
                <div className="relative bg-[#1e2329] rounded-lg shadow-xl max-w-md w-full border border-[#2b3139]">
                    {title && (
                        <div className="flex items-center justify-between p-4 border-b border-[#2b3139]">
                            <h3 className="text-lg font-semibold text-[#eaecef]">{title}</h3>
                            <button
                                onClick={onClose}
                                className="text-[#848e9c] hover:text-[#eaecef]"
                            >
                                <i className="las la-times text-xl"></i>
                            </button>
                        </div>
                    )}
                    <div className="p-4">
                        {children}
                    </div>
                </div>
            </div>
        </div>
    );
};
