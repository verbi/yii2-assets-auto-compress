function pjaxAssetsAutoCompressAssetLoad() {
    var pjaxDynamicScriptLoader = new PjaxDynamicScriptLoader();
    pjaxDynamicScriptLoader.loadedKeys = {};
    pjaxDynamicScriptLoader.lastLoadedKeys = {};
    pjaxDynamicScriptLoader.getAssetsUrl = null;

    pjaxDynamicScriptLoader.addLoadedFiles = function (obj) {
        if (obj.jsFiles && Array.isArray(obj.jsFiles)) {
            obj.jsFiles.each(function (i, fileName) {
                if ($.inArray(fileName, pjaxDynamicScriptLoader.loadedScripts) === -1) {
                    pjaxDynamicScriptLoader.loadedScripts.push(fileName);
                }
            });
        }

        if (obj.loadedKeys) {

            $(Object.keys(obj.loadedKeys)).each(function (i, key) {
                var keys = obj.loadedKeys[key];
                if (keys && Array.isArray(keys) && keys.length) {
                    if (!Array.isArray(pjaxDynamicScriptLoader.loadedKeys[key])) {
                        pjaxDynamicScriptLoader.loadedKeys[key] = [];
                    }
                    $(keys).each(function (j, loadKey) {
                        if ($.inArray(key, pjaxDynamicScriptLoader.loadedKeys[key]) === -1) {
                            pjaxDynamicScriptLoader.loadedKeys[key].push(loadKey);
                        }
                    });
                    pjaxDynamicScriptLoader.lastLoadedKeys = $.extend(true, pjaxDynamicScriptLoader.lastLoadedKeys, pjaxDynamicScriptLoader.loadedKeys);
                }
            });

        }

        if (obj.cssFiles && Array.isArray(obj.cssiles)) {
            obj.cssFiles.each(function (i, fileName) {
                if ($.inArray(fileName, pjaxDynamicScriptLoader.loadedCssFile) === -1) {
                    pjaxDynamicScriptLoader.loadedCssFile.push(fileName);
                }
            });
        }

        /*         $(selector).on('pjax:end', function (e) {
         try {
         $(e.target).find('.js-pjax-scripts').each(function (index, element) {
         
         });
         }
         catch (e) {
         console.log(e);
         }
         });*/
    };


    var currentEvent = pjaxDynamicScriptLoader.pjaxEndEvent;



    pjaxDynamicScriptLoader.pjaxPjaxEndKeysEvent = function (e) {
        try {
            var $target = $(e.target);
            $target.find('.js-pjax-scripts').each(function (index, element) {

                var positions = JSON.parse($(element).html());

                var js = pjaxDynamicScriptLoader.getJsScripts(positions);

                if (positions.loadedKeys) {
                    var loadKeys = {};
                    var loadAssetSw = false;
                    $(Object.keys(positions.loadedKeys)).each(function (i, key) {
                        var keys = positions.loadedKeys[key];

                        if (keys && Array.isArray(keys) && keys.length) {
                            if (!Array.isArray(pjaxDynamicScriptLoader.loadedKeys[key])) {
                                pjaxDynamicScriptLoader.loadedKeys[key] = [];
                            }
                            $(keys).each(function (j, loadingKey) {
                                if ($.inArray(loadingKey, pjaxDynamicScriptLoader.loadedKeys[key]) === -1) {
                                    if (!loadKeys[key] || !Array.isArray(loadKeys[key])) {
                                        loadKeys[key] = [];
                                    }
                                    loadKeys[key].push(loadingKey);
                                    pjaxDynamicScriptLoader.loadedKeys[key].push(loadingKey);
                                    loadAssetSw = true;
                                }
                            });
                        }

                    });
                    if (loadAssetSw) {
                        var data = {
                            loadKeys: loadKeys,
                            loaded: pjaxDynamicScriptLoader.lastLoadedKeys
                        };

                        $.ajax({
                            url: pjaxDynamicScriptLoader.getAssetsUrl,
                            data: data,
                            success: function (data) {
                                if (data.keys) {
                                    $(Object.keys(data.keys)).each(function(i, keyType) {
                                        pjaxDynamicScriptLoader.lastLoadedKeys[keyType] = data.keys[keyType];
                                    });
                                }
                                if (data.css && Array.isArray(data.css)) {
                                    pjaxDynamicScriptLoader.loadCssFiles(data.css);
                                }
                                if (data.js && Array.isArray(data.js)) {
                                    $script(data.js, function () {
                                        if (js) {
                                            eval(js);
                                        }
                                    });
                                }
                                else {
                                    eval(js);
                                }
                            }
                        });
                    }
                    else {
                        eval(js);
                    }
                }
                else {
                    alert('test');
                }

            });
            $target.find('form input[type=text]:visible:first').focus();
        }
        catch (e) {
            console.log(e);
        }
    };

    pjaxDynamicScriptLoader.pjaxEndEvent = function (e) {
        pjaxDynamicScriptLoader.pjaxPjaxEndKeysEvent(e);
        /*currentEvent(e);*/
    };
}

pjaxAssetsAutoCompressAssetLoad();