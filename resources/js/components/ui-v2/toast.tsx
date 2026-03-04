'use client';

/**
 * Toast feedback: re-export Sonner for ui-v2. App root already renders <Toaster />.
 * Use toast.success(), toast.error(), etc. from this module for design-system consistency.
 */
export { Toaster, toast } from 'sonner';
