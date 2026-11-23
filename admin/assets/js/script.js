
// JavaScript for handling role selection and staff options
const roleSelect = document.getElementById('role');
const staffGroup = document.getElementById('staff-group');
const staffSelect = document.getElementById('staff_id');

if (roleSelect && staffGroup && staffSelect) {

    function updateStaffOptions() {

        const selectedRole = roleSelect.value;

        staffGroup.style.display =
            (selectedRole === 'Doctor' || selectedRole === 'Nurse' || selectedRole === 'Receptionist')
            ? 'block'
            : 'none';

        staffSelect.required =
            (selectedRole === 'Doctor' || selectedRole === 'Nurse' || selectedRole === 'Receptionist');

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



