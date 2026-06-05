import {hydrateRoot} from "react-dom/client";
import {createBrowserRouter, matchRoutes, RouterProvider} from "react-router-dom";

import {router} from "./router";
import {App} from "./App";
import {queryClient} from "./utilites/queryClient";
import {dynamicActivateLocale, getClientLocale, getSupportedLocale,} from "./locales.ts";

declare global {
    interface Window {
        __REHYDRATED_STATE__?: unknown;
    }
}

const dehydratedState = window.__REHYDRATED_STATE__;

async function initClientApp() {
    const rawLocale = getClientLocale();
    const locale = getSupportedLocale(rawLocale);
    await dynamicActivateLocale(locale);

    // Resolve lazy-loaded routes before hydration
    const matches = matchRoutes(router, window.location)?.filter((m) => m.route.lazy);
    if (matches && matches.length > 0) {
        await Promise.all(
            matches.map(async (m) => {
                const routeModule = await m.route.lazy?.();
                Object.assign(m.route, {...routeModule, lazy: undefined});
            })
        );
    }

    const browserRouter = createBrowserRouter(router);

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

    hydrateRoot(
        document.getElementById("app") as HTMLElement,
        <App queryClient={queryClient} locale={rawLocale} dehydratedState={dehydratedState}>
            <RouterProvider router={browserRouter}/>
        </App>
    );
}

initClientApp();
