'use strict';
import Loader from './Loader.js';
import PageScroller from './PageScroller.js';
import PageTransitor from './PageTransitor.js';


class App{
    
    constructor(){
        
        window.scrollTo(0,0);

		if ('scrollRestoration' in history)
			history.scrollRestoration = 'manual';

		const isIE11 = !!window.MSInputMethodContext && !!document.documentMode;
		const isEdge = /Edge/.test(navigator.userAgent);

		if(isIE11 || isEdge)
			setTimeout(function(){ window.scrollTo(0, 0); }, 300);
		

		window.onload = async () => {
            
            new PageScroller();

            await new Loader();
            
            new PageTransitor();
        }
        
    }
    
}

new App();
