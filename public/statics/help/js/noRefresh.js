!function(t) {
    function e(o) {
        if (n[o]) return n[o].exports;
        var r = n[o] = {
            exports: {},
            id: o,
            loaded: !1
        };
        return t[o].call(r.exports, r, r.exports, e),
        r.loaded = !0,
        r.exports
    }
    var n = {};
    return e.m = t,
    e.c = n,
    e.p = "//imgcache.qq.com/qcloud/main/scripts/",
    e(0)
} ([function(t, e, n) {
    "use strict";
    t.exports = n(1)
},
function(t, e, n) {
    "use strict";
    function o(t, e) {
        if (! (t instanceof e)) throw new TypeError("Cannot call a class as a function")
    }
    var r = function() {
        function t(t, e) {
            for (var n = 0; n < e.length; n++) {
                var o = e[n];
                o.enumerable = o.enumerable || !1,
                o.configurable = !0,
                "value" in o && (o.writable = !0),
                Object.defineProperty(t, o.key, o)
            }
        }
        return function(e, n, o) {
            return n && t(e.prototype, n),
            o && t(e, o),
            e
        }
    } (),
    i = n(2),
    a = n(5),
    s = n(4),
    u = function() {
        function t(e) {
            o(this, t);
            var n = a.isWebkit,
            r = a.supportPushState,
            i = a.supportDomParser,
            s = n() && r() && i();
            return s ? (this.initOptions(e), void this.init()) : (console.warn("Your browser don't support pushstate or domParser!"), void(this.disable = !0))
        }
        return r(t, [{
            key: "initOptions",
            value: function(t) {
                var e = this,
                n = {
                    delegateSelector: null,
                    contentSelector: ".J-mainContent",
                    bypassXhrAttr: "bypass-xhr",
                    useXhrAttr: "use-xhr",
                    defaultBypass: !0,
                    beforeRender: s.noop,
                    afterRender: s.noop,
                    onBeforePopState: s.noop,
                    bypassFunc: s.noop,
                    nanobarOptions: {},
                    rejectHandler: null,
                    parsePageContent: null,
                    render: function(t) {
                        var n = t.title,
                        o = t.content;
                        document.title = n,
                        e.$container.html(o)
                    }
                };
                this.options = s.extend({},
                n, t)
            }
        },
        {
            key: "init",
            value: function() {
                this.$container = s(this.options.contentSelector);
                var t = decodeURIComponent(window.location.pathname + window.location.search),
                e = window.location.hash;
                this.currentUrl = t,
                history.replaceState({
                    url: t,
                    hash: e
                },
                null, t + e),
                this.progressBar = new i(this.options.nanobarOptions);
                var n = a.mutePrior,
                o = a.ensureDelay,
                r = a.memoize;
                this.fetchPageInfo = n(o(r(this._fetchPageInfo.bind(this)), 200)),
                this.bindEvents()
            }
        },
        {
            key: "destroy",
            value: function() {
                this._getEventDelegator().off("click"),
                s(window).off("popstate"),
                this.$container = null
            }
        },
        {
            key: "bindEvents",
            value: function() {
                var e = this;
                this._getEventDelegator().on("click", "a",
                function(t) {
                    if (!e.disable) {
                        var n = s(this),
                        o = "_blank" === n.attr("target"),
                        r = n.attr("href") || "";
                        if (! (t.ctrlKey || t.shiftKey || t.altKey || t.metaKey || o)) {
                            if (n.data("xhr-reload")) return t.preventDefault(),
                            e.xhrReload();
                            if (! (e.options.bypassFunc(t) || e.options.defaultBypass && !n.data(e.options.useXhrAttr) || !e.options.defaultBypass && null != n.data(e.options.bypassXhrAttr))) {
                                if (0 === r.indexOf("#")) {
                                    var i = r,
                                    a = decodeURIComponent(location.pathname) + location.search;
                                    return void history.pushState({
                                        url: a,
                                        hash: i
                                    },
                                    null, a + i)
                                }
                                return t.preventDefault(),
                                e.changePage(r)
                            }
                        }
                    }
                }),
                s(window).on("popstate",
                function(n) {
                    var o = n.originalEvent.state || {},
                    r = o.url,
                    i = o.hash;
                    e.options.onBeforePopState(n) || r && (r !== e.currentUrl ? e.loadPage(r).then(function() {
                        return t.scroll2Hash(i)
                    }) : t.scroll2Hash(i))
                })
            }
        },
        {
            key: "xhrReload",
            value: function() {
                var t = "" + location.href + (location.href.indexOf("?") > -1 ? "": "?") + "&t=" + Date.now();
                return this.loadPage(t)
            }
        },
        {
            key: "changePage",
            value: function(e) {
                var n = e.split("#"),
                o = decodeURIComponent(n[0]),
                r = n[1] ? "#" + n[1] : "";
                return 0 === o.indexOf("javascript") ? Promise.resolve(!1) : o && this.currentUrl !== o ? this.disable ? (window.location.href = e, Promise.resolve(!1)) : (history.pushState({
                    url: o,
                    hash: r
                },
                null, o + r), this.loadPage(o).then(function() {
                    return r ? t.scroll2Hash(r) : window.scrollTo(0, 0),
                    !0
                })) : (window.scrollTo(0, 0), Promise.resolve(!1))
            }
        },
        {
            key: "parsePageContent",
            value: function(t) {
                try {
                    var e = (new DOMParser).parseFromString(t, "text/html"),
                    n = {};
                    return "function" == typeof this.options.parsePageContent && (n = this.options.parsePageContent.call(this, e)),
                    s.extend({
                        title: e.title,
                        content: s(this.options.contentSelector, e).html()
                    },
                    n)
                } catch(t) {
                    return {}
                }
            }
        },
        {
            key: "loadPage",
            value: function(t) {
                var e = this;
                return this.currentUrl = t,
                this.disable ? void(window.location.href = t) : (this.progressBar.animate(), this.fetchPageInfo(t).then(function(n) {
                    if (!n.content) return void(location.href = t);
                    var o = e.options,
                    r = o.render,
                    i = o.beforeRender,
                    a = o.afterRender;
                    i(n, e.$container),
                    r(n, e.$container),
                    a(n, e.$container)
                },
                function(n) {
                    return "function" == typeof e.options.rejectHandler ? e.options.rejectHandler(n) : void(window.location.href = t)
                }).then(function() {
                    return e.progressBar.complete()
                }))
            }
        },
        {
            key: "disableXHR",
            value: function() {
                this.disable = !0
            }
        },
        {
            key: "enableXHR",
            value: function() {
                this.disable = !1
            }
        },
        {
            key: "_fetchPageInfo",
            value: function(t) {
                var e = this;
                return s.get(t).then(function(t) {
                    return e.parsePageContent(t)
                },
                function(t) {
                    return s.Deferred().reject(t)
                })
            }
        },
        {
            key: "_getEventDelegator",
            value: function() {
                return this.options.delegateSelector ? s(this.options.delegateSelector) : this.$container
            }
        }]),
        t
    } ();
    u.scroll2Hash = a.scroll2Hash,
    window.NoRefresh = t.exports = u
},
function(t, e, n) {
    "use strict";
    function o(t, e) {
        if (! (t instanceof e)) throw new TypeError("Cannot call a class as a function")
    }
    var r = function() {
        function t(t, e) {
            for (var n = 0; n < e.length; n++) {
                var o = e[n];
                o.enumerable = o.enumerable || !1,
                o.configurable = !0,
                "value" in o && (o.writable = !0),
                Object.defineProperty(t, o.key, o)
            }
        }
        return function(e, n, o) {
            return n && t(e.prototype, n),
            o && t(e, o),
            e
        }
    } (),
    i = n(3),
    a = n(4),
    s = function() {
        function t(e) {
            o(this, t),
            this.nanobar = new i(e),
            this.progressTimer = null
        }
        return r(t, [{
            key: "complete",
            value: function() {
                clearTimeout(this.progressTimer),
                this._onlySingleBar() && this.nanobar.go(100)
            }
        },
        {
            key: "animate",
            value: function() {
                function t() {
                    var t = 0;
                    return t = o >= 0 && o < .25 ? (3 * Math.random() + 3) / 100 : o >= .25 && o < .65 ? 3 * Math.random() / 100 : o >= .65 && o < .9 ? 2 * Math.random() / 100 : o >= .9 && o < .99 ? .005 : 0
                }
                var e = this;
                if (this._onlySingleBar()) {
                    var n = this.nanobar;
                    clearTimeout(this.progressTimer),
                    n.go(0);
                    var o = 0,
                    r = function r() {
                        e.progressTimer = setTimeout(function() {
                            clearTimeout(e.progressTimer);
                            var i = t();
                            i && (o += i, n.go(100 * o), r())
                        },
                        250)
                    };
                    r(o)
                }
            }
        },
        {
            key: "destroy",
            value: function() {
                clearTimeout(this.progressTimer),
                this.nanobar = null,
                this.progressTimer = null
            }
        },
        {
            key: "_onlySingleBar",
            value: function() {
                return this.nanobar && a(this.nanobar.el).children().length < 2
            }
        }]),
        t
    } ();
    t.exports = s
},
function(t, e, n) {
    var o, r, i = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ?
    function(t) {
        return typeof t
    }: function(t) {
        return t && "function" == typeof Symbol && t.constructor === Symbol && t !== Symbol.prototype ? "symbol": typeof t
    }; !
    function(n) {
        "use strict";
        function a() {
            var t = document.getElementById("nanobarcss");
            if (null === t) {
                if (t = document.createElement("style"), t.type = "text/css", t.id = "nanobarcss", document.head.insertBefore(t, document.head.firstChild), !t.styleSheet) return t.appendChild(document.createTextNode(c));
                t.styleSheet.cssText = c
            }
        }
        function s(t, e) {
            t.classList ? t.classList.add(e) : t.className += " " + e
        }
        function u(t) {
            function e() {
                var e = i - a;
                e < .1 && e > -.1 ? (n(a), u = 0, 100 === i && (r.style.height = 0, setTimeout(function() {
                    t(r)
                },
                300))) : (n(i - e / 4), setTimeout(o, 16))
            }
            function n(t) {
                i = t,
                r.style.width = i + "%"
            }
            function o(t) {
                t >= 0 ? (a = t, u || (u = 1, e())) : u && e()
            }
            var r = document.createElement("div"),
            i = 0,
            a = 0,
            u = 0,
            l = {
                el: r,
                go: o
            };
            return s(r, "bar"),
            l
        }
        function l(t) {
            function e(t) {
                r.removeChild(t)
            }
            function n() {
                var t = u(e);
                r.appendChild(t.el),
                o = t.go
            }
            t = t || {};
            var o, r = document.createElement("div"),
            i = {
                el: r,
                go: function(t) {
                    o(t),
                    100 === t && n()
                }
            };
            return a(),
            s(r, "nanobar"),
            t.id && (r.id = t.id),
            t.classname && s(r, t.classname),
            t.target ? (r.style.position = "relative", t.target.insertBefore(r, t.target.firstChild)) : (r.style.position = "fixed", document.getElementsByTagName("body")[0].appendChild(r)),
            n(),
            i
        }
        var c = ".nanobar{width: 100%; height: 2px; z-index: 99999; top: 0px; float: left; position: fixed;}.bar{background-color: rgb(1, 112, 204); width: 0px; height: 100%; clear: both; transition: height 0.3s; float: left;}";
        "object" === i(e) ? t.exports = l: (o = [], r = function() {
            return l
        }.apply(e, o), !(void 0 !== r && (t.exports = r)))
    } (void 0)
},
function(t, e) {
    t.exports = jQuery
},
function(t, e, n) {
    "use strict";
    var o = n(4),
    r = n(6),
    i = t.exports = {
        delay: function(t) {
            return o.Deferred(function() {
                setTimeout(this.resolve, t)
            }).promise()
        },
        isWebkit: function() {
            return /(chrome)[ \/]([\w.]+)|(webkit)[ \/]([\w.]+)/i.test(navigator.userAgent)
        },
        supportPushState: function() {
            try {
                return !! history.pushState
            } catch(t) {
                return ! 1
            }
        },
        supportDomParser: function() {
            if ("function" != typeof DOMParser) return ! 1;
            var t = DOMParser.prototype || {};
            return "function" == typeof t.parseFromString
        },
        memoize: function(t, e) {
            var n = {};
            e || (e = r.identity);
            var i = this;
            return function() {
                var a = e.apply(i, arguments);
                return a in n || (n[a] = o.when(t.apply(i, arguments))),
                n[a].then(r.identity)
            }
        },
        mutePrior: function(t, e) {
            var n = [["resolve", "done"], ["reject", "fail"]],
            i = [0],
            a = this;
            return function() {
                var s = t.apply(a, arguments);
                return i.push(s),
                o.Deferred(function(t) {
                    s.then.apply(s, o.map(n,
                    function(n) {
                        return function() {
                            var o = r.indexOf(i, s),
                            u = [].slice.call(arguments);
                            o === i.length - 1 ? (t[n[0]].apply(a, u), i.length = 1) : e && e(n[1], u)
                        }
                    }))
                }).promise()
            }
        },
        ensureDelay: function(t, e) {
            var n = this;
            return function() {
                var o = +new Date,
                r = t.apply(n, arguments);
                return r.then(function(t) {
                    var n = +new Date - o,
                    r = Math.max(0, e - n);
                    return i.delay(r).then(function() {
                        return t
                    })
                })
            }
        },
        scroll2Hash: function(t) {
            if (t || (t = ""), t = decodeURIComponent(decodeURIComponent(t)), 0 === t.indexOf("#")) {
                var e = document.getElementById(t.slice(1));
                if (e) {
                    location.hash !== t && (location.hash = t);
                    var n = (o(e).offset() || {}).top || 0;
                    n -= o(".J-fixedToc").height(),
                    n = Math.max(0, n - 5),
                    window.scrollTo(0, n),
                    o(window).trigger("scroll")
                }
            }
        }
    }
},
function(t, e) {
    t.exports = _
}]);
/*  |xGv00|7b6b076c8dfc26335e59613856341e8b */
