'use strict';
import React, { StrictMode, useEffect, useState } from 'react';
//import ReactDOM from 'react-dom';
import { createRoot } from 'react-dom/client';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import { HelmetProvider } from 'react-helmet-async';
import Loader from './addons/Loader';
import Metas from './components/Metas';
import Scroller from './components/Scroller';
import Transitor from './components/Transitor';
import Header from './components/Header';
import Footer from './components/Footer';
import HomePage from './pages/HomePage';
import WaitingPage from './pages/WaitingPage';
import NotFoundPage from './pages/NotFoundPage';


Loader.init();
Loader.download();


window.defaultMetas = {
    siteName: 'My WordPress Project',
    description: 'My description'
};

window.SYSTEM = {
    adminUrl: 'https://wpp.test/admin/',
    ajaxUrl: '/admin/wp-admin/admin-ajax.php',
    restBasePath: '/admin/wp-json/'
};

window.gscroll = null;


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

                const callPages = await fetch(window.SYSTEM.restBasePath + 'wp/v2/pages');

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
                        <Transitor>
                            
                            <Routes>

                                {routes.map(route => {
                                    
                                    const Component = componentMap[route.acf.component_name];

                                    return (
                                        <Route 
                                            key={route.id} 
                                            path={route.path} 
                                            element={
                                                <>
                                                    <Metas
                                                        title={route.acf?.seo?.title || window.defaultMetas.siteName}
                                                        ogTitle={route.acf?.seo?.og_title || window.defaultMetas.siteName}
                                                        description={route.acf?.seo?.description || window.defaultMetas.description}
                                                        ogDescription={route.acf?.seo?.og_description || window.defaultMetas.description}
                                                    />
                                                    <Component acf={route.acf} />
                                                </>
                                            }
                                        />
                                    )
                                })}

                                <Route path="*" element={<NotFoundPage />} />

                            </Routes>
                            
                            <Footer />
                            
                        </Transitor>
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