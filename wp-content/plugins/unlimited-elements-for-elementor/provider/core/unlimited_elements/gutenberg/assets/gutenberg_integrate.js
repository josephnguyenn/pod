var uelm_WidgetSettingsCache = [];
var uelm_WidgetSettingsCacheFlags = [];

(function (wp) {
    var g_debug = true;
    function trace(str){ console.log(str); }
    function debug(){ if(!g_debug) return; console.log.apply(console, arguments); }

    // ping editor to activate Save btn
    function pingSaveButton(){
        try{
            const d = wp?.data?.dispatch('core/editor');
            if (d?.editPost) { d.editPost({}); return; }
        }catch(e){}
        try{
            const d = wp?.data?.dispatch('core/edit-site');
            if (d?.setIsDirty) { d.setIsDirty(true); return; }
        }catch(e){}
        try{
            const d = wp?.data?.dispatch('core/edit-widgets');
            if (d?.setHasEdits) { d.setHasEdits(true); return; }
        }catch(e){}
    }

    var wbe = wp.blockEditor;
    var wc  = wp.components;
    var wd  = wp.data;
    var we  = wp.element;
    var el  = we.createElement;

    jQuery(document).on("click", ".ue-gutenberg-widget-wrapper", function () {
        jQuery(this).closest("[tabindex]").focus();
    });
    // prevent navigation inside preview
    jQuery(document).on("click", ".ue-gutenberg-widget-wrapper a", function (event) {
        event.preventDefault();
    });

    jQuery(function(){ jQuery("body").append("<div id='div_debug' class='unite-div-debug'></div>"); });

    var edit = function (props) {

        var previewUrl = props.attributes._preview;
        if (previewUrl)
            return el("img", { src: previewUrl, style: { width: "100%", height: "auto" } });

        var blockProps             = wbe.useBlockProps();
        var widgetContentState     = we.useState(null);
        var settingsVisibleState   = we.useState(false);
        var settingsContentState   = we.useState(null);
        var isLoadingSettingsState = we.useState(false);

        var widgetRef              = we.useRef(null);
        var widgetLoaderRef        = we.useRef(null);
        var widgetRequestRef       = we.useRef(null);

        var ucSettingsRef          = we.useRef(new UniteSettingsUC());
        var ucHelperRef            = we.useRef(new UniteCreatorHelper());

        var settingsInitedRef            = we.useRef(false);
        var initedSettingsElementRef     = we.useRef(null);
        var lastSentDataRef              = we.useRef(null);
        var settingsObserverRef          = we.useRef(null);
        var settingsWatchdogTimerRef     = we.useRef(null);

        var ucSettings = ucSettingsRef.current;
        var ucHelper   = ucHelperRef.current;

        // orchestrator refs
        const didFirstPreviewRef    = we.useRef(false);  
        const firstPreviewReadyRef  = we.useRef(false);   
        const lastPreviewPayloadRef = we.useRef(null);   
        const firstPreviewTimerRef  = we.useRef(null);  

        function isSettingsReady() {
            return !!(settingsInitedRef.current &&
                      ucSettings &&
                      typeof ucSettings.isInited === 'function' &&
                      ucSettings.isInited());
        }

        // root block's element 
        function getWidgetRootEl(){
            const $root = jQuery(widgetRef.current);
            // пробуем найти «внутренний» корень, если он помечен
            const $inner = $root.find('[data-uc-root]').first();
            return ($inner.length ? $inner : $root);
        }

        function ensureStyleTag($root){
            if ($root.find("[name=uc_selectors_css]").length === 0) {
                $root.prepend('<style name="uc_selectors_css"></style>');
            }
            return $root.find("[name=uc_selectors_css]");
        }

        function applyStylePreviewOnce(maxTries = 30, delay = 50){
            let tries = 0;
            (function tick(){
                const $root = jQuery(widgetRef.current);
                const ready = isSettingsReady();

                if ($root.length && ready) {
                    ensureStyleTag($root);
                    const css = (ucSettings.getSelectorsCss && ucSettings.getSelectorsCss()) || '';
                    if (css && css !== lastSelectorsCssRef.current) {
                        $root.find("[name=uc_selectors_css]").text(css);
                        lastSelectorsCssRef.current = css;
                    }

                    const selIncludes = ucSettings.getSelectorsIncludes && ucSettings.getSelectorsIncludes();
                    const selHash = stableStringify(selIncludes || {});
                    if (selIncludes && selHash !== lastIncludesHashRef.current) {
                        ucHelper.putIncludes(getPreviewWindowElement(), selIncludes);
                        lastIncludesHashRef.current = selHash;
                    }
                    // одновременно прокинем box-model, если есть
                    applyLiveBoxModelInline();
                    return;
                }
                if (++tries < maxTries) setTimeout(tick, delay);
            })();
        }

        // --- delay for color/range/dimensions ---
        const DELAY_MS = 800;
        const saveDelayTimerRef = we.useRef(null);
        const pendingSaveRef       = we.useRef(false);
        const lastChangeTypeRef    = we.useRef(null);
        const suppressNextReloadRef= we.useRef(false);
        const pendingSuppressByDomRef = we.useRef(false);

        const styleOnlyDirtyRef    = we.useRef(false);

        const lastSelectorsCssRef  = we.useRef('');
        const lastIncludesHashRef  = we.useRef('');

        function applyLiveBoxModelInline(){
            try{
                if (!isSettingsReady()) return;
                const vals = ucSettings.getSettingsValues ? ucSettings.getSettingsValues() : {};
                const pad  = vals?.advanced_padding || vals?.advanced_padding_row || vals?.padding || null;
                const mar  = vals?.advanced_margin  || vals?.advanced_margin_row  || vals?.margin  || null;

                const $root = getWidgetRootEl();
                if (!$root.length) return;

                const style = $root[0].style;

                function setBox(v, prefix){
                    if (!v || typeof v !== 'object') return;
                    const unit = v.unit || 'px';
                    const set = (prop, key) => {
                        if (v[key] === '' || typeof v[key] === 'undefined' || v[key] === null) {
                            style.removeProperty(prop);
                        } else {
                            style.setProperty(prop, String(v[key]) + unit);
                        }
                    };
                    set(prefix+'-top',    'top');
                    set(prefix+'-right',  'right');
                    set(prefix+'-bottom', 'bottom');
                    set(prefix+'-left',   'left');
                }

                setBox(pad, 'padding');
                setBox(mar, 'margin');
            }catch(e){}
        }

        // общая функция обновления css + includes + live box-model
        function updateSelectorsPreview() {
            if (!isSettingsReady()) return;
            const $root = jQuery(widgetRef.current);
            if ($root.length === 0) return;

            ensureStyleTag($root);

            const css = (ucSettings.getSelectorsCss && ucSettings.getSelectorsCss()) || '';
            if (css && css !== lastSelectorsCssRef.current) {
                $root.find('[name=uc_selectors_css]').text(css);
                lastSelectorsCssRef.current = css;
            }

            const includes = ucSettings.getSelectorsIncludes && ucSettings.getSelectorsIncludes();
            const includesHash = stableStringify(includes || {});
            if (includes && includesHash !== lastIncludesHashRef.current) {
                ucHelper.putIncludes(getPreviewWindowElement(), includes);
                lastIncludesHashRef.current = includesHash;
            }

            // и сразу инлайн-бокс-модель
            applyLiveBoxModelInline();
        }

        function flushSaveNow(typeOverride) {
            if (!isSettingsReady()) return;
            if (saveDelayTimerRef.current) {
                clearTimeout(saveDelayTimerRef.current);
                saveDelayTimerRef.current = null;
            }
            try {
                const currentObj = (function(){
                    try { return props.attributes.data ? JSON.parse(props.attributes.data) : {}; }
                    catch(e){ return {}; }
                })();
                const snapshot  = ucSettings.getSettingsValues() || {};
                const mergedObj = { ...currentObj, ...snapshot };
                const mergedStr = JSON.stringify(mergedObj);

                if (lastSentDataRef.current === mergedStr) return;

                try {
                    delete uelm_WidgetSettingsCache[props.attributes._id];
                    uelm_WidgetSettingsCacheFlags[props.attributes._id] = false;
                } catch(e){}

                lastSentDataRef.current = mergedStr;

                const tRaw = (typeOverride ?? lastChangeTypeRef.current ?? '').toString().toLowerCase().trim();

                suppressNextReloadRef.current = (tRaw == 'styles'); // suppress block reloading

                props.setAttributes({ data: mergedStr });
                styleOnlyDirtyRef.current = false;
            } finally {
                pendingSaveRef.current = false;
            }
        }

        var isEditorSidebarOpened = wd.useSelect(function (select) {
            return select("core/edit-post").isEditorSidebarOpened();
        });
        var activeGeneralSidebarName = wd.useSelect(function (select) {
            return select("core/edit-post").getActiveGeneralSidebarName();
        });
        var previewDeviceType = wd.useSelect((select) => {
            const editor = select(wp.editPost?.store || "core/edit-post");
            return editor.getDeviceType?.() || editor.__experimentalGetPreviewDeviceType?.() || "Desktop";
        }, []);

        var widgetId      = "ue-gutenberg-widget-"   + props.clientId;
        var settingsId    = "ue-gutenberg-settings-" + props.clientId;
        var settingsErrorId = settingsId + "-error";
        var settingsVisible = settingsVisibleState[0];
        var setSettingsVisible = settingsVisibleState[1];

        var settingsContent = settingsContentState[0];
        var setSettingsContent = settingsContentState[1];

        var widgetContent = widgetContentState[0];
        var setWidgetContent = widgetContentState[1];

        var isLoadingSettings = isLoadingSettingsState[0];
        var setIsLoadingSettings = isLoadingSettingsState[1];

        function stableStringify(obj){
            try { return JSON.stringify(obj, Object.keys(obj).sort()); }
            catch(e){ return ""; }
        }
        function deepEqual(a, b){
            if (a === b) return true;
            return stableStringify(a) === stableStringify(b);
        }

        var getSettings = function () {
            try { return props.attributes.data ? JSON.parse(props.attributes.data) : null; }
            catch (e) { return null; }
        };
        var getSettingsElement = function () {
            return jQuery("#" + settingsId);
        };
        var getPreviewWindowElement = function () {
            return window.frames["editor-canvas"] || window;
        };

        var saveSettingsIfChanged = function (explicitPatch, suppressReload) {
            const currentObj = (function(){
                try { return props.attributes.data ? JSON.parse(props.attributes.data) : {}; }
                catch(e){ return {}; }
            })();

            const patch     = explicitPatch || ucSettings.getSettingsValues();
            const mergedObj = { ...currentObj, ...patch };

            if (deepEqual(currentObj, mergedObj)) {
                updateSelectorsPreview();
                return;
            }

            const mergedStr = JSON.stringify(mergedObj);

            const isStyleOnly = !!suppressReload || !!pendingSuppressByDomRef.current;
            if (isStyleOnly) {
                pendingSuppressByDomRef.current = false;
                styleOnlyDirtyRef.current = true;

                updateSelectorsPreview();
                pingSaveButton();
                return;
            }

            if (lastSentDataRef.current === mergedStr) {
                updateSelectorsPreview();
                return;
            }

            try {
                delete uelm_WidgetSettingsCache[props.attributes._id];
                uelm_WidgetSettingsCacheFlags[props.attributes._id] = false;
            } catch(e){}

            lastSentDataRef.current = mergedStr;

            props.setAttributes({
                _rootId: ucHelper.getRandomString(5),
                data: mergedStr,
            });
        };

        var initSettings = function () {
            var $settingsElement = getSettingsElement();
            if (!$settingsElement || $settingsElement.length === 0) return;

            var elem = $settingsElement[0];

            if (ucSettings.isInited() && initedSettingsElementRef.current === elem) {
                return;
            }

            if (ucSettings.isInited() && initedSettingsElementRef.current !== elem) {
                ucSettings.destroy();
            }

            ucSettings.init($settingsElement);
            initedSettingsElementRef.current = elem;

            ucSettings.setSelectorWrapperID(widgetId);
            ucSettings.setResponsiveType(previewDeviceType.toLowerCase());

            function handleSettingsEvent(evt, payload) {
                const type = (payload?.type ?? '').toString().toLowerCase().trim();
                const STYLE_ONLY_TYPES = ['color','range','dimensions'];
                const $c = jQuery('#' + settingsId +' [data-name="' + payload.name + '"] [data-selectors]');

                if (STYLE_ONLY_TYPES.includes(type) && $c.length > 0) {
                    // style only fields
                    lastChangeTypeRef.current = 'styles';

                    styleOnlyDirtyRef.current = true;

                    if (saveDelayTimerRef.current) {
                        clearTimeout(saveDelayTimerRef.current);
                        saveDelayTimerRef.current = null;
                    }
                    saveDelayTimerRef.current = setTimeout(() => {
                        flushSaveNow(type); 
                    }, 180);
                } else {
                    // content fields (reload block preview)
                    lastChangeTypeRef.current = 'content';

                    if (saveDelayTimerRef.current) {
                        clearTimeout(saveDelayTimerRef.current);
                        saveDelayTimerRef.current = null;
                    }
                    flushSaveNow(type); 
                }

            }

            ucSettings.setEventOnChange(handleSettingsEvent);
            if (typeof ucSettings.onEvent === 'function') {
                ucSettings.onEvent('settings_instant_change', handleSettingsEvent);
            }

            ucSettings.setEventOnSelectorsChange(function () {
                if (!isSettingsReady()) return;
                updateSelectorsPreview();
            });

            ucSettings.setEventOnResponsiveTypeChange(function (event, type) {
                uelm_WidgetSettingsCacheFlags[props.attributes._id] = true;
                uelm_WidgetSettingsCacheFlags[props.attributes._id + '_settings'] = true;

                var deviceType = type.charAt(0).toUpperCase() + type.substring(1);
                const editorStore = wp.editPost?.store || "core/edit-post";
                const dispatcher = wp.data.dispatch(editorStore);

                if (typeof dispatcher.setDeviceType === "function") {
                    dispatcher.setDeviceType(deviceType);
                } else if (typeof wp.data.dispatch("core/edit-post").__experimentalSetPreviewDeviceType === "function") {
                    wp.data.dispatch("core/edit-post").__experimentalSetPreviewDeviceType(deviceType);
                }
            });

            var values = getSettings();
            if (values !== null) {
                ucSettings.setCacheValues(values);
            } 

            settingsInitedRef.current = true;
        };

        function maybeInitSettings(){
            if (!settingsVisible) return;
            if (!settingsContent) return;

            var $settingsElement = getSettingsElement();
            if (!$settingsElement || $settingsElement.length === 0) return;

            var elem = $settingsElement[0];
            if (!ucSettings.isInited() || initedSettingsElementRef.current !== elem) {
                initSettings();
            }
        }

        function attachSettingsObserver(){
            detachSettingsObserver();
            var observer = new MutationObserver(function(){
                maybeInitSettings();
            });
            observer.observe(document.body, { childList: true, subtree: true });
            settingsObserverRef.current = observer;
        }

        function detachSettingsObserver(){
            if (settingsObserverRef.current) {
                settingsObserverRef.current.disconnect();
                settingsObserverRef.current = null;
            }
        }

        function startSettingsWatchdog(){
            stopSettingsWatchdog();
            settingsWatchdogTimerRef.current = setInterval(function(){
                if (!settingsVisible || !settingsContent) return;

                var $settingsElement = getSettingsElement();
                var elem = $settingsElement && $settingsElement[0];

                if (!elem) return;

                if (!ucSettings.isInited() || initedSettingsElementRef.current !== elem) {
                    initSettings();
                }
            }, 2000);
        }
        function stopSettingsWatchdog(){
            if (settingsWatchdogTimerRef.current) {
                clearInterval(settingsWatchdogTimerRef.current);
                settingsWatchdogTimerRef.current = null;
            }
        }

        // AJAX: load settings HTML
        var loadSettingsContent = function () {
            var widgetCacheKey = props.attributes._id + '_settings';

            setIsLoadingSettings(true);

            if ( uelm_WidgetSettingsCache[widgetCacheKey] && uelm_WidgetSettingsCacheFlags[widgetCacheKey] ) {
                uelm_WidgetSettingsCacheFlags[widgetCacheKey] = false;
                setSettingsContent( uelm_WidgetSettingsCache[widgetCacheKey] );
                setIsLoadingSettings(false);
                return;
            }

            g_ucAdmin.setErrorMessageID(settingsErrorId);

            const urlParams = new URLSearchParams(window.location.search);
            const isTestFreeVersion = urlParams.get("testfreeversion") === "true";

            var requestData = {
                id: props.attributes._id,
                config: getSettings(),
                platform: "gutenberg",
                source: "editor"
            };
            if (isTestFreeVersion) requestData.testfreeversion = true;

            debug('Load get_addon_settings_html');
            g_ucAdmin.ajaxRequest("get_addon_settings_html", requestData, function (response) {

                var html = g_ucAdmin.getVal(response, "html");

                uelm_WidgetSettingsCache[widgetCacheKey] = html;
                uelm_WidgetSettingsCacheFlags[widgetCacheKey] = true;

                setSettingsContent(html);
                setIsLoadingSettings(false);
            }).fail(function() {
                 setIsLoadingSettings(false);
            });
        };

        // AJAX: load widget HTML
        var loadWidgetContent = function (overrideSettings) {
            var widgetCacheKey = props.attributes._id;

            if ( uelm_WidgetSettingsCache[widgetCacheKey] && uelm_WidgetSettingsCacheFlags[widgetCacheKey] ) {
                uelm_WidgetSettingsCacheFlags[widgetCacheKey] = false;
                initWidget( uelm_WidgetSettingsCache[widgetCacheKey] );
                debug('loadWidgetContent loaded from cache');
                return;
            }

            if (!widgetContent) {
                // load existing widgets from the page
                for (var index in g_gutenbergParsedBlocks) {
                    var block = g_gutenbergParsedBlocks[index];
                    if (block.name === props.name) {
                        setWidgetContent(block.html);
                        delete g_gutenbergParsedBlocks[index];
                        debug('loadWidgetContent loaded from page content');
                        return;
                    }
                }
            }

            var settings = overrideSettings ?? getSettings();

            if (widgetRequestRef.current !== null)
                widgetRequestRef.current.abort();

            var loaderElement = jQuery(widgetLoaderRef.current);
            loaderElement.show();

            debug('loadWidgetContent load from server, uc_items length: ' + (settings?.uc_items?.length ?? 0));

            widgetRequestRef.current = g_ucAdmin.ajaxRequest("get_addon_output_data", {
                id: props.attributes._id,
                root_id: props.attributes._rootId,
                platform: "gutenberg",
                source: "editor",
                settings: settings || null,
                selectors: true,
            }, function (response) {
                uelm_WidgetSettingsCache[widgetCacheKey] = response;
                uelm_WidgetSettingsCacheFlags[widgetCacheKey] = true;
                initWidget(response);
            }).always(function () {
                loaderElement.hide();
            });
        };

        var initWidget = function (response) {
            var html = g_ucAdmin.getVal(response, "html");
            var includes = g_ucAdmin.getVal(response, "includes");
            var win = getPreviewWindowElement();

            if (win.jQuery && Array.isArray(includes?.scripts)) {
                includes.scripts = includes.scripts.filter(function (src) {
                    return !/jquery(\.min)?\.js/i.test(src);
                });
            }

            ucHelper.putIncludes(win, includes, function () {
                setWidgetContent(html);
            });

            applyStylePreviewOnce();
        };

        we.useEffect(function () {
            debug('[effect 1]');

            jQuery("#unlimited-elements-styles").remove();

            attachSettingsObserver();
            startSettingsWatchdog();

            loadWidgetContent();

            return function () {
                // cleanup on unmount
                if (firstPreviewTimerRef.current) {
                    clearTimeout(firstPreviewTimerRef.current);
                    firstPreviewTimerRef.current = null;
                }

                if (isSettingsReady()) flushSaveNow();

                ucSettings.destroy();
                initedSettingsElementRef.current = null;
                settingsInitedRef.current = false;
                detachSettingsObserver();
            };
        }, []);

        we.useEffect(function () {
            debug('[effect 2]');
            if (didFirstPreviewRef.current) return;

            const attr = (() => {
                try { return props.attributes.data ? JSON.parse(props.attributes.data) : {}; }
                catch(e){ return {}; }
            })();

            if (attr && Object.keys(attr).length > 0) {
                const payloadStr = JSON.stringify(attr);
                lastPreviewPayloadRef.current = payloadStr;

                loadWidgetContent(attr);               
                didFirstPreviewRef.current    = true; 
                firstPreviewReadyRef.current  = true;  
            }
        }, []);

        // mark color/range/dimensions to suppress reload
        we.useEffect(function () {
            debug('[effect 3]');
            function markIfColorOrRange(e){
                const t = e.target;
                if (!t) return;
                const settingsRoot = document.getElementById(settingsId);
                if (!settingsRoot || !settingsRoot.contains(t)) return;

                const type = (t.type || '').toLowerCase();
                const hasColorClass = t.classList && t.classList.contains('unite-color-picker');
                const isRange = type === 'range' || (t.classList && t.classList.contains('unite-range-slider'));
                const isDimensions = !!(t.closest && t.closest('.unite-dimensions'));

                if (hasColorClass) {
                    lastChangeTypeRef.current = 'color';
                    pendingSuppressByDomRef.current = true;
                    pingSaveButton();
                } else if (isRange || isDimensions) {
                    lastChangeTypeRef.current = isDimensions ? 'dimensions' : 'range';
                    pendingSuppressByDomRef.current = true;
                    pingSaveButton();
                }
            }
            document.addEventListener('input',  markIfColorOrRange, true);
            document.addEventListener('change', markIfColorOrRange, true);
            return () => {
                document.removeEventListener('input',  markIfColorOrRange, true);
                document.removeEventListener('change', markIfColorOrRange, true);
            };
        }, [settingsId]);

        // insert widget HTML into DOM
        we.useEffect(function () {
            debug('[effect 4]');
            if (!widgetContent) return;
            jQuery(widgetRef.current).html(widgetContent);
        }, [widgetContent]);

        // sidebar visibility logic
        we.useEffect(function () {
            debug('[effect 5]');
            const isVisible = props.isSelected
                && isEditorSidebarOpened
                && activeGeneralSidebarName === "edit-post/block";

            setSettingsVisible(isVisible);

            if (isVisible && !settingsContent && !isLoadingSettings) {
                loadSettingsContent();
            } else if (!isVisible) {
                if (isSettingsReady()) flushSaveNow();
            }
        }, [props.isSelected, isEditorSidebarOpened, activeGeneralSidebarName]);

        we.useEffect(function () {
            debug('[effect 6]');
            if (ucSettings.isInited())
                ucSettings.setResponsiveType(previewDeviceType.toLowerCase());
        }, [previewDeviceType]);

        we.useEffect(function () {
            debug('[effect 7]');
            maybeInitSettings();
        }, [settingsVisible, settingsContent]);

        we.useEffect(function () {
            debug('[effect 8]');
            maybeInitSettings();
        }, [previewDeviceType, props.attributes.data]);

        we.useEffect(function () {
            debug('[effect 9]');
            if (!settingsContent) return;
            runFirstPreviewOnce();
        }, [settingsContent]);

        we.useEffect(function () {
            debug('[effect 10]');

            if (!firstPreviewReadyRef.current) {
                if (widgetContent) {
                    firstPreviewReadyRef.current = true;
                } else {
                    return;
                }
            }
            if (suppressNextReloadRef.current) {

                suppressNextReloadRef.current = false;

                updateSelectorsPreview();
                return;
            }

            const settings = (() => {
                try { 
                    return props.attributes.data ? JSON.parse(props.attributes.data) : {}; 
                } catch(e){ 
                    return {}; 
                }
            })();
            const payloadStr = JSON.stringify(settings || {});
            if (payloadStr === lastPreviewPayloadRef.current) {
                debug('[effect 10] [5]');
                updateSelectorsPreview();
                return;
            }
            lastPreviewPayloadRef.current = payloadStr;

            loadWidgetContent(settings);
        }, [props.attributes.data]);

        // fix changes on lost focus
        we.useEffect(function () {
            debug('[effect 11]');

            function onBlur(e){
                const t = e.target;
                if (!t) return;
                const tag = (t.tagName || '').toLowerCase();
                if (tag === 'input' || tag === 'textarea' || tag === 'select') {
                    if (isSettingsReady()) flushSaveNow();
                }
            }
            document.addEventListener('blur', onBlur, true);
            window.addEventListener('beforeunload', function(){ if (isSettingsReady()) flushSaveNow(); });
            return () => {
                document.removeEventListener('blur', onBlur, true);
            };
        }, []);

        we.useEffect(function () {
            debug('[effect 12]');
            function onPointerUpOrTouchEnd() {
                const t = (lastChangeTypeRef.current || '').toLowerCase();
                if (t === 'styles') {
                    flushSaveNow(t); 
                }
            }
            window.addEventListener('pointerup', onPointerUpOrTouchEnd, { passive: true });
            window.addEventListener('touchend',  onPointerUpOrTouchEnd, { passive: true });
            return () => {
                window.removeEventListener('pointerup', onPointerUpOrTouchEnd);
                window.removeEventListener('touchend',  onPointerUpOrTouchEnd);
            };
        }, []);

        // init settings when panel visible + settings HTML ready + real DOM exists
        function maybeInitSettings(){
            if (!settingsVisible) return;
            if (!settingsContent) return;

            var $settingsElement = getSettingsElement();
            if (!$settingsElement || $settingsElement.length === 0) return;

            var elem = $settingsElement[0];
            if (!ucSettings.isInited() || initedSettingsElementRef.current !== elem) {
                initSettings();
            }
        }

        function runFirstPreviewOnce(maxTries = 30, delay = 50) {
            if (didFirstPreviewRef.current) return;
            if (firstPreviewTimerRef.current) return;

            let tries = 0;

            const tick = () => {
                if (didFirstPreviewRef.current) { firstPreviewTimerRef.current = null; return; }

                if (!isSettingsReady()) {
                    if (++tries < maxTries) firstPreviewTimerRef.current = setTimeout(tick, delay);
                    else firstPreviewTimerRef.current = null;
                    return;
                }

                const attr = (() => { try { return props.attributes.data ? JSON.parse(props.attributes.data) : {}; } catch(e){ return {}; } })();

                if (attr?.uc_items?.length) {
                    const payloadStr = JSON.stringify(attr);
                    lastPreviewPayloadRef.current = payloadStr;
                    loadWidgetContent(attr);
                    didFirstPreviewRef.current    = true;
                    firstPreviewReadyRef.current  = true;
                    firstPreviewTimerRef.current  = null;
                    return;
                }

                const def = ucSettings.getSettingsValues?.() || {};
                if (!def?.uc_items?.length) {
                    if (++tries < maxTries) firstPreviewTimerRef.current = setTimeout(tick, delay);
                    else firstPreviewTimerRef.current = null;
                    return;
                }

                const merged = { ...def, ...attr };
                const mergedStr = JSON.stringify(merged);

                if (lastSentDataRef.current !== mergedStr) {
                    suppressNextReloadRef.current = true;
                    lastSentDataRef.current = mergedStr;
                    props.setAttributes({ data: mergedStr });
                }

                lastPreviewPayloadRef.current = mergedStr;
                loadWidgetContent(merged); 

                didFirstPreviewRef.current    = true;
                firstPreviewReadyRef.current  = true;
                firstPreviewTimerRef.current  = null;
            };

            tick();
        }

        var settings = el(
            wbe.InspectorControls, {},
            el("div", { className: "ue-gutenberg-settings-error", id: settingsErrorId }),
            settingsContent && el("div", { id: settingsId, dangerouslySetInnerHTML: { __html: settingsContent } }),
            !settingsContent && isLoadingSettings && el("div", { className: "ue-gutenberg-settings-spinner" }, el(wc.Spinner)),
            !settingsContent && !isLoadingSettings && el("div", null, "No settings found or error occured."),
        );

        var widget = el(
            "div", { className: "ue-gutenberg-widget-wrapper" },
            widgetContent && el("div", { className: "ue-gutenberg-widget-content", id: widgetId, ref: widgetRef }),
            widgetContent && el("div", { className: "ue-gutenberg-widget-loader", ref: widgetLoaderRef }, el(wc.Spinner)),
            !widgetContent && el("div", { className: "ue-gutenberg-widget-placeholder" }, el(wc.Spinner)),
        );

        return el("div", blockProps, settings, widget);
    };

    for (var name in g_gutenbergBlocks) {
        var block = g_gutenbergBlocks[name];
        var args  = jQuery.extend(block, { edit: edit });

        // convert inline SVG icon string to element
        if (typeof args.icon === 'string' && args.icon.trim().startsWith('<svg')) {
            try {
                const sanitized = args.icon.trim();
                args.icon = el('span', { dangerouslySetInnerHTML: { __html: sanitized } });
            } catch (e) {
                args.icon = '';
            }
        }
        wp.blocks.registerBlockType(name, args);
    }
})(wp);
