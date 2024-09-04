'use strict';

const panelElement = document.getElementById('preloader');

const Loader = {
	start: () => {
		console.log('start');
	},
	leave: () => {
		console.log('leave');
	},
	enter: () => {
		console.log('enter');
	},
	download: (what = null) => {

	}
}

export default Loader;