import assert from 'node:assert/strict';
import test from 'node:test';
import { collectModuleResources } from '../webpack.shared.config.js';

test('template asset discovery traverses production concatenated modules', () => {
    const defaultTemplate = {
        resource: 'C:\\project\\src\\themes\\reactwp\\js\\templates\\Default.jsx'
    };
    const styleModule = {
        resource: 'C:\\project\\src\\themes\\reactwp\\scss\\templates\\reactwp.scss'
    };
    const concatenatedModule = {
        rootModule: defaultTemplate,
        modules: new Set([defaultTemplate, styleModule])
    };

    assert.deepEqual(collectModuleResources(concatenatedModule), [
        'C:/project/src/themes/reactwp/js/templates/Default.jsx',
        'C:/project/src/themes/reactwp/scss/templates/reactwp.scss'
    ]);
});

test('template asset discovery tolerates empty modules', () => {
    assert.deepEqual(collectModuleResources(null), []);
    assert.deepEqual(collectModuleResources({}), []);
});
