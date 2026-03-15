'use strict';
import React, { useEffect, useRef, forwardRef } from 'react';
import { Link } from 'react-router-dom';
import parse from 'html-react-parser';
import { gsap } from 'gsap';

const Button = forwardRef(function Button({ to = null, text, className = null, before, after, ...props }, ref){


	const localRef = useRef(null);
	const btnRef = ref || localRef;

	const Tag = to ? Link : 'button';

	const tagProps = {
		to: (to || undefined),
		className: (className ? `btn ${className}` : 'btn'),
		...props
	}


	useEffect(() => {

		const textElement = btnRef.current.querySelector('span');
		if(!textElement) return;
		
		let canEnter = true;

		let anim1 = gsap.timeline({
			onComplete: () => {

				canEnter = true;

				anim1?.restart();
				anim1?.paused(true);

			}
		});

		anim1
		?.to(textElement, .1, {
			y: 10,
			opacity: 0
		})
		.set(textElement, {
			y: -10
		})
		.to(textElement, .1, {
			y: 0,
			opacity: 1
		})
		.paused(true);



		const handleMouseEnter = () => {

			if(!canEnter) return;

			canEnter = false;

			anim1?.play();

		}


		btnRef.current.addEventListener('mouseenter', handleMouseEnter);


		return () => {

			btnRef.current?.removeEventListener('mouseenter', handleMouseEnter);

			if(anim1){
				anim1?.kill();
				anim1 = null;
			}

		}

	}, []);

	return(
		<Tag ref={btnRef} {...tagProps}>
			{before && (<div className="btn-before">{parse(before)}</div>)}
			{text && (<span>{parse(text)}</span>)}
			{after && (<div className="btn-after">{parse(after)}</div>)}
		</Tag>
	);

});

export default Button;