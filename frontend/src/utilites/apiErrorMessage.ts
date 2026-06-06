import {AxiosError} from 'axios';
import {t} from '@lingui/macro';

const ENGLISH = {
    backendNotConfigured:
        'The API is not connected. Deploy the Laravel backend and set BACKEND_URL in Vercel (Settings → Environment Variables), then redeploy.',
    networkUnreachable:
        'Unable to reach the API server. If this is a Vercel deployment, set BACKEND_URL to your Laravel API and redeploy.',
    serverUnreachable: 'Unable to reach the server. Please try again.',
    invalidCredentials: 'Please check your email and password and try again',
    loginFailed: 'Login failed. Please try again.',
    generic: 'Something went wrong. Please try again.',
} as const;

/** Lingui returns a short id when a message was not compiled into the catalog. */
const LINGUI_ID_PATTERN = /^[A-Za-z0-9+/]{5,8}$/;

function localized(linguiValue: string, englishFallback: string): string {
    const trimmed = linguiValue.trim();
    if (!trimmed || LINGUI_ID_PATTERN.test(trimmed)) {
        return englishFallback;
    }
    return trimmed;
}

function isHtmlResponse(data: unknown): boolean {
    if (typeof data !== 'string') {
        return false;
    }
    const trimmed = data.trim().toLowerCase();
    return trimmed.startsWith('<!doctype html') || trimmed.startsWith('<html');
}

function isVercelNotFound(data: unknown): boolean {
    if (typeof data === 'string') {
        return data.includes('NOT_FOUND') || data.includes('The page could not be found');
    }
    if (data && typeof data === 'object' && 'error' in data) {
        const err = (data as {error?: {code?: string}}).error;
        return err?.code === '404' || err?.code === 'NOT_FOUND';
    }
    return false;
}

function extractApiMessage(data: unknown): string | undefined {
    if (typeof data === 'string') {
        const trimmed = data.trim();
        if (!trimmed || isVercelNotFound(trimmed) || isHtmlResponse(trimmed)) {
            return undefined;
        }
        return trimmed;
    }

    if (data && typeof data === 'object' && 'message' in data) {
        const message = (data as {message?: unknown}).message;
        if (typeof message === 'string' && message.trim().length > 0) {
            return message.trim();
        }
    }

    return undefined;
}

export function resolveHumanMessage(value: string | undefined, englishFallback: string): string {
    return localized(value ?? '', englishFallback);
}

export function getApiErrorMessage(error: unknown, fallback?: string): string {
    const defaultFallback = resolveHumanMessage(fallback, ENGLISH.loginFailed);
    const axiosError = error as AxiosError<{message?: unknown; error?: {message?: string}}>;
    const status = axiosError.response?.status;
    const data = axiosError.response?.data;

    if (!axiosError.response) {
        if (axiosError.code === 'ERR_NETWORK' || axiosError.message?.includes('Network Error')) {
            return localized(
                t`Unable to reach the API server. If this is a Vercel deployment, set BACKEND_URL to your Laravel API and redeploy.`,
                ENGLISH.networkUnreachable,
            );
        }
        return defaultFallback;
    }

    if (status === 404 || isVercelNotFound(data) || isHtmlResponse(data)) {
        return localized(
            t`The API is not connected. Deploy the Laravel backend and set BACKEND_URL in Vercel (Settings → Environment Variables), then redeploy.`,
            ENGLISH.backendNotConfigured,
        );
    }

    const rawMessage = extractApiMessage(data);
    if (rawMessage) {
        return resolveHumanMessage(rawMessage, defaultFallback);
    }

    if (status === 401) {
        return localized(
            t`Please check your email and password and try again`,
            ENGLISH.invalidCredentials,
        );
    }

    return defaultFallback;
}

export function isApiMisconfiguredError(error: unknown): boolean {
    const axiosError = error as AxiosError;
    const data = axiosError.response?.data;
    return axiosError.response?.status === 404
        || isVercelNotFound(data)
        || isHtmlResponse(data);
}
