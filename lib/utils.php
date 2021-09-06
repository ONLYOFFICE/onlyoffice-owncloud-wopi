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

namespace OCA\Wopi_Onlyoffice;

use OCA\Wopi_Onlyoffice\AppConfig;

/**
 * Utils function
 *
 * @package OCA\Onlyoffice
 */
class Utils {

    /**
     * Logger
     *
     * @var AppConfig
     */
    private $config;

    /**
     * @param AppConfig $appConfig - application configuration
     */
    public function __construct($appConfig) {

        $this->config = $appConfig;
    }

    /**
     * Get discovery data
     *
     * @return array
     */
    public function GetDiscoveryInfo() {
        $result = [];
        $discoveryUrl = $this->config->GetDocumentServerUrl() . "hosting/discovery";

        $client = \OC::$server->getHTTPClientService()->newClient();
        $response = $client->get($discoveryUrl);
        $discoveryXmlString = $response->getBody();

		$discovery = \simplexml_load_string($discoveryXmlString);
        $apps = $discovery->xpath("/wopi-discovery/net-zone/app");
        foreach ($apps as $app) {
            foreach ($app as $action) {
                array_push($result, [
                    "app" => (string)$app["name"],
                    "favIconUrl" => $app["favIconUrl"] ? (string)$app["favIconUrl"] : "",
                    "name" => (string)$action["name"],
                    "ext" => $action["ext"] ? (string)$action["ext"] : "",
                    "urlsrc" => (string)$action["urlsrc"],
                    "default" => (bool)$action["default"],
                    "requires" => $action["requires"] ? (string)$action["requires"] : ""
                ]);
            }
        }

        return $result;
    }

    /**
     * Get file actions by file extension
     * 
     * @param string $ext - file extension
     *
     * @return array
     */
    public function GetActionsByExt($ext = null) {
        $result = [];
        $discovery = $this->GetDiscoveryInfo();

        foreach ($discovery as $action) {
            if (empty($action["ext"])) {
                continue;
            }
            if (!empty($ext) && $action["ext"] !== $ext) {
                continue;
            }

            if (!array_key_exists($action["ext"], $result)) {
                $result[$action["ext"]] = [];
            }

            $result[$action["ext"]] += [$action["name"] => $action];
        }

        if (count($result) === 1) {
            $result = $result[$ext];
        }

        return $result;
    }
}