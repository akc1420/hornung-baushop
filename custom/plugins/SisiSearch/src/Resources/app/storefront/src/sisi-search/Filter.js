import Plugin from 'src/plugin-system/plugin.class';
import Iterator from 'src/helper/iterator.helper';
import HttpClient from 'src/service/http-client.service';

export default class Filter extends Plugin {

    pro = [];

    ma = [];

    filterName = [];

    maName = [];

    rating = 0;

    ratingId = '';

    p = 0;

    count = 0;

    hits = 0;

    resetText = '';

    merker = [];

    checkboxen = null;

    checkboxesManufactor = null;

    price = [];

    priceId = [];

    strPrice = true;

    strPricemax = true;

    pricelabel = [];

    mulifilter = '';

    init() {
        this._registerEvents();
        this._client = new HttpClient();
    }

    /**
     * @private
     */
    _registerEvents() {
        const checkboxes = $('.filter-multi-select-checkbox');
        var $self = this;
        var $body = $('body');
        $body.on("change", checkboxes, function (event) {
            var targetEvent = event.originalEvent.target.className;
            var includemaxprice = targetEvent.includes(" sisimax-input");
            var includeminprice = targetEvent.includes(" sisimin-input");
            if ((!includeminprice && !includemaxprice)) {
                $self.p = 1;
                $self._fireEvent();
            }
        });

        $body.on("click", '.sisi-price-event', function (event) {
            $self.strPrice = true;
            $self.strPricemax = true;
            $self._fireEvent();
        });

        $body.on("click", '.sisi-ajax-modus .page-item', function (event) {

            var site = $(this).find('label').data('page');
            var hasdisable = $(this).hasClass('disabled');
            var aktiveSite = "";

            if (isNaN($self.p) || ($self.p === null)) {
                $self.p = 1;
            }

            if (!isNaN(site) && (!hasdisable)) {
                $self.p = parseInt(site);
            }

            if (site === 'first' && (!hasdisable)) {
                $self.p =  1;
            }

            if (site === 'prev' && (!hasdisable)) {

                aktiveSite  = $('.sisi-ajax-modus .page-item.active').find('label').data('page');
                aktiveSite = parseInt(aktiveSite);
                $self.p = aktiveSite - 1;
            }

            if (site === 'next' && (!hasdisable)) {
                aktiveSite  = $('.sisi-ajax-modus .page-item.active').find('label').data('page');
                aktiveSite = parseInt(aktiveSite);
                $self.p = aktiveSite+ 1;
            }

            if (site === 'last' && (!hasdisable)) {
                var lastvalue = $(this).find('label').data('value');
                lastvalue = parseInt(lastvalue);
                $self.p = lastvalue;
            }

            if ((!hasdisable)) {
                $self.fetch(false);
            }

        });
        $body.on("click", '.js-offcanvas-close', function (event) {
            $('.offcanvas').removeClass('is-open');
            $('.modal-backdrop').remove();
        });

        window.onload = function () {
            $self._onSetLabel();
            $self.mulifilter = $('#filter-panel-wrapper').html();
        }

        this._onResetFilter();
        this._onResetAllFilter();
        this._scrollPoupUp();
        this._getPage();

    }

    _getPage() {
        var self = this;
        const queryString = window.location.search;
        const urlParams = new URLSearchParams(queryString);
        self.p = urlParams.get('p');
    }

    _fireEvent() {
        var $self = this;
        $self._onChangeFilter();
    }

    _onResetAllFilter() {
        var $self = this;
        $('body').on("click", ".filter-reset-all", function () {
            const checkboxes = $('.filter-multi-select-properties .filter-multi-select-checkbox:checked');
            const checkboxesManufactor = $('.filter-multi-select-manufacturer .filter-multi-select-checkbox:checked');
            $('.sisimin-input').each(function() {
                $(this).val("");
            });
            $('.sisimax-input').each(function() {
                $(this).val("");
            });
            checkboxes.each(function (index) {
                $(this).prop('checked', false);
            });
            checkboxesManufactor.each(function (index) {
                $(this).prop('checked', false);
            });
            $('.filter-rating-select-radio:checked').prop('checked', false);
            $self.price = [];
            $self.strPrice = false
            $self.strPricemax = false;
            $self._onChangeFilter();
            $self.filterName = [];
            $self.maName = [];
            $self.priceId = [];
        });

    }

    _onResetFilter() {
        var $self = this;
        $('body').on("click", ".filter-active-remove", function () {

            var dataId = $(this).data('id');

            $('.' + dataId).each(function() {
                $(this).prop('checked', false);
            });


            if (dataId === "sisimin-input") {
                $('.sisimin-input').each(function() {
                    $(this).val("");
                });
                $("button[data-id="+dataId+"]").parent().remove();
                $self.strPrice = false;
                $self.price[0] = 0;
                $self.priceId[0] = "";
            }
            if (dataId === "sisimax-input") {
                $('.sisimax-input').each(function() {
                    $(this).val("");
                });
                $("button[data-id="+dataId+"]").parent().remove();
                $self.strPricemax = false;
                $self.price[1] = 0
                $self.priceId[1] = "";
            }
            $self._onChangeFilter();

        });
    }


    _onSetLabel() {
        var self = this;
        self._onIterateFilter();
        var filter = self.filterName.concat(self.maName);
        var ids = self.pro.concat(self.ma);
        var filterHtml = self._setFilter(filter, ids);
        $('.filter-panel-active-container').html(filterHtml);
        this._getPage();
    }


    /**
     * @private
     */
    _onChangeFilter() {
        const self = this;
        self._onIterateFilter();
        this.fetch(false);

    }
    _onIterateFilter() {
        const self = this;
        const checkboxes = $('.filter-multi-select-properties .filter-multi-select-checkbox:checked');
        const checkboxesManufactor = $('.filter-multi-select-manufacturer .filter-multi-select-checkbox:checked');
        var $selektorRating = $('.filter-rating-select-radio:checked');
        self.rating = $selektorRating.val();
        self.ratingId = $selektorRating.attr('id');
        if (self.strPrice) {
            $('.sisimin-input').each(function () {
                self.price[0] = $(this).val();
                self.priceId[0] = $(this).attr('id');
                self.strPrice = true;

            });
        }
        if (self.strPricemax) {
            $('.sisimax-input').each(function () {
                self.price[1] = $(this).val();
                self.priceId[1] = $(this).attr('id');
                self.strPricemax = true;
            });
        }
        if (!self.strPrice && !self.strPricemax)  {
            self.price = [];
        }
        var $selektorpricelabel =  $('.sisifilter-range');
        var pricemin = $selektorpricelabel.data('pricefrom');
        var pricemax = $selektorpricelabel.data('priceto');
        var symbol = $selektorpricelabel.data('symbol');
        this.pro = [];
        self.filterName = [];
        self.maName = [];
        self.pricelabel[0] = "";
        self.pricelabel[1] = "";
        checkboxes.each(function (index) {
            var val = $(this).attr('id');
            if (!self.pro.includes(val) && val !== undefined) {
                self.pro.push(val);
                var name = $(this).parent().parent().find('label').html();
                name = name.replace(/(\r\n|\n|\r)/gm, "");
                name = name.trim();
                self.filterName.push(name);

            }
        });
        this.ma = [];
        checkboxesManufactor.each(function (index) {
            var val = $(this).attr('id');
            if (!self.ma.includes(val) && val !== undefined) {
                self.ma.push(val);
                var name = $(this).parent().parent().find('label').html();
                name = name.replace(/(\r\n|\n|\r)/gm, "");
                self.maName.push(name);
            }
        });

        if (this.price[0]) {
            self.pricelabel[0] = pricemin + " " + this.price[0] + " " + symbol;
        }
        if (this.price[1]) {
            self.pricelabel[1] = pricemax + " " + this.price[1] + " " + symbol;
        }

        if (parseInt(self.rating)) {
            var $selektor =  $('.sisi-multifilter-rating');
            var ratingLabel = $selektor.data('label-min') + " " +  self.rating +"/5 " + $selektor.data('label-end');
            self.filterName.push(ratingLabel);
        }
        self.merker = [];
    }

    _scrollPoupUp() {
        const self = this;
        const $selektor = $('.search-headline');
        var strScrolling = $selektor.data('scrolling');
        if (strScrolling  === 'yes') {
            document.addEventListener('scroll', function (event) {
                var elements = document.querySelectorAll('.sisi-last-row');
                if (self.p === undefined) {
                    self.p = 1;
                }
                for (var i = 0; i < elements.length; i++) {
                    var viewport = self.isInViewport(elements[i]);
                    if (viewport) {
                        if (self.p === 0) self.p++;
                        if (self.p === 1) self.p++;
                        if (self.p === null) self.p = 1;
                        if ((self.merker.indexOf(self.p)) === -1 && (self.p > 1)) {
                            self.merker.push(self.p)
                            self.fetch(true);
                        }

                    }
                }

            }, true /*Capture event*/);
        }
    }

    _get_p()
    {
        var vars = {};
        var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi,
            function(m,key,value) {
                vars[key] = value;
            });
     return vars['p'];
    }

    isInViewport(element) {
        const rect = element.getBoundingClientRect();
        if (
            rect.top <= 0 &&
            rect.left >= 0 &&
            rect.bottom <= ((window.innerHeight+200) || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        ) {
            element.classList.add("sisiIsvisible");
            return true;

        } else {
            return false;
        }

    }

    _setFilter(filter, ids) {
        var filterHtml = '';
        var self = this;
        var text = $('.search-headline').data('reset');
        var count = 0;
        var str = true;
        var textpricemin = "";
        var textpricemax = "";

        $.each(filter, function (key, value) {
            filterHtml = filterHtml + '<span class="filter-active"> ' + value
                + '<button class="filter-active-remove" data-id="' + ids[key] + '"> ×</button></span>';
            count++;
        });

        if (self.priceId[0] === 'sisimin-input' && parseInt(self.price[0]) >= 0) {
            textpricemin = '<span class="filter-active"> ' + this.pricelabel[0]
                + '<button class="filter-active-remove" data-id="' + this.priceId[0] + '"> ×</button></span>';
        }
        if (self.priceId[1] === 'sisimax-input' && parseInt(self.price[1]) >= 0) {
            textpricemax = '<span class="filter-active"> ' + this.pricelabel[1]
                + '<button class="filter-active-remove" data-id="' + this.priceId[1] + '"> ×</button></span>';
        }

        if (textpricemin !== "") {
            filterHtml += textpricemin;
            count++;
        }

        if (textpricemax !== "") {
            filterHtml += textpricemax;
            count++;
        }

        if (filterHtml !== '' || self.pro.length > 0 || self.ma.length > 0 || self.pricemax !== undefined
            || self.pricemin !== undefined || self.ra !== undefined) {
            filterHtml = filterHtml + '<button class="filter-reset-all btn btn-sm btn-outline-danger">' + text + '</button>'
        }


        return filterHtml;
    }

    _IterateFilter(url) {
        var index = 0;
        var indexMa = 0;
        var ids = [];
        var self = this;
        this.rating = parseInt(this.rating);
        Iterator.iterate(this.pro, (item) => {
            url = url + '&pro[' + index + ']=' + item;
            ids[index] = item;
            index++;
        });

        Iterator.iterate(this.ma, (item) => {
            url = url + '&ma[' + indexMa + ']=' + item;
            ids[index] = item;
            indexMa++;
            index++;
        });


        $.each(this.price, function(i, item ) {
            ids[index] = [];
            if (item !== "" && item !== undefined && item !== false) {
                url = url + '&pri[' + i + ']=' + item;
                ids[index] = self.priceId[i];
                indexMa++;
                if(self.price[i] > 0) {
                    index++;
                }
            }
        });

        if (this.rating > 0) {
            url = url.trim() + '&ra=' + this.rating;
            ids[index] = this.ratingId;
            index++;
        }

        if (this.p > 0) {
            url = url + "&p=" + this.p;
            index++;
        }

        return [ids, url, index]
    }

    /**
     * Fetch the latest media from the Instagram account with the given count
     */
    fetch(str) {
        const self = this;
        var $selktorheahline = $('.search-headline');
        var url = $selktorheahline.data('ajax');
        var strFilter = $selktorheahline.data('strfilter');
        var search = '?search=' + $('.header-search-input').val();
        var filterHtml = [];
        var ids = [];
        var filter = self.filterName.concat(self.maName);
        url = url + search;
        var iterate = self._IterateFilter(url);
        ids = iterate[0];
        url = iterate[1];
        var mulifilter = '';
        if (!str) {
            self.p = self._get_p();
        }
        if (self.p === undefined) {
            self.p = 1;
        }
        if (!(iterate[2] > 0)) {
            url = url + "&rest=1";
        }
        var itemsLoaded = $(".cms-listing-row .product-box").length;
        if ((self.count > itemsLoaded) || (self.count === 0) || (str === false)) {
            filterHtml = self._setFilter(filter, ids);
            var loader = '<div class="cms-listing-col col-xl-12 sisi-listing-loder"><div class="loader" role="status"' +
                'style="display: inline-block; margin-left: 49%"><span class="sr-only">Loading...</span></div></div>';
            $('.row.cms-listing-row').append(loader);
            var curreny = $('.filter-range-currency-symbol').html();
            if (self.strScrolling !== 'yes' && self.strScrolling !== 'noget') {
                window.history.pushState({}, "", url);
            }
            this._client.get(url, (responseText) => {
                var data = $(responseText).find('.cms-listing-row').html();
                var $hitsSelektor = $(responseText).find('.search-headline');
                var hits = $hitsSelektor.html();
                var count = $hitsSelektor.data('count');
                var checked = this.pro.concat(this.ma);
                var plast = $hitsSelektor.data('last');
                var navi = $(responseText).find('.pagination-nav').first();
                var $panel = $(responseText).find('#filter-panel-wrapper');
                if (strFilter === 'yes') {
                    $.each(ids, function (index, value) {
                        var $selektor = $panel.find('#' + value).parent().parent().parent().parent();
                        $selektor.addClass('show');
                        $selektor.parent().addClass('show');
                    });
                }
                $panel.find('.filter-range-currency-symbol').html(curreny)
                var filterPanel = $panel.html();
                var $selektorNav = $('.pagination-nav');
                $selektorNav.html(navi);
                if (self.p > 1 && self.strScrolling === 'yes') {
                    $('.cms-listing-row').append(data);
                } else {
                    $('.cms-listing-row').html(data);
                }
                $('.search-headline').html(hits).attr('data-count', count).attr('data-last', plast);
                var $selktorPanelWrapper = $('#filter-panel-wrapper');
                var $selktorPanelWrapperMobile = $('.offcanvas');
                if (ids.length === 0) {
                    $selktorPanelWrapper.html(self.mulifilter);
                    $selktorPanelWrapperMobile.html(self.mulifilter);
                } else {   
                    $selktorPanelWrapper.html(filterPanel);
                    $selktorPanelWrapperMobile.html(filterPanel);
                }
                ids = [];
                $(".filter-multi-select-checkbox").each(function (i) {
                    var valItelm = $(this).val();
                    if (checked.includes(valItelm)) {
                        $(this).prop('checked', true);
                    }
                });
                $('#rating-' + self.rating).prop('checked', true);
                var iterate = self._IterateFilter(url);
                ids = iterate[0];
                url = iterate[1];
                filterHtml = self._setFilter(filter, ids);
                $('.sisi-listing-loder').remove();
                $('.filter-panel-active-container').html(filterHtml);
                self.count = count;
                var $cmslistingRow = $(".cms-listing-row .cms-listing-col");
                var len = $cmslistingRow.length;
                $cmslistingRow.each(function( index ) {
                    $(this).removeClass('sisi-last-row');
                    if (index === (len -1)) {
                        $(this).addClass('sisi-last-row')
                    }
                });
                if (str) {
                    self.p++;
                }
                if (self.price.length > 0) {
                    if (self.strPrice) {
                        $('.sisimin-input').val(self.price[0]);
                    }
                    if (self.strPricemax) {
                        $('.sisimax-input').val(self.price[1]);
                    }
                    var $rangeselektor = $('.sisifilter-range');
                    $rangeselektor.addClass('position-static');
                    var $selektor = $rangeselektor.find('.filter-range-dropdown');
                    $selektor.addClass('show')
                    $rangeselektor.find('.filter-panel-item-toggle').attr('aria-expanded', true);
                    var p = $rangeselektor.first();
                    var positionPreise = p.position();
                    var $selektorDrop = $('.filter-range-dropdown');
                    $selektorDrop.removeClass('show').css(
                        {
                            position: "absolute",
                            transform: "translate3d(" +positionPreise.left + "px," +positionPreise.top + "px, 0px)",
                            top: "50px",
                            left: "0px"
                        }
                    ).css("will-change", "transform").attr('x-placement', "bottom-start").on( "click", function(event) {
                        if (!(event.target.id === "sisimin-input" || event.target.id === "sisimax-input")) {
                            $selektorDrop.removeClass('show');
                        }
                    });
                }
            });
        }
    }
}
