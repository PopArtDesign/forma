# Forma

## Установка

1. Подключить файл [forma.min.js](https://raw.githubusercontent.com/PopArtDesign/forma/main/js/forma.min.js)

   ```html
   <script src="js/forma.min.js"></script>
   ```

2. Обернуть форму в элемент `<forma-form>` 

   ```html
   <forma-form>
       <form action="/path/to/forma.php" method="post">
       </form>
   </forma-form>
   ```

3. Добавить элементы для вывода сообщений:

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
