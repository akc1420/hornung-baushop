import template from './sisi-fields-detail.html.twig';

const {Component, Mixin} = Shopware;

Component.register('sisi-fields-detail', {
    template,

    inject: [
        'repositoryFactory',
        'SisiApiCredentialsService'
    ],

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },
    data() {

        return {
            fields: null,
            isLoading: false,
            processSuccess: false,
            repository: null,
            edge: 'none',
            pattern: 'none',
            strvalidate: true,
            colorTable: 'white',
            colorName: 'white',
            fieldTypeColor: 'white',
            synonymStr: 'none',
            stemmingStr: 'none',
            displayconfig: 'inline',
            options: [],
            optionsLanguage: [],
            info: '',
            optionsLanuageText: ''
        };
    },

    created() {
        this.repository = this.repositoryFactory.create('s_plugin_sisi_search_es_fields');
        this.getBundle();
        this.getChannels();
    },
    methods: {
        getBundle() {
            var self = this;
            this.repository
                .get(this.$route.params.id, Shopware.Context.api)
                .then((entity) => {
                    this.fields = entity;
                    this.choiceFields();
                    this.chanceFilter();
                    this.changeStemming();
                    this.disableFields();
                });
        },
        onClickSave() {
            this.isLoading = true;
            this.repository
                .save(this.fields, Shopware.Context.api)
                .then(() => {
                    this.getBundle();
                    this.isLoading = false;
                    this.processSuccess = true;
                }).catch((exception) => {

                this.isLoading = false;
            });
        },
        saveFinish() {
            this.processSuccess = false;
        },
        disableFields() {

            if (this.fields.fieldtype === 'float' || this.fields.fieldtype === 'long' || this.fields.fieldtype === 'date') {
                this.displayconfig = 'none';
            } else {
                this.displayconfig = 'inline';
            }

        },
        choiceFields() {
            if (this.fields.tokenizer === 'Edge_n-gram_tokenizer' || this.fields.tokenizer === 'ngram' || this.fields.tokenizer === 'Edgengramtokenizer') {
                this.edge = 'block';
                this.info = this.$t('ssisi-fields.detail.infoEdge');
            } else {
                this.edge = 'none';
                this.info = '';
            }

            if (this.fields.tokenizer === 'simple_pattern') {
                this.pattern = 'block';
            } else {
                this.pattern = 'none';
            }

        },
        changeStemming() {

            if (this.fields.stemming !== 'noselect') {
                this.stemmingStr = 'block';
            } else {
                this.stemmingStr = 'none';
            }
        },
        chanceFilter() {

            if (this.fields.filter1 === 'synonym' || this.fields.filter2 === 'synonym' || this.fields.filter3 === 'synonym') {
                this.synonymStr = 'block';
            } else {
                this.synonymStr = 'none';
            }

            if (this.fields.filter1 === 'autocomplete' || this.fields.filter2 === 'autocomplete' || this.fields.filter3 === 'autocomplete') {
                this.info = this.$t('ssisi-fields.detail.infoAutocomplete');
            } else {
                this.info = '';
            }

        },
        getChannels() {
            this.SisiApiCredentialsService.channels().then((response) => {
                this.options = response['channel'];
                this.optionsLanguage = response['language'];
            }).catch((exception) => {

            })
        },
        validate() {

            this.strvalidate = true;

            if (!this.fields.tablename) {
                this.strvalidate = false;
                this.colorTable = 'red';
            } else {
                this.colorTable = 'white';
            }
            if (!this.fields.name) {
                this.strvalidate = false;
                this.colorName = 'red';
            } else {
                this.colorName = 'white';
            }
            if (!this.fields.fieldtype) {
                this.strvalidate = false;
                this.fieldTypeColor = 'red';
            } else {
                this.fieldTypeColor = 'white';
            }
        },

    }
});
