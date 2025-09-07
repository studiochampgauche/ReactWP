'use strict';
import React, { useEffect, useRef } from 'react';
import { Link } from 'react-router-dom';
import { gsap } from 'gsap';
import { Observer } from 'gsap/Observer';

const Image = ({ className = null, ...props }) => {

	const ref = useRef(null);

	const tagProps = {
		className: (className ? `img-container ${className}` : 'img-container'),
		...props
	}

	useEffect(() => {

		gsap.registerPlugin(Observer);

		Observer.create({
			axis: 'x',
			lockAxis: true,
			dragMinimum: 20,
			target: ref.current,
			type: 'pointer, touch',
			onRight: () => {
				console.log('right');
			},
			onLeft: () => {
				console.log('left');
			}
		});

	});

	return(
		<section ref={ref} style={{
			height: '50vh'
		}}>
			<p>One-two testing.</p>
		</section>
	);

}

export default Image;