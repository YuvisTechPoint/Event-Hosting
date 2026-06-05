import {timezones} from "../../data/timezones.ts";

const TIMEZONE_ALIASES: Record<string, string> = {
    "Asia/Calcutta": "Asia/Kolkata",
    "Europe/Kiev": "Europe/Kyiv",
};

export const resolveTimezone = (preferred?: string | null): string => {
    const browserTimezone = typeof window !== "undefined"
        ? Intl.DateTimeFormat().resolvedOptions().timeZone
        : undefined;

    const candidates = [preferred, browserTimezone].filter(Boolean) as string[];

    for (const timezone of candidates) {
        const normalized = TIMEZONE_ALIASES[timezone] ?? timezone;
        if (timezones.includes(normalized)) {
            return normalized;
        }
    }

    return "UTC";
};
