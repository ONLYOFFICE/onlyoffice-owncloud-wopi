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

    style("onlyoffice_wopi", "editor");
    script("onlyoffice_wopi", "editor");

?>


<form id="office_form" name="office_form" target="office_frame" action="<?php p($_["actionUrl"]) ?>" method="post">
    <input name="access_token" value="<?php p($_["token"]) ?>" type="hidden" />
    <input name="access_token_ttl" value="<?php p($_["tokenttl"]) ?>" type="hidden" />
</form>

<span id="frameholder"></span>
