'use strict';
import React, { StrictMode, useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import { HelmetProvider } from 'react-helmet-async';
import './inc/Loader';
import './inc/Scroller';
import Wait from './templates/Wait';
import NotFound from './templates/NotFound';



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
                    <Routes>

                        <Route
                            path="*"
                            element={
                                <>
                                    <NotFound />
                                </>
                            }
                        />

                    </Routes>
                </>
            ) : (
                <Routes>
                    <Route path="*" element={<Wait />} />
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