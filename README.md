# Сайт с разделом Контакты, в котором офисы компании расположены на карте
Данный сайт, содержит Главную страницу сайта и раздел Контакты в котором отображаются офисы компании на яндекс карте, 6 demo офисов создаются автоматически при первом открытии раздела, если их удалить из админки, перейдя на главную и обратно в раздел с контактами они опять заново создадутся. 
## Правила установки
* php >=8.0
* устанавливаем чистую версию битрикс 
* удаляем из нее все папки кроме bitrix и upload 
* клонируем https://github.com/spbGeg/create-contacts-on-map.git репозиторий в отдельную директорию 
* переносим все содержимое папки create-contacts-on-map на сайт 
* переключиться на шаблон Контакты в настройках сайта
*  в косоли из корня сайта запускаем скрипт
```
php -f local/php_interface/create_elements_contacts.php
```

## С главной можно перейти по меню Контакты или по ссылке "http://адрес-сайта/contacts"
