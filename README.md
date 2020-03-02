# yandex.turboapi

**Описание**

Модуль позволяет настроить выгрузку турбо-страниц двух вариантов:
- YML-файл;
- API Турбо‑страниц.

Первый вариант мы рекомендуем использовать для интернет-магазинов с учетом заполненных свойств товаров.

_Внимание если у вас не заполнены свойства товаров и они записаны в описание используйте стандартный метод выгрузки через API, иначе ваши карточки товаров будут практически пустыми._

Инструкцию по настройке выгрузки через API [читайте](https://advcont.ru/articles/povyshenie-prodazh/nastroyka-api-turbo-stranits-yandeksa-dlya-1s-bitriks/).
Инструкцию по удалению турбо-страниц [читайте](https://advcont.ru/articles/povyshenie-prodazh/kak-udalit-turbo-stranitsy-v-1s-bitriks/).

Модуль поддерживает передачу данных для следующих типов контента:  
* Кнопка «В корзину» - с переходом посетителя в корзину на сайте с выбранным товаром;
* Кнопка «Заказ в 1 клик» - требуется доработка под шаблон сайта;
* Ссылки в тексте;
* Галерея картинок;
* Картинки в тексте (без описания);
* Таблицы в тексте;
* Блок товары из категории;
* Во всех вложенных элементах channel и item автоматически; кодирует символы UTF-8 KOI8-R;
* Кнопки «Поделиться»;
* Элементы меню;
* Видео из iframe и видео в тексте;
* Цитаты в тексте;
* Непрерывная лента.
* Блоки обратной связи
* Формы обратной связи

**Дополнительная информация:**

С помощью модуля вы можете настроить вывод разных блоков информации для элементов в зависимости от инфоблока, т.к. выгрузка для каждого инфоблока настраивается индивидуально.


С помощью этого можно настроить разные виды:
* вывод дополнительных табов с информацией (доп. Блоки с видео, галереей, и т. д.)
настройка меню для каждого инфоблока — например вывод ссылок на внутренние разделы инфоблока отдельно для каждого;
* вывод разных блоков обратной связи — для разных групп товаров.

И другие возможности, которые вы можете настроить самостоятельно при выгрузке.

Проверить работу выгруженных элементов можно с помощью URL-адреса: https://yandex.ru/turbo?text=url_адрес_страницы.

**_Поддерживается два типа товара:_**
* TYPE_PRODUCT - Простой товар
* TYPE_SKU - Товар с торговыми предложениями

**_Установка и настройка_**

Установка стандартная. Скачайте, в разделе «Установленные решения»  выбираем «Установить».
[Инструкцию по настройке модуля](help/setting/README.md)