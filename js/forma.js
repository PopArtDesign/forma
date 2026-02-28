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

        this.form = this.querySelector('form')
        if (!this.form) {
            throw Error('Forma: <form> element not found.')
        }

        this.successMessage = this.querySelector('forma-success')
        this.failMessage = this.querySelector('forma-fail')
        this.errorMessage = this.querySelector('forma-error')

        this.state = 'initial'

        this.addEventListener('forma:submit', this.addClientInfo)
        this.addEventListener('forma:submit', this.addImNotARobot)

        this.dispatchEvent(new CustomEvent('forma:init', {
            bubbles: true,
            composed: true,
        }))
    }

    connectedCallback() {
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

        this.submitForm(this.form)
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

    submitForm(form) {
        const action = form.getAttribute('action')
        const method = form.getAttribute('method') || 'post'
        const data = new FormData(form)

        this.dispatchEvent(new CustomEvent('forma:submit', {
            bubbles: true,
            composed: true,
            detail: { form, data }
        }))

        return fetch(action, { method, body: data })
            .then(response => response.json())
            .then(validateJSend)
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
