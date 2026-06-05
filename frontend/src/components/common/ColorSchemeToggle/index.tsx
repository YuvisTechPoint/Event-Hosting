import {ActionIcon, Tooltip} from "@mantine/core";
import {IconMoon, IconSun} from "@tabler/icons-react";
import {t} from "@lingui/macro";
import {useColorSchemeToggle} from "../../../contexts/ColorSchemeProvider.tsx";

export const ColorSchemeToggle = () => {
    const {colorScheme, toggleColorScheme} = useColorSchemeToggle();
    const isDark = colorScheme === "dark";

    return (
        <Tooltip label={isDark ? t`Switch to light mode` : t`Switch to dark mode`}>
            <ActionIcon
                variant="subtle"
                color="gray"
                onClick={toggleColorScheme}
                aria-label={isDark ? t`Switch to light mode` : t`Switch to dark mode`}
            >
                {isDark ? <IconSun size={18}/> : <IconMoon size={18}/>}
            </ActionIcon>
        </Tooltip>
    );
};
