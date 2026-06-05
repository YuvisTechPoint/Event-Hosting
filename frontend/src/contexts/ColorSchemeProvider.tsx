import {createContext, FC, PropsWithChildren, useContext, useMemo} from "react";
import {useMantineColorScheme} from "@mantine/core";

export const COLOR_SCHEME_STORAGE_KEY = "event-hosting-color-scheme";

type AppColorScheme = "light" | "dark";

interface ColorSchemeContextValue {
    colorScheme: AppColorScheme;
    toggleColorScheme: () => void;
    setColorScheme: (scheme: AppColorScheme) => void;
}

const ColorSchemeContext = createContext<ColorSchemeContextValue | null>(null);

export const ColorSchemeProvider: FC<PropsWithChildren> = ({children}) => {
    const {colorScheme, setColorScheme, toggleColorScheme} = useMantineColorScheme();

    const value = useMemo(() => ({
        colorScheme: colorScheme as AppColorScheme,
        toggleColorScheme,
        setColorScheme: (scheme: AppColorScheme) => setColorScheme(scheme),
    }), [colorScheme, setColorScheme, toggleColorScheme]);

    return (
        <ColorSchemeContext.Provider value={value}>
            {children}
        </ColorSchemeContext.Provider>
    );
};

export const useColorSchemeToggle = (): ColorSchemeContextValue => {
    const context = useContext(ColorSchemeContext);
    if (!context) {
        throw new Error("useColorSchemeToggle must be used within ColorSchemeProvider");
    }
    return context;
};
