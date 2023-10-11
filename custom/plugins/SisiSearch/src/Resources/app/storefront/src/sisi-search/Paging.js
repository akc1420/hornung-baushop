import Plugin from 'src/plugin-system/plugin.class';

export default class Paging extends Plugin {

    static options = {};

    url = '';

    pro = [];

    ra = null;

    ma = [];

    pricemin = null;

    pricemax = null;

    strpricemin = true;

    strpricemax = true;

    init() {
        this.pro = [];
        this._registerEventListeners();
    }

    /**
     * Register events to handle opening the Modal OffCanvas
     * by clicking a defined trigger selector
     * @private
     */
    _registerEventListeners() {
        var $body = $('body');
        var self =  this;
        self.ma = [];
        this._setAllFilters();
        $body.on("click", '.sisi-get-modus .page-item', function (event) {
            var site = $(this).find('label').data('page');
            var hasdisable = $(this).hasClass('disabled');
            var p = 1;
            var aktiveSite = 1;
            self.url = $('#filter-panel-wrapper').data('sisiurl');
            if (!isNaN(site) && (!hasdisable)) {
                p = parseInt(site);
            }

            if (site === 'first' && (!hasdisable)) {
                p = 1;
            }

            if (site === 'prev' && (!hasdisable)) {
                aktiveSite = $('.sisi-ajax-modus .page-item.active').find('label').data('page');
                aktiveSite = parseInt(aktiveSite);
                p = aktiveSite - 1;
            }

            if (site === 'next' && (!hasdisable)) {
                aktiveSite = $('.sisi-ajax-modus .page-item.active').find('label').data('page');
                aktiveSite = parseInt(aktiveSite);
                p = aktiveSite + 1;
            }

            if (site === 'last' && (!hasdisable)) {
                var lastvalue = $(this).find('label').data('value');
                lastvalue = parseInt(lastvalue);
                p = lastvalue;
            }

            window.location.href = self.url  + "&p=" + p;

        });


        $body.on("change", '.sisi-get-modus .filter-multi-select-properties input.filter-multi-select-checkbox', function (event) {

            $('.sisi-get-modus  .filter-multi-select-properties  .filter-multi-select-checkbox:checked').each(function (index) {
                var id = $(this).val();
                if (id === 'on' || id === '') {
                    id = $(this).attr('id')
                }
                self.pro.push(id);
            });
            self._setAllFilters();
            self._mergeUrl();
            window.location.href = self.url;

        });

        $body.on("click", '.sisi-get-modus .filter-rating-select-radio', function (event) {
            var value = $(this).val();
            self._setAllFilters();
            self.ra = value;
            self._mergeUrl();
            window.location.href = self.url;
        });

        $body.on('change', '.sisi-get-modus .filter-multi-select-manufacturer input.filter-multi-select-checkbox ', function() {
            $('.sisi-get-modus  .filter-multi-select-manufacturer   .filter-multi-select-checkbox:checked').each(function (index) {
                self.ma.push($(this).val());
            });
            self._setAllFilters();
            self._mergeUrl();
            window.location.href = self.url;
        });


        $body.on("click", ".sisi-get-modus  .filter-active-remove", function () {
            var id = $(this).data('id');
            self._setAllFilters(id);
            self._mergeUrl()
            window.location.href = self.url;
        });


        $body.on("click", ".sisi-get-modus  .filter-reset-all", function () {
            self.url = $('#filter-panel-wrapper').data('sisiurl');
            window.location.href = self.url;
        });

        $body.on("click", ".sisi-get-modus  .sisi-price-event", function () {
            self.pricemin = $('#sisimin-input').val();
            self.pricemax = $('#sisimax-input').val();
            self._setAllFilters();
            self._mergeUrl()
            window.location.href = self.url;
        });

    }

    _mergeUrl() {
        var self = this;
        self.url  = window.location.protocol + $('#filter-panel-wrapper').data('sisiurl');


        $.each(self.pro, function(index, value) {
            self.url  = self.url  + "&pro["+index+"]="+value;
        });

        $.each(self.ma, function(index, value) {
            self.url  = self.url  + "&ma["+index+"]="+value;
        });

        if (self.ra !== null) {
            self.url  =  self.url + "&ra=" + self.ra;
        }

        if (self.pricemin !== null && self.pricemin !== undefined && self.pricemin !== "") {
            self.url  =  self.url + "&pri[0]=" + self.pricemin;
        }

        if (self.pricemax !== null && self.pricemax !== undefined && self.pricemax  !== "") {
            self.url  =  self.url + "&pri[1]=" + self.pricemax;
        }

    }

    _setAllFilters(exclude = '') {
        var self = this;
        var url = window.location.href;
        const searchParams = new URLSearchParams(url);
        var filterHtml = '';
        var $selektorpricelabel =  $('.sisifilter-range');
        var symbol = $selektorpricelabel.data('symbol');
        var pricemin = $selektorpricelabel.data('pricefrom');
        var pricemax = $selektorpricelabel.data('priceto');
        var text = $('.search-headline').data('reset');


        searchParams.forEach((value, key) => {
            var isprop = key.substring(0, key.length - 3);


            if (isprop === 'pro' && value !== exclude && !self.pro.includes(value)) {
                self.pro.push(value);
            }
            var excluderating = "rating-" + value;
            if (key === 'ra' && excluderating  !== exclude) {
                self.ra = value;
            }

            if (key === 'pri[0]' && (self.pricemin === undefined) && value !== "") {
                self.pricemin = value;
            }

            if (key === 'pri[1]' && (self.pricemax === undefined) && value !== "") {
                self.pricemax = value;
            }

            if (isprop  === 'ma' && value !== exclude) {
                self.ma.push(value);
            }

        });
        $.each(self.pro, function (index, value) {
            var selektor = '#' + value;
            $(selektor).prop("checked", true);
            var valuename = $(selektor).parent().find('label').text();
            if (valuename !== "") {
                filterHtml = filterHtml + '<span class="filter-active"> ' + valuename
                    + '<button class="filter-active-remove" data-id="' + value + '"> ×</button></span>';
            }
        });

        $.each(self.ma, function (index, value) {
            var selektor = '#' + value;
            $(selektor).prop("checked", true);
            var valuename = $(selektor).parent().find('label').text();
            if (valuename !== "") {
                filterHtml = filterHtml + '<span class="filter-active"> ' + valuename
                    + '<button class="filter-active-remove" data-id="' + value + '"> ×</button></span>';
            }
        });


        if (self.ra !== null && self.ra !== undefined) {
            var $selektor =  $('.sisi-multifilter-rating');
            var ratingLabel = $selektor.data('label-min') + " " +  self.ra +"/5 " + $selektor.data('label-end');
            filterHtml = filterHtml + '<span class="filter-active"> ' + ratingLabel
                + '<button class="filter-active-remove" data-id="rating-' + self.ra + '"> ×</button></span>';
        }


        if (self.pricemin !== null && self.pricemin  !== undefined && self.pricemin !== "") {
            var priceminLabel =  pricemin + " " + self.pricemin + " " + symbol;
            filterHtml = filterHtml + '<span class="filter-active"> ' + priceminLabel +
                '<button class="filter-active-remove" data-id="sisimin-input"> ×</button></span>';
        }


        if (self.pricemax !== null && self.pricemax  !== undefined && self.pricemax !== "") {
            var pricemaxLabel =  pricemax  + " " + self.pricemax + " " + symbol;
            filterHtml = filterHtml + '<span class="filter-active"> ' + pricemaxLabel  +
                 '<button class="filter-active-remove" data-id="sisimax-input"> ×</button></span>';
        }

        if (filterHtml !== '' || self.pro.length > 0 || self.ma.length > 0 || self.pricemax !== undefined
            || self.pricemin !== undefined || self.ra !== undefined) {
            filterHtml = filterHtml + '<button class="filter-reset-all btn btn-sm btn-outline-danger">' + text + '</button>'
        }

        $('.filter-panel-active-container').html(filterHtml);
    }
}
