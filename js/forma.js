customElements.define('forma-form', class extends HTMLElement {
    get #state() {
        return this.getAttribute('state') || 'initial'
    }

    set #state(value) {
        if (!['initial', 'submit', 'success', 'fail', 'error'].includes(value)) {
            throw Error('Forma: invalid state: ' + value)
        }

        this.setAttribute('state', value)
    }

    constructor() {
        super()

        this.#state = 'initial'

        this.addEventListener('submit', this.handleSubmit)
        this.addEventListener('forma:submit', this.handleAddClientInfo)
        this.addEventListener('forma:submit', this.handleAddImNotARobot)
        this.addEventListener('forma:fail', this.handleShowValidationErrors)
        this.addEventListener('input', this.handleClearValidationErrors)
    }

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
                        this.#showSuccessMessage(response.data?.message)
                        this.dispatchEvent(new CustomEvent('forma:success', {
                            bubbles: true,
                            composed: true,
                            detail: { form, response }
                        }))
                        form.reset()
                        break;
                    case 'fail':
                        this.#state = 'fail'
                        this.#showFailMessage(response.data?.message)
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
                this.#showErrorMessage()
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

    handleAddImNotARobot = (event) => {
        const form = event.detail.form
        const value = this.getAttribute('imnotarobot') || form.dataset.imnotarobot

        if (value) {
            event.detail.data.append('forma_imnotarobot', value)
        }
    }

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

    handleClearValidationErrors = (event) => {
        event.target.setCustomValidity('')
    }

    #isSubmitting() {
        return this.#state === 'submit'
    }

    #showSuccessMessage(message) {
        return this.#showMessage('success', message)
    }

    #showFailMessage(message) {
        return this.#showMessage('fail', message)
    }

    #showErrorMessage(message) {
        return this.#showMessage('error', message)
    }

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

    #clearMessages() {
        this.querySelectorAll('forma-success, forma-fail, forma-error')
            .forEach(el => el.innerText = '')
    }
})

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
