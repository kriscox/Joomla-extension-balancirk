var previousDate = '';
var restoringDate = false;
var pendingFrontendIds = [];
var pendingDbIds = [];

jQuery(document).ready(function () {
	var options = Joomla.getOptions('lesson-script') || {};
	var fieldset = document.getElementById('jform_students');

	if (fieldset) {
		fieldset.addEventListener('change', function () { });
	}

	initDatepicker(options);
	bindPresenceSave(options);
	bindConflictModal(options);
});

function getScriptOptions() {
	return Joomla.getOptions('lesson-script') || {};
}

function initDatepicker(options) {
	var $input = jQuery('#jform_date');

	if (!$input.length) {
		return;
	}

	if (jQuery.fn.datepicker) {
		$input.datepicker({
			language: 'nl-BE',
			format: 'dd/mm/yyyy',
			startDate: options.startDisplay || '',
			endDate: options.endDisplay || '',
			todayHighlight: true,
			todayBtn: false,
			clearBtn: true,
			forceParse: false,
			maxViewMode: 0,
			weekStart: 1,
			beforeShowDay: function (date) {
				var mask = parseInt(options.lesdaysMask, 10) || 0;
				var bits = options.weekdayBits || [1, 64, 32, 16, 8, 4, 2];

				return !mask || (mask & bits[date.getDay()]) !== 0;
			},
			autoclose: true
		});

		applyInitialDate($input, options, true);
	} else {
		$input.attr({
			type: 'date',
			min: options.start || '',
			max: options.end || ''
		});

		applyInitialDate($input, options, false);
	}

	$input.on('change', function () {
		if (restoringDate) {
			return;
		}

		handleDateChange(options, this);
	});

	if (previousDate && !options.restoreSelection) {
		replaceWithDatabase(options, toIsoDate(previousDate));
	}
}

function applyInitialDate($input, options, useDatepicker) {
	var autoIso = toIsoDate(options.autoSelectDate || options.autoSelectIso || '');

	if (autoIso && isValidAttendanceDate(autoIso, options)) {
		if (useDatepicker) {
			$input.datepicker('setDate', options.autoSelectDate || autoIso);
			previousDate = $input.val();
		} else {
			$input.val(options.autoSelectIso || autoIso);
			previousDate = $input.val();
		}

		return;
	}

	if (useDatepicker) {
		$input.datepicker('update', '');
	}

	$input.val('');
	previousDate = '';
}

function handleDateChange(options, input) {
	var selectedDate = jQuery(input).val();
	var iso = toIsoDate(selectedDate);

	if (!iso) {
		previousDate = '';
		document.body.style.cursor = 'default';

		return;
	}

	if (!isValidAttendanceDate(iso, options)) {
		restoreDate(previousDate, options);

		return;
	}

	var wasEmpty = toIsoDate(previousDate) === '';

	if (!wasEmpty) {
		previousDate = selectedDate;
		replaceWithDatabase(options, iso);

		return;
	}

	document.body.style.cursor = 'wait';
	loadStudentsData(jQuery('#jform_id').val(), iso).then(function (studentIds) {
		previousDate = selectedDate;
		document.body.style.cursor = 'default';

		if (!studentIds || studentIds.length === 0) {
			return;
		}

		showConflictModal(studentIds);
	}).catch(function (error) {
		console.error('Error fetching data:', error);
		document.body.style.cursor = 'default';
	});
}

function replaceWithDatabase(options, iso) {
	document.body.style.cursor = 'wait';

	loadStudentsData(jQuery('#jform_id').val(), iso).then(function (studentIds) {
		updateCheckboxes(studentIds || []);
		document.body.style.cursor = 'default';
	}).catch(function (error) {
		console.error('Error fetching data:', error);
		document.body.style.cursor = 'default';
	});
}

function restoreDate(value, options) {
	var $input = jQuery('#jform_date');

	restoringDate = true;

	if (jQuery.fn.datepicker && $input.data('datepicker')) {
		if (value) {
			$input.datepicker('update', value);
		} else {
			$input.datepicker('update', '');
			$input.val('');
		}
	} else {
		$input.val(value || '');
	}

	restoringDate = false;
}

function isValidAttendanceDate(isoDate, options) {
	if (!isoDate || !/^\d{4}-\d{2}-\d{2}$/.test(isoDate)) {
		return false;
	}

	if (options.start && isoDate < options.start) {
		return false;
	}

	if (options.end && isoDate > options.end) {
		return false;
	}

	var mask = parseInt(options.lesdaysMask, 10) || 0;

	if (mask) {
		var parts = isoDate.split('-');
		var date = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
		var bits = options.weekdayBits || [1, 64, 32, 16, 8, 4, 2];

		if ((mask & bits[date.getDay()]) === 0) {
			return false;
		}
	}

	return true;
}

function showWarning(message) {
	if (window.Joomla && typeof Joomla.renderMessages === 'function') {
		Joomla.renderMessages({ warning: [message] });
	} else {
		window.alert(message);
	}
}

function getString(options, key, fallback) {
	return (options.strings && options.strings[key]) ? options.strings[key] : fallback;
}

function getCheckedIds() {
	var checkboxesFieldset = document.getElementById('jform_students');

	if (!checkboxesFieldset) {
		return [];
	}

	return Array.prototype.slice.call(checkboxesFieldset.querySelectorAll('input[type="checkbox"]:checked'))
		.map(function (checkbox) {
			return checkbox.value;
		});
}

function unionIds(frontendIds, dbIds) {
	var seen = {};
	var merged = [];

	(frontendIds || []).concat(dbIds || []).forEach(function (id) {
		var key = String(id);

		if (!seen[key]) {
			seen[key] = true;
			merged.push(id);
		}
	});

	return merged;
}

function showConflictModal(dbIds) {
	pendingFrontendIds = getCheckedIds();
	pendingDbIds = dbIds || [];

	var modalElement = document.getElementById('presenceConflictModal');

	if (!modalElement || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
		updateCheckboxes(unionIds(pendingFrontendIds, pendingDbIds));

		return;
	}

	bootstrap.Modal.getOrCreateInstance(modalElement).show();
}

function hideConflictModal() {
	var modalElement = document.getElementById('presenceConflictModal');

	if (modalElement && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
		bootstrap.Modal.getOrCreateInstance(modalElement).hide();
	}
}

function bindConflictModal(options) {
	var mergeButton = document.getElementById('presenceMerge');
	var keepButton = document.getElementById('presenceKeep');
	var overwriteButton = document.getElementById('presenceOverwrite');

	if (mergeButton) {
		mergeButton.addEventListener('click', function () {
			updateCheckboxes(unionIds(pendingFrontendIds, pendingDbIds));
			hideConflictModal();
		});
	}

	if (keepButton) {
		keepButton.addEventListener('click', function () {
			hideConflictModal();
		});
	}

	if (overwriteButton) {
		overwriteButton.addEventListener('click', function () {
			updateCheckboxes(pendingDbIds);
			hideConflictModal();
		});
	}
}

function bindPresenceSave(options) {
	var originalSubmitbutton = typeof Joomla.submitbutton === 'function' ? Joomla.submitbutton : null;

	Joomla.submitbutton = function (task) {
		if (task === 'lesson.presence' && !validatePresenceDateForSave(options)) {
			return false;
		}

		if (originalSubmitbutton) {
			return originalSubmitbutton(task);
		}

		Joomla.submitform(task);
	};
}

function validatePresenceDateForSave(options) {
	var selectedDate = jQuery('#jform_date').val();
	var iso = toIsoDate(selectedDate);

	if (!iso || !isValidAttendanceDate(iso, options)) {
		showWarning(getString(options, 'invalidDate', 'Choose a valid attendance date.'));

		return false;
	}

	return true;
}

function toIsoDate(selectedDate) {
	if (!selectedDate) {
		return '';
	}

	if (/^\d{4}-\d{2}-\d{2}$/.test(selectedDate)) {
		return selectedDate;
	}

	var parts = selectedDate.split(/[\/.\-]/);

	if (parts.length === 3 && parts[2].length === 4) {
		var day = parts[0].padStart(2, '0');
		var month = parts[1].padStart(2, '0');

		return parts[2] + '-' + month + '-' + day;
	}

	return selectedDate;
}

function extractStudentIds(response) {
	if (Array.isArray(response)) {
		return response.map(function (item) {
			return (item && typeof item === 'object') ? item.id : item;
		});
	}

	if (!response || typeof response !== 'object') {
		return [];
	}

	if (Array.isArray(response.students)) {
		return response.students;
	}

	if (response.data && Array.isArray(response.data.students)) {
		return response.data.students;
	}

	if (response.data && Array.isArray(response.data)) {
		return response.data.map(function (item) {
			return (item && typeof item === 'object') ? item.id : item;
		});
	}

	return [];
}

function loadStudentsData(lesson, selectedDate) {
	selectedDate = toIsoDate(selectedDate);
	var options = getScriptOptions();
	var url = options.presencesUrl;

	if (url) {
		url += (url.indexOf('?') === -1 ? '?' : '&') + 'id=' + encodeURIComponent(lesson)
			+ '&date=' + encodeURIComponent(selectedDate);

		return jQuery.ajax({
			url: url,
			method: 'GET',
			dataType: 'json'
		}).then(function (response) {
			document.body.style.cursor = 'default';
			return extractStudentIds(response);
		});
	}

	var apiUrl = '/api/index.php/v1/presence/' + lesson + '/' + selectedDate;
	var token = options.token;

	return new Promise(function (resolve, reject) {
		jQuery.ajax({
			url: apiUrl,
			method: 'GET',
			headers: token ? {
				'Authorization': 'Bearer ' + token
			} : {},
			success: function (response) {
				document.body.style.cursor = 'default';
				resolve(extractStudentIds(response));
			},
			error: function (error) {
				console.error('Error fetching data:', error);
				document.body.style.cursor = 'default';
				reject(error);
			}
		});
	});
}

function updateCheckboxes(studentIds) {
	var checkboxesFieldset = document.getElementById('jform_students');

	if (!checkboxesFieldset) {
		return;
	}

	checkboxesFieldset.querySelectorAll('input[type="checkbox"]').forEach(function (checkbox) {
		checkbox.checked = false;
	});

	(studentIds || []).forEach(function (studentId) {
		var id = (studentId && typeof studentId === 'object') ? studentId.id : studentId;
		var checkbox = checkboxesFieldset.querySelector('input[value="' + id + '"]');
		if (checkbox) {
			checkbox.checked = true;
		}
	});
}
