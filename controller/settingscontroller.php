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

namespace OCA\Onlyoffice_Wopi\Controller;

use OCP\AppFramework\OCSController;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

use OCA\Onlyoffice_Wopi\AppConfig;
use OCA\Onlyoffice_Wopi\Utils;

/**
 * Controller with the main functions
 */
class SettingsController extends OCSController {

    /**
     * Application configuration
     *
     * @var AppConfig
     */
    private $config;

    /**
     * Utils function
     *
     * @var Utils
     */
    private $utils;

    /**
     * @param string $AppName - application name
     * @param IRequest $request - request object
     * @param AppConfig $config - application configuration
     */
    public function __construct($AppName,
                                    IRequest $request,
                                    AppConfig $config
                                    ) {
        parent::__construct($AppName, $request);
        $this->config = $config;

        $this->utils = new Utils($config);
    }

    /**
     * Get supported formats
     *
     * @return array
     *
     * @NoAdminRequired
     * @CORS
     */
    public function formats() {
        $formats = $this->config->GetFormats();
        $discovery = $this->utils->GetActionsByExt();

        foreach ($discovery as $ext => $actions) {
            if (array_key_exists($ext, $formats)) {
                $discovery[$ext] += $formats[$ext];
            }
        }

        return new JSONResponse([
            "formats" => $discovery
        ]);
    }
}