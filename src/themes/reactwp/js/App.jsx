'use strict';
import React, { StrictMode, useEffect, useState } from 'react';
//import ReactDOM from 'react-dom';
import { createRoot } from 'react-dom/client';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import { HelmetProvider } from 'react-helmet-async';
import './utils/Consent';
import Cache from './utils/Cache';
import Loader from './utils/Loader';
import Metas from './utils/Metas';
import Scroller from './utils/Scroller';
import PageTransition from './utils/PageTransition';
import Header from './templates/Header';
import Footer from './templates/Footer';
import HomePage from './templates/HomePage';
import DefaultPage from './templates/DefaultPage';
import SinglePostPage from './templates/SinglePostPage';
import WaitingPage from './templates/WaitingPage';
import NotFoundPage from './templates/NotFoundPage';

if(SYSTEM.cacheActive){

    const lastCacheVersion = localStorage.getItem('cacheVersion');
    const cacheEndTime = localStorage.getItem('cacheEndTime');

    const currentTime = Math.floor(Date.now() / 1000);

    if(!cacheEndTime || currentTime >= +cacheEndTime || !lastCacheVersion || lastCacheVersion != SYSTEM.cacheVersion){

        if(lastCacheVersion){

            Cache.delete('cache-v' + lastCacheVersion).then(() => Cache.init('cache-v' + SYSTEM.cacheVersion).then(() => initApp()));

        } else{

            Cache.init('cache-v' + SYSTEM.cacheVersion).then(() => initApp());

        }

        localStorage.setItem('cacheVersion', SYSTEM.cacheVersion);
        localStorage.setItem('cacheEndTime', (currentTime + +SYSTEM.cacheExpiration));


    } else{

        Cache.init('cache-v' + SYSTEM.cacheVersion).then(() => initApp());

    }

} else{

    const lastCacheVersion = localStorage.getItem('cacheVersion');

    if(lastCacheVersion){

        try{

            Cache.delete('cache-v' + lastCacheVersion);

        } catch(_){

        }

        localStorage.removeItem('cacheVersion');
    }

    if(localStorage.getItem('cacheEndTime'))
        localStorage.removeItem('cacheEndTime');


    initApp();

}


function initApp(){

    window.loader = {
        anim: Loader.init(),
        downloader: Loader.downloader(),
        isLoaded: {
            fonts: false,
            images: false,
            videos: false,
            audios: false
        }
    };
    window.loader.medias = window.loader.downloader.init();



    const componentMap = {
        HomePage,
        DefaultPage,
        SinglePostPage
    };


    const mainNode = document.getElementById('app');
    const root = createRoot(mainNode);

    const App = () => {

        const [isLoaded, setLoaded] = useState(false);

        useEffect(() => {

            setLoaded(true);

        }, []);

        return (
            <Router future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>

                {isLoaded ? (
                    <>
                        <Header />
                        <Scroller>
                            <PageTransition>
                                
                                <Routes>

                                    {ROUTES.map((route, i) => {

                                        const Component = componentMap[route.componentName];

                                        route.seo.pageTitle = route.routeName;

                                        return (
                                            <Route
                                                exact 
                                                key={i} 
                                                path={route.path} 
                                                element={
                                                    <>
                                                        <Metas
                                                            extraDatas={route?.extraDatas}
                                                            seo={route?.seo}
                                                        />
                                                        <Component
                                                            id={route.id}
                                                            type={route.type}
                                                            routeName={route.routeName}
                                                            path={route.path}
                                                            seo={route.seo}
                                                            acf={route.acf}
                                                        />
                                                        <Footer />
                                                    </>
                                                }
                                            />
                                        )
                                    })}

                                    <Route
                                        path="*"
                                        element={
                                            <>
                                                <Metas
                                                    seo={{pageTitle: 'Error 404', stop_indexing: true}}
                                                />
                                                <NotFoundPage />
                                            </>
                                        }
                                    />

                                </Routes>
                                
                            </PageTransition>
                        </Scroller>
                    </>
                ) : (
                    <Routes>
                        <Route path="*" element={<WaitingPage />} />
                    </Routes>
                )}

            </Router>
        );
        
    };

    root.render(
        //<StrictMode>
            <HelmetProvider>
                <App />
            </HelmetProvider>
        //</StrictMode>
    );

}