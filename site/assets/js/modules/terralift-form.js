export default class TerraliftForm {
  constructor() {
    this.inited = false;

    document.addEventListener('DOMContentLoaded', () => {
      this.form = document.getElementById('terralift-form');

      // init success depends on form element being found
      if (this.form) {
        this.inited = true;
        this.messageDiv = document.getElementById('form-message');
        this.submitButton = this.form.querySelector('button[type="submit"]');
        this.originalButtonText = this.submitButton.textContent;
      } else {
        // Form not found - this is OK for pages without the form
        return this;
      }

      this.initFormSubmit();
    });

    return this;
  }

  // Helper method to determine if form has been inited
  isInited() {
    if (!this.inited) {
      console.error('Error: Tried to call terralift form method prior to initialization.');
      return false;
    }
    return true;
  }

  initFormSubmit() {
    this.form.addEventListener('submit', (e) => {
      e.preventDefault();

      if (this.isInited()) {
        this.handleSubmit();
      }
    });
  }

  handleSubmit() {
    // Disable submit button
    this.submitButton.disabled = true;
    this.submitButton.textContent = 'Wird gesendet...';

    // Hide any previous messages
    this.messageDiv.style.display = 'none';

    // Prepare form data
    const formData = new FormData(this.form);

    // Send AJAX request
    fetch(this.form.action, {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
      },
    })
        .then((response) => {
          return response.json().then((data) => {
            return {
              status: response.status,
              data: data,
            };
          });
        })
        .then((result) => {
          this.showMessage(result.data);
        })
        .catch((error) => {
          this.showError();
          console.error('Form submission error:', error);
        });
  }

  showMessage(data) {
    this.messageDiv.style.display = 'block';

    if (data.success) {
      this.messageDiv.classList.remove('is-error');
      this.messageDiv.classList.add('is-success');
      this.messageDiv.textContent = data.message;

      // Reset form on success
      this.form.reset();

      // Re-enable submit button and restore text
      this.submitButton.disabled = false;
      this.submitButton.textContent = this.originalButtonText;
    } else {
      this.messageDiv.classList.remove('is-success');
      this.messageDiv.classList.add('is-error');
      this.messageDiv.textContent = data.message || 'Ein Fehler ist aufgetreten.';

      // Re-enable submit button on error
      this.submitButton.disabled = false;
      this.submitButton.textContent = this.originalButtonText;
    }
  }

  showError() {
    this.messageDiv.style.display = 'block';
    this.messageDiv.classList.remove('is-success');
    this.messageDiv.classList.add('is-error');
    this.messageDiv.textContent = 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.';

    // Re-enable submit button on error
    this.submitButton.disabled = false;
    this.submitButton.textContent = this.originalButtonText;
  }
}
