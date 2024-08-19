import React, { createContext, useState, useContext } from 'react';


const forceContext = createContext();

export const ForceProvider = ({ children }) => {

	const [ isReady, setIsReady ] = useState(false);

	return (

		<forceContext.Provider value={{ isReady, setIsReady }}>
    		{children}
        </forceContext.Provider>

	);

};


export const useForce = () => useContext(forceContext);