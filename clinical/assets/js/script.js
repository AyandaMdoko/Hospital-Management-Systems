
// JavaScript for handling role selection and staff options
const roleSelect = document.getElementById('role');
const staffGroup = document.getElementById('staff-group');
const staffSelect = document.getElementById('staff_id');

if (roleSelect && staffGroup && staffSelect) {

    function updateStaffOptions() {

        const selectedRole = roleSelect.value;

        staffGroup.style.display =
            (selectedRole === 'Doctor' || selectedRole === 'Nurse')
            ? 'block'
            : 'none';

        staffSelect.required =
            (selectedRole === 'Doctor' || selectedRole === 'Nurse');

        Array.from(staffSelect.options).forEach(option => {

            if (option.value === '') {
                option.hidden = false;
                return;
            }

            option.hidden = option.dataset.role !== selectedRole;

        });

        staffSelect.value = '';
    }

    roleSelect.addEventListener('change', updateStaffOptions);
}

// JavaScript for handling document type selection and related fields
(function () {
    'use strict';
    const typeSelect = document.getElementById('document_type');
    const fields = {
        'South African ID': [document.getElementById('sa_id_fields')],
        'Passport': [
            document.getElementById('passport_number_field'),
            document.getElementById('passport_country_field')
        ],
        'No document': [document.getElementById('no_document_note')]
    };

    // Function to update the visibility and requirements of fields based on the selected document type
    function refreshDocumentFields() {
        const selected = typeSelect.value;
        Object.entries(fields).forEach(function (entry) {
            const isSelected = entry[0] === selected;
            entry[1].forEach(function (section) {
                section.hidden = !isSelected;
                section.style.display = isSelected ? '' : 'none';
                section.querySelectorAll('input').forEach(function (input) {
                    input.disabled = !isSelected;
                    input.required = isSelected;
                });
            });
        });
    }

    typeSelect.addEventListener('change', refreshDocumentFields);
    refreshDocumentFields();
})();





