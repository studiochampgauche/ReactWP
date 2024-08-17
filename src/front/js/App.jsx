import React, { StrictMode } from 'react';
//import ReactDOM from 'react-dom';
import { createRoot } from 'react-dom/client';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import Scroller from './components/Scroller'
import Transitor from './components/Transitor'
import Header from './components/Header'
import Footer from './components/Footer'
import HomePage from './pages/HomePage';
import NotFoundPage from './pages/NotFoundPage';


const mainNode = document.getElementById('viewport');
const root = createRoot(mainNode);


const App = () => {
    
    return (
        <Router>
            <Header />
            <Scroller>
                <Transitor>
                    
                    <Routes>
                        <Route path="/" exact element={<HomePage />} />
                        <Route path="*" element={<NotFoundPage />} />
                    </Routes>
                    
                    <Footer />
                    
                </Transitor>
            </Scroller>
        </Router>
    );
    
};

root.render(
    <StrictMode>
        <App />
    </StrictMode>
);