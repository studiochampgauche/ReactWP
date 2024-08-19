import React, { createContext, useState, useContext } from 'react';


const readyGScrollContext = createContext();


export const ReadyGScrollProvider = ({ children }) => {

	const [ isGScrollReady, setIsGScrollReady ] = useState(false);

	return (

		<readyGScrollContext.Provider value={{ isGScrollReady, setIsGScrollReady }}>
    		{children}
        </readyGScrollContext.Provider>

	);

};


export const useGScrollReady = () => useContext(readyGScrollContext);