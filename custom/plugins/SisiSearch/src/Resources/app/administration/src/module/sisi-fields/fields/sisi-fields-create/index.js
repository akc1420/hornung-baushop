const {Component} = Shopware;

Component.extend('sisi-fields-create', 'sisi-fields-detail', {

    methods: {
        getBundle() {
            this.fields = this.repository.create(Shopware.Context.api);
        },

        onClickSave() {
            this.isLoading = true;
            this.validate();
            if (this.strvalidate) {
                this.repository
                    .save(this.fields, Shopware.Context.api)
                    .then(() => {
                        this.isLoading = false;
                        this.$router.push({name: 'sisi.fields.detail', params: {id: this.fields.id}});
                    }).catch((exception) => {
                    console.log(exception);
                    this.isLoading = false;

                });
            } else {
                this.isLoading = false;
            }
        }
    }
});
