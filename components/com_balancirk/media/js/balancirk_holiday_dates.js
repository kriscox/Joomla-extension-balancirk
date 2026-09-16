/**
 * Keep holiday end date in step with start date.
 *
 * Rules match HolidayHelper::shouldReplaceEnd / resolveStoredDates:
 * empty end follows start; a later end the user already chose is kept.
 */
(function (global) {
    function dateKey(input) {
        if (!input) {
            return '';
        }

        var alt = (input.getAttribute('data-alt-value') || '').trim();

        if (/^\d{4}-\d{2}-\d{2}/.test(alt) && alt.indexOf('0000-00-00') !== 0) {
            return alt.substring(0, 10);
        }

        return (input.value || '').trim();
    }

    function shouldReplaceEnd(startKey, endKey, previousStartKey) {
        if (!startKey) {
            return false;
        }

        if (!endKey) {
            return true;
        }

        if (previousStartKey && endKey === previousStartKey) {
            return true;
        }

        return /^\d{4}-\d{2}-\d{2}$/.test(startKey)
            && /^\d{4}-\d{2}-\d{2}$/.test(endKey)
            && endKey < startKey;
    }

    function copyDate(fromInput, toInput) {
        toInput.value = fromInput.value;

        var alt = fromInput.getAttribute('data-alt-value');

        if (alt !== null) {
            toInput.setAttribute('data-alt-value', alt);
        }
    }

    function bind(startInput, endInput) {
        var previousStart = dateKey(startInput);

        function applyFromStart(onlyWhenEmpty) {
            var startKey = dateKey(startInput);
            var endKey = dateKey(endInput);

            if (!startKey) {
                return;
            }

            if (onlyWhenEmpty && endKey) {
                return;
            }

            if (!shouldReplaceEnd(startKey, endKey, previousStart)) {
                return;
            }

            copyDate(startInput, endInput);
        }

        startInput.addEventListener('change', function () {
            applyFromStart(false);
            previousStart = dateKey(startInput);
        });
        startInput.addEventListener('blur', function () {
            applyFromStart(false);
            previousStart = dateKey(startInput);
        });

        var endRoot = endInput.closest('.field-calendar') || endInput;

        function seedEndBeforePicker() {
            applyFromStart(true);
        }

        endRoot.addEventListener('pointerdown', seedEndBeforePicker, true);
        endRoot.addEventListener('focusin', seedEndBeforePicker);
        applyFromStart(true);
    }

    var api = {
        dateKey: dateKey,
        shouldReplaceEnd: shouldReplaceEnd,
        copyDate: copyDate,
        bind: bind
    };

    global.BalancirkHolidayDates = api;

    if (typeof document === 'undefined') {
        return;
    }

    document.addEventListener('DOMContentLoaded', function () {
        var startInput = document.getElementById('jform_startDate');
        var endInput = document.getElementById('jform_endDate');

        if (startInput && endInput) {
            bind(startInput, endInput);
        }
    });
})(typeof window !== 'undefined' ? window : this);
