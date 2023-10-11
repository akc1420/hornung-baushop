import template from './swag-cms-extensions-form-fields.html.twig';
import './swag-cms-extensions-form-fields.scss';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;
const { mapState } = Shopware.Component.getComponentHelper();

Component.register('swag-cms-extensions-form-fields', {
    name: 'swag-cms-extensions-form-fields',

    template,

    inject: [
        'repositoryFactory',
    ],

    mixins: [
        'swag-cms-extensions-form-group-field-error',
    ],

    computed: {
        ...mapState('swCmsDetailCurrentCustomForm', {
            form: state => state.form,
            activeItem: state => state.activeItem,
            groups: state => state.form.groups,
        }),

        groupRepository() {
            return this.repositoryFactory.create('swag_cms_extensions_form_group');
        },

        fieldRepository() {
            return this.repositoryFactory.create('swag_cms_extensions_form_group_field');
        },

        formHasGroups() {
            return this.groups.length > 0;
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            if (this.groups.length >= 1) {
                this.setFirstGroupAsActiveItem();
                return;
            }

            const criteria = new Criteria();
            criteria.addFilter(
                Criteria.equals('formId', this.form.id),
            );
            criteria.addAssociation('fields');
            criteria.addSorting(Criteria.sort('position'));
            criteria.getAssociation('fields').addSorting(Criteria.sort('position'));

            this.groupRepository.search(criteria, Shopware.Context.api).then((groups) => {
                Shopware.State.commit('swCmsDetailCurrentCustomForm/setFormProperty', {
                    property: 'groups',
                    value: groups,
                });

                if (this.groups.length >= 1) {
                    this.setFirstGroupAsActiveItem();
                }
            });
        },

        setFirstGroupAsActiveItem() {
            Shopware.State.commit('swCmsDetailCurrentCustomForm/setActiveItem', this.groups[0]);
        },

        onStartEditor() {
            this.onGroupAdd();
        },

        onGroupAdd() {
            Shopware.State.dispatch('swCmsDetailCurrentCustomForm/addGroup', {
                groupRepository: this.groupRepository,
                fieldRepository: this.fieldRepository,
                groupPrefix: this.$tc(
                    'swag-cms-extensions.sw-cms.components.form-editor.group.groupNamePrefix',
                ),
                fieldPrefix: this.$tc(
                    'swag-cms-extensions.sw-cms.components.form-editor.group.field.fieldPrefix',
                ),
                context: Shopware.Context.api,
            });

            this.validateDuplicateTechnicalName(this.form, this.form.groups.last().fields.last());
        },
    },
});
