export default class AdvancedScrollingHelper {
    /** @type {Number} currentTimeout */
    static currentTimeout;

    /** @type {Number} currentIteration */
    static currentIteration = 0;

    /** @type {Object} easing */
    static easing = {
        none: null,
        linear: AdvancedScrollingHelper.easeInPower,
        in: AdvancedScrollingHelper.easeInPower,
        out: AdvancedScrollingHelper.easeOutPower,
        inOut: AdvancedScrollingHelper.easeInOutPower,
        bouncy: {
            linear: AdvancedScrollingHelper.easeInPower,
            in: AdvancedScrollingHelper.easeInBouncy,
            out: AdvancedScrollingHelper.easeOutBouncy,
            inOut: AdvancedScrollingHelper.easeInOutBouncy,
        },
    };

    /**
     * Constructor
     *
     * @constructor
     * @param {String} scrollBehavior - Determines if the smooth scrolling has an in, out or inOut easing
     * @param {Number} [degree=1] - This is the mathematical grade of the movement polynomial
     * @param {Boolean} [bouncy=false] - Determines if the movement has a bouncy behavior
     * @returns {void}
     */
    constructor(scrollBehavior, degree = 1, bouncy = false) {
        let easings = AdvancedScrollingHelper.easing;
        if (bouncy === true) {
            easings = easings.bouncy;
        }

        this.easingFunction = easings[scrollBehavior];
        this.degree = (scrollBehavior !== 'linear') ? degree : 1;
    }

    /**
     * Scrolls to a specific DOM Element with smooth scrolling behavior and returns a promise after when the scrolling
     * finished
     *
     * @param {Element} targetElement
     * @param {Number} duration
     * @returns {Promise}
     */
    scrollIntoView(targetElement, duration) {
        if (!duration || duration <= 0) {
            return new Promise((resolve) => { resolve(); });
        }

        const self = document.documentElement;
        const from = (typeof self.scrollTop === 'object' ? self.scrollTop.offsetTop : self.scrollTop);
        const to = (typeof targetElement === 'object' ? targetElement.offsetTop : targetElement);

        this.scrollTo(
            self,
            from,
            to,
            0,
            1 / duration,
            20,
            this.easingFunction,
            this.degree
        );

        return new Promise((resolve) => {
            AdvancedScrollingHelper.currentIteration += 1;
            window.setTimeout(resolve, duration, AdvancedScrollingHelper.currentIteration);
        });
    }

    /**
     * Scrolls to a specific position in the DOM
     *
     * @param {Element} element
     * @param {Number} from
     * @param {Number} to
     * @param {Number} time
     * @param {Number} speed
     * @param {Number} step
     * @param {Function|null} easingFunction
     * @param {Number} degree
     * @returns {void}
     */
    scrollTo(element, from, to, time, speed, step, easingFunction, degree) {
        if (time < 0 || time > 1 || speed <= 0) {
            element.scrollTop = to;

            return;
        }

        window.clearTimeout(AdvancedScrollingHelper.currentTimeout);

        if (!easingFunction) {
            element.scrollTop = to;

            return;
        }
        element.scrollTop = from - ((from - to) * easingFunction(time, degree));
        time += speed * step;


        AdvancedScrollingHelper.currentTimeout = window.setTimeout(() => {
            this.scrollTo(element, from, to, time, speed, step, easingFunction, degree);
        }, step);
    }

    /*
     * Some easing functions, used for smooth scrolling calculation with the possiblities of in, out and inOut easing,
     * as well as bouncy and linear functions
     */

    /**
     * Accelerating easing function, using a polynomial
     *
     * @static
     * @param {Number} t
     * @param {Number} exp
     * @returns {Number}
     */
    static easeInPower(t, exp) {
        return t ** exp;
    }

    /**
     * Decelerating easing function, using a polynomial
     *
     * @static
     * @param {Number} t
     * @param {Number} exp
     * @returns {Number}
     */
    static easeOutPower(t, exp) {
        t -= 1;

        return 1 - (t ** exp) * ((-1) ** exp);
    }

    /**
     * Accelerating & Decelerating easing function, using a polynomial
     *
     * @static
     * @param {Number} t
     * @param {Number} exp
     * @returns {Number}
     */
    static easeInOutPower(t, exp) {
        t *= 2;

        if (t < 1) {
            return (t ** exp) / 2;
        }
        t -= 2;

        return (2 - ((t ** exp) * ((-1) ** exp))) / 2;
    }

    /**
     * Accelerating easing function with bouncy behavior
     *
     * @static
     * @param {Number} t
     * @returns {Number}
     */
    static easeInBouncy(t) {
        const c1 = 1.70158;
        const c3 = c1 + 1;

        return c3 * (t ** 3) - c1 * (t ** 2);
    }

    /**
     * Decelerating easing function with bouncy behavior
     *
     * @static
     * @param {Number} t
     * @returns {Number}
     */
    static easeOutBouncy(t) {
        const c1 = 1.70158;
        const c3 = c1 + 1;

        return 1 + c3 * ((t - 1) ** 3) + c1 * ((t - 1) ** 2);
    }

    /**
     * Accelerating & Deceleratingeasing function with bouncy behavior
     *
     * @static
     * @param {Number} t
     * @returns {Number}
     */
    static easeInOutBouncy(t) {
        const c1 = 1.70158;
        const c2 = c1 * 1.525;

        return t < 0.5
            ? (((2 * t) ** 2) * ((c2 + 1) * 2 * t - c2)) / 2
            : (((2 * t - 2) ** 2) * ((c2 + 1) * (t * 2 - 2) + c2) + 2) / 2;
    }
}
