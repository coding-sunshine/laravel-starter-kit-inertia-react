/**
 * NumPad Component - Touch-friendly numeric input
 * Optimized for mobile with large buttons (56px+)
 */

import { useState } from 'react';

interface NumPadProps {
    value: string;
    onChange: (value: string) => void;
    onSubmit?: () => void;
    maxValue?: number;
    decimal?: boolean;
    placeholder?: string;
}

export function NumPad({
    value,
    onChange,
    onSubmit,
    maxValue,
    decimal = false,
    placeholder = '0',
}: NumPadProps) {
    const [, setFocused] = useState(false);

    const handleDigit = (digit: string) => {
        const newValue = value + digit;

        // Limit decimal places
        if (decimal && newValue.includes('.')) {
            const parts = newValue.split('.');
            if (parts[1].length > 2) return;
        }

        // Limit max value
        if (maxValue && parseFloat(newValue) > maxValue) {
            return;
        }

        onChange(newValue);
    };

    const handleDecimal = () => {
        if (!decimal || value.includes('.')) return;
        onChange(value ? value + '.' : '0.');
    };

    const handleBackspace = () => {
        onChange(value.slice(0, -1));
    };

    const handleClear = () => {
        onChange('');
    };

    const buttons = [
        ['1', '2', '3'],
        ['4', '5', '6'],
        ['7', '8', '9'],
        [decimal ? '.' : '', '0', 'DEL'],
    ];

    return (
        <div className="flex w-full flex-col gap-3">
            {/* Display */}
            <input
                type="text"
                value={value}
                onChange={(e) => {
                    const v = e.target.value;
                    if (v === '' || !isNaN(parseFloat(v))) {
                        onChange(v);
                    }
                }}
                onFocus={() => setFocused(true)}
                onBlur={() => setFocused(false)}
                placeholder={placeholder}
                className="w-full border-b-2 border-blue-500 bg-gray-50 p-4 text-right text-3xl font-bold outline-none"
            />

            {/* Numpad Grid */}
            <div className="grid grid-cols-3 gap-2">
                {buttons.map((row) => (
                    <div key={row.join('-')} className="contents">
                        {row.map((btn) => {
                            if (btn === '') {
                                return <div key="empty" />;
                            }

                            if (btn === 'DEL') {
                                return (
                                    <button
                                        key={btn}
                                        onClick={handleBackspace}
                                        className="h-14 rounded-lg bg-red-500 text-xl font-bold text-white transition-colors hover:bg-red-600 active:bg-red-700"
                                    >
                                        ←
                                    </button>
                                );
                            }

                            if (btn === '.') {
                                return (
                                    <button
                                        key={btn}
                                        onClick={handleDecimal}
                                        disabled={value.includes('.')}
                                        className="h-14 rounded-lg bg-gray-300 text-xl font-bold text-gray-600 transition-colors hover:bg-gray-400 active:bg-gray-500 disabled:opacity-50"
                                    >
                                        .
                                    </button>
                                );
                            }

                            return (
                                <button
                                    key={btn}
                                    onClick={() => handleDigit(btn)}
                                    className="h-14 rounded-lg bg-blue-500 text-xl font-bold text-white transition-colors hover:bg-blue-600 active:bg-blue-700"
                                >
                                    {btn}
                                </button>
                            );
                        })}
                    </div>
                ))}
            </div>

            {/* Action Buttons */}
            <div className="grid grid-cols-2 gap-2">
                <button
                    onClick={handleClear}
                    className="rounded-lg bg-gray-400 py-3 font-bold text-white transition-colors hover:bg-gray-500 active:bg-gray-600"
                >
                    Clear
                </button>
                {onSubmit && (
                    <button
                        onClick={onSubmit}
                        disabled={!value}
                        className="rounded-lg bg-green-500 py-3 font-bold text-white transition-colors hover:bg-green-600 active:bg-green-700 disabled:opacity-50"
                    >
                        Confirm
                    </button>
                )}
            </div>
        </div>
    );
}

export default NumPad;
