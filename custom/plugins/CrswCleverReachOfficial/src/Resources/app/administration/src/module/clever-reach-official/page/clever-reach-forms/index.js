import template from './clever-reach-forms.html.twig';
import './clever-reach-forms.scss';

const {Component} = Shopware;

Component.register('clever-reach-forms', {
    template,

    inject: [
        'cleverreachService'
    ],

    data() {
        return {
            isLoading: false,
            items: {
                0: ''
            }
        }
    },

    created() {
        this.getForms();
    },

    methods: {
        getForms: function () {
            this.isLoading = true;

            this.cleverreachService.getForms()
                .then((forms) => {
                    this.items = forms.formsData;
                    this.isLoading = false;
                }).catch(error => {
            });
        },

        openShoppingExperiences: function () {
            this.$router.replace('/sw/cms/index');
        },

        editInCleverReach: function (formId) {
            this.cleverreachService.getRedirectUrl({url: '/admin/forms_layout_create.php'})
                .then((urlData) => {
                    window.open(urlData.url + '?id=' + formId);
                }).catch(error => {
            });
        }
    }
});