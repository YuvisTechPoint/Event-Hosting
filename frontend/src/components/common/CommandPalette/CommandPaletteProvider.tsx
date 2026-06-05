import {createContext, FC, PropsWithChildren, useContext} from "react";
import {useDisclosure, useHotkeys} from "@mantine/hooks";
import {CommandPalette} from "./index.tsx";

interface CommandPaletteContextValue {
    open: () => void;
    close: () => void;
}

const CommandPaletteContext = createContext<CommandPaletteContextValue | null>(null);

export const CommandPaletteProvider: FC<PropsWithChildren> = ({children}) => {
    const [opened, {open, close, toggle}] = useDisclosure(false);

    useHotkeys([
        ["mod+K", (event) => {
            event.preventDefault();
            toggle();
        }],
    ]);

    return (
        <CommandPaletteContext.Provider value={{open, close}}>
            {children}
            <CommandPalette opened={opened} onClose={close}/>
        </CommandPaletteContext.Provider>
    );
};

export const useCommandPalette = (): CommandPaletteContextValue => {
    const context = useContext(CommandPaletteContext);
    if (!context) {
        throw new Error("useCommandPalette must be used within CommandPaletteProvider");
    }
    return context;
};
