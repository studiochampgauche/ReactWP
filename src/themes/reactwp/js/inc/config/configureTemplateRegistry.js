import { registerTemplate } from '../TemplateRegistry';

export const configureTemplateRegistry = () => {
    // Default and NotFound are already registered automatically.
    // Uncomment an example below if you want to override or add templates.
    /*
    registerTemplate('Archive', () => import('../../templates/Archive'));
    registerTemplate('SingleService', () => import('../../templates/SingleService'));
    */
};
