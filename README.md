# forms-simple-captcha
addon for enjoys/forms

## Run built-in server for view example
```shell
php -S localhost:8000 -t ./example .route
```
### Options
 - **width** `int` ширина картинки `default: 150`
 - **height** `int` высота картинки `default: 50`
 - **size** `int` количество символов `default: 6`
 - **chars**  `string` перечисление используемых символов `default: qwertyuiopasdfghjklzxcvbnm1234567890`
 - **font** `string` название шрифта, или полный путь `default: OhioKraft.otf`
 - **gd_FontPath** `string` установка глобальной директории шрифтов для GD `default: './fonts'`
