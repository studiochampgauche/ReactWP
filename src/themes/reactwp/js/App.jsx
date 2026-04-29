import { Suspense, createContext, useContext, useLayoutEffect } from 'react';
import { createRoot } from 'react-dom/client';
import { createBrowserRouter, Outlet, RouterProvider } from 'react-router-dom';
import AppShell from './inc/AppShell';
import { runtime } from './inc/Runtime';
import { initializeTemplateRegistry } from './inc/initializeTemplateRegistry';
import { resolveTemplateEntry } from './inc/TemplateRegistry';
import { useDocumentMeta } from './inc/useDocumentMeta';
import { useRouteTransition } from './inc/useRouteTransition';

initializeTemplateRegistry();

export const RouteContext = createContext(null);

const RouteView = ({ route }) => {
    const templateEntry = resolveTemplateEntry(route.template);
    const Template = templateEntry.Component;

    return (
        <>
            <Template
                key={route.key}
                route={route}
                site={runtime.site}
                theme={runtime.theme}
                system={runtime.system}
                navigation={runtime.navigation}
            />
            <RouteReadySignal route={route} />
        </>
    );
};

const RouteReadySignal = ({ route }) => {
    const { handleRouteReady } = useContext(RouteContext);

    useLayoutEffect(() => {
        handleRouteReady(route);
    }, [route?.key, handleRouteReady, route]);

    return null;
};

const RenderedRoute = () => {
    const { currentRoute } = useContext(RouteContext);

    useDocumentMeta(currentRoute);

    return (
        <Suspense fallback={null}>
            <RouteView route={currentRoute} />
        </Suspense>
    );
};

const ReactWPApplication = () => {
    const routeTransition = useRouteTransition();

    return (
        <RouteContext.Provider value={routeTransition}>
            <AppShell
                showHeader={false}
                showFooter={false}
                headerKey={routeTransition.headerKey}
                footerKey={routeTransition.footerKey}
            >
                <Outlet />
            </AppShell>
        </RouteContext.Provider>
    );
};

const router = createBrowserRouter([
    {
        path: '/',
        element: <ReactWPApplication />,
        children: [
            {
                index: true,
                element: <RenderedRoute />
            },
            {
                path: '*',
                element: <RenderedRoute />
            }
        ]
    }
]);

const mainNode = document.getElementById('app');
const root = createRoot(mainNode);

root.render(<RouterProvider router={router} />);
