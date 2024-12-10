'use strict';

const Consent = {

    init: () => {

        if(!SYSTEM.consentActive){
             
            if(localStorage.getItem('consent'))
                localStorage.removeItem('consent');

            if(localStorage.getItem('consentVersion'))
                localStorage.removeItem('consentVersion');

            if(localStorage.getItem('consentExpiration'))
                localStorage.removeItem('consentExpiration');


            return;
        }


        const panelElement = document.getElementById('consent-panel');
        if(!panelElement) return;

        const panelBox = document.getElementById('consent-box');
        const panelButton = document.getElementById('consent-button');

        const buttons = panelBox.querySelectorAll('.contents .buttons button');


        const currentTime = Math.floor(Date.now() / 1000);


        if(
            localStorage.getItem('consent')

            && localStorage.getItem('consentVersion')

            && localStorage.getItem('consentExpiration')

            && +localStorage.getItem('consentExpiration') > currentTime

            && localStorage.getItem('consentVersion') == SYSTEM.consentVersion

        ){

            panelBox.style.display = 'none';
            panelButton.style.display = 'flex';

        } else {

            panelBox.style.display = 'flex';
            panelButton.style.display = 'none';

        }


        buttons.forEach(button => {

            button.addEventListener('click', () => {


                if(button.classList.contains('accept'))
                    Consent.action(true);
                else if(button.classList.contains('reject'))
                    Consent.action(false);

                    
                panelBox.style.display = 'none';
                panelButton.style.display = 'flex';

            });

            panelButton.addEventListener('click', () => {

                panelBox.style.display = 'flex';
                panelButton.style.display = 'none';

            });

        });

    },
    action: (agreed) => {

        const currentTime = Math.floor(Date.now() / 1000);

        localStorage.setItem('consent', +agreed);
        localStorage.setItem('consentVersion', +SYSTEM.consentVersion);
        localStorage.setItem('consentExpiration', (currentTime + +SYSTEM.consentExpiration));

    }

}

Consent.init();