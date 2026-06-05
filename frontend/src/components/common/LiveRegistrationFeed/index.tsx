import {Card} from '../Card';
import {t, Trans} from '@lingui/macro';
import {Text} from '@mantine/core';
import {useEffect, useState} from 'react';
import {useRealtimeEventPrivateChannel} from '../../../hooks/useRealtimeChannels.ts';
import {formatDateWithLocale} from '../../../utilites/dates.ts';
import classes from './LiveRegistrationFeed.module.scss';

interface RegistrationEntry {
    id: string;
    attendee_name: string;
    ticket_type: string;
    registered_at: string;
}

interface LiveRegistrationFeedProps {
    eventId?: string | number;
    timezone?: string;
}

export const LiveRegistrationFeed = ({eventId, timezone}: LiveRegistrationFeedProps) => {
    const [entries, setEntries] = useState<RegistrationEntry[]>([]);
    const [highlightId, setHighlightId] = useState<string | null>(null);

    useRealtimeEventPrivateChannel({
        eventId,
        enabled: Boolean(eventId),
        events: {
            'attendee.registered': (payload) => {
                const entry: RegistrationEntry = {
                    id: String(payload.attendee_id),
                    attendee_name: String(payload.attendee_name ?? ''),
                    ticket_type: String(payload.ticket_type ?? ''),
                    registered_at: String(payload.registered_at ?? new Date().toISOString()),
                };

                setEntries((prev) => [entry, ...prev.filter((e) => e.id !== entry.id)].slice(0, 10));
                setHighlightId(entry.id);
                window.setTimeout(() => setHighlightId(null), 2000);
            },
        },
    });

    useEffect(() => {
        if (!highlightId) {
            return;
        }

        const timer = window.setTimeout(() => setHighlightId(null), 2000);
        return () => window.clearTimeout(timer);
    }, [highlightId]);

    if (entries.length === 0) {
        return (
            <Card className={classes.feed}>
                <Text fw={600} mb="sm">{t`Live registrations`}</Text>
                <Text size="sm" c="dimmed">
                    {t`New registrations will appear here in real time.`}
                </Text>
            </Card>
        );
    }

    return (
        <Card className={classes.feed}>
            <Text fw={600} mb="sm">{t`Live registrations`}</Text>
            <ul className={classes.list}>
                {entries.map((entry) => (
                    <li
                        key={entry.id}
                        className={`${classes.item} ${highlightId === entry.id ? classes.highlight : ''}`}
                    >
                        <Trans>
                            <strong>{entry.attendee_name}</strong> just registered for {entry.ticket_type}
                        </Trans>
                        <Text size="xs" c="dimmed">
                            {formatDateWithLocale(entry.registered_at, 'shortDateTime', timezone ?? 'UTC')}
                        </Text>
                    </li>
                ))}
            </ul>
        </Card>
    );
};
