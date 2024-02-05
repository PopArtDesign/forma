(function(window, document) {
    function getOptions(element) {
        return {
            successMessage: element.getAttribute('data-forma-success') || 'Form was send successfully!',
            failMessage: element.getAttribute('data-forma-fail') || 'Invalid entries found. Please correct and submit again!',
            errorMessage: element.getAttribute('data-forma-error') || 'An error has occurred, please try again later!',
        }
    }

    function showMessage(element, type, message) {
        const messages = element.querySelector('.forma-messages')
        if (!messages || !message) {
            return false
        }

        messages.innerText = ''
        messages.insertAdjacentHTML(
            'afterbegin',
            `<p class="forma-message forma-message-${type}">${message}</p>`
        );
    }

    function success(element, message) {
        element.classList.remove('forma-fail')
        element.classList.add('forma-success')

        if (message) {
            showMessage(element, 'success', message)
        }
    }

    function fail(element, message) {
        element.classList.remove('forma-success')
        element.classList.add('forma-fail');

        if (message) {
            showMessage(element, 'fail', message)
        }
    }

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

    const submitting = Symbol('Forma Submitting')

    function isSubmitting(element) {
        return element[submitting] || false
    }

    function disableFormWhileSubmitting(element, form) {
        element.classList.add('forma-submitting')
        element[submitting] = true
        form.inert = true
    }

    function enableFormAfterSubmit(element, form) {
        element.classList.remove('forma-submitting')
        delete element[submitting]
        form.inert = false
    }

    document.addEventListener('submit', (e) => {
        const element = e.target.closest('[data-forma]')
        if (!element) {
            return
        }

        e.preventDefault();

        const form = element.querySelector('form');
        if (!form) {
            throw Error('Forma: <form> element not found.')
        }

        if (isSubmitting(form)) {
            return
        }

        disableFormWhileSubmitting(element, form)

        const options = getOptions(element)

        fetch(options.action || form.getAttribute('action'), {
            method: form.getAttribute('method') || 'post',
            body: new FormData(form),
        })
            .then(response => response.json())
            .then(validateJSend)
            .then(response => {
                switch (response.status) {
                    case 'success':
                        success(element, response.data?.message || options.successMessage)
                        form.reset()
                        break;
                    case 'fail':
                        fail(element, response.data?.message || options.failMessage)
                        break;
                    case 'error':
                        throw Error('Forma: JSend: ' + response.message)
                        break;
                }
            })
            .catch((err) => {
                console.log(err)
                fail(element, options.errorMessage)
            })
            .finally(() => {
                enableFormAfterSubmit(element, form)
            })
    })
}(window, window.document));
