import template from './sisi-backend-search-list.html.twig';
import './sisi-backend-search-list.scss';

const {Component} = Shopware;
const {Criteria} = Shopware.Data;

Component.register('sisi-backend-search-list', {
    template,
    name: 'SisiBackendsearch',
    inject: ['SisiApiCredentialsService'],
    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },
    data() {
        return {
            term: '',
            isSearchBarShown:false,
            result:'',
            hoverEvent:false,
            page:0,
            languageValue: [],
            shop: '',
            options:'',
            shopValue: '',
            language:'',
            error:false,
            icon:'none'
        };
    },

    created() {
        this.getChannels();
    },
    methods: {
        changeForm() {
            if (this.term.length > 2) {
                this.sisisearch();
            }
        },
        sisisearch() {
            var config = {'term': this.term, 'page': 0, 'shop': this.shopValue};
            var $self =this;
            if (this.term.length > 1 && (this.shopValue !== '')) {
                $self.icon='block';
                this.SisiApiCredentialsService.sisisearch(config).then((response) => {
                    $self.result = response;
                    $self.error = false;
                    $self.icon='none';
                }).catch((exception) => {

                });
            }
            if (this.shopValue === '') {
                this.error = true;
            }
        },
        changeChannel(){
            this.result = '';
        },
        getChannels(){
            this.SisiApiCredentialsService.channelsWithlanguage().then((response) => {
                this.options =response['channel'];
                this.language =response['language'];
            }).catch((exception) => {

            });
        },
        scrollFunction() {
            const self = this;
            var elements = document.querySelectorAll('.sisi-last-row');
            for (var i = 0; i < elements.length; i++) {
                var viewport = self.isInViewport(elements[i]);
                if (viewport) {
                   this.load();
                }
            }
        },
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
        },
        load() {
            this.page = this.page + 1;
            var config = {'term': this.term,'page':this.page,'shop':this.shopValue}
            this.SisiApiCredentialsService.sisisearch(config).then((response) => {
                var nums1 = this.result['data']['hits']['hits'];
                var nums2 = response['data']['hits']['hits'];
                this.result['data']['hits']['hits'] = nums1.concat(nums2);
            }).catch((exception) => {

            });
        }
    }
});

