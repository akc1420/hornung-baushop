import template from './clever-reach-abandoned-cart.html.twig';
import './clever-reach-abandoned-cart.scss';

const { Component, Mixin } = Shopware;

Component.register('clever-reach-abandoned-cart', {
    template,

    inject: [
        'cleverreachService'
    ],

    mixins: [
        Mixin.getByName('notification')
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
        this.getShops();
    },

    methods: {
        getShops: function () {
            this.isLoading = true;

            this.cleverreachService.getShops()
                .then((shops) => {
                    this.items = shops.shopsData;

                    if (shops.notification) {
                        this.createNotificationInfo({
                            title: this.$tc('clever-reach.abandonedCart.turnOff'),
                            message: this.$tc('clever-reach.abandonedCart.turnOffDesc')
                        });
                    }

                    this.isLoading = false;
                });
        },

        openSettings: function (shopId, shopName) {
            let route = {
                name: 'clever.reach.official.detail',
                params: {
                    shopId: shopId,
                    shopName: shopName
                }
            };

            this.$router.replace(route);
        }
    }
});
