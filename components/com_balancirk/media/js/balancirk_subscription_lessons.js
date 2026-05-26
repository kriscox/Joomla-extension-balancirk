document.addEventListener('DOMContentLoaded', function () {
    var options = Joomla.getOptions('subscription-lessons') || {};
    var studentField = document.getElementById('jform_student');
    var lessonField = document.getElementById('jform_lesson');
    var notice = document.getElementById('subscription-lesson-notice');
    var submitButton = document.getElementById('subscription-submit');

    if (!studentField || !lessonField || !notice || !submitButton) {
        return;
    }

    var placeholder = options.placeholder || '';

    Array.prototype.slice.call(lessonField.options).some(function (option) {
        if (option.value !== '') {
            return false;
        }

        placeholder = option.textContent;

        return true;
    });

    function setSubmitDisabled(disabled) {
        submitButton.disabled = disabled;
    }

    function setNotice(message) {
        if (!message) {
            notice.hidden = true;
            notice.textContent = '';

            return;
        }

        notice.hidden = false;
        notice.textContent = message;
    }

    function resetLessons() {
        lessonField.innerHTML = '';

        if (placeholder) {
            var option = document.createElement('option');
            option.value = '';
            option.textContent = placeholder;
            lessonField.appendChild(option);
        }

        lessonField.value = '';
    }

    function setLessons(lessons) {
        resetLessons();

        lessons.forEach(function (lesson) {
            var option = document.createElement('option');
            option.value = lesson.value;
            option.textContent = lesson.text;
            lessonField.appendChild(option);
        });
    }

    function updateState(payload) {
        var lessons = Array.isArray(payload.lessons) ? payload.lessons : [];
        setLessons(lessons);

        var disabled = !payload.hasOpenLessons || lessons.length === 0;
        lessonField.disabled = disabled;
        setSubmitDisabled(disabled);
        setNotice(payload.message || '');
    }

    function loadLessons(studentId) {
        var url = options.endpoint + '&student_id=' + encodeURIComponent(studentId || '');

        document.body.style.cursor = 'wait';

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Request failed');
                }

                return response.json();
            })
            .then(function (response) {
                updateState(response.data || {});
            })
            .catch(function () {
                resetLessons();
                lessonField.disabled = true;
                setSubmitDisabled(true);
                setNotice(options.errorMessage || '');
            })
            .finally(function () {
                document.body.style.cursor = 'default';
            });
    }

    studentField.addEventListener('change', function () {
        loadLessons(this.value);
    });

    loadLessons(studentField.value);
});
