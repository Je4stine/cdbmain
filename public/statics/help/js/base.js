void
function(t) {
    if (t.call(function() {
        return this
    } ()), "object" == typeof QCMAIN_STORE && QCMAIN_STORE) {
        var n = /^function\s*(?:[^(]*)\(([^)]*)\)\s*{([\s\S]*)}$/,
        i = String(t).match(n) && RegExp.$2;
        i && QCMAIN_STORE.set("common/base.47f58fd04e10d67b175c.js", i, {
            expire: 720
        })
    }
} (function() { !
    function(e) {
        function t(r) {
            if (n[r]) return n[r].exports;
            var i = n[r] = {
                exports: {},
                id: r,
                loaded: !1
            };
            return e[r].call(i.exports, i, i.exports, t),
            i.loaded = !0,
            i.exports
        }
        var n = {};
        return t.m = e,
        t.c = n,
        t.p = "//imgcache.qq.com/qcloud/main/scripts/",
        t(0)
    } ({
        0 : function(e, t, n) { (function(t) {
                function r() {}
                var i = n("+cXR");
                window.console || (window.console = {
                    log: r,
                    warn: r,
                    error: r,
                    info: r,
                    dir: r
                }),
                n("NcIV").polyfill(),
                i.underscore = t._ = n("1z45"),
                i.jquery = t.jQuery = n("uUzA"),
                t.$ = t.jQuery,
                i.cookie = n("AxIY"),
                i.util = n("4Ahm"),
                i.net = n("94Dj"),
                i.login = n("x0+R"),
                i.video = n("ulYS"),
                i.report = n("1au2"),
                e.exports = i
            }).call(t,
            function() {
                return this
            } ())
        },
        "1au2": function(e, t) {
            function n(e) {
                if (!e || !e.url || !e.type) return void console.warn("Unexpected report options!", e);
                var t = new Image;
                t.onload = t.onerror = t.onabort = function() {
                    t = t.onload = t.onerror = t.onabort = null
                },
                t.src = r + "?" + $.param(e)
            }
            var r = "https://report.qcloud.com/collect/exceptionPage";
            e.exports = n
        },
        NcIV: function(e, t, n) { (function(t, r) {
                /*!
	 * @overview es6-promise - a tiny implementation of Promises/A+.
	 * @copyright Copyright (c) 2014 Yehuda Katz, Tom Dale, Stefan Penner and contributors (Conversion to ES6 API by Jake Archibald)
	 * @license   Licensed under MIT license
	 *            See https://raw.githubusercontent.com/stefanpenner/es6-promise/master/LICENSE
	 * @version   4.0.5
	 */
                !
                function(t, n) {
                    e.exports = n()
                } (this,
                function() {
                    "use strict";
                    function e(e) {
                        return "function" == typeof e || "object" == typeof e && null !== e
                    }
                    function i(e) {
                        return "function" == typeof e
                    }
                    function o(e) {
                        J = e
                    }
                    function a(e) {
                        Z = e
                    }
                    function s() {
                        return function() {
                            return t.nextTick(d)
                        }
                    }
                    function u() {
                        return "undefined" != typeof X ?
                        function() {
                            X(d)
                        }: f()
                    }
                    function c() {
                        var e = 0,
                        t = new K(d),
                        n = document.createTextNode("");
                        return t.observe(n, {
                            characterData: !0
                        }),
                        function() {
                            n.data = e = ++e % 2
                        }
                    }
                    function l() {
                        var e = new MessageChannel;
                        return e.port1.onmessage = d,
                        function() {
                            return e.port2.postMessage(0)
                        }
                    }
                    function f() {
                        var e = setTimeout;
                        return function() {
                            return e(d, 1)
                        }
                    }
                    function d() {
                        for (var e = 0; e < Y; e += 2) {
                            var t = ne[e],
                            n = ne[e + 1];
                            t(n),
                            ne[e] = void 0,
                            ne[e + 1] = void 0
                        }
                        Y = 0
                    }
                    function p() {
                        try {
                            var e = n(!
                            function() {
                                var e = new Error('Cannot find module "vertx"');
                                throw e.code = "MODULE_NOT_FOUND",
                                e
                            } ());
                            return X = e.runOnLoop || e.runOnContext,
                            u()
                        } catch(e) {
                            return f()
                        }
                    }
                    function h(e, t) {
                        var n = arguments,
                        r = this,
                        i = new this.constructor(g);
                        void 0 === i[ie] && R(i);
                        var o = r._state;
                        return o ? !
                        function() {
                            var e = n[o - 1];
                            Z(function() {
                                return L(o, i, e, r._result)
                            })
                        } () : S(r, i, e, t),
                        i
                    }
                    function v(e) {
                        var t = this;
                        if (e && "object" == typeof e && e.constructor === t) return e;
                        var n = new t(g);
                        return A(n, e),
                        n
                    }
                    function g() {}
                    function m() {
                        return new TypeError("You cannot resolve a promise with itself")
                    }
                    function y() {
                        return new TypeError("A promises callback cannot return that same promise.")
                    }
                    function b(e) {
                        try {
                            return e.then
                        } catch(e) {
                            return ue.error = e,
                            ue
                        }
                    }
                    function x(e, t, n, r) {
                        try {
                            e.call(t, n, r)
                        } catch(e) {
                            return e
                        }
                    }
                    function w(e, t, n) {
                        Z(function(e) {
                            var r = !1,
                            i = x(n, t,
                            function(n) {
                                r || (r = !0, t !== n ? A(e, n) : E(e, n))
                            },
                            function(t) {
                                r || (r = !0, N(e, t))
                            },
                            "Settle: " + (e._label || " unknown promise")); ! r && i && (r = !0, N(e, i))
                        },
                        e)
                    }
                    function T(e, t) {
                        t._state === ae ? E(e, t._result) : t._state === se ? N(e, t._result) : S(t, void 0,
                        function(t) {
                            return A(e, t)
                        },
                        function(t) {
                            return N(e, t)
                        })
                    }
                    function C(e, t, n) {
                        t.constructor === e.constructor && n === h && t.constructor.resolve === v ? T(e, t) : n === ue ? N(e, ue.error) : void 0 === n ? E(e, t) : i(n) ? w(e, t, n) : E(e, t)
                    }
                    function A(t, n) {
                        t === n ? N(t, m()) : e(n) ? C(t, n, b(n)) : E(t, n)
                    }
                    function k(e) {
                        e._onerror && e._onerror(e._result),
                        j(e)
                    }
                    function E(e, t) {
                        e._state === oe && (e._result = t, e._state = ae, 0 !== e._subscribers.length && Z(j, e))
                    }
                    function N(e, t) {
                        e._state === oe && (e._state = se, e._result = t, Z(k, e))
                    }
                    function S(e, t, n, r) {
                        var i = e._subscribers,
                        o = i.length;
                        e._onerror = null,
                        i[o] = t,
                        i[o + ae] = n,
                        i[o + se] = r,
                        0 === o && e._state && Z(j, e)
                    }
                    function j(e) {
                        var t = e._subscribers,
                        n = e._state;
                        if (0 !== t.length) {
                            for (var r = void 0,
                            i = void 0,
                            o = e._result,
                            a = 0; a < t.length; a += 3) r = t[a],
                            i = t[a + n],
                            r ? L(n, r, i, o) : i(o);
                            e._subscribers.length = 0
                        }
                    }
                    function _() {
                        this.error = null
                    }
                    function D(e, t) {
                        try {
                            return e(t)
                        } catch(e) {
                            return ce.error = e,
                            ce
                        }
                    }
                    function L(e, t, n, r) {
                        var o = i(n),
                        a = void 0,
                        s = void 0,
                        u = void 0,
                        c = void 0;
                        if (o) {
                            if (a = D(n, r), a === ce ? (c = !0, s = a.error, a = null) : u = !0, t === a) return void N(t, y())
                        } else a = r,
                        u = !0;
                        t._state !== oe || (o && u ? A(t, a) : c ? N(t, s) : e === ae ? E(t, a) : e === se && N(t, a))
                    }
                    function O(e, t) {
                        try {
                            t(function(t) {
                                A(e, t)
                            },
                            function(t) {
                                N(e, t)
                            })
                        } catch(t) {
                            N(e, t)
                        }
                    }
                    function M() {
                        return le++
                    }
                    function R(e) {
                        e[ie] = le++,
                        e._state = void 0,
                        e._result = void 0,
                        e._subscribers = []
                    }
                    function H(e, t) {
                        this._instanceConstructor = e,
                        this.promise = new e(g),
                        this.promise[ie] || R(this.promise),
                        G(t) ? (this._input = t, this.length = t.length, this._remaining = t.length, this._result = new Array(this.length), 0 === this.length ? E(this.promise, this._result) : (this.length = this.length || 0, this._enumerate(), 0 === this._remaining && E(this.promise, this._result))) : N(this.promise, F())
                    }
                    function F() {
                        return new Error("Array Methods must be provided an Array")
                    }
                    function I(e) {
                        return new H(this, e).promise
                    }
                    function q(e) {
                        var t = this;
                        return new t(G(e) ?
                        function(n, r) {
                            for (var i = e.length,
                            o = 0; o < i; o++) t.resolve(e[o]).then(n, r)
                        }: function(e, t) {
                            return t(new TypeError("You must pass an array to race."))
                        })
                    }
                    function P(e) {
                        var t = this,
                        n = new t(g);
                        return N(n, e),
                        n
                    }
                    function B() {
                        throw new TypeError("You must pass a resolver function as the first argument to the promise constructor")
                    }
                    function W() {
                        throw new TypeError("Failed to construct 'Promise': Please use the 'new' operator, this object constructor cannot be called as a function.")
                    }
                    function z(e) {
                        this[ie] = M(),
                        this._result = this._state = void 0,
                        this._subscribers = [],
                        g !== e && ("function" != typeof e && B(), this instanceof z ? O(this, e) : W())
                    }
                    function $() {
                        var e = void 0;
                        if ("undefined" != typeof r) e = r;
                        else if ("undefined" != typeof self) e = self;
                        else try {
                            e = Function("return this")()
                        } catch(e) {
                            throw new Error("polyfill failed because global object is unavailable in this environment")
                        }
                        var t = e.Promise;
                        if (t) {
                            var n = null;
                            try {
                                n = Object.prototype.toString.call(t.resolve())
                            } catch(e) {}
                            if ("[object Promise]" === n && !t.cast) return
                        }
                        e.Promise = z
                    }
                    var U = void 0;
                    U = Array.isArray ? Array.isArray: function(e) {
                        return "[object Array]" === Object.prototype.toString.call(e)
                    };
                    var G = U,
                    Y = 0,
                    X = void 0,
                    J = void 0,
                    Z = function(e, t) {
                        ne[Y] = e,
                        ne[Y + 1] = t,
                        Y += 2,
                        2 === Y && (J ? J(d) : re())
                    },
                    V = "undefined" != typeof window ? window: void 0,
                    Q = V || {},
                    K = Q.MutationObserver || Q.WebKitMutationObserver,
                    ee = "undefined" == typeof self && "undefined" != typeof t && "[object process]" === {}.toString.call(t),
                    te = "undefined" != typeof Uint8ClampedArray && "undefined" != typeof importScripts && "undefined" != typeof MessageChannel,
                    ne = new Array(1e3),
                    re = void 0;
                    re = ee ? s() : K ? c() : te ? l() : void 0 === V ? p() : f();
                    var ie = Math.random().toString(36).substring(16),
                    oe = void 0,
                    ae = 1,
                    se = 2,
                    ue = new _,
                    ce = new _,
                    le = 0;
                    return H.prototype._enumerate = function() {
                        for (var e = this.length,
                        t = this._input,
                        n = 0; this._state === oe && n < e; n++) this._eachEntry(t[n], n)
                    },
                    H.prototype._eachEntry = function(e, t) {
                        var n = this._instanceConstructor,
                        r = n.resolve;
                        if (r === v) {
                            var i = b(e);
                            if (i === h && e._state !== oe) this._settledAt(e._state, t, e._result);
                            else if ("function" != typeof i) this._remaining--,
                            this._result[t] = e;
                            else if (n === z) {
                                var o = new n(g);
                                C(o, e, i),
                                this._willSettleAt(o, t)
                            } else this._willSettleAt(new n(function(t) {
                                return t(e)
                            }), t)
                        } else this._willSettleAt(r(e), t)
                    },
                    H.prototype._settledAt = function(e, t, n) {
                        var r = this.promise;
                        r._state === oe && (this._remaining--, e === se ? N(r, n) : this._result[t] = n),
                        0 === this._remaining && E(r, this._result)
                    },
                    H.prototype._willSettleAt = function(e, t) {
                        var n = this;
                        S(e, void 0,
                        function(e) {
                            return n._settledAt(ae, t, e)
                        },
                        function(e) {
                            return n._settledAt(se, t, e)
                        })
                    },
                    z.all = I,
                    z.race = q,
                    z.resolve = v,
                    z.reject = P,
                    z._setScheduler = o,
                    z._setAsap = a,
                    z._asap = Z,
                    z.prototype = {
                        constructor: z,
                        then: h,
                        catch: function(e) {
                            return this.then(null, e)
                        }
                    },
                    z.polyfill = $,
                    z.Promise = z,
                    z
                })
            }).call(t, n("FT5O"),
            function() {
                return this
            } ())
        },
        FT5O: function(e, t) {
            function n() {
                throw new Error("setTimeout has not been defined")
            }
            function r() {
                throw new Error("clearTimeout has not been defined")
            }
            function i(e) {
                if (l === setTimeout) return setTimeout(e, 0);
                if ((l === n || !l) && setTimeout) return l = setTimeout,
                setTimeout(e, 0);
                try {
                    return l(e, 0)
                } catch(t) {
                    try {
                        return l.call(null, e, 0)
                    } catch(t) {
                        return l.call(this, e, 0)
                    }
                }
            }
            function o(e) {
                if (f === clearTimeout) return clearTimeout(e);
                if ((f === r || !f) && clearTimeout) return f = clearTimeout,
                clearTimeout(e);
                try {
                    return f(e)
                } catch(t) {
                    try {
                        return f.call(null, e)
                    } catch(t) {
                        return f.call(this, e)
                    }
                }
            }
            function a() {
                v && p && (v = !1, p.length ? h = p.concat(h) : g = -1, h.length && s())
            }
            function s() {
                if (!v) {
                    var e = i(a);
                    v = !0;
                    for (var t = h.length; t;) {
                        for (p = h, h = []; ++g < t;) p && p[g].run();
                        g = -1,
                        t = h.length
                    }
                    p = null,
                    v = !1,
                    o(e)
                }
            }
            function u(e, t) {
                this.fun = e,
                this.array = t
            }
            function c() {}
            var l, f, d = e.exports = {}; !
            function() {
                try {
                    l = "function" == typeof setTimeout ? setTimeout: n
                } catch(e) {
                    l = n
                }
                try {
                    f = "function" == typeof clearTimeout ? clearTimeout: r
                } catch(e) {
                    f = r
                }
            } ();
            var p, h = [],
            v = !1,
            g = -1;
            d.nextTick = function(e) {
                var t = new Array(arguments.length - 1);
                if (arguments.length > 1) for (var n = 1; n < arguments.length; n++) t[n - 1] = arguments[n];
                h.push(new u(e, t)),
                1 !== h.length || v || i(s)
            },
            u.prototype.run = function() {
                this.fun.apply(null, this.array)
            },
            d.title = "browser",
            d.browser = !0,
            d.env = {},
            d.argv = [],
            d.version = "",
            d.versions = {},
            d.on = c,
            d.addListener = c,
            d.once = c,
            d.off = c,
            d.removeListener = c,
            d.removeAllListeners = c,
            d.emit = c,
            d.prependListener = c,
            d.prependOnceListener = c,
            d.listeners = function(e) {
                return []
            },
            d.binding = function(e) {
                throw new Error("process.binding is not supported")
            },
            d.cwd = function() {
                return "/"
            },
            d.chdir = function(e) {
                throw new Error("process.chdir is not supported")
            },
            d.umask = function() {
                return 0
            }
        },
        "1z45": function(e, t, n) {
            var r, i; (function() {
                function n(e) {
                    function t(t, n, r, i, o, a) {
                        for (; o >= 0 && o < a; o += e) {
                            var s = i ? i[o] : o;
                            r = n(r, t[s], s, t)
                        }
                        return r
                    }
                    return function(n, r, i, o) {
                        r = C(r, o, 4);
                        var a = !_(n) && T.keys(n),
                        s = (a || n).length,
                        u = e > 0 ? 0 : s - 1;
                        return arguments.length < 3 && (i = n[a ? a[u] : u], u += e),
                        t(n, r, i, a, u, s)
                    }
                }
                function o(e) {
                    return function(t, n, r) {
                        n = A(n, r);
                        for (var i = j(t), o = e > 0 ? 0 : i - 1; o >= 0 && o < i; o += e) if (n(t[o], o, t)) return o;
                        return - 1
                    }
                }
                function a(e, t, n) {
                    return function(r, i, o) {
                        var a = 0,
                        s = j(r);
                        if ("number" == typeof o) e > 0 ? a = o >= 0 ? o: Math.max(o + s, a) : s = o >= 0 ? Math.min(o + 1, s) : o + s + 1;
                        else if (n && o && s) return o = n(r, i),
                        r[o] === i ? o: -1;
                        if (i !== i) return o = t(h.call(r, a, s), T.isNaN),
                        o >= 0 ? o + a: -1;
                        for (o = e > 0 ? a: s - 1; o >= 0 && o < s; o += e) if (r[o] === i) return o;
                        return - 1
                    }
                }
                function s(e, t) {
                    var n = R.length,
                    r = e.constructor,
                    i = T.isFunction(r) && r.prototype || f,
                    o = "constructor";
                    for (T.has(e, o) && !T.contains(t, o) && t.push(o); n--;) o = R[n],
                    o in e && e[o] !== i[o] && !T.contains(t, o) && t.push(o)
                }
                var u = this,
                c = u._,
                l = Array.prototype,
                f = Object.prototype,
                d = Function.prototype,
                p = l.push,
                h = l.slice,
                v = f.toString,
                g = f.hasOwnProperty,
                m = Array.isArray,
                y = Object.keys,
                b = d.bind,
                x = Object.create,
                w = function() {},
                T = function(e) {
                    return e instanceof T ? e: this instanceof T ? void(this._wrapped = e) : new T(e)
                };
                "undefined" != typeof e && e.exports && (t = e.exports = T),
                t._ = T,
                T.VERSION = "1.8.3";
                var C = function(e, t, n) {
                    if (void 0 === t) return e;
                    switch (null == n ? 3 : n) {
                    case 1:
                        return function(n) {
                            return e.call(t, n)
                        };
                    case 2:
                        return function(n, r) {
                            return e.call(t, n, r)
                        };
                    case 3:
                        return function(n, r, i) {
                            return e.call(t, n, r, i)
                        };
                    case 4:
                        return function(n, r, i, o) {
                            return e.call(t, n, r, i, o)
                        }
                    }
                    return function() {
                        return e.apply(t, arguments)
                    }
                },
                A = function(e, t, n) {
                    return null == e ? T.identity: T.isFunction(e) ? C(e, t, n) : T.isObject(e) ? T.matcher(e) : T.property(e)
                };
                T.iteratee = function(e, t) {
                    return A(e, t, 1 / 0)
                };
                var k = function(e, t) {
                    return function(n) {
                        var r = arguments.length;
                        if (r < 2 || null == n) return n;
                        for (var i = 1; i < r; i++) for (var o = arguments[i], a = e(o), s = a.length, u = 0; u < s; u++) {
                            var c = a[u];
                            t && void 0 !== n[c] || (n[c] = o[c])
                        }
                        return n
                    }
                },
                E = function(e) {
                    if (!T.isObject(e)) return {};
                    if (x) return x(e);
                    w.prototype = e;
                    var t = new w;
                    return w.prototype = null,
                    t
                },
                N = function(e) {
                    return function(t) {
                        return null == t ? void 0 : t[e]
                    }
                },
                S = Math.pow(2, 53) - 1,
                j = N("length"),
                _ = function(e) {
                    var t = j(e);
                    return "number" == typeof t && t >= 0 && t <= S
                };
                T.each = T.forEach = function(e, t, n) {
                    t = C(t, n);
                    var r, i;
                    if (_(e)) for (r = 0, i = e.length; r < i; r++) t(e[r], r, e);
                    else {
                        var o = T.keys(e);
                        for (r = 0, i = o.length; r < i; r++) t(e[o[r]], o[r], e)
                    }
                    return e
                },
                T.map = T.collect = function(e, t, n) {
                    t = A(t, n);
                    for (var r = !_(e) && T.keys(e), i = (r || e).length, o = Array(i), a = 0; a < i; a++) {
                        var s = r ? r[a] : a;
                        o[a] = t(e[s], s, e)
                    }
                    return o
                },
                T.reduce = T.foldl = T.inject = n(1),
                T.reduceRight = T.foldr = n( - 1),
                T.find = T.detect = function(e, t, n) {
                    var r;
                    if (r = _(e) ? T.findIndex(e, t, n) : T.findKey(e, t, n), void 0 !== r && r !== -1) return e[r]
                },
                T.filter = T.select = function(e, t, n) {
                    var r = [];
                    return t = A(t, n),
                    T.each(e,
                    function(e, n, i) {
                        t(e, n, i) && r.push(e)
                    }),
                    r
                },
                T.reject = function(e, t, n) {
                    return T.filter(e, T.negate(A(t)), n)
                },
                T.every = T.all = function(e, t, n) {
                    t = A(t, n);
                    for (var r = !_(e) && T.keys(e), i = (r || e).length, o = 0; o < i; o++) {
                        var a = r ? r[o] : o;
                        if (!t(e[a], a, e)) return ! 1
                    }
                    return ! 0
                },
                T.some = T.any = function(e, t, n) {
                    t = A(t, n);
                    for (var r = !_(e) && T.keys(e), i = (r || e).length, o = 0; o < i; o++) {
                        var a = r ? r[o] : o;
                        if (t(e[a], a, e)) return ! 0
                    }
                    return ! 1
                },
                T.contains = T.includes = T.include = function(e, t, n, r) {
                    return _(e) || (e = T.values(e)),
                    ("number" != typeof n || r) && (n = 0),
                    T.indexOf(e, t, n) >= 0
                },
                T.invoke = function(e, t) {
                    var n = h.call(arguments, 2),
                    r = T.isFunction(t);
                    return T.map(e,
                    function(e) {
                        var i = r ? t: e[t];
                        return null == i ? i: i.apply(e, n)
                    })
                },
                T.pluck = function(e, t) {
                    return T.map(e, T.property(t))
                },
                T.where = function(e, t) {
                    return T.filter(e, T.matcher(t))
                },
                T.findWhere = function(e, t) {
                    return T.find(e, T.matcher(t))
                },
                T.max = function(e, t, n) {
                    var r, i, o = -(1 / 0),
                    a = -(1 / 0);
                    if (null == t && null != e) {
                        e = _(e) ? e: T.values(e);
                        for (var s = 0,
                        u = e.length; s < u; s++) r = e[s],
                        r > o && (o = r)
                    } else t = A(t, n),
                    T.each(e,
                    function(e, n, r) {
                        i = t(e, n, r),
                        (i > a || i === -(1 / 0) && o === -(1 / 0)) && (o = e, a = i)
                    });
                    return o
                },
                T.min = function(e, t, n) {
                    var r, i, o = 1 / 0,
                    a = 1 / 0;
                    if (null == t && null != e) {
                        e = _(e) ? e: T.values(e);
                        for (var s = 0,
                        u = e.length; s < u; s++) r = e[s],
                        r < o && (o = r)
                    } else t = A(t, n),
                    T.each(e,
                    function(e, n, r) {
                        i = t(e, n, r),
                        (i < a || i === 1 / 0 && o === 1 / 0) && (o = e, a = i)
                    });
                    return o
                },
                T.shuffle = function(e) {
                    for (var t, n = _(e) ? e: T.values(e), r = n.length, i = Array(r), o = 0; o < r; o++) t = T.random(0, o),
                    t !== o && (i[o] = i[t]),
                    i[t] = n[o];
                    return i
                },
                T.sample = function(e, t, n) {
                    return null == t || n ? (_(e) || (e = T.values(e)), e[T.random(e.length - 1)]) : T.shuffle(e).slice(0, Math.max(0, t))
                },
                T.sortBy = function(e, t, n) {
                    return t = A(t, n),
                    T.pluck(T.map(e,
                    function(e, n, r) {
                        return {
                            value: e,
                            index: n,
                            criteria: t(e, n, r)
                        }
                    }).sort(function(e, t) {
                        var n = e.criteria,
                        r = t.criteria;
                        if (n !== r) {
                            if (n > r || void 0 === n) return 1;
                            if (n < r || void 0 === r) return - 1
                        }
                        return e.index - t.index
                    }), "value")
                };
                var D = function(e) {
                    return function(t, n, r) {
                        var i = {};
                        return n = A(n, r),
                        T.each(t,
                        function(r, o) {
                            var a = n(r, o, t);
                            e(i, r, a)
                        }),
                        i
                    }
                };
                T.groupBy = D(function(e, t, n) {
                    T.has(e, n) ? e[n].push(t) : e[n] = [t]
                }),
                T.indexBy = D(function(e, t, n) {
                    e[n] = t
                }),
                T.countBy = D(function(e, t, n) {
                    T.has(e, n) ? e[n]++:e[n] = 1
                }),
                T.toArray = function(e) {
                    return e ? T.isArray(e) ? h.call(e) : _(e) ? T.map(e, T.identity) : T.values(e) : []
                },
                T.size = function(e) {
                    return null == e ? 0 : _(e) ? e.length: T.keys(e).length
                },
                T.partition = function(e, t, n) {
                    t = A(t, n);
                    var r = [],
                    i = [];
                    return T.each(e,
                    function(e, n, o) { (t(e, n, o) ? r: i).push(e)
                    }),
                    [r, i]
                },
                T.first = T.head = T.take = function(e, t, n) {
                    if (null != e) return null == t || n ? e[0] : T.initial(e, e.length - t)
                },
                T.initial = function(e, t, n) {
                    return h.call(e, 0, Math.max(0, e.length - (null == t || n ? 1 : t)))
                },
                T.last = function(e, t, n) {
                    if (null != e) return null == t || n ? e[e.length - 1] : T.rest(e, Math.max(0, e.length - t))
                },
                T.rest = T.tail = T.drop = function(e, t, n) {
                    return h.call(e, null == t || n ? 1 : t)
                },
                T.compact = function(e) {
                    return T.filter(e, T.identity)
                };
                var L = function(e, t, n, r) {
                    for (var i = [], o = 0, a = r || 0, s = j(e); a < s; a++) {
                        var u = e[a];
                        if (_(u) && (T.isArray(u) || T.isArguments(u))) {
                            t || (u = L(u, t, n));
                            var c = 0,
                            l = u.length;
                            for (i.length += l; c < l;) i[o++] = u[c++]
                        } else n || (i[o++] = u)
                    }
                    return i
                };
                T.flatten = function(e, t) {
                    return L(e, t, !1)
                },
                T.without = function(e) {
                    return T.difference(e, h.call(arguments, 1))
                },
                T.uniq = T.unique = function(e, t, n, r) {
                    T.isBoolean(t) || (r = n, n = t, t = !1),
                    null != n && (n = A(n, r));
                    for (var i = [], o = [], a = 0, s = j(e); a < s; a++) {
                        var u = e[a],
                        c = n ? n(u, a, e) : u;
                        t ? (a && o === c || i.push(u), o = c) : n ? T.contains(o, c) || (o.push(c), i.push(u)) : T.contains(i, u) || i.push(u)
                    }
                    return i
                },
                T.union = function() {
                    return T.uniq(L(arguments, !0, !0))
                },
                T.intersection = function(e) {
                    for (var t = [], n = arguments.length, r = 0, i = j(e); r < i; r++) {
                        var o = e[r];
                        if (!T.contains(t, o)) {
                            for (var a = 1; a < n && T.contains(arguments[a], o); a++);
                            a === n && t.push(o)
                        }
                    }
                    return t
                },
                T.difference = function(e) {
                    var t = L(arguments, !0, !0, 1);
                    return T.filter(e,
                    function(e) {
                        return ! T.contains(t, e)
                    })
                },
                T.zip = function() {
                    return T.unzip(arguments)
                },
                T.unzip = function(e) {
                    for (var t = e && T.max(e, j).length || 0, n = Array(t), r = 0; r < t; r++) n[r] = T.pluck(e, r);
                    return n
                },
                T.object = function(e, t) {
                    for (var n = {},
                    r = 0,
                    i = j(e); r < i; r++) t ? n[e[r]] = t[r] : n[e[r][0]] = e[r][1];
                    return n
                },
                T.findIndex = o(1),
                T.findLastIndex = o( - 1),
                T.sortedIndex = function(e, t, n, r) {
                    n = A(n, r, 1);
                    for (var i = n(t), o = 0, a = j(e); o < a;) {
                        var s = Math.floor((o + a) / 2);
                        n(e[s]) < i ? o = s + 1 : a = s
                    }
                    return o
                },
                T.indexOf = a(1, T.findIndex, T.sortedIndex),
                T.lastIndexOf = a( - 1, T.findLastIndex),
                T.range = function(e, t, n) {
                    null == t && (t = e || 0, e = 0),
                    n = n || 1;
                    for (var r = Math.max(Math.ceil((t - e) / n), 0), i = Array(r), o = 0; o < r; o++, e += n) i[o] = e;
                    return i
                };
                var O = function(e, t, n, r, i) {
                    if (! (r instanceof t)) return e.apply(n, i);
                    var o = E(e.prototype),
                    a = e.apply(o, i);
                    return T.isObject(a) ? a: o
                };
                T.bind = function(e, t) {
                    if (b && e.bind === b) return b.apply(e, h.call(arguments, 1));
                    if (!T.isFunction(e)) throw new TypeError("Bind must be called on a function");
                    var n = h.call(arguments, 2),
                    r = function() {
                        return O(e, r, t, this, n.concat(h.call(arguments)))
                    };
                    return r
                },
                T.partial = function(e) {
                    var t = h.call(arguments, 1),
                    n = function() {
                        for (var r = 0,
                        i = t.length,
                        o = Array(i), a = 0; a < i; a++) o[a] = t[a] === T ? arguments[r++] : t[a];
                        for (; r < arguments.length;) o.push(arguments[r++]);
                        return O(e, n, this, this, o)
                    };
                    return n
                },
                T.bindAll = function(e) {
                    var t, n, r = arguments.length;
                    if (r <= 1) throw new Error("bindAll must be passed function names");
                    for (t = 1; t < r; t++) n = arguments[t],
                    e[n] = T.bind(e[n], e);
                    return e
                },
                T.memoize = function(e, t) {
                    var n = function(r) {
                        var i = n.cache,
                        o = "" + (t ? t.apply(this, arguments) : r);
                        return T.has(i, o) || (i[o] = e.apply(this, arguments)),
                        i[o]
                    };
                    return n.cache = {},
                    n
                },
                T.delay = function(e, t) {
                    var n = h.call(arguments, 2);
                    return setTimeout(function() {
                        return e.apply(null, n)
                    },
                    t)
                },
                T.defer = T.partial(T.delay, T, 1),
                T.throttle = function(e, t, n) {
                    var r, i, o, a = null,
                    s = 0;
                    n || (n = {});
                    var u = function() {
                        s = n.leading === !1 ? 0 : T.now(),
                        a = null,
                        o = e.apply(r, i),
                        a || (r = i = null)
                    };
                    return function() {
                        var c = T.now();
                        s || n.leading !== !1 || (s = c);
                        var l = t - (c - s);
                        return r = this,
                        i = arguments,
                        l <= 0 || l > t ? (a && (clearTimeout(a), a = null), s = c, o = e.apply(r, i), a || (r = i = null)) : a || n.trailing === !1 || (a = setTimeout(u, l)),
                        o
                    }
                },
                T.debounce = function(e, t, n) {
                    var r, i, o, a, s, u = function() {
                        var c = T.now() - a;
                        c < t && c >= 0 ? r = setTimeout(u, t - c) : (r = null, n || (s = e.apply(o, i), r || (o = i = null)))
                    };
                    return function() {
                        o = this,
                        i = arguments,
                        a = T.now();
                        var c = n && !r;
                        return r || (r = setTimeout(u, t)),
                        c && (s = e.apply(o, i), o = i = null),
                        s
                    }
                },
                T.wrap = function(e, t) {
                    return T.partial(t, e)
                },
                T.negate = function(e) {
                    return function() {
                        return ! e.apply(this, arguments)
                    }
                },
                T.compose = function() {
                    var e = arguments,
                    t = e.length - 1;
                    return function() {
                        for (var n = t,
                        r = e[t].apply(this, arguments); n--;) r = e[n].call(this, r);
                        return r
                    }
                },
                T.after = function(e, t) {
                    return function() {
                        if (--e < 1) return t.apply(this, arguments)
                    }
                },
                T.before = function(e, t) {
                    var n;
                    return function() {
                        return--e > 0 && (n = t.apply(this, arguments)),
                        e <= 1 && (t = null),
                        n
                    }
                },
                T.once = T.partial(T.before, 2);
                var M = !{
                    toString: null
                }.propertyIsEnumerable("toString"),
                R = ["valueOf", "isPrototypeOf", "toString", "propertyIsEnumerable", "hasOwnProperty", "toLocaleString"];
                T.keys = function(e) {
                    if (!T.isObject(e)) return [];
                    if (y) return y(e);
                    var t = [];
                    for (var n in e) T.has(e, n) && t.push(n);
                    return M && s(e, t),
                    t
                },
                T.allKeys = function(e) {
                    if (!T.isObject(e)) return [];
                    var t = [];
                    for (var n in e) t.push(n);
                    return M && s(e, t),
                    t
                },
                T.values = function(e) {
                    for (var t = T.keys(e), n = t.length, r = Array(n), i = 0; i < n; i++) r[i] = e[t[i]];
                    return r
                },
                T.mapObject = function(e, t, n) {
                    t = A(t, n);
                    for (var r, i = T.keys(e), o = i.length, a = {},
                    s = 0; s < o; s++) r = i[s],
                    a[r] = t(e[r], r, e);
                    return a
                },
                T.pairs = function(e) {
                    for (var t = T.keys(e), n = t.length, r = Array(n), i = 0; i < n; i++) r[i] = [t[i], e[t[i]]];
                    return r
                },
                T.invert = function(e) {
                    for (var t = {},
                    n = T.keys(e), r = 0, i = n.length; r < i; r++) t[e[n[r]]] = n[r];
                    return t
                },
                T.functions = T.methods = function(e) {
                    var t = [];
                    for (var n in e) T.isFunction(e[n]) && t.push(n);
                    return t.sort()
                },
                T.extend = k(T.allKeys),
                T.extendOwn = T.assign = k(T.keys),
                T.findKey = function(e, t, n) {
                    t = A(t, n);
                    for (var r, i = T.keys(e), o = 0, a = i.length; o < a; o++) if (r = i[o], t(e[r], r, e)) return r
                },
                T.pick = function(e, t, n) {
                    var r, i, o = {},
                    a = e;
                    if (null == a) return o;
                    T.isFunction(t) ? (i = T.allKeys(a), r = C(t, n)) : (i = L(arguments, !1, !1, 1), r = function(e, t, n) {
                        return t in n
                    },
                    a = Object(a));
                    for (var s = 0,
                    u = i.length; s < u; s++) {
                        var c = i[s],
                        l = a[c];
                        r(l, c, a) && (o[c] = l)
                    }
                    return o
                },
                T.omit = function(e, t, n) {
                    if (T.isFunction(t)) t = T.negate(t);
                    else {
                        var r = T.map(L(arguments, !1, !1, 1), String);
                        t = function(e, t) {
                            return ! T.contains(r, t)
                        }
                    }
                    return T.pick(e, t, n)
                },
                T.defaults = k(T.allKeys, !0),
                T.create = function(e, t) {
                    var n = E(e);
                    return t && T.extendOwn(n, t),
                    n
                },
                T.clone = function(e) {
                    return T.isObject(e) ? T.isArray(e) ? e.slice() : T.extend({},
                    e) : e
                },
                T.tap = function(e, t) {
                    return t(e),
                    e
                },
                T.isMatch = function(e, t) {
                    var n = T.keys(t),
                    r = n.length;
                    if (null == e) return ! r;
                    for (var i = Object(e), o = 0; o < r; o++) {
                        var a = n[o];
                        if (t[a] !== i[a] || !(a in i)) return ! 1
                    }
                    return ! 0
                };
                var H = function(e, t, n, r) {
                    if (e === t) return 0 !== e || 1 / e === 1 / t;
                    if (null == e || null == t) return e === t;
                    e instanceof T && (e = e._wrapped),
                    t instanceof T && (t = t._wrapped);
                    var i = v.call(e);
                    if (i !== v.call(t)) return ! 1;
                    switch (i) {
                    case "[object RegExp]":
                    case "[object String]":
                        return "" + e == "" + t;
                    case "[object Number]":
                        return + e !== +e ? +t !== +t: 0 === +e ? 1 / +e === 1 / t: +e === +t;
                    case "[object Date]":
                    case "[object Boolean]":
                        return + e === +t
                    }
                    var o = "[object Array]" === i;
                    if (!o) {
                        if ("object" != typeof e || "object" != typeof t) return ! 1;
                        var a = e.constructor,
                        s = t.constructor;
                        if (a !== s && !(T.isFunction(a) && a instanceof a && T.isFunction(s) && s instanceof s) && "constructor" in e && "constructor" in t) return ! 1
                    }
                    n = n || [],
                    r = r || [];
                    for (var u = n.length; u--;) if (n[u] === e) return r[u] === t;
                    if (n.push(e), r.push(t), o) {
                        if (u = e.length, u !== t.length) return ! 1;
                        for (; u--;) if (!H(e[u], t[u], n, r)) return ! 1
                    } else {
                        var c, l = T.keys(e);
                        if (u = l.length, T.keys(t).length !== u) return ! 1;
                        for (; u--;) if (c = l[u], !T.has(t, c) || !H(e[c], t[c], n, r)) return ! 1
                    }
                    return n.pop(),
                    r.pop(),
                    !0
                };
                T.isEqual = function(e, t) {
                    return H(e, t)
                },
                T.isEmpty = function(e) {
                    return null == e || (_(e) && (T.isArray(e) || T.isString(e) || T.isArguments(e)) ? 0 === e.length: 0 === T.keys(e).length)
                },
                T.isElement = function(e) {
                    return ! (!e || 1 !== e.nodeType)
                },
                T.isArray = m ||
                function(e) {
                    return "[object Array]" === v.call(e)
                },
                T.isObject = function(e) {
                    var t = typeof e;
                    return "function" === t || "object" === t && !!e
                },
                T.each(["Arguments", "Function", "String", "Number", "Date", "RegExp", "Error"],
                function(e) {
                    T["is" + e] = function(t) {
                        return v.call(t) === "[object " + e + "]"
                    }
                }),
                T.isArguments(arguments) || (T.isArguments = function(e) {
                    return T.has(e, "callee")
                }),
                "function" != typeof / . / &&"object" != typeof Int8Array && (T.isFunction = function(e) {
                    return "function" == typeof e || !1
                }),
                T.isFinite = function(e) {
                    return isFinite(e) && !isNaN(parseFloat(e))
                },
                T.isNaN = function(e) {
                    return T.isNumber(e) && e !== +e
                },
                T.isBoolean = function(e) {
                    return e === !0 || e === !1 || "[object Boolean]" === v.call(e)
                },
                T.isNull = function(e) {
                    return null === e
                },
                T.isUndefined = function(e) {
                    return void 0 === e
                },
                T.has = function(e, t) {
                    return null != e && g.call(e, t)
                },
                T.noConflict = function() {
                    return u._ = c,
                    this
                },
                T.identity = function(e) {
                    return e
                },
                T.constant = function(e) {
                    return function() {
                        return e
                    }
                },
                T.noop = function() {},
                T.property = N,
                T.propertyOf = function(e) {
                    return null == e ?
                    function() {}: function(t) {
                        return e[t]
                    }
                },
                T.matcher = T.matches = function(e) {
                    return e = T.extendOwn({},
                    e),
                    function(t) {
                        return T.isMatch(t, e)
                    }
                },
                T.times = function(e, t, n) {
                    var r = Array(Math.max(0, e));
                    t = C(t, n, 1);
                    for (var i = 0; i < e; i++) r[i] = t(i);
                    return r
                },
                T.random = function(e, t) {
                    return null == t && (t = e, e = 0),
                    e + Math.floor(Math.random() * (t - e + 1))
                },
                T.now = Date.now ||
                function() {
                    return (new Date).getTime()
                };
                var F = {
                    "&": "&amp;",
                    "<": "&lt;",
                    ">": "&gt;",
                    '"': "&quot;",
                    "'": "&#x27;",
                    "`": "&#x60;"
                },
                I = T.invert(F),
                q = function(e) {
                    var t = function(t) {
                        return e[t]
                    },
                    n = "(?:" + T.keys(e).join("|") + ")",
                    r = RegExp(n),
                    i = RegExp(n, "g");
                    return function(e) {
                        return e = null == e ? "": "" + e,
                        r.test(e) ? e.replace(i, t) : e
                    }
                };
                T.escape = q(F),
                T.unescape = q(I),
                T.result = function(e, t, n) {
                    var r = null == e ? void 0 : e[t];
                    return void 0 === r && (r = n),
                    T.isFunction(r) ? r.call(e) : r
                };
                var P = 0;
                T.uniqueId = function(e) {
                    var t = ++P + "";
                    return e ? e + t: t
                },
                T.templateSettings = {
                    evaluate: /<%([\s\S]+?)%>/g,
                    interpolate: /<%=([\s\S]+?)%>/g,
                    escape: /<%-([\s\S]+?)%>/g
                };
                var B = /(.)^/,
                W = {
                    "'": "'",
                    "\\": "\\",
                    "\r": "r",
                    "\n": "n",
                    "\u2028": "u2028",
                    "\u2029": "u2029"
                },
                z = /\\|'|\r|\n|\u2028|\u2029/g,
                $ = function(e) {
                    return "\\" + W[e]
                };
                T.template = function(e, t, n) { ! t && n && (t = n),
                    t = T.defaults({},
                    t, T.templateSettings);
                    var r = RegExp([(t.escape || B).source, (t.interpolate || B).source, (t.evaluate || B).source].join("|") + "|$", "g"),
                    i = 0,
                    o = "__p+='";
                    e.replace(r,
                    function(t, n, r, a, s) {
                        return o += e.slice(i, s).replace(z, $),
                        i = s + t.length,
                        n ? o += "'+\n((__t=(" + n + "))==null?'':_.escape(__t))+\n'": r ? o += "'+\n((__t=(" + r + "))==null?'':__t)+\n'": a && (o += "';\n" + a + "\n__p+='"),
                        t
                    }),
                    o += "';\n",
                    t.variable || (o = "with(obj||{}){\n" + o + "}\n"),
                    o = "var __t,__p='',__j=Array.prototype.join,print=function(){__p+=__j.call(arguments,'');};\n" + o + "return __p;\n";
                    try {
                        var a = new Function(t.variable || "obj", "_", o)
                    } catch(e) {
                        throw e.source = o,
                        e
                    }
                    var s = function(e) {
                        return a.call(this, e, T)
                    },
                    u = t.variable || "obj";
                    return s.source = "function(" + u + "){\n" + o + "}",
                    s
                },
                T.chain = function(e) {
                    var t = T(e);
                    return t._chain = !0,
                    t
                };
                var U = function(e, t) {
                    return e._chain ? T(t).chain() : t
                };
                T.mixin = function(e) {
                    T.each(T.functions(e),
                    function(t) {
                        var n = T[t] = e[t];
                        T.prototype[t] = function() {
                            var e = [this._wrapped];
                            return p.apply(e, arguments),
                            U(this, n.apply(T, e))
                        }
                    })
                },
                T.mixin(T),
                T.each(["pop", "push", "reverse", "shift", "sort", "splice", "unshift"],
                function(e) {
                    var t = l[e];
                    T.prototype[e] = function() {
                        var n = this._wrapped;
                        return t.apply(n, arguments),
                        "shift" !== e && "splice" !== e || 0 !== n.length || delete n[0],
                        U(this, n)
                    }
                }),
                T.each(["concat", "join", "slice"],
                function(e) {
                    var t = l[e];
                    T.prototype[e] = function() {
                        return U(this, t.apply(this._wrapped, arguments))
                    }
                }),
                T.prototype.value = function() {
                    return this._wrapped
                },
                T.prototype.valueOf = T.prototype.toJSON = T.prototype.value,
                T.prototype.toString = function() {
                    return "" + this._wrapped
                },
                r = [],
                i = function() {
                    return T
                }.apply(t, r),
                !(void 0 !== i && (e.exports = i))
            }).call(this)
        },
        uUzA: function(e, t, n) {
            var r, i; !
            function(t, n) {
                "object" == typeof e && "object" == typeof e.exports ? e.exports = t.document ? n(t, !0) : function(e) {
                    if (!e.document) throw new Error("jQuery requires a window with a document");
                    return n(e)
                }: n(t)
            } ("undefined" != typeof window ? window: this,
            function(n, o) {
                function a(e) {
                    var t = !!e && "length" in e && e.length,
                    n = me.type(e);
                    return "function" !== n && !me.isWindow(e) && ("array" === n || 0 === t || "number" == typeof t && t > 0 && t - 1 in e)
                }
                function s(e, t, n) {
                    if (me.isFunction(t)) return me.grep(e,
                    function(e, r) {
                        return !! t.call(e, r, e) !== n
                    });
                    if (t.nodeType) return me.grep(e,
                    function(e) {
                        return e === t !== n
                    });
                    if ("string" == typeof t) {
                        if (Ne.test(t)) return me.filter(t, e, n);
                        t = me.filter(t, e)
                    }
                    return me.grep(e,
                    function(e) {
                        return me.inArray(e, t) > -1 !== n
                    })
                }
                function u(e, t) {
                    do e = e[t];
                    while (e && 1 !== e.nodeType);
                    return e
                }
                function c(e) {
                    var t = {};
                    return me.each(e.match(Oe) || [],
                    function(e, n) {
                        t[n] = !0
                    }),
                    t
                }
                function l() {
                    se.addEventListener ? (se.removeEventListener("DOMContentLoaded", f), n.removeEventListener("load", f)) : (se.detachEvent("onreadystatechange", f), n.detachEvent("onload", f))
                }
                function f() { (se.addEventListener || "load" === n.event.type || "complete" === se.readyState) && (l(), me.ready())
                }
                function d(e, t, n) {
                    if (void 0 === n && 1 === e.nodeType) {
                        var r = "data-" + t.replace(Ie, "-$1").toLowerCase();
                        if (n = e.getAttribute(r), "string" == typeof n) {
                            try {
                                n = "true" === n || "false" !== n && ("null" === n ? null: +n + "" === n ? +n: Fe.test(n) ? me.parseJSON(n) : n)
                            } catch(e) {}
                            me.data(e, t, n)
                        } else n = void 0
                    }
                    return n
                }
                function p(e) {
                    var t;
                    for (t in e) if (("data" !== t || !me.isEmptyObject(e[t])) && "toJSON" !== t) return ! 1;
                    return ! 0
                }
                function h(e, t, n, r) {
                    if (He(e)) {
                        var i, o, a = me.expando,
                        s = e.nodeType,
                        u = s ? me.cache: e,
                        c = s ? e[a] : e[a] && a;
                        if (c && u[c] && (r || u[c].data) || void 0 !== n || "string" != typeof t) return c || (c = s ? e[a] = ae.pop() || me.guid++:a),
                        u[c] || (u[c] = s ? {}: {
                            toJSON: me.noop
                        }),
                        "object" != typeof t && "function" != typeof t || (r ? u[c] = me.extend(u[c], t) : u[c].data = me.extend(u[c].data, t)),
                        o = u[c],
                        r || (o.data || (o.data = {}), o = o.data),
                        void 0 !== n && (o[me.camelCase(t)] = n),
                        "string" == typeof t ? (i = o[t], null == i && (i = o[me.camelCase(t)])) : i = o,
                        i
                    }
                }
                function v(e, t, n) {
                    if (He(e)) {
                        var r, i, o = e.nodeType,
                        a = o ? me.cache: e,
                        s = o ? e[me.expando] : me.expando;
                        if (a[s]) {
                            if (t && (r = n ? a[s] : a[s].data)) {
                                me.isArray(t) ? t = t.concat(me.map(t, me.camelCase)) : t in r ? t = [t] : (t = me.camelCase(t), t = t in r ? [t] : t.split(" ")),
                                i = t.length;
                                for (; i--;) delete r[t[i]];
                                if (n ? !p(r) : !me.isEmptyObject(r)) return
                            } (n || (delete a[s].data, p(a[s]))) && (o ? me.cleanData([e], !0) : ve.deleteExpando || a != a.window ? delete a[s] : a[s] = void 0)
                        }
                    }
                }
                function g(e, t, n, r) {
                    var i, o = 1,
                    a = 20,
                    s = r ?
                    function() {
                        return r.cur()
                    }: function() {
                        return me.css(e, t, "")
                    },
                    u = s(),
                    c = n && n[3] || (me.cssNumber[t] ? "": "px"),
                    l = (me.cssNumber[t] || "px" !== c && +u) && Pe.exec(me.css(e, t));
                    if (l && l[3] !== c) {
                        c = c || l[3],
                        n = n || [],
                        l = +u || 1;
                        do o = o || ".5",
                        l /= o,
                        me.style(e, t, l + c);
                        while (o !== (o = s() / u) && 1 !== o && --a)
                    }
                    return n && (l = +l || +u || 0, i = n[1] ? l + (n[1] + 1) * n[2] : +n[2], r && (r.unit = c, r.start = l, r.end = i)),
                    i
                }
                function m(e) {
                    var t = Xe.split("|"),
                    n = e.createDocumentFragment();
                    if (n.createElement) for (; t.length;) n.createElement(t.pop());
                    return n
                }
                function y(e, t) {
                    var n, r, i = 0,
                    o = "undefined" != typeof e.getElementsByTagName ? e.getElementsByTagName(t || "*") : "undefined" != typeof e.querySelectorAll ? e.querySelectorAll(t || "*") : void 0;
                    if (!o) for (o = [], n = e.childNodes || e; null != (r = n[i]); i++) ! t || me.nodeName(r, t) ? o.push(r) : me.merge(o, y(r, t));
                    return void 0 === t || t && me.nodeName(e, t) ? me.merge([e], o) : o
                }
                function b(e, t) {
                    for (var n, r = 0; null != (n = e[r]); r++) me._data(n, "globalEval", !t || me._data(t[r], "globalEval"))
                }
                function x(e) {
                    $e.test(e.type) && (e.defaultChecked = e.checked)
                }
                function w(e, t, n, r, i) {
                    for (var o, a, s, u, c, l, f, d = e.length,
                    p = m(t), h = [], v = 0; v < d; v++) if (a = e[v], a || 0 === a) if ("object" === me.type(a)) me.merge(h, a.nodeType ? [a] : a);
                    else if (Ze.test(a)) {
                        for (u = u || p.appendChild(t.createElement("div")), c = (Ue.exec(a) || ["", ""])[1].toLowerCase(), f = Je[c] || Je._default, u.innerHTML = f[1] + me.htmlPrefilter(a) + f[2], o = f[0]; o--;) u = u.lastChild;
                        if (!ve.leadingWhitespace && Ye.test(a) && h.push(t.createTextNode(Ye.exec(a)[0])), !ve.tbody) for (a = "table" !== c || Ve.test(a) ? "<table>" !== f[1] || Ve.test(a) ? 0 : u: u.firstChild, o = a && a.childNodes.length; o--;) me.nodeName(l = a.childNodes[o], "tbody") && !l.childNodes.length && a.removeChild(l);
                        for (me.merge(h, u.childNodes), u.textContent = ""; u.firstChild;) u.removeChild(u.firstChild);
                        u = p.lastChild
                    } else h.push(t.createTextNode(a));
                    for (u && p.removeChild(u), ve.appendChecked || me.grep(y(h, "input"), x), v = 0; a = h[v++];) if (r && me.inArray(a, r) > -1) i && i.push(a);
                    else if (s = me.contains(a.ownerDocument, a), u = y(p.appendChild(a), "script"), s && b(u), n) for (o = 0; a = u[o++];) Ge.test(a.type || "") && n.push(a);
                    return u = null,
                    p
                }
                function T() {
                    return ! 0
                }
                function C() {
                    return ! 1
                }
                function A() {
                    try {
                        return se.activeElement
                    } catch(e) {}
                }
                function k(e, t, n, r, i, o) {
                    var a, s;
                    if ("object" == typeof t) {
                        "string" != typeof n && (r = r || n, n = void 0);
                        for (s in t) k(e, s, n, r, t[s], o);
                        return e
                    }
                    if (null == r && null == i ? (i = n, r = n = void 0) : null == i && ("string" == typeof n ? (i = r, r = void 0) : (i = r, r = n, n = void 0)), i === !1) i = C;
                    else if (!i) return e;
                    return 1 === o && (a = i, i = function(e) {
                        return me().off(e),
                        a.apply(this, arguments)
                    },
                    i.guid = a.guid || (a.guid = me.guid++)),
                    e.each(function() {
                        me.event.add(this, t, i, r, n)
                    })
                }
                function E(e, t) {
                    return me.nodeName(e, "table") && me.nodeName(11 !== t.nodeType ? t: t.firstChild, "tr") ? e.getElementsByTagName("tbody")[0] || e.appendChild(e.ownerDocument.createElement("tbody")) : e
                }
                function N(e) {
                    return e.type = (null !== me.find.attr(e, "type")) + "/" + e.type,
                    e
                }
                function S(e) {
                    var t = ut.exec(e.type);
                    return t ? e.type = t[1] : e.removeAttribute("type"),
                    e
                }
                function j(e, t) {
                    if (1 === t.nodeType && me.hasData(e)) {
                        var n, r, i, o = me._data(e),
                        a = me._data(t, o),
                        s = o.events;
                        if (s) {
                            delete a.handle,
                            a.events = {};
                            for (n in s) for (r = 0, i = s[n].length; r < i; r++) me.event.add(t, n, s[n][r])
                        }
                        a.data && (a.data = me.extend({},
                        a.data))
                    }
                }
                function _(e, t) {
                    var n, r, i;
                    if (1 === t.nodeType) {
                        if (n = t.nodeName.toLowerCase(), !ve.noCloneEvent && t[me.expando]) {
                            i = me._data(t);
                            for (r in i.events) me.removeEvent(t, r, i.handle);
                            t.removeAttribute(me.expando)
                        }
                        "script" === n && t.text !== e.text ? (N(t).text = e.text, S(t)) : "object" === n ? (t.parentNode && (t.outerHTML = e.outerHTML), ve.html5Clone && e.innerHTML && !me.trim(t.innerHTML) && (t.innerHTML = e.innerHTML)) : "input" === n && $e.test(e.type) ? (t.defaultChecked = t.checked = e.checked, t.value !== e.value && (t.value = e.value)) : "option" === n ? t.defaultSelected = t.selected = e.defaultSelected: "input" !== n && "textarea" !== n || (t.defaultValue = e.defaultValue)
                    }
                }
                function D(e, t, n, r) {
                    t = ce.apply([], t);
                    var i, o, a, s, u, c, l = 0,
                    f = e.length,
                    d = f - 1,
                    p = t[0],
                    h = me.isFunction(p);
                    if (h || f > 1 && "string" == typeof p && !ve.checkClone && st.test(p)) return e.each(function(i) {
                        var o = e.eq(i);
                        h && (t[0] = p.call(this, i, o.html())),
                        D(o, t, n, r)
                    });
                    if (f && (c = w(t, e[0].ownerDocument, !1, e, r), i = c.firstChild, 1 === c.childNodes.length && (c = i), i || r)) {
                        for (s = me.map(y(c, "script"), N), a = s.length; l < f; l++) o = c,
                        l !== d && (o = me.clone(o, !0, !0), a && me.merge(s, y(o, "script"))),
                        n.call(e[l], o, l);
                        if (a) for (u = s[s.length - 1].ownerDocument, me.map(s, S), l = 0; l < a; l++) o = s[l],
                        Ge.test(o.type || "") && !me._data(o, "globalEval") && me.contains(u, o) && (o.src ? me._evalUrl && me._evalUrl(o.src) : me.globalEval((o.text || o.textContent || o.innerHTML || "").replace(ct, "")));
                        c = i = null
                    }
                    return e
                }
                function L(e, t, n) {
                    for (var r, i = t ? me.filter(t, e) : e, o = 0; null != (r = i[o]); o++) n || 1 !== r.nodeType || me.cleanData(y(r)),
                    r.parentNode && (n && me.contains(r.ownerDocument, r) && b(y(r, "script")), r.parentNode.removeChild(r));
                    return e
                }
                function O(e, t) {
                    var n = me(t.createElement(e)).appendTo(t.body),
                    r = me.css(n[0], "display");
                    return n.detach(),
                    r
                }
                function M(e) {
                    var t = se,
                    n = pt[e];
                    return n || (n = O(e, t), "none" !== n && n || (dt = (dt || me("<iframe frameborder='0' width='0' height='0'/>")).appendTo(t.documentElement), t = (dt[0].contentWindow || dt[0].contentDocument).document, t.write(), t.close(), n = O(e, t), dt.detach()), pt[e] = n),
                    n
                }
                function R(e, t) {
                    return {
                        get: function() {
                            return e() ? void delete this.get: (this.get = t).apply(this, arguments)
                        }
                    }
                }
                function H(e) {
                    if (e in St) return e;
                    for (var t = e.charAt(0).toUpperCase() + e.slice(1), n = Nt.length; n--;) if (e = Nt[n] + t, e in St) return e
                }
                function F(e, t) {
                    for (var n, r, i, o = [], a = 0, s = e.length; a < s; a++) r = e[a],
                    r.style && (o[a] = me._data(r, "olddisplay"), n = r.style.display, t ? (o[a] || "none" !== n || (r.style.display = ""), "" === r.style.display && We(r) && (o[a] = me._data(r, "olddisplay", M(r.nodeName)))) : (i = We(r), (n && "none" !== n || !i) && me._data(r, "olddisplay", i ? n: me.css(r, "display"))));
                    for (a = 0; a < s; a++) r = e[a],
                    r.style && (t && "none" !== r.style.display && "" !== r.style.display || (r.style.display = t ? o[a] || "": "none"));
                    return e
                }
                function I(e, t, n) {
                    var r = At.exec(t);
                    return r ? Math.max(0, r[1] - (n || 0)) + (r[2] || "px") : t
                }
                function q(e, t, n, r, i) {
                    for (var o = n === (r ? "border": "content") ? 4 : "width" === t ? 1 : 0, a = 0; o < 4; o += 2)"margin" === n && (a += me.css(e, n + Be[o], !0, i)),
                    r ? ("content" === n && (a -= me.css(e, "padding" + Be[o], !0, i)), "margin" !== n && (a -= me.css(e, "border" + Be[o] + "Width", !0, i))) : (a += me.css(e, "padding" + Be[o], !0, i), "padding" !== n && (a += me.css(e, "border" + Be[o] + "Width", !0, i)));
                    return a
                }
                function P(e, t, r) {
                    var i = !0,
                    o = "width" === t ? e.offsetWidth: e.offsetHeight,
                    a = yt(e),
                    s = ve.boxSizing && "border-box" === me.css(e, "boxSizing", !1, a);
                    if (se.msFullscreenElement && n.top !== n && e.getClientRects().length && (o = Math.round(100 * e.getBoundingClientRect()[t])), o <= 0 || null == o) {
                        if (o = bt(e, t, a), (o < 0 || null == o) && (o = e.style[t]), vt.test(o)) return o;
                        i = s && (ve.boxSizingReliable() || o === e.style[t]),
                        o = parseFloat(o) || 0
                    }
                    return o + q(e, t, r || (s ? "border": "content"), i, a) + "px"
                }
                function B(e, t, n, r, i) {
                    return new B.prototype.init(e, t, n, r, i)
                }
                function W() {
                    return n.setTimeout(function() {
                        jt = void 0
                    }),
                    jt = me.now()
                }
                function z(e, t) {
                    var n, r = {
                        height: e
                    },
                    i = 0;
                    for (t = t ? 1 : 0; i < 4; i += 2 - t) n = Be[i],
                    r["margin" + n] = r["padding" + n] = e;
                    return t && (r.opacity = r.width = e),
                    r
                }
                function $(e, t, n) {
                    for (var r, i = (Y.tweeners[t] || []).concat(Y.tweeners["*"]), o = 0, a = i.length; o < a; o++) if (r = i[o].call(n, t, e)) return r
                }
                function U(e, t, n) {
                    var r, i, o, a, s, u, c, l, f = this,
                    d = {},
                    p = e.style,
                    h = e.nodeType && We(e),
                    v = me._data(e, "fxshow");
                    n.queue || (s = me._queueHooks(e, "fx"), null == s.unqueued && (s.unqueued = 0, u = s.empty.fire, s.empty.fire = function() {
                        s.unqueued || u()
                    }), s.unqueued++, f.always(function() {
                        f.always(function() {
                            s.unqueued--,
                            me.queue(e, "fx").length || s.empty.fire()
                        })
                    })),
                    1 === e.nodeType && ("height" in t || "width" in t) && (n.overflow = [p.overflow, p.overflowX, p.overflowY], c = me.css(e, "display"), l = "none" === c ? me._data(e, "olddisplay") || M(e.nodeName) : c, "inline" === l && "none" === me.css(e, "float") && (ve.inlineBlockNeedsLayout && "inline" !== M(e.nodeName) ? p.zoom = 1 : p.display = "inline-block")),
                    n.overflow && (p.overflow = "hidden", ve.shrinkWrapBlocks() || f.always(function() {
                        p.overflow = n.overflow[0],
                        p.overflowX = n.overflow[1],
                        p.overflowY = n.overflow[2]
                    }));
                    for (r in t) if (i = t[r], Dt.exec(i)) {
                        if (delete t[r], o = o || "toggle" === i, i === (h ? "hide": "show")) {
                            if ("show" !== i || !v || void 0 === v[r]) continue;
                            h = !0
                        }
                        d[r] = v && v[r] || me.style(e, r)
                    } else c = void 0;
                    if (me.isEmptyObject(d))"inline" === ("none" === c ? M(e.nodeName) : c) && (p.display = c);
                    else {
                        v ? "hidden" in v && (h = v.hidden) : v = me._data(e, "fxshow", {}),
                        o && (v.hidden = !h),
                        h ? me(e).show() : f.done(function() {
                            me(e).hide()
                        }),
                        f.done(function() {
                            var t;
                            me._removeData(e, "fxshow");
                            for (t in d) me.style(e, t, d[t])
                        });
                        for (r in d) a = $(h ? v[r] : 0, r, f),
                        r in v || (v[r] = a.start, h && (a.end = a.start, a.start = "width" === r || "height" === r ? 1 : 0))
                    }
                }
                function G(e, t) {
                    var n, r, i, o, a;
                    for (n in e) if (r = me.camelCase(n), i = t[r], o = e[n], me.isArray(o) && (i = o[1], o = e[n] = o[0]), n !== r && (e[r] = o, delete e[n]), a = me.cssHooks[r], a && "expand" in a) {
                        o = a.expand(o),
                        delete e[r];
                        for (n in o) n in e || (e[n] = o[n], t[n] = i)
                    } else t[r] = i
                }
                function Y(e, t, n) {
                    var r, i, o = 0,
                    a = Y.prefilters.length,
                    s = me.Deferred().always(function() {
                        delete u.elem
                    }),
                    u = function() {
                        if (i) return ! 1;
                        for (var t = jt || W(), n = Math.max(0, c.startTime + c.duration - t), r = n / c.duration || 0, o = 1 - r, a = 0, u = c.tweens.length; a < u; a++) c.tweens[a].run(o);
                        return s.notifyWith(e, [c, o, n]),
                        o < 1 && u ? n: (s.resolveWith(e, [c]), !1)
                    },
                    c = s.promise({
                        elem: e,
                        props: me.extend({},
                        t),
                        opts: me.extend(!0, {
                            specialEasing: {},
                            easing: me.easing._default
                        },
                        n),
                        originalProperties: t,
                        originalOptions: n,
                        startTime: jt || W(),
                        duration: n.duration,
                        tweens: [],
                        createTween: function(t, n) {
                            var r = me.Tween(e, c.opts, t, n, c.opts.specialEasing[t] || c.opts.easing);
                            return c.tweens.push(r),
                            r
                        },
                        stop: function(t) {
                            var n = 0,
                            r = t ? c.tweens.length: 0;
                            if (i) return this;
                            for (i = !0; n < r; n++) c.tweens[n].run(1);
                            return t ? (s.notifyWith(e, [c, 1, 0]), s.resolveWith(e, [c, t])) : s.rejectWith(e, [c, t]),
                            this
                        }
                    }),
                    l = c.props;
                    for (G(l, c.opts.specialEasing); o < a; o++) if (r = Y.prefilters[o].call(c, e, l, c.opts)) return me.isFunction(r.stop) && (me._queueHooks(c.elem, c.opts.queue).stop = me.proxy(r.stop, r)),
                    r;
                    return me.map(l, $, c),
                    me.isFunction(c.opts.start) && c.opts.start.call(e, c),
                    me.fx.timer(me.extend(u, {
                        elem: e,
                        anim: c,
                        queue: c.opts.queue
                    })),
                    c.progress(c.opts.progress).done(c.opts.done, c.opts.complete).fail(c.opts.fail).always(c.opts.always)
                }
                function X(e) {
                    return me.attr(e, "class") || ""
                }
                function J(e) {
                    return function(t, n) {
                        "string" != typeof t && (n = t, t = "*");
                        var r, i = 0,
                        o = t.toLowerCase().match(Oe) || [];
                        if (me.isFunction(n)) for (; r = o[i++];)"+" === r.charAt(0) ? (r = r.slice(1) || "*", (e[r] = e[r] || []).unshift(n)) : (e[r] = e[r] || []).push(n)
                    }
                }
                function Z(e, t, n, r) {
                    function i(s) {
                        var u;
                        return o[s] = !0,
                        me.each(e[s] || [],
                        function(e, s) {
                            var c = s(t, n, r);
                            return "string" != typeof c || a || o[c] ? a ? !(u = c) : void 0 : (t.dataTypes.unshift(c), i(c), !1)
                        }),
                        u
                    }
                    var o = {},
                    a = e === nn;
                    return i(t.dataTypes[0]) || !o["*"] && i("*")
                }
                function V(e, t) {
                    var n, r, i = me.ajaxSettings.flatOptions || {};
                    for (r in t) void 0 !== t[r] && ((i[r] ? e: n || (n = {}))[r] = t[r]);
                    return n && me.extend(!0, e, n),
                    e
                }
                function Q(e, t, n) {
                    for (var r, i, o, a, s = e.contents,
                    u = e.dataTypes;
                    "*" === u[0];) u.shift(),
                    void 0 === i && (i = e.mimeType || t.getResponseHeader("Content-Type"));
                    if (i) for (a in s) if (s[a] && s[a].test(i)) {
                        u.unshift(a);
                        break
                    }
                    if (u[0] in n) o = u[0];
                    else {
                        for (a in n) {
                            if (!u[0] || e.converters[a + " " + u[0]]) {
                                o = a;
                                break
                            }
                            r || (r = a)
                        }
                        o = o || r
                    }
                    if (o) return o !== u[0] && u.unshift(o),
                    n[o]
                }
                function K(e, t, n, r) {
                    var i, o, a, s, u, c = {},
                    l = e.dataTypes.slice();
                    if (l[1]) for (a in e.converters) c[a.toLowerCase()] = e.converters[a];
                    for (o = l.shift(); o;) if (e.responseFields[o] && (n[e.responseFields[o]] = t), !u && r && e.dataFilter && (t = e.dataFilter(t, e.dataType)), u = o, o = l.shift()) if ("*" === o) o = u;
                    else if ("*" !== u && u !== o) {
                        if (a = c[u + " " + o] || c["* " + o], !a) for (i in c) if (s = i.split(" "), s[1] === o && (a = c[u + " " + s[0]] || c["* " + s[0]])) {
                            a === !0 ? a = c[i] : c[i] !== !0 && (o = s[0], l.unshift(s[1]));
                            break
                        }
                        if (a !== !0) if (a && e.throws) t = a(t);
                        else try {
                            t = a(t)
                        } catch(e) {
                            return {
                                state: "parsererror",
                                error: a ? e: "No conversion from " + u + " to " + o
                            }
                        }
                    }
                    return {
                        state: "success",
                        data: t
                    }
                }
                function ee(e) {
                    return e.style && e.style.display || me.css(e, "display")
                }
                function te(e) {
                    for (; e && 1 === e.nodeType;) {
                        if ("none" === ee(e) || "hidden" === e.type) return ! 0;
                        e = e.parentNode
                    }
                    return ! 1
                }
                function ne(e, t, n, r) {
                    var i;
                    if (me.isArray(t)) me.each(t,
                    function(t, i) {
                        n || un.test(e) ? r(e, i) : ne(e + "[" + ("object" == typeof i && null != i ? t: "") + "]", i, n, r)
                    });
                    else if (n || "object" !== me.type(t)) r(e, t);
                    else for (i in t) ne(e + "[" + i + "]", t[i], n, r)
                }
                function re() {
                    try {
                        return new n.XMLHttpRequest
                    } catch(e) {}
                }
                function ie() {
                    try {
                        return new n.ActiveXObject("Microsoft.XMLHTTP")
                    } catch(e) {}
                }
                function oe(e) {
                    return me.isWindow(e) ? e: 9 === e.nodeType && (e.defaultView || e.parentWindow)
                }
                var ae = [],
                se = n.document,
                ue = ae.slice,
                ce = ae.concat,
                le = ae.push,
                fe = ae.indexOf,
                de = {},
                pe = de.toString,
                he = de.hasOwnProperty,
                ve = {},
                ge = "1.12.3",
                me = function(e, t) {
                    return new me.fn.init(e, t)
                },
                ye = /^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g,
                be = /^-ms-/,
                xe = /-([\da-z])/gi,
                we = function(e, t) {
                    return t.toUpperCase()
                };
                me.fn = me.prototype = {
                    jquery: ge,
                    constructor: me,
                    selector: "",
                    length: 0,
                    toArray: function() {
                        return ue.call(this)
                    },
                    get: function(e) {
                        return null != e ? e < 0 ? this[e + this.length] : this[e] : ue.call(this)
                    },
                    pushStack: function(e) {
                        var t = me.merge(this.constructor(), e);
                        return t.prevObject = this,
                        t.context = this.context,
                        t
                    },
                    each: function(e) {
                        return me.each(this, e)
                    },
                    map: function(e) {
                        return this.pushStack(me.map(this,
                        function(t, n) {
                            return e.call(t, n, t)
                        }))
                    },
                    slice: function() {
                        return this.pushStack(ue.apply(this, arguments))
                    },
                    first: function() {
                        return this.eq(0)
                    },
                    last: function() {
                        return this.eq( - 1)
                    },
                    eq: function(e) {
                        var t = this.length,
                        n = +e + (e < 0 ? t: 0);
                        return this.pushStack(n >= 0 && n < t ? [this[n]] : [])
                    },
                    end: function() {
                        return this.prevObject || this.constructor()
                    },
                    push: le,
                    sort: ae.sort,
                    splice: ae.splice
                },
                me.extend = me.fn.extend = function() {
                    var e, t, n, r, i, o, a = arguments[0] || {},
                    s = 1,
                    u = arguments.length,
                    c = !1;
                    for ("boolean" == typeof a && (c = a, a = arguments[s] || {},
                    s++), "object" == typeof a || me.isFunction(a) || (a = {}), s === u && (a = this, s--); s < u; s++) if (null != (i = arguments[s])) for (r in i) e = a[r],
                    n = i[r],
                    a !== n && (c && n && (me.isPlainObject(n) || (t = me.isArray(n))) ? (t ? (t = !1, o = e && me.isArray(e) ? e: []) : o = e && me.isPlainObject(e) ? e: {},
                    a[r] = me.extend(c, o, n)) : void 0 !== n && (a[r] = n));
                    return a
                },
                me.extend({
                    expando: "jQuery" + (ge + Math.random()).replace(/\D/g, ""),
                    isReady: !0,
                    error: function(e) {
                        throw new Error(e)
                    },
                    noop: function() {},
                    isFunction: function(e) {
                        return "function" === me.type(e)
                    },
                    isArray: Array.isArray ||
                    function(e) {
                        return "array" === me.type(e)
                    },
                    isWindow: function(e) {
                        return null != e && e == e.window
                    },
                    isNumeric: function(e) {
                        var t = e && e.toString();
                        return ! me.isArray(e) && t - parseFloat(t) + 1 >= 0
                    },
                    isEmptyObject: function(e) {
                        var t;
                        for (t in e) return ! 1;
                        return ! 0
                    },
                    isPlainObject: function(e) {
                        var t;
                        if (!e || "object" !== me.type(e) || e.nodeType || me.isWindow(e)) return ! 1;
                        try {
                            if (e.constructor && !he.call(e, "constructor") && !he.call(e.constructor.prototype, "isPrototypeOf")) return ! 1
                        } catch(e) {
                            return ! 1
                        }
                        if (!ve.ownFirst) for (t in e) return he.call(e, t);
                        for (t in e);
                        return void 0 === t || he.call(e, t)
                    },
                    type: function(e) {
                        return null == e ? e + "": "object" == typeof e || "function" == typeof e ? de[pe.call(e)] || "object": typeof e
                    },
                    globalEval: function(e) {
                        e && me.trim(e) && (n.execScript ||
                        function(e) {
                            n.eval.call(n, e)
                        })(e)
                    },
                    camelCase: function(e) {
                        return e.replace(be, "ms-").replace(xe, we)
                    },
                    nodeName: function(e, t) {
                        return e.nodeName && e.nodeName.toLowerCase() === t.toLowerCase()
                    },
                    each: function(e, t) {
                        var n, r = 0;
                        if (a(e)) for (n = e.length; r < n && t.call(e[r], r, e[r]) !== !1; r++);
                        else for (r in e) if (t.call(e[r], r, e[r]) === !1) break;
                        return e
                    },
                    trim: function(e) {
                        return null == e ? "": (e + "").replace(ye, "")
                    },
                    makeArray: function(e, t) {
                        var n = t || [];
                        return null != e && (a(Object(e)) ? me.merge(n, "string" == typeof e ? [e] : e) : le.call(n, e)),
                        n
                    },
                    inArray: function(e, t, n) {
                        var r;
                        if (t) {
                            if (fe) return fe.call(t, e, n);
                            for (r = t.length, n = n ? n < 0 ? Math.max(0, r + n) : n: 0; n < r; n++) if (n in t && t[n] === e) return n
                        }
                        return - 1
                    },
                    merge: function(e, t) {
                        for (var n = +t.length,
                        r = 0,
                        i = e.length; r < n;) e[i++] = t[r++];
                        if (n !== n) for (; void 0 !== t[r];) e[i++] = t[r++];
                        return e.length = i,
                        e
                    },
                    grep: function(e, t, n) {
                        for (var r, i = [], o = 0, a = e.length, s = !n; o < a; o++) r = !t(e[o], o),
                        r !== s && i.push(e[o]);
                        return i
                    },
                    map: function(e, t, n) {
                        var r, i, o = 0,
                        s = [];
                        if (a(e)) for (r = e.length; o < r; o++) i = t(e[o], o, n),
                        null != i && s.push(i);
                        else for (o in e) i = t(e[o], o, n),
                        null != i && s.push(i);
                        return ce.apply([], s)
                    },
                    guid: 1,
                    proxy: function(e, t) {
                        var n, r, i;
                        if ("string" == typeof t && (i = e[t], t = e, e = i), me.isFunction(e)) return n = ue.call(arguments, 2),
                        r = function() {
                            return e.apply(t || this, n.concat(ue.call(arguments)))
                        },
                        r.guid = e.guid = e.guid || me.guid++,
                        r
                    },
                    now: function() {
                        return + new Date
                    },
                    support: ve
                }),
                "function" == typeof Symbol && (me.fn[Symbol.iterator] = ae[Symbol.iterator]),
                me.each("Boolean Number String Function Array Date RegExp Object Error Symbol".split(" "),
                function(e, t) {
                    de["[object " + t + "]"] = t.toLowerCase()
                });
                var Te = function(e) {
                    function t(e, t, n, r) {
                        var i, o, a, s, u, c, f, p, h = t && t.ownerDocument,
                        v = t ? t.nodeType: 9;
                        if (n = n || [], "string" != typeof e || !e || 1 !== v && 9 !== v && 11 !== v) return n;
                        if (!r && ((t ? t.ownerDocument || t: P) !== L && D(t), t = t || L, M)) {
                            if (11 !== v && (c = me.exec(e))) if (i = c[1]) {
                                if (9 === v) {
                                    if (! (a = t.getElementById(i))) return n;
                                    if (a.id === i) return n.push(a),
                                    n
                                } else if (h && (a = h.getElementById(i)) && I(t, a) && a.id === i) return n.push(a),
                                n
                            } else {
                                if (c[2]) return Q.apply(n, t.getElementsByTagName(e)),
                                n;
                                if ((i = c[3]) && w.getElementsByClassName && t.getElementsByClassName) return Q.apply(n, t.getElementsByClassName(i)),
                                n
                            }
                            if (w.qsa && !U[e + " "] && (!R || !R.test(e))) {
                                if (1 !== v) h = t,
                                p = e;
                                else if ("object" !== t.nodeName.toLowerCase()) {
                                    for ((s = t.getAttribute("id")) ? s = s.replace(be, "\\$&") : t.setAttribute("id", s = q), f = k(e), o = f.length, u = de.test(s) ? "#" + s: "[id='" + s + "']"; o--;) f[o] = u + " " + d(f[o]);
                                    p = f.join(","),
                                    h = ye.test(e) && l(t.parentNode) || t
                                }
                                if (p) try {
                                    return Q.apply(n, h.querySelectorAll(p)),
                                    n
                                } catch(e) {} finally {
                                    s === q && t.removeAttribute("id")
                                }
                            }
                        }
                        return N(e.replace(se, "$1"), t, n, r)
                    }
                    function n() {
                        function e(n, r) {
                            return t.push(n + " ") > T.cacheLength && delete e[t.shift()],
                            e[n + " "] = r
                        }
                        var t = [];
                        return e
                    }
                    function r(e) {
                        return e[q] = !0,
                        e
                    }
                    function i(e) {
                        var t = L.createElement("div");
                        try {
                            return !! e(t)
                        } catch(e) {
                            return ! 1
                        } finally {
                            t.parentNode && t.parentNode.removeChild(t),
                            t = null
                        }
                    }
                    function o(e, t) {
                        for (var n = e.split("|"), r = n.length; r--;) T.attrHandle[n[r]] = t
                    }
                    function a(e, t) {
                        var n = t && e,
                        r = n && 1 === e.nodeType && 1 === t.nodeType && (~t.sourceIndex || Y) - (~e.sourceIndex || Y);
                        if (r) return r;
                        if (n) for (; n = n.nextSibling;) if (n === t) return - 1;
                        return e ? 1 : -1
                    }
                    function s(e) {
                        return function(t) {
                            var n = t.nodeName.toLowerCase();
                            return "input" === n && t.type === e
                        }
                    }
                    function u(e) {
                        return function(t) {
                            var n = t.nodeName.toLowerCase();
                            return ("input" === n || "button" === n) && t.type === e
                        }
                    }
                    function c(e) {
                        return r(function(t) {
                            return t = +t,
                            r(function(n, r) {
                                for (var i, o = e([], n.length, t), a = o.length; a--;) n[i = o[a]] && (n[i] = !(r[i] = n[i]))
                            })
                        })
                    }
                    function l(e) {
                        return e && "undefined" != typeof e.getElementsByTagName && e
                    }
                    function f() {}
                    function d(e) {
                        for (var t = 0,
                        n = e.length,
                        r = ""; t < n; t++) r += e[t].value;
                        return r
                    }
                    function p(e, t, n) {
                        var r = t.dir,
                        i = n && "parentNode" === r,
                        o = W++;
                        return t.first ?
                        function(t, n, o) {
                            for (; t = t[r];) if (1 === t.nodeType || i) return e(t, n, o)
                        }: function(t, n, a) {
                            var s, u, c, l = [B, o];
                            if (a) {
                                for (; t = t[r];) if ((1 === t.nodeType || i) && e(t, n, a)) return ! 0
                            } else for (; t = t[r];) if (1 === t.nodeType || i) {
                                if (c = t[q] || (t[q] = {}), u = c[t.uniqueID] || (c[t.uniqueID] = {}), (s = u[r]) && s[0] === B && s[1] === o) return l[2] = s[2];
                                if (u[r] = l, l[2] = e(t, n, a)) return ! 0
                            }
                        }
                    }
                    function h(e) {
                        return e.length > 1 ?
                        function(t, n, r) {
                            for (var i = e.length; i--;) if (!e[i](t, n, r)) return ! 1;
                            return ! 0
                        }: e[0]
                    }
                    function v(e, n, r) {
                        for (var i = 0,
                        o = n.length; i < o; i++) t(e, n[i], r);
                        return r
                    }
                    function g(e, t, n, r, i) {
                        for (var o, a = [], s = 0, u = e.length, c = null != t; s < u; s++)(o = e[s]) && (n && !n(o, r, i) || (a.push(o), c && t.push(s)));
                        return a
                    }
                    function m(e, t, n, i, o, a) {
                        return i && !i[q] && (i = m(i)),
                        o && !o[q] && (o = m(o, a)),
                        r(function(r, a, s, u) {
                            var c, l, f, d = [],
                            p = [],
                            h = a.length,
                            m = r || v(t || "*", s.nodeType ? [s] : s, []),
                            y = !e || !r && t ? m: g(m, d, e, s, u),
                            b = n ? o || (r ? e: h || i) ? [] : a: y;
                            if (n && n(y, b, s, u), i) for (c = g(b, p), i(c, [], s, u), l = c.length; l--;)(f = c[l]) && (b[p[l]] = !(y[p[l]] = f));
                            if (r) {
                                if (o || e) {
                                    if (o) {
                                        for (c = [], l = b.length; l--;)(f = b[l]) && c.push(y[l] = f);
                                        o(null, b = [], c, u)
                                    }
                                    for (l = b.length; l--;)(f = b[l]) && (c = o ? ee(r, f) : d[l]) > -1 && (r[c] = !(a[c] = f))
                                }
                            } else b = g(b === a ? b.splice(h, b.length) : b),
                            o ? o(null, a, b, u) : Q.apply(a, b)
                        })
                    }
                    function y(e) {
                        for (var t, n, r, i = e.length,
                        o = T.relative[e[0].type], a = o || T.relative[" "], s = o ? 1 : 0, u = p(function(e) {
                            return e === t
                        },
                        a, !0), c = p(function(e) {
                            return ee(t, e) > -1
                        },
                        a, !0), l = [function(e, n, r) {
                            var i = !o && (r || n !== S) || ((t = n).nodeType ? u(e, n, r) : c(e, n, r));
                            return t = null,
                            i
                        }]; s < i; s++) if (n = T.relative[e[s].type]) l = [p(h(l), n)];
                        else {
                            if (n = T.filter[e[s].type].apply(null, e[s].matches), n[q]) {
                                for (r = ++s; r < i && !T.relative[e[r].type]; r++);
                                return m(s > 1 && h(l), s > 1 && d(e.slice(0, s - 1).concat({
                                    value: " " === e[s - 2].type ? "*": ""
                                })).replace(se, "$1"), n, s < r && y(e.slice(s, r)), r < i && y(e = e.slice(r)), r < i && d(e))
                            }
                            l.push(n)
                        }
                        return h(l)
                    }
                    function b(e, n) {
                        var i = n.length > 0,
                        o = e.length > 0,
                        a = function(r, a, s, u, c) {
                            var l, f, d, p = 0,
                            h = "0",
                            v = r && [],
                            m = [],
                            y = S,
                            b = r || o && T.find.TAG("*", c),
                            x = B += null == y ? 1 : Math.random() || .1,
                            w = b.length;
                            for (c && (S = a === L || a || c); h !== w && null != (l = b[h]); h++) {
                                if (o && l) {
                                    for (f = 0, a || l.ownerDocument === L || (D(l), s = !M); d = e[f++];) if (d(l, a || L, s)) {
                                        u.push(l);
                                        break
                                    }
                                    c && (B = x)
                                }
                                i && ((l = !d && l) && p--, r && v.push(l))
                            }
                            if (p += h, i && h !== p) {
                                for (f = 0; d = n[f++];) d(v, m, a, s);
                                if (r) {
                                    if (p > 0) for (; h--;) v[h] || m[h] || (m[h] = Z.call(u));
                                    m = g(m)
                                }
                                Q.apply(u, m),
                                c && !r && m.length > 0 && p + n.length > 1 && t.uniqueSort(u)
                            }
                            return c && (B = x, S = y),
                            v
                        };
                        return i ? r(a) : a
                    }
                    var x, w, T, C, A, k, E, N, S, j, _, D, L, O, M, R, H, F, I, q = "sizzle" + 1 * new Date,
                    P = e.document,
                    B = 0,
                    W = 0,
                    z = n(),
                    $ = n(),
                    U = n(),
                    G = function(e, t) {
                        return e === t && (_ = !0),
                        0
                    },
                    Y = 1 << 31,
                    X = {}.hasOwnProperty,
                    J = [],
                    Z = J.pop,
                    V = J.push,
                    Q = J.push,
                    K = J.slice,
                    ee = function(e, t) {
                        for (var n = 0,
                        r = e.length; n < r; n++) if (e[n] === t) return n;
                        return - 1
                    },
                    te = "checked|selected|async|autofocus|autoplay|controls|defer|disabled|hidden|ismap|loop|multiple|open|readonly|required|scoped",
                    ne = "[\\x20\\t\\r\\n\\f]",
                    re = "(?:\\\\.|[\\w-]|[^\\x00-\\xa0])+",
                    ie = "\\[" + ne + "*(" + re + ")(?:" + ne + "*([*^$|!~]?=)" + ne + "*(?:'((?:\\\\.|[^\\\\'])*)'|\"((?:\\\\.|[^\\\\\"])*)\"|(" + re + "))|)" + ne + "*\\]",
                    oe = ":(" + re + ")(?:\\((('((?:\\\\.|[^\\\\'])*)'|\"((?:\\\\.|[^\\\\\"])*)\")|((?:\\\\.|[^\\\\()[\\]]|" + ie + ")*)|.*)\\)|)",
                    ae = new RegExp(ne + "+", "g"),
                    se = new RegExp("^" + ne + "+|((?:^|[^\\\\])(?:\\\\.)*)" + ne + "+$", "g"),
                    ue = new RegExp("^" + ne + "*," + ne + "*"),
                    ce = new RegExp("^" + ne + "*([>+~]|" + ne + ")" + ne + "*"),
                    le = new RegExp("=" + ne + "*([^\\]'\"]*?)" + ne + "*\\]", "g"),
                    fe = new RegExp(oe),
                    de = new RegExp("^" + re + "$"),
                    pe = {
                        ID: new RegExp("^#(" + re + ")"),
                        CLASS: new RegExp("^\\.(" + re + ")"),
                        TAG: new RegExp("^(" + re + "|[*])"),
                        ATTR: new RegExp("^" + ie),
                        PSEUDO: new RegExp("^" + oe),
                        CHILD: new RegExp("^:(only|first|last|nth|nth-last)-(child|of-type)(?:\\(" + ne + "*(even|odd|(([+-]|)(\\d*)n|)" + ne + "*(?:([+-]|)" + ne + "*(\\d+)|))" + ne + "*\\)|)", "i"),
                        bool: new RegExp("^(?:" + te + ")$", "i"),
                        needsContext: new RegExp("^" + ne + "*[>+~]|:(even|odd|eq|gt|lt|nth|first|last)(?:\\(" + ne + "*((?:-\\d)?\\d*)" + ne + "*\\)|)(?=[^-]|$)", "i")
                    },
                    he = /^(?:input|select|textarea|button)$/i,
                    ve = /^h\d$/i,
                    ge = /^[^{]+\{\s*\[native \w/,
                    me = /^(?:#([\w-]+)|(\w+)|\.([\w-]+))$/,
                    ye = /[+~]/,
                    be = /'|\\/g,
                    xe = new RegExp("\\\\([\\da-f]{1,6}" + ne + "?|(" + ne + ")|.)", "ig"),
                    we = function(e, t, n) {
                        var r = "0x" + t - 65536;
                        return r !== r || n ? t: r < 0 ? String.fromCharCode(r + 65536) : String.fromCharCode(r >> 10 | 55296, 1023 & r | 56320)
                    },
                    Te = function() {
                        D()
                    };
                    try {
                        Q.apply(J = K.call(P.childNodes), P.childNodes),
                        J[P.childNodes.length].nodeType
                    } catch(e) {
                        Q = {
                            apply: J.length ?
                            function(e, t) {
                                V.apply(e, K.call(t))
                            }: function(e, t) {
                                for (var n = e.length,
                                r = 0; e[n++] = t[r++];);
                                e.length = n - 1
                            }
                        }
                    }
                    w = t.support = {},
                    A = t.isXML = function(e) {
                        var t = e && (e.ownerDocument || e).documentElement;
                        return !! t && "HTML" !== t.nodeName
                    },
                    D = t.setDocument = function(e) {
                        var t, n, r = e ? e.ownerDocument || e: P;
                        return r !== L && 9 === r.nodeType && r.documentElement ? (L = r, O = L.documentElement, M = !A(L), (n = L.defaultView) && n.top !== n && (n.addEventListener ? n.addEventListener("unload", Te, !1) : n.attachEvent && n.attachEvent("onunload", Te)), w.attributes = i(function(e) {
                            return e.className = "i",
                            !e.getAttribute("className")
                        }), w.getElementsByTagName = i(function(e) {
                            return e.appendChild(L.createComment("")),
                            !e.getElementsByTagName("*").length
                        }), w.getElementsByClassName = ge.test(L.getElementsByClassName), w.getById = i(function(e) {
                            return O.appendChild(e).id = q,
                            !L.getElementsByName || !L.getElementsByName(q).length
                        }), w.getById ? (T.find.ID = function(e, t) {
                            if ("undefined" != typeof t.getElementById && M) {
                                var n = t.getElementById(e);
                                return n ? [n] : []
                            }
                        },
                        T.filter.ID = function(e) {
                            var t = e.replace(xe, we);
                            return function(e) {
                                return e.getAttribute("id") === t
                            }
                        }) : (delete T.find.ID, T.filter.ID = function(e) {
                            var t = e.replace(xe, we);
                            return function(e) {
                                var n = "undefined" != typeof e.getAttributeNode && e.getAttributeNode("id");
                                return n && n.value === t
                            }
                        }), T.find.TAG = w.getElementsByTagName ?
                        function(e, t) {
                            return "undefined" != typeof t.getElementsByTagName ? t.getElementsByTagName(e) : w.qsa ? t.querySelectorAll(e) : void 0
                        }: function(e, t) {
                            var n, r = [],
                            i = 0,
                            o = t.getElementsByTagName(e);
                            if ("*" === e) {
                                for (; n = o[i++];) 1 === n.nodeType && r.push(n);
                                return r
                            }
                            return o
                        },
                        T.find.CLASS = w.getElementsByClassName &&
                        function(e, t) {
                            if ("undefined" != typeof t.getElementsByClassName && M) return t.getElementsByClassName(e)
                        },
                        H = [], R = [], (w.qsa = ge.test(L.querySelectorAll)) && (i(function(e) {
                            O.appendChild(e).innerHTML = "<a id='" + q + "'></a><select id='" + q + "-\r\\' msallowcapture=''><option selected=''></option></select>",
                            e.querySelectorAll("[msallowcapture^='']").length && R.push("[*^$]=" + ne + "*(?:''|\"\")"),
                            e.querySelectorAll("[selected]").length || R.push("\\[" + ne + "*(?:value|" + te + ")"),
                            e.querySelectorAll("[id~=" + q + "-]").length || R.push("~="),
                            e.querySelectorAll(":checked").length || R.push(":checked"),
                            e.querySelectorAll("a#" + q + "+*").length || R.push(".#.+[+~]")
                        }), i(function(e) {
                            var t = L.createElement("input");
                            t.setAttribute("type", "hidden"),
                            e.appendChild(t).setAttribute("name", "D"),
                            e.querySelectorAll("[name=d]").length && R.push("name" + ne + "*[*^$|!~]?="),
                            e.querySelectorAll(":enabled").length || R.push(":enabled", ":disabled"),
                            e.querySelectorAll("*,:x"),
                            R.push(",.*:")
                        })), (w.matchesSelector = ge.test(F = O.matches || O.webkitMatchesSelector || O.mozMatchesSelector || O.oMatchesSelector || O.msMatchesSelector)) && i(function(e) {
                            w.disconnectedMatch = F.call(e, "div"),
                            F.call(e, "[s!='']:x"),
                            H.push("!=", oe)
                        }), R = R.length && new RegExp(R.join("|")), H = H.length && new RegExp(H.join("|")), t = ge.test(O.compareDocumentPosition), I = t || ge.test(O.contains) ?
                        function(e, t) {
                            var n = 9 === e.nodeType ? e.documentElement: e,
                            r = t && t.parentNode;
                            return e === r || !(!r || 1 !== r.nodeType || !(n.contains ? n.contains(r) : e.compareDocumentPosition && 16 & e.compareDocumentPosition(r)))
                        }: function(e, t) {
                            if (t) for (; t = t.parentNode;) if (t === e) return ! 0;
                            return ! 1
                        },
                        G = t ?
                        function(e, t) {
                            if (e === t) return _ = !0,
                            0;
                            var n = !e.compareDocumentPosition - !t.compareDocumentPosition;
                            return n ? n: (n = (e.ownerDocument || e) === (t.ownerDocument || t) ? e.compareDocumentPosition(t) : 1, 1 & n || !w.sortDetached && t.compareDocumentPosition(e) === n ? e === L || e.ownerDocument === P && I(P, e) ? -1 : t === L || t.ownerDocument === P && I(P, t) ? 1 : j ? ee(j, e) - ee(j, t) : 0 : 4 & n ? -1 : 1)
                        }: function(e, t) {
                            if (e === t) return _ = !0,
                            0;
                            var n, r = 0,
                            i = e.parentNode,
                            o = t.parentNode,
                            s = [e],
                            u = [t];
                            if (!i || !o) return e === L ? -1 : t === L ? 1 : i ? -1 : o ? 1 : j ? ee(j, e) - ee(j, t) : 0;
                            if (i === o) return a(e, t);
                            for (n = e; n = n.parentNode;) s.unshift(n);
                            for (n = t; n = n.parentNode;) u.unshift(n);
                            for (; s[r] === u[r];) r++;
                            return r ? a(s[r], u[r]) : s[r] === P ? -1 : u[r] === P ? 1 : 0
                        },
                        L) : L
                    },
                    t.matches = function(e, n) {
                        return t(e, null, null, n)
                    },
                    t.matchesSelector = function(e, n) {
                        if ((e.ownerDocument || e) !== L && D(e), n = n.replace(le, "='$1']"), w.matchesSelector && M && !U[n + " "] && (!H || !H.test(n)) && (!R || !R.test(n))) try {
                            var r = F.call(e, n);
                            if (r || w.disconnectedMatch || e.document && 11 !== e.document.nodeType) return r
                        } catch(e) {}
                        return t(n, L, null, [e]).length > 0
                    },
                    t.contains = function(e, t) {
                        return (e.ownerDocument || e) !== L && D(e),
                        I(e, t)
                    },
                    t.attr = function(e, t) { (e.ownerDocument || e) !== L && D(e);
                        var n = T.attrHandle[t.toLowerCase()],
                        r = n && X.call(T.attrHandle, t.toLowerCase()) ? n(e, t, !M) : void 0;
                        return void 0 !== r ? r: w.attributes || !M ? e.getAttribute(t) : (r = e.getAttributeNode(t)) && r.specified ? r.value: null
                    },
                    t.error = function(e) {
                        throw new Error("Syntax error, unrecognized expression: " + e)
                    },
                    t.uniqueSort = function(e) {
                        var t, n = [],
                        r = 0,
                        i = 0;
                        if (_ = !w.detectDuplicates, j = !w.sortStable && e.slice(0), e.sort(G), _) {
                            for (; t = e[i++];) t === e[i] && (r = n.push(i));
                            for (; r--;) e.splice(n[r], 1)
                        }
                        return j = null,
                        e
                    },
                    C = t.getText = function(e) {
                        var t, n = "",
                        r = 0,
                        i = e.nodeType;
                        if (i) {
                            if (1 === i || 9 === i || 11 === i) {
                                if ("string" == typeof e.textContent) return e.textContent;
                                for (e = e.firstChild; e; e = e.nextSibling) n += C(e)
                            } else if (3 === i || 4 === i) return e.nodeValue
                        } else for (; t = e[r++];) n += C(t);
                        return n
                    },
                    T = t.selectors = {
                        cacheLength: 50,
                        createPseudo: r,
                        match: pe,
                        attrHandle: {},
                        find: {},
                        relative: {
                            ">": {
                                dir: "parentNode",
                                first: !0
                            },
                            " ": {
                                dir: "parentNode"
                            },
                            "+": {
                                dir: "previousSibling",
                                first: !0
                            },
                            "~": {
                                dir: "previousSibling"
                            }
                        },
                        preFilter: {
                            ATTR: function(e) {
                                return e[1] = e[1].replace(xe, we),
                                e[3] = (e[3] || e[4] || e[5] || "").replace(xe, we),
                                "~=" === e[2] && (e[3] = " " + e[3] + " "),
                                e.slice(0, 4)
                            },
                            CHILD: function(e) {
                                return e[1] = e[1].toLowerCase(),
                                "nth" === e[1].slice(0, 3) ? (e[3] || t.error(e[0]), e[4] = +(e[4] ? e[5] + (e[6] || 1) : 2 * ("even" === e[3] || "odd" === e[3])), e[5] = +(e[7] + e[8] || "odd" === e[3])) : e[3] && t.error(e[0]),
                                e
                            },
                            PSEUDO: function(e) {
                                var t, n = !e[6] && e[2];
                                return pe.CHILD.test(e[0]) ? null: (e[3] ? e[2] = e[4] || e[5] || "": n && fe.test(n) && (t = k(n, !0)) && (t = n.indexOf(")", n.length - t) - n.length) && (e[0] = e[0].slice(0, t), e[2] = n.slice(0, t)), e.slice(0, 3))
                            }
                        },
                        filter: {
                            TAG: function(e) {
                                var t = e.replace(xe, we).toLowerCase();
                                return "*" === e ?
                                function() {
                                    return ! 0
                                }: function(e) {
                                    return e.nodeName && e.nodeName.toLowerCase() === t
                                }
                            },
                            CLASS: function(e) {
                                var t = z[e + " "];
                                return t || (t = new RegExp("(^|" + ne + ")" + e + "(" + ne + "|$)")) && z(e,
                                function(e) {
                                    return t.test("string" == typeof e.className && e.className || "undefined" != typeof e.getAttribute && e.getAttribute("class") || "")
                                })
                            },
                            ATTR: function(e, n, r) {
                                return function(i) {
                                    var o = t.attr(i, e);
                                    return null == o ? "!=" === n: !n || (o += "", "=" === n ? o === r: "!=" === n ? o !== r: "^=" === n ? r && 0 === o.indexOf(r) : "*=" === n ? r && o.indexOf(r) > -1 : "$=" === n ? r && o.slice( - r.length) === r: "~=" === n ? (" " + o.replace(ae, " ") + " ").indexOf(r) > -1 : "|=" === n && (o === r || o.slice(0, r.length + 1) === r + "-"))
                                }
                            },
                            CHILD: function(e, t, n, r, i) {
                                var o = "nth" !== e.slice(0, 3),
                                a = "last" !== e.slice( - 4),
                                s = "of-type" === t;
                                return 1 === r && 0 === i ?
                                function(e) {
                                    return !! e.parentNode
                                }: function(t, n, u) {
                                    var c, l, f, d, p, h, v = o !== a ? "nextSibling": "previousSibling",
                                    g = t.parentNode,
                                    m = s && t.nodeName.toLowerCase(),
                                    y = !u && !s,
                                    b = !1;
                                    if (g) {
                                        if (o) {
                                            for (; v;) {
                                                for (d = t; d = d[v];) if (s ? d.nodeName.toLowerCase() === m: 1 === d.nodeType) return ! 1;
                                                h = v = "only" === e && !h && "nextSibling"
                                            }
                                            return ! 0
                                        }
                                        if (h = [a ? g.firstChild: g.lastChild], a && y) {
                                            for (d = g, f = d[q] || (d[q] = {}), l = f[d.uniqueID] || (f[d.uniqueID] = {}), c = l[e] || [], p = c[0] === B && c[1], b = p && c[2], d = p && g.childNodes[p]; d = ++p && d && d[v] || (b = p = 0) || h.pop();) if (1 === d.nodeType && ++b && d === t) {
                                                l[e] = [B, p, b];
                                                break
                                            }
                                        } else if (y && (d = t, f = d[q] || (d[q] = {}), l = f[d.uniqueID] || (f[d.uniqueID] = {}), c = l[e] || [], p = c[0] === B && c[1], b = p), b === !1) for (; (d = ++p && d && d[v] || (b = p = 0) || h.pop()) && ((s ? d.nodeName.toLowerCase() !== m: 1 !== d.nodeType) || !++b || (y && (f = d[q] || (d[q] = {}), l = f[d.uniqueID] || (f[d.uniqueID] = {}), l[e] = [B, b]), d !== t)););
                                        return b -= i,
                                        b === r || b % r === 0 && b / r >= 0
                                    }
                                }
                            },
                            PSEUDO: function(e, n) {
                                var i, o = T.pseudos[e] || T.setFilters[e.toLowerCase()] || t.error("unsupported pseudo: " + e);
                                return o[q] ? o(n) : o.length > 1 ? (i = [e, e, "", n], T.setFilters.hasOwnProperty(e.toLowerCase()) ? r(function(e, t) {
                                    for (var r, i = o(e, n), a = i.length; a--;) r = ee(e, i[a]),
                                    e[r] = !(t[r] = i[a])
                                }) : function(e) {
                                    return o(e, 0, i)
                                }) : o
                            }
                        },
                        pseudos: {
                            not: r(function(e) {
                                var t = [],
                                n = [],
                                i = E(e.replace(se, "$1"));
                                return i[q] ? r(function(e, t, n, r) {
                                    for (var o, a = i(e, null, r, []), s = e.length; s--;)(o = a[s]) && (e[s] = !(t[s] = o))
                                }) : function(e, r, o) {
                                    return t[0] = e,
                                    i(t, null, o, n),
                                    t[0] = null,
                                    !n.pop()
                                }
                            }),
                            has: r(function(e) {
                                return function(n) {
                                    return t(e, n).length > 0
                                }
                            }),
                            contains: r(function(e) {
                                return e = e.replace(xe, we),
                                function(t) {
                                    return (t.textContent || t.innerText || C(t)).indexOf(e) > -1
                                }
                            }),
                            lang: r(function(e) {
                                return de.test(e || "") || t.error("unsupported lang: " + e),
                                e = e.replace(xe, we).toLowerCase(),
                                function(t) {
                                    var n;
                                    do
                                    if (n = M ? t.lang: t.getAttribute("xml:lang") || t.getAttribute("lang")) return n = n.toLowerCase(),
                                    n === e || 0 === n.indexOf(e + "-");
                                    while ((t = t.parentNode) && 1 === t.nodeType);
                                    return ! 1
                                }
                            }),
                            target: function(t) {
                                var n = e.location && e.location.hash;
                                return n && n.slice(1) === t.id
                            },
                            root: function(e) {
                                return e === O
                            },
                            focus: function(e) {
                                return e === L.activeElement && (!L.hasFocus || L.hasFocus()) && !!(e.type || e.href || ~e.tabIndex)
                            },
                            enabled: function(e) {
                                return e.disabled === !1
                            },
                            disabled: function(e) {
                                return e.disabled === !0
                            },
                            checked: function(e) {
                                var t = e.nodeName.toLowerCase();
                                return "input" === t && !!e.checked || "option" === t && !!e.selected
                            },
                            selected: function(e) {
                                return e.parentNode && e.parentNode.selectedIndex,
                                e.selected === !0
                            },
                            empty: function(e) {
                                for (e = e.firstChild; e; e = e.nextSibling) if (e.nodeType < 6) return ! 1;
                                return ! 0
                            },
                            parent: function(e) {
                                return ! T.pseudos.empty(e)
                            },
                            header: function(e) {
                                return ve.test(e.nodeName)
                            },
                            input: function(e) {
                                return he.test(e.nodeName)
                            },
                            button: function(e) {
                                var t = e.nodeName.toLowerCase();
                                return "input" === t && "button" === e.type || "button" === t
                            },
                            text: function(e) {
                                var t;
                                return "input" === e.nodeName.toLowerCase() && "text" === e.type && (null == (t = e.getAttribute("type")) || "text" === t.toLowerCase())
                            },
                            first: c(function() {
                                return [0]
                            }),
                            last: c(function(e, t) {
                                return [t - 1]
                            }),
                            eq: c(function(e, t, n) {
                                return [n < 0 ? n + t: n]
                            }),
                            even: c(function(e, t) {
                                for (var n = 0; n < t; n += 2) e.push(n);
                                return e
                            }),
                            odd: c(function(e, t) {
                                for (var n = 1; n < t; n += 2) e.push(n);
                                return e
                            }),
                            lt: c(function(e, t, n) {
                                for (var r = n < 0 ? n + t: n; --r >= 0;) e.push(r);
                                return e
                            }),
                            gt: c(function(e, t, n) {
                                for (var r = n < 0 ? n + t: n; ++r < t;) e.push(r);
                                return e
                            })
                        }
                    },
                    T.pseudos.nth = T.pseudos.eq;
                    for (x in {
                        radio: !0,
                        checkbox: !0,
                        file: !0,
                        password: !0,
                        image: !0
                    }) T.pseudos[x] = s(x);
                    for (x in {
                        submit: !0,
                        reset: !0
                    }) T.pseudos[x] = u(x);
                    return f.prototype = T.filters = T.pseudos,
                    T.setFilters = new f,
                    k = t.tokenize = function(e, n) {
                        var r, i, o, a, s, u, c, l = $[e + " "];
                        if (l) return n ? 0 : l.slice(0);
                        for (s = e, u = [], c = T.preFilter; s;) {
                            r && !(i = ue.exec(s)) || (i && (s = s.slice(i[0].length) || s), u.push(o = [])),
                            r = !1,
                            (i = ce.exec(s)) && (r = i.shift(), o.push({
                                value: r,
                                type: i[0].replace(se, " ")
                            }), s = s.slice(r.length));
                            for (a in T.filter) ! (i = pe[a].exec(s)) || c[a] && !(i = c[a](i)) || (r = i.shift(), o.push({
                                value: r,
                                type: a,
                                matches: i
                            }), s = s.slice(r.length));
                            if (!r) break
                        }
                        return n ? s.length: s ? t.error(e) : $(e, u).slice(0)
                    },
                    E = t.compile = function(e, t) {
                        var n, r = [],
                        i = [],
                        o = U[e + " "];
                        if (!o) {
                            for (t || (t = k(e)), n = t.length; n--;) o = y(t[n]),
                            o[q] ? r.push(o) : i.push(o);
                            o = U(e, b(i, r)),
                            o.selector = e
                        }
                        return o
                    },
                    N = t.select = function(e, t, n, r) {
                        var i, o, a, s, u, c = "function" == typeof e && e,
                        f = !r && k(e = c.selector || e);
                        if (n = n || [], 1 === f.length) {
                            if (o = f[0] = f[0].slice(0), o.length > 2 && "ID" === (a = o[0]).type && w.getById && 9 === t.nodeType && M && T.relative[o[1].type]) {
                                if (t = (T.find.ID(a.matches[0].replace(xe, we), t) || [])[0], !t) return n;
                                c && (t = t.parentNode),
                                e = e.slice(o.shift().value.length)
                            }
                            for (i = pe.needsContext.test(e) ? 0 : o.length; i--&&(a = o[i], !T.relative[s = a.type]);) if ((u = T.find[s]) && (r = u(a.matches[0].replace(xe, we), ye.test(o[0].type) && l(t.parentNode) || t))) {
                                if (o.splice(i, 1), e = r.length && d(o), !e) return Q.apply(n, r),
                                n;
                                break
                            }
                        }
                        return (c || E(e, f))(r, t, !M, n, !t || ye.test(e) && l(t.parentNode) || t),
                        n
                    },
                    w.sortStable = q.split("").sort(G).join("") === q,
                    w.detectDuplicates = !!_,
                    D(),
                    w.sortDetached = i(function(e) {
                        return 1 & e.compareDocumentPosition(L.createElement("div"))
                    }),
                    i(function(e) {
                        return e.innerHTML = "<a href='#'></a>",
                        "#" === e.firstChild.getAttribute("href")
                    }) || o("type|href|height|width",
                    function(e, t, n) {
                        if (!n) return e.getAttribute(t, "type" === t.toLowerCase() ? 1 : 2)
                    }),
                    w.attributes && i(function(e) {
                        return e.innerHTML = "<input/>",
                        e.firstChild.setAttribute("value", ""),
                        "" === e.firstChild.getAttribute("value")
                    }) || o("value",
                    function(e, t, n) {
                        if (!n && "input" === e.nodeName.toLowerCase()) return e.defaultValue
                    }),
                    i(function(e) {
                        return null == e.getAttribute("disabled")
                    }) || o(te,
                    function(e, t, n) {
                        var r;
                        if (!n) return e[t] === !0 ? t.toLowerCase() : (r = e.getAttributeNode(t)) && r.specified ? r.value: null
                    }),
                    t
                } (n);
                me.find = Te,
                me.expr = Te.selectors,
                me.expr[":"] = me.expr.pseudos,
                me.uniqueSort = me.unique = Te.uniqueSort,
                me.text = Te.getText,
                me.isXMLDoc = Te.isXML,
                me.contains = Te.contains;
                var Ce = function(e, t, n) {
                    for (var r = [], i = void 0 !== n; (e = e[t]) && 9 !== e.nodeType;) if (1 === e.nodeType) {
                        if (i && me(e).is(n)) break;
                        r.push(e)
                    }
                    return r
                },
                Ae = function(e, t) {
                    for (var n = []; e; e = e.nextSibling) 1 === e.nodeType && e !== t && n.push(e);
                    return n
                },
                ke = me.expr.match.needsContext,
                Ee = /^<([\w-]+)\s*\/?>(?:<\/\1>|)$/,
                Ne = /^.[^:#\[\.,]*$/;
                me.filter = function(e, t, n) {
                    var r = t[0];
                    return n && (e = ":not(" + e + ")"),
                    1 === t.length && 1 === r.nodeType ? me.find.matchesSelector(r, e) ? [r] : [] : me.find.matches(e, me.grep(t,
                    function(e) {
                        return 1 === e.nodeType
                    }))
                },
                me.fn.extend({
                    find: function(e) {
                        var t, n = [],
                        r = this,
                        i = r.length;
                        if ("string" != typeof e) return this.pushStack(me(e).filter(function() {
                            for (t = 0; t < i; t++) if (me.contains(r[t], this)) return ! 0
                        }));
                        for (t = 0; t < i; t++) me.find(e, r[t], n);
                        return n = this.pushStack(i > 1 ? me.unique(n) : n),
                        n.selector = this.selector ? this.selector + " " + e: e,
                        n
                    },
                    filter: function(e) {
                        return this.pushStack(s(this, e || [], !1))
                    },
                    not: function(e) {
                        return this.pushStack(s(this, e || [], !0))
                    },
                    is: function(e) {
                        return !! s(this, "string" == typeof e && ke.test(e) ? me(e) : e || [], !1).length
                    }
                });
                var Se, je = /^(?:\s*(<[\w\W]+>)[^>]*|#([\w-]*))$/,
                _e = me.fn.init = function(e, t, n) {
                    var r, i;
                    if (!e) return this;
                    if (n = n || Se, "string" == typeof e) {
                        if (r = "<" === e.charAt(0) && ">" === e.charAt(e.length - 1) && e.length >= 3 ? [null, e, null] : je.exec(e), !r || !r[1] && t) return ! t || t.jquery ? (t || n).find(e) : this.constructor(t).find(e);
                        if (r[1]) {
                            if (t = t instanceof me ? t[0] : t, me.merge(this, me.parseHTML(r[1], t && t.nodeType ? t.ownerDocument || t: se, !0)), Ee.test(r[1]) && me.isPlainObject(t)) for (r in t) me.isFunction(this[r]) ? this[r](t[r]) : this.attr(r, t[r]);
                            return this
                        }
                        if (i = se.getElementById(r[2]), i && i.parentNode) {
                            if (i.id !== r[2]) return Se.find(e);
                            this.length = 1,
                            this[0] = i
                        }
                        return this.context = se,
                        this.selector = e,
                        this
                    }
                    return e.nodeType ? (this.context = this[0] = e, this.length = 1, this) : me.isFunction(e) ? "undefined" != typeof n.ready ? n.ready(e) : e(me) : (void 0 !== e.selector && (this.selector = e.selector, this.context = e.context), me.makeArray(e, this))
                };
                _e.prototype = me.fn,
                Se = me(se);
                var De = /^(?:parents|prev(?:Until|All))/,
                Le = {
                    children: !0,
                    contents: !0,
                    next: !0,
                    prev: !0
                };
                me.fn.extend({
                    has: function(e) {
                        var t, n = me(e, this),
                        r = n.length;
                        return this.filter(function() {
                            for (t = 0; t < r; t++) if (me.contains(this, n[t])) return ! 0
                        })
                    },
                    closest: function(e, t) {
                        for (var n, r = 0,
                        i = this.length,
                        o = [], a = ke.test(e) || "string" != typeof e ? me(e, t || this.context) : 0; r < i; r++) for (n = this[r]; n && n !== t; n = n.parentNode) if (n.nodeType < 11 && (a ? a.index(n) > -1 : 1 === n.nodeType && me.find.matchesSelector(n, e))) {
                            o.push(n);
                            break
                        }
                        return this.pushStack(o.length > 1 ? me.uniqueSort(o) : o)
                    },
                    index: function(e) {
                        return e ? "string" == typeof e ? me.inArray(this[0], me(e)) : me.inArray(e.jquery ? e[0] : e, this) : this[0] && this[0].parentNode ? this.first().prevAll().length: -1;
                    },
                    add: function(e, t) {
                        return this.pushStack(me.uniqueSort(me.merge(this.get(), me(e, t))))
                    },
                    addBack: function(e) {
                        return this.add(null == e ? this.prevObject: this.prevObject.filter(e))
                    }
                }),
                me.each({
                    parent: function(e) {
                        var t = e.parentNode;
                        return t && 11 !== t.nodeType ? t: null
                    },
                    parents: function(e) {
                        return Ce(e, "parentNode")
                    },
                    parentsUntil: function(e, t, n) {
                        return Ce(e, "parentNode", n)
                    },
                    next: function(e) {
                        return u(e, "nextSibling")
                    },
                    prev: function(e) {
                        return u(e, "previousSibling")
                    },
                    nextAll: function(e) {
                        return Ce(e, "nextSibling")
                    },
                    prevAll: function(e) {
                        return Ce(e, "previousSibling")
                    },
                    nextUntil: function(e, t, n) {
                        return Ce(e, "nextSibling", n)
                    },
                    prevUntil: function(e, t, n) {
                        return Ce(e, "previousSibling", n)
                    },
                    siblings: function(e) {
                        return Ae((e.parentNode || {}).firstChild, e)
                    },
                    children: function(e) {
                        return Ae(e.firstChild)
                    },
                    contents: function(e) {
                        return me.nodeName(e, "iframe") ? e.contentDocument || e.contentWindow.document: me.merge([], e.childNodes)
                    }
                },
                function(e, t) {
                    me.fn[e] = function(n, r) {
                        var i = me.map(this, t, n);
                        return "Until" !== e.slice( - 5) && (r = n),
                        r && "string" == typeof r && (i = me.filter(r, i)),
                        this.length > 1 && (Le[e] || (i = me.uniqueSort(i)), De.test(e) && (i = i.reverse())),
                        this.pushStack(i)
                    }
                });
                var Oe = /\S+/g;
                me.Callbacks = function(e) {
                    e = "string" == typeof e ? c(e) : me.extend({},
                    e);
                    var t, n, r, i, o = [],
                    a = [],
                    s = -1,
                    u = function() {
                        for (i = e.once, r = t = !0; a.length; s = -1) for (n = a.shift(); ++s < o.length;) o[s].apply(n[0], n[1]) === !1 && e.stopOnFalse && (s = o.length, n = !1);
                        e.memory || (n = !1),
                        t = !1,
                        i && (o = n ? [] : "")
                    },
                    l = {
                        add: function() {
                            return o && (n && !t && (s = o.length - 1, a.push(n)),
                            function t(n) {
                                me.each(n,
                                function(n, r) {
                                    me.isFunction(r) ? e.unique && l.has(r) || o.push(r) : r && r.length && "string" !== me.type(r) && t(r)
                                })
                            } (arguments), n && !t && u()),
                            this
                        },
                        remove: function() {
                            return me.each(arguments,
                            function(e, t) {
                                for (var n; (n = me.inArray(t, o, n)) > -1;) o.splice(n, 1),
                                n <= s && s--
                            }),
                            this
                        },
                        has: function(e) {
                            return e ? me.inArray(e, o) > -1 : o.length > 0
                        },
                        empty: function() {
                            return o && (o = []),
                            this
                        },
                        disable: function() {
                            return i = a = [],
                            o = n = "",
                            this
                        },
                        disabled: function() {
                            return ! o
                        },
                        lock: function() {
                            return i = !0,
                            n || l.disable(),
                            this
                        },
                        locked: function() {
                            return !! i
                        },
                        fireWith: function(e, n) {
                            return i || (n = n || [], n = [e, n.slice ? n.slice() : n], a.push(n), t || u()),
                            this
                        },
                        fire: function() {
                            return l.fireWith(this, arguments),
                            this
                        },
                        fired: function() {
                            return !! r
                        }
                    };
                    return l
                },
                me.extend({
                    Deferred: function(e) {
                        var t = [["resolve", "done", me.Callbacks("once memory"), "resolved"], ["reject", "fail", me.Callbacks("once memory"), "rejected"], ["notify", "progress", me.Callbacks("memory")]],
                        n = "pending",
                        r = {
                            state: function() {
                                return n
                            },
                            always: function() {
                                return i.done(arguments).fail(arguments),
                                this
                            },
                            then: function() {
                                var e = arguments;
                                return me.Deferred(function(n) {
                                    me.each(t,
                                    function(t, o) {
                                        var a = me.isFunction(e[t]) && e[t];
                                        i[o[1]](function() {
                                            var e = a && a.apply(this, arguments);
                                            e && me.isFunction(e.promise) ? e.promise().progress(n.notify).done(n.resolve).fail(n.reject) : n[o[0] + "With"](this === r ? n.promise() : this, a ? [e] : arguments)
                                        })
                                    }),
                                    e = null
                                }).promise()
                            },
                            promise: function(e) {
                                return null != e ? me.extend(e, r) : r
                            }
                        },
                        i = {};
                        return r.pipe = r.then,
                        me.each(t,
                        function(e, o) {
                            var a = o[2],
                            s = o[3];
                            r[o[1]] = a.add,
                            s && a.add(function() {
                                n = s
                            },
                            t[1 ^ e][2].disable, t[2][2].lock),
                            i[o[0]] = function() {
                                return i[o[0] + "With"](this === i ? r: this, arguments),
                                this
                            },
                            i[o[0] + "With"] = a.fireWith
                        }),
                        r.promise(i),
                        e && e.call(i, i),
                        i
                    },
                    when: function(e) {
                        var t, n, r, i = 0,
                        o = ue.call(arguments),
                        a = o.length,
                        s = 1 !== a || e && me.isFunction(e.promise) ? a: 0,
                        u = 1 === s ? e: me.Deferred(),
                        c = function(e, n, r) {
                            return function(i) {
                                n[e] = this,
                                r[e] = arguments.length > 1 ? ue.call(arguments) : i,
                                r === t ? u.notifyWith(n, r) : --s || u.resolveWith(n, r)
                            }
                        };
                        if (a > 1) for (t = new Array(a), n = new Array(a), r = new Array(a); i < a; i++) o[i] && me.isFunction(o[i].promise) ? o[i].promise().progress(c(i, n, t)).done(c(i, r, o)).fail(u.reject) : --s;
                        return s || u.resolveWith(r, o),
                        u.promise()
                    }
                });
                var Me;
                me.fn.ready = function(e) {
                    return me.ready.promise().done(e),
                    this
                },
                me.extend({
                    isReady: !1,
                    readyWait: 1,
                    holdReady: function(e) {
                        e ? me.readyWait++:me.ready(!0)
                    },
                    ready: function(e) { (e === !0 ? --me.readyWait: me.isReady) || (me.isReady = !0, e !== !0 && --me.readyWait > 0 || (Me.resolveWith(se, [me]), me.fn.triggerHandler && (me(se).triggerHandler("ready"), me(se).off("ready"))))
                    }
                }),
                me.ready.promise = function(e) {
                    if (!Me) if (Me = me.Deferred(), "complete" === se.readyState || "loading" !== se.readyState && !se.documentElement.doScroll) n.setTimeout(me.ready);
                    else if (se.addEventListener) se.addEventListener("DOMContentLoaded", f),
                    n.addEventListener("load", f);
                    else {
                        se.attachEvent("onreadystatechange", f),
                        n.attachEvent("onload", f);
                        var t = !1;
                        try {
                            t = null == n.frameElement && se.documentElement
                        } catch(e) {}
                        t && t.doScroll && !
                        function e() {
                            if (!me.isReady) {
                                try {
                                    t.doScroll("left")
                                } catch(t) {
                                    return n.setTimeout(e, 50)
                                }
                                l(),
                                me.ready()
                            }
                        } ()
                    }
                    return Me.promise(e)
                },
                me.ready.promise();
                var Re;
                for (Re in me(ve)) break;
                ve.ownFirst = "0" === Re,
                ve.inlineBlockNeedsLayout = !1,
                me(function() {
                    var e, t, n, r;
                    n = se.getElementsByTagName("body")[0],
                    n && n.style && (t = se.createElement("div"), r = se.createElement("div"), r.style.cssText = "position:absolute;border:0;width:0;height:0;top:0;left:-9999px", n.appendChild(r).appendChild(t), "undefined" != typeof t.style.zoom && (t.style.cssText = "display:inline;margin:0;border:0;padding:1px;width:1px;zoom:1", ve.inlineBlockNeedsLayout = e = 3 === t.offsetWidth, e && (n.style.zoom = 1)), n.removeChild(r))
                }),
                function() {
                    var e = se.createElement("div");
                    ve.deleteExpando = !0;
                    try {
                        delete e.test
                    } catch(e) {
                        ve.deleteExpando = !1
                    }
                    e = null
                } ();
                var He = function(e) {
                    var t = me.noData[(e.nodeName + " ").toLowerCase()],
                    n = +e.nodeType || 1;
                    return (1 === n || 9 === n) && (!t || t !== !0 && e.getAttribute("classid") === t)
                },
                Fe = /^(?:\{[\w\W]*\}|\[[\w\W]*\])$/,
                Ie = /([A-Z])/g;
                me.extend({
                    cache: {},
                    noData: {
                        "applet ": !0,
                        "embed ": !0,
                        "object ": "clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"
                    },
                    hasData: function(e) {
                        return e = e.nodeType ? me.cache[e[me.expando]] : e[me.expando],
                        !!e && !p(e)
                    },
                    data: function(e, t, n) {
                        return h(e, t, n)
                    },
                    removeData: function(e, t) {
                        return v(e, t)
                    },
                    _data: function(e, t, n) {
                        return h(e, t, n, !0)
                    },
                    _removeData: function(e, t) {
                        return v(e, t, !0)
                    }
                }),
                me.fn.extend({
                    data: function(e, t) {
                        var n, r, i, o = this[0],
                        a = o && o.attributes;
                        if (void 0 === e) {
                            if (this.length && (i = me.data(o), 1 === o.nodeType && !me._data(o, "parsedAttrs"))) {
                                for (n = a.length; n--;) a[n] && (r = a[n].name, 0 === r.indexOf("data-") && (r = me.camelCase(r.slice(5)), d(o, r, i[r])));
                                me._data(o, "parsedAttrs", !0)
                            }
                            return i
                        }
                        return "object" == typeof e ? this.each(function() {
                            me.data(this, e)
                        }) : arguments.length > 1 ? this.each(function() {
                            me.data(this, e, t)
                        }) : o ? d(o, e, me.data(o, e)) : void 0
                    },
                    removeData: function(e) {
                        return this.each(function() {
                            me.removeData(this, e)
                        })
                    }
                }),
                me.extend({
                    queue: function(e, t, n) {
                        var r;
                        if (e) return t = (t || "fx") + "queue",
                        r = me._data(e, t),
                        n && (!r || me.isArray(n) ? r = me._data(e, t, me.makeArray(n)) : r.push(n)),
                        r || []
                    },
                    dequeue: function(e, t) {
                        t = t || "fx";
                        var n = me.queue(e, t),
                        r = n.length,
                        i = n.shift(),
                        o = me._queueHooks(e, t),
                        a = function() {
                            me.dequeue(e, t)
                        };
                        "inprogress" === i && (i = n.shift(), r--),
                        i && ("fx" === t && n.unshift("inprogress"), delete o.stop, i.call(e, a, o)),
                        !r && o && o.empty.fire()
                    },
                    _queueHooks: function(e, t) {
                        var n = t + "queueHooks";
                        return me._data(e, n) || me._data(e, n, {
                            empty: me.Callbacks("once memory").add(function() {
                                me._removeData(e, t + "queue"),
                                me._removeData(e, n)
                            })
                        })
                    }
                }),
                me.fn.extend({
                    queue: function(e, t) {
                        var n = 2;
                        return "string" != typeof e && (t = e, e = "fx", n--),
                        arguments.length < n ? me.queue(this[0], e) : void 0 === t ? this: this.each(function() {
                            var n = me.queue(this, e, t);
                            me._queueHooks(this, e),
                            "fx" === e && "inprogress" !== n[0] && me.dequeue(this, e)
                        })
                    },
                    dequeue: function(e) {
                        return this.each(function() {
                            me.dequeue(this, e)
                        })
                    },
                    clearQueue: function(e) {
                        return this.queue(e || "fx", [])
                    },
                    promise: function(e, t) {
                        var n, r = 1,
                        i = me.Deferred(),
                        o = this,
                        a = this.length,
                        s = function() {--r || i.resolveWith(o, [o])
                        };
                        for ("string" != typeof e && (t = e, e = void 0), e = e || "fx"; a--;) n = me._data(o[a], e + "queueHooks"),
                        n && n.empty && (r++, n.empty.add(s));
                        return s(),
                        i.promise(t)
                    }
                }),
                function() {
                    var e;
                    ve.shrinkWrapBlocks = function() {
                        if (null != e) return e;
                        e = !1;
                        var t, n, r;
                        return n = se.getElementsByTagName("body")[0],
                        n && n.style ? (t = se.createElement("div"), r = se.createElement("div"), r.style.cssText = "position:absolute;border:0;width:0;height:0;top:0;left:-9999px", n.appendChild(r).appendChild(t), "undefined" != typeof t.style.zoom && (t.style.cssText = "-webkit-box-sizing:content-box;-moz-box-sizing:content-box;box-sizing:content-box;display:block;margin:0;border:0;padding:1px;width:1px;zoom:1", t.appendChild(se.createElement("div")).style.width = "5px", e = 3 !== t.offsetWidth), n.removeChild(r), e) : void 0
                    }
                } ();
                var qe = /[+-]?(?:\d*\.|)\d+(?:[eE][+-]?\d+|)/.source,
                Pe = new RegExp("^(?:([+-])=|)(" + qe + ")([a-z%]*)$", "i"),
                Be = ["Top", "Right", "Bottom", "Left"],
                We = function(e, t) {
                    return e = t || e,
                    "none" === me.css(e, "display") || !me.contains(e.ownerDocument, e)
                },
                ze = function(e, t, n, r, i, o, a) {
                    var s = 0,
                    u = e.length,
                    c = null == n;
                    if ("object" === me.type(n)) {
                        i = !0;
                        for (s in n) ze(e, t, s, n[s], !0, o, a)
                    } else if (void 0 !== r && (i = !0, me.isFunction(r) || (a = !0), c && (a ? (t.call(e, r), t = null) : (c = t, t = function(e, t, n) {
                        return c.call(me(e), n)
                    })), t)) for (; s < u; s++) t(e[s], n, a ? r: r.call(e[s], s, t(e[s], n)));
                    return i ? e: c ? t.call(e) : u ? t(e[0], n) : o
                },
                $e = /^(?:checkbox|radio)$/i,
                Ue = /<([\w:-]+)/,
                Ge = /^$|\/(?:java|ecma)script/i,
                Ye = /^\s+/,
                Xe = "abbr|article|aside|audio|bdi|canvas|data|datalist|details|dialog|figcaption|figure|footer|header|hgroup|main|mark|meter|nav|output|picture|progress|section|summary|template|time|video"; !
                function() {
                    var e = se.createElement("div"),
                    t = se.createDocumentFragment(),
                    n = se.createElement("input");
                    e.innerHTML = "  <link/><table></table><a href='/a'>a</a><input type='checkbox'/>",
                    ve.leadingWhitespace = 3 === e.firstChild.nodeType,
                    ve.tbody = !e.getElementsByTagName("tbody").length,
                    ve.htmlSerialize = !!e.getElementsByTagName("link").length,
                    ve.html5Clone = "<:nav></:nav>" !== se.createElement("nav").cloneNode(!0).outerHTML,
                    n.type = "checkbox",
                    n.checked = !0,
                    t.appendChild(n),
                    ve.appendChecked = n.checked,
                    e.innerHTML = "<textarea>x</textarea>",
                    ve.noCloneChecked = !!e.cloneNode(!0).lastChild.defaultValue,
                    t.appendChild(e),
                    n = se.createElement("input"),
                    n.setAttribute("type", "radio"),
                    n.setAttribute("checked", "checked"),
                    n.setAttribute("name", "t"),
                    e.appendChild(n),
                    ve.checkClone = e.cloneNode(!0).cloneNode(!0).lastChild.checked,
                    ve.noCloneEvent = !!e.addEventListener,
                    e[me.expando] = 1,
                    ve.attributes = !e.getAttribute(me.expando)
                } ();
                var Je = {
                    option: [1, "<select multiple='multiple'>", "</select>"],
                    legend: [1, "<fieldset>", "</fieldset>"],
                    area: [1, "<map>", "</map>"],
                    param: [1, "<object>", "</object>"],
                    thead: [1, "<table>", "</table>"],
                    tr: [2, "<table><tbody>", "</tbody></table>"],
                    col: [2, "<table><tbody></tbody><colgroup>", "</colgroup></table>"],
                    td: [3, "<table><tbody><tr>", "</tr></tbody></table>"],
                    _default: ve.htmlSerialize ? [0, "", ""] : [1, "X<div>", "</div>"]
                };
                Je.optgroup = Je.option,
                Je.tbody = Je.tfoot = Je.colgroup = Je.caption = Je.thead,
                Je.th = Je.td;
                var Ze = /<|&#?\w+;/,
                Ve = /<tbody/i; !
                function() {
                    var e, t, r = se.createElement("div");
                    for (e in {
                        submit: !0,
                        change: !0,
                        focusin: !0
                    }) t = "on" + e,
                    (ve[e] = t in n) || (r.setAttribute(t, "t"), ve[e] = r.attributes[t].expando === !1);
                    r = null
                } ();
                var Qe = /^(?:input|select|textarea)$/i,
                Ke = /^key/,
                et = /^(?:mouse|pointer|contextmenu|drag|drop)|click/,
                tt = /^(?:focusinfocus|focusoutblur)$/,
                nt = /^([^.]*)(?:\.(.+)|)/;
                me.event = {
                    global: {},
                    add: function(e, t, n, r, i) {
                        var o, a, s, u, c, l, f, d, p, h, v, g = me._data(e);
                        if (g) {
                            for (n.handler && (u = n, n = u.handler, i = u.selector), n.guid || (n.guid = me.guid++), (a = g.events) || (a = g.events = {}), (l = g.handle) || (l = g.handle = function(e) {
                                return "undefined" == typeof me || e && me.event.triggered === e.type ? void 0 : me.event.dispatch.apply(l.elem, arguments)
                            },
                            l.elem = e), t = (t || "").match(Oe) || [""], s = t.length; s--;) o = nt.exec(t[s]) || [],
                            p = v = o[1],
                            h = (o[2] || "").split(".").sort(),
                            p && (c = me.event.special[p] || {},
                            p = (i ? c.delegateType: c.bindType) || p, c = me.event.special[p] || {},
                            f = me.extend({
                                type: p,
                                origType: v,
                                data: r,
                                handler: n,
                                guid: n.guid,
                                selector: i,
                                needsContext: i && me.expr.match.needsContext.test(i),
                                namespace: h.join(".")
                            },
                            u), (d = a[p]) || (d = a[p] = [], d.delegateCount = 0, c.setup && c.setup.call(e, r, h, l) !== !1 || (e.addEventListener ? e.addEventListener(p, l, !1) : e.attachEvent && e.attachEvent("on" + p, l))), c.add && (c.add.call(e, f), f.handler.guid || (f.handler.guid = n.guid)), i ? d.splice(d.delegateCount++, 0, f) : d.push(f), me.event.global[p] = !0);
                            e = null
                        }
                    },
                    remove: function(e, t, n, r, i) {
                        var o, a, s, u, c, l, f, d, p, h, v, g = me.hasData(e) && me._data(e);
                        if (g && (l = g.events)) {
                            for (t = (t || "").match(Oe) || [""], c = t.length; c--;) if (s = nt.exec(t[c]) || [], p = v = s[1], h = (s[2] || "").split(".").sort(), p) {
                                for (f = me.event.special[p] || {},
                                p = (r ? f.delegateType: f.bindType) || p, d = l[p] || [], s = s[2] && new RegExp("(^|\\.)" + h.join("\\.(?:.*\\.|)") + "(\\.|$)"), u = o = d.length; o--;) a = d[o],
                                !i && v !== a.origType || n && n.guid !== a.guid || s && !s.test(a.namespace) || r && r !== a.selector && ("**" !== r || !a.selector) || (d.splice(o, 1), a.selector && d.delegateCount--, f.remove && f.remove.call(e, a));
                                u && !d.length && (f.teardown && f.teardown.call(e, h, g.handle) !== !1 || me.removeEvent(e, p, g.handle), delete l[p])
                            } else for (p in l) me.event.remove(e, p + t[c], n, r, !0);
                            me.isEmptyObject(l) && (delete g.handle, me._removeData(e, "events"))
                        }
                    },
                    trigger: function(e, t, r, i) {
                        var o, a, s, u, c, l, f, d = [r || se],
                        p = he.call(e, "type") ? e.type: e,
                        h = he.call(e, "namespace") ? e.namespace.split(".") : [];
                        if (s = l = r = r || se, 3 !== r.nodeType && 8 !== r.nodeType && !tt.test(p + me.event.triggered) && (p.indexOf(".") > -1 && (h = p.split("."), p = h.shift(), h.sort()), a = p.indexOf(":") < 0 && "on" + p, e = e[me.expando] ? e: new me.Event(p, "object" == typeof e && e), e.isTrigger = i ? 2 : 3, e.namespace = h.join("."), e.rnamespace = e.namespace ? new RegExp("(^|\\.)" + h.join("\\.(?:.*\\.|)") + "(\\.|$)") : null, e.result = void 0, e.target || (e.target = r), t = null == t ? [e] : me.makeArray(t, [e]), c = me.event.special[p] || {},
                        i || !c.trigger || c.trigger.apply(r, t) !== !1)) {
                            if (!i && !c.noBubble && !me.isWindow(r)) {
                                for (u = c.delegateType || p, tt.test(u + p) || (s = s.parentNode); s; s = s.parentNode) d.push(s),
                                l = s;
                                l === (r.ownerDocument || se) && d.push(l.defaultView || l.parentWindow || n)
                            }
                            for (f = 0; (s = d[f++]) && !e.isPropagationStopped();) e.type = f > 1 ? u: c.bindType || p,
                            o = (me._data(s, "events") || {})[e.type] && me._data(s, "handle"),
                            o && o.apply(s, t),
                            o = a && s[a],
                            o && o.apply && He(s) && (e.result = o.apply(s, t), e.result === !1 && e.preventDefault());
                            if (e.type = p, !i && !e.isDefaultPrevented() && (!c._default || c._default.apply(d.pop(), t) === !1) && He(r) && a && r[p] && !me.isWindow(r)) {
                                l = r[a],
                                l && (r[a] = null),
                                me.event.triggered = p;
                                try {
                                    r[p]()
                                } catch(e) {}
                                me.event.triggered = void 0,
                                l && (r[a] = l)
                            }
                            return e.result
                        }
                    },
                    dispatch: function(e) {
                        e = me.event.fix(e);
                        var t, n, r, i, o, a = [],
                        s = ue.call(arguments),
                        u = (me._data(this, "events") || {})[e.type] || [],
                        c = me.event.special[e.type] || {};
                        if (s[0] = e, e.delegateTarget = this, !c.preDispatch || c.preDispatch.call(this, e) !== !1) {
                            for (a = me.event.handlers.call(this, e, u), t = 0; (i = a[t++]) && !e.isPropagationStopped();) for (e.currentTarget = i.elem, n = 0; (o = i.handlers[n++]) && !e.isImmediatePropagationStopped();) e.rnamespace && !e.rnamespace.test(o.namespace) || (e.handleObj = o, e.data = o.data, r = ((me.event.special[o.origType] || {}).handle || o.handler).apply(i.elem, s), void 0 !== r && (e.result = r) === !1 && (e.preventDefault(), e.stopPropagation()));
                            return c.postDispatch && c.postDispatch.call(this, e),
                            e.result
                        }
                    },
                    handlers: function(e, t) {
                        var n, r, i, o, a = [],
                        s = t.delegateCount,
                        u = e.target;
                        if (s && u.nodeType && ("click" !== e.type || isNaN(e.button) || e.button < 1)) for (; u != this; u = u.parentNode || this) if (1 === u.nodeType && (u.disabled !== !0 || "click" !== e.type)) {
                            for (r = [], n = 0; n < s; n++) o = t[n],
                            i = o.selector + " ",
                            void 0 === r[i] && (r[i] = o.needsContext ? me(i, this).index(u) > -1 : me.find(i, this, null, [u]).length),
                            r[i] && r.push(o);
                            r.length && a.push({
                                elem: u,
                                handlers: r
                            })
                        }
                        return s < t.length && a.push({
                            elem: this,
                            handlers: t.slice(s)
                        }),
                        a
                    },
                    fix: function(e) {
                        if (e[me.expando]) return e;
                        var t, n, r, i = e.type,
                        o = e,
                        a = this.fixHooks[i];
                        for (a || (this.fixHooks[i] = a = et.test(i) ? this.mouseHooks: Ke.test(i) ? this.keyHooks: {}), r = a.props ? this.props.concat(a.props) : this.props, e = new me.Event(o), t = r.length; t--;) n = r[t],
                        e[n] = o[n];
                        return e.target || (e.target = o.srcElement || se),
                        3 === e.target.nodeType && (e.target = e.target.parentNode),
                        e.metaKey = !!e.metaKey,
                        a.filter ? a.filter(e, o) : e
                    },
                    props: "altKey bubbles cancelable ctrlKey currentTarget detail eventPhase metaKey relatedTarget shiftKey target timeStamp view which".split(" "),
                    fixHooks: {},
                    keyHooks: {
                        props: "char charCode key keyCode".split(" "),
                        filter: function(e, t) {
                            return null == e.which && (e.which = null != t.charCode ? t.charCode: t.keyCode),
                            e
                        }
                    },
                    mouseHooks: {
                        props: "button buttons clientX clientY fromElement offsetX offsetY pageX pageY screenX screenY toElement".split(" "),
                        filter: function(e, t) {
                            var n, r, i, o = t.button,
                            a = t.fromElement;
                            return null == e.pageX && null != t.clientX && (r = e.target.ownerDocument || se, i = r.documentElement, n = r.body, e.pageX = t.clientX + (i && i.scrollLeft || n && n.scrollLeft || 0) - (i && i.clientLeft || n && n.clientLeft || 0), e.pageY = t.clientY + (i && i.scrollTop || n && n.scrollTop || 0) - (i && i.clientTop || n && n.clientTop || 0)),
                            !e.relatedTarget && a && (e.relatedTarget = a === e.target ? t.toElement: a),
                            e.which || void 0 === o || (e.which = 1 & o ? 1 : 2 & o ? 3 : 4 & o ? 2 : 0),
                            e
                        }
                    },
                    special: {
                        load: {
                            noBubble: !0
                        },
                        focus: {
                            trigger: function() {
                                if (this !== A() && this.focus) try {
                                    return this.focus(),
                                    !1
                                } catch(e) {}
                            },
                            delegateType: "focusin"
                        },
                        blur: {
                            trigger: function() {
                                if (this === A() && this.blur) return this.blur(),
                                !1
                            },
                            delegateType: "focusout"
                        },
                        click: {
                            trigger: function() {
                                if (me.nodeName(this, "input") && "checkbox" === this.type && this.click) return this.click(),
                                !1
                            },
                            _default: function(e) {
                                return me.nodeName(e.target, "a")
                            }
                        },
                        beforeunload: {
                            postDispatch: function(e) {
                                void 0 !== e.result && e.originalEvent && (e.originalEvent.returnValue = e.result)
                            }
                        }
                    },
                    simulate: function(e, t, n) {
                        var r = me.extend(new me.Event, n, {
                            type: e,
                            isSimulated: !0
                        });
                        me.event.trigger(r, null, t),
                        r.isDefaultPrevented() && n.preventDefault()
                    }
                },
                me.removeEvent = se.removeEventListener ?
                function(e, t, n) {
                    e.removeEventListener && e.removeEventListener(t, n)
                }: function(e, t, n) {
                    var r = "on" + t;
                    e.detachEvent && ("undefined" == typeof e[r] && (e[r] = null), e.detachEvent(r, n))
                },
                me.Event = function(e, t) {
                    return this instanceof me.Event ? (e && e.type ? (this.originalEvent = e, this.type = e.type, this.isDefaultPrevented = e.defaultPrevented || void 0 === e.defaultPrevented && e.returnValue === !1 ? T: C) : this.type = e, t && me.extend(this, t), this.timeStamp = e && e.timeStamp || me.now(), void(this[me.expando] = !0)) : new me.Event(e, t)
                },
                me.Event.prototype = {
                    constructor: me.Event,
                    isDefaultPrevented: C,
                    isPropagationStopped: C,
                    isImmediatePropagationStopped: C,
                    preventDefault: function() {
                        var e = this.originalEvent;
                        this.isDefaultPrevented = T,
                        e && (e.preventDefault ? e.preventDefault() : e.returnValue = !1)
                    },
                    stopPropagation: function() {
                        var e = this.originalEvent;
                        this.isPropagationStopped = T,
                        e && !this.isSimulated && (e.stopPropagation && e.stopPropagation(), e.cancelBubble = !0)
                    },
                    stopImmediatePropagation: function() {
                        var e = this.originalEvent;
                        this.isImmediatePropagationStopped = T,
                        e && e.stopImmediatePropagation && e.stopImmediatePropagation(),
                        this.stopPropagation()
                    }
                },
                me.each({
                    mouseenter: "mouseover",
                    mouseleave: "mouseout",
                    pointerenter: "pointerover",
                    pointerleave: "pointerout"
                },
                function(e, t) {
                    me.event.special[e] = {
                        delegateType: t,
                        bindType: t,
                        handle: function(e) {
                            var n, r = this,
                            i = e.relatedTarget,
                            o = e.handleObj;
                            return i && (i === r || me.contains(r, i)) || (e.type = o.origType, n = o.handler.apply(this, arguments), e.type = t),
                            n
                        }
                    }
                }),
                ve.submit || (me.event.special.submit = {
                    setup: function() {
                        return ! me.nodeName(this, "form") && void me.event.add(this, "click._submit keypress._submit",
                        function(e) {
                            var t = e.target,
                            n = me.nodeName(t, "input") || me.nodeName(t, "button") ? me.prop(t, "form") : void 0;
                            n && !me._data(n, "submit") && (me.event.add(n, "submit._submit",
                            function(e) {
                                e._submitBubble = !0
                            }), me._data(n, "submit", !0))
                        })
                    },
                    postDispatch: function(e) {
                        e._submitBubble && (delete e._submitBubble, this.parentNode && !e.isTrigger && me.event.simulate("submit", this.parentNode, e))
                    },
                    teardown: function() {
                        return ! me.nodeName(this, "form") && void me.event.remove(this, "._submit")
                    }
                }),
                ve.change || (me.event.special.change = {
                    setup: function() {
                        return Qe.test(this.nodeName) ? ("checkbox" !== this.type && "radio" !== this.type || (me.event.add(this, "propertychange._change",
                        function(e) {
                            "checked" === e.originalEvent.propertyName && (this._justChanged = !0)
                        }), me.event.add(this, "click._change",
                        function(e) {
                            this._justChanged && !e.isTrigger && (this._justChanged = !1),
                            me.event.simulate("change", this, e)
                        })), !1) : void me.event.add(this, "beforeactivate._change",
                        function(e) {
                            var t = e.target;
                            Qe.test(t.nodeName) && !me._data(t, "change") && (me.event.add(t, "change._change",
                            function(e) { ! this.parentNode || e.isSimulated || e.isTrigger || me.event.simulate("change", this.parentNode, e)
                            }), me._data(t, "change", !0))
                        })
                    },
                    handle: function(e) {
                        var t = e.target;
                        if (this !== t || e.isSimulated || e.isTrigger || "radio" !== t.type && "checkbox" !== t.type) return e.handleObj.handler.apply(this, arguments)
                    },
                    teardown: function() {
                        return me.event.remove(this, "._change"),
                        !Qe.test(this.nodeName)
                    }
                }),
                ve.focusin || me.each({
                    focus: "focusin",
                    blur: "focusout"
                },
                function(e, t) {
                    var n = function(e) {
                        me.event.simulate(t, e.target, me.event.fix(e))
                    };
                    me.event.special[t] = {
                        setup: function() {
                            var r = this.ownerDocument || this,
                            i = me._data(r, t);
                            i || r.addEventListener(e, n, !0),
                            me._data(r, t, (i || 0) + 1)
                        },
                        teardown: function() {
                            var r = this.ownerDocument || this,
                            i = me._data(r, t) - 1;
                            i ? me._data(r, t, i) : (r.removeEventListener(e, n, !0), me._removeData(r, t))
                        }
                    }
                }),
                me.fn.extend({
                    on: function(e, t, n, r) {
                        return k(this, e, t, n, r)
                    },
                    one: function(e, t, n, r) {
                        return k(this, e, t, n, r, 1)
                    },
                    off: function(e, t, n) {
                        var r, i;
                        if (e && e.preventDefault && e.handleObj) return r = e.handleObj,
                        me(e.delegateTarget).off(r.namespace ? r.origType + "." + r.namespace: r.origType, r.selector, r.handler),
                        this;
                        if ("object" == typeof e) {
                            for (i in e) this.off(i, t, e[i]);
                            return this
                        }
                        return t !== !1 && "function" != typeof t || (n = t, t = void 0),
                        n === !1 && (n = C),
                        this.each(function() {
                            me.event.remove(this, e, n, t)
                        })
                    },
                    trigger: function(e, t) {
                        return this.each(function() {
                            me.event.trigger(e, t, this)
                        })
                    },
                    triggerHandler: function(e, t) {
                        var n = this[0];
                        if (n) return me.event.trigger(e, t, n, !0)
                    }
                });
                var rt = / jQuery\d+="(?:null|\d+)"/g,
                it = new RegExp("<(?:" + Xe + ")[\\s/>]", "i"),
                ot = /<(?!area|br|col|embed|hr|img|input|link|meta|param)(([\w:-]+)[^>]*)\/>/gi,
                at = /<script|<style|<link/i,
                st = /checked\s*(?:[^=]|=\s*.checked.)/i,
                ut = /^true\/(.*)/,
                ct = /^\s*<!(?:\[CDATA\[|--)|(?:\]\]|--)>\s*$/g,
                lt = m(se),
                ft = lt.appendChild(se.createElement("div"));
                me.extend({
                    htmlPrefilter: function(e) {
                        return e.replace(ot, "<$1></$2>")
                    },
                    clone: function(e, t, n) {
                        var r, i, o, a, s, u = me.contains(e.ownerDocument, e);
                        if (ve.html5Clone || me.isXMLDoc(e) || !it.test("<" + e.nodeName + ">") ? o = e.cloneNode(!0) : (ft.innerHTML = e.outerHTML, ft.removeChild(o = ft.firstChild)), !(ve.noCloneEvent && ve.noCloneChecked || 1 !== e.nodeType && 11 !== e.nodeType || me.isXMLDoc(e))) for (r = y(o), s = y(e), a = 0; null != (i = s[a]); ++a) r[a] && _(i, r[a]);
                        if (t) if (n) for (s = s || y(e), r = r || y(o), a = 0; null != (i = s[a]); a++) j(i, r[a]);
                        else j(e, o);
                        return r = y(o, "script"),
                        r.length > 0 && b(r, !u && y(e, "script")),
                        r = s = i = null,
                        o
                    },
                    cleanData: function(e, t) {
                        for (var n, r, i, o, a = 0,
                        s = me.expando,
                        u = me.cache,
                        c = ve.attributes,
                        l = me.event.special; null != (n = e[a]); a++) if ((t || He(n)) && (i = n[s], o = i && u[i])) {
                            if (o.events) for (r in o.events) l[r] ? me.event.remove(n, r) : me.removeEvent(n, r, o.handle);
                            u[i] && (delete u[i], c || "undefined" == typeof n.removeAttribute ? n[s] = void 0 : n.removeAttribute(s), ae.push(i))
                        }
                    }
                }),
                me.fn.extend({
                    domManip: D,
                    detach: function(e) {
                        return L(this, e, !0)
                    },
                    remove: function(e) {
                        return L(this, e)
                    },
                    text: function(e) {
                        return ze(this,
                        function(e) {
                            return void 0 === e ? me.text(this) : this.empty().append((this[0] && this[0].ownerDocument || se).createTextNode(e))
                        },
                        null, e, arguments.length)
                    },
                    append: function() {
                        return D(this, arguments,
                        function(e) {
                            if (1 === this.nodeType || 11 === this.nodeType || 9 === this.nodeType) {
                                var t = E(this, e);
                                t.appendChild(e)
                            }
                        })
                    },
                    prepend: function() {
                        return D(this, arguments,
                        function(e) {
                            if (1 === this.nodeType || 11 === this.nodeType || 9 === this.nodeType) {
                                var t = E(this, e);
                                t.insertBefore(e, t.firstChild)
                            }
                        })
                    },
                    before: function() {
                        return D(this, arguments,
                        function(e) {
                            this.parentNode && this.parentNode.insertBefore(e, this)
                        })
                    },
                    after: function() {
                        return D(this, arguments,
                        function(e) {
                            this.parentNode && this.parentNode.insertBefore(e, this.nextSibling)
                        })
                    },
                    empty: function() {
                        for (var e, t = 0; null != (e = this[t]); t++) {
                            for (1 === e.nodeType && me.cleanData(y(e, !1)); e.firstChild;) e.removeChild(e.firstChild);
                            e.options && me.nodeName(e, "select") && (e.options.length = 0)
                        }
                        return this
                    },
                    clone: function(e, t) {
                        return e = null != e && e,
                        t = null == t ? e: t,
                        this.map(function() {
                            return me.clone(this, e, t)
                        })
                    },
                    html: function(e) {
                        return ze(this,
                        function(e) {
                            var t = this[0] || {},
                            n = 0,
                            r = this.length;
                            if (void 0 === e) return 1 === t.nodeType ? t.innerHTML.replace(rt, "") : void 0;
                            if ("string" == typeof e && !at.test(e) && (ve.htmlSerialize || !it.test(e)) && (ve.leadingWhitespace || !Ye.test(e)) && !Je[(Ue.exec(e) || ["", ""])[1].toLowerCase()]) {
                                e = me.htmlPrefilter(e);
                                try {
                                    for (; n < r; n++) t = this[n] || {},
                                    1 === t.nodeType && (me.cleanData(y(t, !1)), t.innerHTML = e);
                                    t = 0
                                } catch(e) {}
                            }
                            t && this.empty().append(e)
                        },
                        null, e, arguments.length)
                    },
                    replaceWith: function() {
                        var e = [];
                        return D(this, arguments,
                        function(t) {
                            var n = this.parentNode;
                            me.inArray(this, e) < 0 && (me.cleanData(y(this)), n && n.replaceChild(t, this))
                        },
                        e)
                    }
                }),
                me.each({
                    appendTo: "append",
                    prependTo: "prepend",
                    insertBefore: "before",
                    insertAfter: "after",
                    replaceAll: "replaceWith"
                },
                function(e, t) {
                    me.fn[e] = function(e) {
                        for (var n, r = 0,
                        i = [], o = me(e), a = o.length - 1; r <= a; r++) n = r === a ? this: this.clone(!0),
                        me(o[r])[t](n),
                        le.apply(i, n.get());
                        return this.pushStack(i)
                    }
                });
                var dt, pt = {
                    HTML: "block",
                    BODY: "block"
                },
                ht = /^margin/,
                vt = new RegExp("^(" + qe + ")(?!px)[a-z%]+$", "i"),
                gt = function(e, t, n, r) {
                    var i, o, a = {};
                    for (o in t) a[o] = e.style[o],
                    e.style[o] = t[o];
                    i = n.apply(e, r || []);
                    for (o in t) e.style[o] = a[o];
                    return i
                },
                mt = se.documentElement; !
                function() {
                    function e() {
                        var e, l, f = se.documentElement;
                        f.appendChild(u),
                        c.style.cssText = "-webkit-box-sizing:border-box;box-sizing:border-box;position:relative;display:block;margin:auto;border:1px;padding:1px;top:1%;width:50%",
                        t = i = s = !1,
                        r = a = !0,
                        n.getComputedStyle && (l = n.getComputedStyle(c), t = "1%" !== (l || {}).top, s = "2px" === (l || {}).marginLeft, i = "4px" === (l || {
                            width: "4px"
                        }).width, c.style.marginRight = "50%", r = "4px" === (l || {
                            marginRight: "4px"
                        }).marginRight, e = c.appendChild(se.createElement("div")), e.style.cssText = c.style.cssText = "-webkit-box-sizing:content-box;-moz-box-sizing:content-box;box-sizing:content-box;display:block;margin:0;border:0;padding:0", e.style.marginRight = e.style.width = "0", c.style.width = "1px", a = !parseFloat((n.getComputedStyle(e) || {}).marginRight), c.removeChild(e)),
                        c.style.display = "none",
                        o = 0 === c.getClientRects().length,
                        o && (c.style.display = "", c.innerHTML = "<table><tr><td></td><td>t</td></tr></table>", e = c.getElementsByTagName("td"), e[0].style.cssText = "margin:0;border:0;padding:0;display:none", o = 0 === e[0].offsetHeight, o && (e[0].style.display = "", e[1].style.display = "none", o = 0 === e[0].offsetHeight)),
                        f.removeChild(u)
                    }
                    var t, r, i, o, a, s, u = se.createElement("div"),
                    c = se.createElement("div");
                    c.style && (c.style.cssText = "float:left;opacity:.5", ve.opacity = "0.5" === c.style.opacity, ve.cssFloat = !!c.style.cssFloat, c.style.backgroundClip = "content-box", c.cloneNode(!0).style.backgroundClip = "", ve.clearCloneStyle = "content-box" === c.style.backgroundClip, u = se.createElement("div"), u.style.cssText = "border:0;width:8px;height:0;top:0;left:-9999px;padding:0;margin-top:1px;position:absolute", c.innerHTML = "", u.appendChild(c), ve.boxSizing = "" === c.style.boxSizing || "" === c.style.MozBoxSizing || "" === c.style.WebkitBoxSizing, me.extend(ve, {
                        reliableHiddenOffsets: function() {
                            return null == t && e(),
                            o
                        },
                        boxSizingReliable: function() {
                            return null == t && e(),
                            i
                        },
                        pixelMarginRight: function() {
                            return null == t && e(),
                            r
                        },
                        pixelPosition: function() {
                            return null == t && e(),
                            t
                        },
                        reliableMarginRight: function() {
                            return null == t && e(),
                            a
                        },
                        reliableMarginLeft: function() {
                            return null == t && e(),
                            s
                        }
                    }))
                } ();
                var yt, bt, xt = /^(top|right|bottom|left)$/;
                n.getComputedStyle ? (yt = function(e) {
                    var t = e.ownerDocument.defaultView;
                    return t && t.opener || (t = n),
                    t.getComputedStyle(e)
                },
                bt = function(e, t, n) {
                    var r, i, o, a, s = e.style;
                    return n = n || yt(e),
                    a = n ? n.getPropertyValue(t) || n[t] : void 0,
                    "" !== a && void 0 !== a || me.contains(e.ownerDocument, e) || (a = me.style(e, t)),
                    n && !ve.pixelMarginRight() && vt.test(a) && ht.test(t) && (r = s.width, i = s.minWidth, o = s.maxWidth, s.minWidth = s.maxWidth = s.width = a, a = n.width, s.width = r, s.minWidth = i, s.maxWidth = o),
                    void 0 === a ? a: a + ""
                }) : mt.currentStyle && (yt = function(e) {
                    return e.currentStyle
                },
                bt = function(e, t, n) {
                    var r, i, o, a, s = e.style;
                    return n = n || yt(e),
                    a = n ? n[t] : void 0,
                    null == a && s && s[t] && (a = s[t]),
                    vt.test(a) && !xt.test(t) && (r = s.left, i = e.runtimeStyle, o = i && i.left, o && (i.left = e.currentStyle.left), s.left = "fontSize" === t ? "1em": a, a = s.pixelLeft + "px", s.left = r, o && (i.left = o)),
                    void 0 === a ? a: a + "" || "auto"
                });
                var wt = /alpha\([^)]*\)/i,
                Tt = /opacity\s*=\s*([^)]*)/i,
                Ct = /^(none|table(?!-c[ea]).+)/,
                At = new RegExp("^(" + qe + ")(.*)$", "i"),
                kt = {
                    position: "absolute",
                    visibility: "hidden",
                    display: "block"
                },
                Et = {
                    letterSpacing: "0",
                    fontWeight: "400"
                },
                Nt = ["Webkit", "O", "Moz", "ms"],
                St = se.createElement("div").style;
                me.extend({
                    cssHooks: {
                        opacity: {
                            get: function(e, t) {
                                if (t) {
                                    var n = bt(e, "opacity");
                                    return "" === n ? "1": n
                                }
                            }
                        }
                    },
                    cssNumber: {
                        animationIterationCount: !0,
                        columnCount: !0,
                        fillOpacity: !0,
                        flexGrow: !0,
                        flexShrink: !0,
                        fontWeight: !0,
                        lineHeight: !0,
                        opacity: !0,
                        order: !0,
                        orphans: !0,
                        widows: !0,
                        zIndex: !0,
                        zoom: !0
                    },
                    cssProps: {
                        float: ve.cssFloat ? "cssFloat": "styleFloat"
                    },
                    style: function(e, t, n, r) {
                        if (e && 3 !== e.nodeType && 8 !== e.nodeType && e.style) {
                            var i, o, a, s = me.camelCase(t),
                            u = e.style;
                            if (t = me.cssProps[s] || (me.cssProps[s] = H(s) || s), a = me.cssHooks[t] || me.cssHooks[s], void 0 === n) return a && "get" in a && void 0 !== (i = a.get(e, !1, r)) ? i: u[t];
                            if (o = typeof n, "string" === o && (i = Pe.exec(n)) && i[1] && (n = g(e, t, i), o = "number"), null != n && n === n && ("number" === o && (n += i && i[3] || (me.cssNumber[s] ? "": "px")), ve.clearCloneStyle || "" !== n || 0 !== t.indexOf("background") || (u[t] = "inherit"), !(a && "set" in a && void 0 === (n = a.set(e, n, r))))) try {
                                u[t] = n
                            } catch(e) {}
                        }
                    },
                    css: function(e, t, n, r) {
                        var i, o, a, s = me.camelCase(t);
                        return t = me.cssProps[s] || (me.cssProps[s] = H(s) || s),
                        a = me.cssHooks[t] || me.cssHooks[s],
                        a && "get" in a && (o = a.get(e, !0, n)),
                        void 0 === o && (o = bt(e, t, r)),
                        "normal" === o && t in Et && (o = Et[t]),
                        "" === n || n ? (i = parseFloat(o), n === !0 || isFinite(i) ? i || 0 : o) : o
                    }
                }),
                me.each(["height", "width"],
                function(e, t) {
                    me.cssHooks[t] = {
                        get: function(e, n, r) {
                            if (n) return Ct.test(me.css(e, "display")) && 0 === e.offsetWidth ? gt(e, kt,
                            function() {
                                return P(e, t, r)
                            }) : P(e, t, r)
                        },
                        set: function(e, n, r) {
                            var i = r && yt(e);
                            return I(e, n, r ? q(e, t, r, ve.boxSizing && "border-box" === me.css(e, "boxSizing", !1, i), i) : 0)
                        }
                    }
                }),
                ve.opacity || (me.cssHooks.opacity = {
                    get: function(e, t) {
                        return Tt.test((t && e.currentStyle ? e.currentStyle.filter: e.style.filter) || "") ? .01 * parseFloat(RegExp.$1) + "": t ? "1": ""
                    },
                    set: function(e, t) {
                        var n = e.style,
                        r = e.currentStyle,
                        i = me.isNumeric(t) ? "alpha(opacity=" + 100 * t + ")": "",
                        o = r && r.filter || n.filter || "";
                        n.zoom = 1,
                        (t >= 1 || "" === t) && "" === me.trim(o.replace(wt, "")) && n.removeAttribute && (n.removeAttribute("filter"), "" === t || r && !r.filter) || (n.filter = wt.test(o) ? o.replace(wt, i) : o + " " + i)
                    }
                }),
                me.cssHooks.marginRight = R(ve.reliableMarginRight,
                function(e, t) {
                    if (t) return gt(e, {
                        display: "inline-block"
                    },
                    bt, [e, "marginRight"])
                }),
                me.cssHooks.marginLeft = R(ve.reliableMarginLeft,
                function(e, t) {
                    if (t) return (parseFloat(bt(e, "marginLeft")) || (me.contains(e.ownerDocument, e) ? e.getBoundingClientRect().left - gt(e, {
                        marginLeft: 0
                    },
                    function() {
                        return e.getBoundingClientRect().left
                    }) : 0)) + "px"
                }),
                me.each({
                    margin: "",
                    padding: "",
                    border: "Width"
                },
                function(e, t) {
                    me.cssHooks[e + t] = {
                        expand: function(n) {
                            for (var r = 0,
                            i = {},
                            o = "string" == typeof n ? n.split(" ") : [n]; r < 4; r++) i[e + Be[r] + t] = o[r] || o[r - 2] || o[0];
                            return i
                        }
                    },
                    ht.test(e) || (me.cssHooks[e + t].set = I)
                }),
                me.fn.extend({
                    css: function(e, t) {
                        return ze(this,
                        function(e, t, n) {
                            var r, i, o = {},
                            a = 0;
                            if (me.isArray(t)) {
                                for (r = yt(e), i = t.length; a < i; a++) o[t[a]] = me.css(e, t[a], !1, r);
                                return o
                            }
                            return void 0 !== n ? me.style(e, t, n) : me.css(e, t)
                        },
                        e, t, arguments.length > 1)
                    },
                    show: function() {
                        return F(this, !0)
                    },
                    hide: function() {
                        return F(this)
                    },
                    toggle: function(e) {
                        return "boolean" == typeof e ? e ? this.show() : this.hide() : this.each(function() {
                            We(this) ? me(this).show() : me(this).hide()
                        })
                    }
                }),
                me.Tween = B,
                B.prototype = {
                    constructor: B,
                    init: function(e, t, n, r, i, o) {
                        this.elem = e,
                        this.prop = n,
                        this.easing = i || me.easing._default,
                        this.options = t,
                        this.start = this.now = this.cur(),
                        this.end = r,
                        this.unit = o || (me.cssNumber[n] ? "": "px")
                    },
                    cur: function() {
                        var e = B.propHooks[this.prop];
                        return e && e.get ? e.get(this) : B.propHooks._default.get(this)
                    },
                    run: function(e) {
                        var t, n = B.propHooks[this.prop];
                        return this.options.duration ? this.pos = t = me.easing[this.easing](e, this.options.duration * e, 0, 1, this.options.duration) : this.pos = t = e,
                        this.now = (this.end - this.start) * t + this.start,
                        this.options.step && this.options.step.call(this.elem, this.now, this),
                        n && n.set ? n.set(this) : B.propHooks._default.set(this),
                        this
                    }
                },
                B.prototype.init.prototype = B.prototype,
                B.propHooks = {
                    _default: {
                        get: function(e) {
                            var t;
                            return 1 !== e.elem.nodeType || null != e.elem[e.prop] && null == e.elem.style[e.prop] ? e.elem[e.prop] : (t = me.css(e.elem, e.prop, ""), t && "auto" !== t ? t: 0)
                        },
                        set: function(e) {
                            me.fx.step[e.prop] ? me.fx.step[e.prop](e) : 1 !== e.elem.nodeType || null == e.elem.style[me.cssProps[e.prop]] && !me.cssHooks[e.prop] ? e.elem[e.prop] = e.now: me.style(e.elem, e.prop, e.now + e.unit)
                        }
                    }
                },
                B.propHooks.scrollTop = B.propHooks.scrollLeft = {
                    set: function(e) {
                        e.elem.nodeType && e.elem.parentNode && (e.elem[e.prop] = e.now)
                    }
                },
                me.easing = {
                    linear: function(e) {
                        return e
                    },
                    swing: function(e) {
                        return.5 - Math.cos(e * Math.PI) / 2
                    },
                    _default: "swing"
                },
                me.fx = B.prototype.init,
                me.fx.step = {};
                var jt, _t, Dt = /^(?:toggle|show|hide)$/,
                Lt = /queueHooks$/;
                me.Animation = me.extend(Y, {
                    tweeners: {
                        "*": [function(e, t) {
                            var n = this.createTween(e, t);
                            return g(n.elem, e, Pe.exec(t), n),
                            n
                        }]
                    },
                    tweener: function(e, t) {
                        me.isFunction(e) ? (t = e, e = ["*"]) : e = e.match(Oe);
                        for (var n, r = 0,
                        i = e.length; r < i; r++) n = e[r],
                        Y.tweeners[n] = Y.tweeners[n] || [],
                        Y.tweeners[n].unshift(t)
                    },
                    prefilters: [U],
                    prefilter: function(e, t) {
                        t ? Y.prefilters.unshift(e) : Y.prefilters.push(e)
                    }
                }),
                me.speed = function(e, t, n) {
                    var r = e && "object" == typeof e ? me.extend({},
                    e) : {
                        complete: n || !n && t || me.isFunction(e) && e,
                        duration: e,
                        easing: n && t || t && !me.isFunction(t) && t
                    };
                    return r.duration = me.fx.off ? 0 : "number" == typeof r.duration ? r.duration: r.duration in me.fx.speeds ? me.fx.speeds[r.duration] : me.fx.speeds._default,
                    null != r.queue && r.queue !== !0 || (r.queue = "fx"),
                    r.old = r.complete,
                    r.complete = function() {
                        me.isFunction(r.old) && r.old.call(this),
                        r.queue && me.dequeue(this, r.queue)
                    },
                    r
                },
                me.fn.extend({
                    fadeTo: function(e, t, n, r) {
                        return this.filter(We).css("opacity", 0).show().end().animate({
                            opacity: t
                        },
                        e, n, r)
                    },
                    animate: function(e, t, n, r) {
                        var i = me.isEmptyObject(e),
                        o = me.speed(t, n, r),
                        a = function() {
                            var t = Y(this, me.extend({},
                            e), o); (i || me._data(this, "finish")) && t.stop(!0)
                        };
                        return a.finish = a,
                        i || o.queue === !1 ? this.each(a) : this.queue(o.queue, a)
                    },
                    stop: function(e, t, n) {
                        var r = function(e) {
                            var t = e.stop;
                            delete e.stop,
                            t(n)
                        };
                        return "string" != typeof e && (n = t, t = e, e = void 0),
                        t && e !== !1 && this.queue(e || "fx", []),
                        this.each(function() {
                            var t = !0,
                            i = null != e && e + "queueHooks",
                            o = me.timers,
                            a = me._data(this);
                            if (i) a[i] && a[i].stop && r(a[i]);
                            else for (i in a) a[i] && a[i].stop && Lt.test(i) && r(a[i]);
                            for (i = o.length; i--;) o[i].elem !== this || null != e && o[i].queue !== e || (o[i].anim.stop(n), t = !1, o.splice(i, 1)); ! t && n || me.dequeue(this, e)
                        })
                    },
                    finish: function(e) {
                        return e !== !1 && (e = e || "fx"),
                        this.each(function() {
                            var t, n = me._data(this),
                            r = n[e + "queue"],
                            i = n[e + "queueHooks"],
                            o = me.timers,
                            a = r ? r.length: 0;
                            for (n.finish = !0, me.queue(this, e, []), i && i.stop && i.stop.call(this, !0), t = o.length; t--;) o[t].elem === this && o[t].queue === e && (o[t].anim.stop(!0), o.splice(t, 1));
                            for (t = 0; t < a; t++) r[t] && r[t].finish && r[t].finish.call(this);
                            delete n.finish
                        })
                    }
                }),
                me.each(["toggle", "show", "hide"],
                function(e, t) {
                    var n = me.fn[t];
                    me.fn[t] = function(e, r, i) {
                        return null == e || "boolean" == typeof e ? n.apply(this, arguments) : this.animate(z(t, !0), e, r, i)
                    }
                }),
                me.each({
                    slideDown: z("show"),
                    slideUp: z("hide"),
                    slideToggle: z("toggle"),
                    fadeIn: {
                        opacity: "show"
                    },
                    fadeOut: {
                        opacity: "hide"
                    },
                    fadeToggle: {
                        opacity: "toggle"
                    }
                },
                function(e, t) {
                    me.fn[e] = function(e, n, r) {
                        return this.animate(t, e, n, r)
                    }
                }),
                me.timers = [],
                me.fx.tick = function() {
                    var e, t = me.timers,
                    n = 0;
                    for (jt = me.now(); n < t.length; n++) e = t[n],
                    e() || t[n] !== e || t.splice(n--, 1);
                    t.length || me.fx.stop(),
                    jt = void 0
                },
                me.fx.timer = function(e) {
                    me.timers.push(e),
                    e() ? me.fx.start() : me.timers.pop()
                },
                me.fx.interval = 13,
                me.fx.start = function() {
                    _t || (_t = n.setInterval(me.fx.tick, me.fx.interval))
                },
                me.fx.stop = function() {
                    n.clearInterval(_t),
                    _t = null
                },
                me.fx.speeds = {
                    slow: 600,
                    fast: 200,
                    _default: 400
                },
                me.fn.delay = function(e, t) {
                    return e = me.fx ? me.fx.speeds[e] || e: e,
                    t = t || "fx",
                    this.queue(t,
                    function(t, r) {
                        var i = n.setTimeout(t, e);
                        r.stop = function() {
                            n.clearTimeout(i)
                        }
                    })
                },
                function() {
                    var e, t = se.createElement("input"),
                    n = se.createElement("div"),
                    r = se.createElement("select"),
                    i = r.appendChild(se.createElement("option"));
                    n = se.createElement("div"),
                    n.setAttribute("className", "t"),
                    n.innerHTML = "  <link/><table></table><a href='/a'>a</a><input type='checkbox'/>",
                    e = n.getElementsByTagName("a")[0],
                    t.setAttribute("type", "checkbox"),
                    n.appendChild(t),
                    e = n.getElementsByTagName("a")[0],
                    e.style.cssText = "top:1px",
                    ve.getSetAttribute = "t" !== n.className,
                    ve.style = /top/.test(e.getAttribute("style")),
                    ve.hrefNormalized = "/a" === e.getAttribute("href"),
                    ve.checkOn = !!t.value,
                    ve.optSelected = i.selected,
                    ve.enctype = !!se.createElement("form").enctype,
                    r.disabled = !0,
                    ve.optDisabled = !i.disabled,
                    t = se.createElement("input"),
                    t.setAttribute("value", ""),
                    ve.input = "" === t.getAttribute("value"),
                    t.value = "t",
                    t.setAttribute("type", "radio"),
                    ve.radioValue = "t" === t.value
                } ();
                var Ot = /\r/g,
                Mt = /[\x20\t\r\n\f]+/g;
                me.fn.extend({
                    val: function(e) {
                        var t, n, r, i = this[0]; {
                            if (arguments.length) return r = me.isFunction(e),
                            this.each(function(n) {
                                var i;
                                1 === this.nodeType && (i = r ? e.call(this, n, me(this).val()) : e, null == i ? i = "": "number" == typeof i ? i += "": me.isArray(i) && (i = me.map(i,
                                function(e) {
                                    return null == e ? "": e + ""
                                })), t = me.valHooks[this.type] || me.valHooks[this.nodeName.toLowerCase()], t && "set" in t && void 0 !== t.set(this, i, "value") || (this.value = i))
                            });
                            if (i) return t = me.valHooks[i.type] || me.valHooks[i.nodeName.toLowerCase()],
                            t && "get" in t && void 0 !== (n = t.get(i, "value")) ? n: (n = i.value, "string" == typeof n ? n.replace(Ot, "") : null == n ? "": n)
                        }
                    }
                }),
                me.extend({
                    valHooks: {
                        option: {
                            get: function(e) {
                                var t = me.find.attr(e, "value");
                                return null != t ? t: me.trim(me.text(e)).replace(Mt, " ")
                            }
                        },
                        select: {
                            get: function(e) {
                                for (var t, n, r = e.options,
                                i = e.selectedIndex,
                                o = "select-one" === e.type || i < 0,
                                a = o ? null: [], s = o ? i + 1 : r.length, u = i < 0 ? s: o ? i: 0; u < s; u++) if (n = r[u], (n.selected || u === i) && (ve.optDisabled ? !n.disabled: null === n.getAttribute("disabled")) && (!n.parentNode.disabled || !me.nodeName(n.parentNode, "optgroup"))) {
                                    if (t = me(n).val(), o) return t;
                                    a.push(t)
                                }
                                return a
                            },
                            set: function(e, t) {
                                for (var n, r, i = e.options,
                                o = me.makeArray(t), a = i.length; a--;) if (r = i[a], me.inArray(me.valHooks.option.get(r), o) > -1) try {
                                    r.selected = n = !0
                                } catch(e) {
                                    r.scrollHeight
                                } else r.selected = !1;
                                return n || (e.selectedIndex = -1),
                                i
                            }
                        }
                    }
                }),
                me.each(["radio", "checkbox"],
                function() {
                    me.valHooks[this] = {
                        set: function(e, t) {
                            if (me.isArray(t)) return e.checked = me.inArray(me(e).val(), t) > -1
                        }
                    },
                    ve.checkOn || (me.valHooks[this].get = function(e) {
                        return null === e.getAttribute("value") ? "on": e.value
                    })
                });
                var Rt, Ht, Ft = me.expr.attrHandle,
                It = /^(?:checked|selected)$/i,
                qt = ve.getSetAttribute,
                Pt = ve.input;
                me.fn.extend({
                    attr: function(e, t) {
                        return ze(this, me.attr, e, t, arguments.length > 1)
                    },
                    removeAttr: function(e) {
                        return this.each(function() {
                            me.removeAttr(this, e)
                        })
                    }
                }),
                me.extend({
                    attr: function(e, t, n) {
                        var r, i, o = e.nodeType;
                        if (3 !== o && 8 !== o && 2 !== o) return "undefined" == typeof e.getAttribute ? me.prop(e, t, n) : (1 === o && me.isXMLDoc(e) || (t = t.toLowerCase(), i = me.attrHooks[t] || (me.expr.match.bool.test(t) ? Ht: Rt)), void 0 !== n ? null === n ? void me.removeAttr(e, t) : i && "set" in i && void 0 !== (r = i.set(e, n, t)) ? r: (e.setAttribute(t, n + ""), n) : i && "get" in i && null !== (r = i.get(e, t)) ? r: (r = me.find.attr(e, t), null == r ? void 0 : r))
                    },
                    attrHooks: {
                        type: {
                            set: function(e, t) {
                                if (!ve.radioValue && "radio" === t && me.nodeName(e, "input")) {
                                    var n = e.value;
                                    return e.setAttribute("type", t),
                                    n && (e.value = n),
                                    t
                                }
                            }
                        }
                    },
                    removeAttr: function(e, t) {
                        var n, r, i = 0,
                        o = t && t.match(Oe);
                        if (o && 1 === e.nodeType) for (; n = o[i++];) r = me.propFix[n] || n,
                        me.expr.match.bool.test(n) ? Pt && qt || !It.test(n) ? e[r] = !1 : e[me.camelCase("default-" + n)] = e[r] = !1 : me.attr(e, n, ""),
                        e.removeAttribute(qt ? n: r)
                    }
                }),
                Ht = {
                    set: function(e, t, n) {
                        return t === !1 ? me.removeAttr(e, n) : Pt && qt || !It.test(n) ? e.setAttribute(!qt && me.propFix[n] || n, n) : e[me.camelCase("default-" + n)] = e[n] = !0,
                        n
                    }
                },
                me.each(me.expr.match.bool.source.match(/\w+/g),
                function(e, t) {
                    var n = Ft[t] || me.find.attr;
                    Pt && qt || !It.test(t) ? Ft[t] = function(e, t, r) {
                        var i, o;
                        return r || (o = Ft[t], Ft[t] = i, i = null != n(e, t, r) ? t.toLowerCase() : null, Ft[t] = o),
                        i
                    }: Ft[t] = function(e, t, n) {
                        if (!n) return e[me.camelCase("default-" + t)] ? t.toLowerCase() : null
                    }
                }),
                Pt && qt || (me.attrHooks.value = {
                    set: function(e, t, n) {
                        return me.nodeName(e, "input") ? void(e.defaultValue = t) : Rt && Rt.set(e, t, n)
                    }
                }),
                qt || (Rt = {
                    set: function(e, t, n) {
                        var r = e.getAttributeNode(n);
                        if (r || e.setAttributeNode(r = e.ownerDocument.createAttribute(n)), r.value = t += "", "value" === n || t === e.getAttribute(n)) return t
                    }
                },
                Ft.id = Ft.name = Ft.coords = function(e, t, n) {
                    var r;
                    if (!n) return (r = e.getAttributeNode(t)) && "" !== r.value ? r.value: null
                },
                me.valHooks.button = {
                    get: function(e, t) {
                        var n = e.getAttributeNode(t);
                        if (n && n.specified) return n.value
                    },
                    set: Rt.set
                },
                me.attrHooks.contenteditable = {
                    set: function(e, t, n) {
                        Rt.set(e, "" !== t && t, n)
                    }
                },
                me.each(["width", "height"],
                function(e, t) {
                    me.attrHooks[t] = {
                        set: function(e, n) {
                            if ("" === n) return e.setAttribute(t, "auto"),
                            n
                        }
                    }
                })),
                ve.style || (me.attrHooks.style = {
                    get: function(e) {
                        return e.style.cssText || void 0
                    },
                    set: function(e, t) {
                        return e.style.cssText = t + ""
                    }
                });
                var Bt = /^(?:input|select|textarea|button|object)$/i,
                Wt = /^(?:a|area)$/i;
                me.fn.extend({
                    prop: function(e, t) {
                        return ze(this, me.prop, e, t, arguments.length > 1)
                    },
                    removeProp: function(e) {
                        return e = me.propFix[e] || e,
                        this.each(function() {
                            try {
                                this[e] = void 0,
                                delete this[e]
                            } catch(e) {}
                        })
                    }
                }),
                me.extend({
                    prop: function(e, t, n) {
                        var r, i, o = e.nodeType;
                        if (3 !== o && 8 !== o && 2 !== o) return 1 === o && me.isXMLDoc(e) || (t = me.propFix[t] || t, i = me.propHooks[t]),
                        void 0 !== n ? i && "set" in i && void 0 !== (r = i.set(e, n, t)) ? r: e[t] = n: i && "get" in i && null !== (r = i.get(e, t)) ? r: e[t]
                    },
                    propHooks: {
                        tabIndex: {
                            get: function(e) {
                                var t = me.find.attr(e, "tabindex");
                                return t ? parseInt(t, 10) : Bt.test(e.nodeName) || Wt.test(e.nodeName) && e.href ? 0 : -1
                            }
                        }
                    },
                    propFix: {
                        for: "htmlFor",
                        class: "className"
                    }
                }),
                ve.hrefNormalized || me.each(["href", "src"],
                function(e, t) {
                    me.propHooks[t] = {
                        get: function(e) {
                            return e.getAttribute(t, 4)
                        }
                    }
                }),
                ve.optSelected || (me.propHooks.selected = {
                    get: function(e) {
                        var t = e.parentNode;
                        return t && (t.selectedIndex, t.parentNode && t.parentNode.selectedIndex),
                        null
                    },
                    set: function(e) {
                        var t = e.parentNode;
                        t && (t.selectedIndex, t.parentNode && t.parentNode.selectedIndex)
                    }
                }),
                me.each(["tabIndex", "readOnly", "maxLength", "cellSpacing", "cellPadding", "rowSpan", "colSpan", "useMap", "frameBorder", "contentEditable"],
                function() {
                    me.propFix[this.toLowerCase()] = this
                }),
                ve.enctype || (me.propFix.enctype = "encoding");
                var zt = /[\t\r\n\f]/g;
                me.fn.extend({
                    addClass: function(e) {
                        var t, n, r, i, o, a, s, u = 0;
                        if (me.isFunction(e)) return this.each(function(t) {
                            me(this).addClass(e.call(this, t, X(this)))
                        });
                        if ("string" == typeof e && e) for (t = e.match(Oe) || []; n = this[u++];) if (i = X(n), r = 1 === n.nodeType && (" " + i + " ").replace(zt, " ")) {
                            for (a = 0; o = t[a++];) r.indexOf(" " + o + " ") < 0 && (r += o + " ");
                            s = me.trim(r),
                            i !== s && me.attr(n, "class", s)
                        }
                        return this
                    },
                    removeClass: function(e) {
                        var t, n, r, i, o, a, s, u = 0;
                        if (me.isFunction(e)) return this.each(function(t) {
                            me(this).removeClass(e.call(this, t, X(this)))
                        });
                        if (!arguments.length) return this.attr("class", "");
                        if ("string" == typeof e && e) for (t = e.match(Oe) || []; n = this[u++];) if (i = X(n), r = 1 === n.nodeType && (" " + i + " ").replace(zt, " ")) {
                            for (a = 0; o = t[a++];) for (; r.indexOf(" " + o + " ") > -1;) r = r.replace(" " + o + " ", " ");
                            s = me.trim(r),
                            i !== s && me.attr(n, "class", s)
                        }
                        return this
                    },
                    toggleClass: function(e, t) {
                        var n = typeof e;
                        return "boolean" == typeof t && "string" === n ? t ? this.addClass(e) : this.removeClass(e) : me.isFunction(e) ? this.each(function(n) {
                            me(this).toggleClass(e.call(this, n, X(this), t), t)
                        }) : this.each(function() {
                            var t, r, i, o;
                            if ("string" === n) for (r = 0, i = me(this), o = e.match(Oe) || []; t = o[r++];) i.hasClass(t) ? i.removeClass(t) : i.addClass(t);
                            else void 0 !== e && "boolean" !== n || (t = X(this), t && me._data(this, "__className__", t), me.attr(this, "class", t || e === !1 ? "": me._data(this, "__className__") || ""))
                        })
                    },
                    hasClass: function(e) {
                        var t, n, r = 0;
                        for (t = " " + e + " "; n = this[r++];) if (1 === n.nodeType && (" " + X(n) + " ").replace(zt, " ").indexOf(t) > -1) return ! 0;
                        return ! 1
                    }
                }),
                me.each("blur focus focusin focusout load resize scroll unload click dblclick mousedown mouseup mousemove mouseover mouseout mouseenter mouseleave change select submit keydown keypress keyup error contextmenu".split(" "),
                function(e, t) {
                    me.fn[t] = function(e, n) {
                        return arguments.length > 0 ? this.on(t, null, e, n) : this.trigger(t)
                    }
                }),
                me.fn.extend({
                    hover: function(e, t) {
                        return this.mouseenter(e).mouseleave(t || e)
                    }
                });
                var $t = n.location,
                Ut = me.now(),
                Gt = /\?/,
                Yt = /(,)|(\[|{)|(}|])|"(?:[^"\\\r\n]|\\["\\\/bfnrt]|\\u[\da-fA-F]{4})*"\s*:?|true|false|null|-?(?!0\d)\d+(?:\.\d+|)(?:[eE][+-]?\d+|)/g;
                me.parseJSON = function(e) {
                    if (n.JSON && n.JSON.parse) return n.JSON.parse(e + "");
                    var t, r = null,
                    i = me.trim(e + "");
                    return i && !me.trim(i.replace(Yt,
                    function(e, n, i, o) {
                        return t && n && (r = 0),
                        0 === r ? e: (t = i || n, r += !o - !i, "")
                    })) ? Function("return " + i)() : me.error("Invalid JSON: " + e)
                },
                me.parseXML = function(e) {
                    var t, r;
                    if (!e || "string" != typeof e) return null;
                    try {
                        n.DOMParser ? (r = new n.DOMParser, t = r.parseFromString(e, "text/xml")) : (t = new n.ActiveXObject("Microsoft.XMLDOM"), t.async = "false", t.loadXML(e))
                    } catch(e) {
                        t = void 0
                    }
                    return t && t.documentElement && !t.getElementsByTagName("parsererror").length || me.error("Invalid XML: " + e),
                    t
                };
                var Xt = /#.*$/,
                Jt = /([?&])_=[^&]*/,
                Zt = /^(.*?):[ \t]*([^\r\n]*)\r?$/gm,
                Vt = /^(?:about|app|app-storage|.+-extension|file|res|widget):$/,
                Qt = /^(?:GET|HEAD)$/,
                Kt = /^\/\//,
                en = /^([\w.+-]+:)(?:\/\/(?:[^\/?#]*@|)([^\/?#:]*)(?::(\d+)|)|)/,
                tn = {},
                nn = {},
                rn = "*/".concat("*"),
                on = $t.href,
                an = en.exec(on.toLowerCase()) || [];
                me.extend({
                    active: 0,
                    lastModified: {},
                    etag: {},
                    ajaxSettings: {
                        url: on,
                        type: "GET",
                        isLocal: Vt.test(an[1]),
                        global: !0,
                        processData: !0,
                        async: !0,
                        contentType: "application/x-www-form-urlencoded; charset=UTF-8",
                        accepts: {
                            "*": rn,
                            text: "text/plain",
                            html: "text/html",
                            xml: "application/xml, text/xml",
                            json: "application/json, text/javascript"
                        },
                        contents: {
                            xml: /\bxml\b/,
                            html: /\bhtml/,
                            json: /\bjson\b/
                        },
                        responseFields: {
                            xml: "responseXML",
                            text: "responseText",
                            json: "responseJSON"
                        },
                        converters: {
                            "* text": String,
                            "text html": !0,
                            "text json": me.parseJSON,
                            "text xml": me.parseXML
                        },
                        flatOptions: {
                            url: !0,
                            context: !0
                        }
                    },
                    ajaxSetup: function(e, t) {
                        return t ? V(V(e, me.ajaxSettings), t) : V(me.ajaxSettings, e)
                    },
                    ajaxPrefilter: J(tn),
                    ajaxTransport: J(nn),
                    ajax: function(e, t) {
                        function r(e, t, r, i) {
                            var o, f, y, b, w, C = t;
                            2 !== x && (x = 2, u && n.clearTimeout(u), l = void 0, s = i || "", T.readyState = e > 0 ? 4 : 0, o = e >= 200 && e < 300 || 304 === e, r && (b = Q(d, T, r)), b = K(d, b, T, o), o ? (d.ifModified && (w = T.getResponseHeader("Last-Modified"), w && (me.lastModified[a] = w), w = T.getResponseHeader("etag"), w && (me.etag[a] = w)), 204 === e || "HEAD" === d.type ? C = "nocontent": 304 === e ? C = "notmodified": (C = b.state, f = b.data, y = b.error, o = !y)) : (y = C, !e && C || (C = "error", e < 0 && (e = 0))), T.status = e, T.statusText = (t || C) + "", o ? v.resolveWith(p, [f, C, T]) : v.rejectWith(p, [T, C, y]), T.statusCode(m), m = void 0, c && h.trigger(o ? "ajaxSuccess": "ajaxError", [T, d, o ? f: y]), g.fireWith(p, [T, C]), c && (h.trigger("ajaxComplete", [T, d]), --me.active || me.event.trigger("ajaxStop")))
                        }
                        "object" == typeof e && (t = e, e = void 0),
                        t = t || {};
                        var i, o, a, s, u, c, l, f, d = me.ajaxSetup({},
                        t),
                        p = d.context || d,
                        h = d.context && (p.nodeType || p.jquery) ? me(p) : me.event,
                        v = me.Deferred(),
                        g = me.Callbacks("once memory"),
                        m = d.statusCode || {},
                        y = {},
                        b = {},
                        x = 0,
                        w = "canceled",
                        T = {
                            readyState: 0,
                            getResponseHeader: function(e) {
                                var t;
                                if (2 === x) {
                                    if (!f) for (f = {}; t = Zt.exec(s);) f[t[1].toLowerCase()] = t[2];
                                    t = f[e.toLowerCase()]
                                }
                                return null == t ? null: t
                            },
                            getAllResponseHeaders: function() {
                                return 2 === x ? s: null
                            },
                            setRequestHeader: function(e, t) {
                                var n = e.toLowerCase();
                                return x || (e = b[n] = b[n] || e, y[e] = t),
                                this
                            },
                            overrideMimeType: function(e) {
                                return x || (d.mimeType = e),
                                this
                            },
                            statusCode: function(e) {
                                var t;
                                if (e) if (x < 2) for (t in e) m[t] = [m[t], e[t]];
                                else T.always(e[T.status]);
                                return this
                            },
                            abort: function(e) {
                                var t = e || w;
                                return l && l.abort(t),
                                r(0, t),
                                this
                            }
                        };
                        if (v.promise(T).complete = g.add, T.success = T.done, T.error = T.fail, d.url = ((e || d.url || on) + "").replace(Xt, "").replace(Kt, an[1] + "//"), d.type = t.method || t.type || d.method || d.type, d.dataTypes = me.trim(d.dataType || "*").toLowerCase().match(Oe) || [""], null == d.crossDomain && (i = en.exec(d.url.toLowerCase()), d.crossDomain = !(!i || i[1] === an[1] && i[2] === an[2] && (i[3] || ("http:" === i[1] ? "80": "443")) === (an[3] || ("http:" === an[1] ? "80": "443")))), d.data && d.processData && "string" != typeof d.data && (d.data = me.param(d.data, d.traditional)), Z(tn, d, t, T), 2 === x) return T;
                        c = me.event && d.global,
                        c && 0 === me.active++&&me.event.trigger("ajaxStart"),
                        d.type = d.type.toUpperCase(),
                        d.hasContent = !Qt.test(d.type),
                        a = d.url,
                        d.hasContent || (d.data && (a = d.url += (Gt.test(a) ? "&": "?") + d.data, delete d.data), d.cache === !1 && (d.url = Jt.test(a) ? a.replace(Jt, "$1_=" + Ut++) : a + (Gt.test(a) ? "&": "?") + "_=" + Ut++)),
                        d.ifModified && (me.lastModified[a] && T.setRequestHeader("If-Modified-Since", me.lastModified[a]), me.etag[a] && T.setRequestHeader("If-None-Match", me.etag[a])),
                        (d.data && d.hasContent && d.contentType !== !1 || t.contentType) && T.setRequestHeader("Content-Type", d.contentType),
                        T.setRequestHeader("Accept", d.dataTypes[0] && d.accepts[d.dataTypes[0]] ? d.accepts[d.dataTypes[0]] + ("*" !== d.dataTypes[0] ? ", " + rn + "; q=0.01": "") : d.accepts["*"]);
                        for (o in d.headers) T.setRequestHeader(o, d.headers[o]);
                        if (d.beforeSend && (d.beforeSend.call(p, T, d) === !1 || 2 === x)) return T.abort();
                        w = "abort";
                        for (o in {
                            success: 1,
                            error: 1,
                            complete: 1
                        }) T[o](d[o]);
                        if (l = Z(nn, d, t, T)) {
                            if (T.readyState = 1, c && h.trigger("ajaxSend", [T, d]), 2 === x) return T;
                            d.async && d.timeout > 0 && (u = n.setTimeout(function() {
                                T.abort("timeout")
                            },
                            d.timeout));
                            try {
                                x = 1,
                                l.send(y, r)
                            } catch(e) {
                                if (! (x < 2)) throw e;
                                r( - 1, e)
                            }
                        } else r( - 1, "No Transport");
                        return T
                    },
                    getJSON: function(e, t, n) {
                        return me.get(e, t, n, "json")
                    },
                    getScript: function(e, t) {
                        return me.get(e, void 0, t, "script")
                    }
                }),
                me.each(["get", "post"],
                function(e, t) {
                    me[t] = function(e, n, r, i) {
                        return me.isFunction(n) && (i = i || r, r = n, n = void 0),
                        me.ajax(me.extend({
                            url: e,
                            type: t,
                            dataType: i,
                            data: n,
                            success: r
                        },
                        me.isPlainObject(e) && e))
                    }
                }),
                me._evalUrl = function(e) {
                    return me.ajax({
                        url: e,
                        type: "GET",
                        dataType: "script",
                        cache: !0,
                        async: !1,
                        global: !1,
                        throws: !0
                    })
                },
                me.fn.extend({
                    wrapAll: function(e) {
                        if (me.isFunction(e)) return this.each(function(t) {
                            me(this).wrapAll(e.call(this, t))
                        });
                        if (this[0]) {
                            var t = me(e, this[0].ownerDocument).eq(0).clone(!0);
                            this[0].parentNode && t.insertBefore(this[0]),
                            t.map(function() {
                                for (var e = this; e.firstChild && 1 === e.firstChild.nodeType;) e = e.firstChild;
                                return e
                            }).append(this)
                        }
                        return this
                    },
                    wrapInner: function(e) {
                        return me.isFunction(e) ? this.each(function(t) {
                            me(this).wrapInner(e.call(this, t))
                        }) : this.each(function() {
                            var t = me(this),
                            n = t.contents();
                            n.length ? n.wrapAll(e) : t.append(e)
                        })
                    },
                    wrap: function(e) {
                        var t = me.isFunction(e);
                        return this.each(function(n) {
                            me(this).wrapAll(t ? e.call(this, n) : e)
                        })
                    },
                    unwrap: function() {
                        return this.parent().each(function() {
                            me.nodeName(this, "body") || me(this).replaceWith(this.childNodes)
                        }).end()
                    }
                }),
                me.expr.filters.hidden = function(e) {
                    return ve.reliableHiddenOffsets() ? e.offsetWidth <= 0 && e.offsetHeight <= 0 && !e.getClientRects().length: te(e)
                },
                me.expr.filters.visible = function(e) {
                    return ! me.expr.filters.hidden(e)
                };
                var sn = /%20/g,
                un = /\[\]$/,
                cn = /\r?\n/g,
                ln = /^(?:submit|button|image|reset|file)$/i,
                fn = /^(?:input|select|textarea|keygen)/i;
                me.param = function(e, t) {
                    var n, r = [],
                    i = function(e, t) {
                        t = me.isFunction(t) ? t() : null == t ? "": t,
                        r[r.length] = encodeURIComponent(e) + "=" + encodeURIComponent(t)
                    };
                    if (void 0 === t && (t = me.ajaxSettings && me.ajaxSettings.traditional), me.isArray(e) || e.jquery && !me.isPlainObject(e)) me.each(e,
                    function() {
                        i(this.name, this.value)
                    });
                    else for (n in e) ne(n, e[n], t, i);
                    return r.join("&").replace(sn, "+")
                },
                me.fn.extend({
                    serialize: function() {
                        return me.param(this.serializeArray())
                    },
                    serializeArray: function() {
                        return this.map(function() {
                            var e = me.prop(this, "elements");
                            return e ? me.makeArray(e) : this
                        }).filter(function() {
                            var e = this.type;
                            return this.name && !me(this).is(":disabled") && fn.test(this.nodeName) && !ln.test(e) && (this.checked || !$e.test(e))
                        }).map(function(e, t) {
                            var n = me(this).val();
                            return null == n ? null: me.isArray(n) ? me.map(n,
                            function(e) {
                                return {
                                    name: t.name,
                                    value: e.replace(cn, "\r\n")
                                }
                            }) : {
                                name: t.name,
                                value: n.replace(cn, "\r\n")
                            }
                        }).get()
                    }
                }),
                me.ajaxSettings.xhr = void 0 !== n.ActiveXObject ?
                function() {
                    return this.isLocal ? ie() : se.documentMode > 8 ? re() : /^(get|post|head|put|delete|options)$/i.test(this.type) && re() || ie()
                }: re;
                var dn = 0,
                pn = {},
                hn = me.ajaxSettings.xhr();
                n.attachEvent && n.attachEvent("onunload",
                function() {
                    for (var e in pn) pn[e](void 0, !0)
                }),
                ve.cors = !!hn && "withCredentials" in hn,
                hn = ve.ajax = !!hn,
                hn && me.ajaxTransport(function(e) {
                    if (!e.crossDomain || ve.cors) {
                        var t;
                        return {
                            send: function(r, i) {
                                var o, a = e.xhr(),
                                s = ++dn;
                                if (a.open(e.type, e.url, e.async, e.username, e.password), e.xhrFields) for (o in e.xhrFields) a[o] = e.xhrFields[o];
                                e.mimeType && a.overrideMimeType && a.overrideMimeType(e.mimeType),
                                e.crossDomain || r["X-Requested-With"] || (r["X-Requested-With"] = "XMLHttpRequest");
                                for (o in r) void 0 !== r[o] && a.setRequestHeader(o, r[o] + "");
                                a.send(e.hasContent && e.data || null),
                                t = function(n, r) {
                                    var o, u, c;
                                    if (t && (r || 4 === a.readyState)) if (delete pn[s], t = void 0, a.onreadystatechange = me.noop, r) 4 !== a.readyState && a.abort();
                                    else {
                                        c = {},
                                        o = a.status,
                                        "string" == typeof a.responseText && (c.text = a.responseText);
                                        try {
                                            u = a.statusText
                                        } catch(e) {
                                            u = ""
                                        }
                                        o || !e.isLocal || e.crossDomain ? 1223 === o && (o = 204) : o = c.text ? 200 : 404
                                    }
                                    c && i(o, u, c, a.getAllResponseHeaders())
                                },
                                e.async ? 4 === a.readyState ? n.setTimeout(t) : a.onreadystatechange = pn[s] = t: t()
                            },
                            abort: function() {
                                t && t(void 0, !0)
                            }
                        }
                    }
                }),
                me.ajaxSetup({
                    accepts: {
                        script: "text/javascript, application/javascript, application/ecmascript, application/x-ecmascript"
                    },
                    contents: {
                        script: /\b(?:java|ecma)script\b/
                    },
                    converters: {
                        "text script": function(e) {
                            return me.globalEval(e),
                            e
                        }
                    }
                }),
                me.ajaxPrefilter("script",
                function(e) {
                    void 0 === e.cache && (e.cache = !1),
                    e.crossDomain && (e.type = "GET", e.global = !1)
                }),
                me.ajaxTransport("script",
                function(e) {
                    if (e.crossDomain) {
                        var t, n = se.head || me("head")[0] || se.documentElement;
                        return {
                            send: function(r, i) {
                                t = se.createElement("script"),
                                t.async = !0,
                                e.scriptCharset && (t.charset = e.scriptCharset),
                                t.src = e.url,
                                t.onload = t.onreadystatechange = function(e, n) { (n || !t.readyState || /loaded|complete/.test(t.readyState)) && (t.onload = t.onreadystatechange = null, t.parentNode && t.parentNode.removeChild(t), t = null, n || i(200, "success"))
                                },
                                n.insertBefore(t, n.firstChild)
                            },
                            abort: function() {
                                t && t.onload(void 0, !0)
                            }
                        }
                    }
                });
                var vn = [],
                gn = /(=)\?(?=&|$)|\?\?/;
                me.ajaxSetup({
                    jsonp: "callback",
                    jsonpCallback: function() {
                        var e = vn.pop() || me.expando + "_" + Ut++;
                        return this[e] = !0,
                        e
                    }
                }),
                me.ajaxPrefilter("json jsonp",
                function(e, t, r) {
                    var i, o, a, s = e.jsonp !== !1 && (gn.test(e.url) ? "url": "string" == typeof e.data && 0 === (e.contentType || "").indexOf("application/x-www-form-urlencoded") && gn.test(e.data) && "data");
                    if (s || "jsonp" === e.dataTypes[0]) return i = e.jsonpCallback = me.isFunction(e.jsonpCallback) ? e.jsonpCallback() : e.jsonpCallback,
                    s ? e[s] = e[s].replace(gn, "$1" + i) : e.jsonp !== !1 && (e.url += (Gt.test(e.url) ? "&": "?") + e.jsonp + "=" + i),
                    e.converters["script json"] = function() {
                        return a || me.error(i + " was not called"),
                        a[0]
                    },
                    e.dataTypes[0] = "json",
                    o = n[i],
                    n[i] = function() {
                        a = arguments
                    },
                    r.always(function() {
                        void 0 === o ? me(n).removeProp(i) : n[i] = o,
                        e[i] && (e.jsonpCallback = t.jsonpCallback, vn.push(i)),
                        a && me.isFunction(o) && o(a[0]),
                        a = o = void 0
                    }),
                    "script"
                }),
                me.parseHTML = function(e, t, n) {
                    if (!e || "string" != typeof e) return null;
                    "boolean" == typeof t && (n = t, t = !1),
                    t = t || se;
                    var r = Ee.exec(e),
                    i = !n && [];
                    return r ? [t.createElement(r[1])] : (r = w([e], t, i), i && i.length && me(i).remove(), me.merge([], r.childNodes))
                };
                var mn = me.fn.load;
                me.fn.load = function(e, t, n) {
                    if ("string" != typeof e && mn) return mn.apply(this, arguments);
                    var r, i, o, a = this,
                    s = e.indexOf(" ");
                    return s > -1 && (r = me.trim(e.slice(s, e.length)), e = e.slice(0, s)),
                    me.isFunction(t) ? (n = t, t = void 0) : t && "object" == typeof t && (i = "POST"),
                    a.length > 0 && me.ajax({
                        url: e,
                        type: i || "GET",
                        dataType: "html",
                        data: t
                    }).done(function(e) {
                        o = arguments,
                        a.html(r ? me("<div>").append(me.parseHTML(e)).find(r) : e)
                    }).always(n &&
                    function(e, t) {
                        a.each(function() {
                            n.apply(this, o || [e.responseText, t, e])
                        })
                    }),
                    this
                },
                me.each(["ajaxStart", "ajaxStop", "ajaxComplete", "ajaxError", "ajaxSuccess", "ajaxSend"],
                function(e, t) {
                    me.fn[t] = function(e) {
                        return this.on(t, e)
                    }
                }),
                me.expr.filters.animated = function(e) {
                    return me.grep(me.timers,
                    function(t) {
                        return e === t.elem
                    }).length
                },
                me.offset = {
                    setOffset: function(e, t, n) {
                        var r, i, o, a, s, u, c, l = me.css(e, "position"),
                        f = me(e),
                        d = {};
                        "static" === l && (e.style.position = "relative"),
                        s = f.offset(),
                        o = me.css(e, "top"),
                        u = me.css(e, "left"),
                        c = ("absolute" === l || "fixed" === l) && me.inArray("auto", [o, u]) > -1,
                        c ? (r = f.position(), a = r.top, i = r.left) : (a = parseFloat(o) || 0, i = parseFloat(u) || 0),
                        me.isFunction(t) && (t = t.call(e, n, me.extend({},
                        s))),
                        null != t.top && (d.top = t.top - s.top + a),
                        null != t.left && (d.left = t.left - s.left + i),
                        "using" in t ? t.using.call(e, d) : f.css(d)
                    }
                },
                me.fn.extend({
                    offset: function(e) {
                        if (arguments.length) return void 0 === e ? this: this.each(function(t) {
                            me.offset.setOffset(this, e, t)
                        });
                        var t, n, r = {
                            top: 0,
                            left: 0
                        },
                        i = this[0],
                        o = i && i.ownerDocument;
                        if (o) return t = o.documentElement,
                        me.contains(t, i) ? ("undefined" != typeof i.getBoundingClientRect && (r = i.getBoundingClientRect()), n = oe(o), {
                            top: r.top + (n.pageYOffset || t.scrollTop) - (t.clientTop || 0),
                            left: r.left + (n.pageXOffset || t.scrollLeft) - (t.clientLeft || 0)
                        }) : r
                    },
                    position: function() {
                        if (this[0]) {
                            var e, t, n = {
                                top: 0,
                                left: 0
                            },
                            r = this[0];
                            return "fixed" === me.css(r, "position") ? t = r.getBoundingClientRect() : (e = this.offsetParent(), t = this.offset(), me.nodeName(e[0], "html") || (n = e.offset()), n.top += me.css(e[0], "borderTopWidth", !0), n.left += me.css(e[0], "borderLeftWidth", !0)),
                            {
                                top: t.top - n.top - me.css(r, "marginTop", !0),
                                left: t.left - n.left - me.css(r, "marginLeft", !0)
                            }
                        }
                    },
                    offsetParent: function() {
                        return this.map(function() {
                            for (var e = this.offsetParent; e && !me.nodeName(e, "html") && "static" === me.css(e, "position");) e = e.offsetParent;
                            return e || mt
                        })
                    }
                }),
                me.each({
                    scrollLeft: "pageXOffset",
                    scrollTop: "pageYOffset"
                },
                function(e, t) {
                    var n = /Y/.test(t);
                    me.fn[e] = function(r) {
                        return ze(this,
                        function(e, r, i) {
                            var o = oe(e);
                            return void 0 === i ? o ? t in o ? o[t] : o.document.documentElement[r] : e[r] : void(o ? o.scrollTo(n ? me(o).scrollLeft() : i, n ? i: me(o).scrollTop()) : e[r] = i)
                        },
                        e, r, arguments.length, null)
                    }
                }),
                me.each(["top", "left"],
                function(e, t) {
                    me.cssHooks[t] = R(ve.pixelPosition,
                    function(e, n) {
                        if (n) return n = bt(e, t),
                        vt.test(n) ? me(e).position()[t] + "px": n
                    })
                }),
                me.each({
                    Height: "height",
                    Width: "width"
                },
                function(e, t) {
                    me.each({
                        padding: "inner" + e,
                        content: t,
                        "": "outer" + e
                    },
                    function(n, r) {
                        me.fn[r] = function(r, i) {
                            var o = arguments.length && (n || "boolean" != typeof r),
                            a = n || (r === !0 || i === !0 ? "margin": "border");
                            return ze(this,
                            function(t, n, r) {
                                var i;
                                return me.isWindow(t) ? t.document.documentElement["client" + e] : 9 === t.nodeType ? (i = t.documentElement, Math.max(t.body["scroll" + e], i["scroll" + e], t.body["offset" + e], i["offset" + e], i["client" + e])) : void 0 === r ? me.css(t, n, a) : me.style(t, n, r, a)
                            },
                            t, o ? r: void 0, o, null)
                        }
                    })
                }),
                me.fn.extend({
                    bind: function(e, t, n) {
                        return this.on(e, null, t, n)
                    },
                    unbind: function(e, t) {
                        return this.off(e, null, t)
                    },
                    delegate: function(e, t, n, r) {
                        return this.on(t, e, n, r)
                    },
                    undelegate: function(e, t, n) {
                        return 1 === arguments.length ? this.off(e, "**") : this.off(t, e || "**", n)
                    }
                }),
                me.fn.size = function() {
                    return this.length
                },
                me.fn.andSelf = me.fn.addBack,
                r = [],
                i = function() {
                    return me
                }.apply(t, r),
                !(void 0 !== i && (e.exports = i));
                var yn = n.jQuery,
                bn = n.$;
                return me.noConflict = function(e) {
                    return n.$ === me && (n.$ = bn),
                    e && n.jQuery === me && (n.jQuery = yn),
                    me
                },
                o || (n.jQuery = n.$ = me),
                me
            })
        },
        AxIY: function(e, t, n) {
            function r(e, t) {
                function n() {
                    c = c.onload = c.onerror = null
                }
                var r = s[o];
                if (r && e) {
                    var i = [];
                    for (var u in e) i.push(u + "=" + (e[u] || ""));
                    r = "https://" + r + a + "?" + i.join("&");
                    var c = new Image;
                    t || (t = $.noop),
                    c.onerror = function(e) {
                        t(new Error("Cgi error")),
                        n()
                    },
                    c.onload = function(e) {
                        t(),
                        n()
                    },
                    c.src = r
                }
            }
            var i = n("YHhD"),
            o = location.hostname,
            a = "/services/sync/cookie",
            s = {
                "cloud.tencent.com": "www.qcloud.com",
                "www.qcloud.com": "cloud.tencent.com",
                "intl.cloud.tencent.com": "www.qcloud.com"
            };
            e.exports = {
                syncCookie: r,
                get: function(e) {
                    return i.get(e)
                },
                set: function(e, t, n) {
                    if (n || (n = {}), n.domain || (o.indexOf("cloud.tencent.com") != -1 ? n.domain = ".cloud.tencent.com": "www.qcloud.com" === o && (n.domain = ".qcloud.com")), n.path || (n.path = "/"), n.sync) {
                        var a = {};
                        a[e] = t,
                        r(a, n.syncCallback)
                    }
                    return i.set(e, t, n)
                },
                remove: function(e, t) {
                    if (t || (t = {}), t.domain || (o.indexOf("cloud.tencent.com") != -1 ? t.domain = ".cloud.tencent.com": "www.qcloud.com" === o && (t.domain = ".qcloud.com")), t.path || (t.path = "/"), t.sync) {
                        var n = {};
                        n[e] = "",
                        r(n)
                    }
                    return i.remove(e, t)
                }
            }
        },
        YHhD: function(e, t, n) {
            function r(e) {
                return a.get(e)
            }
            function i(e, t, n) {
                return a.set(e, t, n)
            }
            function o(e, t) {
                return a.remove(e, t)
            }
            var a = n("WPtr"),
            s = {
                getCookie: r,
                setCookie: function(e, t, n, r, o, a) {
                    return i(e, t, {
                        expires: parseInt(n) / 60 * 24,
                        path: r,
                        domain: o,
                        secure: a
                    })
                },
                delCookie: function(e, t, n, r) {
                    return o(e, {
                        path: t,
                        domain: n,
                        secure: r
                    })
                },
                get: r,
                set: i,
                remove: o
            };
            e.exports = s
        },
        "+cXR": function(e, t, n) { (function(t) {
                function n(e, t) {
                    for (var n = t.split("."), r = e, i = n.length, o = 0; o < i; o += 1)"undefined" == typeof r[n[o]] && (r[n[o]] = {}),
                    r = r[n[o]];
                    return r
                }
                e.exports = n(t, "qcloud.main")
            }).call(t,
            function() {
                return this
            } ())
        },
        "4Ahm": function(e, t, n) {
            function r(e) {
                var t = e.match(u) || [],
                n = t[0],
                r = t[1] ? t[1] + ":": "",
                i = t[3],
                o = t[4] || "",
                a = "/" + (t[5] || ""),
                s = t[6],
                c = t[7] ? "#" + t[7] : "",
                l = {};
                return s && s.split("&").forEach(function(e) {
                    var t = e.split("=");
                    l[t[0]] = decodeURIComponent(t[1])
                }),
                {
                    href: n,
                    protocol: r,
                    origin: r + "//" + i + (o ? ":" + o: ""),
                    host: i + (o ? ":" + o: ""),
                    hostname: i,
                    port: o,
                    pathname: a,
                    search: s ? "?" + s: "",
                    hash: c,
                    query: l
                }
            }
            function i(e, t, n, r) {
                function i() { (r ? a >= 0 : a > 0) ? (t(a), a--) : (clearInterval(o), n())
                }
                var o, a = Math.round(e);
                return "undefined" == typeof r && (r = !0),
                o = setInterval(function() {
                    i(a)
                },
                1e3),
                i(),
                {
                    abort: function() {
                        clearInterval(o)
                    },
                    getRemainingTime: function() {
                        return a
                    }
                }
            }
            function o(e) {
                if (e = "" + e, /^\d{5,}$/.test(e)) {
                    var t = e.slice(0, 2),
                    n = e.slice(2, -2),
                    r = e.slice( - 2);
                    n = n.length <= 4 ? "****".slice(0, n.length) : n.slice(0, Math.floor(n.length / 2) - 2) + "****" + n.slice(Math.floor(n.length / 2) + 2),
                    e = t + n + r
                }
                return e
            }
            function a(e) {
                if (e.indexOf("*") != -1) return e;
                var t = e.split("@");
                if (2 != t.length) return e;
                var n = t[0],
                r = t[1];
                return n = n.length > 4 ? n.slice(0, -4) + "****": n.slice(0, 1) + "****",
                n + "@" + r
            }
            var s = n(1);
            n(2); !
            function() {
                var e, t;
                s.uaMatch = function(e) {
                    e = e.toLowerCase();
                    var t = /(chrome)[ \/]([\w.]+)/.exec(e) || /(webkit)[ \/]([\w.]+)/.exec(e) || /(opera)(?:.*version|)[ \/]([\w.]+)/.exec(e) || /(msie) ([\w.]+)/.exec(e) || e.indexOf("compatible") < 0 && /(mozilla)(?:.*? rv:([\w.]+)|)/.exec(e) || [];
                    return {
                        browser: t[1] || "",
                        version: t[2] || "0"
                    }
                },
                s.browser || (e = s.uaMatch(navigator.userAgent), t = {},
                e.browser && (t[e.browser] = !0, t.version = e.version), t.chrome ? t.webkit = !0 : t.webkit && (t.safari = !0), s.browser = t)
            } ();
            var u = /^(?:([A-Za-z]+):)?(\/{0,3})([0-9.\-A-Za-z]+)(?::(\d+))?(?:\/([^?#]*))?(?:\?([^#]*))?(?:#(.*))?$/,
            c = {
                name: "util",
                countdown: i,
                url: {
                    parse: r
                },
                maskPhoneNumber: o,
                maskMail: a
            };
            e.exports = c
        },
        1 : function(e, t) {
            e.exports = jQuery
        },
        2 : function(e, t) {
            e.exports = _
        },
        "94Dj": function(e, t, n) {
            var r = n(1),
            i = n(2),
            o = n("x0+R"),
            a = {
                name: "net",
                get: function(e, t, n) {
                    return n = r.extend({},
                    n, {
                        method: "GET"
                    }),
                    this._send(e, r.param(t || {}), n)
                },
                post: function(e, t, n) {
                    return n = r.extend({},
                    n, {
                        method: "POST"
                    }),
                    this._send(e, r.param(t || {}), n)
                },
                jsonp: function(e, t, n) {
                    return n = r.extend({},
                    n, {
                        method: "GET",
                        dataType: "jsonp",
                        jsonp: "callback"
                    }),
                    this._send(e, r.param(t || {}), n)
                },
                json: function(e, t, n) {
                    return n = r.extend({},
                    n, {
                        method: "POST",
                        contentType: "application/json; charset=UTF-8"
                    }),
                    this._send(e, JSON.stringify(t || {}), n)
                },
                _send: function(e, t, n) {
                    var i = this,
                    a = {};
                    return o.getUin() && (a.uin = o.getUin()),
                    o.getCsrfCode() && (a.csrfCode = o.getCsrfCode()),
                    n = r.extend({
                        splitFlowByCode: !0
                    },
                    n),
                    r.ajax({
                        url: this._addParam(e, a),
                        data: t,
                        method: n.method,
                        dataType: n.dataType,
                        contentType: n.contentType,
                        jsonp: n.jsonp,
                        processData: !1,
                        cache: !1,
                        timeout: n.timeout || 2e4
                    }).then(function(e) {
                        return r.isPlainObject(e) && "code" in e || (e = {
                            code: "oops",
                            msg: "invalid response"
                        }),
                        e
                    },
                    function(e, t, n) {
                        var o;
                        return o = "timeout" === t ? i._timeout() : i._error(),
                        r.Deferred().resolve(o)
                    }).then(function(e) {
                        return e || (e = {}),
                        n.splitFlowByCode ? 0 === e.code ? e.data: r.Deferred().reject(e) : e
                    })
                },
                _addParam: function(e, t) {
                    if (i.isEmpty(t)) return e;
                    var n = ~e.indexOf("?") ? "&": "?";
                    return e + n + r.param(t)
                },
                _error: function() {
                    return {
                        code: 1,
                        type: "CONNECT_ERROR",
                        msg: "\u8fde\u63a5\u670d\u52a1\u5668\u5f02\u5e38\uff0c\u8bf7\u7a0d\u540e\u518d\u8bd5"
                    }
                },
                _timeout: function() {
                    return {
                        code: 2,
                        type: "CONNECT_TIMEOUT",
                        msg: "\u8fde\u63a5\u670d\u52a1\u5668\u8d85\u65f6\uff0c\u8bf7\u7a0d\u540e\u518d\u8bd5"
                    }
                }
            };
            e.exports = a
        },
        "x0+R": function(e, t, n) {
            var r = n("+cXR").cookie,
            i = {
                _uin: "",
                _csrfCode: "",
                init: function() {
                    return this.updateUin(),
                    this.updateCsrfCode(),
                    this
                },
                getUin: function() {
                    return this._uin
                },
                getCsrfCode: function() {
                    return this._csrfCode
                },
                updateUin: function() {
                    var e = r.get("uin") || "";
                    this._uin = e.replace(/^o0*/, "")
                },
                updateCsrfCode: function() {
                    var e = r.get("skey") || r.get("p_skey");
                    if (e) {
                        for (var t = 5381,
                        n = 0,
                        i = e.length; n < i; n += 1) t += (t << 5) + e.charCodeAt(n);
                        this._csrfCode = 2147483647 & t
                    } else this._csrfCode = ""
                },
                showLoginBox: function(e) {
                    location.href = "/login?s_url=" + encodeURIComponent(location.href)
                }
            };
            e.exports = i.init()
        },
        ulYS: function(e, t) {
            function n() {
                for (var e = navigator.userAgent.toLowerCase(), t = ["android", "ipad", "iphone", "windows phone"], n = 0; n < t.length; n++) if (e.indexOf(t[n]) !== -1) return ! 0;
                return ! 1
            }
            function r() {
                this.init()
            }
            var i = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAB4AAAAdAQMAAACHak5PAAAABGdBTUEAALGPC/xhBQAAAAFzUkdCAK7OHOkAAAMhaVRYdFhNTDpjb20uYWRvYmUueG1wAAAAAAA8P3hwYWNrZXQgYmVnaW49Iu+7vyIgaWQ9Ilc1TTBNcENlaGlIenJlU3pOVGN6a2M5ZCI/PiA8eDp4bXBtZXRhIHhtbG5zOng9ImFkb2JlOm5zOm1ldGEvIiB4OnhtcHRrPSJBZG9iZSBYTVAgQ29yZSA1LjUtYzAxNCA3OS4xNTE0ODEsIDIwMTMvMDMvMTMtMTI6MDk6MTUgICAgICAgICI+IDxyZGY6UkRGIHhtbG5zOnJkZj0iaHR0cDovL3d3dy53My5vcmcvMTk5OS8wMi8yMi1yZGYtc3ludGF4LW5zIyI+IDxyZGY6RGVzY3JpcHRpb24gcmRmOmFib3V0PSIiIHhtbG5zOnhtcD0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wLyIgeG1sbnM6eG1wTU09Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9tbS8iIHhtbG5zOnN0UmVmPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvc1R5cGUvUmVzb3VyY2VSZWYjIiB4bXA6Q3JlYXRvclRvb2w9IkFkb2JlIFBob3Rvc2hvcCBDQyAoV2luZG93cykiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6RTZGNUI3QjUxQjM0MTFFNjg4OEQ5NEZEMTdEMjdGODEiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6RTZGNUI3QjYxQjM0MTFFNjg4OEQ5NEZEMTdEMjdGODEiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDpFNkY1QjdCMzFCMzQxMUU2ODg4RDk0RkQxN0QyN0Y4MSIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDpFNkY1QjdCNDFCMzQxMUU2ODg4RDk0RkQxN0QyN0Y4MSIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/Pn3wzuAAAAAZdEVYdFNvZnR3YXJlAEFkb2JlIEltYWdlUmVhZHlxyWU8AAAABlBMVEVMaXH///+a4ocPAAAAAXRSTlMAQObYZgAAAFRJREFUCNctjtsNwCAMA6+qRH8yAGMwEqMwGqP1sBAREMePsKGY0BlYHZuCTeNZvB7fhogo0zlDJl0NpZpveUm7v2CZhhfF0cYlfnFORtKSmw3OLj8z3g7aGBtSgwAAAABJRU5ErkJggi8qICB8eEd2MDB8Y2RlM2JmZjA5NGZkNGRmOWRjMjJhNWI5MGM5Mjc2ODIgKi8=",
            o = {
                wrapper: '<div role="video-player" style="display: none;"><div style="<%= styles.mask %>"><a href="javascript:;" class="J-close-btn" style="<%= styles.closeBtn %>"></a></div><div class="J-videoWrapper"></div></div>',
                video: '<div style="<%= styles.videoWrapper %> width: <%= width %>px; height: <%= height %>px;margin-top: -<%= marginTop %>px; margin-left: -<%= marginLeft %>px;"><video class="J-videoPlayer" style="background-color: #000;" width="<%= width %>" height="<%= height %>" <%- mode %> src="<%= src %>">\u5f53\u524d\u6d4f\u89c8\u5668\u4e0d\u80fd\u652f\u6301\u89c6\u9891\u64ad\u653e\uff0c\u8bf7\u91c7\u7528chrome\u6216IE9\u4ee5\u4e0a\u6d4f\u89c8\u5668</video><div class="emod-video-loading J-videoLoading" style="<%= styles.loading %>">\u89c6\u9891\u8f7d\u5165\u4e2d...</div></div>'
            },
            a = {
                mask: "position: fixed;top: 0;left: 0;bottom: 0;right: 0;width: 100%;z-index: 10001;height: 100%;background: rgba(0,0,0,.8);",
                closeBtn: "display: inline-block;width: 30px;height: 29px;background-image: url(" + i + ");background-repeat: no-repeat;position: absolute;top: 50px;right: 50px;",
                videoWrapper: "position: fixed;top: 50%;left: 50%;z-index: 10002;",
                loading: "width: 100%;height: 100%;position: absolute;left: 0;top: 0;opacity: 0.8;color: rgb(255, 255, 255);text-align: center;line-height: 500px;font-size: 16px;background-color: rgb(0, 0, 0);"
            };
            r.prototype = {
                constructor: r,
                $wrapper: null,
                init: function() {
                    var e = {
                        styles: a
                    },
                    t = _.template(o.wrapper, {}),
                    n = this.$wrapper = $(t(e));
                    n.appendTo("body"),
                    this.bindEvent()
                },
                bindEvent: function() {
                    var e = this;
                    this.$wrapper.on("click", ".J-close-btn",
                    function() {
                        e.hide()
                    })
                },
                show: function(e) {
                    var t = $.extend({
                        src: "",
                        controls: !0,
                        loop: !1,
                        autoplay: !0,
                        width: 700,
                        height: 500,
                        mode: [],
                        styles: a
                    },
                    e);
                    t.controls && t.mode.push("controls"),
                    t.loop && t.mode.push("loop"),
                    t.autoplay && t.mode.push("autoplay"),
                    t.marginLeft = t.width / 2,
                    t.marginTop = t.height / 2,
                    t.mode = t.mode.join(" ");
                    var r = _.template(o.video, {}),
                    i = this.$wrapper.find(".J-videoWrapper");
                    i.html(r(t));
                    var s = i.find(".J-videoLoading"),
                    u = i.find(".J-videoPlayer");
                    u.one("play",
                    function() {
                        s.hide(),
                        $(this).off("error")
                    }).one("error",
                    function() {
                        s.text("\u64ad\u653e\u51fa\u9519\u4e86\uff0c\u8bf7\u7a0d\u540e\u91cd\u8bd5"),
                        $(this).off("play")
                    }),
                    n() && (u.off("play").off("error"), s.hide()),
                    t.autoplay || (u.off("play"), s.hide()),
                    this.$wrapper.show()
                },
                hide: function() {
                    var e = this.$wrapper.find(".J-videoWrapper .J-videoPlayer").get(0);
                    e.src = "",
                    e.load && e.load(),
                    this.$wrapper.hide()
                }
            };
            var s = function() {
                var e = null;
                return function() {
                    return e || (e = new r),
                    e
                }
            } ();
            $.fn.videoPlayer = function(e) {
                var t = arguments;
                return this.each(function() {
                    function n() {
                        i.show()
                    }
                    var r = $(this),
                    i = r.data("videoPlayer");
                    if ("string" == typeof e) {
                        if (!i) return void console.warn("Video player has been destroyed or hasn't initialize yet!");
                        "function" == typeof i[e] && (t = Array.prototype.slice.call(t), t.splice(0, 1), i[e].apply(i, t))
                    } else {
                        var o = "";
                        if (! (o = r.data("src") || e.src)) return void console.warn("You need set data-src attribute to initialize video player!");
                        if (e = $.extend({},
                        e, {
                            src: o
                        }), !i) {
                            var a = s();
                            i = {
                                show: a.show.bind(a, e),
                                hide: a.hide,
                                destroy: function() {
                                    r.off("click"),
                                    r.data("videoPlayer", null)
                                }
                            },
                            r.data("videoPlayer", i)
                        }
                        r.on("click", n)
                    }
                })
            },
            e.exports = {
                init: s
            }
        },
        WPtr: function(e, t, n) {
            var r, i; !
            function(o) {
                r = o,
                i = "function" == typeof r ? r.call(t, n, t, e) : r,
                !(void 0 !== i && (e.exports = i))
            } (function() {
                function e() {
                    for (var e = 0,
                    t = {}; e < arguments.length; e++) {
                        var n = arguments[e];
                        for (var r in n) t[r] = n[r]
                    }
                    return t
                }
                function t(n) {
                    function r(t, i, o) {
                        var a;
                        if ("undefined" != typeof document) {
                            if (arguments.length > 1) {
                                if (o = e({
                                    path: "/"
                                },
                                r.defaults, o), "number" == typeof o.expires) {
                                    var s = new Date;
                                    s.setMilliseconds(s.getMilliseconds() + 864e5 * o.expires),
                                    o.expires = s
                                }
                                try {
                                    a = JSON.stringify(i),
                                    /^[\{\[]/.test(a) && (i = a)
                                } catch(e) {}
                                return i = n.write ? n.write(i, t) : encodeURIComponent(String(i)).replace(/%(23|24|26|2B|3A|3C|3E|3D|2F|3F|40|5B|5D|5E|60|7B|7D|7C)/g, decodeURIComponent),
                                t = encodeURIComponent(String(t)),
                                t = t.replace(/%(23|24|26|2B|5E|60|7C)/g, decodeURIComponent),
                                t = t.replace(/[\(\)]/g, escape),
                                document.cookie = [t, "=", i, o.expires && "; expires=" + o.expires.toUTCString(), o.path && "; path=" + o.path, o.domain && "; domain=" + o.domain, o.secure ? "; secure": ""].join("")
                            }
                            t || (a = {});
                            for (var u = document.cookie ? document.cookie.split("; ") : [], c = /(%[0-9A-Z]{2})+/g, l = 0; l < u.length; l++) {
                                var f = u[l].split("="),
                                d = f.slice(1).join("=");
                                '"' === d.charAt(0) && (d = d.slice(1, -1));
                                try {
                                    var p = f[0].replace(c, decodeURIComponent);
                                    if (d = n.read ? n.read(d, p) : n(d, p) || d.replace(c, decodeURIComponent), this.json) try {
                                        d = JSON.parse(d)
                                    } catch(e) {}
                                    if (t === p) {
                                        a = d;
                                        break
                                    }
                                    t || (a[p] = d)
                                } catch(e) {}
                            }
                            return a
                        }
                    }
                    return r.set = r,
                    r.get = function(e) {
                        return r(e)
                    },
                    r.getJSON = function() {
                        return r.apply({
                            json: !0
                        },
                        [].slice.call(arguments))
                    },
                    r.defaults = {},
                    r.remove = function(t, n) {
                        r(t, "", e(n, {
                            expires: -1
                        }))
                    },
                    r.withConverter = t,
                    r
                }
                return t(function() {})
            })
        }
    });
});