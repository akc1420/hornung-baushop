import template from './cr-abandoned-cart-list.html.twig';
import './cr-abandoned-cart-list.scss';

const { Component, Mixin } = Shopware;

Component.register('cr-abandoned-cart-list', {
    template,
    inject: [
        'cleverreachService'
    ],

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('listing'),
    ],

    data() {
        return {
            abandonedCarts: [],
            total: 0,
            limit: 25,
            sortBy: 'scheduledTime',
            sortDirection: 'DESC',
            isLoading: false,
            filterLoading: false,
            showDeleteModal: false,
            availableRecoveryEmailStatuses: [],
            availableRecoveryStatuses: [],
            recoveryEmailStatusFilter: null,
            salesChannelFilter: null,
            recoveryStatusFilter: null,
            salesChannels: [],
            lang: Shopware.Context.api.languageId
        };
    },

    computed: {
        abandonedCartColumns() {
            return this.getAbandonedCartColumns();
        },

        searchParameters() {
            return  {
                filters: {
                    status: this.recoveryEmailStatusFilter,
                    isRecovered: this.recoveryStatusFilter ? JSON.parse(this.recoveryStatusFilter.toLowerCase()) : null,
                    automationId: this.salesChannelFilter
                },
                term: this.term,
                limit: this.limit,
                page: this.page,
                sortBy: this.sortBy,
                sortDirection: this.sortDirection,
                lang: Shopware.Context.api.languageId
            }
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.limit = 25;
            this.getList();
            this.getSalesChannels();
        },

        onChangeLanguage(langId) {
            this.lang = langId;
            this.getList();
        },

        getList() {
            this.isLoading = true;
            this.abandonedCarts = [];

            this.cleverreachService.getAbandonedCartRecords(this.searchParameters)
                .then((response) => {
                    this.abandonedCarts = this.formatAbandonedCarts(response.records);
                    this.total = response.count;
                    this.isLoading = false;
                });

            this.isLoading = false;
        },

        getSalesChannels() {
            this.isLoading = true;
            let salesChannels = [];
            this.cleverreachService.getShops().then((response) => {
                if (response.shopsData.length > 0) {
                    for (let i = 0; i < response.shopsData.length; i++) {
                        salesChannels.push({"id": response.shopsData[i].automationId, "name": response.shopsData[i].shopName})
                    }

                    this.salesChannels = salesChannels;
                    this.isLoading = false;
                }
            });
        },

        getAbandonedCartColumns() {
            return [{
                property: 'scheduledTime',
                label: 'cr-abandoned-cart.abandonedCart.scheduledTime',
                allowResize: true,
                primary: true,
            }, {
                property: 'sentTime',
                label: 'cr-abandoned-cart.abandonedCart.sentTime',
                allowResize: true
            }, {
                property: 'amount',
                label: 'cr-abandoned-cart.abandonedCart.amount',
                allowResize: true,
                sortable: false
            }, {
                property: 'email',
                label: 'cr-abandoned-cart.abandonedCart.customerEmail',
                allowResize: true
            }, {
                property: 'salesChannel',
                label: 'cr-abandoned-cart.abandonedCart.salesChannel',
                allowResize: true
            }, {
                property: 'status',
                label: 'cr-abandoned-cart.abandonedCart.recoveryEmailStatus',
                allowResize: true
            }, {
                property: 'isRecovered',
                label: 'cr-abandoned-cart.abandonedCart.recoveryStatus',
                allowResize: true
            }, {
                property: 'errorMessage',
                label: 'cr-abandoned-cart.abandonedCart.message',
                allowResize: true,
                sortable: false,
            }];
        },

        formatAbandonedCarts(records) {
            let data = records;
            for (let i = 0; i < data.length; i++) {
                data[i].scheduledTime = this.formatTime(data[i].scheduledTime);
                data[i].sentTime = this.formatTime(data[i].sentTime);
            }

            return data;
        },

        formatTime(sourceDateTime) {
            if (!sourceDateTime) {
                return null;
            }

            let date = new Date(sourceDateTime);

            return date.toLocaleString('it', {
                    day: '2-digit',
                    year: '2-digit',
                    month: '2-digit',
                }) +
                ', ' +
                date.toLocaleTimeString('it', {
                    hour: '2-digit',
                    minute: '2-digit'}
                )
        },

        getRecoveryEmailStatusVariant(status) {
            switch (status) {
                case 'sent' :
                    return 'success';
                case 'sending':
                    return 'info';
                case 'pending':
                    return 'warning';
                default:
                    return 'neutral';
            }
        },

        getStatusLabel(translations) {
            return translations[this.lang];
        },

        getRecoveryStatusVariant(recoveryStatus) {
            return recoveryStatus ? 'success' : 'neutral';
        },

        onDelete(id) {
            this.showDeleteModal = id;
        },

        onCloseDeleteModal() {
            this.showDeleteModal = false;
        },

        onConfirmDelete(recordId) {
            this.showDeleteModal = false;
            return this.cleverreachService.deleteAbandonedCartRecord(recordId).then((response) => {
                if (response.success) {
                    this.createNotificationSuccess({
                        title: this.$tc('global.default.success'),
                        message: this.$tc('cr-abandoned-cart.abandonedCart.notification.deleteSuccess')
                    });
                    this.getList();
                } else {
                    this.createNotificationError({
                        title: this.$tc('global.default.success'),
                        message: this.$tc('cr-abandoned-cart.abandonedCart.notification.deleteError')
                    });
                }
            });
        },

        triggerEmail(recordId) {
            return this.cleverreachService.triggerAutomation(recordId).then((response) => {
                if (response.success) {
                    this.getList();
                    this.createNotificationSuccess({
                        title: this.$tc('global.default.success'),
                        message: this.$tc('cr-abandoned-cart.abandonedCart.notification.triggerSuccess')
                    });
                } else {
                    this.createNotificationError({
                        title: this.$tc('global.default.success'),
                        message: this.$tc('cr-abandoned-cart.abandonedCart.notification.triggerError')
                    });
                }
            });
        },

        onRefresh() {
            this.getList();
        },

        onChangeRecoveryEmailStatusFilter(value) {
            this.isLoading = true;
            this.recoveryEmailStatusFilter = value;

            this.getList();
        },

        onChangeRecoveryStatusFilter(value) {
            this.isLoading = true;
            this.recoveryStatusFilter = value;

            this.getList();
        },

        onChangeSalesChannelFilter(value) {
            this.isLoading = true;
            this.salesChannelFilter = value;

            this.getList();
        },
    }
});
