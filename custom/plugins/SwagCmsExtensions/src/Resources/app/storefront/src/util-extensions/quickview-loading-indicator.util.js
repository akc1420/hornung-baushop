import LoadingIndicatorUtil from 'src/utility/loading-indicator/loading-indicator.util';

const QUICKVIEW_LOADING_INDICATOR_CLASS = 'quickview-loading-indicator';

export default class QuickviewLoadingIndicatorUtil extends LoadingIndicatorUtil {
    /**
     * returns the loader template
     *
     * @returns {string}
     */
    static getTemplate() {
        return `
            <div class="container ${QUICKVIEW_LOADING_INDICATOR_CLASS}">
                ${super.getTemplate()}
            </div>
        `;
    }
}
