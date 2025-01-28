(function (Drupal) {
  'use strict';

  Drupal.behaviors.statusDropdown = {
    attach: function (context, settings) {
      once('statusDropdownBehavior', '.status-dropdown', context).forEach(function (element) {
        element.addEventListener('change', function () {
          var submissionId = element.getAttribute('data-submission-id'); // Pobierz ID zgłoszenia
          var newStatus = element.value; // Pobierz nowy status

          console.log(newStatus);
          console.log(submissionId);

          // Wyślij żądanie AJAX do backendu
          fetch('/update-status', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              submission_id: submissionId,
              status: newStatus
            }),
          })
            .then(function (response) {
              if (!response.ok) {
                throw new Error('Network response was not ok');
              }
              return response.json();
            })
            .then(function (data) {
              alert('Status został zmieniony na: ' + newStatus);
            })
            .catch(function (error) {
              console.error('There was a problem with the fetch operation:', error);
              alert('Wystąpił problem podczas zmiany statusu.');
            });
        });
      });
    }
  };
})(Drupal);
