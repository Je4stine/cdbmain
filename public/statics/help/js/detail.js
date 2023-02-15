!function(e) {
    function t(i) {
        if (o[i]) return o[i].exports;
        var n = o[i] = {
            exports: {},
            id: i,
            loaded: !1
        };
        return e[i].call(n.exports, n, n.exports, t),
        n.loaded = !0,
        n.exports
    }
    var o = {};
    return t.m = e,
    t.c = o,
    t.p = "//imgcache.qq.com/qcloud/main/scripts/",
    t(0)
} ({
    0 : function(e, t, o) {
        "use strict";
        function i(e, t) {
            if (! (e instanceof t)) throw new TypeError("Cannot call a class as a function")
        }
        function n(e) {
            return e.filter(function(e) {
                return !! e
            }).reduce(function(e, t) {
                return e.then(function() {
                    return s(t)
                })
            },
            Promise.resolve())
        }
        function s(e) {
            return new Promise(function(t, o) {
                $.getScript(e).done(function(e) {
                    return t(e)
                }).fail(function(e) {
                    return o(e)
                })
            })
        }
        function a() {
            var e = $(".J-navCateType").html();
            $(".J-sideBarPopTrigger").find("li>a").each(function(t, o) {
                e === o.innerHTML && $(o).parent().remove()
            }),
            $(".J-sideBarPopTrigger").find(".nav-down-menu").each(function(e, t) {
                var o = $(t);
                o.children().length <= 1 && o.remove()
            })
        }
        function r() {
            var e = $(".J-stickyNode");
            if (0 != e.length) {
                var t = $(this),
                o = $(".J-docSideNav"),
                i = $(".J-sideNavInnerBox"),
                n = $(".J-sideNavDownBox"),
                s = t.scrollTop(),
                a = $("#navigationBar").outerHeight() + $(".J-documentCenterSearch").outerHeight(),
                r = e.offset().left + e.width(),
                d = $(".J-mainDetail").width() + parseInt($(".J-mainDetail").css("padding-left"));
                if (n.css({
                    position: "fixed",
                    left: r,
                    width: d
                }), s >= a) {
                    var l = e.width();
                    e.css("position", "fixed"),
                    e.css("width", l),
                    e.css("top", 0),
                    n.css("top", 0)
                } else {
                    var u = o.offset().top - s;
                    n.css("top", u),
                    e.css("position", ""),
                    e.css("width", ""),
                    e.css("top", "")
                }
                c(),
                h(),
                "true" === i.attr("data-needFixFocusElePosition") && p(!1)
            }
        }
        function c() {
            var e = $(".J-docSideNav").offset().top,
            t = b.height() - k,
            o = b.scrollTop();
            e > o && (t -= e - o),
            $(".J-sideNavDownBox").css("max-height", t + "px")
        }
        function h() {
            var e, t = $(".J-docSideNav").offset().top,
            o = b.scrollTop(),
            i = $(".J-qc-footer").offset().top,
            n = $(".J-sideBarPopTrigger").outerHeight(),
            s = b.height();
            e = i - o - (T - k) < s ? t > o ? i - t - T - n: i - o - T - n: t > o ? s - (t - o) - n - k: s - n - k,
            $(".J-sideNavInnerBox").css("height", e + "px")
        }
        function d() {
            var e, t = $(this),
            o = $(".J-docSideNav"),
            i = t.scrollTop(),
            n = $(".J-subHeader"),
            s = $(".J-sideNavInnerBox"),
            a = $("#navigationBar").height() || $("#qc-navigation-mobile").height(),
            r = b.height() - n.outerHeight();
            i <= a ? (e = a - i, r -= e, n.css("position", "absolute"), n.css("top", ""), o.hasClass("opened") ? (o.removeClass("fixed"), s.css("height", r)) : o.hasClass("show") && o.removeClass("fixed")) : (n.css("position", "fixed"), n.css("top", 0), s.css("height", r), (o.hasClass("opened") || o.hasClass("show")) && o.addClass("fixed")),
            $(".J-sideNavDownBox").height(r),
            $(".J-stickyNode").css("max-width", "345px")
        }
        function l() {
            x.on("click", ".J-notice-close-btn",
            function() {
                return x.html("")
            }),
            y.on("click", ".J-product-doc-video",
            function() {
                var e = $(this).data("src");
                e && J.show({
                    src: e
                })
            }),
            y.on({
                mouseenter: function(e) {
                    if (b.outerWidth() > 768) {
                        var t = $(".J-sideNavDownBox");
                        clearTimeout(t.data("triggerTimer")),
                        clearTimeout(t.data("dismissTimer"));
                        var o = setTimeout(function() {
                            t.removeData("triggerTimer").addClass("hover"),
                            a(),
                            t.show().scrollTop(0)
                        },
                        200);
                        t.data("triggerTimer", o)
                    }
                },
                mouseleave: function() {
                    if (b.outerWidth() > 768) {
                        var e = $(".J-sideNavDownBox");
                        e.hasClass("hover") || clearTimeout(e.data("triggerTimer"));
                        var t = setTimeout(function() {
                            clearTimeout(e.data("triggerTimer")),
                            e.removeClass("hover"),
                            e.hide()
                        },
                        300);
                        e.data("dismissTimer", t)
                    }
                }
            },
            ".J-sideBarPopTrigger, .J-sideNavDownBox"),
            y.on("click", ".J-navLayer",
            function() {
                function e() {
                    t.toggleClass("down")
                }
                var t = $(this);
                t.is(":animated") || (t.hasClass("down") ? t.next().slideUp(100, e) : t.next().slideDown(0, e))
            }),
            y.on("click.J-phoneMenu", ".J-phoneMenu",
            function() {
                var e = $(".J-docSideNav"),
                t = $(".J-stickyNode"),
                o = $(".J-sideNavInnerBox"),
                i = $("#navigationBar").height() || $("#qc-navigation-mobile").height(),
                n = $(".J-subHeader"),
                s = n.outerHeight(),
                a = b.scrollTop(),
                r = b.height(),
                c = r - s;
                navigator.userAgent.toLowerCase();
                if (e.hasClass("show") && $(".J-middle-down").trigger("click"), e.toggleClass("opened"), e.hasClass("opened")) {
                    var h;
                    a < i ? (h = i - a, e.removeClass("fixed")) : (h = 0, e.addClass("fixed")),
                    c -= h,
                    o.css("height", c),
                    t.css("transition", "max-width 300ms"),
                    setTimeout(function() {
                        t.css("max-width", 345)
                    }),
                    $(".J-modal").show()
                } else t.css("transition", "inherit").css("max-width", 0),
                $(".J-modal").hide()
            }),
            y.on("click", ".J-middle-down",
            function() {
                var e = $(this),
                t = $(".J-docSideNav"),
                o = $(".J-sideNavDownBox"),
                i = b.scrollTop(),
                n = $("#navigationBar").height() || $("#qc-navigation-mobile").height(),
                s = $(".J-subHeader").outerHeight(),
                r = b.height(),
                c = r - s;
                if (t.hasClass("opened") && $(".J-phoneMenu").trigger("click"), e.toggleClass("cur"), e.hasClass("cur")) {
                    if (a(), o.find("ul").length <= 0) return void e.toggleClass("cur");
                    t.addClass("show"),
                    i <= n ? t.removeClass("fixed") : t.addClass("fixed"),
                    o.css("height", c),
                    o.show(),
                    $(".J-modal").show()
                } else t.removeClass("show"),
                o.css("height", ""),
                o.hide(),
                $(".J-modal").hide()
            }),
            y.on("click", ".J-modal",
            function() {
                var e = $(".J-docSideNav"),
                t = $(".J-stickyNode"),
                o = $(".J-sideNavDownBox"),
                i = $(".J-middle-down");
                e.removeClass("opened"),
                i.removeClass("cur"),
                t.css("transition", "inherit").css("max-width", 0),
                e.removeClass("show"),
                o.css("height", ""),
                o.hide(),
                $(".J-modal").hide()
            }),
            y.on("click", '.J-tocBox li>a, #docArticleContent a[href^="#"]',
            function(e) {
                e.preventDefault();
                var t = $(this).attr("href") || "";
                w.scroll2Hash(t)
            }),
            b.on("scroll",
            function() {
                b.outerWidth() > 768 ? r.call(this) : d.call(this)
            }),
            b.on("scroll.toggle-toc-visible",
            function() {
                var e = $(".J-tocBox"),
                t = $(".J-fixedToc");
                if (!e.size() || b.outerWidth() < 768) return void t.hide();
                var o = e.offset().top + e.height(),
                i = t.height() + 5,
                n = b.scrollTop();
                n > o - i ? t.show() : t.hide().find(".J-tocExpander").removeClass("down")
            }).trigger("scroll"),
            b.on("resize.adaptRWD",
            function(e) {
                if (!e.isTrigger) {
                    var t = $(".J-stickyNode"),
                    o = $(".J-docSideNav"),
                    i = $(".J-sideNavInnerBox"),
                    n = $(".J-sideNavDownBox"),
                    s = $("#navigationBar").outerHeight() + $(".J-documentCenterSearch").outerHeight(),
                    a = $(".J-subHeader").outerHeight(),
                    c = b.height() - a,
                    h = b.width(),
                    l = b.scrollTop();
                    b.trigger("scroll.toggle-toc-visible"),
                    o.css("height", ""),
                    t.css({
                        position: "",
                        width: "",
                        top: ""
                    }),
                    n.css({
                        top: "",
                        height: "",
                        left: "",
                        position: "",
                        "max-height": "",
                        width: ""
                    }),
                    h <= 768 ? (o.hasClass("opened") && (t.css("max-width", 345), i.css("height", c)), o.hasClass("show") && n.show(), d.call(this)) : (n.hide(), o.removeClass("fixed"), t.css("max-width", ""), l > s && t.width(o.width()), r.call(this))
                }
            })
        }
        function u() {
            var e = $(".J-sideNavInnerBox");
            e.off("mouseenter.watchMouse").on("mouseenter.watchMouse",
            function() {
                $(this).attr("data-needFixFocusElePosition", !1)
            }),
            e.off("mouseleave.watchMouse").on("mouseleave.watchMouse",
            function() {
                $(this).attr("data-needFixFocusElePosition", !0)
            }),
            e.off("mousemove.watchMouse").on("mousemove.watchMouse",
            function() {
                $(this).attr("data-needFixFocusElePosition", !1),
                $(this).off("mousemove.watchMouse")
            }),
            e.off("mousewheel.watchMouse").on("mousewheel.watchMouse",
            function() {
                var e = $(this);
                e.attr("data-needFixFocusElePosition", !1),
                e.off("mousewheel.watchMouse")
            }),
            e.off("DOMMouseScroll.watchMouse").on("DOMMouseScroll.watchMouse",
            function() {
                var e = $(this);
                e.attr("data-needFixFocusElePosition", !1),
                e.off("DOMMouseScroll.watchMouse")
            }),
            e.off("touchstart.watchMouse").on("touchstart.watchMouse",
            function(e) {
                $(".J-sideNavInnerBox").attr("data-needFixFocusElePosition", !1),
                e.stopPropagation()
            }),
            e.off("touchmove.watchMouse").on("touchmove.watchMouse",
            function(e) {
                $(".J-sideNavInnerBox").attr("data-needFixFocusElePosition", !1),
                e.stopPropagation()
            }),
            $(document).off("touchstart.watchMouse").on("touchstart.watchMouse",
            function(e) {
                $(".J-sideNavInnerBox").attr("data-needFixFocusElePosition", !0)
            }),
            $(document).off("touchmove.watchMouse").on("touchmove.watchMouse",
            function(e) {
                $(".J-sideNavInnerBox").attr("data-needFixFocusElePosition", !0)
            })
        }
        function f() {
            var e = $(".list-focus").eq(0);
            if (e.length && !e.is(":hidden")) {
                var t = $(".J-sideNavInnerBox"),
                o = t.offset().top,
                i = t.height(),
                n = i + o,
                s = e.offset().top,
                a = s - o,
                r = s - n;
                a < 0 ? t.scrollTop(t.scrollTop() - Math.abs(a)) : (r > 0 || Math.abs(r) < e.height()) && t.scrollTop(t.scrollTop() + Math.abs(r) + e.height())
            }
        }
        function p(e) {
            e ? setTimeout(f, 0) : f()
        }
        function v() {
            b.width() < 768 || ($(".J-sideNavInnerBox").attr("data-needFixFocusElePosition", !0), p(!0), u())
        }
        function g() {
            var e = new w({
                contentSelector: ".J-mainContent",
                defaultBypass: !1,
                parsePageContent: function(t) {
                    var o = $(t),
                    i = o.find(".J-extend-link"),
                    n = o.find(".J-extend-script");
                    return (n.length || i.length) && e.disableXHR(),
                    {
                        $styles: i,
                        $scripts: n,
                        $notice: o.find(".J-doc-notice-wrap")
                    }
                },
                render: function(e, t) {
                    var o = e.title,
                    i = e.content,
                    s = e.$styles,
                    a = e.$scripts,
                    r = e.$notice;
                    if (document.title = o, s && s.length && s.appendTo("head"), t.html(i), a && a.length) {
                        var c = [];
                        a.each(function() {
                            c.push($(this).attr("src"))
                        }),
                        n(c)
                    }
                    x.html(r.html())
                },
                afterRender: function() {
                    new B;
                    var e = $(".J-floatCategory");
                    e.length && (new N(e), new M),
                    "function" == typeof window.handleDomainHref && window.handleDomainHref(),
                    b.trigger("scroll"),
                    v()
                },
                onBeforePopState: function() {
                    window.BR && "function" == typeof window.BR.abort && window.BR.abort()
                }
            });
            window.DO_NOT_USE_XHR === !0 && e.disableXHR();
            var t = location.hash;
            t.length > 1 && setTimeout(function() {
                w.scroll2Hash(t)
            }),
            new B;
            var o = $(".J-floatCategory");
            o.length && (new N(o), new M),
            l(),
            v()
        }
        var m = function() {
            function e(e, t) {
                for (var o = 0; o < t.length; o++) {
                    var i = t[o];
                    i.enumerable = i.enumerable || !1,
                    i.configurable = !0,
                    "value" in i && (i.writable = !0),
                    Object.defineProperty(e, i.key, i)
                }
            }
            return function(t, o, i) {
                return o && e(t.prototype, o),
                i && e(t, i),
                t
            }
        } (),
        w = o(1);
        o("6FnE");
        var C = o("+cXR").video,
        J = C.init(),
        x = $(".J-doc-notice-wrap"),
        b = $(window),
        y = $(document),
        k = 20,
        T = 40;
        b.on("hashchange", !1);
        var M = function() {
            function e() {
                i(this, e),
                this.$navContainer = $(".J-navContainer"),
                this.$documentContainer = $(".J-mainContent .doc-box"),
                this.$contentContainer = $(".J-mainDetail"),
                this.$content = $(".J-innerMain"),
                this.minWidth = 1268,
                this.hasAbsolute = void 0,
                $(".J-qc-footer").length ? this.$footer = $(".J-qc-footer") : this.$footer = $(".tc-footer"),
                this.init()
            }
            return m(e, [{
                key: "init",
                value: function() {
                    this.positionSwitch(),
                    this.stickySwitch(),
                    this.watchScroll(),
                    this.watchResize()
                }
            },
            {
                key: "positionSwitch",
                value: function() {
                    if (b.width() < this.minWidth)(this.hasAbsolute || this.$navContainer.hasClass("absolute")) && (this.$navContainer.removeClass("absolute"), this.$navContainer.css("margin-left", ""), this.$content.css("margin-right", ""), this.hasAbsolute = !1);
                    else {
                        this.hasAbsolute || (this.$navContainer.addClass("absolute"), this.hasAbsolute = !0);
                        var e = (b.width() - this.$documentContainer.outerWidth()) / 2 - this.$navContainer.outerWidth();
                        e > 0 && (e = 0),
                        this.$navContainer.css("margin-left", e + "px"),
                        this.$content.css("margin-right", -1 * e + "px")
                    }
                }
            },
            {
                key: "stickySwitch",
                value: function() {
                    var e = b.scrollTop();
                    if (this.hasAbsolute && e > this.$content.find(".J-mainTitle").offset().top) {
                        var t = this.$contentContainer.offset().left + this.$contentContainer.outerWidth(),
                        o = this.$footer.offset().top - e - 40;
                        o > b.height() ? o = b.height() : o < 0 && (o = 0),
                        this.$navContainer.css({
                            position: "fixed",
                            top: 0,
                            left: t,
                            "max-height": o + "px"
                        })
                    } else this.$navContainer.css({
                        position: "",
                        top: "",
                        left: "",
                        "max-height": ""
                    })
                }
            },
            {
                key: "watchResize",
                value: function() {
                    var e = this;
                    b.off("resize.floatNavManage").on("resize.floatNavManage",
                    function() {
                        e.positionSwitch(),
                        e.stickySwitch()
                    })
                }
            },
            {
                key: "watchScroll",
                value: function() {
                    var e = this;
                    b.off("scroll.floatNavManage").on("scroll.floatNavManage",
                    function() {
                        e.stickySwitch()
                    })
                }
            }]),
            e
        } (),
        N = function() {
            function e(t) {
                i(this, e),
                this.$floatCategory = t,
                this.$titles = this.getTitles(),
                this.topMap = [],
                this.feedbackContainer = $("#feedback-confirm"),
                this.$footer = $(".J-qc-footer"),
                this.$footer.length || (this.$footer = $(".tc-footer")),
                this.feedbackContainer.length && (this.feedbackContainerEdgeData = parseInt(this.feedbackContainer.css("margin-top") || 0) + parseInt(this.feedbackContainer.css("border-top-width") || 0) + parseInt(this.feedbackContainer.css("padding-top") || 0)),
                this.$footer.length && (this.$footerEdgeData = parseInt(this.$footer.css("margin-top") || 0) + parseInt(this.$footer.css("border-top-width") || 0) + parseInt(this.$footer.css("padding-top") || 0)),
                this.init()
            }
            return m(e, [{
                key: "init",
                value: function() {
                    this.preProcessTitles(),
                    this.watchScroll()
                }
            },
            {
                key: "getTitles",
                value: function() {
                    for (var e = 1,
                    t = []; e < 7;) $("#docArticleContent h" + e).length && t.push("#docArticleContent h" + e),
                    e++;
                    return $(t.join(","))
                }
            },
            {
                key: "preProcessTitles",
                value: function() {
                    var e = this;
                    $(this.$titles).each(function(t, o) {
                        var i = $(o),
                        n = i.attr("id"),
                        s = e.$floatCategory.find("a[href='#" + n + "']");
                        if (s.length) {
                            if (s.length > 1) {
                                var a = $("#docArticleContent [id='" + n + "']").index(i);
                                s = s.eq(a)
                            }
                            var r = {};
                            r.edgeData = parseInt(i.css("margin-top") || 0) + parseInt(i.css("border-top-width") || 0) + parseInt(i.css("padding-top") || 0),
                            r.target = s,
                            r.sourceTarget = i,
                            r.title = s.attr("title"),
                            e.topMap.push(r)
                        }
                    })
                }
            },
            {
                key: "computeTitleRange",
                value: function() {
                    var e = this;
                    $(this.topMap).each(function(e, t) {
                        var o = t.sourceTarget;
                        t.start = o.offset().top - t.edgeData
                    }),
                    $(this.topMap).each(function(t, o) {
                        e.topMap[t + 1] ? o.end = e.topMap[t + 1].start: e.feedbackContainer.length ? o.end = e.feedbackContainer.offset().top - e.feedbackContainerEdgeData: o.end = e.$footer.offset().top - e.$footerEdgeData
                    })
                }
            },
            {
                key: "watchScroll",
                value: function() {
                    var e = this;
                    b.off("scroll.positionManage").on("scroll.positionManage",
                    function() {
                        var t = b.scrollTop();
                        b.width() < 768 || (e.computeTitleRange(), $(e.topMap).each(function(o, i) {
                            return i.start <= t && t < i.end ? (e.$floatCategory.find(".active").removeClass("active"), i.target.addClass("active"), !1) : void(o === e.topMap.length - 1 && e.$floatCategory.find(".active").removeClass("active"))
                        }))
                    })
                }
            }]),
            e
        } (),
        B = function() {
            function e() {
                i(this, e),
                this.codeTemplate = $("#codeTemplate").html(),
                this.init()
            }
            return m(e, [{
                key: "init",
                value: function() {
                    try {
                        this.processResource(),
                        this.bindEvent()
                    } catch(e) {
                        console.error(e),
                        toggleElement("block")
                    }
                }
            },
            {
                key: "processResource",
                value: function() {
                    var e = this;
                    $('script[type="text/plugin"][action="start"]').each(function(t, o) {
                        var i = $(o),
                        n = i.attr("data-type"),
                        s = void 0;
                        switch (n) {
                        case "code":
                            s = e.processCode(i),
                            e.renderCode({
                                data: s,
                                target: i
                            })
                        }
                    }),
                    $("pre").each(function(t, o) {
                        var i = $(o); ! i.closest(".J-markdownCode").length && i.is(":visible") && e.renderCodeWithoutLanuageBar({
                            $codeEl: i
                        })
                    })
                }
            },
            {
                key: "processCode",
                value: function(e) {
                    for (var t = [], o = {},
                    i = e; (i = i.next()) && i.length && ("end" !== i.attr("action") || "text/plugin" !== i.attr("type"));) if (1 == i[0].nodeType && "pre" == i[0].tagName.toLowerCase()) {
                        var n = i.find("code").attr("class").replace(/lang-/, ""),
                        s = i.prop("outerHTML");
                        this.validateData(n, o) && t.push({
                            type: n,
                            code: s
                        })
                    }
                    return t
                }
            },
            {
                key: "validateData",
                value: function(e, t) {
                    return ! t[e] && (t[e] = !0, !0)
                }
            },
            {
                key: "renderCode",
                value: function(e) {
                    var t = e.data,
                    o = e.target,
                    i = $(this.codeTemplate),
                    n = "",
                    s = "",
                    a = void 0;
                    0 !== t.length && ($(t).each(function(e, t) {
                        a = e > 0 ? "": "active",
                        n += '<li class="' + a + ' J-langName" data-type="' + t.type + '"><a data-bypass-xhr="1" href="javascript: void 0;" >' + t.type + "</a></li>",
                        s += t.code
                    }), i.find(".J-language").html(n), i.find(".J-codeBox").append(s).find("pre:eq(0)").show(), i.insertAfter(o))
                }
            },
            {
                key: "renderCodeWithoutLanuageBar",
                value: function(e) {
                    var t = e.$codeEl,
                    o = $(this.codeTemplate);
                    o.find(".J-language").closest(".markdown-code-hd").hide(),
                    o.find(".J-codeBox").append(t[0].outerHTML).find("pre").css({
                        "padding-top": "24px",
                        "padding-bottom": "24px"
                    }),
                    t.replaceWith(o)
                }
            },
            {
                key: "bindEvent",
                value: function() {
                    this.bindTabSwitch(),
                    this.bindCopy(),
                    this.bindCopyTips()
                }
            },
            {
                key: "bindTabSwitch",
                value: function() {
                    $("#docArticleContent").on("click", ".J-langName",
                    function(e) {
                        var t = $(e.currentTarget),
                        o = t.attr("data-type"),
                        i = t.closest(".J-markdownCode");
                        i.find("pre").hide(),
                        t.closest("ul").find("li").removeClass("active"),
                        t.addClass("active"),
                        i.find('code[class="lang-' + o + '"]').closest("pre").show(),
                        b.trigger("scroll")
                    })
                }
            },
            {
                key: "bindCopy",
                value: function() {
                    $(".J-copyContent").each(function(e, t) {
                        var o = new Clipboard(t, {
                            text: function(e) {
                                return $.trim($(e).closest(".J-codeBox").find("pre:visible").text()) || " "
                            }
                        });
                        o.on("success",
                        function(e) {
                            var t = $(e.trigger).closest("li").find(".J-copy-success");
                            t.is(":visible") || (t.show(), setTimeout(function() {
                                t.hide()
                            },
                            1e3))
                        })
                    })
                }
            },
            {
                key: "bindCopyTips",
                value: function() {
                    b.outerWidth() > 768 && ($(document).off("mouseenter.copyTips").on("mouseenter.copyTips", ".J-copyContent",
                    function(e) {
                        var t = $(e.target),
                        o = $(t).closest("li").find(".J-copy-tips");
                        o.show(),
                        e.stopPropagation()
                    }), $(document).off("mouseleave.copyTips").on("mouseleave.copyTips", ".J-copyContent",
                    function(e) {
                        var t = $(e.target),
                        o = $(t).closest("li").find(".J-copy-tips");
                        o.hide(),
                        e.stopPropagation()
                    }))
                }
            }]),
            e
        } ();
        g()
    },
    1 : function(e, t) {
        e.exports = NoRefresh
    },
    "6FnE": function(e, t, o) {
        "use strict";
        function i(e) {
            var t = $("#feedbackText").val() || "",
            o = $('input[name="badfeedbacktype"]:checked').val() || "",
            i = getCookie("uin") || "",
            n = String.prototype.replace.call(G_DOC_INFO.path, "/document/", "");
            return i = i.replace(/^o0*/gi, ""),
            "api" === n && (n = "product"),
            {
                isUseful: e,
                tagId: o,
                suggestion: t,
                uin: i,
                articleId: G_DOC_INFO.id,
                moduleId: r[n]
            }
        }
        function n(e) {
            s.net.post("/document/ajax/", $.extend(e, {
                action: "feedback"
            }))
        }
        var s = o("+cXR"),
        a = ".J-mainContent",
        r = {
            product: 2,
            "developer-resource": 3
        };
        $(a).on("click", "#feedback-confirm a",
        function(e) {
            var t = $(this);
            $("#feedback-confirm").hide(),
            t.hasClass("J-confirm-yes") ? (n(i(!0)), $("#feedback-useful").show()) : $("#feedback-unuse").show(),
            $(window).trigger("scroll")
        }),
        $(a).on("click", "#feedback-unuse button",
        function(e) {
            var t = $(this);
            if (t.hasClass("J-btn-yes")) {
                var o = i(!1);
                if ("" === o.tagId && "" === o.suggestion) return $("#feedbackTips").show(),
                !1;
                n(o),
                $("#feedbackTips").hide(),
                $("#feedback-unuse").hide(),
                $("#feedback-useful").show()
            } else $("#feedback-unuse").hide(),
            $("#feedback-confirm").show();
            $(window).trigger("scroll")
        })
    },
    "+cXR": function(e, t, o) { (function(t) {
            "use strict";
            function o(e, t) {
                for (var o = t.split("."), i = e, n = o.length, s = 0; s < n; s += 1)"undefined" == typeof i[o[s]] && (i[o[s]] = {}),
                i = i[o[s]];
                return i
            }
            e.exports = o(t, "qcloud.main")
        }).call(t,
        function() {
            return this
        } ())
    }
});
/*  |xGv00|befbc8f70e1c72a2c86da6e7366ca26d */
