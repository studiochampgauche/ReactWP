(function(window, document){
    'use strict';

    var SETTINGS_ACTION = 'universal_smtp_save_settings';
    var TEST_ACTION = 'universal_smtp_send_test';
    var FIELD_KEYS = [
        'enabled',
        'host',
        'port',
        'encryption',
        'authenticate',
        'auth_type',
        'username',
        'password',
        'clear_password',
        'from_email',
        'from_name',
        'force_from_email',
        'force_from_name',
        'return_path',
        'timeout',
        'recipient'
    ];

    function ready(callback){
        if(document.readyState === 'loading'){
            document.addEventListener('DOMContentLoaded', callback, {once: true});
            return;
        }

        callback();
    }

    function message(config, key, fallback){
        var messages = config && config.messages && typeof config.messages === 'object'
            ? config.messages
            : {};

        return typeof messages[key] === 'string' && messages[key] !== ''
            ? messages[key]
            : fallback;
    }

    function setControlLabel(control, value){
        if(!control || typeof value !== 'string' || value === ''){
            return;
        }

        if((control.tagName || '').toLowerCase() === 'input'){
            control.value = value;
        }else{
            control.textContent = value;
        }
    }

    function controlLabel(control){
        if(!control){
            return '';
        }

        return (control.tagName || '').toLowerCase() === 'input'
            ? control.value
            : control.textContent;
    }

    function setPending(form, control, pending, pendingLabel, originalLabel){
        if(form){
            if(pending){
                form.setAttribute('aria-busy', 'true');
            }else{
                form.removeAttribute('aria-busy');
            }
        }

        if(control){
            control.disabled = pending;
            control.setAttribute('aria-disabled', pending ? 'true' : 'false');
            setControlLabel(control, pending ? pendingLabel : originalLabel);
        }
    }

    function initialize(root){
        if(!root || root.__universalSmtpReady === true){
            return;
        }

        var config = window.universalSmtpAdmin && typeof window.universalSmtpAdmin === 'object'
            ? window.universalSmtpAdmin
            : (window.UniversalSMTPAdmin && typeof window.UniversalSMTPAdmin === 'object'
                ? window.UniversalSMTPAdmin
                : {});
        var settingsForm = root.querySelector('[data-usmtp-settings-form]');
        var testForm = root.querySelector('[data-usmtp-test-form]');
        var notice = root.querySelector('[data-usmtp-notice]');
        var noticeMessage = notice
            ? (notice.querySelector('[data-usmtp-notice-message]') || notice.querySelector('p'))
            : null;
        var noticeDismiss = notice ? notice.querySelector('[data-usmtp-notice-dismiss]') : null;
        var ajaxUrl = typeof config.ajaxUrl === 'string' ? config.ajaxUrl : '';
        var optionName = typeof config.optionName === 'string' ? config.optionName : '';

        function showNotice(state, content, focus){
            if(!notice || !noticeMessage){
                return;
            }

            noticeMessage.textContent = content;
            notice.classList.remove(
                'usmtp-admin__notice--pending',
                'usmtp-admin__notice--success',
                'usmtp-admin__notice--error'
            );
            notice.classList.add('usmtp-admin__notice--' + state);
            notice.setAttribute('role', state === 'error' ? 'alert' : 'status');
            notice.setAttribute('aria-live', state === 'error' ? 'assertive' : 'polite');
            notice.hidden = false;

            if(focus && typeof notice.focus === 'function'){
                if(!notice.hasAttribute('tabindex')){
                    notice.setAttribute('tabindex', '-1');
                }
                notice.focus();
            }
        }

        function clearFieldErrors(form){
            if(!form){
                return;
            }

            Array.prototype.forEach.call(form.querySelectorAll('[aria-invalid="true"]'), function(field){
                field.removeAttribute('aria-invalid');
            });
        }

        function fieldForKey(form, key){
            if(!form || FIELD_KEYS.indexOf(key) === -1){
                return null;
            }

            var expectedName = optionName !== '' ? optionName + '[' + key + ']' : '';
            var fields = form.querySelectorAll('[name]');

            for(var index = 0; index < fields.length; index += 1){
                var name = fields[index].getAttribute('name') || '';

                if(
                    name === key
                    || name === expectedName
                    || name.slice(-(key.length + 2)) === '[' + key + ']'
                ){
                    return fields[index];
                }
            }

            return null;
        }

        function collectErrors(data){
            var messages = [];
            var fieldErrors = data && data.fieldErrors && typeof data.fieldErrors === 'object'
                ? data.fieldErrors
                : {};

            Object.keys(fieldErrors).forEach(function(key){
                var value = fieldErrors[key];
                var errorMessage = typeof value === 'string'
                    ? value
                    : (value && typeof value.message === 'string' ? value.message : '');

                if(errorMessage !== ''){
                    messages.push(errorMessage);
                }
            });

            if(data && Array.isArray(data.errors)){
                data.errors.forEach(function(error){
                    var errorMessage = typeof error === 'string'
                        ? error
                        : (error && typeof error.message === 'string' ? error.message : '');

                    if(errorMessage !== '' && messages.indexOf(errorMessage) === -1){
                        messages.push(errorMessage);
                    }
                });
            }

            return {messages: messages, fieldErrors: fieldErrors};
        }

        function markFieldErrors(form, fieldErrors){
            var hasFieldError = false;

            Object.keys(fieldErrors || {}).forEach(function(key){
                var field = fieldForKey(form, key);

                if(field){
                    field.setAttribute('aria-invalid', 'true');
                    hasFieldError = true;
                }
            });

            return hasFieldError;
        }

        function request(form, action, nonce){
            var body = new FormData(form);

            body.set('action', action);
            body.set('nonce', nonce);

            return fetch(ajaxUrl, {
                method: 'POST',
                body: body,
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(function(response){
                return response.json();
            });
        }

        if(noticeDismiss){
            noticeDismiss.addEventListener('click', function(){
                notice.hidden = true;
            });
        }

        if(settingsForm){
            var authToggle = settingsForm.querySelector('[name$="[authenticate]"]');
            var authGroups = settingsForm.querySelectorAll('[data-usmtp-auth-fields]');
            var passwordInput = settingsForm.querySelector('[name$="[password]"]');
            var clearPassword = settingsForm.querySelector('[name$="[clear_password]"]');
            var passwordStatus = settingsForm.querySelector('[data-usmtp-password-status]');
            var passwordManaged = Boolean(passwordInput && passwordInput.disabled && !clearPassword);

            function synchronizePasswordStatus(configured, managed){
                if(!passwordStatus || typeof configured !== 'boolean'){
                    return;
                }

                var state = managed ? 'managed' : (configured ? 'configured' : 'empty');

                passwordStatus.classList.toggle('is-managed', state === 'managed');
                passwordStatus.classList.toggle('is-configured', state === 'configured');
                passwordStatus.classList.toggle('is-empty', state === 'empty');
                passwordStatus.setAttribute('data-status', state);

                Array.prototype.forEach.call(
                    passwordStatus.querySelectorAll('[data-usmtp-password-state]'),
                    function(stateElement){
                        stateElement.hidden = stateElement.getAttribute('data-usmtp-password-state') !== passwordStatus.getAttribute('data-status');
                    }
                );
            }

            function synchronizeAuthentication(){
                var authenticated = Boolean(authToggle && authToggle.checked);

                Array.prototype.forEach.call(authGroups, function(group){
                    group.hidden = false;
                    group.removeAttribute('aria-hidden');
                    group.classList.toggle('is-authentication-disabled', !authenticated);

                    Array.prototype.forEach.call(group.querySelectorAll('input, select, textarea, button'), function(control){
                        control.disabled = !authenticated && control !== clearPassword;
                    });
                });

                if(passwordInput){
                    passwordInput.disabled = !authenticated
                        || passwordManaged
                        || Boolean(clearPassword && clearPassword.checked);
                    passwordInput.setAttribute(
                        'aria-disabled',
                        passwordInput.disabled ? 'true' : 'false'
                    );
                }
            }

            if(authToggle){
                authToggle.addEventListener('change', synchronizeAuthentication);
            }

            if(clearPassword){
                clearPassword.addEventListener('change', synchronizeAuthentication);
            }

            if(passwordInput){
                passwordInput.addEventListener('input', function(){
                    if(passwordInput.value !== '' && clearPassword && clearPassword.checked){
                        clearPassword.checked = false;
                        synchronizeAuthentication();
                    }
                });
            }

            synchronizeAuthentication();

            var saveControl = settingsForm.querySelector('[data-usmtp-save]');
            var saveLabel = controlLabel(saveControl);
            var saving = false;

            settingsForm.addEventListener('submit', function(event){
                var saveNonce = typeof config.saveNonce === 'string' ? config.saveNonce : '';

                if(saving){
                    event.preventDefault();
                    return;
                }

                if(
                    event.defaultPrevented
                    || ajaxUrl === ''
                    || saveNonce === ''
                    || typeof fetch !== 'function'
                    || typeof FormData !== 'function'
                ){
                    return;
                }

                event.preventDefault();
                saving = true;
                clearFieldErrors(settingsForm);
                setPending(
                    settingsForm,
                    saveControl,
                    true,
                    message(config, 'saving', saveLabel),
                    saveLabel
                );
                showNotice('pending', message(config, 'saving', saveLabel), false);

                request(settingsForm, SETTINGS_ACTION, saveNonce)
                    .then(function(result){
                        var data = result && result.data && typeof result.data === 'object'
                            ? result.data
                            : {};

                        if(result && result.success === true){
                            if(passwordInput){
                                passwordInput.value = '';
                            }
                            if(clearPassword){
                                clearPassword.checked = false;
                            }
                            if(typeof data.passwordManaged === 'boolean'){
                                passwordManaged = data.passwordManaged;
                            }
                            synchronizePasswordStatus(data.passwordConfigured, passwordManaged);
                            synchronizeAuthentication();
                            showNotice(
                                'success',
                                typeof data.message === 'string'
                                    ? data.message
                                    : message(config, 'saved', saveLabel),
                                false
                            );
                            return;
                        }

                        var errors = collectErrors(data);
                        var hasValidationError = markFieldErrors(settingsForm, errors.fieldErrors)
                            || errors.messages.length > 0;

                        showNotice(
                            'error',
                            errors.messages.length > 0
                                ? errors.messages.join(' ')
                                : (typeof data.message === 'string'
                                    ? data.message
                                    : message(config, 'genericError', 'The settings could not be saved.')),
                            hasValidationError
                        );
                    })
                    .catch(function(){
                        showNotice(
                            'error',
                            message(config, 'genericError', 'The server could not be reached.'),
                            false
                        );
                    })
                    .then(function(){
                        saving = false;
                        setPending(settingsForm, saveControl, false, '', saveLabel);
                    }, function(){
                        saving = false;
                        setPending(settingsForm, saveControl, false, '', saveLabel);
                    });
            });
        }

        if(testForm){
            var testControl = testForm.querySelector('[data-usmtp-test]');
            var testLabel = controlLabel(testControl);
            var testing = false;

            testForm.addEventListener('submit', function(event){
                var testNonce = typeof config.testNonce === 'string' ? config.testNonce : '';

                if(testing){
                    event.preventDefault();
                    return;
                }

                if(
                    event.defaultPrevented
                    || ajaxUrl === ''
                    || testNonce === ''
                    || typeof fetch !== 'function'
                    || typeof FormData !== 'function'
                ){
                    return;
                }

                event.preventDefault();
                testing = true;
                clearFieldErrors(testForm);
                setPending(
                    testForm,
                    testControl,
                    true,
                    message(config, 'testing', testLabel),
                    testLabel
                );
                showNotice('pending', message(config, 'testing', testLabel), false);

                request(testForm, TEST_ACTION, testNonce)
                    .then(function(result){
                        var data = result && result.data && typeof result.data === 'object'
                            ? result.data
                            : {};

                        if(result && result.success === true){
                            showNotice(
                                'success',
                                typeof data.message === 'string'
                                    ? data.message
                                    : message(config, 'testSent', testLabel),
                                false
                            );
                            return;
                        }

                        var errors = collectErrors(data);
                        var hasValidationError = markFieldErrors(testForm, errors.fieldErrors)
                            || errors.messages.length > 0;

                        showNotice(
                            'error',
                            errors.messages.length > 0
                                ? errors.messages.join(' ')
                                : (typeof data.message === 'string'
                                    ? data.message
                                    : message(config, 'genericError', 'The test email could not be sent.')),
                            hasValidationError
                        );
                    })
                    .catch(function(){
                        showNotice(
                            'error',
                            message(config, 'genericError', 'The server could not be reached.'),
                            false
                        );
                    })
                    .then(function(){
                        testing = false;
                        setPending(testForm, testControl, false, '', testLabel);
                    }, function(){
                        testing = false;
                        setPending(testForm, testControl, false, '', testLabel);
                    });
            });
        }

        root.classList.add('is-enhanced');
        root.__universalSmtpReady = true;
    }

    ready(function(){
        var root = typeof document.querySelector === 'function'
            ? document.querySelector('.usmtp-admin')
            : null;

        initialize(root);
    });

})(typeof window !== 'undefined' ? window : {}, document);
