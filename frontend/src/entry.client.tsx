import {createRoot, hydrateRoot} from "react-dom/client";
import {createBrowserRouter, matchRoutes, RouterProvider} from "react-router-dom";

import {appRouter} from "./router";
import {App} from "./App";
import {queryClient} from "./utilites/queryClient";
import {dynamicActivateLocale, getClientLocale, getSupportedLocale,} from "./locales.ts";

declare global {
    interface Window {
        __REHYDRATED_STATE__?: unknown;
    }
}

const dehydratedState = window.__REHYDRATED_STATE__;

function hasServerRenderedMarkup(container: HTMLElement): boolean {
    for (const node of container.childNodes) {
        if (node.nodeType === Node.ELEMENT_NODE) {
            return true;
        }
        if (node.nodeType === Node.TEXT_NODE && node.textContent?.trim()) {
            return true;
        }
    }
    return false;
}

async function initClientApp() {
    const rawLocale = getClientLocale();
    const locale = getSupportedLocale(rawLocale);
    await dynamicActivateLocale(locale);

    const matches = matchRoutes(appRouter, window.location)?.filter((m) => m.route.lazy);
    if (matches && matches.length > 0) {
        await Promise.all(
            matches.map(async (m) => {
                const routeModule = await m.route.lazy?.();
                Object.assign(m.route, {...routeModule, lazy: undefined});
            })
        );
    }

    const browserRouter = createBrowserRouter(appRouter);

    if ('serviceWorker' in navigator && import.meta.env.PROD) {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    }

    const prefetchRoutes = [
        () => import('./components/routes/auth/Login'),
        () => import('./components/routes/events/Dashboard'),
    ];
    prefetchRoutes.forEach((load) => {
        load().catch(() => {});
    });

    const appElement = document.getElementById("app") as HTMLElement;
    const appTree = (
        <App queryClient={queryClient} locale={rawLocale} dehydratedState={dehydratedState}>
            <RouterProvider router={browserRouter}/>
        </App>
    );

    if (hasServerRenderedMarkup(appElement)) {
        hydrateRoot(appElement, appTree);
    } else {
        createRoot(appElement).render(appTree);
    }
}

initClientApp();
