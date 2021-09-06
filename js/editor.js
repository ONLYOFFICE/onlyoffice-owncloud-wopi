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

 (function ($, OCA) {

    OCA.WopiOnlyoffice = _.extend({
        AppName: "onlyoffice_wopi"
    }, OCA.WopiOnlyoffice);
     
    OCA.WopiOnlyoffice.InitEditor = function () {
        var frameholder = document.getElementById("frameholder");
        var office_frame = document.createElement("iframe");
        office_frame.name = "office_frame";
        office_frame.id = "office_frame";

        office_frame.title = "Office Frame";

        office_frame.setAttribute("allowfullscreen", "true");

        office_frame.setAttribute("sandbox", "allow-scripts allow-same-origin allow-forms allow-popups allow-top-navigation allow-popups-to-escape-sandbox");
        frameholder.appendChild(office_frame);

        document.getElementById("office_form").submit();
    }

    $(document).ready(OCA.WopiOnlyoffice.InitEditor);

 })(jQuery, OCA);