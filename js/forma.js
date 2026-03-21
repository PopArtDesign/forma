customElements.define('forma-form', class extends HTMLElement {
    /**
     * Returns the current state of the form.
     * @returns {string} The form state: 'initial', 'submit', 'success', 'fail', or 'error'
     */
    get #state() {
        return this.getAttribute('state') || 'initial'
    }

    /**
     * Sets the form state with validation.
     * @param {string} value - The state to set ('initial', 'submit', 'success', 'fail', or 'error')
     * @throws {Error} If the state value is invalid
     */
    set #state(value) {
        if (!['initial', 'submit', 'success', 'fail', 'error'].includes(value)) {
            throw Error('Forma: invalid state: ' + value)
        }

        this.setAttribute('state', value)
    }

    /**
     * Initializes the custom element by attaching event listeners and setting initial state.
     */
    constructor() {
        super()

        this.#state = 'initial'

        this.addEventListener('submit', this.handleSubmit)
        this.addEventListener('forma:submit', this.handleAddClientInfo)
        this.addEventListener('forma:submit', this.handleAddImNotARobot)
        this.addEventListener('forma:success', this.handleShowMessage)
        this.addEventListener('forma:fail', this.handleShowMessage)
        this.addEventListener('forma:error', this.handleShowMessage)
        this.addEventListener('forma:fail', this.handleShowValidationErrors)
        this.addEventListener('input', this.handleClearValidationErrors)
    }

    /**
     * Handles form submission by preventing default behavior, validating state, and sending data to server.
     * @param {Event} event - The submit event
     */
    handleSubmit = (event) => {
        event.preventDefault()

        if (this.#isSubmitting()) {
            return
        }

        this.#state = 'submit'

        const form = event.target
        form.inert = true

        const action = form.getAttribute('action')
        const method = form.getAttribute('method') || 'post'
        const data = new FormData(form)

        this.dispatchEvent(new CustomEvent('forma:submit', {
            bubbles: true,
            composed: true,
            detail: { form, data }
        }))

        fetch(action, { method, body: data })
            .then(response => response.json())
            .then(validateJSend)
            .then(response => {
                switch (response.status) {
                    case 'success':
                        this.#state = 'success'
                        this.dispatchEvent(new CustomEvent('forma:success', {
                            bubbles: true,
                            composed: true,
                            detail: { form, response }
                        }))
                        form.reset()
                        break;
                    case 'fail':
                        this.#state = 'fail'
                        this.dispatchEvent(new CustomEvent('forma:fail', {
                            bubbles: true,
                            composed: true,
                            detail: { form, response }
                        }))
                        break;
                    case 'error':
                        throw Error('Forma: JSend: ' + response.message)
                }
            })
            .catch(error => {
                this.#state = 'error'
                this.dispatchEvent(new CustomEvent('forma:error', {
                    bubbles: true,
                    composed: true,
                    detail: { form, error }
                }))

                throw error
            })
            .finally(() => {
                form.inert = false
            })
    }

    /**
     * Adds client information to the form data before submission.
     * @param {CustomEvent} event - The forma:submit event containing form data
     */
    handleAddClientInfo = (event) => {
        event.detail.data.append('forma_client_info', JSON.stringify({
            url: window.location.href,
            title: document.title,
            timestamp: (new Date()).toISOString(),
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            language: navigator.language,
            userAgent: navigator.userAgent,
        }))
    }

    /**
     * Adds imnotarobot token to the form data if configured.
     * @param {CustomEvent} event - The forma:submit event containing form data
     */
    handleAddImNotARobot = (event) => {
        const form = event.detail.form
        const value = this.getAttribute('imnotarobot') || form.dataset.imnotarobot

        if (value) {
            event.detail.data.append('forma_imnotarobot', value)
        }
    }

    /**
     * Displays validation error messages for form fields with errors.
     * @param {CustomEvent} event - The forma:fail event containing error details
     */
    handleShowValidationErrors = (event) => {
        const errors = event.detail.response.data?.errors || null
        if (!errors) {
            return
        }

        const form = event.detail.form

        Array.from(form.elements).forEach((el) => {
            if (el.setCustomValidity) {
                el.setCustomValidity('')
            }
        })

        Object.entries(errors).forEach(([field, errors]) => {
            const message = Array.isArray(errors) ? errors[0] : errors
            const formField = getFormField(form, field)

            if (formField) {
                formField.setCustomValidity(message)
            }
        })

        requestAnimationFrame(() => {
            if (!form.checkValidity()) {
                form.reportValidity()
            }
        })
    }

    /**
     * Clears custom validity error from the form field.
     * @param {Event} event - The input event
     */
    handleClearValidationErrors = (event) => {
        event.target.setCustomValidity('')
    }

    /**
     * Handles forma:success, forma:fail, and forma:error events to display appropriate messages.
     * @param {Event} event - The event (forma:success, forma:fail, or forma:error)
     */
    handleShowMessage = (event) => {
        const type = event.type.split(':')[1]
        const message = event.detail.response?.data?.message
        this.#showMessage(type, message)
    }

    /**
     * Checks if the form is currently in the submit state.
     * @returns {boolean} True if the form is submitting
     * @private
     */
    #isSubmitting() {
        return this.#state === 'submit'
    }

    /**
     * Shows a message in the specified element type.
     * @param {string} type - The message type ('success', 'fail', or 'error')
     * @param {string} message - The message to display
     * @returns {boolean} True if message was displayed, false otherwise
     * @private
     */
    #showMessage(type, message) {
        this.#clearMessages()

        const element = this.querySelector(`forma-${type}`)
        if (!element) {
            return false
        }

        message = message || element.getAttribute('default')
        if (!message) {
            return false
        }

        element.innerHTML = message
        return true
    }

    /**
     * Clears all message elements (success, fail, error).
     * @private
     */
    #clearMessages() {
        this.querySelectorAll('forma-success, forma-fail, forma-error')
            .forEach(el => el.innerText = '')
    }
})

/**
 * Validates JSend response format.
 * @param {Object} response - The JSend response object
 * @returns {Object} The validated response object
 * @throws {Error} If the response format is invalid
 */
function validateJSend(response) {
    const status = response.status

    if (status === undefined) {
        throw Error('Forma: JSend: status field is not present')
    }

    if (!status) {
        throw Error('Forma: JSend: status field is empty')
    }

    if (!['success', 'fail', 'error'].includes(status)) {
        throw Error('Forma: JSend: invalid status: ' + status)
    }

    if ((status === 'success' || status === 'fail') && response.data === undefined) {
        throw Error('Forma: JSend: data field is not present')
    }

    if (status === 'error' && response.message === undefined) {
        throw Error('Forma: JSend: message field is not present')
    }

    return response
}

/**
 * Finds a form field by its name path.
 * Supports nested field names using dot notation (e.g., 'address.city').
 * Also supports array indices (e.g., 'items[0]').
 * @param {HTMLFormElement} form - The form element
 * @param {string} path - The field name path
 * @returns {HTMLElement|null} The matching field element, or null if not found
 */
function getFormField(form, path) {
    const target = path.split('.');
    const fields = form.querySelectorAll('[name]');
    const matches = [];

    fields.forEach(field => {
        const parts = parseFieldName(field.name);

        if (parts.length !== target.length) return;

        let ok = true;

        for (let i = 0; i < parts.length; i++) {
            const p = parts[i];
            const t = target[i];

            if (p === '') continue;

            if (/^\d+$/.test(t)) {
                if (p !== '' && p !== t) {
                    ok = false;
                    break;
                }
            } else {
                if (p !== t) {
                    ok = false;
                    break;
                }
            }
        }

        if (ok) matches.push(field);
    });

    const last = target[target.length - 1];

    if (/^\d+$/.test(last)) {
        return matches[Number(last)] || null;
    }

    return matches[0] || null;
}

function parseFieldName(name) {
    const parts = [];
    const regex = /([^\[\]]+)|\[(.*?)\]/g;
    let m;

    while ((m = regex.exec(name))) {
        parts.push(m[1] ?? m[2]);
    }

    return parts;
}
