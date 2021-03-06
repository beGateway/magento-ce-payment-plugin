# BeGateway Payment Module for Magento CE

[Русская версия](#Модуль-оплаты-begateway-для-magento-ce)

This is a Payment Module for Magento Community Edition, that gives you the ability to process payments through payment service providers running on BeGateway platform.

## Requirements

  * Magento Community Edition 1.8+
  * [BeGateway PHP API library v2.7.x](https://github.com/begateway/begateway-api-php) - (Integrated in Module)
  * PCI DSS certified server in order to use ```BeGateway Direct```

*Note:* this module has been tested only with Magento __Community Edition__, it may not work as intended with Magento __Enterprise Edition__

## Installation (manual)

  * [Download the Payment Module archive](https://github.com/begateway/magento-ce-payment-plugin/raw/master/magento-ce-payment-plugin.zip), unpack it and upload its contents to a new folder ```<root>/``` of your Magento installation


## Configuration

  * Login inside the __Admin Panel__ and go to ```System``` -> ```Configuration``` -> ```Payment Methods```
  * If the Payment Module Panel ```BeGateway``` is not visible in the list of available Payment Methods,
  go to  ```System``` -> ```Cache Management``` and clear Magento Cache by clicking on ```Flush Magento Cache```
  * Go back to ```Payment Methods``` and click the button ```Configure``` under the payment method ```BeGateway Checkout``` or ```BeGateway Direct``` to expand the available settings
  * Set ```Enabled``` to ```Yes```, set the correct credentials, select your prefered transaction types and additional settings and click ```Save config```

## Configure Magento over secured HTTPS Connection

This configuration is needed for ```BeGateway Direct``` Method to be usable.

Steps:

  * Ensure you have installed a valid SSL Certificate on your Web Server & you have configured your Virtual Host correctly.
  * Login to Magento Admin Panel
  * Navigate to ```System``` -> ```Configuration``` -> ```General``` -> ```Web```
  * Expand Tab ```Secure``` and set ```Use Secure URLs on Storefront``` and ```Use Secure URLs in Admin``` to ```Yes```
  * Set your ```Base URL``` and click ```Save Config```
  * It is recommended to add a **Rewrite Rule** from ```http``` to ```https``` or to configure a **Permanent Redirect** to ```https``` in your virtual host

## Test data

You can use the following information to adjust the payment method in test mode:

  * Shop Id ```361```
  * Shop Secret Key ```b8647b68898b084b836474ed8d61ffe117c9a01168d867f24953b776ddcb134d```
  * Checkout Domain ```checkout.begateway.com```
  * Gateway Domain ```demo-gateway.begateway.com```
  * Test mode `yes`

Use the following test card to make successful test payment:

  * Card number: 4200000000000000
  * Name on card: JOHN DOE
  * Card expiry date: 01/30
  * CVC: 123

Use the following test card to make failed test payment:

  * Card number: 4005550000000019
  * Name on card: JOHN DOE
  * Card expiry date: 01/30
  * CVC: 123

# Модуль оплаты BeGateway для Magento CE

Модуль оплаты для Magento Community Edition, который даст вам возможность начать принимать платежи через провайдеров платежей, использующих платформу beGateway.

## Требования

  * Magento Community Edition 1.8+
  * [BeGateway PHP API библиотека v2.7.x](https://github.com/begateway/begateway-api-php) - (поставляется с модулем)
  * PCI DSS сертифицированный сервер, чтобы принимать платежи через ```BeGateway Direct```

*Примечание:* этот модуль тестировался только с Magento __Community Edition__ и может работать не стабильно с Magento __Enterprise Edition__

## Установка (ручная)

  * [Скачайте архив модуля](https://github.com/begateway/magento-ce-payment-plugin/raw/master/magento-ce-payment-plugin.zip), распакуйте его и скопируйте его содержимое в новую директорию ```<root>/``` вашей Magento инсталляции

## Настройка

  * Войдите в личный кабинет администратора и перейдите в ```Система``` -> ```Конфигурация``` -> ```Продажи``` -> ```Методы оплаты```
  * Если панели модуля оплаты ```BeGateway Checkout``` или ```BeGateway Direct``` не видны в списке доступных методов оплаты, то перейдите в ```Система``` -> ```Управление кэшем``` и очистите Magento кэш, нажав ```Очистить кэш Magento```
  * Вернитесь назад в ```Методы оплаты``` и нажмите кнопку ```Настроить``` под способом оплаты ```BeGateway Checkout``` или ```BeGateway Direct```, чтобы раскрыть доступные настройки
  * Выберите ```Да``` в выпадающем списке параметра ```Включено```, задайте данные вашего магазина, выберите тип операции, доступные способы оплаты и прочие настройки. Нажмите ```Сохранить конфигурацию```, чтобы их сохранить

## Настройть Magento для работы через шифрованное соединение

Данная настройка необходима для использования способа оплаты ```BeGateway Direct```.

Шаги (названия параметров могут отличаться из-за различных пакетов русификации Magento):

  * Убедитесь, что вы установили рабочий SSL сертификат на вашем веб-сервере и произвели необходимые настройки.
  * Зайдите в панель администратора Magento
  * Перейдите в ```Система``` -> ```Конфигурация``` -> ```Основное``` -> ```Интернет```
  * Раскройте закладку ```Безопасное соединение``` и установите ```Использовать защищённые URL в пользовательской части``` и ```Использовать защищённые URL в панели администрирования``` в ```Да```
  * Задайте ваш ```Базовый URL``` и нажмите ```Сохранить конфигурацию```
  * Рекомендуем добавить **Rewrite Rule** с ```http``` на ```https``` или настроить **Permanent Redirect** на ```https``` в настройках вашего веб-сервера

## Тестовые данные

Вы можете использовать приведенные ниже тестовые данные, чтобы протестировать оплату.

  * Id магазина ```361```
  * Секретный ключ магазина ```b8647b68898b084b836474ed8d61ffe117c9a01168d867f24953b776ddcb134d```
  * Домен страницы оплаты ```checkout.begateway.com```
  * Домен платежного шлюза ```demo-gateway.begateway.com```

Используйте следующие данные карты для успешного тестового платежа:

  * Номер карты: 4200000000000000
  * Имя на карте: JOHN DOE
  * Месяц срока действия карты: 01/30
  * CVC: 123

Используйте следующие данные карты для неуспешного тестового платежа:

  * Номер карты: 4005550000000019
  * Имя на карте: JOHN DOE
  * Месяц срока действия карты: 01/30
  * CVC: 123
