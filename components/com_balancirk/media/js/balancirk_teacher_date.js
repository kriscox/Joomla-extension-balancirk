var changed = false;
var oldDate = '';

jQuery(document).ready(function () {
	const fieldset = document.querySelectorAll('fieldset#jform_teachers')[0];
	fieldset.addEventListener('change', function () { changed = true; });

	jQuery("#jform_date").on("change", function () {
		// Set mouspointer to hourglass
		document.body.style.cursor = 'wait';

		var selectedDate = jQuery(this).val();

		// TODO: Add a check to see if the date has changed
		if (changed && oldDate != selectedDate) {
			$('#confirmModal').modal('show');

			// When user confirms the change
			$('#confirmChange').on('click', function () {
				// Close modal
				$('#confirmModal').modal('hide');

				update();
			});

			// When user cancels the change
			$('#confirmModal').on('hidden.bs.modal', function (e) {
				// Revert date value
				$('#dateInput').val(oldDate);

				return;
			});
		} else {
			update();
		}

		function update() {
			changed = false;
			// Save the old_date
			oldDate = selectedDate;

			var lesson = jQuery("#jform_id").val();
			if (lesson && selectedDate) {
				loadTeachersData(lesson, selectedDate).then(function (response) {
					// Assuming the response is an array of teacher IDs
					updateCheckboxes(response);
				}).catch(function (error) {
					console.error('Error fetching data:', error);
				});
			}
		}
	});

})

function loadTeachersData(lesson, selectedDate) {
	// reformat selectedDate from dd/mm/yyyy to yyyy-mm-dd
	var dateParts = selectedDate.split("/");
	selectedDate = dateParts[2] + '-' + dateParts[1] + '-' + dateParts[0];

	// Make an API call using AJAX
	var apiUrl = 'api/index.php/v1/teacher/' + lesson + '/' + selectedDate;
	var token = Joomla.getOptions('teacher-script').token;

	return new Promise(function (resolve, reject) {
		jQuery.ajax({
			url: apiUrl,
			method: 'GET',
			headers: {
				'Authorization': 'Bearer ' + token
			},
			success: function (response) {
				// Assuming the response is an array of teacher IDs
				updateCheckboxes(response);

				// Set mousepounter to default
				document.body.style.cursor = 'default';
			},
			error: function (error) {
				console.error('Error fetching data:', error);

				// Set mousepounter to default
				document.body.style.cursor = 'default';
			}
		});
	});
}

function updateCheckboxes(teacherIds) {
	// Assuming 'jform_teachers' is the ID of your checkboxes fieldset
	var checkboxesFieldset = document.getElementById('jform_teachers');

	// Uncheck all checkboxes
	checkboxesFieldset.querySelectorAll('input[type="checkbox"]').forEach(function (checkbox) {
		checkbox.checked = false;
	});

	// Check checkboxes based on the API response
	teacherIds.data.forEach(function (teacherId) {
		var checkbox = checkboxesFieldset.querySelector('input[value="' + teacherId['id'] + '"]');
		if (checkbox) {
			checkbox.checked = true;
		}
	});
}
