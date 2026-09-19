(function () {
	'use strict';

	var pendingTask = null;
	var asked = false;
	var submitting = false;

	function getOptions() {
		return (window.Joomla && Joomla.getOptions)
			? (Joomla.getOptions('balancirk-lesson-waitlist') || {})
			: {};
	}

	function seatsToPromote(newMax, options) {
		var original = parseInt(options.originalMaxStudents, 10) || 0;
		var enrolled = parseInt(options.enrolledCount, 10) || 0;
		var waiting = parseInt(options.waitingCount, 10) || 0;

		if (newMax <= original) {
			return 0;
		}

		var free = Math.max(0, newMax - enrolled);

		return Math.max(0, Math.min(free, waiting));
	}

	function readNewMax() {
		var field = document.getElementById('jform_max_students');

		if (!field) {
			return 0;
		}

		return parseInt(field.value, 10) || 0;
	}

	function setPromoteFlag(value) {
		var field = document.getElementById('jform_promote_waitlist');

		if (field) {
			field.value = value ? '1' : '0';
		}
	}

	function isSaveTask(task) {
		return task === 'lesson.apply'
			|| task === 'lesson.save'
			|| task === 'lesson.save2new'
			|| task === 'lesson.save2copy';
	}

	function submitPending(originalSubmitbutton) {
		if (submitting || !pendingTask) {
			return;
		}

		submitting = true;
		asked = true;

		var task = pendingTask;
		pendingTask = null;

		if (typeof originalSubmitbutton === 'function') {
			originalSubmitbutton(task);
			return;
		}

		if (window.Joomla && typeof Joomla.submitform === 'function') {
			Joomla.submitform(task, document.getElementById('lesson-form'));
		}
	}

	function showPromoteModal(count, options, originalSubmitbutton) {
		var template = options.confirmTemplate || '%s';
		var message = template.replace('%s', String(count));
		var messageEl = document.getElementById('balancirk-promote-waitlist-message');

		if (messageEl) {
			messageEl.textContent = message;
		}

		setPromoteFlag(0);

		var modalEl = document.getElementById('balancirk-promote-waitlist-modal');

		if (modalEl && window.bootstrap && window.bootstrap.Modal) {
			var modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);

			modalEl.addEventListener('shown.bs.modal', function onShown() {
				modalEl.removeEventListener('shown.bs.modal', onShown);
				var noBtn = document.getElementById('balancirk-promote-waitlist-no');

				if (noBtn) {
					noBtn.focus();
				}
			});

			modalEl.addEventListener('hidden.bs.modal', function onHidden() {
				modalEl.removeEventListener('hidden.bs.modal', onHidden);
				var flagField = document.getElementById('jform_promote_waitlist');
				setPromoteFlag(flagField && flagField.value === '1' ? 1 : 0);
				submitPending(originalSubmitbutton);
			});

			modal.show();
			return true;
		}

		return false;
	}

	document.addEventListener('DOMContentLoaded', function () {
		var form = document.getElementById('lesson-form');

		if (!form || !window.Joomla) {
			return;
		}

		var originalSubmitbutton = Joomla.submitbutton;
		var yes = document.getElementById('balancirk-promote-waitlist-yes');
		var no = document.getElementById('balancirk-promote-waitlist-no');

		if (yes) {
			yes.addEventListener('click', function () {
				setPromoteFlag(1);
			});
		}

		if (no) {
			no.addEventListener('click', function () {
				setPromoteFlag(0);
			});
		}

		Joomla.submitbutton = function (task) {
			if (task !== 'lesson.cancel' && document.formvalidator && !document.formvalidator.isValid(form)) {
				return false;
			}

			if (!asked && isSaveTask(task)) {
				var options = getOptions();
				var count = seatsToPromote(readNewMax(), options);

				if (count > 0) {
					pendingTask = task;

					if (showPromoteModal(count, options, originalSubmitbutton)) {
						return;
					}

					setPromoteFlag(0);
				}
			}

			if (typeof originalSubmitbutton === 'function') {
				return originalSubmitbutton(task);
			}

			Joomla.submitform(task, form);
		};
	});
})();
