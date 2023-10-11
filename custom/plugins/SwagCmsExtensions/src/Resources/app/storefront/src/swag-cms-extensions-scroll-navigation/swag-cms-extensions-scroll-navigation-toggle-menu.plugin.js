import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';

export default class SwagCmsExtensionsScrollNavigationToggleMenu extends Plugin {
    /**
     * Plugin options
     *
     * @type {Object}
     */
    static options = {
        /**
         * Selector of the menu toggle siwtches
         *
         * @type {String}
         */
        toggleSwitchSelector: '.scroll-navigation-menu-toggle',

        /**
         * Class, which is applied on the scroll navigation if the list is visible
         *
         * @type {String}
         */
        visibleClass: 'list--visible',
    };

    /**
     * Plugin constructor which binds the onclick function to each target toggle switch
     *
     * @returns {void}
     */
    init() {
        this.sidebar = this.el;
        const toggleSwitches = DomAccess.querySelectorAll(this.sidebar, this.options.toggleSwitchSelector);

        if (toggleSwitches === null) {
            return;
        }

        toggleSwitches.forEach((toggleSwitch) => {
            toggleSwitch.onclick = this.toggleVisibility.bind(this);
        });
    }

    /**
     * Toggles the visibleClass on the scroll navigation
     *
     * @returns {void}
     */
    toggleVisibility() {
        if (this.sidebar.classList.contains(this.options.visibleClass)) {
            this.sidebar.classList.remove(this.options.visibleClass);
        } else {
            this.sidebar.classList.add(this.options.visibleClass);
        }
    }
}
