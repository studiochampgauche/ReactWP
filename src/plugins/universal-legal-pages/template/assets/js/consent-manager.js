(function(window, document){
    'use strict';

    var API_VERSION = 4;
    var GLOBAL_NAME = 'UniversalLegalConsent';
    var CONFIG_NAME = 'UniversalLegalConsentConfig';
    var READY_EVENT = 'universal-legal-pages:ready';
    var CHANGE_EVENT = 'universal-legal-pages:change';
    var HOST_ATTRIBUTE = 'data-universal-legal-consent-root';
    var OPEN_TRIGGER_ATTRIBUTE = 'data-ulc-open';
    var SERVICE_PURPOSES = ['necessary', 'preferences', 'analytics', 'marketing', 'external'];
    var OPTIONAL_PURPOSES = ['preferences', 'analytics', 'marketing', 'external'];
    var CATEGORY_NAMES = [];
    var OPTIONAL_CATEGORIES = [];
    var SERVICE_KINDS = ['integration', 'iframe', 'script', 'pixel', 'unknown'];
    var MAX_SERVICES = 32;
    var MAX_CUSTOM_INTEGRATIONS = 12;
    var MAX_CUSTOM_SCRIPT_URL_LENGTH = 2048;
    var MAX_CUSTOM_INIT_CODE_BYTES = 8192;
    var MAX_CUSTOM_INTEGRATIONS_BYTES = 131072;
    var MAX_SERVICE_DOMAINS = 8;
    var MAX_SERVICE_PLACEHOLDERS = 256;
    var SERVICE_PLACEHOLDER_SELECTOR = '[data-ulc-service][data-ulc-src]';
    var MAX_LEGAL_LINKS = 1000;
    var MAX_LANGUAGES = 32;
    var CONSENT_STRING_LIMITS = {
        title: 120,
        message: 600,
        legalLinksLabel: 120,
        'actions.acceptAll': 80,
        'actions.rejectAll': 80,
        'actions.customize': 80,
        'actions.save': 80,
        'actions.close': 80,
        'actions.revisit': 80,
        'dialog.title': 120,
        'dialog.description': 500,
        'categories.necessary.label': 80,
        'categories.necessary.description': 300,
        'categories.preferences.label': 80,
        'categories.preferences.description': 300,
        'categories.analytics.label': 80,
        'categories.analytics.description': 300,
        'categories.marketing.label': 80,
        'categories.marketing.description': 300,
        'categories.external.label': 80,
        'categories.external.description': 300,
        'services.title': 120,
        'services.description': 300,
        'services.blocked': 300,
        'services.allow': 80,
        'services.unclassified': 300,
        'terms.label': 160,
        'terms.description': 300,
        'terms.requiredError': 240,
        'gpc.notice': 300,
        'error.generic': 240
    };
    var MULTILINE_STRING_PATHS = [
        'message',
        'dialog.description',
        'categories.necessary.description',
        'categories.preferences.description',
        'categories.analytics.description',
        'categories.marketing.description',
        'categories.external.description',
        'services.description',
        'services.blocked',
        'services.unclassified',
        'terms.description',
        'terms.requiredError',
        'gpc.notice',
        'error.generic'
    ];
    var managerScript = document.currentScript;
    var managerScriptNonce = managerScript && typeof managerScript.nonce === 'string'
        ? managerScript.nonce
        : managerScript && typeof managerScript.getAttribute === 'function'
            ? managerScript.getAttribute('nonce') || ''
            : '';
    var existingGlobal = window[GLOBAL_NAME];

    if(existingGlobal && existingGlobal.__universalLegalConsentVersion === API_VERSION){
        return;
    }

    var queuedIntegrations = collectQueuedIntegrations(existingGlobal);
    var queuedLanguage = collectQueuedLanguage(existingGlobal);
    var config = normalizeConfig(window[CONFIG_NAME]);

    if(!config){
        return;
    }

    CATEGORY_NAMES = config.categoryIds.slice();
    OPTIONAL_CATEGORIES = config.optionalCategoryIds.slice();

    var consentCookieName = window.location.protocol === 'https:'
        ? '__Host-' + config.cookieName
        : config.cookieName;
    var consentCookiePath = window.location.protocol === 'https:'
        ? '/'
        : config.cookiePath;

    var host = null;
    var shadow = null;
    var elements = {};
    var currentConsent = null;
    var integrations = Object.create(null);
    var integrationOrder = [];
    var googleTagInitialized = false;
    var configuredGoogleDestinations = Object.create(null);
    var initialized = false;
    var dialogOpen = false;
    var dialogTrigger = null;
    var inertRecords = [];
    var scrollRecord = null;
    var pendingDialogRequest = null;
    var gpcActive = config.respectGpc && navigator.globalPrivacyControl === true;
    var servicePlaceholders = [];
    var placeholderObserver = null;

    var api = {
        __universalLegalConsentVersion: API_VERSION,
        registerIntegration: registerIntegration,
        getConsent: getConsent,
        hasConsent: hasConsent,
        hasServiceConsent: hasServiceConsent,
        openPreferences: openPreferences,
        setLanguage: setLanguage
    };

    window[GLOBAL_NAME] = api;

    if(queuedLanguage){
        setLanguage(queuedLanguage);
    }

    initializeGoogleConsentMode();
    registerKnownIntegrations();
    registerConfiguredIntegrations();
    processQueuedIntegrations(queuedIntegrations);

    if(document.readyState === 'loading'){
        document.addEventListener('DOMContentLoaded', initialize, {once: true});
    }else{
        initialize();
    }

    function initialize(){

        if(initialized || !document.body){
            return;
        }

        initialized = true;
        currentConsent = readConsentCookie(gpcActive);

        if(currentConsent && gpcActive && (!currentConsent.gpc || currentConsent.marketing || hasGrantedMarketingService(currentConsent))){
            var previousConsent = copyConsent(currentConsent, true);
            var marketingWasGranted = currentConsent.marketing || hasGrantedMarketingService(currentConsent);

            config.activatableServiceIds.forEach(function(serviceId){
                if(config.servicesById[serviceId].purpose === 'marketing'){
                    currentConsent.services[serviceId] = false;
                }
            });
            currentConsent.gpc = true;
            currentConsent.timestamp = new Date().toISOString();
            currentConsent = canonicalConsent(currentConsent);
            writeConsentCookie(currentConsent);

            if(marketingWasGranted){
                clearVendorCookies({analytics: false, googleAds: true, meta: true});
            }

            dispatchDocumentEvent(CHANGE_EVENT, {
                version: API_VERSION,
                consent: copyConsent(currentConsent),
                previousConsent: previousConsent,
                source: 'gpc'
            });
        }

        createInterface();
        renderInterfaceState();
        initializeServicePlaceholders();
        updateGoogleConsentMode(currentConsent);
        applyConsentToIntegrations(currentConsent, null);

        shadow.addEventListener('click', handleShadowClick);
        shadow.addEventListener('change', handleShadowChange);
        shadow.addEventListener('submit', handleShadowSubmit);
        document.addEventListener('click', handleDocumentClick);
        document.addEventListener('keydown', handleDocumentKeydown, true);

        if(pendingDialogRequest){
            var request = pendingDialogRequest;
            pendingDialogRequest = null;
            showPreferences(request.options, request.trigger);
        }

        dispatchDocumentEvent(READY_EVENT, {
            version: API_VERSION,
            consent: copyConsent(currentConsent),
            gpc: gpcActive
        });

    }

    function collectQueuedIntegrations(value){

        if(Array.isArray(value)){
            return value.slice();
        }

        if(value && Array.isArray(value.queue)){
            return value.queue.slice();
        }

        return [];

    }

    function collectQueuedLanguage(value){

        return value && typeof value.pendingLanguage === 'string'
            ? value.pendingLanguage
            : '';

    }

    function processQueuedIntegrations(queue){

        queue.forEach(function(item){

            if(Array.isArray(item) && item[0] === 'registerIntegration'){
                registerIntegration(item[1]);
                return;
            }

            registerIntegration(item);

        });

    }

    function normalizeConfig(raw){

        try{
            if(!isPlainObject(raw) || raw.version !== API_VERSION){
                return null;
            }

            if(
                typeof raw.cookieName !== 'string'
                || !/^[A-Za-z0-9._-]{1,128}$/.test(raw.cookieName)
                || typeof raw.cookiePath !== 'string'
                || raw.cookiePath.charAt(0) !== '/'
                || raw.cookiePath.length > 256
                || /[;\r\n]/.test(raw.cookiePath)
                || typeof raw.durationDays !== 'number'
                || !isFinite(raw.durationDays)
                || Math.floor(raw.durationDays) !== raw.durationDays
                || raw.durationDays < 30
                || raw.durationDays > 365
                || typeof raw.policyVersion !== 'string'
                || !/^[A-Za-z0-9._-]{1,32}$/.test(raw.policyVersion)
                || typeof raw.serviceRegistryVersion !== 'string'
                || !/^[a-f0-9]{24}$/.test(raw.serviceRegistryVersion)
                || typeof raw.respectGpc !== 'boolean'
                || typeof raw.showRevisitButton !== 'boolean'
                || typeof raw.termsRequired !== 'boolean'
            ){
                return null;
            }

            var stylesheetUrl = normalizeStylesheetUrl(raw.stylesheetUrl);

            if(!stylesheetUrl){
                return null;
            }

            var legacyLinks = normalizeLinks(raw.links);
            var legalLinks = typeof raw.legalLinks === 'undefined'
                ? normalizeLegalLinks([
                    legacyLinks.privacy,
                    legacyLinks.cookies,
                    legacyLinks.terms
                ].filter(Boolean))
                : normalizeLegalLinks(raw.legalLinks);
            var termsLink = typeof raw.termsLink === 'undefined'
                ? legacyLinks.terms
                : normalizeLink(raw.termsLink);

            if(legalLinks === null){
                return null;
            }

            var strings = normalizeStrings(raw.strings);
            var categoryRegistry = normalizeCategories(raw.categories);

            if(!categoryRegistry){
                return null;
            }

            var serviceRegistry = normalizeServices(raw.services, categoryRegistry);
            var integrations = normalizeKnownIntegrationIds(raw.integrations);
            var integrationCategories = normalizeKnownIntegrationCategories(
                raw.integrationCategories,
                integrations,
                categoryRegistry.byId
            );
            var stringTranslations = normalizeStringTranslations(raw.stringTranslations);
            var bannerTranslations = normalizeBannerTranslations(raw.bannerTranslations);
            var linkTranslations = normalizeLinkTranslations(raw.linkTranslations);
            var categoryTranslations = normalizeCategoryTranslations(raw.categoryTranslations, categoryRegistry);
            var currentLanguage = normalizeLanguageCode(raw.currentLanguage);
            var resolvedStringLanguage = resolveBannerLanguage(currentLanguage, stringTranslations);
            var resolvedBannerLanguage = resolveBannerLanguage(currentLanguage, bannerTranslations);

            if(linkTranslations === null || categoryTranslations === null || integrationCategories === null){
                return null;
            }

            if(resolvedStringLanguage){
                currentLanguage = resolvedStringLanguage;
                strings = stringTranslations[resolvedStringLanguage];
            }else if(resolvedBannerLanguage){
                currentLanguage = resolvedBannerLanguage;
                strings.title = bannerTranslations[resolvedBannerLanguage].title;
                strings.message = bannerTranslations[resolvedBannerLanguage].message;
            }

            var resolvedLinkLanguage = resolveBannerLanguage(currentLanguage, linkTranslations);

            if(resolvedLinkLanguage){
                legalLinks = linkTranslations[resolvedLinkLanguage].legalLinks;
                termsLink = linkTranslations[resolvedLinkLanguage].termsLink;
            }

            var resolvedCategoryLanguage = resolveBannerLanguage(currentLanguage, categoryTranslations);

            if(resolvedCategoryLanguage){
                categoryRegistry = categoryTranslations[resolvedCategoryLanguage];
            }

            if(raw.termsRequired && (
                !termsLink
                || Object.keys(linkTranslations).some(function(code){
                    return !linkTranslations[code].termsLink;
                })
            )){
                return null;
            }

            if(!serviceRegistry){
                return null;
            }

            var customIntegrations = normalizeConfiguredIntegrations(
                raw.customIntegrations,
                serviceRegistry.byId,
                categoryRegistry.byId
            );

            if(customIntegrations === null){
                return null;
            }

            return {
                version: API_VERSION,
                cookieName: raw.cookieName,
                cookiePath: raw.cookiePath,
                durationDays: raw.durationDays,
                policyVersion: raw.policyVersion,
                serviceRegistryVersion: raw.serviceRegistryVersion,
                respectGpc: raw.respectGpc,
                showRevisitButton: raw.showRevisitButton,
                termsRequired: raw.termsRequired,
                stylesheetUrl: stylesheetUrl,
                legalLinks: legalLinks,
                termsLink: termsLink,
                integrations: integrations,
                integrationCategories: integrationCategories,
                customIntegrations: customIntegrations,
                categories: categoryRegistry.categories,
                categoriesById: categoryRegistry.byId,
                categoryIds: categoryRegistry.ids,
                optionalCategoryIds: categoryRegistry.optionalIds,
                services: serviceRegistry.services,
                servicesById: serviceRegistry.byId,
                serviceIdsByCategory: serviceRegistry.idsByCategory,
                activatableServiceIds: serviceRegistry.activatableIds,
                visitorServiceIds: serviceRegistry.visitorIds,
                strings: strings,
                currentLanguage: currentLanguage,
                bannerTranslations: bannerTranslations,
                stringTranslations: stringTranslations,
                linkTranslations: linkTranslations,
                categoryTranslations: categoryTranslations
            };
        }catch(error){
            return null;
        }

    }

    function normalizeCategories(raw){

        if(!Array.isArray(raw) || !raw.length || raw.length > 16){
            return null;
        }

        var categories = [];
        var byId = Object.create(null);
        var ids = [];
        var optionalIds = [];

        for(var index = 0; index < raw.length; index += 1){
            var definition = raw[index];

            if(!hasExactKeys(definition, ['id', 'label', 'description'])){
                return null;
            }

            var id = typeof definition.id === 'string' ? definition.id : '';
            var label = normalizedString(definition.label, '');
            var description = normalizedString(definition.description, '');

            if(
                !/^(?:necessary|preferences|analytics|marketing|external|category-[a-f0-9]{16})$/.test(id)
                || byId[id]
                || !label
                || unicodeLength(label) > 80
                || /[\r\n]/.test(label)
                || !description
                || unicodeLength(description) > 300
                || /[\u0000-\u0008\u000B\u000C\u000E-\u001F\u007F]/.test(label + description)
                || (id === 'necessary' && index !== 0)
            ){
                return null;
            }

            var category = {
                id: id,
                label: label,
                description: description
            };
            categories.push(category);
            byId[id] = category;
            ids.push(id);
            if(id !== 'necessary'){
                optionalIds.push(id);
            }
        }

        if(!byId.necessary){
            return null;
        }

        return {
            categories: categories,
            byId: byId,
            ids: ids,
            optionalIds: optionalIds
        };

    }

    function normalizeCategoryTranslations(raw, baseRegistry){

        var translations = Object.create(null);

        if(typeof raw === 'undefined'){
            return translations;
        }

        if(!isPlainObject(raw) || Object.keys(raw).length > MAX_LANGUAGES){
            return null;
        }

        var codes = Object.keys(raw);

        for(var index = 0; index < codes.length; index += 1){
            var code = codes[index];
            var registry = normalizeCategories(raw[code]);

            if(normalizeLanguageCode(code) !== code || !registry){
                return null;
            }

            if(registry.categories.length !== baseRegistry.categories.length){
                return null;
            }

            for(var categoryIndex = 0; categoryIndex < baseRegistry.categories.length; categoryIndex += 1){
                if(
                    registry.categories[categoryIndex].id !== baseRegistry.categories[categoryIndex].id
                ){
                    return null;
                }
            }

            translations[code] = registry;
        }

        return translations;

    }

    function normalizeServices(raw, categoryRegistry){

        if(!Array.isArray(raw) || raw.length > MAX_SERVICES){
            return null;
        }

        var services = [];
        var byId = Object.create(null);
        var idsByCategory = Object.create(null);
        var activatableIds = [];
        var visitorIds = [];

        categoryRegistry.ids.concat(['unclassified']).forEach(function(category){
            idsByCategory[category] = [];
        });

        for(var index = 0; index < raw.length; index += 1){
            var service = normalizeService(raw[index], categoryRegistry.byId);

            if(!service || byId[service.id]){
                return null;
            }

            byId[service.id] = service;
            services.push(service);

            if(service.managed){
                idsByCategory[service.category].push(service.id);

                if(service.category !== 'unclassified'){
                    visitorIds.push(service.id);

                    if(service.category !== 'necessary'){
                        activatableIds.push(service.id);
                    }
                }
            }
        }

        return {
            services: services,
            byId: byId,
            idsByCategory: idsByCategory,
            activatableIds: activatableIds,
            visitorIds: visitorIds
        };

    }

    function normalizeService(raw, categoriesById){

        if(!hasExactKeys(raw, ['id', 'label', 'category', 'purpose', 'domains', 'kind', 'managed'])){
            return null;
        }

        var id = typeof raw.id === 'string' ? raw.id.trim() : '';
        var label = typeof raw.label === 'string' ? raw.label.trim() : '';
        var category = typeof raw.category === 'string' ? raw.category : '';
        var purpose = typeof raw.purpose === 'string' ? raw.purpose : '';
        var kind = typeof raw.kind === 'string' ? raw.kind : '';

        if(
            !/^[a-z0-9][a-z0-9._-]{0,63}$/.test(id)
            || !label
            || unicodeLength(label) > 80
            || /[\u0000-\u001F\u007F]/.test(label)
            || (category !== 'unclassified' && !categoriesById[category])
            || SERVICE_PURPOSES.indexOf(purpose) === -1
            || SERVICE_KINDS.indexOf(kind) === -1
            || typeof raw.managed !== 'boolean'
            || !Array.isArray(raw.domains)
            || raw.domains.length > MAX_SERVICE_DOMAINS
        ){
            return null;
        }

        var domains = [];

        for(var index = 0; index < raw.domains.length; index += 1){
            var domain = normalizeServiceDomain(raw.domains[index]);

            if(!domain || domains.indexOf(domain) !== -1){
                return null;
            }

            domains.push(domain);
        }

        if(!domains.length){
            return null;
        }

        return {
            id: id,
            label: label,
            category: category,
            purpose: purpose,
            domains: domains,
            kind: kind,
            managed: raw.managed
        };

    }

    function normalizeServiceDomain(value){

        if(typeof value !== 'string' || !value || value.length > 253 || value !== value.toLowerCase()){
            return '';
        }

        if(value === 'localhost'){
            return value;
        }

        if(!/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)*[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/.test(value)){
            return '';
        }

        return value;

    }

    function normalizeStylesheetUrl(value){

        var url = normalizeHttpUrl(value);

        if(!url){
            return null;
        }

        return url.href;

    }

    function normalizeLinks(raw){

        raw = isPlainObject(raw) ? raw : {};

        return {
            privacy: normalizeLink(raw.privacy),
            cookies: normalizeLink(raw.cookies),
            terms: normalizeLink(raw.terms)
        };

    }

    function normalizeLink(raw){

        if(raw === null || typeof raw === 'undefined'){
            return null;
        }

        if(!isPlainObject(raw)){
            return null;
        }

        var url = normalizeHttpUrl(raw.url);
        var label = normalizedString(raw.label, '');

        if(!url || !label){
            return null;
        }

        return {
            url: url.href,
            label: label
        };

    }

    function normalizeLegalLinks(raw){

        if(!Array.isArray(raw) || raw.length > MAX_LEGAL_LINKS){
            return null;
        }

        var links = [];
        var seen = Object.create(null);

        for(var index = 0; index < raw.length; index++){
            var link = normalizeLink(raw[index]);

            if(!link || seen[link.url]){
                return null;
            }

            seen[link.url] = true;
            links.push(link);
        }

        return links;

    }

    function normalizeLinkTranslations(raw){

        var translations = Object.create(null);

        if(typeof raw === 'undefined'){
            return translations;
        }

        if(!isPlainObject(raw)){
            return null;
        }

        var codes = Object.keys(raw);

        if(codes.length > MAX_LANGUAGES){
            return null;
        }

        for(var index = 0; index < codes.length; index += 1){
            var code = codes[index];
            var bundle = raw[code];

            if(normalizeLanguageCode(code) !== code || !hasExactKeys(bundle, ['legalLinks', 'termsLink'])){
                return null;
            }

            var legalLinks = normalizeLegalLinks(bundle.legalLinks);
            var termsLink = normalizeLink(bundle.termsLink);

            if(legalLinks === null || (bundle.termsLink !== null && !termsLink)){
                return null;
            }

            translations[code] = {
                legalLinks: legalLinks,
                termsLink: termsLink
            };
        }

        return translations;

    }

    function normalizeHttpUrl(value){

        if(typeof value !== 'string' || !value.trim() || value.length > 2048){
            return null;
        }

        try{
            var url = new URL(value, window.location.href);

            return (
                (url.protocol === 'http:' || url.protocol === 'https:')
                && !url.username
                && !url.password
            ) ? url : null;
        }catch(error){
            return null;
        }

    }

    function normalizeKnownIntegrationIds(raw){

        raw = isPlainObject(raw) ? raw : {};

        return {
            googleAnalytics: validId(raw.googleAnalytics, /^G-[A-Z0-9]{4,20}$/),
            googleTagManager: validId(raw.googleTagManager, /^GTM-[A-Z0-9]{4,20}$/),
            googleAds: validId(raw.googleAds, /^AW-[0-9]{5,20}$/),
            metaPixel: validId(raw.metaPixel, /^[0-9]{5,32}$/)
        };

    }

    function normalizeKnownIntegrationCategories(raw, integrations, categoriesById){

        var keys = ['googleAnalytics', 'googleTagManager', 'googleAds', 'metaPixel'];

        if(!hasExactKeys(raw, keys)){
            return null;
        }

        var normalized = Object.create(null);

        for(var index = 0; index < keys.length; index += 1){
            var key = keys[index];
            var category = typeof raw[key] === 'string' ? raw[key] : '';
            var available = Boolean(categoriesById[category]);

            if(category !== '' && !available){
                return null;
            }

            normalized[key] = available ? category : '';
        }

        return normalized;

    }

    function normalizeConfiguredIntegrations(raw, servicesById, categoriesById){

        if(typeof raw === 'undefined'){
            return [];
        }

        if(!Array.isArray(raw) || raw.length > MAX_CUSTOM_INTEGRATIONS){
            return null;
        }

        var normalized = [];
        var seen = Object.create(null);
        var seenUrls = Object.create(null);
        var totalBytes = 0;

        for(var index = 0; index < raw.length; index += 1){
            var definition = raw[index];

            if(
                !isPlainObject(definition)
                || Object.keys(definition).sort().join('|') !== 'id|initCode|scriptUrl'
                || typeof definition.id !== 'string'
                || !/^custom-[a-f0-9]{16}$/.test(definition.id)
                || seen[definition.id]
                || typeof definition.initCode !== 'string'
                || definition.initCode.indexOf('\u0000') !== -1
                || utf8ByteLength(definition.initCode) > MAX_CUSTOM_INIT_CODE_BYTES
            ){
                return null;
            }

            var service = servicesById[definition.id];
            var scriptUrl = normalizeCustomScriptUrl(definition.scriptUrl);
            var scriptHostname = scriptUrl ? new URL(scriptUrl).hostname : '';

            if(
                !service
                || !service.managed
                || service.kind !== 'integration'
                || !categoriesById[service.category]
                || !scriptUrl
                || service.domains.length !== 1
                || service.domains[0] !== scriptHostname
                || seenUrls[scriptUrl]
            ){
                return null;
            }

            seen[definition.id] = true;
            seenUrls[scriptUrl] = true;
            totalBytes += utf8ByteLength(definition.id)
                + utf8ByteLength(scriptUrl)
                + utf8ByteLength(definition.initCode);

            if(totalBytes > MAX_CUSTOM_INTEGRATIONS_BYTES){
                return null;
            }

            normalized.push({
                id: definition.id,
                scriptUrl: scriptUrl,
                initCode: definition.initCode
            });
        }

        return normalized;

    }

    function normalizeCustomScriptUrl(value){

        if(
            typeof value !== 'string'
            || !value
            || value !== value.trim()
            || value.length > MAX_CUSTOM_SCRIPT_URL_LENGTH
            || /[\s\\]/.test(value)
            || /[<>"']/.test(value)
            || value.indexOf('#') !== -1
        ){
            return null;
        }

        try{
            var url = new URL(value);

            if(
                url.protocol !== 'https:'
                || !url.hostname
                || url.username
                || url.password
                || url.hash
                || url.href.length > MAX_CUSTOM_SCRIPT_URL_LENGTH
            ){
                return null;
            }

            return url.href;
        }catch(error){
            return null;
        }

    }

    function utf8ByteLength(value){

        var length = 0;

        for(var index = 0; index < value.length; index += 1){
            var code = value.charCodeAt(index);

            if(code < 0x80){
                length += 1;
            }else if(code < 0x800){
                length += 2;
            }else if(code >= 0xD800 && code <= 0xDBFF && index + 1 < value.length){
                var next = value.charCodeAt(index + 1);

                if(next >= 0xDC00 && next <= 0xDFFF){
                    length += 4;
                    index += 1;
                }else{
                    return Infinity;
                }
            }else if(code >= 0xDC00 && code <= 0xDFFF){
                return Infinity;
            }else{
                length += 3;
            }

            if(length > MAX_CUSTOM_INIT_CODE_BYTES){
                return length;
            }
        }

        return length;

    }

    function normalizeLanguageCode(value){

        if(typeof value !== 'string'){
            return '';
        }

        var code = value.trim().toLowerCase();

        return /^[a-z][a-z0-9_-]{1,31}$/.test(code) ? code : '';

    }

    function normalizeBannerTranslations(raw){

        var translations = Object.create(null);

        if(typeof raw === 'undefined'){
            return translations;
        }

        if(!isPlainObject(raw)){
            return translations;
        }

        var codes = Object.keys(raw);

        if(codes.length > MAX_LANGUAGES){
            return translations;
        }

        for(var index = 0; index < codes.length; index += 1){
            var code = codes[index];
            var translation = raw[code];

            if(
                normalizeLanguageCode(code) !== code
                || !isPlainObject(translation)
                || Object.keys(translation).sort().join('|') !== 'message|title'
                || typeof translation.title !== 'string'
                || !translation.title.trim()
                || unicodeLength(translation.title) > 120
                || typeof translation.message !== 'string'
                || !translation.message.trim()
                || unicodeLength(translation.message) > 600
            ){
                return Object.create(null);
            }

            translations[code] = {
                title: translation.title.trim(),
                message: translation.message.trim()
            };
        }

        return translations;

    }

    function normalizeStringTranslations(raw){

        var translations = Object.create(null);

        if(typeof raw === 'undefined'){
            return translations;
        }

        if(!isPlainObject(raw)){
            return translations;
        }

        var codes = Object.keys(raw);

        if(codes.length > MAX_LANGUAGES){
            return translations;
        }

        for(var index = 0; index < codes.length; index += 1){
            var code = codes[index];
            var normalized = normalizeConsentStringBundle(raw[code]);

            if(normalizeLanguageCode(code) !== code || !normalized){
                return Object.create(null);
            }

            translations[code] = normalized;
        }

        return translations;

    }

    function normalizeConsentStringBundle(raw){

        if(
            !hasExactKeys(raw, ['title', 'message', 'legalLinksLabel', 'actions', 'dialog', 'categories', 'services', 'terms', 'gpc', 'error'])
            || !hasExactKeys(raw.actions, ['acceptAll', 'rejectAll', 'customize', 'save', 'close', 'revisit'])
            || !hasExactKeys(raw.dialog, ['title', 'description'])
            || !hasExactKeys(raw.categories, ['necessary', 'preferences', 'analytics', 'marketing', 'external'])
            || !hasExactKeys(raw.categories.necessary, ['label', 'description'])
            || !hasExactKeys(raw.categories.preferences, ['label', 'description'])
            || !hasExactKeys(raw.categories.analytics, ['label', 'description'])
            || !hasExactKeys(raw.categories.marketing, ['label', 'description'])
            || !hasExactKeys(raw.categories.external, ['label', 'description'])
            || !hasExactKeys(raw.services, ['title', 'description', 'blocked', 'allow', 'unclassified'])
            || !hasExactKeys(raw.terms, ['label', 'description', 'requiredError'])
            || !hasExactKeys(raw.gpc, ['notice'])
            || !hasExactKeys(raw.error, ['generic'])
        ){
            return null;
        }

        var paths = Object.keys(CONSENT_STRING_LIMITS);

        for(var index = 0; index < paths.length; index += 1){
            var path = paths[index];
            var value = nestedValue(raw, path);

            if(
                typeof value !== 'string'
                || !value.trim()
                || unicodeLength(value) > CONSENT_STRING_LIMITS[path]
                || /[\u0000-\u0008\u000B\u000C\u000E-\u001F\u007F]/.test(value)
                || /<[^>]*>/.test(value)
                || (MULTILINE_STRING_PATHS.indexOf(path) === -1 && /[\r\n]/.test(value))
            ){
                return null;
            }
        }

        return normalizeStrings(raw);

    }

    function hasExactKeys(value, keys){

        return isPlainObject(value)
            && Object.keys(value).sort().join('|') === keys.slice().sort().join('|');

    }

    function nestedValue(value, path){

        var keys = path.split('.');

        for(var index = 0; index < keys.length; index += 1){
            if(!isPlainObject(value) || !Object.prototype.hasOwnProperty.call(value, keys[index])){
                return null;
            }

            value = value[keys[index]];
        }

        return value;

    }

    function unicodeLength(value){

        return Array.from(value).length;

    }

    function resolveBannerLanguage(value, translations){

        var code = normalizeLanguageCode(value);

        if(!code){
            return '';
        }

        if(translations[code]){
            return code;
        }

        var primary = code.split(/[-_]/)[0];

        return primary && translations[primary] ? primary : '';

    }

    function validId(value, pattern){

        if(typeof value !== 'string'){
            return null;
        }

        var candidate = value.trim().toUpperCase();

        return pattern.test(candidate) ? candidate : null;

    }

    function normalizeStrings(raw){

        raw = isPlainObject(raw) ? raw : {};

        var actions = isPlainObject(raw.actions) ? raw.actions : {};
        var dialog = isPlainObject(raw.dialog) ? raw.dialog : {};
        var categories = isPlainObject(raw.categories) ? raw.categories : {};
        var services = isPlainObject(raw.services) ? raw.services : {};
        var terms = isPlainObject(raw.terms) ? raw.terms : {};
        var gpc = isPlainObject(raw.gpc) ? raw.gpc : {};
        var error = isPlainObject(raw.error) ? raw.error : {};

        return {
            title: normalizedString(raw.title, 'Vos choix de confidentialité'),
            message: normalizedString(
                raw.message,
                'Nous utilisons des cookies nécessaires au fonctionnement du site et, avec votre accord, des outils de mesure et de marketing.'
            ),
            legalLinksLabel: normalizedString(raw.legalLinksLabel, 'Documents légaux'),
            actions: {
                acceptAll: normalizedString(actions.acceptAll, 'Tout accepter'),
                rejectAll: normalizedString(actions.rejectAll, 'Tout refuser'),
                customize: normalizedString(actions.customize, 'Personnaliser'),
                save: normalizedString(actions.save, 'Enregistrer mes choix'),
                close: normalizedString(actions.close, 'Fermer'),
                revisit: normalizedString(actions.revisit, 'Gérer mes cookies')
            },
            dialog: {
                title: normalizedString(dialog.title, 'Préférences de confidentialité'),
                description: normalizedString(
                    dialog.description,
                    'Choisissez les catégories facultatives que vous autorisez. Vous pourrez modifier ce choix plus tard.'
                )
            },
            categories: {
                necessary: normalizeCategoryStrings(
                    categories.necessary,
                    'Nécessaires',
                    'Requis pour le fonctionnement et la sécurité du site. Toujours actif.'
                ),
                preferences: normalizeCategoryStrings(
                    categories.preferences,
                    'Préférences',
                    'Mémorise vos préférences d’affichage et de fonctionnement.'
                ),
                analytics: normalizeCategoryStrings(
                    categories.analytics,
                    'Analyse',
                    'Mesure l’utilisation du site afin de l’améliorer.'
                ),
                marketing: normalizeCategoryStrings(
                    categories.marketing,
                    'Marketing',
                    'Mesure les campagnes et permet la personnalisation publicitaire.'
                ),
                external: normalizeCategoryStrings(
                    categories.external,
                    'Contenu externe',
                    'Autorise les contenus intégrés provenant de services externes.'
                )
            },
            services: {
                title: normalizedString(services.title, 'Services'),
                description: normalizedString(
                    services.description,
                    'Choisissez précisément les services que vous autorisez dans chaque catégorie.'
                ),
                blocked: normalizedString(
                    services.blocked,
                    'Ce contenu est bloqué jusqu’à ce que vous autorisiez ce service.'
                ),
                allow: normalizedString(services.allow, 'Autoriser ce service'),
                unclassified: normalizedString(
                    services.unclassified,
                    'Ce service n’est pas encore classé et ne peut pas être autorisé.'
                )
            },
            terms: {
                label: normalizedString(terms.label, 'Je confirme avoir lu et accepté'),
                description: normalizedString(
                    terms.description,
                    'Cette acceptation est conservée dans ce navigateur avec vos choix de confidentialité.'
                ),
                requiredError: normalizedString(
                    terms.requiredError,
                    'Veuillez accepter les conditions pour enregistrer ce choix.'
                )
            },
            gpc: {
                notice: normalizedString(
                    gpc.notice,
                    'Votre navigateur indique une préférence globale de confidentialité. Le marketing demeure désactivé.'
                )
            },
            error: {
                generic: normalizedString(
                    error.generic,
                    'Impossible d’enregistrer ce choix. Veuillez réessayer.'
                )
            }
        };

    }

    function normalizeCategoryStrings(raw, defaultLabel, defaultDescription){

        raw = isPlainObject(raw) ? raw : {};

        return {
            label: normalizedString(raw.label, defaultLabel),
            description: normalizedString(raw.description, defaultDescription)
        };

    }

    function normalizedString(value, fallback){

        if(typeof value !== 'string' || !value.trim() || value.length > 1000){
            return fallback;
        }

        return value.trim();

    }

    function isPlainObject(value){

        return value !== null && typeof value === 'object' && !Array.isArray(value);

    }

    function initializeGoogleConsentMode(){

        if(!hasGoogleIntegrationConfiguration()){
            return;
        }

        ensureGtag();
        window.gtag('consent', 'default', googleConsentState(null));

    }

    function updateGoogleConsentMode(consent){

        if(!hasGoogleIntegrationConfiguration()){
            return;
        }

        ensureGtag();
        window.gtag('consent', 'update', googleConsentState(consent));

    }

    function hasGoogleIntegrationConfiguration(){

        return Boolean(
            config.integrations.googleAnalytics
            || config.integrations.googleTagManager
            || config.integrations.googleAds
        );

    }

    function ensureGtag(){

        window.dataLayer = Array.isArray(window.dataLayer) ? window.dataLayer : [];

        if(typeof window.gtag !== 'function'){
            window.gtag = function(){
                window.dataLayer.push(arguments);
            };
        }

    }

    function googleConsentState(consent){

        var analyticsGranted = Boolean(consent && consent.analytics);
        var marketingGranted = Boolean(consent && !gpcActive && consent.marketing);
        var preferencesGranted = Boolean(consent && consent.preferences);

        return {
            functionality_storage: preferencesGranted ? 'granted' : 'denied',
            personalization_storage: preferencesGranted ? 'granted' : 'denied',
            analytics_storage: analyticsGranted ? 'granted' : 'denied',
            ad_storage: marketingGranted ? 'granted' : 'denied',
            ad_user_data: marketingGranted ? 'granted' : 'denied',
            ad_personalization: marketingGranted ? 'granted' : 'denied',
            security_storage: 'granted'
        };

    }

    function registerKnownIntegrations(){

        if(config.integrations.googleAnalytics){
            registerIntegration({
                id: 'google-analytics-4',
                serviceId: 'google-analytics-4',
                categories: [],
                mode: 'all',
                load: function(){
                    loadGoogleTag(config.integrations.googleAnalytics);
                }
            });
        }

        if(config.integrations.googleTagManager){
            registerIntegration({
                id: 'google-tag-manager',
                categories: [config.integrationCategories.googleTagManager],
                purpose: 'analytics',
                mode: 'all',
                load: function(){
                    window.dataLayer = Array.isArray(window.dataLayer) ? window.dataLayer : [];
                    window.dataLayer.push({
                        'gtm.start': new Date().getTime(),
                        event: 'gtm.js'
                    });
                    loadExternalScript(
                        'ulp-google-tag-manager',
                        'https://www.googletagmanager.com/gtm.js?id=' + encodeURIComponent(config.integrations.googleTagManager)
                    );
                }
            });
        }

        if(config.integrations.googleAds){
            registerIntegration({
                id: 'google-ads-remarketing',
                serviceId: 'google-ads-remarketing',
                categories: [],
                mode: 'all',
                load: function(){
                    loadGoogleTag(config.integrations.googleAds);
                }
            });
        }

        if(config.integrations.metaPixel){
            registerIntegration({
                id: 'meta-pixel',
                serviceId: 'meta-pixel',
                categories: [],
                mode: 'all',
                load: function(){
                    ensureMetaPixel();
                    window.fbq('consent', 'grant');
                    window.fbq('init', config.integrations.metaPixel);
                    window.fbq('track', 'PageView');
                    loadExternalScript(
                        'ulp-meta-pixel',
                        'https://connect.facebook.net/en_US/fbevents.js'
                    );
                },
                update: function(consent){
                    if(typeof window.fbq === 'function'){
                        window.fbq('consent', serviceConsentFrom(consent, 'meta-pixel') && !gpcActive ? 'grant' : 'revoke');
                    }
                }
            });
        }

    }

    function registerConfiguredIntegrations(){

        config.customIntegrations.forEach(function(definition){
            registerIntegration({
                id: definition.id,
                serviceId: definition.id,
                categories: [],
                load: function(){
                    return loadConfiguredIntegration(definition);
                }
            });
        });

    }

    function loadConfiguredIntegration(definition){

        return new Promise(function(resolve, reject){
            var scriptId = 'ulp-' + definition.id;
            var existing = document.getElementById(scriptId);

            if(existing){
                if(
                    existing.getAttribute('data-ulc-custom-integration') === definition.id
                    && existing.getAttribute('data-ulc-loaded') === 'true'
                    && existing.getAttribute('src') === definition.scriptUrl
                    && runConfiguredIntegrationCode(definition)
                ){
                    resolve();
                }else{
                    reject(false);
                }
                return;
            }

            var script = document.createElement('script');
            script.id = scriptId;
            script.async = true;
            script.setAttribute('src', definition.scriptUrl);
            script.setAttribute('data-ulc-custom-integration', definition.id);
            applyManagerScriptNonce(script);
            script.onload = function(){
                script.onload = null;
                script.onerror = null;
                script.setAttribute('data-ulc-loaded', 'true');

                if(runConfiguredIntegrationCode(definition)){
                    resolve();
                }else{
                    reject(false);
                }
            };
            script.onerror = function(){
                script.onload = null;
                script.onerror = null;

                if(script.parentNode){
                    script.parentNode.removeChild(script);
                }

                reject(false);
            };

            (document.head || document.documentElement).appendChild(script);
        });

    }

    function runConfiguredIntegrationCode(definition){

        if(!definition.initCode){
            return true;
        }

        var scriptId = 'ulp-' + definition.id + '-init';

        if(document.getElementById(scriptId)){
            return true;
        }

        try{
            var script = document.createElement('script');
            script.id = scriptId;
            script.textContent = definition.initCode;
            applyManagerScriptNonce(script);
            (document.head || document.documentElement).appendChild(script);
            return true;
        }catch(error){
            return false;
        }

    }

    function loadGoogleTag(destinationId){

        ensureGtag();

        if(!googleTagInitialized){
            window.gtag('js', new Date());
            loadExternalScript(
                'ulp-google-tag',
                'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(destinationId)
            );
            googleTagInitialized = true;
        }

        if(!configuredGoogleDestinations[destinationId]){
            window.gtag('config', destinationId);
            configuredGoogleDestinations[destinationId] = true;
        }

    }

    function ensureMetaPixel(){

        if(typeof window.fbq === 'function'){
            return;
        }

        var fbq = function(){

            if(fbq.callMethod){
                fbq.callMethod.apply(fbq, arguments);
            }else{
                fbq.queue.push(arguments);
            }

        };

        fbq.push = fbq;
        fbq.loaded = true;
        fbq.version = '2.0';
        fbq.queue = [];
        window.fbq = fbq;
        window._fbq = fbq;

    }

    function loadExternalScript(id, source){

        if(document.getElementById(id)){
            return;
        }

        var script = document.createElement('script');
        script.id = id;
        script.async = true;
        script.src = source;
        applyManagerScriptNonce(script);
        script.onerror = function(){
            script.onerror = null;
        };

        (document.head || document.documentElement).appendChild(script);

    }

    function applyManagerScriptNonce(script){

        if(managerScriptNonce){
            script.nonce = managerScriptNonce;
        }

    }

    function registerIntegration(definition){

        if(!isPlainObject(definition)){
            return false;
        }

        var id = typeof definition.id === 'string' ? definition.id.trim() : '';

        if(!/^[a-z0-9][a-z0-9._-]{0,63}$/.test(id) || integrations[id] || integrationOrder.length >= MAX_SERVICES || typeof definition.load !== 'function'){
            return false;
        }

        var categories = normalizeIntegrationCategories(definition.categories);
        var hasServiceId = Object.prototype.hasOwnProperty.call(definition, 'serviceId');
        var serviceId = normalizeIntegrationServiceId(definition.serviceId);
        var hasMode = Object.prototype.hasOwnProperty.call(definition, 'mode');
        var hasPurpose = Object.prototype.hasOwnProperty.call(definition, 'purpose');
        var purpose = hasPurpose && typeof definition.purpose === 'string'
            ? definition.purpose
            : '';

        if(categories === null
            || (hasMode && definition.mode !== 'all' && definition.mode !== 'any')
            || (hasPurpose && OPTIONAL_PURPOSES.indexOf(purpose) === -1)){
            return false;
        }

        if(hasServiceId){
            if(!serviceId){
                return false;
            }

            var service = config.servicesById[serviceId];
            var serviceCategory = service.category;

            if(categories.length && (categories.length !== 1 || categories[0] !== serviceCategory)){
                return false;
            }

            if(hasPurpose && purpose !== service.purpose){
                return false;
            }

            categories = [serviceCategory];
            purpose = service.purpose;
        }else if(!categories.length || !hasPurpose){
            return false;
        }

        var integration = {
            id: id,
            serviceId: serviceId,
            categories: categories,
            purpose: purpose,
            mode: definition.mode === 'any' ? 'any' : 'all',
            load: definition.load,
            update: typeof definition.update === 'function' ? definition.update : null,
            loaded: false
        };

        integrations[id] = integration;
        integrationOrder.push(integration);

        if(initialized){
            applyConsentToIntegration(integration, currentConsent, null);
        }

        return true;

    }

    function normalizeIntegrationServiceId(value){

        if(typeof value === 'undefined' || value === null || value === ''){
            return null;
        }

        if(typeof value !== 'string'){
            return null;
        }

        var serviceId = value.trim();
        var service = config.servicesById[serviceId];

        return service && service.managed
            && service.category !== 'unclassified'
            ? serviceId
            : null;

    }

    function normalizeIntegrationCategories(value){

        if(typeof value === 'undefined' || value === null || value === ''){
            return [];
        }

        var values = typeof value === 'string' ? [value] : value;
        var normalized = [];

        if(!Array.isArray(values) || values.length > CATEGORY_NAMES.length){
            return null;
        }

        for(var index = 0; index < values.length; index += 1){
            var category = values[index];

            if(
                typeof category !== 'string'
                || CATEGORY_NAMES.indexOf(category) === -1
                || normalized.indexOf(category) !== -1
            ){
                return null;
            }

            normalized.push(category);
        }

        return normalized;

    }

    function getConsent(){

        return copyConsent(currentConsent);

    }

    function hasConsent(categories, mode){

        var values = typeof categories === 'string' ? [categories] : categories;
        var normalized = [];

        if(
            !Array.isArray(values)
            || !values.length
            || values.length > CATEGORY_NAMES.length
            || (typeof mode !== 'undefined' && mode !== 'all' && mode !== 'any')
        ){
            return false;
        }

        for(var index = 0; index < values.length; index += 1){
            var category = values[index];

            if(
                typeof category !== 'string'
                || CATEGORY_NAMES.indexOf(category) === -1
                || normalized.indexOf(category) !== -1
            ){
                return false;
            }

            normalized.push(category);
        }

        if(!currentConsent){
            return false;
        }

        var method = mode === 'any' ? 'some' : 'every';

        return normalized[method](function(category){
            return categoryConsentFrom(currentConsent, category);
        });

    }

    function hasServiceConsent(id){

        if(typeof id !== 'string'){
            return false;
        }

        var service = config.servicesById[id];

        if(!service || !service.managed || service.category === 'unclassified'){
            return false;
        }

        if(service.category === 'necessary'){
            return true;
        }

        return serviceIsBlockedByGpc(service)
            ? false
            : serviceConsentFrom(currentConsent, id);

    }

    function serviceConsentFrom(consent, id){

        var service = config.servicesById[id];

        if(service && service.category === 'necessary'){
            return true;
        }

        return Boolean(consent && isPlainObject(consent.services) && consent.services[id] === true);

    }

    function categoryConsentFrom(consent, category){

        return Boolean(
            consent
            && isPlainObject(consent.categories)
            && consent.categories[category] === true
        );

    }

    function serviceIsBlockedByGpc(service){

        return Boolean(
            gpcActive
            && service
            && service.category !== 'necessary'
            && service.purpose === 'marketing'
        );

    }

    function hasGrantedMarketingService(consent){

        return config.activatableServiceIds.some(function(serviceId){
            var service = config.servicesById[serviceId];

            return service.purpose === 'marketing' && serviceConsentFrom(consent, serviceId);
        });

    }

    function setLanguage(value){

        var code = resolveBannerLanguage(value, config.stringTranslations);
        var translation = code ? config.stringTranslations[code] : null;

        if(translation){
            config.currentLanguage = code;
            config.strings = translation;
            applyLinkTranslation(code);
            applyCategoryTranslation(code);

            if(initialized){
                refreshInterfaceCopy();
            }

            return true;
        }

        code = resolveBannerLanguage(value, config.bannerTranslations);
        translation = code ? config.bannerTranslations[code] : null;

        if(!translation){
            code = resolveBannerLanguage(value, config.linkTranslations);

            if(!code){
                return false;
            }

            config.currentLanguage = code;
            applyLinkTranslation(code);
            applyCategoryTranslation(code);

            if(initialized){
                refreshInterfaceCopy();
            }

            return true;
        }

        config.currentLanguage = code;
        config.strings.title = translation.title;
        config.strings.message = translation.message;
        applyLinkTranslation(code);
        applyCategoryTranslation(code);

        if(initialized){
            refreshInterfaceCopy();
        }

        return true;

    }

    function applyLinkTranslation(value){

        var code = resolveBannerLanguage(value, config.linkTranslations);

        if(!code){
            return false;
        }

        config.legalLinks = config.linkTranslations[code].legalLinks;
        config.termsLink = config.linkTranslations[code].termsLink;
        return true;

    }

    function applyCategoryTranslation(value){

        var code = resolveBannerLanguage(value, config.categoryTranslations);

        if(!code){
            return false;
        }

        var registry = config.categoryTranslations[code];
        config.categories = registry.categories;
        config.categoriesById = registry.byId;
        return true;

    }

    function refreshInterfaceCopy(){

        if(!initialized){
            return;
        }

        host.setAttribute('lang', config.currentLanguage || document.documentElement.lang || '');
        elements.bannerTitle.textContent = config.strings.title;
        elements.bannerMessage.textContent = config.strings.message;
        elements.revisit.textContent = config.strings.actions.revisit;
        elements.bannerAccept.textContent = config.strings.actions.acceptAll;
        elements.bannerReject.textContent = config.strings.actions.rejectAll;
        elements.bannerCustomize.textContent = config.strings.actions.customize;
        elements.dialogTitle.textContent = config.strings.dialog.title;
        elements.dialogDescription.textContent = config.strings.dialog.description;
        elements.dialogClose.setAttribute('aria-label', config.strings.actions.close);
        if(elements.dialogReject){
            elements.dialogReject.textContent = config.strings.actions.rejectAll;
        }
        elements.dialogSave.textContent = config.strings.actions.save;

        if(elements.servicesTitle){
            elements.servicesTitle.textContent = config.strings.services.title;
            elements.servicesDescription.textContent = config.strings.services.description;
        }

        elements.legalLinkRegions.forEach(refreshLegalLinkRegion);

        CATEGORY_NAMES.forEach(function(category){
            elements.categoryLabels[category].textContent = config.categoriesById[category].label;
            elements.categoryDescriptions[category].textContent = config.categoriesById[category].description;
        });

        if(elements.termsLabel){
            elements.termsLabel.textContent = config.strings.terms.label;
            elements.termsDescription.textContent = config.strings.terms.description;
            elements.termsError.textContent = config.strings.terms.requiredError;
            elements.termsLink.textContent = config.termsLink.label;
            elements.termsLink.href = config.termsLink.url;
        }

        [elements.bannerGpc, elements.dialogGpc].forEach(function(notice){
            if(notice){
                notice.textContent = config.strings.gpc.notice;
            }
        });

        [elements.bannerError, elements.dialogError].forEach(function(error){
            if(error && !error.hidden){
                error.textContent = config.strings.error.generic;
            }
        });

        renderServicePlaceholders();

    }

    function openPreferences(trigger){

        var request = {focusTerms: false, selectAll: false};
        var resolvedTrigger = trigger && trigger.nodeType === 1 ? trigger : null;

        if(!initialized){
            pendingDialogRequest = {
                options: request,
                trigger: resolvedTrigger
            };
            return true;
        }

        showPreferences(request, resolvedTrigger);
        return true;

    }

    function applyConsentToIntegrations(consent, previousConsent){

        integrationOrder.forEach(function(integration){
            applyConsentToIntegration(integration, consent, previousConsent);
        });

    }

    function applyConsentToIntegration(integration, consent, previousConsent){

        var alwaysRequired = integrationIsAlwaysRequired(integration);

        if(!consent && !alwaysRequired){
            return;
        }

        if(integration.loaded && integration.update){
            safelyCall(integration.update, [copyConsent(consent), copyConsent(previousConsent), api]);
        }

        if(!integration.loaded && (alwaysRequired || integrationHasConsent(integration, consent))){
            integration.loaded = true;

            if(!safelyLoadIntegration(integration, consent)){
                integration.loaded = false;
            }
        }

    }

    function integrationIsAlwaysRequired(integration){

        if(integration.serviceId){
            var service = config.servicesById[integration.serviceId];

            return Boolean(service && service.category === 'necessary');
        }

        return integration.categories.length > 0 && integration.categories.every(function(category){
            return category === 'necessary';
        });

    }

    function integrationHasConsent(integration, consent){

        if(integration.serviceId){
            var service = config.servicesById[integration.serviceId];

            return Boolean(
                service
                && !serviceIsBlockedByGpc(service)
                && serviceConsentFrom(consent, integration.serviceId)
            );
        }

        if(gpcActive && integration.purpose === 'marketing'){
            return false;
        }

        var method = integration.mode === 'any' ? 'some' : 'every';

        return integration.categories[method](function(category){
            return categoryConsentFrom(consent, category);
        });

    }

    function safelyCall(callback, args){

        try{
            callback.apply(null, args);
            return true;
        }catch(error){
            return false;
        }

    }

    function safelyLoadIntegration(integration, consent){

        try{
            var result = integration.load(copyConsent(consent), api);

            if(result && typeof result.catch === 'function'){
                result.catch(function(){
                    integration.loaded = false;
                });
            }

            return true;
        }catch(error){
            return false;
        }

    }

    function readConsentCookie(preserveGpcMarketing){

        var encoded = readCookieValue(consentCookieName);

        if(!encoded){
            return null;
        }

        try{
            var parsed = JSON.parse(decodeURIComponent(encoded));

            return isValidConsent(parsed) ? canonicalConsent(parsed, preserveGpcMarketing) : null;
        }catch(error){
            return null;
        }

    }

    function readCookieValue(name){

        var prefix = name + '=';
        var cookies = document.cookie ? document.cookie.split(';') : [];

        for(var index = 0; index < cookies.length; index += 1){
            var cookie = cookies[index].trim();

            if(cookie.indexOf(prefix) === 0){
                return cookie.slice(prefix.length);
            }
        }

        return null;

    }

    function isValidConsent(value){

        if(!isPlainObject(value)){
            return false;
        }

        var expectedKeys = [
            'version',
            'policyVersion',
            'serviceRegistryVersion',
            'necessary',
            'preferences',
            'analytics',
            'marketing',
            'external',
            'categories',
            'services',
            'terms',
            'gpc',
            'timestamp'
        ];
        var keys = Object.keys(value).sort();

        if(keys.length !== expectedKeys.length || keys.join('|') !== expectedKeys.sort().join('|')){
            return false;
        }

        return value.version === API_VERSION
            && value.policyVersion === config.policyVersion
            && value.serviceRegistryVersion === config.serviceRegistryVersion
            && value.necessary === true
            && typeof value.preferences === 'boolean'
            && typeof value.analytics === 'boolean'
            && typeof value.marketing === 'boolean'
            && typeof value.external === 'boolean'
            && isValidCategoryConsent(value.categories)
            && isValidServiceConsent(value.services)
            && hasCanonicalCategoryStates(value)
            && typeof value.terms === 'boolean'
            && typeof value.gpc === 'boolean'
            && (
                !config.termsRequired
                || value.terms === true
                || (noGrantedCategories(value.categories) && noGrantedServices(value.services))
            )
            && !(value.gpc && value.marketing)
            && !(value.gpc && hasGrantedMarketingService(value))
            && isIsoTimestamp(value.timestamp);

    }

    function isValidCategoryConsent(value){

        if(!isPlainObject(value)){
            return false;
        }

        var keys = Object.keys(value).sort();
        var expected = CATEGORY_NAMES.slice().sort();

        if(keys.length !== expected.length || keys.join('|') !== expected.join('|')){
            return false;
        }

        return keys.every(function(category){
            return typeof value[category] === 'boolean';
        }) && value.necessary === true;

    }

    function isValidServiceConsent(value){

        if(!isPlainObject(value)){
            return false;
        }

        var keys = Object.keys(value).sort();
        var expected = config.activatableServiceIds.slice().sort();

        if(keys.length !== expected.length || keys.join('|') !== expected.join('|')){
            return false;
        }

        return keys.every(function(serviceId){
            return typeof value[serviceId] === 'boolean';
        });

    }

    function hasCanonicalCategoryStates(value){

        var categoriesAreCanonical = OPTIONAL_CATEGORIES.every(function(category){
            var serviceIds = config.serviceIdsByCategory[category];

            return !serviceIds.length || value.categories[category] === serviceIds.every(function(serviceId){
                return value.services[serviceId] === true;
            });
        });

        return categoriesAreCanonical && OPTIONAL_PURPOSES.every(function(purpose){
            return value[purpose] === technicalPurposeConsent(purpose, value.categories, value.services, value.gpc);
        });

    }

    function noGrantedCategories(categories){

        return OPTIONAL_CATEGORIES.every(function(category){
            return categories[category] === false;
        });

    }

    function noGrantedServices(services){

        return config.activatableServiceIds.every(function(serviceId){
            return services[serviceId] === false;
        });

    }

    function isIsoTimestamp(value){

        if(typeof value !== 'string' || !/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z$/.test(value)){
            return false;
        }

        var timestamp = Date.parse(value);
        var now = Date.now();
        var futureTolerance = 5 * 60 * 1000;
        var maximumAge = config.durationDays * 86400000;

        return !isNaN(timestamp)
            && new Date(timestamp).toISOString() === value
            && timestamp <= now + futureTolerance
            && timestamp >= now - maximumAge;

    }

    function canonicalConsent(value, preserveGpcMarketing){

        var services = Object.create(null);
        var categories = Object.create(null);
        var forceMarketingOff = gpcActive && preserveGpcMarketing !== true;

        config.activatableServiceIds.forEach(function(serviceId){
            var service = config.servicesById[serviceId];
            services[serviceId] = service.purpose === 'marketing' && forceMarketingOff
                ? false
                : Boolean(value.services && value.services[serviceId] === true);
        });

        CATEGORY_NAMES.forEach(function(category){
            categories[category] = category === 'necessary'
                ? true
                : categoryConsentValue(
                    category,
                    value.categories && value.categories[category],
                    services
                );
        });

        return {
            version: API_VERSION,
            policyVersion: config.policyVersion,
            serviceRegistryVersion: config.serviceRegistryVersion,
            necessary: true,
            preferences: technicalPurposeConsent('preferences', categories, services, false),
            analytics: technicalPurposeConsent('analytics', categories, services, false),
            marketing: technicalPurposeConsent('marketing', categories, services, forceMarketingOff || value.gpc === true),
            external: technicalPurposeConsent('external', categories, services, false),
            categories: categories,
            services: services,
            terms: value.terms === true,
            gpc: value.gpc === true,
            timestamp: value.timestamp
        };

    }

    function categoryConsentValue(category, fallback, services){

        var serviceIds = config.serviceIdsByCategory[category];

        return serviceIds.length
            ? serviceIds.every(function(serviceId){ return services[serviceId] === true; })
            : fallback === true;

    }

    function technicalPurposeConsent(purpose, categories, services, blockMarketing){

        if(purpose === 'marketing' && blockMarketing){
            return false;
        }

        var serviceGranted = config.activatableServiceIds.some(function(serviceId){
            var service = config.servicesById[serviceId];

            return service.purpose === purpose && services[serviceId] === true;
        });

        if(serviceGranted){
            return true;
        }

        var gtmCategory = config.integrationCategories.googleTagManager;
        var gtmGranted = Boolean(
            config.integrations.googleTagManager
            && gtmCategory
            && categories[gtmCategory] === true
        );

        return gtmGranted && (purpose === 'analytics' || purpose === 'marketing');

    }

    function allCategorySelections(granted){

        var selections = Object.create(null);

        CATEGORY_NAMES.forEach(function(category){
            selections[category] = category === 'necessary'
                ? true
                : Boolean(granted);
        });

        return selections;

    }

    function emptyConsent(){

        return canonicalConsent({
            version: API_VERSION,
            policyVersion: config.policyVersion,
            necessary: true,
            categories: allCategorySelections(false),
            services: Object.create(null),
            terms: false,
            gpc: gpcActive,
            timestamp: ''
        });

    }

    function createConsent(selections){

        return {
            version: API_VERSION,
            policyVersion: config.policyVersion,
            necessary: true,
            categories: isPlainObject(selections.categories) ? selections.categories : allCategorySelections(false),
            services: isPlainObject(selections.services) ? selections.services : Object.create(null),
            terms: config.termsRequired && selections.terms === true,
            gpc: gpcActive,
            timestamp: new Date().toISOString()
        };

    }

    function copyConsent(consent, preserveGpcMarketing){

        return consent ? canonicalConsent(consent, preserveGpcMarketing) : null;

    }

    function writeConsentCookie(consent){

        try{
            var expires = new Date(Date.now() + (config.durationDays * 86400000));
            var cookie = consentCookieName + '=' + encodeURIComponent(JSON.stringify(canonicalConsent(consent)))
                + '; Expires=' + expires.toUTCString()
                + '; Path=' + consentCookiePath
                + '; SameSite=Lax';

            if(window.location.protocol === 'https:'){
                cookie += '; Secure';
            }

            document.cookie = cookie;

            var stored = readConsentCookie();

            return Boolean(stored && stored.timestamp === consent.timestamp);
        }catch(error){
            return false;
        }

    }

    function saveConsent(nextConsent, source, preferredFocusTarget){

        nextConsent = canonicalConsent(nextConsent);

        if(!writeConsentCookie(nextConsent)){
            showGenericError(dialogOpen);
            return false;
        }

        var consentSaveTrigger = preferredFocusTarget || (dialogOpen ? dialogTrigger : null);
        var previousConsent = copyConsent(currentConsent);
        var revoked = revokedCategories(previousConsent, nextConsent);
        var purposeRevocations = revokedTechnicalPurposes(previousConsent, nextConsent);
        var serviceRevocations = revokedServices(previousConsent, nextConsent);
        var serviceWasRevoked = Object.keys(serviceRevocations).length > 0;
        var vendorRevocations = knownVendorRevocations(purposeRevocations, serviceRevocations);

        currentConsent = canonicalConsent(nextConsent);
        updateGoogleConsentMode(currentConsent);

        if(vendorRevocations.meta && typeof window.fbq === 'function'){
            safelyCall(window.fbq, ['consent', 'revoke']);
        }

        applyConsentToIntegrations(currentConsent, previousConsent);
        clearVendorCookies(vendorRevocations);
        closePreferences(false);
        renderInterfaceState();
        clearGenericErrors();
        focusAfterConsentSave(consentSaveTrigger);
        renderServicePlaceholders();

        dispatchDocumentEvent(CHANGE_EVENT, {
            version: API_VERSION,
            consent: copyConsent(currentConsent),
            previousConsent: previousConsent,
            source: source
        });

        if(revoked.any || serviceWasRevoked){
            window.setTimeout(function(){
                try{
                    window.location.reload();
                }catch(error){
                    return;
                }
            }, 0);
        }

        return true;

    }

    function revokedCategories(previousConsent, nextConsent){

        var revoked = {any: false};

        if(!previousConsent){
            return revoked;
        }

        OPTIONAL_CATEGORIES.forEach(function(category){
            if(categoryConsentFrom(previousConsent, category) && !categoryConsentFrom(nextConsent, category)){
                revoked.any = true;
            }
        });

        return revoked;

    }

    function revokedTechnicalPurposes(previousConsent, nextConsent){

        var revoked = Object.create(null);

        OPTIONAL_PURPOSES.forEach(function(purpose){
            revoked[purpose] = Boolean(previousConsent && previousConsent[purpose] && !nextConsent[purpose]);
        });

        return revoked;

    }

    function revokedServices(previousConsent, nextConsent){

        var revoked = Object.create(null);

        if(!previousConsent){
            return revoked;
        }

        config.activatableServiceIds.forEach(function(serviceId){
            if(serviceConsentFrom(previousConsent, serviceId) && !serviceConsentFrom(nextConsent, serviceId)){
                revoked[serviceId] = true;
            }
        });

        return revoked;

    }

    function knownVendorRevocations(purposes, services){

        return {
            analytics: Boolean(services['google-analytics-4'] || purposes.analytics),
            googleAds: Boolean(services['google-ads-remarketing'] || purposes.marketing),
            meta: Boolean(services['meta-pixel'] || purposes.marketing)
        };

    }

    function clearVendorCookies(revoked){

        if(!revoked.analytics && !revoked.googleAds && !revoked.meta){
            return;
        }

        var cookieNames = document.cookie ? document.cookie.split(';').map(function(cookie){
            return cookie.split('=')[0].trim();
        }) : [];

        cookieNames.forEach(function(name){
            var analyticsCookie = /^(_ga(?:_.+)?|_gid|_gat(?:_.+)?|_gac_.+)$/.test(name);
            var googleAdsCookie = /^_gcl_.+$/.test(name);
            var metaCookie = /^(_fbp|_fbc|fr)$/.test(name);

            if(
                (revoked.analytics && analyticsCookie)
                || (revoked.googleAds && googleAdsCookie)
                || (revoked.meta && metaCookie)
            ){
                expireCookieEverywhere(name);
            }
        });

    }

    function expireCookieEverywhere(name){

        var paths = config.cookiePath === '/' ? ['/'] : [config.cookiePath, '/'];
        var domains = cookieDomains();

        paths.forEach(function(path){
            expireCookie(name, path, null);

            domains.forEach(function(domain){
                expireCookie(name, path, domain);
            });
        });

    }

    function cookieDomains(){

        var hostname = window.location.hostname;

        if(!hostname || hostname === 'localhost' || /^[0-9.]+$/.test(hostname)){
            return hostname ? [hostname] : [];
        }

        var labels = hostname.split('.');
        var domains = [hostname, '.' + hostname];

        for(var index = 1; index < labels.length - 1; index += 1){
            domains.push('.' + labels.slice(index).join('.'));
        }

        return domains.filter(function(domain, index, values){
            return values.indexOf(domain) === index;
        });

    }

    function expireCookie(name, path, domain){

        try{
            var cookie = name + '=; Expires=Thu, 01 Jan 1970 00:00:00 GMT; Max-Age=0; Path=' + path + '; SameSite=Lax';

            if(domain){
                cookie += '; Domain=' + domain;
            }

            if(window.location.protocol === 'https:'){
                cookie += '; Secure';
            }

            document.cookie = cookie;
        }catch(error){
            return;
        }

    }

    function createInterface(){

        host = document.createElement('div');
        host.setAttribute(HOST_ATTRIBUTE, '');
        host.setAttribute('lang', config.currentLanguage || document.documentElement.lang || '');
        shadow = host.attachShadow({mode: 'open'});
        elements.legalLinkRegions = [];
        elements.categoryLabels = {};
        elements.categoryDescriptions = {};
        elements.serviceInputs = {};
        elements.serviceInputsByCategory = Object.create(null);

        var stylesheet = document.createElement('link');
        stylesheet.rel = 'stylesheet';
        stylesheet.href = config.stylesheetUrl;
        shadow.appendChild(stylesheet);

        elements.banner = createBanner();
        elements.overlay = createPreferencesDialog();
        elements.revisit = createElement('button', 'ulc-revisit', config.strings.actions.revisit);
        elements.revisit.type = 'button';
        elements.revisit.setAttribute('data-action', 'open-preferences');
        elements.revisit.setAttribute('aria-haspopup', 'dialog');
        elements.revisit.setAttribute('aria-controls', 'ulc-preferences-dialog');
        elements.revisit.hidden = true;

        shadow.appendChild(elements.banner);
        shadow.appendChild(elements.overlay);
        shadow.appendChild(elements.revisit);
        document.body.appendChild(host);

    }

    function createBanner(){

        var banner = createElement('section', 'ulc-banner');
        var inner = createElement('div', 'ulc-banner__inner');
        var copy = createElement('div', 'ulc-banner__copy');
        var title = createElement('h2', 'ulc-banner__title', config.strings.title);
        var message = createElement('p', 'ulc-banner__message', config.strings.message);
        var actions = createElement('div', 'ulc-actions ulc-banner__actions');

        title.id = 'ulc-banner-title';
        message.id = 'ulc-banner-message';
        banner.setAttribute('role', 'region');
        banner.setAttribute('aria-labelledby', title.id);
        banner.setAttribute('aria-describedby', message.id);
        banner.setAttribute('aria-live', 'polite');
        elements.bannerTitle = title;
        elements.bannerMessage = message;

        copy.appendChild(title);
        copy.appendChild(message);

        var links = createLegalLinks('ulc-legal-links');

        if(links){
            copy.appendChild(links);
        }

        if(gpcActive){
            elements.bannerGpc = createElement('p', 'ulc-notice', config.strings.gpc.notice);
            copy.appendChild(elements.bannerGpc);
        }

        elements.bannerError = createElement('p', 'ulc-error');
        elements.bannerError.setAttribute('role', 'status');
        elements.bannerError.setAttribute('aria-live', 'polite');
        elements.bannerError.hidden = true;
        copy.appendChild(elements.bannerError);

        var acceptAll = createActionButton('accept-all', config.strings.actions.acceptAll, 'ulc-button ulc-button--primary');

        if(config.termsRequired){
            acceptAll.setAttribute('aria-haspopup', 'dialog');
            acceptAll.setAttribute('aria-controls', 'ulc-preferences-dialog');
        }

        elements.bannerAccept = acceptAll;
        elements.bannerReject = createActionButton('reject-all', config.strings.actions.rejectAll, 'ulc-button ulc-button--secondary');
        elements.bannerCustomize = createActionButton('open-preferences', config.strings.actions.customize, 'ulc-button ulc-button--text');
        actions.appendChild(elements.bannerAccept);
        actions.appendChild(elements.bannerReject);
        actions.appendChild(elements.bannerCustomize);

        inner.appendChild(copy);
        inner.appendChild(actions);
        banner.appendChild(inner);

        return banner;

    }

    function createPreferencesDialog(){

        var overlay = createElement('div', 'ulc-overlay');
        var dialog = createElement('div', 'ulc-dialog');
        var form = createElement('form', 'ulc-dialog__form');
        var header = createElement('div', 'ulc-dialog__header');
        var title = createElement('h2', 'ulc-dialog__title', config.strings.dialog.title);
        var description = createElement('p', 'ulc-dialog__description', config.strings.dialog.description);
        var close = createActionButton('close-preferences', config.strings.actions.close, 'ulc-dialog__close');
        var categories = createElement('div', 'ulc-categories');

        overlay.hidden = true;
        overlay.setAttribute('data-overlay', '');
        form.noValidate = true;
        dialog.id = 'ulc-preferences-dialog';
        dialog.setAttribute('role', 'dialog');
        dialog.setAttribute('aria-modal', 'true');
        title.id = 'ulc-dialog-title';
        title.tabIndex = -1;
        description.id = 'ulc-dialog-description';
        dialog.setAttribute('aria-labelledby', title.id);
        dialog.setAttribute('aria-describedby', description.id);
        close.setAttribute('aria-label', config.strings.actions.close);
        close.textContent = '×';

        elements.dialog = dialog;
        elements.dialogTitle = title;
        elements.dialogDescription = description;
        elements.dialogClose = close;
        elements.form = form;
        elements.categoryInputs = {};

        header.appendChild(title);
        header.appendChild(close);
        form.appendChild(header);
        form.appendChild(description);

        var legalLinks = createLegalLinks('ulc-legal-links ulc-dialog__legal-links');

        if(legalLinks){
            form.appendChild(legalLinks);
        }

        if(config.visitorServiceIds.length){
            var servicesIntro = createElement('div', 'ulc-services__intro');
            elements.servicesTitle = createElement('h3', 'ulc-services__title', config.strings.services.title);
            elements.servicesDescription = createElement('p', 'ulc-services__description', config.strings.services.description);
            servicesIntro.appendChild(elements.servicesTitle);
            servicesIntro.appendChild(elements.servicesDescription);
            form.appendChild(servicesIntro);
        }

        CATEGORY_NAMES.forEach(function(category){
            categories.appendChild(createCategoryControl(category));
        });

        form.appendChild(categories);

        if(gpcActive){
            elements.dialogGpc = createElement('p', 'ulc-notice', config.strings.gpc.notice);
            form.appendChild(elements.dialogGpc);
        }

        if(config.termsRequired){
            form.appendChild(createTermsControl());
        }

        elements.dialogError = createElement('p', 'ulc-error');
        elements.dialogError.setAttribute('role', 'status');
        elements.dialogError.setAttribute('aria-live', 'polite');
        elements.dialogError.hidden = true;
        form.appendChild(elements.dialogError);

        var footer = createElement('div', 'ulc-dialog__footer');
        var save = createElement('button', 'ulc-button ulc-button--primary', config.strings.actions.save);
        save.type = 'submit';
        elements.dialogReject = null;
        elements.dialogSave = save;

        if(OPTIONAL_CATEGORIES.length){
            elements.dialogReject = createActionButton('reject-all', config.strings.actions.rejectAll, 'ulc-button ulc-button--secondary');
            footer.appendChild(elements.dialogReject);
        }

        footer.appendChild(save);
        form.appendChild(footer);

        dialog.appendChild(form);
        overlay.appendChild(dialog);

        return overlay;

    }

    function createCategoryControl(category){

        var strings = config.categoriesById[category];
        var item = createElement('div', 'ulc-category');
        var input = document.createElement('input');
        var content = createElement('div', 'ulc-category__content');
        var label = createElement('label', 'ulc-category__label', strings.label);
        var description = createElement('p', 'ulc-category__description', strings.description);

        input.type = 'checkbox';
        input.id = 'ulc-category-' + category;
        input.name = category;
        input.value = '1';
        label.htmlFor = input.id;
        description.id = input.id + '-description';
        input.setAttribute('aria-describedby', description.id);

        if(category === 'necessary'){
            input.checked = true;
            input.disabled = true;
        }

        elements.categoryInputs[category] = input;
        elements.categoryLabels[category] = label;
        elements.categoryDescriptions[category] = description;
        content.appendChild(label);
        content.appendChild(description);
        item.appendChild(input);
        item.appendChild(content);

        var serviceIds = config.serviceIdsByCategory[category];

        if(serviceIds.length){
            var list = createElement('ul', 'ulc-service-list');
            list.setAttribute('aria-label', strings.label);
            elements.serviceInputsByCategory[category] = [];

            serviceIds.forEach(function(serviceId){
                list.appendChild(createServiceControl(config.servicesById[serviceId]));
            });

            item.appendChild(list);
        }

        return item;

    }

    function createServiceControl(service){

        var item = createElement('li', 'ulc-service');
        var input = document.createElement('input');
        var label = createElement('label', 'ulc-service__label', service.label);

        input.type = 'checkbox';
        input.id = 'ulc-service-' + service.id;
        input.name = 'services[' + service.id + ']';
        input.value = '1';
        input.setAttribute('data-service-id', service.id);
        label.htmlFor = input.id;

        if(service.category === 'necessary'){
            input.checked = true;
            input.disabled = true;
        }

        if(serviceIsBlockedByGpc(service)){
            input.checked = false;
            input.disabled = true;
        }

        elements.serviceInputs[service.id] = input;
        elements.serviceInputsByCategory[service.category].push(input);
        item.appendChild(input);
        item.appendChild(label);

        return item;

    }

    function createTermsControl(){

        var item = createElement('div', 'ulc-terms');
        var line = createElement('div', 'ulc-terms__line');
        var content = createElement('div', 'ulc-terms__content');
        var input = document.createElement('input');
        var labelLine = createElement('div', 'ulc-terms__label-line');
        var label = createElement('label', 'ulc-terms__label', config.strings.terms.label);
        var description = createElement('p', 'ulc-terms__description', config.strings.terms.description);
        var error = createElement('p', 'ulc-error', config.strings.terms.requiredError);

        input.type = 'checkbox';
        input.id = 'ulc-terms-confirmation';
        input.name = 'terms';
        input.value = '1';
        input.required = true;
        label.htmlFor = input.id;
        description.id = input.id + '-description';
        error.id = input.id + '-error';
        error.setAttribute('role', 'alert');
        error.hidden = true;
        input.setAttribute('aria-describedby', description.id + ' ' + error.id);
        label.id = input.id + '-label';

        labelLine.appendChild(label);

        if(config.termsLink){
            var termsLink = createLegalLink(config.termsLink);
            termsLink.id = input.id + '-link';
            labelLine.appendChild(document.createTextNode(' '));
            labelLine.appendChild(termsLink);
            input.setAttribute('aria-labelledby', label.id + ' ' + termsLink.id);
            elements.termsLink = termsLink;
        }

        content.appendChild(labelLine);
        content.appendChild(description);
        line.appendChild(input);
        line.appendChild(content);
        item.appendChild(line);
        item.appendChild(error);

        elements.termsInput = input;
        elements.termsLabel = label;
        elements.termsDescription = description;
        elements.termsError = error;

        return item;

    }

    function createLegalLinks(className){

        var links = createElement('nav', className);
        elements.legalLinkRegions.push(links);
        refreshLegalLinkRegion(links);

        return links;

    }

    function refreshLegalLinkRegion(region){

        while(region.firstChild){
            region.removeChild(region.firstChild);
        }

        region.setAttribute('aria-label', config.strings.legalLinksLabel);

        config.legalLinks.forEach(function(link){
            region.appendChild(createLegalLink(link));
        });

        region.hidden = config.legalLinks.length === 0;

    }

    function createLegalLink(link){

        var anchor = createElement('a', 'ulc-link', link.label);
        anchor.href = link.url;

        return anchor;

    }

    function createActionButton(action, label, className){

        var button = createElement('button', className, label);
        button.type = 'button';
        button.setAttribute('data-action', action);

        if(action === 'open-preferences'){
            button.setAttribute('aria-haspopup', 'dialog');
            button.setAttribute('aria-controls', 'ulc-preferences-dialog');
        }

        return button;

    }

    function createElement(tagName, className, text){

        var element = document.createElement(tagName);

        if(className){
            element.className = className;
        }

        if(typeof text === 'string'){
            element.textContent = text;
        }

        return element;

    }

    function initializeServicePlaceholders(){

        processServicePlaceholderTree(document, MAX_SERVICE_PLACEHOLDERS);

        if(typeof window.MutationObserver !== 'function' || !document.body){
            return;
        }

        placeholderObserver = new window.MutationObserver(function(records){
            var remaining = MAX_SERVICE_PLACEHOLDERS;

            records.forEach(function(record){
                if(remaining <= 0){
                    return;
                }

                if(record.type === 'attributes'){
                    var existing = findServicePlaceholder(record.target);

                    if(existing){
                        renderServicePlaceholder(existing);
                    }else{
                        remaining -= processServicePlaceholderTree(record.target, remaining);
                    }
                    return;
                }

                Array.prototype.forEach.call(record.addedNodes || [], function(node){
                    if(remaining > 0){
                        remaining -= processServicePlaceholderTree(node, remaining);
                    }
                });
            });
        });
        placeholderObserver.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['data-ulc-service', 'data-ulc-src']
        });

        window.addEventListener('pagehide', disconnectPlaceholderObserver, {once: true});

    }

    function disconnectPlaceholderObserver(){

        if(placeholderObserver){
            placeholderObserver.disconnect();
            placeholderObserver = null;
        }

    }

    function processServicePlaceholderTree(root, limit){

        servicePlaceholders = servicePlaceholders.filter(function(record){
            return record.host && record.host.isConnected !== false;
        });

        if(!root || limit <= 0 || servicePlaceholders.length >= MAX_SERVICE_PLACEHOLDERS){
            return 0;
        }

        var candidates = [];

        if(typeof root.matches === 'function' && root.matches(SERVICE_PLACEHOLDER_SELECTOR)){
            candidates.push(root);
        }

        if(typeof root.querySelectorAll === 'function'){
            candidates = candidates.concat(Array.prototype.slice.call(root.querySelectorAll(SERVICE_PLACEHOLDER_SELECTOR)));
        }

        var processed = 0;

        candidates.some(function(candidate){
            if(processed >= limit || servicePlaceholders.length >= MAX_SERVICE_PLACEHOLDERS){
                return true;
            }

            if(createServicePlaceholder(candidate)){
                processed += 1;
            }

            return false;
        });

        return processed;

    }

    function createServicePlaceholder(element){

        if(findServicePlaceholder(element) || typeof element.attachShadow !== 'function' || element.shadowRoot){
            return false;
        }

        var root;

        try{
            root = element.attachShadow({mode: 'open'});
        }catch(error){
            return false;
        }

        var stylesheet = document.createElement('link');
        var content = createElement('div', 'ulc-embed');
        var record = {host: element, root: root, content: content};

        stylesheet.rel = 'stylesheet';
        stylesheet.href = config.stylesheetUrl;
        element.tabIndex = -1;
        root.appendChild(stylesheet);
        root.appendChild(content);
        servicePlaceholders.push(record);
        renderServicePlaceholder(record);

        return true;

    }

    function findServicePlaceholder(element){

        for(var index = 0; index < servicePlaceholders.length; index += 1){
            if(servicePlaceholders[index].host === element){
                return servicePlaceholders[index];
            }
        }

        return null;

    }

    function renderServicePlaceholders(){

        servicePlaceholders = servicePlaceholders.filter(function(record){
            return record.host && record.host.isConnected !== false;
        });
        servicePlaceholders.forEach(renderServicePlaceholder);

    }

    function renderServicePlaceholder(record){

        clearElement(record.content);

        var serviceId = normalizedServiceId(record.host.getAttribute('data-ulc-service'));
        var service = serviceId ? config.servicesById[serviceId] : null;
        var source = record.host.getAttribute('data-ulc-src');
        var embedUrl = service ? normalizeServiceEmbedUrl(service, source) : null;
        var accessibleLabel = service
            ? normalizedPlaceholderTitle(record.host.getAttribute('data-ulc-title'), service.label)
            : config.strings.services.unclassified;

        record.host.setAttribute('role', 'group');
        record.host.setAttribute('aria-label', accessibleLabel);

        if(service && embedUrl && service.kind === 'iframe' && hasServiceConsent(service.id)){
            record.content.appendChild(createServiceIframe(accessibleLabel, embedUrl));
            return;
        }

        record.content.appendChild(createElement('strong', 'ulc-embed__title', accessibleLabel));

        var activatable = Boolean(
            service
            && service.managed
            && embedUrl
            && service.kind === 'iframe'
            && OPTIONAL_CATEGORIES.indexOf(service.category) !== -1
            && !serviceIsBlockedByGpc(service)
        );
        var message = activatable
            ? config.strings.services.blocked
            : config.strings.services.unclassified;
        record.content.appendChild(createElement('p', 'ulc-embed__message', message));

        if(activatable){
            var button = createElement('button', 'ulc-button ulc-button--primary ulc-embed__allow', config.strings.services.allow);
            button.type = 'button';
            button.addEventListener('click', function(){
                allowServiceFromPlaceholder(service, record);
            });
            record.content.appendChild(button);
        }

    }

    function normalizedServiceId(value){

        return typeof value === 'string' && /^[a-z0-9][a-z0-9._-]{0,63}$/.test(value)
            ? value
            : '';

    }

    function normalizedPlaceholderTitle(value, fallback){

        if(
            typeof value !== 'string'
            || !value.trim()
            || unicodeLength(value.trim()) > 160
            || /[<>\u0000-\u001F\u007F]/.test(value)
        ){
            return fallback;
        }

        return value.trim();

    }

    function clearElement(element){

        while(element.firstChild){
            element.removeChild(element.firstChild);
        }

    }

    function allowServiceFromPlaceholder(service, record){

        if(config.termsRequired){
            showPreferences({selectAll: false, focusTerms: true, serviceId: service.id}, record.host);
            return;
        }

        var consent = currentConsent || emptyConsent();
        var services = Object.create(null);

        config.activatableServiceIds.forEach(function(serviceId){
            services[serviceId] = serviceConsentFrom(consent, serviceId);
        });
        services[service.id] = true;

        saveConsent(createConsent({
            categories: consent.categories,
            services: services,
            terms: false
        }), 'service', record.host);

    }

    function normalizeServiceEmbedUrl(service, value){

        var url = normalizeHttpUrl(value);

        if(
            !url
            || url.username
            || url.password
            || url.port
            || url.hash
            || url.hostname.charAt(url.hostname.length - 1) === '.'
            || service.domains.indexOf(url.hostname.toLowerCase()) === -1
        ){
            return null;
        }

        if(isYouTubeHostname(url.hostname)){
            return normalizeYouTubeEmbedUrl(url);
        }

        if(url.hostname.toLowerCase() === 'player.vimeo.com'){
            return normalizeVimeoEmbedUrl(url);
        }

        return normalizeGenericEmbedUrl(url);

    }

    function isYouTubeHostname(hostname){

        return [
            'youtube.com',
            'www.youtube.com',
            'youtube-nocookie.com',
            'www.youtube-nocookie.com',
            'youtu.be',
            'www.youtu.be'
        ].indexOf(hostname.toLowerCase()) !== -1;

    }

    function normalizeYouTubeEmbedUrl(url){

        var parts = url.pathname.split('/').filter(Boolean);
        var videoId = parts.length === 2 && parts[0] === 'embed' ? parts[1] : '';

        if(!/^[A-Za-z0-9_-]{6,64}$/.test(videoId)){
            return null;
        }

        var allowed = [
            'autoplay',
            'cc_lang_pref',
            'cc_load_policy',
            'color',
            'controls',
            'disablekb',
            'end',
            'fs',
            'hl',
            'iv_load_policy',
            'loop',
            'modestbranding',
            'mute',
            'playlist',
            'playsinline',
            'rel',
            'start'
        ];
        var normalized = new URL('https://www.youtube-nocookie.com/embed/' + videoId);

        if(!copyAllowedQuery(url, normalized, allowed, [])){
            return null;
        }

        return normalized;

    }

    function normalizeVimeoEmbedUrl(url){

        var match = url.pathname.match(/^\/video\/([0-9]{1,20})\/?$/);

        if(!match){
            return null;
        }

        var normalized = new URL('https://player.vimeo.com/video/' + match[1]);
        var allowed = [
            'autopause',
            'autoplay',
            'background',
            'byline',
            'color',
            'controls',
            'dnt',
            'loop',
            'muted',
            'playsinline',
            'portrait',
            'quality',
            'responsive',
            'speed',
            'texttrack',
            'title',
            'transparent'
        ];

        return copyAllowedQuery(url, normalized, allowed, []) ? normalized : null;

    }

    function normalizeGenericEmbedUrl(url){

        if(url.href.length > 2048 || !/^\/[\u0020-\u007E]*$/.test(url.pathname) || url.search.length > 512){
            return null;
        }

        return url;

    }

    function copyAllowedQuery(source, destination, allowed, ignored){

        var valid = true;
        var seen = Object.create(null);

        source.searchParams.forEach(function(value, key){
            if(ignored.indexOf(key) !== -1){
                return;
            }

            if(
                allowed.indexOf(key) === -1
                || seen[key]
                || value.length > 128
                || !/^[A-Za-z0-9._~-]*$/.test(value)
            ){
                valid = false;
                return;
            }

            seen[key] = true;
            destination.searchParams.append(key, value);
        });

        return valid;

    }

    function createServiceIframe(title, url){

        var iframe = document.createElement('iframe');
        iframe.className = 'ulc-embed__iframe';
        iframe.title = title;
        iframe.loading = 'lazy';
        iframe.referrerPolicy = 'no-referrer';
        iframe.allowFullscreen = true;
        iframe.setAttribute('allowfullscreen', '');
        iframe.setAttribute('sandbox', 'allow-scripts allow-same-origin allow-presentation');
        iframe.src = url.href;

        return iframe;

    }

    function renderInterfaceState(){

        if(!initialized || !elements.banner){
            return;
        }

        var hasSavedConsent = Boolean(currentConsent);

        elements.banner.hidden = hasSavedConsent || dialogOpen;
        elements.revisit.hidden = dialogOpen || !hasSavedConsent || !config.showRevisitButton;

    }

    function handleShadowClick(event){

        var actionTarget = closestAction(event.target);

        if(!actionTarget){
            if(dialogOpen && event.target === elements.overlay){
                closePreferences(true);
            }
            return;
        }

        var action = actionTarget.getAttribute('data-action');

        if(action === 'accept-all'){
            if(config.termsRequired){
                showPreferences({selectAll: true, focusTerms: true}, actionTarget);
            }else{
                saveConsent(createConsent({
                    categories: allCategorySelections(true),
                    services: allServiceSelections(true),
                    terms: false
                }), 'accept-all');
            }
            return;
        }

        if(action === 'reject-all'){
            saveConsent(createConsent({
                categories: allCategorySelections(false),
                services: allServiceSelections(false),
                terms: false
            }), 'reject-all');
            return;
        }

        if(action === 'open-preferences'){
            showPreferences({selectAll: false, focusTerms: false}, actionTarget);
            return;
        }

        if(action === 'close-preferences'){
            closePreferences(true);
        }

    }

    function handleDocumentClick(event){

        if(
            event.defaultPrevented
            || (typeof event.button === 'number' && event.button !== 0)
            || event.altKey
            || event.ctrlKey
            || event.metaKey
            || event.shiftKey
        ){
            return;
        }

        var trigger = closestOpenTrigger(event.target);

        if(!trigger){
            return;
        }

        event.preventDefault();
        openPreferences(trigger);

    }

    function closestOpenTrigger(target){

        if(!target || typeof target.closest !== 'function'){
            return null;
        }

        return target.closest('[' + OPEN_TRIGGER_ATTRIBUTE + ']');

    }

    function closestAction(target){

        if(!target || typeof target.closest !== 'function'){
            return null;
        }

        return target.closest('[data-action]');

    }

    function handleShadowChange(event){

        if(event.target === elements.termsInput && elements.termsInput.checked){
            clearTermsError();
        }

        var category = categoryForInput(event.target);

        if(category){
            setServiceInputsForCategory(category, event.target.checked);
            syncCategoryControl(category);
            return;
        }

        var serviceId = event.target && event.target.getAttribute
            ? event.target.getAttribute('data-service-id')
            : '';

        if(serviceId && elements.serviceInputs[serviceId] === event.target){
            syncCategoryControl(config.servicesById[serviceId].category);
        }

    }

    function handleShadowSubmit(event){

        if(event.target !== elements.form){
            return;
        }

        event.preventDefault();

        if(config.termsRequired && !elements.termsInput.checked){
            showTermsError();
            return;
        }

        saveConsent(createConsent({
            categories: categorySelectionsFromInputs(),
            services: serviceSelectionsFromInputs(),
            terms: config.termsRequired && elements.termsInput.checked
        }), 'preferences');

    }

    function handleDocumentKeydown(event){

        if(!dialogOpen){
            return;
        }

        if(event.key === 'Escape'){
            event.preventDefault();
            closePreferences(true);
            return;
        }

        if(event.key !== 'Tab'){
            return;
        }

        var focusable = getDialogFocusableElements();

        if(!focusable.length){
            event.preventDefault();
            focusElement(elements.dialogTitle);
            return;
        }

        var activeElement = shadow.activeElement;
        var first = focusable[0];
        var last = focusable[focusable.length - 1];

        if(event.shiftKey && (activeElement === first || activeElement === elements.dialogTitle)){
            event.preventDefault();
            focusElement(last);
        }else if(!event.shiftKey && activeElement === last){
            event.preventDefault();
            focusElement(first);
        }

    }

    function showPreferences(options, trigger){

        if(!initialized || dialogOpen){
            return;
        }

        options = options || {};
        dialogOpen = true;
        dialogTrigger = trigger || shadow.activeElement || document.activeElement;

        var consent = currentConsent || emptyConsent();
        var serviceId = typeof options.serviceId === 'string' ? options.serviceId : '';

        OPTIONAL_CATEGORIES.forEach(function(category){
            elements.categoryInputs[category].checked = serviceId
                ? false
                : options.selectAll ? true : categoryConsentFrom(consent, category);
            elements.categoryInputs[category].indeterminate = false;
        });

        config.activatableServiceIds.forEach(function(id){
            elements.serviceInputs[id].checked = serviceId
                ? id === serviceId
                : options.selectAll ? true : serviceConsentFrom(consent, id);
        });

        if(gpcActive){
            config.activatableServiceIds.forEach(function(serviceId){
                var service = config.servicesById[serviceId];

                if(service.purpose === 'marketing'){
                    elements.serviceInputs[serviceId].checked = false;
                }
            });
        }

        OPTIONAL_CATEGORIES.forEach(syncCategoryControl);

        if(elements.termsInput){
            elements.termsInput.checked = consent.terms === true;
            clearTermsError();
        }

        clearGenericErrors();
        setBackgroundInert(true);
        lockDocumentScroll(true);
        elements.overlay.hidden = false;
        renderInterfaceState();

        var focusTarget = options.focusTerms && elements.termsInput
            ? elements.termsInput
            : elements.dialogTitle;

        focusElement(focusTarget);

    }

    function categoryForInput(input){

        for(var index = 0; index < CATEGORY_NAMES.length; index += 1){
            var category = CATEGORY_NAMES[index];

            if(elements.categoryInputs[category] === input){
                return category;
            }
        }

        return '';

    }

    function setServiceInputsForCategory(category, checked){

        var inputs = elements.serviceInputsByCategory[category] || [];

        inputs.forEach(function(input){
            var serviceId = input.getAttribute('data-service-id');
            var service = config.servicesById[serviceId];
            input.checked = serviceIsBlockedByGpc(service) ? false : checked;
        });

    }

    function syncCategoryControl(category){

        var input = elements.categoryInputs[category];
        var serviceInputs = elements.serviceInputsByCategory[category] || [];

        if(!input || !serviceInputs.length){
            return;
        }

        var granted = serviceInputs.filter(function(serviceInput){
            return serviceInput.checked;
        }).length;

        input.checked = granted === serviceInputs.length;
        input.indeterminate = granted > 0 && granted < serviceInputs.length;

    }

    function serviceSelectionsFromInputs(){

        var selections = Object.create(null);

        config.activatableServiceIds.forEach(function(serviceId){
            selections[serviceId] = elements.serviceInputs[serviceId].checked === true;
        });

        return selections;

    }

    function categorySelectionsFromInputs(){

        var selections = Object.create(null);

        CATEGORY_NAMES.forEach(function(category){
            selections[category] = category === 'necessary'
                ? true
                : Boolean(elements.categoryInputs[category].checked);
        });

        return selections;

    }

    function allServiceSelections(granted){

        var selections = Object.create(null);

        config.activatableServiceIds.forEach(function(serviceId){
            var service = config.servicesById[serviceId];
            selections[serviceId] = Boolean(granted && !serviceIsBlockedByGpc(service));
        });

        return selections;

    }

    function closePreferences(restoreFocus){

        if(!dialogOpen){
            return;
        }

        dialogOpen = false;
        elements.overlay.hidden = true;
        setBackgroundInert(false);
        lockDocumentScroll(false);
        clearTermsError();
        clearGenericErrors();
        renderInterfaceState();

        if(restoreFocus){
            var target = dialogTrigger;

            if(!target || !target.isConnected || typeof target.focus !== 'function'){
                target = !elements.revisit.hidden ? elements.revisit : null;
            }

            if(target){
                focusElement(target);
            }
        }

        dialogTrigger = null;

    }

    function focusElement(element){

        if(!element || typeof element.focus !== 'function'){
            return;
        }

        try{
            element.focus({preventScroll: true});
        }catch(error){
            element.focus();
        }

    }

    function focusAfterConsentSave(preferredTarget){

        if(preferredTarget && preferredTarget.isConnected && typeof preferredTarget.focus === 'function'){
            focusElement(preferredTarget);
            return;
        }

        if(elements.revisit && !elements.revisit.hidden){
            focusElement(elements.revisit);
            return;
        }

        var target = document.querySelector('main, [role="main"], h1') || document.body;
        var hadTabindex = target.hasAttribute('tabindex');
        var previousTabindex = target.getAttribute('tabindex');

        if(!hadTabindex){
            target.setAttribute('tabindex', '-1');
            target.addEventListener('blur', function cleanupTemporaryTabindex(){
                target.removeAttribute('tabindex');
            }, {once: true});
        }

        focusElement(target);

        if(hadTabindex && previousTabindex !== null){
            target.setAttribute('tabindex', previousTabindex);
        }

    }

    function getDialogFocusableElements(){

        var selector = [
            'a[href]',
            'button:not([disabled])',
            'input:not([disabled])',
            'select:not([disabled])',
            'textarea:not([disabled])',
            '[tabindex]:not([tabindex="-1"])'
        ].join(',');

        return Array.prototype.slice.call(elements.dialog.querySelectorAll(selector)).filter(function(element){
            return !element.hidden && element.getAttribute('aria-hidden') !== 'true';
        });

    }

    function setBackgroundInert(enabled){

        if(!window.HTMLElement || !('inert' in window.HTMLElement.prototype)){
            return;
        }

        if(enabled){
            inertRecords = Array.prototype.slice.call(document.body.children).filter(function(element){
                return element !== host;
            }).map(function(element){
                var record = {
                    element: element,
                    hadAttribute: element.hasAttribute('inert'),
                    attributeValue: element.getAttribute('inert'),
                    propertyValue: element.inert
                };

                element.inert = true;
                return record;
            });
            return;
        }

        inertRecords.forEach(function(record){
            record.element.inert = record.propertyValue;

            if(record.hadAttribute){
                record.element.setAttribute('inert', record.attributeValue === null ? '' : record.attributeValue);
            }else{
                record.element.removeAttribute('inert');
            }
        });
        inertRecords = [];

    }

    function lockDocumentScroll(enabled){

        if(enabled){
            scrollRecord = {
                htmlOverflow: document.documentElement.style.overflow,
                bodyOverflow: document.body.style.overflow
            };
            document.documentElement.style.overflow = 'hidden';
            document.body.style.overflow = 'hidden';
            return;
        }

        if(scrollRecord){
            document.documentElement.style.overflow = scrollRecord.htmlOverflow;
            document.body.style.overflow = scrollRecord.bodyOverflow;
            scrollRecord = null;
        }

    }

    function showTermsError(){

        elements.termsError.hidden = false;
        elements.termsInput.setAttribute('aria-invalid', 'true');
        focusElement(elements.termsInput);

    }

    function clearTermsError(){

        if(!elements.termsError || !elements.termsInput){
            return;
        }

        elements.termsError.hidden = true;
        elements.termsInput.removeAttribute('aria-invalid');

    }

    function showGenericError(inDialog){

        var target = inDialog ? elements.dialogError : elements.bannerError;

        if(!target){
            return;
        }

        target.textContent = config.strings.error.generic;
        target.hidden = false;

    }

    function clearGenericErrors(){

        [elements.bannerError, elements.dialogError].forEach(function(error){
            if(error){
                error.textContent = '';
                error.hidden = true;
            }
        });

    }

    function dispatchDocumentEvent(name, detail){

        var event;

        try{
            event = new CustomEvent(name, {detail: copyEventDetail(detail)});
        }catch(error){
            event = document.createEvent('CustomEvent');
            event.initCustomEvent(name, false, false, copyEventDetail(detail));
        }

        document.dispatchEvent(event);

    }

    function copyEventDetail(detail){

        return {
            version: detail.version,
            consent: copyConsent(detail.consent),
            previousConsent: copyConsent(detail.previousConsent),
            source: typeof detail.source === 'string' ? detail.source : null,
            gpc: detail.gpc === true
        };

    }

})(window, document);
