import template from './kzph-simplenote-note.html.twig';
import './kzph-simplenote-note.scss';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Shopware.Component.register('kzph-simplenote-note', {
    template,

    inject: [
        'repositoryFactory',
        'userService'
    ],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            note: null,
            items: null,
            openItems: [],
            checkedDesktopItems: [],
            checkedReplicateItems: [],
            checkedDoneItems: [],
            checkedItems: [],
            showButtonForReplicateInOrder: false,
            showButtonForShowMessage: false
        }
    },

    computed: {
        kzphSimplenoteRepository() {
            return this.repositoryFactory.create('kzph_simplenote');
        },

        currentUser() {
            return Shopware.State.get('session').currentUser;
        },

        getItems() {
            if (this.items) {
                document.querySelector(".kzphSimplenoteBadge").textContent = this.openItems.length;
                document.querySelector(".kzphSimplenoteBadge").classList.remove('hasSimplenotes');

                if (this.openItems.length > 0)
                    document.querySelector(".kzphSimplenoteBadge").classList.add('hasSimplenotes');

                return this.items;
            }

            return;
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            if (this.$route.meta.entityType == 'customer' || this.$route.meta.entityType == 'product') {
                this.showButtonForReplicateInOrder = true;
                this.showButtonForShowMessage = true;
            }

            this.loadItems();
            return this.items;
        },

        loadItems() {
            const criteria = new Criteria();
            criteria.addFilter(
                Criteria.equals('entityId', this.$route.params.id)
            );
            criteria.addSorting(Criteria.sort('createdAt', 'desc'));

            this.kzphSimplenoteRepository
                .search(criteria, Shopware.Context.api)
                .then(result => {
                    this.items = result;
                    this.openItems = [];

                    this.items.forEach((value, index) => {
                        if (value.showDesktop == 1)
                            this.checkedDesktopItems.push(value.id);

                        if (value.replicateInOrder == 1)
                            this.checkedReplicateItems.push(value.id);

                        if (value.done == 0)
                            this.openItems.push(value.id);

                        if (value.done == 1)
                            this.checkedDoneItems.push(value.id);

                        if (value.showMessage == 1)
                            this.checkedItems.push(value.id);
                    });
                });
        },

        onNoteSaveClick() {
            if (this.note == null || this.note.length == 0)
                return;

            this.createNotificationSuccess({ title: this.$tc('kzph-simplenote-note.general.title'), message: this.$tc('kzph-simplenote-note.general.saved') })

            let entity = this.kzphSimplenoteRepository.create(Shopware.Context.api);
            entity.entityId = this.$route.params.id;
            entity.entityType = this.$route.meta.entityType;
            entity.username = this.currentUser.lastName;
            entity.note = this.note;

            this.kzphSimplenoteRepository.save(entity, Shopware.Context.api).then(result => {
                this.loadItems();
            });

            this.note = '';
        },

        onNoteShowOnDesktopChange(event) {
            let itemId = event.target.value;
            let checked = event.target.checked;

            if (!itemId)
                return;

            const criteria = new Criteria();
            criteria.addFilter(
                Criteria.equals('id', itemId)
            );

            this.kzphSimplenoteRepository
                .search(criteria, Shopware.Context.api)
                .then(result => {
                    result.forEach((entity) => {
                        if (checked == true)
                            entity.showDesktop = 1;
                        else
                            entity.showDesktop = 0;

                        this.kzphSimplenoteRepository.save(entity, Shopware.Context.api);
                    });
                });
        },

        onNotReplicateInOrderChange(event) {
            let itemId = event.target.value;
            let checked = event.target.checked;

            if (!itemId)
                return;

            const criteria = new Criteria();
            criteria.addFilter(
                Criteria.equals('id', itemId)
            );

            this.kzphSimplenoteRepository
                .search(criteria, Shopware.Context.api)
                .then(result => {
                    result.forEach((entity) => {
                        if (checked == true)
                            entity.replicateInOrder = 1;
                        else
                            entity.replicateInOrder = 0;

                        this.kzphSimplenoteRepository.save(entity, Shopware.Context.api);
                    });
                });
        },

        onDoneChange(event) {
            let itemId = event.target.value;
            let checked = event.target.checked;

            if (!itemId)
                return;

            const criteria = new Criteria();
            criteria.addFilter(
                Criteria.equals('id', itemId)
            );

            this.kzphSimplenoteRepository
                .search(criteria, Shopware.Context.api)
                .then(result => {
                    result.forEach((entity) => {
                        if (checked == true)
                            entity.done = 1;
                        else
                            entity.done = 0;

                        this.kzphSimplenoteRepository.save(entity, Shopware.Context.api).then(result => {
                            this.loadItems();
                        });
                    });
                });
        },

        onNoteShowMessageChange(event) {
            let itemId = event.target.value;
            let checked = event.target.checked;

            if (!itemId)
                return;

            const criteria = new Criteria();
            criteria.addFilter(
                Criteria.equals('id', itemId)
            );

            this.kzphSimplenoteRepository
                .search(criteria, Shopware.Context.api)
                .then(result => {
                    result.forEach((entity) => {
                        if (checked == true)
                            entity.showMessage = 1;
                        else
                            entity.showMessage = 0;

                        this.kzphSimplenoteRepository.save(entity, Shopware.Context.api);
                    });
                });
        },

        onShowConfirmation(event) {
            event.target.parentNode.style.display = "none";
            event.target.parentNode.nextElementSibling.style.display = "block";
        },

        onDeleteThisNote(event) {
            let itemId = event.target.dataset.id;

            if (!itemId)
                return;

            this.onRestoreConfirmation(event);
            this.kzphSimplenoteRepository.delete(itemId, Shopware.Context.api).then(result => {
                this.createNotificationSuccess({ title: this.$tc('kzph-simplenote-note.general.title'), message: this.$tc('kzph-simplenote-note.general.deleted') });
                this.loadItems();
            });
        },

        onRestoreConfirmation(event) {
            event.target.parentNode.parentNode.style.display = "none";
            event.target.parentNode.parentNode.previousElementSibling.style.display = "block";
        }
    }
});