import {useEffect, useRef} from 'react';
import {getEcho, isRealtimeEnabled} from '../utilites/echo.ts';

type EventHandler = (payload: Record<string, unknown>) => void;

interface UseRealtimeEventPrivateChannelOptions {
    eventId?: string | number;
    events: Record<string, EventHandler>;
    enabled?: boolean;
}

/**
 * Subscribe to private-event.{eventId} (Echo.private `event.{eventId}`).
 */
export const useRealtimeEventPrivateChannel = ({
    eventId,
    events,
    enabled = true,
}: UseRealtimeEventPrivateChannelOptions) => {
    const handlersRef = useRef(events);
    handlersRef.current = events;

    useEffect(() => {
        if (!enabled || !eventId || !isRealtimeEnabled()) {
            return;
        }

        const echo = getEcho();
        if (!echo) {
            return;
        }

        const channel = echo.private(`event.${eventId}`);

        Object.entries(handlersRef.current).forEach(([eventName, handler]) => {
            channel.listen(`.${eventName}`, (payload: Record<string, unknown>) => handler(payload));
        });

        return () => {
            Object.keys(handlersRef.current).forEach((eventName) => {
                channel.stopListening(`.${eventName}`);
            });
            echo.leave(`private-event.${eventId}`);
        };
    }, [eventId, enabled]);
};

interface UseRealtimePublicChannelOptions {
    channelName?: string;
    events: Record<string, EventHandler>;
    enabled?: boolean;
}

/**
 * Subscribe to a public channel (no auth), e.g. event.{id}.capacity or event.{id}.check-in.
 */
export const useRealtimePublicChannel = ({
    channelName,
    events,
    enabled = true,
}: UseRealtimePublicChannelOptions) => {
    const handlersRef = useRef(events);
    handlersRef.current = events;

    useEffect(() => {
        if (!enabled || !channelName || !isRealtimeEnabled()) {
            return;
        }

        const echo = getEcho();
        if (!echo) {
            return;
        }

        const channel = echo.channel(channelName);

        Object.entries(handlersRef.current).forEach(([eventName, handler]) => {
            channel.listen(`.${eventName}`, (payload: Record<string, unknown>) => handler(payload));
        });

        return () => {
            Object.keys(handlersRef.current).forEach((eventName) => {
                channel.stopListening(`.${eventName}`);
            });
            echo.leave(channelName);
        };
    }, [channelName, enabled]);
};
