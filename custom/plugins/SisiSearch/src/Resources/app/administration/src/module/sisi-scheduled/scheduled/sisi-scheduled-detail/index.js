import template from './sisi-scheduled-detail.html.twig';

const {Component, Mixin} = Shopware;

Component.register('sisi-scheduled-detail', {
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
            options: [],
            optionsLanguage: [],
            displayall:'none',
            colorlastExecutionTime:'white',
            colornextExecutionTime: 'white',
            colorTime: 'white',
            colorTitle: 'white',
            strdays: 'none'
        };
    },
    created() {
        this.repository = this.repositoryFactory.create('sisi_search_es_scheduledtask');
        this.getBundle();
        this.getChannels();
        this.choiceFields();

    },
    methods: {
        getBundle() {
            var self = this;
            this.repository
                .get(this.$route.params.id, Shopware.Context.api)
                .then((entity) => {
                    this.fields = entity;
                    this.displayKind();
                });
        },
        choiceFields() {
            if (this.fields.kind === 'update' || this.fields.kind === 'updateG') {
                this.strdays = 'block';
            } else {
                this.strdays = 'none';
            }

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
        getChannels() {
            this.SisiApiCredentialsService.channels().then((response) => {
                this.options = response['channel'];
                this.optionsLanguage = response['language'];
                this.optionsLanguage.shift();
            }).catch((exception) => {
            });
        },
        displayKind() {
            if (this.fields.kind === 'delete') {
                this.displayall = 'block';
            } else{
                this.displayall = 'none';
            }
            this.choiceFields();
        },
        validate() {
            this.strvalidate = true;

            if (!this.fields.nextExecutionTime) {
                this.strvalidate = false;
                this.colornextExecutionTime = 'red';
            }
            else{
                this.colornextExecutionTime = 'white';
            }

            if (!this.fields.time) {
                this.strvalidate = false;
                this.colorTime = 'red';
            } else{
                this.colorTime = 'white';
            }

            if (!this.fields.title) {
                this.strvalidate = false;
                this.colorTitle = 'red';
            } else{
                this.colorTitle = 'white';
            }
        },
    }
});
