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

namespace OCA\Onlyoffice_Wopi\AppInfo;

use OCP\AppFramework\App;
use OCP\Files\IMimeTypeDetector;
use OCP\Util;

use OCA\Onlyoffice_Wopi\AppConfig;
use OCA\Onlyoffice_Wopi\Controller\WopiController;
use OCA\Onlyoffice_Wopi\Controller\EditorController;
use OCA\Onlyoffice_Wopi\Controller\SettingsController;

class Application extends App {

    /**
     * Application configuration
     *
     * @var AppConfig
     */
    public $appConfig;

    public function __construct(array $urlParams = []) {
        $appName = "onlyoffice_wopi";

        parent::__construct($appName, $urlParams);

        $this->appConfig = new AppConfig($appName);

        // Default script and style if configured
        $eventDispatcher = \OC::$server->getEventDispatcher();
        $eventDispatcher->addListener("OCA\Files::loadAdditionalScripts",
            function () {
                if (!empty($this->appConfig->GetDocumentServerUrl())) {
                    Util::addScript("onlyoffice_wopi", "main");

                    Util::addStyle("onlyoffice_wopi", "main");
                }
            });

        require_once __DIR__ . "/../3rdparty/jwt/BeforeValidException.php";
        require_once __DIR__ . "/../3rdparty/jwt/ExpiredException.php";
        require_once __DIR__ . "/../3rdparty/jwt/SignatureInvalidException.php";
        require_once __DIR__ . "/../3rdparty/jwt/JWT.php";

        $container = $this->getContainer();

        $detector = $container->query(IMimeTypeDetector::class);
        $detector->getAllMappings();
        $detector->registerType("ott", "application/vnd.oasis.opendocument.text-template");
        $detector->registerType("ots", "application/vnd.oasis.opendocument.spreadsheet-template");
        $detector->registerType("otp", "application/vnd.oasis.opendocument.presentation-template");

        $container->registerService("L10N", function ($c) {
            return $c->query("ServerContainer")->getL10N($c->query("AppName"));
        });

        $container->registerService("RootStorage", function ($c) {
            return $c->query("ServerContainer")->getRootFolder();
        });

        $container->registerService("UserSession", function ($c) {
            return $c->query("ServerContainer")->getUserSession();
        });

        $container->registerService("Logger", function ($c) {
            return $c->query("ServerContainer")->getLogger();
        });

        $container->registerService("URLGenerator", function ($c) {
            return $c->query("ServerContainer")->getURLGenerator();
        });


        // Controllers
        $container->registerService("WopiController", function ($c) {
            return new WopiController(
                $c->query("AppName"),
                $c->query("Request"),
                $c->query("RootStorage"),
                $c->query("ServerContainer")->getUserManager(),
                $c->query("Logger"),
                $this->appConfig
            );
        });

        $container->registerService("EditorController", function ($c) {
            return new EditorController(
                $c->query("AppName"),
                $c->query("Request"),
                $c->query("RootStorage"),
                $c->query("UserSession"),
                $c->query("URLGenerator"),
                $c->query("L10N"),
                $c->query("Logger"),
                $this->appConfig
            );
        });

        $container->registerService("SettingsController", function ($c) {
            return new SettingsController(
                $c->query("AppName"),
                $c->query("Request"),
                $this->appConfig
            );
        });
    }
}
