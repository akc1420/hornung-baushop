import Plugin from 'src/plugin-system/plugin.class';
import HttpClient from 'src/service/http-client.service';

export default class ClosePopup extends Plugin {

    static options = {};

    pro = [];

    url = '';

    init() {
        this._registerEventListeners();
        this._client = new HttpClient();
        this.pro = [];
    }

    /**
     * Register events to handle opening the Modal OffCanvas
     * by clicking a defined trigger selector
     * @private
     */
    _registerEventListeners() {
        this.el.addEventListener('click', this._closePopup.bind(this));
        this._submitManfature();
        this._submitCategories()
        this._submitproperties();
        this._scrollPoupUp();
        this._setFilterInfo();
        this._resetFilter();
        this._chanceForm();
        this._setSuggestText();
    }

    _setSuggestText() {
        var self = this;
        $(this.el).on("click", ".sisi-suggest-text", function () {
            var text = $(this).html();
            text = text.trim();
            $('.header-search-input').val(text);
            var url = self._mergeUrl(text, '', '', '');
            self.fetch(url, '', '', '');
        });
    }

    _scrollPoupUp() {
        const self = this;
        const $selektor = $('.header-search-form');
        var strScrolling = $selektor.data('scrolling');
        if (!(strScrolling  === 'yes')) {
            document.addEventListener('scroll', function (event) {
                if (event.target.id === 'sisi-search-suggest-container-right') {
                    var elements = document.querySelectorAll('.sisi-last-row');
                    for (var i = 0; i < elements.length; i++) {
                        var viewport = self.isInViewport(elements[i]);
                        if (viewport) {
                            self.loadProduct();
                        }
                    }
                }
            }, true /*Capture event*/);
        }
    }

    loadProduct() {
        const self = this;
        var search = $('.header-search-input').val();
        var url = $('.header-search-form').data('url');
        var url = url + search;
        var $selector = $('#sisi-search-suggest-container');
        var pageNr = $selector.data('from');
        var max = $selector.data('max');
        var size = $selector.data('size');
        var ma = $selector.data('ma');

        if (ma !== undefined && ma !== '') {
            url += '&ma=' + ma;
        }
        if (pageNr === '' || pageNr === undefined) {
            $selector.data('from', 1);
            pageNr = 1;
        }
        if (max > ((pageNr + 1) * size)) {
            pageNr++;
            url += '&p=' + pageNr;
            self._client.get(url, (responseText) => {
                $('#sisi-search-suggest-container').data('from', pageNr);
                var right = $(responseText).find('#sisi-search-suggest-container-right ul').html();
                ;
                $(self.el).find('#sisi-search-suggest-container-right ul').append(right);
            });
        }
    }

    isInViewport(element) {
        const rect = element.getBoundingClientRect();
        if (!element.classList.contains("sisiIsvisible")) {
            if (
                rect.top >= 0 &&
                rect.left >= 0 &&
                rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                rect.right <= (window.innerWidth || document.documentElement.clientWidth)
            ) {
                element.classList.add("sisiIsvisible");
                return true;

            } else {
                return false;
            }

        } else {
            return false;
        }
    }

    _chanceForm() {
        var self = this;
        $('body').on('input', '.header-search-input', function () {
            self.pro = [];
        });

    }

    _resetFilter() {
        const self = this;
        $(this.el).on("click", ".sisi-resetFilter", function () {
            var search = $('.header-search-input').val();
            var url = self._mergeUrl(search, '', '', '');
            self.fetch(url, '', '', '');
            self.pro = [];
            return false;
        });
    }

    _submitCategories() {
        const self = this;
        $(this.el).on("click", ".sisi-categories", function () {
            var $containerSelektor = $('#sisi-search-suggest-container');
            var cat = $(this).data('categories');
            cat = self._replace_and(cat);
            var search = $('.header-search-input').val();
            var ma = $containerSelektor.data('ma');
            ma = self._replace_and(ma);
            var pro = $containerSelektor.data('pro');
            pro = self._replace_and(pro);
            var url = self._mergeUrl(search, cat, ma, pro);
            self.fetch(url, cat, ma, pro);
            return false;
        });

    }

    _submitManfature() {
        const self = this;
        $(this.el).on("click", ".sisi-manufacturer", function () {
            var $containerSelektor = $('#sisi-search-suggest-container');
            var ma = $(this).data('manufacturer');
            ma = self._replace_and(ma);
            var search = $('.header-search-input').val();
            var cat = $containerSelektor.data('cat');
            cat = self._replace_and(cat);
            var url = self._mergeUrl(search, cat, ma, self.pro);
            self.fetch(url, cat, ma, self.pro);
            return false;
        });
    }

    _submitproperties() {
        const self = this;
        $(this.el).on("click", ".sisi-properties", function () {
            var $containerSelektor = $('#sisi-search-suggest-container');
            self.pro.push($(this).data('properties'));
            var search = $('.header-search-input').val();
            var cat = $containerSelektor.data('cat');
            var ma = $containerSelektor.data('ma');
            var url = self._mergeUrl(search, cat, ma, self.pro)
            self.fetch(url, cat, ma, self.pro);
            return false;
        });
    }

    _replace_and(stringValue) {
        return stringValue.toString().replace("&", "");
    }

    _setFilterInfo(cat, ma, pro) {

        var count = 0;

        if (cat !== '' && cat !== undefined && cat !== 0) {
            count++;
        }
        if (ma !== '' && ma !== undefined && ma !== 0) {
            count++;
        }
        if (pro !== '' && pro !== undefined && pro !== 0) {
            for (var i = 0; i < pro.length; i++) {
                if (pro[i] !== '' && pro[i] !== undefined && pro[i] !== 0 && pro[i].length > 2) {
                    count++;
                }
            }
        }
        if (count > 0) {
            $('.sisi-resetFilter').append(count);
        }

    }

    _closePopup(event) {

        $(this.el).on('click', '.sisi-search-suggest-container-close-inner', function () {
            var $popup = $('#sisi-search-suggest-container');
            $popup.css({'display': 'none'});
        });
    }

    _mergeUrl(search, cat, ma, pro) {

        var url = $('.header-search-form').data('url');
        url = url + search;

        if (search !== '' && cat !== '' && cat !== undefined && cat !== 0) {
            url += '&cat=' + cat;
        }

        if (search !== '' && ma !== '' && ma !== undefined && ma !== 0) {
            url += '&ma=' + ma;
        }

        if (pro !== '' && pro !== undefined && pro !== 0) {
            for (var i = 0; i < pro.length; i++) {
                if (pro[i] !== '' && pro[i] !== undefined && pro[i] !== 0 && pro[i].length > 2) {
                    url += '&pro[' + i + ']=' + pro[i];
                }
            }
        }
        return url;
    }

    /**
     * Fetch the latest media from the Instagram account with the given count
     */
    fetch(url, cat, ma, pro) {
        const self = this;
        this._client.get(url, (responseText) => {
            $('.search-suggest').html(responseText);
            self._setFilterInfo(cat, ma, pro)
        });
    }
}
