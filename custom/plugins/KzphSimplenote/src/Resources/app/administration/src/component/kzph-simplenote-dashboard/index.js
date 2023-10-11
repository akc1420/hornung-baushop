const { Component } = Shopware;
const { Criteria } = Shopware.Data;

import template from './index.html.twig';

Component.register('kzph-simplenote-dashboard', {
    template,

    inject: [
        'repositoryFactory'
    ],

    data() {
        return {
            noteData: [],
            entityData: [],
            fullyLoaded: false,
            isLoading: false,
            isEmpty: true,
            page: 1,
            limit: 25
        };
    },

    computed: {
        noteRepository() {
            return this.repositoryFactory.create('kzph_simplenote');
        },
        customerRepository() {
            return this.repositoryFactory.create('customer');
        },
        productRepository() {
            return this.repositoryFactory.create('product');
        },
        orderRepository() {
            return this.repositoryFactory.create('order');
        },
        categoryRepository() {
            return this.repositoryFactory.create('category');
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.isLoading = true;

            this.fullyLoaded = false;
            this.fetchData().then((response) => {
                this.noteData = [];

                var listArray = new Array();
                var entityArray = new Array();

                entityArray["customer"] = new Array();
                entityArray["product"] = new Array();
                entityArray["order"] = new Array();
                entityArray["category"] = new Array();

                var i = 0;

                response.forEach(function (element) {
                    var newItem = new Array();
                    newItem["id"] = element.id;
                    newItem["entityType"] = element.entityType;
                    newItem["entityId"] = element.entityId;
                    newItem["username"] = element.username;
                    newItem["createdAt"] = element.createdAt;
                    newItem["note"] = element.note;
                    newItem["destination"] = '...';

                    entityArray[element.entityType][i] = element.entityId;

                    listArray[i] = newItem;
                    i++;

                    listArray['total'] = response.total;
                    listArray['criteria'] = response.criteria;
                });

                if (this.noteData.length > 0)
                    this.isEmpty = false;
                else
                    this.isEmpty = true;

                if (i == 0) {
                    this.isLoading = false;
                    this.fullyLoaded = true;

                    listArray['total'] = 0;
                    listArray['criteria'] = response.criteria;
                }

                this.noteData = listArray;
                this.entityData = entityArray;

                this.loadDestinations();
            });
        },

        fetchData() {
            const criteria = new Criteria();
            criteria.addFilter(
                Criteria.equals('showDesktop', 1)
            );

            criteria.limit = this.limit;
            criteria.setPage(this.page);

            criteria.addSorting(Criteria.sort('createdAt', 'DESC', false));

            return this.noteRepository.search(criteria);
        },

        onPageChange( { page = 1, limit = 25 } ) {
            this.page = page;
            this.limit = limit;

            this.showLoadIndicator();
            this.createdComponent();
        },

        async _loadAndExtendCustomerInformation() {
            var entities = this.entityData;

            if (entities['customer'].length == 0)
                return;

            //prepare criteria
            var criteria = new Criteria();
            criteria.addFilter(
                Criteria.equalsAny('id', entities['customer'])
            );

            //load all entity information and extend destinations
            this.customerRepository.search(criteria).then((response) => {
                this.showLoadIndicator();
                var items = new Array();
                response.forEach(function (element) {
                    items[element.id] = element.firstName + ' ' + element.lastName;
                });

                //now, go through all items and extend the destinationinformation
                this.noteData.forEach(function (element) {
                    var cId = element.entityId;

                    if (items[cId] !== undefined) {
                        element.destination = items[cId];
                    }
                });
                this.hideLoadIndicator();
            });

        },

        async _loadAndExtendProductInformation() {
            var entities = this.entityData;

            if (entities['product'].length == 0)
                return;

            //prepare criteria
            var criteria = new Criteria();
            criteria.addFilter(
                Criteria.equalsAny('id', entities['product'])
            );

            //load all entity information and extend destinations
            this.productRepository.search(criteria).then((response) => {
                this.showLoadIndicator();
                var items = new Array();
                response.forEach(function (element) {
                    items[element.id] = element.name;
                });

                //now, go through all items and extend the destinationinformation
                this.noteData.forEach(function (element) {
                    var cId = element.entityId;

                    if (items[cId] !== undefined) {
                        element.destination = items[cId];
                    }
                });
                this.hideLoadIndicator();
            });

        },

        async _loadAndExtendOrderInformation() {
            var entities = this.entityData;

            if (entities['order'].length == 0)
                return;

            //prepare criteria
            var criteria = new Criteria();
            criteria.addFilter(
                Criteria.equalsAny('id', entities['order'])
            );

            //load all entity information and extend destinations
            this.orderRepository.search(criteria).then((response) => {
                this.showLoadIndicator();
                var items = new Array();
                response.forEach(function (element) {
                    items[element.id] = element.orderNumber;
                });

                //now, go through all items and extend the destinationinformation
                this.noteData.forEach(function (element) {
                    var cId = element.entityId;

                    if (items[cId] !== undefined) {
                        element.destination = items[cId];
                    }
                });
                this.hideLoadIndicator();
            });

        },

        async _loadAndExtendCategoryInformation() {
            var entities = this.entityData;

            if (entities['category'].length == 0)
                return;

            //prepare criteria
            var criteria = new Criteria();
            criteria.addFilter(
                Criteria.equalsAny('id', entities['category'])
            );

            //load all entity information and extend destinations
            this.categoryRepository.search(criteria).then((response) => {
                this.showLoadIndicator();
                var items = new Array();
                response.forEach(function (element) {
                    items[element.id] = element.name;
                });

                //now, go through all items and extend the destinationinformation
                this.noteData.forEach(function (element) {
                    var cId = element.entityId;

                    if (items[cId] !== undefined) {
                        element.destination = items[cId];
                    }
                });
                this.hideLoadIndicator();
            });
        },

        showLoadIndicator() {
            this.isLoading = true;
            this.fullyLoaded = false;
        },

        hideLoadIndicator() {
            this.isLoading = false;
            this.fullyLoaded = true;
        },

        async loadDestinations() {
            this._loadAndExtendCustomerInformation();
            this._loadAndExtendProductInformation();
            this._loadAndExtendOrderInformation();
            this._loadAndExtendCategoryInformation();
        },

        noteColumns() {
            return [
                {
                    property: 'username',
                    label: this.$tc('kzph-simplenote-note.dashboard.username'),
                    primary: false,
                    allowResize: true
                },
                {
                    property: 'entityDestination',
                    label: this.$tc('kzph-simplenote-note.dashboard.destination'),
                    primary: false,
                    allowResize: true
                },
                {
                    property: 'note',
                    label: this.$tc('kzph-simplenote-note.dashboard.note'),
                    primary: false,
                    allowResize: true
                },
                {
                    property: 'createdAt',
                    label: this.$tc('kzph-simplenote-note.dashboard.date'),
                    primary: false,
                    allowResize: true
                }
            ];
        },
    },
});