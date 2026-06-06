/** Shared mobile breakpoint — must match mixins.scss `md` (768px). */
export const MOBILE_MAX_WIDTH_PX = 768;

export const MOBILE_MEDIA_QUERY = `(max-width: ${MOBILE_MAX_WIDTH_PX}px)` as const;

export const DESKTOP_MIN_WIDTH_PX = MOBILE_MAX_WIDTH_PX;

export const isDesktopWidth = (width: number): boolean => width >= DESKTOP_MIN_WIDTH_PX;
