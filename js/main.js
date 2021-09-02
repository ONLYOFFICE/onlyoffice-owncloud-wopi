(function (OCA) {

    OCA.WopiOnlyoffice = _.extend({
        AppName: "wopi_onlyoffice",
    }, OCA.WopiOnlyoffice);

    OCA.WopiOnlyoffice.setting = {};

    OCA.WopiOnlyoffice.GetSettings = function (callbackSettings) {
        if (OCA.WopiOnlyoffice.setting.formats) {

            callbackSettings();

        } else {

            $.get(OC.linkToOCS("apps/" + OCA.WopiOnlyoffice.AppName, 2) + "settings",
                function onSuccess(settings) {
                    OCA.WopiOnlyoffice.setting = settings;

                    callbackSettings();
                }
            );

        }
    };

    OCA.WopiOnlyoffice.registerAction = function () {
        var register = function () {

            var formats = OCA.WopiOnlyoffice.setting.formats;

            $.each(formats, function (ext, config) {
                if (!config.mime) {
                    return true;
                }

                OCA.Files.fileActions.registerAction({
                    name: "onlyofficeOpen",
                    displayName: t(OCA.WopiOnlyoffice.AppName, "Open in ONLYOFFICE"),
                    mime: config.mime,
                    permissions: OC.PERMISSION_READ,
                    iconClass: "icon-onlyoffice-open",
                    actionHandler: OCA.WopiOnlyoffice.FileClick
                });
            });
        }

        OCA.WopiOnlyoffice.GetSettings(register);
    }

    OCA.WopiOnlyoffice.FileClick = function (fileName, context) {
        var fileInfoModel = context.fileInfoModel || context.fileList.getModelForFile(fileName);

        var url = OC.generateUrl("/apps/" + OCA.WopiOnlyoffice.AppName + "/{fileId}",
            {
                fileId: fileInfoModel.id
            });

        window.open(url, "_blank");
    }

    var initPage = function () {
        OCA.WopiOnlyoffice.registerAction();
    }

    $(document).ready(initPage);

})(OCA);