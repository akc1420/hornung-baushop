/* eslint-disable max-len */
import { shallowMount, createLocalVue } from '@vue/test-utils';
import 'src/module/sw-settings-rule/component/sw-settings-rule-assignment-listing';
import 'src/module/sw-settings-rule/view/sw-settings-rule-detail-assignments';
import 'src/app/component/entity/sw-entity-listing';
import 'src/app/component/data-grid/sw-data-grid';
import 'src/app/component/context-menu/sw-context-button';
import 'src/app/component/context-menu/sw-context-menu';
import 'src/app/component/context-menu/sw-context-menu-item';
import 'src/app/component/utils/sw-popover';
import EntityCollection from 'src/core/data/entity-collection.data';
import flushPromises from 'flush-promises';

// Component override
import '../../../src/extension/module/sw-settings-rule/sw-settings-rule-detail-assignments';

function createEntityCollectionMock(entityName, items = []) {
    return new EntityCollection('/route', entityName, {}, {}, items, items.length);
}

function createWrapper(entitiesWithResults = []) {
    const localVue = createLocalVue();
    localVue.directive('tooltip', {});

    return shallowMount(Shopware.Component.build('sw-settings-rule-detail-assignments'), {
        localVue,

        mocks: {
            $tc: key => key,
            $te: key => key,
            $device: { onResize: () => {} }
        },

        stubs: {
            'sw-card': {
                template: '<div class="sw-card"><slot name="toolbar"></slot><slot name="grid"></slot></div>'
            },
            'sw-loader': true,
            'sw-empty-state': true,
            'sw-settings-rule-assignment-listing': Shopware.Component.build('sw-settings-rule-assignment-listing'),
            'sw-entity-listing': Shopware.Component.build('sw-entity-listing'),
            'sw-data-grid': Shopware.Component.build('sw-data-grid'),
            'sw-pagination': true,
            'sw-context-button': Shopware.Component.build('sw-context-button'),
            'sw-checkbox-field': true,
            'sw-context-menu-item': true,
            'sw-icon': true,
            'sw-button': true,
            'sw-field-error': true,
            'sw-card-filter': true,
            'router-link': {
                template: '<a class="router-link" :detail-route="to.name"><slot></slot></a>',
                props: ['to']
            }
        },
        propsData: {
            ruleId: 'uuid1',
            rule: {
                name: 'Test rule',
                priority: 7,
                description: 'Lorem ipsum',
                type: ''
            }
        },
        provide: {
            acl: {
                can: () => true
            },
            feature: {
                isActive: () => true
            },
            validationService: {},
            shortcutService: {
                startEventListener: () => {},
                stopEventListener: () => {}
            },

            repositoryFactory: {
                create: (entityName) => {
                    return {
                        search: (_, api) => {
                            const entities = [
                                { section:{ page: { id: '123', name: 'Foo' } } },
                                { section:{ page: { id: '456', name: 'Bar' } } },
                                { section:{ page: { id: '789', name: 'Baz' } } },
                            ];

                            if (api.inheritance) {
                                entities.push({ name: 'Inherited' });
                            }

                            if (entitiesWithResults.includes(entityName)) {
                                return Promise.resolve(createEntityCollectionMock(entityName, entities));
                            }

                            return Promise.resolve(createEntityCollectionMock(entityName));
                        }
                    };
                }
            },

            ruleConditionDataProviderService: {}
        }
    });
}

describe('src/module/sw-settings-rule/view/sw-settings-rule-detail-assignments', () => {
    it('should be a Vue.JS component', async () => {
        const wrapper = createWrapper();

        expect(wrapper.vm).toBeTruthy();
    });

    it('should prepare association entities list', async () => {
        const wrapper = createWrapper([]);

        expect(wrapper.vm.associationEntities).toEqual(
            expect.arrayContaining([
                expect.objectContaining({
                    id: 'swagCmsExtensionsBlockRule',
                    entityName: 'cms_block',
                    detailRoute: expect.any(String),
                    repository: expect.any(Object),
                    gridColumns: expect.any(Array),
                    criteria: expect.any(Function),
                    loadedData: null
                }),
            ])
        );
    });

    it('should try to load and assign entity data for defined entities', async () => {
        const wrapper = createWrapper([
            'cms_block',
        ]);
        await flushPromises();

        const expectedEntityCollectionResult = expect.arrayContaining([
            expect.objectContaining({ section:{ page: { id: '123', name: 'Foo' } } } ),
            expect.objectContaining({ section:{ page: { id: '456', name: 'Bar' } } } ),
            expect.objectContaining({ section:{ page: { id: '789', name: 'Baz' } } })
        ]);

        expect(wrapper.vm.associationEntities).toEqual(
            expect.arrayContaining([
                expect.objectContaining({
                    id: 'swagCmsExtensionsBlockRule',
                    entityName: 'cms_block',
                    detailRoute: expect.any(String),
                    repository: expect.any(Object),
                    gridColumns: expect.any(Array),
                    criteria: expect.any(Function),
                    loadedData: expectedEntityCollectionResult
                }),
            ])
        );
    });

    it('should render an entity-listing for each entity when all entities have results', async () => {
        const wrapper = createWrapper([
            'cms_block',
        ]);
        await flushPromises();

        // Expect entity listings to be present
        expect(wrapper.find('.sw-settings-rule-detail-assignments__card-swagCmsExtensionsBlockRule .router-link').exists()).toBeTruthy();

        // Empty states should not be present
        expect(wrapper.find('.sw-settings-rule-detail-assignments__entity-empty-state-swagCmsExtensionsBlockRule').exists()).toBeFalsy();

        // Loader should not be present
        expect(wrapper.find('.sw-settings-rule-detail-assignments__card-loader').exists()).toBeFalsy();
    });

    it('should render an entity-listing also if no assignment is found', async () => {
        const wrapper = createWrapper([]);
        await flushPromises();

        // Expect entity listings to not be present
        expect(wrapper.find('.sw-settings-rule-detail-assignments__card-swagCmsExtensionsBlockRule .router-link').exists()).toBeFalsy();

        // Expect empty states to be present
        expect(wrapper.find('.sw-settings-rule-detail-assignments__entity-empty-state-swagCmsExtensionsBlockRule').exists()).toBeTruthy();

        // Loader should not be present
        expect(wrapper.find('.sw-settings-rule-detail-assignments__card-loader').exists()).toBeFalsy();
    });

    it('should render an empty-state when none of the associated entities returns a result', async () => {
        const wrapper = createWrapper();
        await flushPromises();

        expect(wrapper.find('.sw-settings-rule-detail-assignments__entity-empty-state').exists()).toBeTruthy();
        expect(wrapper.find('.sw-settings-rule-detail-assignments__card-loader').exists()).toBeFalsy();
    });

    it('should have the right link inside the template', async () => {
        const wrapper = createWrapper([
            'cms_block',
        ]);
        await flushPromises();

        const cmsListing = wrapper.find(
            '.sw-settings-rule-detail-assignments__entity-listing-swagCmsExtensionsBlockRule .sw-data-grid__cell--section-page-name  .router-link'
        );

        // expect entity listing to exist
        expect(cmsListing.exists()).toBe(true);


        const categoryDetailRouteAttribute = cmsListing.attributes('detail-route');

        // expect detail-route attribute to be correct
        expect(categoryDetailRouteAttribute).toBe('sw.cms.detail');
    });
});
