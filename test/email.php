<p>
    Новое сообщение:
</p>
<table>
    <tr>
        <td><strong>Имя:</strong>&nbsp;&nbsp;</td>
        <td><?php echo \htmlspecialchars($name); ?></td>
    </tr>
    <tr>
        <td><strong>Телефон:</strong>&nbsp;&nbsp;</td>
        <td><?php echo \htmlspecialchars($phone); ?></td>
    </tr>
    <tr>
        <td><strong>Email:</strong>&nbsp;&nbsp;</td>
        <td><?php echo \htmlspecialchars($email) ?: 'не указан'; ?></td>
    </tr>
    <tr>
        <td><strong>Сообщение:</strong>&nbsp;&nbsp;</td>
        <td><?php echo \htmlspecialchars($message); ?></td>
    </tr>
</table>

<h4>Техническая информация</h4>

<?php \PopArtDesign\Forma\clientInfo(); ?>
