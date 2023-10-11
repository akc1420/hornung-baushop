import template from './swag-cms-extensions-form-editor-settings-group-field.html.twig';
import './swag-cms-extensions-form-editor-settings-group-field.scss';

const { Component } = Shopware;
const { mapState } = Shopware.Component.getComponentHelper();

Component.register('swag-cms-extensions-form-editor-settings-group-field', {
    name: 'swag-cms-extensions-form-editor-settings-group-field',

    template,

    data() {
        return {
            displayHeader: true,
        };
    },

    computed: {
        ...mapState('swCmsDetailCurrentCustomForm', {
            item: state => state.activeItem,
        }),

        fieldComponentName() {
            return `swag-cms-extensions-form-editor-settings-field-type-${this.item.type}`;
        },
    },

    watch: {
        item(newItem, oldItem) {
            if (newItem.id === oldItem.id) {
                return;
            }

            // This prevents unintentional removal of api errors on active item change
            this.displayHeader = false;

            this.$nextTick(() => {
                this.displayHeader = true;
            });
        },
    },
});
