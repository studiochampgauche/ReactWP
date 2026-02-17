'use strict';
import React, { useEffect, useRef, forwardRef } from 'react';
import { Link } from 'react-router-dom';
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

		let canEnter = true;

		let anim1 = gsap.timeline({
			onComplete: () => {

				canEnter = true;

				anim1?.restart();
				anim1?.paused(true);

			}
		});

		anim1
		?.to(btnRef.current.querySelector('span'), .1, {
			y: 10,
			opacity: 0
		})
		.set(btnRef.current.querySelector('span'), {
			y: -10
		})
		.to(btnRef.current.querySelector('span'), .1, {
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
			{before && (<div className="btn-before">{before}</div>)}
			{text && (<span>{text}</span>)}
			{after && (<div className="btn-after">{after}</div>)}
		</Tag>
	);

});

export default Button;