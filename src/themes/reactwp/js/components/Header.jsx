import React, { forwardRef, useRef } from 'react';
import { createPortal } from 'react-dom';

const Header = forwardRef(function Header({
    show = false,
    className = null,
    mountId = 'app-header',
    ...props
}, ref){
    
    const localRef = useRef(null);
    const headerRef = ref || localRef;

    if(!show){
        return null;
    }

    const mountNode = typeof document !== 'undefined'
        ? (document.getElementById(mountId) || document.body)
        : null;

    if(!mountNode){
        return null;
    }

    const tagProps = {
        className,
        ...props
    };

    return createPortal(
        <header ref={headerRef} {...tagProps}></header>,
        mountNode
    );
});

export default Header;