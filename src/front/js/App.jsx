'use strict';
import React, { StrictMode, useEffect, useState } from 'react';
//import ReactDOM from 'react-dom';
import { createRoot } from 'react-dom/client';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import { HelmetProvider } from 'react-helmet-async';
import Loader from './addons/Loader';
import Metas from './components/Metas';
import Scroller from './components/Scroller';
import PageTransition from './components/PageTransition';
import Header from './components/Header';
import Footer from './components/Footer';
import HomePage from './pages/HomePage';
import WaitingPage from './pages/WaitingPage';
import NotFoundPage from './pages/NotFoundPage';


window.SYSTEM = {
    baseUrl: 'https://wpp.test/',
    adminUrl: 'https://wpp.test/admin/',
    ajaxPath: '/admin/wp-admin/admin-ajax.php',
    restPath: '/admin/wp-json/',
    loaded: {
        css: false
    }
};

window.defaultMetas = {
    robots: 'max-image-preview:large, noindex, nofollow',
    siteName: 'My WordPress Project',
    description: 'My WordPress Project a React Front-end with a back-end WordPress',
    image: window.SYSTEM.baseUrl + 'assets/images/sharing.jpg'
};

window.gscroll = null;


window.loader = Loader.init();

window.medias = {
    download: Loader.download(), 
};
window.medias.init = window.medias.download.init();


document.addEventListener("DOMContentLoaded", () => {

    const cssLinkElement = document.getElementById('mainStyle');

    if (cssLinkElement.sheet) {

        window.SYSTEM.loaded.css = true;

    } else {

        cssLinkElement.onload = () => {

            window.SYSTEM.loaded.css = true;

        };

        cssLinkElement.onerror = () => {

            window.SYSTEM.loaded.css = false;

        };

    }
});


const componentMap = {
    HomePage
};


const mainNode = document.getElementById('app');
const root = createRoot(mainNode);

const App = () => {

    const [routes, setRoutes] = useState([]);
    const [loaded, setLoaded] = useState(false);

    useEffect(() => {


        const fetchRoutes = async () => {

            try{

                const callPages = await fetch(window.SYSTEM.restPath + 'wp/v2/pages?_fields=id,link,acf');

                if(!callPages.ok) throw new Error('Pages can\'t be loaded');


                const pages = await callPages.json();

                setRoutes([
                    ...pages.map(page => ({ id: page.id, path: page.link.replace(window.SYSTEM.adminUrl, '/'), acf: page.acf }))
                ]);

                setLoaded(true);

            } catch(error){

                console.error(error);

            }

        }

        fetchRoutes();

    }, []);

    return (
        <Router>

            {loaded ? (
                <>
                    <Header />
                    <Scroller>
                        <PageTransition>
                            
                            <Routes>

                                {routes.map((route, i) => {
                                    
                                    const Component = componentMap[route.acf.component_name];

                                    return (
                                        <Route
                                            exact 
                                            key={i} 
                                            path={route.path} 
                                            element={
                                                <>
                                                    <Metas
                                                        title={route.acf?.seo?.title || window.defaultMetas.siteName}
                                                        ogTitle={route.acf?.seo?.og_title || window.defaultMetas.siteName}
                                                        description={route.acf?.seo?.description || window.defaultMetas.description}
                                                        ogDescription={route.acf?.seo?.og_description || window.defaultMetas.description}
                                                        robots={!route.acf?.seo?.stop_indexing ? 'max-image-preview:large, index, follow' : window.defaultMetas.robots}
                                                        image={route.acf?.seo?.image || window.defaultMetas.images}
                                                    />
                                                    <Component id={route.id} path={route.path} acf={route.acf} />
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
                                                title='Page not found'
                                            /> 
                                            <NotFoundPage />
                                        </>
                                    }
                                />

                            </Routes>
                            
                            <Footer />
                            
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