'use strict';
import React, { StrictMode, useEffect, useState } from 'react';
//import ReactDOM from 'react-dom';
import { createRoot } from 'react-dom/client';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import { HelmetProvider } from 'react-helmet-async';
import Scroller from './components/Scroller';
import Transitor from './components/Transitor';
import Header from './components/Header';
import Footer from './components/Footer';
import HomePage from './pages/HomePage';
import NotFoundPage from './pages/NotFoundPage';


window.SYSTEM = {
    ajaxUrl: '/admin/wp-admin/admin-ajax.php',
    restBasePath: '/admin/wp-json/'
};

window.gscroll = null;

const mainNode = document.getElementById('app');
const root = createRoot(mainNode);

const App = () => {

    return (
        <Router>
            <Header />
            <Scroller>
                {/*<Transitor>*/}
                    
                    <Routes>
                        <Route path="/" exact element={<HomePage />} />
                        <Route path="*" element={<NotFoundPage />} />
                    </Routes>
                    
                    <Footer />
                    
                {/*</Transitor>*/}
            </Scroller>
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