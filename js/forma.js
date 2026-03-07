customElements.define('forma-form', class extends HTMLElement {
    get state() {
        return this.getAttribute('state') || 'initial'
    }

    set state(value) {
        if (!['initial', 'submit', 'success', 'fail', 'error'].includes(value)) {
            throw Error('Forma: invalid state: '.value)
        }

        this.setAttribute('state', value)
    }

    constructor() {
        super()

        this.state = 'initial'

        this.addEventListener('forma:submit', this.addClientInfo)
        this.addEventListener('forma:submit', this.addImNotARobot)
        this.addEventListener('forma:fail', this.showValidationErrors)

        this.dispatchEvent(new CustomEvent('forma:init', {
            bubbles: true,
            composed: true,
        }))
    }

    connectedCallback() {
        this.form = this.querySelector('form')
        if (!this.form) {
            throw Error('Forma: <form> element not found.')
        }

        this.successMessage = this.querySelector('forma-success')
        this.failMessage = this.querySelector('forma-fail')
        this.errorMessage = this.querySelector('forma-error')

        this.form.addEventListener('submit', this.submitHandler)
    }

    disconnectedCallback() {
        this.form.removeEventListener('submit', this.submitHandler)
    }

    isSubmitting() {
        return this.state === 'submit'
    }

    disableForm() {
        this.form.inert = true
    }

    enableForm() {
        this.form.inert = false
    }

    reset() {
        this.form.reset()
    }

    showMessage(type, message) {
        this.clearMessages()

        const element = this[`${type}Message`]
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

    showSuccessMessage(message) {
        return this.showMessage('success', message)
    }

    showFailMessage(message) {
        return this.showMessage('fail', message)
    }

    showErrorMessage(message) {
        return this.showMessage('error', message)
    }

    clearMessages() {
        this.successMessage.innerText = ''
        this.failMessage.innerText = ''
        this.errorMessage.innerText = ''
    }

    submitHandler = (event) => {
        event.preventDefault()

        if (this.isSubmitting()) {
            return
        }

        this.state = 'submit'
        this.disableForm()

        const action = this.form.getAttribute('action')
        const method = this.form.getAttribute('method') || 'post'
        const data = new FormData(this.form)

        this.dispatchEvent(new CustomEvent('forma:submit', {
            bubbles: true,
            composed: true,
            detail: { form: this.form, data }
        }))

        fetch(action, { method, body: data })
            .then(response => response.json())
            .then(validateJSend)
            .then(response => {
                switch (response.status) {
                    case 'success':
                        this.state = 'success'
                        this.showSuccessMessage(response.data?.message)
                        this.dispatchEvent(new CustomEvent('forma:success', {
                            bubbles: true,
                            composed: true,
                            detail: { response }
                        }))
                        this.reset()
                        break;
                    case 'fail':
                        this.state = 'fail'
                        this.showFailMessage(response.data?.message)
                        this.dispatchEvent(new CustomEvent('forma:fail', {
                            bubbles: true,
                            composed: true,
                            detail: { response }
                        }))
                        break;
                    case 'error':
                        throw Error('Forma: JSend: ' + response.message)
                }
            })
            .catch(error => {
                this.state = 'error'
                this.showErrorMessage()
                this.dispatchEvent(new CustomEvent('forma:error', {
                    bubbles: true,
                    composed: true,
                    detail: { error }
                }))

                throw error
            })
            .finally(() => {
                this.enableForm()
            })
    }

    addClientInfo = (event) => {
        event.detail.data.append('forma_client_info', JSON.stringify({
            url: window.location.href,
            title: document.title,
            timestamp: (new Date()).toISOString(),
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            language: navigator.language,
            userAgent: navigator.userAgent,
        }))
    }

    addImNotARobot = (event) => {
        const value = this.getAttribute('imnotarobot') || this.form.dataset.imnotarobot

        if (value) {
            event.detail.data.append('forma_imnotarobot', value)
        }
    }

    showValidationErrors = (event) => {
        const errors = event.detail.response.data?.errors || null
        if (!errors) {
            return
        }

        Array.from(this.form.elements).forEach((el) => {
            if (el.setCustomValidity) {
                el.setCustomValidity('')
            }
        })

        Object.entries(errors).forEach(([field, errors]) => {
            const message = Array.isArray(errors) ? errors[0] : errors
            const formField = getFormField(this.form, field)

            if (formField) {
                formField.setCustomValidity(message)

                formField.addEventListener('input', (event) => {
                    event.target.setCustomValidity('')
                }, { once: true })
            }
        })

        requestAnimationFrame(() => {
            if (!this.form.checkValidity()) {
                this.form.reportValidity()
            }
        })
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
