import template from './sw-social-shopping-channel-statistics.html.twig';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('sw-social-shopping-channel-statistics', {
    template,

    inject: [
        'repositoryFactory',
        'acl',
    ],

    data () {
        return {
            historyOrderDataCount: [],
            historyOrderDataSum: [],
            historyCustomerDataCount: [],
            statisticDateRangesOrderCount: {
                value: '30Days',
                options: {
                    '30Days': 30,
                    '14Days': 14,
                    '7Days': 7,
                    '24Hours': 24,
                    yesterday: 1,
                },
            },
            statisticDateRangesOrderSum: {
                value: '30Days',
                options: {
                    '30Days': 30,
                    '14Days': 14,
                    '7Days': 7,
                    '24Hours': 24,
                    yesterday: 1,
                },
            },
            statisticDateRangesCustomerCount: {
                value: '30Days',
                options: {
                    '30Days': 30,
                    '14Days': 14,
                    '7Days': 7,
                    '24Hours': 24,
                    yesterday: 1,
                },
            },
            isLoading: true
        }
    },

    computed: {
        includesChartCard() {
            return Shopware.Component.getComponentRegistry().has('sw-chart-card');
        },

        chartOptionsOrderCount() {
            return {
                xaxis: {
                    type: 'datetime',
                    min: this.dateAgoValue(this.statisticDateRangesOrderCount).getTime(),
                    labels: {
                        datetimeUTC: false,
                    },
                },
                yaxis: {
                    min: 0,
                    tickAmount: 3,
                    labels: {
                        formatter: (value) => { return parseInt(value, 10); },
                    },
                },
            };
        },

        chartOptionsCustomerCount() {
            return {
                xaxis: {
                    type: 'datetime',
                    min: this.dateAgoValue(this.statisticDateRangesCustomerCount).getTime(),
                    labels: {
                        datetimeUTC: false,
                    },
                },
                yaxis: {
                    min: 0,
                    tickAmount: 3,
                    labels: {
                        formatter: (value) => { return parseInt(value, 10); },
                    },
                },
            };
        },

        chartOptionsOrderSum() {
            return {
                xaxis: {
                    type: 'datetime',
                    min: this.dateAgoValue(this.statisticDateRangesOrderSum).getTime(),
                    labels: {
                        datetimeUTC: false,
                    },
                },
                yaxis: {
                    min: 0,
                    tickAmount: 5,
                    labels: {
                        formatter: (value) => this.$options.filters.currency(value, null, 2),
                    },
                },
            };
        },

        orderRepository() {
            return this.repositoryFactory.create('order');
        },

        customerRepository() {
            return this.repositoryFactory.create('customer');
        },

        orderCountCriteria() {
            const criteria = new Criteria();

            criteria.addAssociation('swagSocialShoppingOrder');
            criteria.addAggregation(Criteria.count('count', 'id'));
            criteria.addFilter(Criteria.equals('swagSocialShoppingOrder.referralCode', this.$attrs.salesChannel.id));
            criteria.addFilter(Criteria.range('orderDate', { gte: this.formatDate(this.dateAgoValue(this.statisticDateRangesOrderCount)) }));
            criteria.addSorting(Criteria.sort('orderDate', 'DESC'));

            return criteria;
        },

        customerCountCriteria() {
            const criteria = new Criteria();

            criteria.addAssociation('swagSocialShoppingCustomer');
            criteria.addFilter(Criteria.equals('swagSocialShoppingCustomer.referralCode', this.$attrs.salesChannel.id))
            criteria.addFilter(Criteria.range('firstLogin', { gte: this.formatDate(this.dateAgoValue(this.statisticDateRangesCustomerCount)) }));
            criteria.addSorting(Criteria.sort('firstLogin', 'DESC'));

            return criteria;
        },

        orderSumCriteria() {
            const criteria = new Criteria();

            criteria.addAssociation('swagSocialShoppingOrder');
            criteria.addFilter(Criteria.equals('swagSocialShoppingOrder.referralCode', this.$attrs.salesChannel.id));
            criteria.addAssociation('stateMachineState');
            criteria.addFilter(Criteria.equals('transactions.stateMachineState.technicalName', 'paid'));
            criteria.addFilter(Criteria.range('orderDate', { gte: this.formatDate(this.dateAgoValue(this.statisticDateRangesOrderSum)) }));
            criteria.addSorting(Criteria.sort('orderDate', 'DESC'));

            return criteria;
        },

        orderCountSeries() {
            const orderDataArray = this.extractHistoryOrderData(this.historyOrderDataCount);

            if (!orderDataArray) {
                return [];
            }

            return [{ name: this.$tc('swag-social-shopping.networks.base.statistics.numbers'), data: orderDataArray }];
        },

        customerCountSeries() {
            const customerDataArray = this.extractHistoryCustomerData(this.historyCustomerDataCount);

            if (!customerDataArray) {
                return [];
            }

            return [{ name: this.$tc('swag-social-shopping.networks.base.statistics.numbers'), data: customerDataArray }];
        },

        orderSumSeries() {
            const orderDataArray = this.extractTurnoverData(this.historyOrderDataSum);

            if (!orderDataArray) {
                return [];
            }

            return [{ name: this.$tc('swag-social-shopping.networks.base.statistics.totalTurnover'), data: orderDataArray }];
        },

        today() {
            const today = Shopware.Utils.format.dateWithUserTimezone();
            today.setHours(0, 0, 0, 0);
            return today;
        },

        systemCurrencyISOCode() {
            return Shopware.Context.app.systemCurrencyISOCode;
        },

    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.fetchData();
        },

        async fetchData() {
            try {
                let promises = [];

                if (this.acl.can('order.viewer')) {
                    promises.push(this.getHistoryOrderCountData(), this.getHistoryOrderSumData());
                }

                if (this.acl.can('customer.viewer')) {
                    promises.push(this.getHistoryCustomerCountData());
                }

                await Promise.allSettled(promises);
            } finally {
                this.isLoading = false;
            }
        },

        onOrderCountRangeUpdate(value) {
            this.statisticDateRangesOrderCount.value = value;
            this.getHistoryOrderCountData();
        },

        onCustomerCountRangeUpdate(value) {
            this.statisticDateRangesCustomerCount.value = value;
            this.getHistoryCustomerCountData();
        },

        onOrderSumRangeUpdate(value) {
            this.statisticDateRangesOrderSum.value = value;
            this.getHistoryOrderSumData();
        },

        getHistoryOrderCountData() {
            return this.orderRepository.search(this.orderCountCriteria).then((response) => {
                this.historyOrderDataCount = response;
            });
        },

        getHistoryCustomerCountData() {
            return this.customerRepository.search(this.customerCountCriteria).then((response) => {
                this.historyCustomerDataCount = response;
            });
        },

        getHistoryOrderSumData() {
            return this.orderRepository.search(this.orderSumCriteria).then((response) => {
                this.historyOrderDataSum = response;
            });
        },

        formatOrderDataArray(array) {
            return array.reduce((tmpSeriesData, order) => {
                const orderDateTime = order.orderDate;

                if (!tmpSeriesData[orderDateTime]) {
                    tmpSeriesData[orderDateTime] = [];
                }

                tmpSeriesData[orderDateTime].push(order);

                return tmpSeriesData;
            }, {});
        },

        formatCustomerDataArray(array) {
            return array.reduce((tmpSeriesData, customer) => {
                const firstLoginDateTime = new Date(customer.firstLogin);
                const firstLoginDate = new Date(firstLoginDateTime.getFullYear(), firstLoginDateTime.getMonth(), firstLoginDateTime.getDate());

                if (!tmpSeriesData[firstLoginDate.getTime()]) {
                    tmpSeriesData[firstLoginDate.getTime()] = [];
                }

                tmpSeriesData[firstLoginDate.getTime()].push(customer);

                return tmpSeriesData;
            }, {});
        },

        extractHistoryOrderData(data) {
            return Object.entries(this.formatOrderDataArray(data)).map(([key, value]) => {
                return { x: this.parseOrderDate(key), y: value.length };
            });
        },

        extractTurnoverData(data) {
            return Object.entries(this.formatOrderDataArray(data)).map(([key, value]) => {
                return { x: this.parseOrderDate(key), y: value.reduce((turnover, order) => { return turnover + order.amountTotal }, 0) };
            });
        },

        extractHistoryCustomerData(data) {
            return Object.entries(this.formatCustomerDataArray(data)).map(([key, value]) => {
                return { x: parseInt(key), y: value.length };
            });
        },

        getOrderAmountTotal(value) {
            return value.reduce((acc, order) => acc + order.amountTotal, 0);
        },

        dateAgoValue(range) {
            const date = Shopware.Utils.format.dateWithUserTimezone();
            const selectedDateRange = range.value;
            const dateRange = range.options[selectedDateRange] ?? 0;

            if (selectedDateRange === '24Hours') {
                date.setHours(date.getHours() - dateRange);

                return date;
            }

            date.setDate(date.getDate() - dateRange);
            date.setHours(0, 0, 0, 0);

            return date;
        },

        getTimeUnitInterval(range) {
            const statisticDateRange = range.value;

            if (statisticDateRange === 'yesterday' || statisticDateRange === '24Hours') {
                return 'hour';
            }

            return 'day';
        },

        getChartRangeSubtitle(range) {
            return (this.formatChartHeadlineDate(this.dateAgoValue(range)) + '-' + (this.formatChartHeadlineDate(this.today)));
        },

        formatDate(date) {
            return this.formatDateToISO(date);
        },

        formatDateToISO(date) {
            return Shopware.Utils.format.toISODate(date, false);
        },

        formatChartHeadlineDate(date) {
            const lastKnownLang = Shopware.Application.getContainer('factory').locale.getLastKnownLocale();

            return date.toLocaleDateString(lastKnownLang, {
                day: 'numeric',
                month: 'short',
            });
        },

        parseOrderDate(date) {
            const parsedDate = new Date(date.replace(/-/g, '/').replace('T', ' ').replace(/\..*|\+.*/, ''));
            return parsedDate.getTime();
        }
    },
});
