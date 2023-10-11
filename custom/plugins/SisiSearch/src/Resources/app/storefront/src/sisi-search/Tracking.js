import Plugin from 'src/plugin-system/plugin.class';
import HttpClient from 'src/service/http-client.service';

export default class Tracking extends Plugin {

    static options = {};

    values = [];

    init() {
        this._registerEventListeners();
        this._client = new HttpClient();
    }

    /**
     * Register events to handle opening the Modal OffCanvas
     * by clicking a defined trigger selector
     * @private
     */
    _registerEventListeners() {
        this._clickProductList();
    }


    _clickProductList() {
        var $self = this;
        var hasClass = false;
        $('.header-search-form').each(function() {
            hasClass = $(this).hasClass('sisilogaktive');
        });

        if (hasClass) {
            $(this.el).on("click", ".sisi-search-suggest-container-right .search-suggest-product a", function () {
                var number = $(this).data('number');
                if (number !== undefined) {
                    return $self.fetch(number, $(this));
                }
            });
            $(this.el).on("click", ".product-box a", function () {
                var number = $(this).parent().parent().parent().parent().data('number');
                if (number !== undefined) {
                    return $self.fetch(number, $(this));
                }
            })
        }
        return true;
    }
    _strfetch($self) {
        window.location.href = $self.attr('href');
    }
    _mergeUrl(number, $self) {
        var searchTerm  = $('.header-search-input').val();
        var produktname = $self.attr('title');
        if (produktname === undefined) {
            return true;
        }
        var urlLink =  $self.attr('href');
        var $formSelektor = $('.header-search-form');
        var url =  $formSelektor.data('tracking');
        url = url + "?searchTerm=" + searchTerm;
        url = url + "&produktname=" + produktname;
        url = url + "&urlLink=" + urlLink;
        url = url + "&number=" + number;
        url = url + "&language=" + $formSelektor.data('language');
        return url;
    }

    /**
     * Fetch
     */
    fetch(number, $self) {
        var url = this._mergeUrl(number, $self)
        var $this = this;
        if (url === true) {
            return true;
        }
        this._client.get(url, function(){
            return $this._strfetch($self);
        }, (responseText) => {
            return responseText[0];
        }, 'application/json', true);

        return false;
    }

}
