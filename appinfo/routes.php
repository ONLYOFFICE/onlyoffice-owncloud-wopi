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

return [
    "routes" => [
        ["name" => "editor#index", "url" => "/{fileId}", "verb" => "GET"],
        ["name" => "wopi#check_file_info", "url" => "/files/{fileId}", "verb" => "GET"],
        ["name" => "wopi#get_contents", "url" => "/files/{fileId}/contents", "verb" => "GET"],
        ["name" => "wopi#put_contents", "url" => "/files/{fileId}/contents", "verb" => "POST"],
    ],
    "ocs" => [
        ["name" => "settings#formats", "url" => "/settings", "verb" => "GET"],
    ]
];
