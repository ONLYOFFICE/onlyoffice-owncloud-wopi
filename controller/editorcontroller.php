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

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;

use OCA\Onlyoffice_Wopi\AppConfig;
use OCA\Onlyoffice_Wopi\TokenManager;
use OCA\Onlyoffice_Wopi\Utils;

/**
 * Controller with the main functions
 */
class EditorController extends Controller {

    /**
     * Current user session
     *
     * @var IUserSession
     */
    private $userSession;

    /**
     * Root folder
     *
     * @var IRootFolder
     */
    private $root;

    /**
     * Url generator service
     *
     * @var IURLGenerator
     */
    private $urlGenerator;

    /**
     * l10n service
     *
     * @var IL10N
     */
    private $trans;

    /**
     * Logger
     *
     * @var ILogger
     */
    private $logger;

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
     * Application configuration
     *
     * @var TokenManager
     */
    private $tokenManager;

    /**
     * @param string $AppName - application name
     * @param IRequest $request - request object
     * @param IRootFolder $root - root folder
     * @param IUserSession $userSession - current user session
     * @param IURLGenerator $urlGenerator - url generator service
     * @param IL10N $trans - l10n service
     * @param ILogger $logger - logger
     * @param AppConfig $config - application configuration
     */
    public function __construct($AppName,
                                    IRequest $request,
                                    IRootFolder $root,
                                    IUserSession $userSession,
                                    IURLGenerator $urlGenerator,
                                    IL10N $trans,
                                    ILogger $logger,
                                    AppConfig $config
                                    ) {
        parent::__construct($AppName, $request);

        $this->userSession = $userSession;
        $this->root = $root;
        $this->urlGenerator = $urlGenerator;
        $this->trans = $trans;
        $this->logger = $logger;
        $this->config = $config;

        $this->utils = new Utils($config);
        $this->tokenManager = new TokenManager($AppName, $config);
    }

    /**
     * Print editor section
     *
     * @param integer $fileId - file identifier
     *
     * @return TemplateResponse|RedirectResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index($fileId) {
        $this->logger->debug("Open: $fileId", ["app" => $this->appName]);
        $params = [];

        $documentServerUrl = $this->config->GetDocumentServerUrl();

        if (empty($documentServerUrl)) {
            $this->logger->error("documentServerUrl is empty", ["app" => $this->appName]);
            return $this->renderError($this->trans->t("ONLYOFFICE app is not configured. Please contact admin"));
        }

        $user = $this->userSession->getUser();
        $userId = null;
        if (!empty($user)) {
            $userId = $user->getUID();
        }

        list ($file, $error) = $this->getFile($userId, $fileId);
        if (isset($error)) {
            $this->logger->error("Open: $fileId error", ["app" => $this->appName]);
            return $this->renderError($error);
        }

        $fileName = $file->getName();
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $fileActions = $this->utils->GetActionsByExt($ext);

        $canEdit = isset($fileActions["edit"]) && $fileActions["edit"];
        $editable = $file->isUpdateable();

        $action = $fileActions["view"];
        if ($canEdit && $editable) {
            $action = $fileActions["edit"];
        }

        $actionUrl = $this->getActionUrl($action, $fileId);

        list ($token, $expired) = $this->tokenManager->generate($fileId, $editable, $userId);

        $params = [
            "actionUrl" => $actionUrl,
            "token" => $token,
            "tokenttl" => $expired
        ];

        $response = new TemplateResponse($this->appName, "editor", $params);

        $csp = new ContentSecurityPolicy();
        $csp->allowInlineScript(true);

        if (preg_match("/^https?:\/\//i", $documentServerUrl)) {
            $csp->addAllowedScriptDomain($documentServerUrl);
            $csp->addAllowedFrameDomain($documentServerUrl);
        } else {
            $csp->addAllowedFrameDomain("'self'");
        }
        $response->setContentSecurityPolicy($csp);

        return $response;
    }

    /**
     * Getting action url by action and fileId
     *
     * @param string $userId - user identifier
     * @param integer $fileId - file identifier
     *
     * @return string
     */
    private function getActionUrl($action, $fileId) {
        $wopisrc = $this->urlGenerator->linkToRouteAbsolute($this->appName . ".wopi.check_file_info", ["fileId" => $fileId]);
        $langCode = str_replace("_", "-", \OC::$server->getL10NFactory("")->get("")->getLanguageCode());

        $actionUrl = preg_replace("/<.*&>/", "", $action["urlsrc"]) . "WOPISrc=" . $wopisrc
                        . "&lang=" . $langCode;

        return $actionUrl;
    }

    /**
     * Getting file by identifier
     *
     * @param string $userId - user identifier
     * @param integer $fileId - file identifier
     *
     * @return array
     */
    private function getFile($userId, $fileId) {
        if (empty($fileId)) {
            return [null, $this->trans->t("FileId is empty")];
        }

        try {
            $folder = $this->root->getUserFolder($userId);
            $files = $folder->getById($fileId);
        } catch (\Exception $e) {
            $this->logger->logException($e, ["message" => "getFile: $fileId", "app" => $this->appName]);
            return [null, $this->trans->t("Invalid request")];
        }

        if (empty($files)) {
            $this->logger->info("Files not found: $fileId", ["app" => $this->appName]);
            return [null, $this->trans->t("File not found")];
        }

        $file = $files[0];

        if (!$file->isReadable()) {
            return [null, $this->trans->t("You do not have enough permissions to view the file")];
        }

        return [$file, null];
    }

    /**
     * Print error page
     *
     * @param string $error - error message
     * @param string $hint - error hint
     *
     * @return TemplateResponse
     */
    private function renderError($error, $hint = "") {
        return new TemplateResponse("", "error", [
                "errors" => [
                    [
                        "error" => $error,
                        "hint" => $hint
                    ]
                ]
            ], "error");
    }
}