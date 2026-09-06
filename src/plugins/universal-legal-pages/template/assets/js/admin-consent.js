(function(window, document){
    'use strict';

    function initializeSectionSwitcher(container){
        if(!container || container.__ulpSectionSwitcherReady === true){
            return;
        }

        var navigation = container.querySelector('[data-ulp-section-navigation]');
        var select = container.querySelector('[data-ulp-section-select]');
        var panels = Array.prototype.slice.call(container.querySelectorAll('[data-ulp-section-panel]'));

        if(!navigation || !select || panels.length === 0 || select.options.length !== panels.length){
            return;
        }

        function activatePanel(index){
            var activeIndex = index >= 0 && index < panels.length ? index : 0;

            panels.forEach(function(panel, panelIndex){
                panel.hidden = panelIndex !== activeIndex;
            });

            select.selectedIndex = activeIndex;
        }

        select.addEventListener('change', function(){
            activatePanel(select.selectedIndex);
        });

        var form = typeof container.closest === 'function' ? container.closest('form') : null;
        var invalidActivationPending = false;

        if(form && typeof form.addEventListener === 'function'){
            form.addEventListener('invalid', function(event){
                if(invalidActivationPending){
                    return;
                }

                var panelIndex = panels.findIndex(function(panel){
                    return typeof panel.contains === 'function' && panel.contains(event.target);
                });

                if(panelIndex < 0){
                    return;
                }

                invalidActivationPending = true;
                activatePanel(panelIndex);

                if(typeof window.setTimeout === 'function'){
                    window.setTimeout(function(){
                        invalidActivationPending = false;
                    }, 0);
                }
            }, true);
        }

        container.classList.add('is-enhanced');
        container.__ulpSectionSwitcherReady = true;
        navigation.hidden = false;
        activatePanel(select.selectedIndex);
    }

    var sectionSwitchers = document.querySelectorAll('[data-ulp-section-switcher]');

    Array.prototype.forEach.call(sectionSwitchers, function(container){
        initializeSectionSwitcher(container);
    });

    function initializeLanguageEditor(editor){
        if(!editor || editor.__ulpLanguageReady === true){
            return;
        }

        var tabs = Array.prototype.slice.call(editor.querySelectorAll('[data-ulp-language-tab]'));
        var panels = Array.prototype.slice.call(editor.querySelectorAll('[data-ulp-language-panel]'));

        if(tabs.length < 2 || tabs.length !== panels.length){
            return;
        }

        function activateTab(index, moveFocus){
            tabs.forEach(function(tab, tabIndex){
                var selected = tabIndex === index;

                tab.setAttribute('aria-selected', selected ? 'true' : 'false');
                tab.setAttribute('tabindex', selected ? '0' : '-1');
                panels[tabIndex].hidden = !selected;
            });

            if(moveFocus){
                tabs[index].focus();
            }
        }

        tabs.forEach(function(tab, index){
            tab.addEventListener('click', function(){
                activateTab(index, false);
            });

            tab.addEventListener('keydown', function(event){
                var nextIndex = null;

                if(event.key === 'ArrowLeft'){
                    nextIndex = index === 0 ? tabs.length - 1 : index - 1;
                }else if(event.key === 'ArrowRight'){
                    nextIndex = index === tabs.length - 1 ? 0 : index + 1;
                }else if(event.key === 'Home'){
                    nextIndex = 0;
                }else if(event.key === 'End'){
                    nextIndex = tabs.length - 1;
                }

                if(nextIndex === null){
                    return;
                }

                event.preventDefault();
                activateTab(nextIndex, true);
            });
        });

        var selectedIndex = tabs.findIndex(function(tab){
            return tab.getAttribute('aria-selected') === 'true';
        });

        editor.classList.add('is-enhanced');
        editor.__ulpLanguageReady = true;
        activateTab(selectedIndex >= 0 ? selectedIndex : 0, false);
    }

    var editors = document.querySelectorAll('[data-ulp-language-editor]');

    Array.prototype.forEach.call(editors, function(editor){
        initializeLanguageEditor(editor);
    });

    var pageOrders = document.querySelectorAll('[data-ulp-page-order]');

    Array.prototype.forEach.call(pageOrders, function(pageOrder){
        var selectedList = pageOrder.querySelector('[data-ulp-selected-pages]');
        var availableList = pageOrder.querySelector('[data-ulp-available-pages]');
        var selectedEmpty = pageOrder.querySelector('[data-ulp-selected-empty]');
        var availableEmpty = pageOrder.querySelector('[data-ulp-available-empty]');
        var status = pageOrder.querySelector('[data-ulp-page-status]');
        var draggedItem = null;

        if(!selectedList || !availableList || !status){
            return;
        }

        function items(list){
            return Array.prototype.slice.call(list.querySelectorAll('[data-ulp-page-item]'));
        }

        function announce(item, messageAttribute){
            var label = item.getAttribute('data-page-label') || '';
            var message = pageOrder.getAttribute(messageAttribute) || '';
            status.textContent = label && message ? label + ' — ' + message + '.' : '';
        }

        function updateState(){
            var selectedItems = items(selectedList);
            var availableItems = items(availableList);

            selectedItems.forEach(function(item, index){
                var position = item.querySelector('[data-ulp-page-position]');
                var up = item.querySelector('[data-ulp-page-up]');
                var down = item.querySelector('[data-ulp-page-down]');

                item.setAttribute('draggable', 'true');

                if(position){
                    position.textContent = String(index + 1);
                }

                if(up){
                    up.disabled = index === 0;
                }

                if(down){
                    down.disabled = index === selectedItems.length - 1;
                }
            });

            availableItems.forEach(function(item){
                var position = item.querySelector('[data-ulp-page-position]');
                var up = item.querySelector('[data-ulp-page-up]');
                var down = item.querySelector('[data-ulp-page-down]');

                item.removeAttribute('draggable');

                if(position){
                    position.textContent = '';
                }

                if(up){
                    up.disabled = true;
                }

                if(down){
                    down.disabled = true;
                }
            });

            if(selectedEmpty){
                selectedEmpty.hidden = selectedItems.length > 0;
            }

            if(availableEmpty){
                availableEmpty.hidden = availableItems.length > 0;
            }
        }

        function sortAvailable(){
            items(availableList)
                .sort(function(first, second){
                    return (first.getAttribute('data-page-label') || '').localeCompare(
                        second.getAttribute('data-page-label') || '',
                        undefined,
                        {sensitivity: 'base'}
                    );
                })
                .forEach(function(item){
                    availableList.appendChild(item);
                });
        }

        pageOrder.addEventListener('change', function(event){
            var toggle = event.target.closest('[data-ulp-page-toggle]');

            if(!toggle || !pageOrder.contains(toggle)){
                return;
            }

            var item = toggle.closest('[data-ulp-page-item]');

            if(!item){
                return;
            }

            if(toggle.checked){
                selectedList.appendChild(item);
                announce(item, 'data-added-message');
            }else{
                availableList.appendChild(item);
                sortAvailable();
                announce(item, 'data-removed-message');
            }

            updateState();
        });

        pageOrder.addEventListener('click', function(event){
            var up = event.target.closest('[data-ulp-page-up]');
            var down = event.target.closest('[data-ulp-page-down]');
            var action = up || down;

            if(!action || action.disabled || !pageOrder.contains(action)){
                return;
            }

            var item = action.closest('[data-ulp-page-item]');

            if(!item || item.parentNode !== selectedList){
                return;
            }

            if(up && item.previousElementSibling){
                selectedList.insertBefore(item, item.previousElementSibling);
            }else if(down && item.nextElementSibling){
                selectedList.insertBefore(item.nextElementSibling, item);
            }else{
                return;
            }

            updateState();
            announce(item, 'data-moved-message');
            action.focus();
        });

        selectedList.addEventListener('dragstart', function(event){
            var item = event.target.closest('[data-ulp-page-item]');

            if(!item || item.parentNode !== selectedList){
                return;
            }

            draggedItem = item;
            item.classList.add('is-dragging');
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', item.getAttribute('data-page-id') || '');
        });

        selectedList.addEventListener('dragover', function(event){
            if(!draggedItem){
                return;
            }

            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';

            var target = event.target.closest('[data-ulp-page-item]');

            if(target === draggedItem){
                return;
            }

            if(!target || target.parentNode !== selectedList){
                selectedList.appendChild(draggedItem);
                return;
            }

            var bounds = target.getBoundingClientRect();
            var insertAfter = event.clientY > bounds.top + bounds.height / 2;
            selectedList.insertBefore(draggedItem, insertAfter ? target.nextSibling : target);
        });

        selectedList.addEventListener('drop', function(event){
            if(draggedItem){
                event.preventDefault();
            }
        });

        selectedList.addEventListener('dragend', function(){
            if(!draggedItem){
                return;
            }

            draggedItem.classList.remove('is-dragging');
            updateState();
            announce(draggedItem, 'data-moved-message');
            draggedItem = null;
        });

        pageOrder.classList.add('is-enhanced');
        updateState();
    });

    var refreshCategorySelects = function(){};
    var categoryEditor = typeof document.querySelector === 'function'
        ? document.querySelector('[data-ulc-categories]')
        : null;

    if(categoryEditor){
        var categoryList = categoryEditor.querySelector('[data-ulc-category-list]');
        var categoryTemplate = categoryEditor.querySelector('[data-ulc-category-template]');
        var addCategoryButton = categoryEditor.querySelector('[data-ulc-add-category]');
        var categoryStatus = categoryEditor.querySelector('[data-ulc-category-status]');
        var categoryMaximum = Number.parseInt(categoryEditor.getAttribute('data-max') || '16', 10);
        var unclassifiedLabel = categoryEditor.getAttribute('data-unclassified-label') || 'Unclassified';
        var notSelectedLabel = categoryEditor.getAttribute('data-not-selected-label') || 'Not selected';
        var nextCategoryIndex = 0;

        function categoryCards(){
            return categoryList
                ? Array.prototype.slice.call(categoryList.querySelectorAll('[data-ulc-category-card]'))
                : [];
        }

        function categoryLabel(card){
            var labels = card
                ? Array.prototype.slice.call(card.querySelectorAll('[data-ulc-category-label]'))
                : [];
            var label = labels.find(function(candidate){
                var panel = candidate.closest('[data-ulp-language-panel]');

                return !panel || panel.hidden === false;
            }) || labels[0] || null;

            return label ? label.value.trim() : '';
        }

        function categoryDefinitions(){
            return categoryCards().map(function(card){
                var id = card.querySelector('[data-ulc-category-id]');
                var title = card.querySelector('[data-ulc-category-title]');
                var label = categoryLabel(card);

                return {
                    id: id ? id.value : '',
                    label: label || (title ? title.textContent.trim() : '') || (id ? id.value : '')
                };
            }).filter(function(definition){
                return definition.id !== '';
            });
        }

        function createCategoryId(){
            var parts = new Uint32Array(2);

            if(window.crypto && typeof window.crypto.getRandomValues === 'function'){
                window.crypto.getRandomValues(parts);
            }else{
                parts[0] = Date.now() >>> 0;
                parts[1] = Math.floor(Math.random() * 0xFFFFFFFF) >>> 0;
            }

            return 'category-' + Array.prototype.map.call(parts, function(part){
                return part.toString(16).padStart(8, '0');
            }).join('');
        }

        function announceCategory(messageAttribute, label){
            if(!categoryStatus){
                return;
            }

            var message = categoryEditor.getAttribute(messageAttribute) || '';
            categoryStatus.textContent = label && message ? label + ' — ' + message : message;
        }

        function replaceCategoryIndex(element, index){
            ['name', 'id', 'for', 'aria-controls', 'aria-labelledby'].forEach(function(attribute){
                var value = element.getAttribute(attribute) || '';

                if(value.indexOf('__INDEX__') !== -1){
                    value = value.replaceAll('__INDEX__', String(index));
                }

                value = value.replace(
                    /ulp-consent-category-[0-9]+-/,
                    'ulp-consent-category-' + index + '-'
                );
                value = value.replace(
                    /\[consent_categories\]\[[0-9]+\]/,
                    '[consent_categories][' + index + ']'
                );

                if(value !== ''){
                    element.setAttribute(attribute, value);
                }
            });
        }

        function reindexCategoryCards(){
            categoryCards().forEach(function(card, index){
                replaceCategoryIndex(card, index);
                card.querySelectorAll('[name], [id], label[for], [aria-controls], [aria-labelledby]').forEach(function(element){
                    replaceCategoryIndex(element, index);
                });
            });

            nextCategoryIndex = categoryCards().length;
        }

        function updateCategoryCardTitle(card){
            var title = card ? card.querySelector('[data-ulc-category-title]') : null;

            if(!title){
                return;
            }

            title.textContent = categoryLabel(card)
                || card.getAttribute('data-ulc-default-title')
                || title.textContent;
        }

        function updateCategoryState(){
            if(!addCategoryButton){
                return;
            }

            var atLimit = categoryCards().length >= categoryMaximum;
            addCategoryButton.disabled = atLimit;
            addCategoryButton.setAttribute('aria-disabled', atLimit ? 'true' : 'false');
        }

        refreshCategorySelects = function(){
            var definitions = categoryDefinitions();

            document.querySelectorAll('[data-ulc-category-select]').forEach(function(select){
                var kind = select.getAttribute('data-ulc-category-select');
                var current = select.value;
                var allowed = definitions.slice();

                while(select.firstChild){
                    select.removeChild(select.firstChild);
                }

                if(kind === 'integration'){
                    var emptyOption = document.createElement('option');
                    emptyOption.value = '';
                    emptyOption.textContent = notSelectedLabel;
                    select.appendChild(emptyOption);
                }

                allowed.forEach(function(definition){
                    var option = document.createElement('option');
                    option.value = definition.id;
                    option.textContent = definition.label;
                    select.appendChild(option);
                });

                if(kind === 'service'){
                    var unclassifiedOption = document.createElement('option');
                    unclassifiedOption.value = 'unclassified';
                    unclassifiedOption.textContent = unclassifiedLabel;
                    select.appendChild(unclassifiedOption);
                }

                var stillAvailable = Array.prototype.some.call(select.options, function(option){
                    return option.value === current;
                });

                select.value = stillAvailable
                    ? current
                    : (kind === 'service' ? 'unclassified' : (select.options[0] ? select.options[0].value : ''));
            });
        };

        categoryCards().forEach(function(card){
            var title = card.querySelector('[data-ulc-category-title]');
            card.setAttribute('data-ulc-default-title', title ? title.textContent : '');
        });

        if(categoryList){
            categoryList.addEventListener('input', function(event){
                var label = event.target.closest('[data-ulc-category-label]');

                if(label && categoryList.contains(label)){
                    updateCategoryCardTitle(label.closest('[data-ulc-category-card]'));
                    refreshCategorySelects();
                }
            });

            categoryList.addEventListener('click', function(event){
                var removeButton = event.target.closest('[data-ulc-remove-category]');

                if(!removeButton || !categoryList.contains(removeButton)){
                    return;
                }

                var card = removeButton.closest('[data-ulc-category-card]');

                if(!card || card.getAttribute('data-category-protected') === 'true'){
                    return;
                }

                var label = categoryLabel(card);
                card.parentNode.removeChild(card);
                reindexCategoryCards();
                refreshCategorySelects();
                updateCategoryState();
                announceCategory('data-removed-message', label);

                if(addCategoryButton){
                    addCategoryButton.focus();
                }
            });
        }

        if(categoryList && categoryTemplate && categoryTemplate.content && addCategoryButton){
            addCategoryButton.addEventListener('click', function(){
                if(categoryCards().length >= categoryMaximum){
                    announceCategory('data-limit-message', '');
                    updateCategoryState();
                    return;
                }

                var fragment = categoryTemplate.content.cloneNode(true);
                var id = createCategoryId();

                fragment.querySelectorAll('*').forEach(function(element){
                    ['name', 'id', 'for', 'aria-controls', 'aria-labelledby', 'data-category-id'].forEach(function(attribute){
                        var value = element.getAttribute(attribute);

                        if(value){
                            element.setAttribute(
                                attribute,
                                value.replaceAll('__INDEX__', String(nextCategoryIndex)).replaceAll('__CATEGORY_ID__', id)
                            );
                        }
                    });

                    if(element.matches('[data-ulc-category-id]')){
                        element.value = id;
                    }
                });

                categoryList.appendChild(fragment);
                reindexCategoryCards();

                var card = categoryCards().slice(-1)[0];

                if(card){
                    var title = card.querySelector('[data-ulc-category-title]');
                    card.setAttribute('data-ulc-default-title', title ? title.textContent : '');
                    card.querySelectorAll('[data-ulp-language-editor]').forEach(initializeLanguageEditor);
                    var label = card.querySelector('[data-ulc-category-label]');
                    announceCategory('data-added-message', title ? title.textContent : '');

                    if(label){
                        label.focus();
                    }
                }

                refreshCategorySelects();
                updateCategoryState();
            });
        }

        categoryEditor.classList.add('is-enhanced');
        reindexCategoryCards();
        refreshCategorySelects();
        updateCategoryState();

        var categoryForm = categoryEditor.closest('form');

        if(categoryForm){
            categoryForm.addEventListener('submit', reindexCategoryCards);
        }
    }

    var customIntegrationEditors = document.querySelectorAll('[data-ulc-custom-integrations]');

    Array.prototype.forEach.call(customIntegrationEditors, function(editor){
        var list = editor.querySelector('[data-ulc-custom-list]');
        var template = editor.querySelector('[data-ulc-custom-template]');
        var addButton = editor.querySelector('[data-ulc-add-custom]');
        var status = editor.querySelector('[data-ulc-custom-status]');
        var maximum = Number.parseInt(editor.getAttribute('data-max') || '12', 10);
        var nextIndex = 0;

        if(!list || !template || !template.content || !addButton || !Number.isFinite(maximum) || maximum < 1){
            return;
        }

        function cards(){
            return Array.prototype.slice.call(list.querySelectorAll('[data-ulc-custom-card]'));
        }

        function announce(messageAttribute, label){
            if(!status){
                return;
            }

            var message = editor.getAttribute(messageAttribute) || '';
            status.textContent = label && message ? label + ' — ' + message : message;
        }

        function updateState(){
            var atLimit = cards().length >= maximum;
            addButton.disabled = atLimit;
            addButton.setAttribute('aria-disabled', atLimit ? 'true' : 'false');
        }

        function reindexCards(){
            cards().forEach(function(card, index){
                card.querySelectorAll('[name]').forEach(function(field){
                    var name = field.getAttribute('name') || '';
                    field.setAttribute(
                        'name',
                        name.replace(/\[custom_integrations\]\[[0-9]+\]/, '[custom_integrations][' + index + ']')
                    );
                });

                card.querySelectorAll('[id], label[for]').forEach(function(element){
                    ['id', 'for'].forEach(function(attribute){
                        var value = element.getAttribute(attribute) || '';

                        if(value.indexOf('ulp-custom-integration-') === 0){
                            element.setAttribute(
                                attribute,
                                value.replace(/^ulp-custom-integration-[0-9]+-/, 'ulp-custom-integration-' + index + '-')
                            );
                        }
                    });
                });
            });

            nextIndex = cards().length;
        }

        function updateCardTitle(card){
            var input = card.querySelector('[data-ulc-custom-label]');
            var title = card.querySelector('[data-ulc-custom-title]');

            if(!input || !title){
                return;
            }

            var label = input.value.trim();
            var fallback = card.getAttribute('data-ulc-default-title') || title.textContent;
            title.textContent = label || fallback;
        }

        cards().forEach(function(card, index){
            var title = card.querySelector('[data-ulc-custom-title]');
            card.setAttribute('data-ulc-default-title', title ? title.textContent : '');
            nextIndex = Math.max(nextIndex, index + 1);
        });

        addButton.addEventListener('click', function(){
            if(cards().length >= maximum){
                announce('data-limit-message', '');
                updateState();
                return;
            }

            var fragment = template.content.cloneNode(true);
            var indexedElements = fragment.querySelectorAll('[name], [id], label[for]');

            Array.prototype.forEach.call(indexedElements, function(element){
                ['name', 'id', 'for'].forEach(function(attribute){
                    var value = element.getAttribute(attribute);

                    if(value && value.indexOf('__INDEX__') !== -1){
                        element.setAttribute(attribute, value.replaceAll('__INDEX__', String(nextIndex)));
                    }
                });
            });

            nextIndex += 1;
            list.appendChild(fragment);
            reindexCards();
            refreshCategorySelects();

            var card = cards().slice(-1)[0];

            if(!card){
                updateState();
                return;
            }

            var title = card.querySelector('[data-ulc-custom-title]');
            var label = card.querySelector('[data-ulc-custom-label]');
            card.setAttribute('data-ulc-default-title', title ? title.textContent : '');
            announce('data-added-message', title ? title.textContent : '');
            updateState();

            if(label){
                label.focus();
            }
        });

        list.addEventListener('input', function(event){
            var label = event.target.closest('[data-ulc-custom-label]');

            if(!label || !list.contains(label)){
                return;
            }

            var card = label.closest('[data-ulc-custom-card]');

            if(card){
                updateCardTitle(card);
            }
        });

        list.addEventListener('click', function(event){
            var removeButton = event.target.closest('[data-ulc-remove-custom]');

            if(!removeButton || !list.contains(removeButton)){
                return;
            }

            var card = removeButton.closest('[data-ulc-custom-card]');

            if(!card){
                return;
            }

            var title = card.querySelector('[data-ulc-custom-title]');
            var label = title ? title.textContent : '';
            card.parentNode.removeChild(card);
            reindexCards();
            announce('data-removed-message', label);
            updateState();
            addButton.focus();
        });

        editor.classList.add('is-enhanced');
        var form = editor.closest('form');

        if(form){
            form.addEventListener('submit', reindexCards);
        }

        reindexCards();
        updateState();
    });

    var settingsForm = typeof document.querySelector === 'function'
        ? document.querySelector('[data-ulp-settings-form]')
        : null;
    var saveNotice = typeof document.querySelector === 'function'
        ? document.querySelector('[data-ulp-save-notice]')
        : null;

    if(
        settingsForm
        && saveNotice
        && typeof fetch === 'function'
        && typeof FormData === 'function'
    ){
        var saveMessage = saveNotice.querySelector('[data-ulp-save-message]');
        var dismissSaveNotice = saveNotice.querySelector('[data-ulp-save-dismiss]');
        var submitControl = settingsForm.querySelector('[type="submit"]');
        var ajaxUrl = settingsForm.getAttribute('data-ulp-ajax-url') || '';
        var ajaxAction = settingsForm.getAttribute('data-ulp-ajax-action') || '';
        var savingLabel = settingsForm.getAttribute('data-ulp-saving-label') || '';
        var networkError = settingsForm.getAttribute('data-ulp-network-error') || '';
        var saving = false;
        var submitLabel = submitControl
            ? ((submitControl.tagName || '').toLowerCase() === 'input' ? submitControl.value : submitControl.textContent)
            : '';

        function setSubmitLabel(value){
            if(!submitControl){
                return;
            }

            if((submitControl.tagName || '').toLowerCase() === 'input'){
                submitControl.value = value;
            }else{
                submitControl.textContent = value;
            }
        }

        function showSaveNotice(message, state){
            if(!saveMessage){
                return;
            }

            saveMessage.textContent = message;
            saveNotice.classList.remove(
                'ulp-admin__save-notice--pending',
                'ulp-admin__save-notice--success',
                'ulp-admin__save-notice--error'
            );
            saveNotice.classList.add('ulp-admin__save-notice--' + state);
            saveNotice.setAttribute('role', state === 'error' ? 'alert' : 'status');
            saveNotice.setAttribute('aria-live', state === 'error' ? 'assertive' : 'polite');
            saveNotice.hidden = false;

            if(state === 'error' && typeof saveNotice.focus === 'function'){
                saveNotice.focus();
            }
        }

        function synchronizeCustomIntegrationIds(records){
            if(!Array.isArray(records)){
                return;
            }

            var idsByIndex = {};

            records.forEach(function(record){
                if(record && Number.isInteger(record.index) && typeof record.id === 'string'){
                    idsByIndex[record.index] = record.id;
                }
            });

            settingsForm.querySelectorAll('[data-ulc-custom-card]').forEach(function(card, index){
                var idInput = card.querySelector('[data-ulc-custom-id]');
                var savedId = idsByIndex[index] || '';

                if(idInput && savedId){
                    idInput.value = savedId;
                }
            });
        }

        function finishSave(){
            saving = false;
            settingsForm.removeAttribute('aria-busy');

            if(submitControl){
                submitControl.disabled = false;
                setSubmitLabel(submitLabel);
            }
        }

        if(dismissSaveNotice){
            dismissSaveNotice.addEventListener('click', function(){
                saveNotice.hidden = true;
            });
        }

        settingsForm.addEventListener('submit', function(event){
            if(event.defaultPrevented || saving || ajaxUrl === '' || ajaxAction === ''){
                return;
            }

            event.preventDefault();
            saving = true;
            settingsForm.setAttribute('aria-busy', 'true');

            if(submitControl){
                submitControl.disabled = true;
                setSubmitLabel(savingLabel || submitLabel);
            }

            if(typeof document.querySelectorAll === 'function'){
                document.querySelectorAll('.ulp-admin > [id^="setting-error-"]').forEach(function(notice){
                    notice.hidden = true;
                });
            }

            showSaveNotice(savingLabel || submitLabel, 'pending');

            var request = new FormData(settingsForm);
            request.set('action', ajaxAction);

            fetch(ajaxUrl, {
                method: 'POST',
                body: request,
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function(response){
                    return response.json();
                })
                .then(function(result){
                    var data = result && result.data && typeof result.data === 'object'
                        ? result.data
                        : {};
                    var errors = Array.isArray(data.errors)
                        ? data.errors.map(function(error){
                            return error && typeof error.message === 'string' ? error.message : '';
                        }).filter(Boolean)
                        : [];

                    synchronizeCustomIntegrationIds(data.customIntegrations);

                    if(result && result.success === true){
                        showSaveNotice(typeof data.message === 'string' ? data.message : submitLabel, 'success');
                        return;
                    }

                    showSaveNotice(
                        errors.length > 0
                            ? errors.join(' ')
                            : (typeof data.message === 'string' ? data.message : networkError),
                        'error'
                    );
                })
                .catch(function(){
                    showSaveNotice(networkError, 'error');
                })
                .then(finishSave, finishSave);
        });
    }

})(typeof window !== 'undefined' ? window : {}, document);
