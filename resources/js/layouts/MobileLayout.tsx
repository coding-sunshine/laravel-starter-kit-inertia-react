/**
 * MobileLayout - Responsive mobile-first layout wrapper
 * Handles bottom navigation spacing and touch-friendly layout
 */

import { ReactNode } from 'react';

interface MobileLayoutProps {
  children: ReactNode;
  noPadding?: boolean;
  showBottomSpacer?: boolean;
}

export function MobileLayout({
  children,
  noPadding = false,
  showBottomSpacer = true,
}: MobileLayoutProps) {
  return (
    <div className="flex flex-col min-h-screen bg-white">
      {/* Main content */}
      <main
        className={`flex-1 ${
          noPadding
            ? 'w-full'
            : 'w-full px-3 py-4 sm:px-4 sm:py-6 md:px-6 md:p-6'
        }`}
      >
        {children}
      </main>

      {/* Bottom spacer for fixed bottom navigation (56px min-height) */}
      {showBottomSpacer && <div className="h-20 sm:h-16" />}
    </div>
  );
}

/**
 * Container for full-width mobile content (e.g., grids, maps)
 */
export function MobileFullWidthContainer({
  children,
}: {
  children: ReactNode;
}) {
  return (
    <div className="w-full bg-white">
      {children}
    </div>
  );
}

/**
 * Safe area padding for notched devices
 */
export function MobileSafeArea({
  children,
  top = true,
  bottom = true,
}: {
  children: ReactNode;
  top?: boolean;
  bottom?: boolean;
}) {
  return (
    <div
      className={`w-full ${
        top ? 'pt-safe-top' : ''
      } ${
        bottom ? 'pb-safe-bottom' : ''
      }`}
    >
      {children}
    </div>
  );
}

/**
 * Form group with proper touch-friendly spacing
 */
export function MobileFormGroup({
  children,
  label,
  error,
}: {
  children: ReactNode;
  label?: string;
  error?: string;
}) {
  return (
    <div className="mb-6">
      {label && (
        <label className="block text-sm font-semibold text-gray-700 mb-2">
          {label}
        </label>
      )}
      {children}
      {error && (
        <p className="mt-1 text-sm text-red-600">{error}</p>
      )}
    </div>
  );
}

/**
 * Card wrapper for content grouping
 */
export function MobileCard({
  children,
  header,
  footer,
}: {
  children: ReactNode;
  header?: ReactNode;
  footer?: ReactNode;
}) {
  return (
    <div className="bg-white border border-gray-200 rounded-lg overflow-hidden mb-4">
      {header && (
        <div className="px-4 py-3 border-b border-gray-200 bg-gray-50">
          {header}
        </div>
      )}
      <div className="px-4 py-4">
        {children}
      </div>
      {footer && (
        <div className="px-4 py-3 border-t border-gray-200 bg-gray-50">
          {footer}
        </div>
      )}
    </div>
  );
}

/**
 * Action button group (full-width on mobile)
 */
export function MobileActionGroup({
  children,
  layout = 'stacked',
}: {
  children: ReactNode;
  layout?: 'stacked' | 'side-by-side';
}) {
  return (
    <div
      className={`flex gap-2 ${
        layout === 'stacked'
          ? 'flex-col'
          : 'flex-col sm:flex-row'
      }`}
    >
      {children}
    </div>
  );
}

/**
 * Touch-friendly list item
 */
export function MobileListItem({
  children,
  onPress,
  icon,
  rightElement,
  badge,
}: {
  children: ReactNode;
  onPress?: () => void;
  icon?: ReactNode;
  rightElement?: ReactNode;
  badge?: number;
}) {
  return (
    <button
      onClick={onPress}
      className="w-full flex items-center gap-3 px-4 py-3 min-h-[56px] bg-white border-b border-gray-100 hover:bg-gray-50 active:bg-gray-100 transition-colors text-left disabled:opacity-50"
      disabled={!onPress}
    >
      {icon && (
        <div className="flex-shrink-0 w-6 h-6 flex items-center justify-center">
          {icon}
        </div>
      )}
      <div className="flex-1">
        {children}
      </div>
      {badge !== undefined && badge > 0 && (
        <span className="inline-flex items-center justify-center w-6 h-6 bg-red-500 text-white text-xs font-bold rounded-full flex-shrink-0">
          {badge > 9 ? '9+' : badge}
        </span>
      )}
      {rightElement && (
        <div className="flex-shrink-0">
          {rightElement}
        </div>
      )}
    </button>
  );
}

export default MobileLayout;
