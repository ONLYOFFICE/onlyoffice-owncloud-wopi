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

use OCP\ILogger;

use OCA\Wopi_Onlyoffice\AppConfig;

/**
 * Token manager
 *
 * @package OCA\Onlyoffice
 */
class TokenManager {

    /**
     * Application name
     *
     * @var string
     */
    private $appName;

    /**
     * Logger
     *
     * @var ILogger
     */
    private $logger;

    /**
     * Logger
     *
     * @var AppConfig
     */
    private $config;

    /**
     * @param string $AppName - application name
     * @param AppConfig $appConfig - application configuration
     */
    public function __construct($AppName, $appConfig) {

        $this->appName = $AppName;
        $this->config = $appConfig;

        $this->logger = \OC::$server->getLogger();
    }

    /**
     * Get access token
     * 
     * @param int $fileId - file identifier
     * @param bool $canWrite - user can edit
     * @param string $userId - user identifier
     *
     * @return array
     */
    public function generate($fileId, $canWrite, $userId) {
        $secret = $this->config->GetSecret();
        $expired = (time() + 60 * 60 * 10) * 1000;
        $params = [
            "fileId" => $fileId,
            "canWrite" => $canWrite,
            "userId" => $userId,
            "expired" => $expired
        ];

        $token = \Firebase\JWT\JWT::encode($params, $secret);

        return [$token, $expired];
    }

    /**
     * Verify token
     * 
     * @param string $token - access token
     *
     * @return array
     */
    public function verify($token) {
        $secret = $this->config->GetSecret();
        try {
            $payload = \Firebase\JWT\JWT::decode($token, $secret, array("HS256"));
        } catch (\UnexpectedValueException $e) {
            $this->logger->logException($e, ["message" => "Invalid jwt", "app" => $this->appName]);
            return [];
        }

        return $payload;
    }
}