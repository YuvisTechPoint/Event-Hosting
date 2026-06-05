import React, {createContext, useCallback, useContext, useMemo, useState} from 'react';
import {notifications as toastNotifications} from '@mantine/notifications';
import {useRealtimeEventPrivateChannel} from '../hooks/useRealtimeChannels.ts';

export interface OrganizerNotification {
    id: string;
    type: string;
    title: string;
    message: string;
    created_at: string;
    metadata?: Record<string, unknown>;
    read?: boolean;
}

interface EventNotificationsContextValue {
    notifications: OrganizerNotification[];
    unreadCount: number;
    markAllRead: () => void;
    markRead: (id: string) => void;
}

const EventNotificationsContext = createContext<EventNotificationsContextValue>({
    notifications: [],
    unreadCount: 0,
    markAllRead: () => {},
    markRead: () => {},
});

const MAX_NOTIFICATIONS = 50;

export const EventNotificationsProvider: React.FC<{
    eventId?: string | number;
    children: React.ReactNode;
}> = ({eventId, children}) => {
    const [notifications, setNotifications] = useState<OrganizerNotification[]>([]);

    const addNotification = useCallback((payload: Record<string, unknown>) => {
        const notification: OrganizerNotification = {
            id: String(payload.id ?? `${payload.type}-${payload.created_at}`),
            type: String(payload.type ?? 'general'),
            title: String(payload.title ?? ''),
            message: String(payload.message ?? ''),
            created_at: String(payload.created_at ?? new Date().toISOString()),
            metadata: (payload.metadata as Record<string, unknown>) ?? {},
            read: false,
        };

        setNotifications((prev) => [notification, ...prev].slice(0, MAX_NOTIFICATIONS));

        toastNotifications.show({
            title: notification.title,
            message: notification.message,
            color: 'blue',
            position: 'top-right',
        });
    }, []);

    useRealtimeEventPrivateChannel({
        eventId,
        enabled: Boolean(eventId),
        events: {
            'notification.new': addNotification,
        },
    });

    const unreadCount = useMemo(
        () => notifications.filter((n) => !n.read).length,
        [notifications],
    );

    const markAllRead = useCallback(() => {
        setNotifications((prev) => prev.map((n) => ({...n, read: true})));
    }, []);

    const markRead = useCallback((id: string) => {
        setNotifications((prev) => prev.map((n) => (n.id === id ? {...n, read: true} : n)));
    }, []);

    const value = useMemo(
        () => ({notifications, unreadCount, markAllRead, markRead}),
        [notifications, unreadCount, markAllRead, markRead],
    );

    return (
        <EventNotificationsContext.Provider value={value}>
            {children}
        </EventNotificationsContext.Provider>
    );
};

export const useEventNotifications = () => useContext(EventNotificationsContext);
