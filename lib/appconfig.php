<?php
/**
 *
 * (c) Copyright Ascensio System SIA 2021
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 */

namespace OCA\Onlyoffice_Wopi;

use OCP\IConfig;

/**
 * Application configutarion
 *
 * @package OCA\Onlyoffice
 */
class AppConfig {

    /**
     * Application name
     *
     * @var string
     */
    private $appName;

    /**
     * Config service
     *
     * @var IConfig
     */
    private $config;


    /**
     * The config key for the document server address
     *
     * @var string
     */
    private $_documentserver = "DocumentServerUrl";

    /**
     * The config key for the document server address
     *
     * @var string
     */
    private $_secret = "secret";

    /**
     * @param string $AppName - application name
     */
    public function __construct($AppName) {

        $this->appName = $AppName;

        $this->config = \OC::$server->getConfig();
    }

    /**
     * Get value from the system configuration
     *
     * @param string $key - key configuration
     * @param bool $system - get from root or from app section
     *
     * @return string
     */
    public function GetSystemValue($key, $system = false) {
        if ($system) {
            return $this->config->getSystemValue($key);
        }
        if (!empty($this->config->getSystemValue($this->appName))
            && array_key_exists($key, $this->config->getSystemValue($this->appName))) {
            return $this->config->getSystemValue($this->appName)[$key];
        }
        return null;
    }

    /**
     * Get the document service address from the application configuration
     *
     * @return string
     */
    public function GetDocumentServerUrl() {

        $url = $this->GetSystemValue($this->_documentserver);

        if ($url !== "/") {
            $url = rtrim($url, "/");
            if (strlen($url) > 0) {
                $url = $url . "/";
            }
        }
        return $url;
    }

    /**
     * Get secret
     *
     * @return string
     */
    public function GetSecret() {

        $secret = $this->GetSystemValue($this->_secret);
        if (empty($secret)) {
            $secret = "secret";
        }

        return $secret;
    }

    /**
     * Get the document service address from the application configuration
     *
     * @return array
     */
    public function GetFormats() {

        return $this->formats;
    }

    /**
     * Additional data about formats
     *
     * @var array
     */
    private $formats = [
        //Word
        "docx" => [ "mime" => "application/vnd.openxmlformats-officedocument.wordprocessingml.document" ],
        "pdf" => [ "mime" => "application/pdf" ],
        "odt" => [ "mime" => "application/vnd.oasis.opendocument.text" ],
        "docm" => [ "mime" => "application/vnd.ms-word.document.macroEnabled.12" ],
        "xps" => [ "mime" => "application/vnd.mx-xpsdocument" ],
        "djvu" => [ "mime" => "image/vnd.djvu" ],
        "txt" => [ "mime" => "text/plain" ],
        //Excel
        "xlsx" => [ "mime" => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" ],
        "xlsm" => [ "mime" => "application/vnd.ms-excel.sheet.macroEnabled.12" ],
        "ods" => [ "mime" => "application/vnd.oasis.opendocument.spreadsheet" ],
        "csv" => [ "mime" => "text/csv" ],
        //PowerPoint
        "pptx" => [ "mime" => "application/vnd.openxmlformats-officedocument.presentationml.presentation" ],
        "pptm" => [ "mime" => "application/vnd.ms-powerpoint.presentation.macroEnabled.12" ],
        "odp" => [ "mime" => "application/vnd.oasis.opendocument.presentation" ]
    ];
}
