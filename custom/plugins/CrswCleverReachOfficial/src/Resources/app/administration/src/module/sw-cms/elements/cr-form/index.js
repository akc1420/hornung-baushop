import './component';
import './config';
import './preview';
import './snippet/de-DE.json';
import './snippet/en-GB.json';
import './snippet/es-ES.json';
import './snippet/fr-FR.json';
import './snippet/it-IT.json';

const Criteria = Shopware.Data.Criteria;
const criteria = new Criteria();

Shopware.Service('cmsService').registerCmsElement({
    name: 'cr-form',
    label: 'CleverReach Form',
    component: 'sw-cms-el-cr-form',
    configComponent: 'sw-cms-el-config-cr-form',
    previewComponent: 'sw-cms-el-preview-cr-form',
    defaultConfig: {
        queue: {
            source: 'static',
            value: null,
            required: true,
            entity: {
                name: 'cleverreach_entity',
                criteria: criteria
            }
        }
    },

    defaultData: {
        boxLayout: 'standard',
        queue: {
            htmlContent: `Your CR Form`.trim(),
        }
    },

    collect: function collect(elem) {
        const context = Object.assign(
            {},
            Shopware.Context.api,
            { inheritance: true }
        );

        const criteriaList = {};

        Object.keys(elem.config).forEach((configKey) => {
            if (elem.config[configKey].source === 'mapped') {
                return;
            }

            const entity = elem.config[configKey].entity;

            if (entity && elem.config[configKey].value) {
                const entityKey = entity.name;
                const entityData = {
                    value: [elem.config[configKey].value],
                    key: configKey,
                    searchCriteria: entity.criteria ? entity.criteria : new Criteria(),
                    ...entity
                };

                entityData.searchCriteria.setIds(entityData.value);
                entityData.context = context;

                criteriaList[`entity-${entityKey}`] = entityData;
            }
        });

        return criteriaList;
    }
});
