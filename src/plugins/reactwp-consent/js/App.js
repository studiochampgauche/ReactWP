'use strict';


const Consent = {
	panel: document.getElementById('consent-panel'),
	box: document.getElementById('consent-box'),
	btn: document.getElementById('consent-btn'),
	init: function(){

		const currentTime = Math.floor(Date.now() / 1000);

		if(
            localStorage.getItem('consent')

            && localStorage.getItem('consentVersion')

            && localStorage.getItem('consentExpiration')

            && +localStorage.getItem('consentExpiration') > currentTime

            && localStorage.getItem('consentVersion') == RWP_CONSENT_BOX.version

        ){

            this.box.style.display = 'none';
            this.btn.style.display = 'flex';

        } else {

            this.box.style.display = 'flex';
            this.btn.style.display = 'none';

            localStorage.removeItem('consent');
            localStorage.removeItem('consentVersion');
            localStorage.removeItem('consentExpiration');

        }


		const buttons = this.box.querySelectorAll('.buttons .btn');

		buttons?.forEach((button, i) => {

			button.addEventListener('click', () => {

				if(i === 0)
					this.action(true);
				else
					this.action(false);

				this.box.style.display = 'none';
                this.btn.style.display = 'flex';

			});

		});


		this.btn.addEventListener('click', () => {

            this.box.style.display = 'flex';
            this.btn.style.display = 'none';

        });


	},
	action: (agreed) => {


		const currentTime = Math.floor(Date.now() / 1000);

        localStorage.setItem('consent', +agreed);
        localStorage.setItem('consentVersion', RWP_CONSENT_BOX.version);
        localStorage.setItem('consentExpiration', (currentTime + +RWP_CONSENT_BOX.expiration));

	}
};

Consent.init();