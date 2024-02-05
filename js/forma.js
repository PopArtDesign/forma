customElements.define('forma-form', class extends HTMLElement {
    get state() {
        return this.getAttribute('state') || 'initial'
    }

    set state(value) {
        if (!['initial', 'submit', 'success', 'fail', 'error'].includes(value)) {
            throw Error('Forma: invalid state: ' . value)
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

        element.innerText = message
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
        const body = new FormData(this.form)

        fetch(action, { method, body })
            .then(response => response.json())
            .then(this.validateJSend)
            .then(response => {
                switch (response.status) {
                    case 'success':
                        this.state = 'success'
                        this.showSuccessMessage(response.data?.message)
                        this.reset()
                        break;
                    case 'fail':
                        this.state = 'fail'
                        this.showFailMessage(response.data?.message)
                        break;
                    case 'error':
                        this.state = 'error'
                        throw Error('Forma: JSend: ' + response.message)
                        break;
                }
            })
            .catch(error => {
                this.state = 'error'
                this.showErrorMessage()

                throw error
            })
            .finally(() => {
                this.enableForm()
            })
    }

    validateJSend = (response) => {
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
})

document.addEventListener('submit', (event) => {
    const form = event.target
    if (!form.hasAttribute('data-secret')) {
        return
    }

    const secret = form.getAttribute('data-secret')

    let input = form['_secret']
    if (!input) {
        input = document.createElement('input')
        input.name = '_secret'
        input.type = 'hidden'
        form.append(input);
    }

    input.value = secret
}, { capture: true })
