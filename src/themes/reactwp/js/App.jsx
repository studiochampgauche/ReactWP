'use strict';
import React, { StrictMode, createContext, useContext, useEffect, useLayoutEffect, useState, lazy } from 'react';
import { createRoot } from 'react-dom/client';
import { createBrowserRouter, RouterProvider, Outlet } from 'react-router-dom';
import { HelmetProvider } from 'react-helmet-async';
import NotFoundTemplate from './templates/NotFound';
import PageTransition from './inc/PageTransition';
import Footer from './templates/components/Footer';
import Metas from './inc/Metas';
import Loader from './inc/Loader';
import PageTransitionAnimation from './inc/PageTransitionAnimation';
import './inc/Scroller';
import '../scss/default.scss';

export const RouteContext = createContext(null);


window.loader = {
    isLoaded: false
};

Loader.setRoute(CURRENT_ROUTE);
Loader.download();

Loader.setAnimation(({ gsap, ScrollTrigger, done }) => {

    let tl = gsap.timeline({
        onComplete: () => {
            tl.kill();
            tl = null;

            window.gscroll?.paused(false);
            done();
        }
    });

    tl
    .to({}, .1, {})
    .add(() => {
        if(!window.loader?.isLoaded){
            tl.restart();
            return;
        }
    })
    .to('#loader', .4, {
        opacity: 0,
        pointerEvents: 'none',
        onStart: () => {
            ScrollTrigger?.refresh();
        }
    });

    return tl;

});

window.loader.init = Loader.init();


PageTransitionAnimation
.setLeave(({ gsap }) => {
    return gsap.to('#viewport', .2, {
        opacity: 0,
        pointerEvents: 'none'
    });
})
.setEnter(({ gsap }) => {
    return gsap.to('#viewport', .2, {
        opacity: 1,
        pointerEvents: 'initial'
    });
});


const templates = {
    Default: lazy(() => import('./templates/Default')),
};

const Template = templates[CURRENT_ROUTE.template] || templates['Default'];

function LoaderBridge(){
    const { currentRoute } = useContext(RouteContext);

    useEffect(() => {
        Loader.setRoute(currentRoute);
    }, [currentRoute]);

    return null;
}


function CurrentRouteElement(){
    const { currentRoute } = useContext(RouteContext);

    useLayoutEffect(() => {
        Loader.markRouteReady(currentRoute.path);
    }, [currentRoute.path]);

    const Template = templates[currentRoute.template] || templates.Default;

    const seo = {
        ...currentRoute.seo,
        pageName: currentRoute.pageName
    };

    const routeProps = {
        id: currentRoute.id,
        type: currentRoute.type,
        template: currentRoute.template,
        pageName: currentRoute.pageName,
        path: currentRoute.path,
        mediaGroups: currentRoute.mediaGroups,
        data: currentRoute.data
    };

    return (
        <>
            <Metas seo={seo} />
            <Template key={currentRoute.path} {...routeProps} />
            <Footer />
        </>
    );
}

const App = () => {

    const [currentRoute, setCurrentRoute] = useState(CURRENT_ROUTE);

    return (
        <>
            <RouteContext.Provider value={{ currentRoute, setCurrentRoute }}>
                <LoaderBridge />
                <PageTransition />
                <Outlet />
            </RouteContext.Provider>
        </>
    );
    
};



const router = createBrowserRouter([
    {
        path: "/",
        element: <App />,
        children: [
            {
                index: true,
                element: <CurrentRouteElement />
            },
            {
                path: '*',
                element: <CurrentRouteElement />
            }
        ],
    },
]);


const mainNode = document.getElementById('app');
const root = createRoot(mainNode);

root.render(
    <HelmetProvider>
        <RouterProvider router={router} />
    </HelmetProvider>
);