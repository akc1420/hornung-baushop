import template from './sisi-fields-index.html.twig';
import './../scss/sisi-fields-index.scss'

const {Component, Mixin} = Shopware;
const {Criteria} = Shopware.Data;
const httpClient = Shopware.Application.getContainer('init').httpClient;

Component.register('sisi-fields-index', {
    template,
    name: 'SisiFields',
    inject: [
        'SisiApiCredentialsService'
    ],
    data() {
        return {
            sisiValidate: false,
            language: '',
            limit: '',
            main: '',
            memory: '',
            pid: '',
            text: '',
            log: '',
            unixPid: '',
            statusText: '',
            render: 'none',
            deleteValue: '1',
            shopValue: '',
            languageValue: '',
            shop: '',
            options: [],
            optionsLanguage: [],
            kind: '',
            time:'',
            loader: 'none',
            timeStr: 'none',
            strvalidateUpdate: false
        };
    },
    created() {
        this.getChannels();
    },
    methods: {
        startIndex() {
            var config = {
                'shopID': this.shop,
                'limit': this.limit,
                'languageID': this.language,
                'main': this.main,
                'memory': this.memory,
                'update': this.kind,
                'time': this.time
            };

            if (this.kind === '1' || this.kind === '2') {
                var time = parseInt(this.time);
                if (!Number.isInteger(time)) {
                    this.strvalidateUpdate = true;
                    return false;
                }
                if (time === 0) {
                    this.strvalidateUpdate = true;
                    return false;
                }
            }
            this.strvalidateUpdate = false;
            if (this.shop !== "") {
                this.SisiApiCredentialsService.testConfig(config).then((response) => {
                    this.sisiValidate = true;
                    this.pid = response;
                    this.status();
                }).catch((exception) => {

                    this.sisiValidate = false;
                });
            } else {
                this.statusText = this.$t('ssisi-fields.index.validate');
                this.render = 'block';
            }

        },
        status() {
            var config = {'pid': this.pid};
            this.loader = 'block';
            this.SisiApiCredentialsService.getStatus(config).then((response) => {

                this.log = response['log'];
                this.unixPid = response['pid'];
                this.statusText = response['status'];
                this.render = 'block';
                this.loader = 'none';

            }).catch((exception) => {

            });
        },
        deleteIndex() {
            var config = {'all': this.deleteValue, 'languageID': this.languageValue, 'shopID': this.shop}
            if (this.shop !== "") {
                this.SisiApiCredentialsService.delete(config).then((response) => {
                    this.status();

                }).catch((exception) => {

                });
            } else {
                this.statusText = this.$t('ssisi-fields.index.validate');
                this.render = 'block';
            }
        },
        deleteInaktiveIndex() {
            var config = {'all': this.deleteValue, 'languageID': this.languageValue, 'shopID': this.shop}
            if (this.shop !== "") {
                this.SisiApiCredentialsService.Inaktive(config).then((response) => {
                    this.status();

                }).catch((exception) => {

                });
            } else {
                this.statusText = this.$t('ssisi-fields.index.validate');
                this.render = 'block';
            }
        },
        getChannels() {
            this.SisiApiCredentialsService.channels().then((response) => {

                this.options = response['channel'];
                this.optionsLanguage = response['language'];

            }).catch((exception) => {

            });
        },
        chanceTime() {
            if (this.kind === '1' || this.kind === '2') {
                this.timeStr = 'block';
            } else {
                this.timeStr = 'none'
            }
        }
    }
});
