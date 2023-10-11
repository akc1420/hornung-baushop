import template from './swag-cms-extensions-form-editor-group.html.twig';
import './swag-cms-extensions-form-editor-group.scss';

const { Component } = Shopware;
const { mapState } = Shopware.Component.getComponentHelper();

Component.register('swag-cms-extensions-form-editor-group', {
    name: 'swag-cms-extensions-form-editor-group',

    template,

    inject: [
        'repositoryFactory',
    ],

    mixins: [
        'cms-state',
        'position',
        'swag-cms-extensions-form-group-field-error',
    ],

    props: {
        group: {
            type: Object,
            required: true,
        },

        groupCount: {
            type: Number,
            required: true,
        },
    },

    computed: {
        ...mapState('swCmsDetailCurrentCustomForm', [
            'activeItem',
            'form',
        ]),

        groupHasError() {
            return !!Shopware.State.getters['error/getErrorsForEntity'](
                'swag_cms_extensions_form_group',
                this.group.id,
            );
        },

        formGroupRepository() {
            return this.repositoryFactory.create('swag_cms_extensions_form_group');
        },

        formFieldRepository() {
            return this.repositoryFactory.create('swag_cms_extensions_form_group_field');
        },

        isActive() {
            return this.group.id === this.activeItem.id;
        },

        groupDragData() {
            return {
                delay: 200,
                dragGroup: 'editor-group',
                data: { group: this.group },
                onDragEnter: this.onGroupDragSort,
                onDrop: this.onGroupDragStop,
            };
        },

        groupDropData() {
            return {
                dragGroup: 'editor-group',
                data: { group: this.group },
            };
        },
    },

    methods: {
        onAddField() {
            Shopware.State.commit('swCmsDetailCurrentCustomForm/addField', {
                groupId: this.group.id,
                repository: this.formFieldRepository,
                context: Shopware.Context.api,
                prefix: this.$tc(
                    'swag-cms-extensions.sw-cms.components.form-editor.group.field.fieldPrefix',
                ),
            });

            this.validateDuplicateTechnicalName(this.form, this.group.fields.last());
        },

        onTitleClick() {
            Shopware.State.commit('swCmsDetailCurrentCustomForm/setActiveItem', this.group);
        },

        onMoveUp() {
            Shopware.State.commit('swCmsDetailCurrentCustomForm/moveItem', {
                itemId: this.group.id,
                callback: this.lowerPositionValue,
            });
        },

        onMoveDown() {
            Shopware.State.commit('swCmsDetailCurrentCustomForm/moveItem', {
                itemId: this.group.id,
                callback: this.raisePositionValue,
            });
        },

        onDuplicate() {
            Shopware.State.dispatch('swCmsDetailCurrentCustomForm/duplicateGroup', {
                groupId: this.group.id,
                groupRepository: this.formGroupRepository,
                groupPrefix: this.$tc(
                    'swag-cms-extensions.sw-cms.components.form-editor.group.groupNamePrefix',
                ),
                fieldRepository: this.formFieldRepository,
                context: Shopware.Context.api,
            });
        },

        onDelete() {
            const fieldIds = this.group.fields.map(field => field.id);

            Shopware.State.commit('swCmsDetailCurrentCustomForm/deleteGroup', this.group.id);

            // Remove errors in state for fields in group
            fieldIds.forEach((fieldId) => {
                const errors = Shopware.State.getters['error/getErrorsForEntity'](
                    'swag_cms_extensions_form_group_field',
                    fieldId,
                );

                if (!errors) {
                    return;
                }

                Object.keys(errors).forEach((key) => {
                    Shopware.State.dispatch(
                        'error/removeApiError',
                        { expression: errors[key].selfLink },
                    );
                });
            });

            // Remove errors in state for the group
            const errors = Shopware.State.getters['error/getErrorsForEntity'](
                'swag_cms_extensions_form_group',
                this.group.id,
            );

            if (!errors) {
                return;
            }

            Object.keys(errors).forEach((key) => {
                Shopware.State.dispatch(
                    'error/removeApiError',
                    { expression: errors[key].selfLink },
                );
            });
        },

        onGroupDragStop() {
            Shopware.State.commit('swCmsDetailCurrentCustomForm/setGroupProperty', {
                groupId: this.group.id,
                property: 'isDragging',
                value: false,
            });
        },

        onGroupDragSort(dragData, dropData, validDrop) {
            if (!validDrop) {
                return;
            }

            if (dragData.group.position !== dropData.group.position) {
                Shopware.State.commit('swCmsDetailCurrentCustomForm/setGroupProperty', {
                    groupId: this.group.id,
                    property: 'isDragging',
                    value: true,
                });

                Shopware.State.dispatch('swCmsDetailCurrentCustomForm/setItemPosition', {
                    itemId: dragData.group.id,
                    to: dropData.group.position,
                    from: dragData.group.position,
                    callbackUp: this.lowerPositionValue,
                    callbackDown: this.raisePositionValue,
                });
            }
        },
    },
});
