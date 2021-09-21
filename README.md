## Features
- Opening a file
- Save save

## Installing

```bash
cd /var/www/owncloud/apps-external
git clone https://github.com/ONLYOFFICE/onlyoffice-owncloud-wopi.git onlyoffice_wopi
```

## Setting
Settings to сonfig file owncloud/config/config.php:
```bash
"onlyoffice_wopi" =>
    array (
        "DocumentServerUrl" => "http(s)://document_server_address/"
    ),
```
