/**
 * BottomNavigation Component - Mobile-first tab navigation
 * Sticky bottom bar with large touch targets (56px+ min-height)
 * Used for main navigation on mobile devices
 */

import { Link } from '@inertiajs/react';
import { ReactNode } from 'react';

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
        <nav className="fixed right-0 bottom-0 left-0 z-40 border-t border-gray-200 bg-white">
            <div className="mx-auto max-w-screen-xl px-0">
                <div className="flex items-center justify-around">
                    {items.map((item) => {
                        const isActive = activeId === item.id || item.active;

                        const navItemContent = (
                            <div
                                className={`relative flex min-h-[56px] flex-1 cursor-pointer flex-col items-center justify-center gap-1 transition-colors duration-200 ${
                                    isActive
                                        ? 'font-semibold text-blue-600'
                                        : 'text-gray-600 hover:text-gray-800'
                                } `}
                                onClick={() => !item.href && handleClick(item)}
                            >
                                <div className="flex h-6 w-6 items-center justify-center">
                                    {item.icon}
                                </div>
                                <span className="max-w-[60px] truncate text-xs font-medium">
                                    {item.label}
                                </span>

                                {/* Badge */}
                                {item.badge !== undefined && item.badge > 0 && (
                                    <span className="absolute top-1 right-2 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-xs font-bold text-white">
                                        {item.badge > 9 ? '9+' : item.badge}
                                    </span>
                                )}

                                {/* Active indicator */}
                                {isActive && (
                                    <div className="absolute right-0 bottom-0 left-0 h-1 rounded-t bg-blue-600" />
                                )}
                            </div>
                        );

                        if (item.href) {
                            return (
                                <Link
                                    key={item.id}
                                    href={item.href}
                                    className="flex flex-1 items-center justify-center"
                                    preserveScroll
                                >
                                    {navItemContent}
                                </Link>
                            );
                        }

                        return (
                            <div
                                key={item.id}
                                className="flex flex-1 items-center justify-center"
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
