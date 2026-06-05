import {ActionIcon, Badge, Indicator, Menu, ScrollArea, Text} from '@mantine/core';
import {IconBell} from '@tabler/icons-react';
import {t} from '@lingui/macro';
import {useEventNotifications} from '../../../contexts/EventNotificationsContext.tsx';
import {formatDateWithLocale} from '../../../utilites/dates.ts';
import classes from './NotificationBell.module.scss';

export const NotificationBell = () => {
    const {notifications, unreadCount, markAllRead, markRead} = useEventNotifications();

    return (
        <Menu
            position="bottom-end"
            width={360}
            withinPortal
            onOpen={markAllRead}
        >
            <Menu.Target>
                <Indicator
                    inline
                    label={unreadCount > 99 ? '99+' : unreadCount}
                    size={16}
                    disabled={unreadCount === 0}
                    color="red"
                >
                    <ActionIcon variant="transparent" color="white" aria-label={t`Notifications`}>
                        <IconBell size={20}/>
                    </ActionIcon>
                </Indicator>
            </Menu.Target>

            <Menu.Dropdown>
                <Menu.Label>{t`Notifications`}</Menu.Label>
                {notifications.length === 0 && (
                    <Text size="sm" c="dimmed" px="sm" py="xs">
                        {t`No notifications yet`}
                    </Text>
                )}
                <ScrollArea.Autosize mah={320}>
                    {notifications.map((notification) => (
                        <Menu.Item
                            key={notification.id}
                            className={notification.read ? undefined : classes.unread}
                            onClick={() => markRead(notification.id)}
                        >
                            <div className={classes.item}>
                                <div className={classes.titleRow}>
                                    <Text size="sm" fw={600}>{notification.title}</Text>
                                    {!notification.read && <Badge size="xs" color="primary">{t`New`}</Badge>}
                                </div>
                                <Text size="xs" c="dimmed">{notification.message}</Text>
                                <Text size="xs" c="dimmed" mt={4}>
                                    {formatDateWithLocale(notification.created_at, 'shortDateTime', 'UTC')}
                                </Text>
                            </div>
                        </Menu.Item>
                    ))}
                </ScrollArea.Autosize>
            </Menu.Dropdown>
        </Menu>
    );
};
