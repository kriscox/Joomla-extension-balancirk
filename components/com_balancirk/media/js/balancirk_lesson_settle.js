/**
 * Year-start waitlist settlement: FIFO check before submit.
 */
(() => {
  'use strict';

  const options = Joomla.getOptions('balancirk-lesson-settle', {}) || {};
  const fifoIds = Array.isArray(options.fifoIds) ? options.fifoIds.map(Number) : [];
  const form = document.getElementById('waitlist-settle-form');
  const overrideField = document.getElementById('confirm_fifo_override');
  const modalEl = document.getElementById('balancirk-fifo-override-modal');
  const confirmBtn = document.getElementById('balancirk-fifo-override-confirm');

  if (!form) {
    return;
  }

  const isFifoPrefix = (selected) => {
    if (!selected.length) {
      return true;
    }
    if (selected.length > fifoIds.length) {
      return false;
    }
    const prefix = fifoIds.slice(0, selected.length).slice().sort((a, b) => a - b);
    const sorted = selected.slice().sort((a, b) => a - b);
    return prefix.length === sorted.length && prefix.every((id, i) => id === sorted[i]);
  };

  const getSelectedPromoteIds = () =>
    Array.from(document.querySelectorAll('.balancirk-promote-check:checked')).map((el) => Number(el.value));

  form.addEventListener('submit', (event) => {
    if (overrideField && overrideField.value === '1') {
      return;
    }

    const promoteIds = getSelectedPromoteIds();
    const dismissIds = Array.from(document.querySelectorAll('.balancirk-dismiss-check:checked')).map((el) => Number(el.value));

    if (!promoteIds.length && !dismissIds.length) {
      event.preventDefault();
      return;
    }

    // Mutual exclusion: same id in both lists
    const overlap = promoteIds.filter((id) => dismissIds.includes(id));
    if (overlap.length) {
      event.preventDefault();
      window.alert(Joomla.Text._('COM_BALANCIRK_WAITLIST_SETTLE_OVERLAP') || 'Cannot promote and dismiss the same student.');
      return;
    }

    if (!isFifoPrefix(promoteIds)) {
      event.preventDefault();
      if (modalEl && window.bootstrap && window.bootstrap.Modal) {
        window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
      } else if (window.confirm(Joomla.Text._('COM_BALANCIRK_WAITLIST_SETTLE_FIFO_CONFIRM') || 'Override FIFO?')) {
        if (overrideField) {
          overrideField.value = '1';
        }
        form.submit();
      }
    }
  });

  if (confirmBtn) {
    confirmBtn.addEventListener('click', () => {
      if (overrideField) {
        overrideField.value = '1';
      }
      if (modalEl && window.bootstrap && window.bootstrap.Modal) {
        window.bootstrap.Modal.getOrCreateInstance(modalEl).hide();
      }
      form.submit();
    });
  }

  // Uncheck the other action when one is selected for the same row
  document.querySelectorAll('.balancirk-promote-check, .balancirk-dismiss-check').forEach((checkbox) => {
    checkbox.addEventListener('change', () => {
      if (!checkbox.checked) {
        return;
      }
      const row = checkbox.closest('tr');
      if (!row) {
        return;
      }
      if (checkbox.classList.contains('balancirk-promote-check')) {
        const other = row.querySelector('.balancirk-dismiss-check');
        if (other) {
          other.checked = false;
        }
      } else {
        const other = row.querySelector('.balancirk-promote-check');
        if (other) {
          other.checked = false;
        }
      }
    });
  });
})();
