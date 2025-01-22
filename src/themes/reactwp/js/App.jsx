'use strict';
import React, { StrictMode, useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import { HelmetProvider } from 'react-helmet-async';
import NotFoundTemplate from './templates/NotFound';
import DefaultTemplate from './templates/Default';
import WaitTemplate from './templates/Wait';
import PageTransition from './inc/PageTransition';
import Metas from './inc/Metas';
import './inc/Loader';
import './inc/Scroller';


const templates = {
	DefaultTemplate
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
                	<PageTransition />
                    <Routes>

                    	{ROUTES.map((route, i) => {

                            const Template = templates[route.template] || templates['DefaultTemplate'];


                            route.seo.pageTitle = route.pageName;

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
                                            <Template
                                                id={route.id}
                                                type={route.type}
                                                routeName={route.routeName}
                                                pageName={route.pageName}
                                                path={route.path}
                                                seo={route.seo}
                                                acf={route.acf}
                                            />
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
                                        seo={{pageTitle: CL === 'fr' ? 'Erreur 404' : 'Error 404', do_not_index: true}}
                                    />
                                    <NotFoundTemplate />
                                </>
                            }
                        />

                    </Routes>
                </>
            ) : (
                <Routes>
                    <Route path="*" element={<WaitTemplate />} />
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