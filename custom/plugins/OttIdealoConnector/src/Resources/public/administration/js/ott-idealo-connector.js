!(function (t) {
    var e = {};
    function n(o) {
        if (e[o]) return e[o].exports;
        var r = (e[o] = { i: o, l: !1, exports: {} });
        return t[o].call(r.exports, r, r.exports, n), (r.l = !0), r.exports;
    }
    (n.m = t),
        (n.c = e),
        (n.d = function (t, e, o) {
            n.o(t, e) ||
                Object.defineProperty(t, e, { enumerable: !0, get: o });
        }),
        (n.r = function (t) {
            'undefined' != typeof Symbol &&
                Symbol.toStringTag &&
                Object.defineProperty(t, Symbol.toStringTag, {
                    value: 'Module',
                }),
                Object.defineProperty(t, '__esModule', { value: !0 });
        }),
        (n.t = function (t, e) {
            if ((1 & e && (t = n(t)), 8 & e)) return t;
            if (4 & e && 'object' == typeof t && t && t.__esModule) return t;
            var o = Object.create(null);
            if (
                (n.r(o),
                Object.defineProperty(o, 'default', {
                    enumerable: !0,
                    value: t,
                }),
                2 & e && 'string' != typeof t)
            )
                for (var r in t)
                    n.d(
                        o,
                        r,
                        function (e) {
                            return t[e];
                        }.bind(null, r)
                    );
            return o;
        }),
        (n.n = function (t) {
            var e =
                t && t.__esModule
                    ? function () {
                          return t.default;
                      }
                    : function () {
                          return t;
                      };
            return n.d(e, 'a', e), e;
        }),
        (n.o = function (t, e) {
            return Object.prototype.hasOwnProperty.call(t, e);
        }),
        (n.p = '/bundles/administration/'),
        n((n.s = 'pzPC'));
})({
    '55rN': function (t, e) {
        var n = Shopware.Component,
            o = Shopware.Data.Criteria;
        n.extend('ott-order-state-select', 'sw-entity-single-select', {
            props: {
                criteria: {
                    type: Object,
                    required: !1,
                    default: function () {
                        var t = new o(1, this.resultLimit);
                        return (
                            t.addFilter(
                                o.equals(
                                    'stateMachine.technicalName',
                                    'order.state'
                                )
                            ),
                            t
                        );
                    },
                },
            },
        });
    },
    HIT5: function (t, e) {
        var n = Shopware.Component,
            o = Shopware.Data.Criteria;
        n.extend('ott-delivery-state-select', 'sw-entity-single-select', {
            props: {
                criteria: {
                    type: Object,
                    required: !1,
                    default: function () {
                        var t = new o(1, this.resultLimit);
                        return (
                            t.addFilter(
                                o.equals(
                                    'stateMachine.technicalName',
                                    'order_delivery.state'
                                )
                            ),
                            t
                        );
                    },
                },
            },
        });
    },
    'R/1z': function (t) {
        t.exports = JSON.parse(
            '{"ott-idealo-api-test-button":{"title":"API Test","success":"Connection was successfully tested","error":"Connection could not be established. Please check your access data"}}'
        );
    },
    X47u: function (t, e) {
        var n = Shopware.Component,
            o = Shopware.Data.Criteria;
        n.extend('ott-payment-state-select', 'sw-entity-single-select', {
            props: {
                criteria: {
                    type: Object,
                    required: !1,
                    default: function () {
                        var t = new o(1, this.resultLimit);
                        return (
                            t.addFilter(
                                o.equals(
                                    'stateMachine.technicalName',
                                    'order_transaction.state'
                                )
                            ),
                            t
                        );
                    },
                },
            },
        });
    },
    ewRk: function (t, e) {
        function n(t) {
            return (n =
                'function' == typeof Symbol &&
                'symbol' == typeof Symbol.iterator
                    ? function (t) {
                          return typeof t;
                      }
                    : function (t) {
                          return t &&
                              'function' == typeof Symbol &&
                              t.constructor === Symbol &&
                              t !== Symbol.prototype
                              ? 'symbol'
                              : typeof t;
                      })(t);
        }
        function o(t, e) {
            if (!(t instanceof e))
                throw new TypeError('Cannot call a class as a function');
        }
        function r(t, e) {
            for (var n = 0; n < e.length; n++) {
                var o = e[n];
                (o.enumerable = o.enumerable || !1),
                    (o.configurable = !0),
                    'value' in o && (o.writable = !0),
                    Object.defineProperty(t, o.key, o);
            }
        }
        function i(t, e) {
            return (i =
                Object.setPrototypeOf ||
                function (t, e) {
                    return (t.__proto__ = e), t;
                })(t, e);
        }
        function a(t) {
            var e = (function () {
                if ('undefined' == typeof Reflect || !Reflect.construct)
                    return !1;
                if (Reflect.construct.sham) return !1;
                if ('function' == typeof Proxy) return !0;
                try {
                    return (
                        Boolean.prototype.valueOf.call(
                            Reflect.construct(Boolean, [], function () {})
                        ),
                        !0
                    );
                } catch (t) {
                    return !1;
                }
            })();
            return function () {
                var n,
                    o = s(t);
                if (e) {
                    var r = s(this).constructor;
                    n = Reflect.construct(o, arguments, r);
                } else n = o.apply(this, arguments);
                return c(this, n);
            };
        }
        function c(t, e) {
            return !e || ('object' !== n(e) && 'function' != typeof e)
                ? (function (t) {
                      if (void 0 === t)
                          throw new ReferenceError(
                              "this hasn't been initialised - super() hasn't been called"
                          );
                      return t;
                  })(t)
                : e;
        }
        function s(t) {
            return (s = Object.setPrototypeOf
                ? Object.getPrototypeOf
                : function (t) {
                      return t.__proto__ || Object.getPrototypeOf(t);
                  })(t);
        }
        var u = Shopware.Classes.ApiService,
            l = Shopware.Application,
            f = (function (t) {
                !(function (t, e) {
                    if ('function' != typeof e && null !== e)
                        throw new TypeError(
                            'Super expression must either be null or a function'
                        );
                    (t.prototype = Object.create(e && e.prototype, {
                        constructor: {
                            value: t,
                            writable: !0,
                            configurable: !0,
                        },
                    })),
                        e && i(t, e);
                })(l, t);
                var e,
                    n,
                    c,
                    s = a(l);
                function l(t, e) {
                    var n =
                        arguments.length > 2 && void 0 !== arguments[2]
                            ? arguments[2]
                            : 'ott-idealo-api-test';
                    return o(this, l), s.call(this, t, e, n);
                }
                return (
                    (e = l),
                    (n = [
                        {
                            key: 'check',
                            value: function (t) {
                                var e = this.getBasicHeaders({});
                                return this.httpClient
                                    .post(
                                        '_action/'.concat(
                                            this.getApiBasePath(),
                                            '/verify'
                                        ),
                                        t,
                                        { headers: e }
                                    )
                                    .then(function (t) {
                                        return u.handleResponse(t);
                                    });
                            },
                        },
                    ]) && r(e.prototype, n),
                    c && r(e, c),
                    l
                );
            })(u);
        l.addServiceProvider('OttIdealoApiClient', function (t) {
            var e = l.getContainer('init');
            return new f(e.httpClient, t.loginService);
        });
    },
    gllP: function (t) {
        t.exports = JSON.parse(
            '{"ott-idealo-api-test-button":{"title":"API Test","success":"Verbindung wurde erfolgreich getestet","error":"Verbindung konnte nicht hergestellt werden. Bitte prüfen Sie Ihre Zugangsdaten"}}'
        );
    },
    pzPC: function (t, e, n) {
        'use strict';
        n.r(e);
        var o = n('wJak'),
            r = n.n(o),
            i = (n('55rN'), n('X47u'), n('HIT5'), n('ewRk'), n('tgPf')),
            a = n.n(i),
            c = Shopware,
            s = c.Component,
            u = c.Mixin;
        s.register('ott-idealo-api-test-button', {
            template: a.a,
            inject: ['OttIdealoApiClient'],
            mixins: [u.getByName('notification')],
            data: function () {
                return {
                    isLoading: !1,
                    isSaveSuccessful: !1,
                    label: this.$tc('ott-idealo-api-test-button.title'),
                };
            },
            computed: {
                pluginConfig: function () {
                    return {
                        clientId: document.getElementById(
                            'OttIdealoConnector.config.clientId'
                        ).value,
                        clientSecret: document.getElementById(
                            'OttIdealoConnector.config.clientSecret'
                        ).value,
                        isSandbox: document.getElementsByName(
                            'OttIdealoConnector.config.sandbox'
                        )[0].checked,
                    };
                },
            },
            methods: {
                saveFinish: function () {
                    this.isSaveSuccessful = !1;
                },
                check: function () {
                    var t = this;
                    (this.isLoading = !0),
                        this.OttIdealoApiClient.check(this.pluginConfig).then(
                            function (e) {
                                e.success
                                    ? ((t.isSaveSuccessful = !0),
                                      t.createNotificationSuccess({
                                          title: t.$tc(
                                              'ott-idealo-api-test-button.title'
                                          ),
                                          message: t.$tc(
                                              'ott-idealo-api-test-button.success'
                                          ),
                                      }))
                                    : t.createNotificationError({
                                          title: t.$tc(
                                              'ott-idealo-api-test-button.title'
                                          ),
                                          message: t.$tc(
                                              'ott-idealo-api-test-button.error'
                                          ),
                                      }),
                                    (t.isLoading = !1);
                            }
                        );
                },
            },
        });
        var l = n('gllP'),
            f = n('R/1z'),
            d = Shopware.Component;
        Shopware.Locale.extend('de-DE', l),
            Shopware.Locale.extend('en-GB', f),
            d.override('sw-order-list', {
                template: r.a,
                computed: {
                    orderColumns: function () {
                        var t = this.getOrderColumns();
                        return (
                            t.push({
                                property: 'customFields.ott_idealo_id',
                                dataIndex: 'customFields.ott_idealo_id',
                                label: 'Idealo TransaktionsID',
                                inlineEdit: 'string',
                                allowResize: !0,
                                align: 'left',
                            }),
                            t
                        );
                    },
                },
            });
    },
    tgPf: function (t, e) {
        t.exports =
            '<div>\n    <sw-button-process\n        :isLoading="isLoading"\n        :processSuccess="isSaveSuccessful"\n        @process-finish="saveFinish"\n        @click="check"\n    >API Test</sw-button-process>\n</div>\n';
    },
    wJak: function (t, e) {
        t.exports =
            '{% block sw_order_list_grid_columns_order_date %}\n    {% parent %}\n\n    <template slot="column-customFields.ott_idealo_id" slot-scope="{ item }">\n        {{ item.customFields.ott_idealo_id }}\n    </template>\n{% endblock %}\n';
    },
});
