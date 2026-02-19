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
  const [focused, setFocused] = useState(false);

  const handleDigit = (digit: string) => {
    let newValue = value + digit;

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
    <div className="flex flex-col gap-3 w-full">
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
        className="w-full text-3xl font-bold text-right p-4 border-b-2 border-blue-500 bg-gray-50 outline-none"
      />

      {/* Numpad Grid */}
      <div className="grid grid-cols-3 gap-2">
        {buttons.map((row, rowIdx) => (
          <div key={rowIdx} className="contents">
            {row.map((btn) => {
              if (btn === '') {
                return <div key={`${rowIdx}-empty`} />;
              }

              if (btn === 'DEL') {
                return (
                  <button
                    key={btn}
                    onClick={handleBackspace}
                    className="h-14 rounded-lg bg-red-500 text-white font-bold text-xl hover:bg-red-600 active:bg-red-700 transition-colors"
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
                    className="h-14 rounded-lg bg-gray-300 text-gray-600 font-bold text-xl hover:bg-gray-400 disabled:opacity-50 active:bg-gray-500 transition-colors"
                  >
                    .
                  </button>
                );
              }

              return (
                <button
                  key={btn}
                  onClick={() => handleDigit(btn)}
                  className="h-14 rounded-lg bg-blue-500 text-white font-bold text-xl hover:bg-blue-600 active:bg-blue-700 transition-colors"
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
          className="py-3 rounded-lg bg-gray-400 text-white font-bold hover:bg-gray-500 active:bg-gray-600 transition-colors"
        >
          Clear
        </button>
        {onSubmit && (
          <button
            onClick={onSubmit}
            disabled={!value}
            className="py-3 rounded-lg bg-green-500 text-white font-bold hover:bg-green-600 disabled:opacity-50 active:bg-green-700 transition-colors"
          >
            Confirm
          </button>
        )}
      </div>
    </div>
  );
}

export default NumPad;
