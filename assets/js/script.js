const menuToggle = document.getElementById("menuToggle");
const primaryNav = document.getElementById("primaryNav");

// Toggle visibility for password fields that use an eye button.
function togglePass(inputId, button) {
  const input = document.getElementById(inputId);
  if (!input) return;

  const isPassword = input.type === "password";
  input.type = isPassword ? "text" : "password";

  const img = button.querySelector("img");
  if (img) {
    img.src = isPassword
      ? "assets/icons/eye.png"
      : "assets/icons/eye close.png";
    img.alt = isPassword ? "Hide password" : "Show password";
  }

  button.setAttribute(
    "aria-label",
    isPassword ? "Hide password" : "Show password",
  );
}

// Show or hide both password fields with the reset form checkbox.
const newPassword = document.getElementById("new-password");
const confirmPassword = document.getElementById("confirm-password");
const showPasswordsCheckbox = document.getElementById("show-passwords");
if (
  showPasswordsCheckbox instanceof HTMLInputElement &&
  newPassword instanceof HTMLInputElement &&
  confirmPassword instanceof HTMLInputElement
) {
  showPasswordsCheckbox.addEventListener("change", () => {
    const type = showPasswordsCheckbox.checked ? "text" : "password";
    newPassword.type = type;
    confirmPassword.type = type;
  });
}

// Show or hide all password fields with the change-password form checkbox.
const changePasswordCheckbox = document.getElementById("change-show-passwords");
const changePasswordFields = [
  document.getElementById("current_password"),
  document.getElementById("new_password"),
  document.getElementById("confirm_password"),
];
if (
  changePasswordCheckbox instanceof HTMLInputElement &&
  changePasswordFields.every((field) => field instanceof HTMLInputElement)
) {
  changePasswordCheckbox.addEventListener("change", () => {
    const type = changePasswordCheckbox.checked ? "text" : "password";
    changePasswordFields.forEach((field) => {
      if (field instanceof HTMLInputElement) field.type = type;
    });
  });
}

// Validate that the new and confirmation passwords match.
const resetForm = document.getElementById("resetForm");
const passwordError = document.getElementById("passwordError");

if (
  resetForm instanceof HTMLFormElement &&
  newPassword instanceof HTMLInputElement &&
  confirmPassword instanceof HTMLInputElement &&
  passwordError instanceof HTMLElement
) {
  const validatePasswordMatch = () => {
    const mismatch =
      confirmPassword.value.length > 0 &&
      newPassword.value !== confirmPassword.value;

    confirmPassword.setCustomValidity(mismatch ? "Passwords do not match." : "");
    passwordError.textContent = mismatch ? "Passwords do not match." : "";
    return !mismatch;
  };

  newPassword.addEventListener("input", validatePasswordMatch);
  confirmPassword.addEventListener("input", validatePasswordMatch);
  resetForm.addEventListener("submit", (event) => {
    if (!validatePasswordMatch()) {
      event.preventDefault();
      confirmPassword.reportValidity();
    }
  });
}