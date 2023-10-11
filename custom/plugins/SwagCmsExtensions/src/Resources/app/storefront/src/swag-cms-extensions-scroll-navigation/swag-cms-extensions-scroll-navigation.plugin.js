import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';
import ScrollHelper from './swag-cms-extensions-scroll-navigation-advanced-scrolling.helper';

export default class SwagCmsExtensionsScrollNavigation extends Plugin {
    /**
     * Plugin options
     *
     * @type {Object}
     */
    static options = {
        /**
         * Selector of the list with all navigation point links
         *
         * @type {String}
         */
        navigationListSelector: '.scroll-navigation-sidebar-list',

        /**
         * Selector of the complete section, which the navigation list can navigate to.
         *
         * @type {String}
         */
        anchoredSectionsSelector: '.swag-cms-extensions-scroll-navigation-wrapper',

        /**
         * Selector of navigation list entries
         *
         * @type {String}
         */
        navigationAnchorSelector: '.scroll-navigation-anchor',

        /**
         * Selector of the entry element to which the `activeEntryClass` will be applied
         *
         * @type {String}
         */
        entrySelector: '.scroll-navigation-sidebar-entry',

        /**
         * Class name which applies to the currently active navigation list entry
         *
         * @type {String}
         */
        activeEntryClass: 'scroll-navigation-sidebar-entry--active',

        /**
         * Selector of the navigation element for mobile viewports, which shows the list of navigation points
         *
         * @type {String}
         */
        mobileListButtonSelector: '#scroll-navigation-mobile-button-list',

        /**
         * Selector of the up navigation element for mobile viewports
         *
         * @type {String}
         */
        mobileUpButtonSelector: '#scroll-navigation-mobile-button-up',

        /**
         * Selector of the down navigation element for mobile viewports
         *
         * @type {String}
         */
        mobileDownButtonSelector: '#scroll-navigation-mobile-button-down',

        /**
         * Properties of {@link https://developer.mozilla.org/en-US/docs/Web/API/IntersectionObserver}
         *
         * @type {Object}
         */
        observerOptions: {
            rootMargin: '-10% 0% -60% 0%',
        },

        /**
         * Current page entity, necessary to apply the smooth scrolling configuration
         *
         * @type {Object}
         */
        pageSettings: {},
    };

    /**
     * Plugin constructor which filters out the necessary sections for the associated navigation entires.
     *
     * @returns {void}
     */
    init() {
        this.sidebar = this.el;
        const anchoredSections = DomAccess.querySelectorAll(document, this.options.anchoredSectionsSelector);
        this.anchoredSections = anchoredSections;
        this.navigationList = DomAccess.querySelector(this.sidebar, this.options.navigationListSelector);
        this.smoothScrollingEnabled = this.options.pageSettings && this.options.pageSettings.active;

        this.enableSmoothScrolling(this.navigationList);
        this.registerObserver(anchoredSections);
    }

    /**
     * Iterates through the provided navigationList entries and enables smooth scrolling for them
     *
     * @param {Element} navigationList
     * @returns {void}
     */
    enableSmoothScrolling(navigationList) {
        if (!this.smoothScrollingEnabled) {
            this.scrollHelper = new ScrollHelper(
                'none'
            );
        } else {
            this.scrollHelper = new ScrollHelper(
                this.options.pageSettings.easing,
                this.options.pageSettings.easingDegree,
                this.options.pageSettings.bouncy
            );
        }

        const links = DomAccess.querySelectorAll(navigationList, this.options.entrySelector);
        links.forEach((entry) => {
            entry.addEventListener('click', this.onClickScrollSmoothly.bind(this));
        });
    }

    /**
     * Iterates through the provided sections and observes them using an
     * {@link https://developer.mozilla.org/en-US/docs/Web/API/IntersectionObserver}.
     *
     * @param {HtmlCollection} sectionsToObserve
     * @returns {Boolean}
     */
    registerObserver(sectionsToObserve) {
        const observer = new IntersectionObserver(this.onIntersection.bind(this), this.options.observerOptions);
        const sections = Array.from(sectionsToObserve);

        if (!sections || sections.length <= 0) {
            return false;
        }

        sections.forEach((section) => {
            observer.observe(section);
        });

        return true;
    }

    /**
     * Click EventHandler of the navigation list, which enables and performs smooth scrolling
     *
     * @param {Event} event
     * @returns {Boolean}
     */
    onClickScrollSmoothly(event) {
        event.preventDefault();
        const target = event.target;
        const entryClass = this.options.entrySelector.substring(1);

        // Depending on the click position, the event target may differ between the bullet or its entry
        if (![target, target.parentNode].some(element => element.classList.contains(entryClass))) {
            return false;
        }

        const hash = (target.hash !== undefined ? target.hash : target.parentNode.hash);
        this.performSmoothScrolling(hash);

        return true;
    }

    /**
     * Performs the actual smooth scrolling process to an element associated to a specific hash
     *
     * @param {String} hash
     * @returns {void}
     */
    performSmoothScrolling(hash) {
        const target = DomAccess.querySelector(document, hash);
        let duration = 1;

        if (this.smoothScrollingEnabled) {
            duration = this.options.pageSettings.duration;
        }

        this.scrollHelper.scrollIntoView(target, duration).then((promiseIteration) => {
            // Only set the location hash, if this promise is returned by the newest scrolling process
            if (promiseIteration === ScrollHelper.currentIteration) {
                window.location.hash = hash;
            }
        });
    }

    /**
     * Callback which will be fired when an element starts or stops intersecting with the root element of the observer.
     *
     * @param {Array} sections
     * @returns {Boolean}
     */
    onIntersection(sections) {
        const intersectingSection = sections.find(item => item.isIntersecting);

        if (!intersectingSection || !intersectingSection.target) {
            return false;
        }

        const target = intersectingSection.target;

        return this.setActiveNavigationItem(target);
    }

    /**
     * Removes the active class from all navigation entries and sets the provided target as the active navigation
     * entry.
     *
     * @param {Element} target
     * @returns {Boolean}
     */
    setActiveNavigationItem(target) {
        // De-activate all list items
        const navItems = Array.from(DomAccess.querySelectorAll(this.navigationList, this.options.entrySelector));

        if (!navItems || navItems.length <= 0) {
            return false;
        }

        navItems.forEach((item) => {
            item.classList.remove(this.options.activeEntryClass);
        });

        // Get new active item based on section id
        const navigationSection = DomAccess.querySelector(target, this.options.navigationAnchorSelector);
        const activeId = navigationSection.id;

        // Set active navigation entry
        DomAccess
            .querySelector(this.navigationList, `[href="#${activeId}"]${this.options.entrySelector}`)
            .classList.add(this.options.activeEntryClass);

        this.setUpDownNavigation(target);

        return true;
    }

    /**
     * Sets up the up and down navigation buttons for mobile viewport
     *
     * @param {Element} target
     * @returns {Boolean}
     */
    setUpDownNavigation(target) {
        if (this.anchoredSections.length <= 0) {
            return false;
        }

        const activeSection = DomAccess.querySelector(target, this.options.navigationAnchorSelector);
        const activeIndex = Array.from(this.anchoredSections).findIndex((section) => {
            const navigationAnchor = DomAccess.querySelector(section, this.options.navigationAnchorSelector);
            return navigationAnchor.id === activeSection.id;
        });

        if (activeIndex < 0) {
            return false;
        }

        const arrowUpButton = DomAccess.querySelector(this.sidebar, this.options.mobileUpButtonSelector);
        const arrowDownButton = DomAccess.querySelector(this.sidebar, this.options.mobileDownButtonSelector);

        const hashUp = `#${this.getPreviousSectionId(activeIndex)}`;
        const hashDown = `#${this.getNextSectionId(activeIndex)}`;

        arrowUpButton.addEventListener('click', this.performSmoothScrolling.bind(this, hashUp));
        arrowDownButton.addEventListener('click', this.performSmoothScrolling.bind(this, hashDown));

        return true;
    }

    /**
     * Returns the section id of the previous section for the up button on mobile viewports
     *
     * @param {Integer} activeIndex
     * @returns {String}
     */
    getPreviousSectionId(activeIndex) {
        const targetIndex = Math.max(0, activeIndex - 1);

        return this.getSectionIdByIndex(targetIndex);
    }

    /**
     * Returns the section id of the next section for the down button on mobile viewports
     *
     * @param {Integer} activeIndex
     * @returns {String}
     */
    getNextSectionId(activeIndex) {
        const targetIndex = Math.min(this.anchoredSections.length - 1, activeIndex + 1);

        return this.getSectionIdByIndex(targetIndex);
    }

    /**
     * Helper function, which returns the section id of a section by index
     *
     * @param {Integer} targetIndex
     * @returns {String}
     */
    getSectionIdByIndex(targetIndex) {
        const section = DomAccess.querySelector(
            this.anchoredSections[targetIndex],
            this.options.navigationAnchorSelector
        );

        return section.id;
    }
}
