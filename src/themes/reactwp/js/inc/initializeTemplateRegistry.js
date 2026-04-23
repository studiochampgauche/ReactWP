import { resetTemplateRegistry } from './TemplateRegistry';
import { configureTemplateRegistry } from './config/configureTemplateRegistry';

export const initializeTemplateRegistry = () => {
    resetTemplateRegistry();
    configureTemplateRegistry();
};
