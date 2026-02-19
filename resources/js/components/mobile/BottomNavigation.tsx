/**
 * BottomNavigation Component - Mobile-first tab navigation
 * Sticky bottom bar with large touch targets (56px+ min-height)
 * Used for main navigation on mobile devices
 */

import { ReactNode } from 'react';
import { Link } from '@inertiajs/react';

export interface NavItem {
  id: string;
  label: string;
  icon: ReactNode;
  href?: string;
  badge?: number;
  onClick?: () => void;
  active?: boolean;
}

interface BottomNavigationProps {
  items: NavItem[];
  activeId?: string;
  onItemClick?: (id: string) => void;
}

export function BottomNavigation({
  items,
  activeId,
  onItemClick,
}: BottomNavigationProps) {
  const handleClick = (item: NavItem) => {
    if (item.onClick) {
      item.onClick();
    }
    if (onItemClick) {
      onItemClick(item.id);
    }
  };

  return (
    <nav className="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 z-40">
      <div className="max-w-screen-xl mx-auto px-0">
        <div className="flex justify-around items-center">
          {items.map((item) => {
            const isActive =
              activeId === item.id || item.active;

            const navItemContent = (
              <div
                className={`
                  flex flex-col items-center justify-center gap-1
                  min-h-[56px] flex-1
                  transition-colors duration-200
                  cursor-pointer
                  relative
                  ${
                    isActive
                      ? 'text-blue-600 font-semibold'
                      : 'text-gray-600 hover:text-gray-800'
                  }
                `}
                onClick={() => !item.href && handleClick(item)}
              >
                <div className="w-6 h-6 flex items-center justify-center">
                  {item.icon}
                </div>
                <span className="text-xs font-medium truncate max-w-[60px]">
                  {item.label}
                </span>

                {/* Badge */}
                {item.badge !== undefined && item.badge > 0 && (
                  <span className="absolute top-1 right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold">
                    {item.badge > 9 ? '9+' : item.badge}
                  </span>
                )}

                {/* Active indicator */}
                {isActive && (
                  <div className="absolute bottom-0 left-0 right-0 h-1 bg-blue-600 rounded-t" />
                )}
              </div>
            );

            if (item.href) {
              return (
                <Link
                  key={item.id}
                  href={item.href}
                  className="flex-1 flex items-center justify-center"
                  preserveScroll
                >
                  {navItemContent}
                </Link>
              );
            }

            return (
              <div
                key={item.id}
                className="flex-1 flex items-center justify-center"
              >
                {navItemContent}
              </div>
            );
          })}
        </div>
      </div>
    </nav>
  );
}

export default BottomNavigation;
