'use strict';
import React, { useEffect, useState, useRef } from 'react'
import { useNavigate, useLocation } from 'react-router-dom';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';


const PageTransition = () => {
	
	const navigate = useNavigate();
	const location = useLocation();

	const [isEntering, setEntering] = useState(false);
	const [isMiddle, setMiddle] = useState(false);
	const [isLeaving, setLeaving] = useState(false);

	useEffect(() => {

		console.log('pathname change');

	}, [location.pathname]);

	
}

export default PageTransition;