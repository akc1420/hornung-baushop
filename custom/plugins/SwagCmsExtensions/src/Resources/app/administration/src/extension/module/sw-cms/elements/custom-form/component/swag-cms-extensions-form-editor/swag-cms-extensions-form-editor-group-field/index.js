import template from './swag-cms-extensions-form-editor-group-field.html.twig';
import './swag-cms-extensions-form-editor-group-field.scss';

const { Component } = Shopware;
const { mapState } = Shopware.Component.getComponentHelper();

Component.register('swag-cms-extensions-form-editor-group-field', {
    name: 'swag-cms-extensions-form-editor-group-field',

    template,

    inject: [
        'repositoryFactory',
    ],

    mixins: [
        'cms-state',
        'position',
    ],

    props: {
        field: {
            type: Object,
            required: true,
        },

        groupId: {
            type: String,
            required: true,
        },

        groupCount: {
            type: Number,
            required: true,
        },
    },

    data() {
        return {
            showMoveFieldToGroupModal: false,
            moveToGroupId: null,
        };
    },

    computed: {
        ...mapState('swCmsDetailCurrentCustomForm', {
            activeItem: state => state.activeItem,
            groups: state => state.form.groups,
        }),

        fieldHasError() {
            return !!Shopware.State.getters['error/getErrorsForEntity'](
                'swag_cms_extensions_form_group_field',
                this.field.id,
            );
        },

        moveToGroups() {
            return this.groups.filter(group => group.id !== this.groupId);
        },

        isActive() {
            return this.field.id === this.activeItem.id;
        },

        fieldDragData() {
            return {
                delay: 200,
                dragGroup: `editor-field-${this.groupId}`,
                data: { field: this.field },
                onDragEnter: this.onFieldDragSort,
                onDrop: this.onFieldDragStop,
            };
        },

        fieldDropData() {
            return {
                dragGroup: `editor-field-${this.groupId}`,
                data: { field: this.field },
            };
        },

        fieldRepository() {
            return this.repositoryFactory.create('swag_cms_extensions_form_group_field');
        },
    },

    methods: {
        onTitleClick() {
            Shopware.State.commit('swCmsDetailCurrentCustomForm/setActiveItem', this.field);
        },

        onDelete() {
            Shopware.State.commit(
                'swCmsDetailCurrentCustomForm/deleteField',
                {
                    fieldId: this.field.id,
                    groupId: this.groupId,
                },
            );

            const errors = Shopware.State.getters['error/getErrorsForEntity'](
                'swag_cms_extensions_form_group_field',
                this.field.id,
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

        onDuplicate() {
            Shopware.State.commit('swCmsDetailCurrentCustomForm/duplicateField', {
                groupId: this.groupId,
                fieldId: this.field.id,
                repository: this.fieldRepository,
                context: Shopware.Context.api,
            });
        },

        onEdit() {
            Shopware.State.commit('swCmsDetailCurrentCustomForm/setActiveItem', this.field);
        },

        onMoveDown() {
            Shopware.State.commit('swCmsDetailCurrentCustomForm/moveItem', {
                itemId: this.field.id,
                callback: this.raisePositionValue,
            });
        },

        onMoveUp() {
            Shopware.State.commit('swCmsDetailCurrentCustomForm/moveItem', {
                itemId: this.field.id,
                callback: this.lowerPositionValue,
            });
        },

        onFieldDragStop(dragData) {
            Shopware.State.commit('swCmsDetailCurrentCustomForm/setFieldProperty', {
                fieldId: dragData.field.id,
                property: 'isDragging',
                value: false,
            });
        },

        onFieldDragSort(dragData, dropData, validDrop) {
            if (!validDrop) {
                return;
            }

            if (dragData.field.position !== dropData.field.position) {
                Shopware.State.commit('swCmsDetailCurrentCustomForm/setFieldProperty', {
                    fieldId: dragData.field.id,
                    property: 'isDragging',
                    value: true,
                });

                Shopware.State.dispatch('swCmsDetailCurrentCustomForm/setItemPosition', {
                    itemId: dragData.field.id,
                    to: dropData.field.position,
                    from: dragData.field.position,
                    callbackUp: this.lowerPositionValue,
                    callbackDown: this.raisePositionValue,
                });
            }
        },

        onMoveToGroupModalOpen() {
            this.showMoveFieldToGroupModal = true;
        },

        onMoveToGroupModalClose() {
            this.showMoveFieldToGroupModal = false;
        },

        moveFieldToGroup() {
            this.onMoveToGroupModalClose();

            this.$nextTick(() => {
                Shopware.State.dispatch('swCmsDetailCurrentCustomForm/moveFieldToGroup', {
                    toGroupId: this.moveToGroupId,
                    fromGroupId: this.groupId,
                    fieldId: this.field.id,
                    fieldRepository: this.fieldRepository,
                    context: Shopware.Context.api,
                });
            });
        },
    },
});
