'use strict';
import React, { StrictMode, useEffect, useState } from 'react';
//import ReactDOM from 'react-dom';
import { createRoot } from 'react-dom/client';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import { HelmetProvider } from 'react-helmet-async';
import Cache from './addons/Cache';
import Loader from './addons/Loader';
import Metas from './components/Metas';
import Scroller from './components/Scroller';
import PageTransition from './components/PageTransition';
import Header from './components/Header';
import Footer from './components/Footer';
import HomePage from './pages/HomePage';
import SinglePostPage from './pages/SinglePostPage';
import WaitingPage from './pages/WaitingPage';
import NotFoundPage from './pages/NotFoundPage';


window.gscroll = null;

window.SYSTEM = {
    baseUrl: 'https://wpp.test/',
    adminUrl: 'https://wpp.test/admin/',
    ajaxPath: '/admin/wp-admin/admin-ajax.php',
    restPath: '/admin/wp-json/',
};


//Cache.init('scg-cache').then(() => {

    window.loader = {
        anim: Loader.init(),
        downloader: Loader.downloader(),
        isLoaded: {
            css: false,
            fonts: false,
            images: false,
            videos: false,
            audios: false
        }
    };
    window.loader.medias = window.loader.downloader.init();

//});



const componentMap = {
    HomePage,
    SinglePostPage
};


const mainNode = document.getElementById('app');
const root = createRoot(mainNode);

const App = () => {

    const [isSettings, setSettings] = useState([]);
    const [isRoutes, setRoutes] = useState([]);
    const [isLoaded, setLoaded] = useState(false);

    useEffect(() => {


        const fetchRoutes = async () => {

            try{

                const settingsPromise = fetch(await Cache.get(window.SYSTEM.restPath + 'scg/v1/settings'));
                const postsPromise = fetch(await Cache.get(window.SYSTEM.restPath + 'wp/v2/posts?_fields=id,title,link,acf,date_gmt,modified_gmt,author'));
                const pagesPromise = fetch(await Cache.get(window.SYSTEM.restPath + 'wp/v2/pages?_fields=id,title,link,acf'));


                const [callSettings, callPosts, callPages] = await Promise.all([settingsPromise, postsPromise, pagesPromise]);

                if(!callSettings.ok) throw new Error('Settings can\'t be loaded');
                if(!callPosts.ok) throw new Error('Posts can\'t be loaded');
                if(!callPages.ok) throw new Error('Pages can\'t be loaded');


                Cache.put(callSettings.url, callSettings.clone());
                Cache.put(callPosts.url, callPosts.clone());
                Cache.put(callPages.url, callPages.clone());


                const settings = await callSettings.json();
                const posts = await callPosts.json();
                const pages = await callPages.json();
                

                setRoutes([
                    ...pages.map(page => ({
                        id: page.id,
                        title: page.title.rendered,
                        path: page.link.replace(window.SYSTEM.adminUrl, '/'),
                        acf: page.acf
                    })),
                    ...posts.map(post => {

                        post.acf.seo.og_type = 'article'; // because default is website

                        return {
                            id: post.id,
                            date: post.date_gmt,
                            modified: post.modified_gmt,
                            //author: users.filter(user => user.id === post.author)[0].link.replace('/admin', ''),
                            title: post.title.rendered,
                            path: post.link.replace(window.SYSTEM.adminUrl, '/'),
                            acf: post.acf
                        }
                    }),
                ]);

                setSettings(settings);

                setLoaded(true);

            } catch(error){

                console.error(error);

            }

        }

        fetchRoutes();

    }, []);

    return (
        <Router>

            {isLoaded ? (
                <>
                    <Header />
                    <Scroller>
                        <PageTransition>
                            
                            <Routes>

                                {isRoutes.map((route, i) => {

                                    const Component = componentMap[route.acf.component_name];

                                    return (
                                        <Route
                                            exact 
                                            key={i} 
                                            path={route.path} 
                                            element={
                                                <>
                                                    <Metas
                                                        datas={{
                                                            page_name: route?.title,
                                                            date: route?.date,
                                                            modified: route?.modified,
                                                            name: route?.acf?.name,
                                                            seo: route.acf?.seo,
                                                            author: route?.author
                                                        }}
                                                        settings={isSettings}
                                                    />
                                                    <Component id={route?.id} title={route?.title} path={route?.path} acf={route?.acf} />
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
                                                datas={{page_name: 'Error 404', seo: {stop_indexing: true}}}
                                                settings={isSettings}
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