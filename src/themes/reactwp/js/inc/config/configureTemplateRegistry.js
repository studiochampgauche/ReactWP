import { registerTemplate } from '../TemplateRegistry';

export const configureTemplateRegistry = () => {
    // Default and NotFound are already registered automatically.
    // Uncomment an example below if you want to override or add templates.
    /*
    registerTemplate('Archive', {
        loader: () => import('../../templates/Archive'),
        render: 'static',
        // Use assetKey when the registry name and template filename differ.
        assetKey: 'Archive',
        cache: {
            tags: ['post-type:post']
        }
    });
    registerTemplate('Account', {
        loader: () => import('../../templates/Account'),
        render: 'server',
        cache: {
            html: false
        }
    });
    */

    registerTemplate('HomeTemplate', {
        loader: () => import('../../templates/Default'),
        render: 'static',
        assetKey: 'Default'
    });
};
