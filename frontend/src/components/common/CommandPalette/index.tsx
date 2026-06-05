import {Group, Modal, Text, TextInput, UnstyledButton} from "@mantine/core";
import {IconSearch} from "@tabler/icons-react";
import {t} from "@lingui/macro";
import {useMemo, useState} from "react";
import {useNavigate, useParams} from "react-router";

interface CommandPaletteProps {
    opened: boolean;
    onClose: () => void;
}

interface CommandItem {
    id: string;
    label: string;
    path: string;
}

export const CommandPalette = ({opened, onClose}: CommandPaletteProps) => {
    const navigate = useNavigate();
    const {eventId} = useParams();
    const [query, setQuery] = useState("");

    const items = useMemo<CommandItem[]>(() => {
        const base: CommandItem[] = [
            {id: "dashboard", label: t`Dashboard`, path: "/manage/events"},
            {id: "discover", label: t`Discover`, path: "/discover"},
            {id: "profile", label: t`Profile`, path: "/manage/profile"},
        ];

        if (eventId) {
            base.push(
                {id: "event-dashboard", label: t`Event Dashboard`, path: `/manage/event/${eventId}/dashboard`},
                {id: "event-hackathon", label: t`Hackathon`, path: `/manage/event/${eventId}/hackathon`},
                {id: "event-attendees", label: t`Attendees`, path: `/manage/event/${eventId}/attendees`},
                {id: "event-settings", label: t`Event Settings`, path: `/manage/event/${eventId}/settings`},
            );
        }

        return base;
    }, [eventId]);

    const filteredItems = items.filter((item) =>
        item.label.toLowerCase().includes(query.trim().toLowerCase()),
    );

    const handleSelect = (path: string) => {
        navigate(path);
        setQuery("");
        onClose();
    };

    return (
        <Modal
            opened={opened}
            onClose={() => {
                setQuery("");
                onClose();
            }}
            title={t`Command palette`}
            size="md"
            centered
        >
            <TextInput
                placeholder={t`Search pages...`}
                leftSection={<IconSearch size={16}/>}
                value={query}
                onChange={(event) => setQuery(event.currentTarget.value)}
                autoFocus
                mb="md"
            />
            {filteredItems.length === 0 ? (
                <Text c="dimmed" size="sm">{t`No matching pages.`}</Text>
            ) : (
                filteredItems.map((item) => (
                    <UnstyledButton
                        key={item.id}
                        onClick={() => handleSelect(item.path)}
                        w="100%"
                        p="sm"
                        style={{borderRadius: 8}}
                    >
                        <Group justify="space-between">
                            <Text size="sm">{item.label}</Text>
                            <Text size="xs" c="dimmed">{item.path}</Text>
                        </Group>
                    </UnstyledButton>
                ))
            )}
        </Modal>
    );
};
