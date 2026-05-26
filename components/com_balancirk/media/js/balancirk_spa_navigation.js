(function (window, document) {
    'use strict';

    var NAV_EVENT_NAME = 'balancirk:navigate';

    function isAngularSpaEnabled() {
        return Boolean(window.balancirkAngularSpa);
    }

    function buildNavigationDetail(url, options) {
        return {
            url: url,
            replace: Boolean(options && options.replace),
            source: options && options.source ? options.source : 'ui'
        };
    }

    function notifyAngularNavigation(detail) {
        var event = new CustomEvent(NAV_EVENT_NAME, {
            cancelable: true,
            detail: detail
        });

        return window.dispatchEvent(event);
    }

    function openWithBrowserNavigation(detail) {
        if (detail.replace) {
            window.location.replace(detail.url);
            return;
        }

        window.location.href = detail.url;
    }

    function navigate(url, options) {
        if (!url) {
            return;
        }

        var detail = buildNavigationDetail(url, options);

        if (isAngularSpaEnabled()) {
            var wasNotCancelled = notifyAngularNavigation(detail);

            if (!wasNotCancelled) {
                return;
            }

            if (detail.replace) {
                window.history.replaceState({ balancirk: true, url: detail.url }, '', detail.url);
            } else {
                window.history.pushState({ balancirk: true, url: detail.url }, '', detail.url);
            }

            return;
        }

        openWithBrowserNavigation(detail);
    }

    function shouldIgnoreClick(event, link) {
        return event.defaultPrevented
            || event.button !== 0
            || event.metaKey
            || event.ctrlKey
            || event.shiftKey
            || event.altKey
            || link.hasAttribute('download')
            || (link.getAttribute('target') && link.getAttribute('target') !== '_self');
    }

    document.addEventListener('click', function (event) {
        var link = event.target.closest('a[data-balancirk-spa-nav]');

        if (!link || shouldIgnoreClick(event, link)) {
            return;
        }

        event.preventDefault();
        navigate(link.href, { source: 'link' });
    });

    window.BalancirkSpaNavigation = {
        navigate: navigate,
        eventName: NAV_EVENT_NAME
    };
})(window, document);
