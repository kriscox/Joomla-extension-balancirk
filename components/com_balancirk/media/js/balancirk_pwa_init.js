(function (window, document) {
    'use strict';

    if (!('serviceWorker' in navigator)) {
        return;
    }

    window.addEventListener('load', function () {
        navigator.serviceWorker.register('/media/com_balancirk/js/balancirk_sw.js').catch(function (error) {
            console.warn('Balancirk SW registration failed', error);
        });
    });
})(window, document);
