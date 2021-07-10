(window["aioseopjsonp"]=window["aioseopjsonp"]||[]).push([["local-seo-Locations-vue","local-seo-pro-LocalSeoCta-vue","local-seo-pro-Locations-vue"],{"41bf":function(t,e,n){},"734c":function(t,e,n){"use strict";n.r(e);var i=function(){var t=this,e=t.$createElement,n=t._self._c||e;return n("cta",{attrs:{"cta-link":t.$aioseo.urls.aio.featureManager+"&aioseo-activate=aioseo-local-business","cta-button-action":"","cta-button-loading":t.activationLoading,"same-tab":"","button-text":t.strings.ctaButtonText,"learn-more-link":t.$links.getDocUrl("localSeo"),"feature-list":[t.strings.businessType,t.strings.businessContact,t.strings.paymentInfo,t.strings.image,t.strings.showOpeningHours,t.strings.selectTimeZoneCTA]},on:{"cta-button-click":t.activateAddon},scopedSlots:t._u([{key:"header-text",fn:function(){return[t._v(" "+t._s(t.strings.locationSeoHeader)+" ")]},proxy:!0},{key:"description",fn:function(){return[t.failed?n("core-alert",{attrs:{type:"red"}},[t._v(" "+t._s(t.strings.activateError)+" ")]):t._e(),t._v(" "+t._s(t.strings.ctaDescription)+" ")]},proxy:!0},{key:"learn-more-text",fn:function(){return[t._v(" "+t._s(t.strings.learnMoreText)+" ")]},proxy:!0}])})},s=[],o=(n("7db0"),n("5530")),a=n("2f62"),c={data:function(){return{failed:!1,activationLoading:!1,strings:{locationSeoHeader:this.$t.__("Enable Local SEO on your Site",this.$tdPro),ctaDescription:this.$t.__("The Local SEO module is a premium feature that enables businesses to tell Google about their business, including their business name, address and phone number, opening hours and price range.  This information may be displayed as a Knowledge Graph card or business carousel in the search engine sidebar.",this.$tdPro),ctaButtonText:this.$t.__("Activate Local SEO",this.$tdPro),learnMoreText:this.$t.__("Learn more about Local SEO",this.$tdPro),showOpeningHours:this.$t.__("Show Opening Hours",this.$td),selectTimeZoneCTA:this.$t.__("Select your timezone",this.$td),businessType:this.$t.__("Business Type",this.$td),businessContact:this.$t.__("Business Contact Info",this.$td),paymentInfo:this.$t.__("Payment Info",this.$td),image:this.$t.__("Business Image",this.$td),activateError:this.$t.__("An error occurred while activating the addon. Please upload it manually or contact support for more information.",this.$td)}}},computed:Object(o["a"])({},Object(a["e"])(["addons"])),methods:Object(o["a"])(Object(o["a"])(Object(o["a"])({},Object(a["b"])(["installPlugins"])),Object(a["d"])(["updateAddon"])),{},{activateAddon:function(){var t=this;this.failed=!1,this.activationLoading=!0;var e=this.addons.find((function(t){return"aioseo-local-business"===t.sku}));this.installPlugins([{plugin:e.basename}]).then((function(n){t.activationLoading=!1,n.body.failed.length?t.failed=!0:(e.isActive=!0,t.updateAddon(e))})).catch((function(){t.activationLoading=!1}))}})},r=c,l=n("2877"),u=Object(l["a"])(r,i,s,!1,null,null,null);e["default"]=u.exports},"85eb":function(t,e,n){"use strict";var i=n("41bf"),s=n.n(i);s.a},f0a3:function(t,e,n){"use strict";n.r(e);var i=function(){var t=this,e=t.$createElement,n=t._self._c||e;return n("div",{staticClass:"aioseo-locations"},[t.isUnlicensed||!t.localSeoIsActive||t.localSeoUpgrade?t._e():n("locations"),t.isUnlicensed||t.localSeoIsActive||!t.localSeoCanActivate||t.localSeoUpgrade?t._e():n("locations-activate"),t.isUnlicensed||t.localSeoUpgrade?n("locations-lite"):t._e()],1)},s=[],o=(n("7db0"),n("5530")),a=n("2f62"),c=n("bcdb"),r=n("2d4e"),l=n("ba9f"),u={components:{Locations:c["default"],LocationsActivate:r["default"],LocationsLite:l["default"]},data:function(){return{strings:{locationsSettings:this.$t.__("Locations Settings",this.$td)}}},computed:Object(o["a"])(Object(o["a"])(Object(o["a"])({},Object(a["c"])(["isUnlicensed"])),Object(a["e"])(["options","settings","addons"])),{},{localSeoIsActive:function(){var t=this.addons.find((function(t){return"aioseo-local-business"===t.sku}));return t&&t.isActive},localSeoUpgrade:function(){var t=this.addons.find((function(t){return"aioseo-local-business"===t.sku}));return!t||t.requiresUpgrade},localSeoCanActivate:function(){var t=this.addons.find((function(t){return"aioseo-local-business"===t.sku}));return t&&!t.isActive}})},d=u,f=(n("85eb"),n("2877")),h=Object(f["a"])(d,i,s,!1,null,null,null);e["default"]=h.exports}}]);