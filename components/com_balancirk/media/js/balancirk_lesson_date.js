var changed = false;
var oldDate = '';

jQuery(document).ready(function () {
	const fieldset = document.querySelectorAll('fieldset#jform_students')[0];

	if (fieldset) {
		fieldset.addEventListener('change', function () { changed = true; });
	}

	jQuery("#jform_date").on("change", function () {
		document.body.style.cursor = 'wait';

		var selectedDate = jQuery(this).val();

		if (changed && oldDate != selectedDate) {
			$('#confirmModal').modal('show');

			$('#confirmChange').on('click', function () {
				$('#confirmModal').modal('hide');

				update();
			});

			$('#confirmModal').on('hidden.bs.modal', function (e) {
				$('#dateInput').val(oldDate);

				return;
			});
		} else {
			update();
		}

		function update() {
			changed = false;
			oldDate = selectedDate;

			var lesson = jQuery("#jform_id").val();
			if (lesson && selectedDate) {
				loadStudentsData(lesson, selectedDate).then(function (studentIds) {
					updateCheckboxes(studentIds);
				}).catch(function (error) {
					console.error('Error fetching data:', error);
				});
			} else {
				document.body.style.cursor = 'default';
			}
		}
	});

})

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
	var dateParts = selectedDate.split("/");
	selectedDate = dateParts[2] + '-' + dateParts[1] + '-' + dateParts[0];

	var apiUrl = '/api/index.php/v1/presence/' + lesson + '/' + selectedDate;
	var token = (Joomla.getOptions('lesson-script') || {}).token;

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
