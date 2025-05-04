'use strict';
import React, { useEffect, useRef } from 'react';
import { Link } from 'react-router-dom';
import { gsap } from 'gsap';

const Button = ({ to = null, text, className = null, before, after, ...props }) => {

	const Tag = to ? Link : 'button';

	const tagProps = {
		to: (to || undefined),
		className: (className ? `btn ${className}` : 'btn'),
		...props
	}


	const ref = useRef(null);


	useEffect(() => {

		const killEvents = [];

		const arrowElement = ref.current.querySelector('.arrow');

		let anim1 = gsap.timeline();

		if(ref.current.classList.contains('turn')){

			anim1
			.to(arrowElement, .2, {
				width: ((ref.current?.getBoundingClientRect().right - ref?.current.getBoundingClientRect().left) - ((ref.current?.getBoundingClientRect().right - ref.current.querySelector('.arrow')?.getBoundingClientRect().right) * 2)),
			})
			.to(arrowElement.querySelector('svg'), .2, {
				rotate: 90,
				scale: .85
			}, 0)
			.paused(true);

		} else {

			anim1
			.to(arrowElement, .2, {
				width: ((ref.current?.getBoundingClientRect().right - ref?.current.getBoundingClientRect().left) - ((ref.current?.getBoundingClientRect().right - ref.current.querySelector('.arrow')?.getBoundingClientRect().right) * 2)),
			})
			.paused(true);

		}

		const handleMouseEnter = () => {

			anim1.play();
			anim1.reversed(false)

		}

		const handleMouseLeave = () => {

			anim1.play();
			anim1.reversed(true)

		}


		ref.current?.addEventListener('mouseenter', handleMouseEnter);
		ref.current?.addEventListener('mouseleave', handleMouseLeave);


		killEvents.push(() => {

			if(anim1){
				anim1.kill();
				anim1 = null;
			}

			ref.current?.removeEventListener('mouseenter', handleMouseEnter);
			ref.current?.removeEventListener('mouseleave', handleMouseLeave);

		});

		return () => killEvents?.forEach(killEvent => killEvent());

	}, []);

	return(
		<Tag ref={ref} {...tagProps}>
			{before && (<div className="btn-before" dangerouslySetInnerHTML={{ __html: before }} />)}
			{text && (<span dangerouslySetInnerHTML={{ __html: text }}  />)}
			{after && (<div className="btn-after" dangerouslySetInnerHTML={{ __html: after }} />)}
		</Tag>
	);

}

export default Button;