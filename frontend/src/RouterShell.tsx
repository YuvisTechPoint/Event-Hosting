import {Outlet} from "react-router";
import {CommandPaletteProvider} from "./components/common/CommandPalette/CommandPaletteProvider.tsx";

export const RouterShell = () => (
    <CommandPaletteProvider>
        <Outlet/>
    </CommandPaletteProvider>
);
