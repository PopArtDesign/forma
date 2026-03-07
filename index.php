<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width" />
        <title>Forma demo page</title>
<style>
* {
    box-sizing: border-box;
}

.container {
    max-width: 400px;
    margin: 0 auto;
}

.feedback-header {
    text-align: center;
}

.feedback {
    padding: 1em;
    font-size: 16px;
    background: #eee;
    border-radius: 3px;
}

.feedback header {
    text-align: center;
}

.feedback input, .feedback textarea {
    font-size: 1.2em;
    padding: 0.5em;
    margin: 0.5em 0;
    display: block;
    width: 100%;
    border: 1px solid #cddad0;
    border-radius: 0.2em;
}

.feedback button[type="submit"] {
    font-size: 1.2em;
    margin: 0.5em auto;
    padding: 0.5em;
    display: block;
    width: 100%;
    border: 1px solid #cddad0;
    border-radius: 0.2em;
    border: none;
    background: #336600;
    color: #fff;
    text-align: center;
}

.feedback button[type="submit"]:hover {
    cursor: pointer;
    background: #5c8f48;
}

.feedback .feedback-submit-loading {
    display: none;
}

.feedback forma-form[state="submit"] form {
    opacity: 0.5;
}

.feedback forma-form[state="submit"] .feedback-submit-text {
    display: none;
}

.feedback forma-form[state="submit"] .feedback-submit-loading {
    display: initial;
}

.feedback .feedback-messages {
    text-align: center;
}

.feedback .feedback-messages .feedback-success {
    color: green;
}

.feedback .feedback-messages .feedback-error {
    color: red;
}

.feedback footer {
    margin: 1em 0 0 0;
    color: #a9a9a9;
    font-size: 0.8em;
    text-align: center;
}
</style>
        <script defer src="js/forma.js"></script>
        <script>
        document.addEventListener('forma:submit', (e) => {
            console.log(e);
        })
        </script>
    </head>
    <body>
        <div class="container feedback">
           <h2 class="feedback-header">Обратная связь</h2>
           <forma-form imnotarobot="imnotarobot!">
                <header>
                    Оставьте нам сообщение и мы с Вами свяжемся.
                </header>
                <!-- Контейнер для сообщений -->
                <div class="feedback-messages">
                    <p class="feedback-success">
                        <forma-success default="Спасибо! Мы свяжемся с Вами в ближайшее время!"><forma-success>
                    </p>
                    <p class="feedback-error">
                        <forma-fail default="Произошла ошибка! Проверьте данные и попробуйте отправить форму ещё раз!"></forma-fail>
                        <forma-error default="Произошла ошибка! Попробуйте отправить форму ещё раз позднее!"></forma-error>
                    </p>
                </div>
                <form action="/test/handler.php" method="post" novalidate>
                    <label>
                        Ваше имя *
                        <input type="text" name="name" required placeholder="Ваше имя" />
                    </label>
                    <label>
                        Ваш телефон *
                        <input type="tel" name="phone" required placeholder="Ваш телефон" />
                    </label>
                    <label>
                        Ваш email
                        <input type="email" name="email" placeholder="Ваш email" />
                    </label>
                    <label>
                        Сообщение *
                        <textarea name="message" required placeholder="Сообщение"></textarea>
                    </label>
                    <label>
                        Файл
                        <input type="file" name="file" placeholder="Файл для прикрепления" />
                    </label>

                    <button type="submit">
                        <span class="feedback-submit-text">Отправить</span>
                        <span class="feedback-submit-loading">Отправка...</span>
                    </button>
                </form>
                <footer>
                    Ваши данные не будут переданы третьим лицам
                </footer>
            </forma-form>
        </div>
    </body>
</html>
