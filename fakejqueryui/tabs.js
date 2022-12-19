/**
 * Replica of jQuery UI tabs function for Moodle
 *
 * @author Alex Morris <alex.morris@catalyst.net.nz>
 * @copyright 2022 Catalyst IT
 */
define(['jquery'],
    function($) {
        $.fn.extend({
            uniqueId: (function() {
                var uuid = 0;

                return function() {
                    return this.each(function() {
                        if (!this.id) {
                            this.id = "ui-id-" + (++uuid);
                        }
                    });
                };
            })(),

            removeUniqueId: function() {
                return this.each(function() {
                    if (/^ui-id-\d+$/.test(this.id)) {
                        $(this).removeAttr("id");
                    }
                });
            }
        });

        /**
         * Add tab function to jQuery.
         */
        $.fn.tabs = function() {
            var that = this;
            var tablist = null;
            var tabs = null;
            var anchors = null;
            var panels = null;
            var activeIndex = null;
            var running = false;
            var active = null;
            var xhr = null;

            /**
             * Get tab list
             * @returns {*|null}
             */
            var getList = function() {
                if (tablist != null) {
                    return tablist;
                }
                return that.find("ol, ul").eq(0);
            };

            /**
             * Check if anchor link is pointing to a local element
             * @param anchor
             * @returns {boolean}
             */
            var isLocal = function(anchor) {
                var rhash = /#.*$/;
                var anchorUrl, locationUrl;

                anchorUrl = anchor.href.replace(rhash, "");
                locationUrl = location.href.replace(rhash, "");

                // Decoding may throw an error if the URL isn't UTF-8 (#9518)
                try {
                    anchorUrl = decodeURIComponent(anchorUrl);
                } catch (error) {
                }
                try {
                    locationUrl = decodeURIComponent(locationUrl);
                } catch (error) {
                }

                return anchor.hash.length > 1 && anchorUrl === locationUrl;
            };

            var sanitizeSelector = function(hash) {
                return hash ? hash.replace(/[!"$%&'()*+,.\/:;<=>?@\[\]\^`{|}~]/g, "\\$&") : "";
            };

            /**
             * Create tab panel with given id.
             * @param panelId
             */
            var createPanel = function(panelId) {
                return $("<div>").attr("id", panelId).data("ui-tabs-destroy", true);
            };

            /**
             * Style and add attributes to tabs, anchors, panels.
             */
            var processTabs = function() {
                tablist = getList();
                tablist.attr('role', 'tablist');
                tablist.addClass(['ui-tabs-nav', 'ui-helper-reset', 'ui-helper-clearfix', 'ui-widget-header']);
                tablist.on("mousedown> li", function(event) {
                    if ($(this).is("ui-state-disabled")) {
                        event.preventDefault();
                    }
                })
                    .on("focus.ui-tabs-anchor", function() {
                        if ($(this).closest("li").is("ui-state-disabled")) {
                            this.blur();
                        }
                    });

                tabs = tablist.find("> li:has(a[href])");
                tabs.attr({
                    "role": "tab",
                    "tabIndex": -1
                });
                tabs.addClass(["ui-tabs-tab", "ui-state-default"]);

                anchors = tabs.map(
                    function() {
                        return $("a", this)[0];
                    }
                ).attr("tabIndex", -1);
                anchors.addClass("ui-tabs-anchor");

                panels = $();
                anchors.each(function(i, anchor) {
                    var selector, panel, panelId,
                        anchorId = $(anchor).uniqueId().attr("id"),
                        tab = $(anchor).closest("li"),
                        originalAriaControls = tab.attr("aria-controls");

                    // Inline tab
                    if (isLocal(anchor)) {
                        selector = anchor.hash;
                        panelId = selector.substring(1);
                        panel = that.find(sanitizeSelector(selector));
                    } else { // Remote tab
                        panelId = tab.attr("aria-controls") || $({}).uniqueId()[0].id;
                        selector = "#" + panelId;
                        panel = that.find(selector);
                        if (!panel.length) {
                            panel = createPanel(panelId);
                            panel.insertAfter(panels[i - 1] || tablist);
                        }
                        panel.attr("aria-live", "polite");
                    }

                    if (panel.length) {
                        panels = panels.add(panel);
                    }
                    if (originalAriaControls) {
                        tab.data("ui-tabs-aria-controls", originalAriaControls);
                    }
                    tab.attr({
                        "aria-controls": panelId,
                        "aria-labelledby": anchorId
                    });
                    panel.attr("aria-labelledby", anchorId);
                });

                panels.attr("role", "tabpanel");
                panels.addClass(["ui-tabs-panel", "ui-widget-content"]);
            };

            /**
             * Find and initialise active tab
             * @returns {*}
             */
            var initialActive = function() {
                var preActive = activeIndex;
                var locationHash = location.hash.substring(1);

                if (preActive === null) {
                    // Check the fragment identifier in the URL for a tab
                    if (locationHash) {
                        tabs.each(function(i, tab) {
                            if ($(tab).attr("aria-controls") === locationHash) {
                                preActive = i;
                                return false;
                            }
                        });
                    }

                    // Check for a tab marked active via a class
                    if (preActive === null) {
                        preActive = tabs.index(tabs.filter(".ui-tabs-active"));
                    }
                    // No active tab, set to false
                    if (preActive === null || preActive === -1) {
                        preActive = tabs.length ? 0 : false;
                    }
                }

                if (preActive !== false) {
                    preActive = tabs.index(tabs.eq(preActive));
                    if (preActive === -1) {
                        preActive = 0;
                    }
                }
                if (preActive === false && anchors.length) {
                    preActive = 0;
                }

                return preActive;
            };

            /**
             * Find active tab element by index
             * @param index
             * @returns {*|jQuery|HTMLElement}
             */
            var findActive = function(index) {
                return index === false ? $() : tabs.eq(index);
            };

            /**
             * Get the tabs corresponding panel
             * @param tab
             * @returns {*}
             */
            var getPanelForTab = function(tab) {
                var id = $(tab).attr("aria-controls");
                return that.find(sanitizeSelector("#" + id));
            };

            /**
             * Toggle tab
             * @param event
             * @param eventData
             */
            var toggle = function(event, eventData) {
                var toShow = eventData.newPanel;
                var toHide = eventData.oldPanel;

                running = true;

                function complete() {
                    running = false;
                }

                function show() {
                    eventData.newTab.closest("li").addClass(['ui-tabs-active', 'ui-state-active']);
                    toShow.show();
                    complete();
                }

                eventData.oldTab.closest("li").removeClass(["ui-tabs-active", "ui-state-active"]);
                toHide.hide();
                show();

                toHide.attr("aria-hidden", "true");
                eventData.oldTab.attr({
                    "aria-selected": "false",
                    "aria-expanded": "false",
                });

                if (toShow.length && toHide.length) {
                    eventData.oldTab.attr("tabIndex", -1);
                } else if (toShow.length) {
                    tabs.filter(function() {
                        return $(this).attr("tabIndex") === 0;
                    })
                        .attr("tabIndex", -1);
                }

                toShow.attr("aria-hidden", "false");
                eventData.newTab.attr({
                    "aria-selected": "true",
                    "aria-expanded": "true",
                    tabIndex: 0
                });
            };

            /**
             * Provides option for use of a href string instead of numerical index.
             * @param index
             * @returns {*}
             */
            var getIndex = function(index) {
                if (typeof index === "string") {
                    index = anchors.index(anchors.filter("[href$='" + $.escapeSelector(index) + "']"));
                }
                return index;
            };

            /**
             * Load remote tab content
             * @param index
             */
            var load = function(index) {
                index = getIndex(index);
                var tab = tabs.eq(index);
                var anchor = tab.find(".ui-tabs-anchor");
                var panel = getPanelForTab(tab);

                var complete = function(jqXHR, status) {
                    if (status === "abort") {
                        panels.stop(false, true);
                    }
                    tab.removeClass("ui-tabs-loading");
                    panel.removeAttr("aria-busy");
                    if (jqXHR === xhr) {
                        xhr = null;
                    }
                };


                // Loading remote tabs, not local!
                if (isLocal(anchor[0])) {
                    return;
                }

                xhr = $.ajax({
                    url: anchor.attr("href").replace(/#.*$/, ""),
                });
                if (xhr && xhr.statusText !== "canceled") {
                    tab.addClass("ui-tabs-loading");
                    panel.attr("aria-busy", "true");
                    xhr.done(function(response, status, jqXHR) {
                        setTimeout(function() {
                            panel.html(response);
                            complete(jqXHR, status);
                        });
                    }).fail(function(jqXHR, status) {
                        setTimeout(function() {
                            complete(jqXHR, status);
                        });
                    });
                }
            };

            /**
             * Handle tab anchor click event
             * @param event
             */
            var eventHandler = function(event) {
                var activeTab = active;
                var anchor = $(event.currentTarget);
                var tab = anchor.closest("li");
                var clickedIsActive = tab[0] === activeTab[0];
                var toShow = getPanelForTab(tab);
                var toHide = !activeTab.length ? $() : getPanelForTab(activeTab);
                var eventData = {
                    oldTab: activeTab,
                    oldPanel: toHide,
                    newTab: tab,
                    newPanel: toShow
                };

                event.preventDefault();

                if (tab.hasClass("ui-state-disabled") || tab.hasClass("ui-tabs-loading") || running || clickedIsActive) {
                    return;
                }

                activeIndex = tabs.index(tab);

                active = clickedIsActive ? $() : tab;
                if (!toHide.length && !toShow.length) {
                    $.error("Tabs: Mismatching fragment identifier");
                }
                if (toShow.length) {
                    load(tabs.index(tab));
                }
                toggle(event, eventData);
            };

            /**
             * Set upe vent handlers.
             */
            var setupEvents = function() {
                anchors.on("click", eventHandler);
            };

            /**
             * Refresh tab panel display
             */
            var refresh = function() {
                setupEvents();

                tabs.not(active).attr({
                    "aria-selected": "false",
                    "aria-expanded": "false",
                    "tabIndex": -1
                });
                panels.not(getPanelForTab(active))
                    .hide()
                    .attr("aria-hidden", "true");

                if (!active.length) {
                    tabs.eq(0).attr("tabIndex", 0);
                } else {
                    active.attr({
                        "aria-selected": "true",
                        "aria-expanded": "true",
                        "tabIndex": 0
                    });
                    active.addClass(["ui-tabs-active", "ui-state-active"]);
                    getPanelForTab(active).show().attr("aria-hidden", "false");
                }
            };

            // Initialise
            this.addClass(['ui-tabs', 'ui-widget', 'ui-widget-content']);

            processTabs();
            activeIndex = initialActive();

            if (activeIndex !== false && anchors.length) {
                active = findActive(activeIndex);
            } else {
                active = $();
            }

            refresh();

            if (active.length) {
                load(activeIndex);
            }
        };
    }
);