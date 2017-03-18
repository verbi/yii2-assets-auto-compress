function pjaxAssetsAutoCompressAssetLoad() {
    var pjaxDynamicScriptLoader = new PjaxDynamicScriptLoader();
    pjaxDynamicScriptLoader.loadedKeys = {};
    pjaxDynamicScriptLoader.lastLoadedKeys = {};

    pjaxDynamicScriptLoader.addLoadedFiles = function (obj) {
        if (obj.jsFiles && Array.isArray(obj.jsFiles)) {
            obj.jsFiles.each(function (i, fileName) {
                if ($.inArray(fileName, pjaxDynamicScriptLoader.loadedScripts)===-1) {
                    pjaxDynamicScriptLoader.loadedScripts.push(fileName);
                }
            });
        }
        
        if (obj.loadedKeys) {
            
            $(Object.keys(obj.loadedKeys)).each(function(i, key) {
                var keys = obj.loadedKeys[key];
                if (keys && Array.isArray(keys) && keys.length) {
                    if(!Array.isArray(pjaxDynamicScriptLoader.loadedKeys[key])) {
                        pjaxDynamicScriptLoader.loadedKeys[key] = [];
                    }
                    $(keys).each(function (j, loadKey) {
                        if ($.inArray(key, pjaxDynamicScriptLoader.loadedKeys[key])===-1) {
                            pjaxDynamicScriptLoader.loadedKeys[key].push(loadKey);
                        }
                    });
                    pjaxDynamicScriptLoader.lastLoadedKeys = $.extend(true, pjaxDynamicScriptLoader.lastLoadedKeys, pjaxDynamicScriptLoader.loadedKeys);
                }
            });
            
        }

        if (obj.cssFiles && Array.isArray(obj.cssiles)) {
            obj.cssFiles.each(function (i, fileName) {
                if ($.inArray(fileName, pjaxDynamicScriptLoader.loadedCssFile)===-1) {
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
            
            $(e.target).find('.js-pjax-scripts').each(function (index, element) {

                var positions = JSON.parse($(element).html());
                
                
                if(positions.loadedKeys){
                    var loadKeys = {};
                    var loadAssetSw = false;
                    $(Object.keys(positions.loadedKeys)).each(function(i, key) {
                        var keys = positions.loadedKeys[key];
                        
                        if (keys && Array.isArray(keys) && keys.length) {
                            if(!Array.isArray(pjaxDynamicScriptLoader.loadedKeys[key])) {
                                pjaxDynamicScriptLoader.loadedKeys[key] = [];
                            }
                            $(keys).each(function (j, loadingKey) {
                                if ($.inArray(loadingKey, pjaxDynamicScriptLoader.loadedKeys[key])===-1) {
                                    if(!loadKeys[key] || !Array.isArray(loadKeys[key])) {
                                        loadKeys[key] = [];
                                    }
                                    loadKeys[key].push(loadingKey);
                                    pjaxDynamicScriptLoader.loadedKeys[key].push(loadingKey);
                                    loadAssetSw = true;
                                }
                            });
                        }
                        
                    });
                    if(loadAssetSw) {
                        var data = {
                            loadKeys: loadKeys,
                            loaded: pjaxDynamicScriptLoader.lastLoadedKeys
                        };
                        $.ajax({
                            url: '',
                            data: data,
                            complete: function(data) {
                                
                            }
                        });
                        alert(JSON.stringify(data));
                            
                    }
                }
                
            });
        }
        catch (e) {
            console.log(e);
        }
    };

    pjaxDynamicScriptLoader.pjaxEndEvent = function (e) {
        currentEvent(e);
        pjaxDynamicScriptLoader.pjaxPjaxEndKeysEvent(e);
    };

    pjaxDynamicScriptLoader.loadScriptByKey = function (jsKey) {
        alert(jsKey);


        /*    $script(jsFiles, function () {
         if (js) {
         eval(js);
         }
         });*/
    };
}

pjaxAssetsAutoCompressAssetLoad();