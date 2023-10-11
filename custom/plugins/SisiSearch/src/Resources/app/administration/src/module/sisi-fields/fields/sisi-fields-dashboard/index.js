import template from './sisi-fields-dashboard.html.twig';

const {Component, Mixin} = Shopware;
const {Criteria} = Shopware.Data;
const httpClient = Shopware.Application.getContainer('init').httpClient;

Component.register('sisi-fields-dashboard', {
    template,
    name: 'SisiDashborad',
    inject: [
        'SisiApiCredentialsService'
    ],
    created() {
        this.getChannels();
    },
    data() {
        return {
            values : null,
            options:'',
            shopValue: '',
            language:'',
            hits : [{ id: 'uuid1', productname: 'Wordify', term: 'Portia Jobson' }]
        };
    },
    methods: {
        log(message){
            console.log(message)
        },
        changeChannel() {
            this.getHistory();
        },
        getChannels(){
            var $self = this;
            this.SisiApiCredentialsService.channelsWithlanguage().then((response) => {
                $self.options = response['channel'];
            }).catch((exception) => {

            });

        },
        getHistory() {
            var $self = this;
            var config = {'channel': this.shopValue};
            this.SisiApiCredentialsService.history(config).then((response) => {
                $self.values = response;
            }).catch((exception) => {

            });
        },
        hitsProductnameSource(value){
            var buckets = value['aggregations']['historyProductname']['buckets'];
            var hits = [];
            var arr = [];
            for(var j = 0; j < buckets.length; j++) {
                hits =
                    {
                        'productname': buckets[j]['key'],
                        'count': buckets[j]['doc_count']
                    };
                arr[j] = hits
            }
            return arr
        },
        hitsTermSource(value){
            var buckets = value['aggregations']['historyTerms']['buckets'];
            var hits = [];
            var arr = [];
            for(var j = 0; j < buckets.length; j++) {
                hits =
                    {
                        'term' : buckets[j]['key'],
                        'count' : buckets[j]['doc_count']
                    };
                arr[j]= hits
            }
            return arr
        }

    }
});
