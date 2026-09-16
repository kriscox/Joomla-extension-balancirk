var previousDate = '';
var restoringDate = false;
var pendingInitialLoad = false;

jQuery(document).ready(function () {
	var options = Joomla.getOptions('teacher-script') || {};
	var fieldset = document.getElementById('jform_teachers');

	if (fieldset) {
		fieldset.addEventListener('change', function () { });
	}

	pendingInitialLoad = !!options.autoSelectDate || !!options.autoSelectIso;

	jQuery('#jform_date').on('change', function () {
		if (restoringDate) {
			return;
		}

		handleTeacherDateChange(options, this);
	});

	initTeacherDatepicker(options);
	bindTeacherSave(options);
});

function getTeacherOptions() {
	return Joomla.getOptions('teacher-script') || {};
}

function initTeacherDatepicker(options) {
	var $input = jQuery('#jform_date');

	if (!$input.length) {
		return;
	}

	if (jQuery.fn.datepicker) {
		$input.datepicker({
			language: 'nl-BE',
			startDate: options.startDisplay || '',
			endDate: options.endDisplay || '',
			todayHighlight: true,
			todayBtn: true,
			maxViewMode: 0,
			weekStart: 1,
			beforeShowDay: function (date) {
				var mask = parseInt(options.lesdaysMask, 10) || 0;
				var bits = options.weekdayBits || [1, 64, 32, 16, 8, 4, 2];

				return !mask || (mask & bits[date.getDay()]) !== 0;
			},
			autoclose: true
		});

		if (options.autoSelectDate) {
			$input.datepicker('setDate', options.autoSelectDate);
		}
	} else {
		$input.attr({
			type: 'date',
			min: options.start || '',
			max: options.end || ''
		});

		if (options.autoSelectIso) {
			$input.val(options.autoSelectIso);
			$input.trigger('change');
		}
	}
}

function handleTeacherDateChange(options, input) {
	var selectedDate = jQuery(input).val();
	var iso = toIsoDate(selectedDate);

	if (pendingInitialLoad) {
		pendingInitialLoad = false;

		if (!iso || !isValidAttendanceDate(iso, options)) {
			previousDate = '';
			document.body.style.cursor = 'default';

			return;
		}

		previousDate = selectedDate;

		if (!options.restoreSelection) {
			loadTeacherCheckboxes(iso);
		}

		return;
	}

	if (!iso || !isValidAttendanceDate(iso, options)) {
		showTeacherWarning(getTeacherString(options, 'invalidDate', 'Choose a valid teacher attendance date.'));
		restoreTeacherDate(previousDate);

		return;
	}

	previousDate = selectedDate;
	loadTeacherCheckboxes(iso);
}

function loadTeacherCheckboxes(iso) {
	document.body.style.cursor = 'wait';

	loadTeachersData(jQuery('#jform_id').val(), iso).then(function (teacherIds) {
		updateCheckboxes(teacherIds || []);
		document.body.style.cursor = 'default';
	}).catch(function (error) {
		console.error('Error fetching data:', error);
		document.body.style.cursor = 'default';
	});
}

function restoreTeacherDate(value) {
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

function showTeacherWarning(message) {
	if (window.Joomla && typeof Joomla.renderMessages === 'function') {
		Joomla.renderMessages({ warning: [message] });
	} else {
		window.alert(message);
	}
}

function getTeacherString(options, key, fallback) {
	return (options.strings && options.strings[key]) ? options.strings[key] : fallback;
}

function bindTeacherSave(options) {
	var originalSubmitbutton = typeof Joomla.submitbutton === 'function' ? Joomla.submitbutton : null;

	Joomla.submitbutton = function (task) {
		if (task === 'lesson.teacher' && !validateTeacherDateForSave(options)) {
			return false;
		}

		if (originalSubmitbutton) {
			return originalSubmitbutton(task);
		}

		Joomla.submitform(task);
	};
}

function validateTeacherDateForSave(options) {
	var selectedDate = jQuery('#jform_date').val();
	var iso = toIsoDate(selectedDate);

	if (!iso || !isValidAttendanceDate(iso, options)) {
		showTeacherWarning(getTeacherString(options, 'invalidDate', 'Choose a valid teacher attendance date.'));

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

function extractTeacherIds(response) {
	if (Array.isArray(response)) {
		return response.map(function (item) {
			return (item && typeof item === 'object') ? item.id : item;
		});
	}

	if (!response || typeof response !== 'object') {
		return [];
	}

	if (Array.isArray(response.teachers)) {
		return response.teachers;
	}

	if (response.data && Array.isArray(response.data.teachers)) {
		return response.data.teachers;
	}

	if (response.data && Array.isArray(response.data)) {
		return response.data.map(function (item) {
			return (item && typeof item === 'object') ? (item.id || item.teacher) : item;
		});
	}

	return [];
}

function loadTeachersData(lesson, selectedDate) {
	selectedDate = toIsoDate(selectedDate);
	var options = getTeacherOptions();
	var url = options.teachersUrl;

	if (url) {
		url += (url.indexOf('?') === -1 ? '?' : '&') + 'id=' + encodeURIComponent(lesson)
			+ '&date=' + encodeURIComponent(selectedDate);

		return jQuery.ajax({
			url: url,
			method: 'GET',
			dataType: 'json'
		}).then(function (response) {
			document.body.style.cursor = 'default';
			return extractTeacherIds(response);
		});
	}

	var apiUrl = '/api/index.php/v1/teacher/' + lesson + '/' + selectedDate;
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
				resolve(extractTeacherIds(response));
			},
			error: function (error) {
				console.error('Error fetching data:', error);
				document.body.style.cursor = 'default';
				reject(error);
			}
		});
	});
}

function updateCheckboxes(teacherIds) {
	var checkboxesFieldset = document.getElementById('jform_teachers');

	if (!checkboxesFieldset) {
		return;
	}

	checkboxesFieldset.querySelectorAll('input[type="checkbox"]').forEach(function (checkbox) {
		checkbox.checked = false;
	});

	(teacherIds || []).forEach(function (teacherId) {
		var id = (teacherId && typeof teacherId === 'object') ? (teacherId.id || teacherId.teacher) : teacherId;
		var checkbox = checkboxesFieldset.querySelector('input[value="' + id + '"]');
		if (checkbox) {
			checkbox.checked = true;
		}
	});
}
