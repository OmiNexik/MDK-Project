# 🎬 CineFlow

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-005C84?style=for-the-badge&logo=mysql&logoColor=white)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)

CineFlow - это современная веб-платформа для просмотра фильмов и ТВ-шоу с удобным интерфейсом и системой рекомендаций.

## 🚀 Особенности

- 🔐 Безопасная система аутентификации
- ✉️ Подтверждение email при регистрации
- 🎯 Персонализированные рекомендации
- 📱 Адаптивный дизайн
- 🌙 Темная тема

## 🛠 Технологии

- **Frontend**: HTML5, CSS3, JavaScript
- **Backend**: PHP 8
- **База данных**: MySQL
- **Почтовый сервис**: PHPMailer
- **Безопасность**: Password Hashing, Email Verification

## ⚙️ Установка

1. Клонируйте репозиторий:
```bash
git clone https://github.com/yourusername/CineFlow.git
```

2. Установите зависимости через Composer:
```bash
composer install
```

3. Настройте базу данных:
- Создайте базу данных MySQL
- Импортируйте структуру из `database.sql`
- Обновите параметры подключения в конфигурационном файле

4. Настройте отправку email:
- Создайте файл `mail_config.php`
- Укажите настройки SMTP

5. Запустите локальный сервер:
```bash
php -S localhost:8000
```

## 📋 Требования

- PHP >= 8.0
- MySQL >= 5.7
- Composer
- Настроенный SMTP-сервер для отправки email

## 🔒 Безопасность

- Хеширование паролей с использованием современных алгоритмов
- Защита от SQL-инъекций через подготовленные запросы
- Проверка и валидация email
- Безопасные сессии и куки

## 📝 Лицензия

Этот проект распространяется под лицензией MIT. Подробности в файле [LICENSE](LICENSE).

## 👥 Авторы

- [Володя](https://github.com/OmiNexik)
- [Ярославчик](https://github.com/anq308)

## 🤝 Вклад в проект

Мы приветствуем ваш вклад в развитие проекта! Для этого:

1. Форкните репозиторий
2. Создайте ветку для ваших изменений
3. Внесите изменения
4. Создайте Pull Request

## 📞 Контакты

Если у вас есть вопросы или предложения, создайте [Issue](https://github.com/yourusername/CineFlow/issues) или свяжитесь с нами напрямую.

---
⭐️ Если вам понравился проект, не забудьте поставить звезду!
