define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const _ = require('underscore');
    const routing = require('routing');
    const $ = require('jquery');
    const manageFocus = require('oroui/js/tools/manage-focus').default;

    const CookieBannerView = BaseView.extend({

        /** @property {Object} */
        options: {},

        /** @property {String} */
        onCookiesAcceptedRoute: '',

        /** @property {String} */
        template: require('tpl-loader!orocookieconsent/templates/cookie-banner-view.html'),

        /** @property {String} */
        storageKey: 'cookieBannerHide',

        /** @property {Object} */
        events: {
            'click [data-action="accept"]': 'onAccept',
            'click [data-role="close"]': 'onClose',
            'transitionend': 'onTransitionend'
        },

        /**
         * @inheritdoc
         */
        constructor: function CookieBannerView(options) {
            CookieBannerView.__super__.constructor.call(this, options);
        },

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            this.onCookiesAcceptedRoute = this.options.cookiesAcceptedRoute;
            if (JSON.parse(localStorage.getItem(this.storageKey))) {
                this.dispose();
                return;
            }

            this.render();
            this.$el.addClass('show');
        },

        onTransitionend: _.once(function() {
            manageFocus.focusTabbable(this.$el);
        }),

        /**
         * @inheritdoc
         */
        render: function() {
            this.$el.html(this.template({
                bannerText: this.options.bannerText,
                bannerButtonLabel: _.__('oro_cookie_banner.accept_button.label'),
                landingPageHref: this.options.landingPageHref,
                landingPageLabel: this.options.landingPageLabel
            }));

            return this;
        },

        /**
         * Remove banner and save state in local storage
         */
        onAccept: function() {
            this._removeBanner();
            $.ajax({
                async: true,
                type: 'POST',
                url: routing.generate(this.onCookiesAcceptedRoute),
                success: result => {
                    /**
                     * In case when something went wrong on backend side
                     * we put result to the local storage
                     */
                    if (!result.success) {
                        localStorage.setItem(this.storageKey, true);
                    }
                }
            });
        },

        /**
         * Remove banner without save state in local storage
         */
        onClose: function() {
            this._removeBanner();
        },

        _removeBanner: function() {
            this.$el.remove();
            this.dispose();
        }
    });

    return CookieBannerView;
});
