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
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http;
use OCP\Files\IRootFolder;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\Lock\LockedException;

use OCA\Onlyoffice_Wopi\AppConfig;
use OCA\Onlyoffice_Wopi\TokenManager;

/**
 * Controller with the main functions
 */
class WopiController extends OCSController {   

    /**
    * User manager
    *
    * @var IUserManager
    */
   private $userManager;

    /**
     * Root folder
     *
     * @var IRootFolder
     */
    private $root;

    /**
     * Logger
     *
     * @var ILogger
     */
    private $logger;

    /**
     * @param string $AppName - application name
     * @param IRequest $request - request object
     * @param IRootFolder $root - root folder
     * @param IUserManager $userManager - user manager
     * @param ILogger $logger - logger
     * @param AppConfig $config - application configuration
     */
    public function __construct($AppName,
                                    IRequest $request,
                                    IRootFolder $root,
                                    IUserManager $userManager,
                                    ILogger $logger,
                                    AppConfig $config
                                    ) {
        parent::__construct($AppName, $request);

        $this->root = $root;
        $this->userManager = $userManager;
        $this->logger = $logger;

        $this->tokenManager = new TokenManager($AppName, $config);
    }

    /**
     * Get file info for wopi
     *
     * @param int $fileId - file identifier
     * @param string $access_token - access token
     *
     * @return JSONResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @CORS
     * @PublicPage
     */
    public function checkFileInfo($fileId, $access_token) {
        $payload = $this->tokenManager->verify($access_token);
        if (empty($payload)) {
            return new JSONResponse([], Http::STATUS_UNAUTHORIZED);
        }

        if (!isset($payload->fileId)
            || isset($payload->fileId) && $payload->fileId !== $fileId) {
            $this->logger->error("checkFileInfo: invalid fileId", ["app" => $this->appName]);
            return new JSONResponse([], Http::STATUS_FORBIDDEN);
        }

        $userId = isset($payload->userId) ? $payload->userId : null;
        $canWrite = isset($payload->canWrite) ? $payload->canWrite : false;

        list ($file, $error) = $this->getFile($userId, $fileId);
        if (isset($error)) {
            $this->logger->error("checkFileInfo: getFile $fileId error", ["app" => $this->appName]);
            return $error;
        }

        $user = $this->userManager->get($userId);
        if (empty($user)) {
            $this->logger->error("checkFileInfo: user $userId not found", ["app" => $this->appName]);
            return new JSONResponse([], Http::STATUS_FORBIDDEN);
        }

        $fileInfo = [
            "BaseFileName" => $file->getName(),
            "OwnerId" => $file->getFileInfo()->getOwner()->getUID(),
            "Size" => $file->getSize(),
            "UserId" => $userId,
            "Version" => $file->getFileInfo()->getMtime(),
            "UserFriendlyName" => $user->getDisplayName(),
            "UserCanWrite" => $canWrite
        ];

        return new JSONResponse($fileInfo);
    }

    /**
     * Get file content
     *
     * @param int $fileId - file identifier
     * @param string $access_token - access token
     *
     * @return DataDownloadResponse|JSONResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @CORS
     * @PublicPage
     */
    public function getContents($fileId, $access_token) {
        $payload = $this->tokenManager->verify($access_token);
        if (empty($payload)) {
            return new JSONResponse([], Http::STATUS_UNAUTHORIZED);
        }

        if (!isset($payload->fileId)
            || isset($payload->fileId) && $payload->fileId !== $fileId) {
            $this->logger->error("getContents: invalid fileId", ["app" => $this->appName]);
            return new JSONResponse([], Http::STATUS_FORBIDDEN);
        }

        \OC_Util::tearDownFS();

        $userId = isset($payload->userId) ? $payload->userId : null;
        $user = $this->userManager->get($userId);
        if (!empty($user)) {
            \OC_User::setUserId($userId);
            \OC_Util::setupFS($userId);
        }

        list ($file, $error) = $this->getFile($userId, $fileId);
        if (isset($error)) {
            $this->logger->error("getContents: getFile $fileId error", ["app" => $this->appName]);
            return $error;
        }

        return new DataDownloadResponse($file->getContent(), $file->getName(), $file->getMimeType());
    }

    /**
     * Put file content
     *
     * @param int $fileId - file identifier
     * @param string $access_token - access token
     *
     * @return JSONResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @CORS
     * @PublicPage
     */
    public function putContents($fileId, $access_token) {
        $payload = $this->tokenManager->verify($access_token);
        if (empty($payload)) {
            return new JSONResponse([], Http::STATUS_UNAUTHORIZED);
        }

        if (!isset($payload->fileId)
            || isset($payload->fileId) && $payload->fileId !== $fileId) {
            $this->logger->error("putContents: invalid fileId", ["app" => $this->appName]);
            return new JSONResponse([], Http::STATUS_FORBIDDEN);
        }

        try {
            \OC_Util::tearDownFS();

            $userId = isset($payload->userId) ? $payload->userId : null;
            $user = $this->userManager->get($userId);
            if (!empty($user)) {
                \OC_User::setUserId($userId);
                \OC_Util::setupFS($userId);
            }

            list ($file, $error) = $this->getFile($userId, $fileId);
            if (isset($error)) {
                $this->logger->error("putContents: getFile $fileId error", ["app" => $this->appName]);
                return new $error;
            }

            $newData = fopen("php://input", "rb");

            $this->retryOperation(function () use ($file, $newData) {
                return $file->putContent($newData);
            });
        } catch (\Exception $e) {
            $this->logger->logException($e, ["message" => "putContents: $fileId error", "app" => $this->appName]);
            return new JSONResponse([], Http::STATUS_FORBIDDEN);
        }

        return new JSONResponse([], Http::STATUS_OK);
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
            $this->logger->error("FileId is empty", ["app" => $this->appName]);
            return [null, new JSONResponse([], Http::STATUS_FORBIDDEN)];
        }

        try {
            $folder = $this->root->getUserFolder($userId);
            $files = $folder->getById($fileId);
        } catch (\Exception $e) {
            $this->logger->logException($e, ["message" => "getFile: $fileId invalid request", "app" => $this->appName]);
            return [null, new JSONResponse([], Http::STATUS_FORBIDDEN)];
        }

        if (empty($files)) {
            $this->logger->error("Files not found: $fileId", ["app" => $this->appName]);
            return [null, new JSONResponse([], Http::STATUS_NOT_FOUND)];
        }

        $file = $files[0];

        if (!$file->isReadable()) {
            $this->logger->info("Don't have enough permissions to view the file: $fileId", ["app" => $this->appName]);
            return [null, new JSONResponse([], Http::STATUS_FORBIDDEN)];
        }

        return [$file, null];
    }

    /**
     * Retry operation if a LockedException occurred
     * Other exceptions will still be thrown
     *
     * @param callable $operation
     *
     * @throws LockedException
     */
    private function retryOperation(callable $operation) {
        $i = 0;
        while (true) {
            try {
                return $operation();
            } catch (LockedException $e) {
                if (++$i === 4) {
                    throw $e;
                }
            }
            usleep(500000);
        }
    }
}