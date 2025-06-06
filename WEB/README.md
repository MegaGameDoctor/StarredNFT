## API
Серверная часть представляет из себя набор нужных методов для работы сайта и проведении всех его функциональных процессов. Для обращения к API используется метод GET или POST. Каждый ответ формируется в формате JSON и содержит в себе поле status. Если status=false, значит данные следует брать из поля error, которое содержит текст ошибки. При status=true данные берутся из поля data.
1) checkSession.php - Принимает на вход параметры tg (Айди телеграмм), initData (Авторизационные данные Телеграмм). Возвращает либо ошибку и status=false, либо status=true, создавая аккаунт в базе данных или обновляя поле с IP у уже существующего аккаунта.
2) buyItem.php - Принимает на вход параметры tg (Айди телеграмм) и id (Айди товара). В случае успешной валидации и проверки данных возвращает status=true и выполняет процесс приобретения товара, в ином случае status=false и ошибка.
3) getAllOffers.php - Принимает на вход tg (Айди телеграмм), page (Страница) и limit (Лимит элементов в ответе). Возвращает список всех активных товаров. Также этот метод может принимать добавочные параметры в запрос в зависимости от требуемой сортировки возвращаемых значений.
4) getConfig.php - Возвращает некоторые конфигурационные данные, необходимые сайту для корректной работы с визуалом.
5) getGiftVisualData.php - Принимает на вход tg (Айди телеграмм), type (Тип подарка) и number (Номер подарка). Возвращает всевозможные данные об НФТ подарке, которые возвращает телеграмм API.
6) makeOffer.php - Принимает на вход tg (Айди телеграмм), price (Цена в звездах), number (Номер подарка) и type (Тип подарка). Позволяет разместить свой товар на маркете. После успешной валидации товар добавляется в базу, с пользователя списывается комиссия за создание товара.
7) user/getBalance.php - Принимает на вход tg (Айди телеграмм). Возвращает баланс в звездах.
8) user/getOffers.php - Принимает на вход tg (Айди телеграмм), page (Страница), limit (Лимит элементов в ответе) и sortBy (Параметры сортировки). Возвращает список всех размещенных или неактивных товаров пользователя.
9) user/cancelOffer.php - Принимает на вход tg (Айди телеграмм) и id (Айди товара). При успешной валидации данных отменяет размещение товара пользователя на маркете.
10) user/topUpBalance.php - Принимает на вход tg (Айди телеграмм) и sum (Сумма в звездах). При успешном запросе отправляет запрос к боту для создания транзакции в звездах на пополнение.
11) user/withdrawBalance.php - Принимает на вход tg (Айди телеграмм), sum (Сумма в выбранной валюте), type (Тип валюты) и wallet (Номер карты/Адрес кошелька TON). Создаёт запрос на вывод средств, вывод осуществляет бот.
## База данных
База данных MySQL состоит из 6 таблиц:
1) userdata - Таблица с данными о пользователях. Включает в себя столбцы id (Айди), tg_id (Айди телеграмм), lastIP (IP последней сессии) и balance (Баланс в звездах).
2) market - Таблица с данными о товарах. Включает в себя столбцы id (Айди), owner (Айди владельца), category (Название категории), name (Название товара), nameNumber (Редкость модели), price (Цена), number (Номер подарка), patternName (Название узора), patternNumber (Редкость узора), backName (Название фона), backNumber (Редкость фона), startDate (Дата размещения), endDate (Дата истечения размещения), rarity (Редкость товара) и status (Статус товара).
3) active_offers - Таблица с данными о p2p сделках. Включает в себя столбцы id (Айди), buyer (Айди покупателя), seller (Айди продавца) и status (Статус сделки).
4) msgsForSend - Таблица с очередью на отправку сообщению боту. Включает в себя столбцы id (Айди), target (Айди получателя), message (Сообщение) и additional (Дополнительные данные для обработки ботом).
5) withdraw_requests - Таблица с данными о запросах на вывод средств. Включает в себя столбцы id (Айди), user (Айди пользователя), sum (Сумма вывода), type (Тип валюты), wallet (Номер карты/Адрес кошелька TON) и status (Статус вывода).
## Размещение
Чтобы разместить - достаточно загрузить файлы в любую удобную дирректорию WEB сервера и указать данные для подключения к базе данных в файле config.php, а также импортировать в саму базу данных файл db.sql из этого репозитория.
