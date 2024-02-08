# Forma

## Установка

1. Скачать и распаковать [последнюю версию](https://github.com/PopArtDesign/forma/archive/refs/heads/main.zip)

2. Подключить файл [forma.min.js](https://raw.githubusercontent.com/PopArtDesign/forma/main/js/forma.min.js)

   ```html
   <script src="js/forma.min.js"></script>
   ```

3. Обернуть форму в элемент `<forma-form>`. Атрибут `target` формы должен содержать адрес файла [forma.php](forma.php)

   ```html
   <forma-form>
       <form action="/path/to/forma.php" method="post">
       </form>
   </forma-form>
   ```

4. Добавить элементы для вывода сообщений:

   #### Сообщение об успешной отправке:

   ```html
   <forma-success default="Спасибо! Мы свяжемся с вами в ближайшее время!"><forma-success>
   ```

   #### Сообщение об ошибке (то, что зависит от отправителя):

   - неправильно заполненные поля
   - и т.д.

   ```html
   <forma-fail default="Произошла ошибка! Проверьте данные и попробуйте отправить форму ещё раз!"></forma-fail>
   ```

   #### Сообщение о фатальной ошибке (то, что не зависит от отправителя):

   - ошибка сервера
   - невозможность отправки письма
   - и т.д.

   ```html
   <forma-error default="Произошла ошибка! Попробуйте отправить форму ещё раз позднее!"></forma-error>
   ```

## Пример

```html
<forma-form class="call-me">
    <h2>Заказать звонок</h2>

    <div class="call-me-success">
        <forma-success default="Спасибо! Мы свяжемся с вами в ближайшее время!"><forma-success>
    </div>

    <div class="call-me-erros">
        <forma-fail default="Произошла ошибка! Проверьте данные и попробуйте отправить форму ещё раз!"></forma-fail>
        <forma-error default="Произошла ошибка! Попробуйте отправить форму ещё раз позднее!"></forma-error>
    </div>

    <form action="/path/to/forma.php" method="post">
        <input type="text" name="name" required placeholder="Введите имя" />
        <input type="tel" name="phone" required placeholder="Введите телефон" />

        <button type="submit">Отправить</button>
    </form>
</forma-form>
```

## Конфигурация

После установки поведение формы можно изменить в следующих файлах:

 - [config.php](config.php): файл с основной конфигурацией
 - [forma.php](forma.php): обработчик формы
 - [email.php](email.php): шаблон письма

## Состояния

Элемент `<forma-form>` может находиться в нескольких состояниях:

- `initial` - исходное состояние
- `submit` - форма отправляется
- `success` - форма успешно отправлена
- `fail` - ошибка, которую можно исправить
- `error` - фатальная ошибка

Информация о состоянии доступна через атрибут `state`. Это можно использовать для настройки внешнего вида формы через CSS: 

```css
/** Сокрытие кнопки во время отправки формы */
forma-form[state="submit"] [type="submit"] {
    display: none;
}
```

## I'm not a Robot

В простейшем случае, для защиты от роботов можно добавлять в форму специальное поле при помощи JavaScript:

```html
<form action="/forma.php" method="post" data-imnotarobot="imnotarobot!">
```

Значение поля нужно указать в [config.php](config.php):

```php
define('IMNOTAROBOT_VALUE', 'imnotarobot!');
```

## reCAPTCHA v3

1. Создать капчу в [консоли администратора](https://www.google.com/recaptcha/admin/create)

2. Подключить скрипт:

   ```html
   <script src="https://www.google.com/recaptcha/api.js"></script>
   ```

3. Добавить к форме идентификатор (например, `id="myForm"`)

   ```html
   <form id="myForm">
   </form>
   ```

4. Изменить кнопку отправки, указав ключ для капчи и корректный идентификатор формы:

   ```html
   <button
       type="submit"
       class="g-recaptcha"
       data-sitekey="КЛЮЧ ДЛЯ КАПЧИ"
       data-action="submit"
       data-callback="submitMyForm"
    >
       <script>function submitMyForm(token) { document.forms['myForm'].requestSubmit() }</script>

       Отправить
    </button>
    ```

5. В файле [config.php](config.php) указать секретный ключ:

   ```php
   define('RECAPTCHA_SECRET', 'SECRET_KEY');
   ```
