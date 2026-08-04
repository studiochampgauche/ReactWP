import { Suspense, createContext, useContext, useLayoutEffect } from 'react';
import { createRoot, hydrateRoot } from 'react-dom/client';
import { createBrowserRouter, Outlet, RouterProvider } from 'react-router';
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
                currentUser={runtime.currentUser}
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
const initialTemplate = resolveTemplateEntry(runtime.route.template);
const renderSource = mainNode?.dataset.rwpRender || 'client';
const shouldHydrate = Boolean(
    mainNode
    && renderSource !== 'client'
    && mainNode.hasChildNodes()
);

const renderApplication = () => {
    const application = <RouterProvider router={router} />;

    if(shouldHydrate){
        hydrateRoot(mainNode, application, {
            onRecoverableError(error){
                console.warn('ReactWP recovered from an initial hydration mismatch.', error);
            }
        });
        return;
    }

    createRoot(mainNode).render(application);
};

if(shouldHydrate){
    initialTemplate.preload()
        .then(renderApplication)
        .catch(() => {
            mainNode.replaceChildren();
            mainNode.dataset.rwpRender = 'client';
            createRoot(mainNode).render(<RouterProvider router={router} />);
        });
} else {
    renderApplication();
}
