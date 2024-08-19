import React, { createContext, useState, useContext } from 'react';
import { ReadyGScrollProvider } from './ready/Scroller';


const readyContext = createContext();


export const ReadyProvider = ({ children }) => {

	const [ isReady, setIsReady ] = useState(false);

	return (
		<ReadyGScrollProvider>
			<readyContext.Provider value={{ isReady, setIsReady }}>
	    		{children}
	        </readyContext.Provider>
        </ReadyGScrollProvider>

	);

};


export const useReady = () => useContext(readyContext);