var app = new Hnd.App({
    searchEngineMinChars: 3
});

var oCameraImageResizeObserver = null;

function logFilmGalleryException(oException) {
    if (window.console && window.console.error) {
        window.console.error(oException);
    }
}

function getFilmUaGpu() {
    try {
        var oCanvas = document.createElement("canvas");
        var oGl = oCanvas.getContext("webgl") || oCanvas.getContext("experimental-webgl");
        var oDebugInfo;
        if (!oGl) {
            return "Not supported";
        }
        oDebugInfo = oGl.getExtension("WEBGL_debug_renderer_info");
        if (oDebugInfo) {
            return oGl.getParameter(oDebugInfo.UNMASKED_RENDERER_WEBGL);
        }
        return "Unknown GPU";
    } catch (oException) {
        logFilmGalleryException(oException);
        return "Error";
    }
}

function detectFilmUaFonts() {
    var aTestFonts = ["Arial", "Times New Roman", "Courier New", "Comic Sans MS", "Tahoma"];
    var sBaseFont = "monospace";
    var aResults = [];
    var oCanvas = document.createElement("canvas");
    var oContext = oCanvas.getContext("2d");
    var iBaseWidth;
    var iI;
    var iWidth;
    if (!oContext) {
        return aResults;
    }
    oContext.font = "72px " + sBaseFont;
    iBaseWidth = oContext.measureText("abcdefghiABCDEFGHI").width;
    for (iI = 0; iI < aTestFonts.length; iI += 1) {
        oContext.font = "72px " + aTestFonts[iI] + "," + sBaseFont;
        iWidth = oContext.measureText("abcdefghiABCDEFGHI").width;
        if (iWidth !== iBaseWidth) {
            aResults.push(aTestFonts[iI]);
        }
    }
    return aResults;
}

function detectFilmUaPlugins() {
    var aResults = [];
    var iI;
    if (!navigator.plugins) {
        return aResults;
    }
    for (iI = 0; iI < navigator.plugins.length; iI += 1) {
        aResults.push(navigator.plugins[iI].name);
    }
    return aResults;
}

function detectFilmUaMimes() {
    var aResults = [];
    var iI;
    if (!navigator.mimeTypes) {
        return aResults;
    }
    for (iI = 0; iI < navigator.mimeTypes.length; iI += 1) {
        aResults.push(navigator.mimeTypes[iI].type);
    }
    return aResults;
}

function sendFilmUaFingerprint(oFingerprint) {
    fetch(window.location.pathname + "?fingerprint=1", {
        method: "POST",
        credentials: "same-origin",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(oFingerprint)
    })
}

function getFilmUaFullBrowserVersion(sBrowserName, aBrands) {
    var aNames = [sBrowserName];
    var iI;
    var iJ;
    if (sBrowserName == "Chrome") {
        aNames.push("Google Chrome", "Chromium");
    } else if (sBrowserName == "Microsoft Edge") {
        aNames.push("Microsoft Edge");
    } else if (sBrowserName == "Opera") {
        aNames.push("Opera");
    }
    for (iI = 0; iI < aNames.length; iI += 1) {
        for (iJ = 0; iJ < aBrands.length; iJ += 1) {
            if (aBrands[iJ].brand == aNames[iI] && aBrands[iJ].version) {
                return aBrands[iJ].version;
            }
        }
    }
    return "";
}

function addFilmUaBrowserData(oFingerprint, oUserAgentData) {
    var oResult = null;
    var oBrowser = {};
    var oOperatingSystem = {};
    var oPlatform = {};
    var aBrands = [];
    if (window.bowser && typeof window.bowser.parse == "function") {
        try {
            oResult = window.bowser.parse(navigator.userAgent || "", oUserAgentData || null);
        } catch (oException) {
            logFilmGalleryException(oException);
            oResult = null;
        }
    }
    if (oResult) {
        oBrowser = oResult.browser || {};
        oOperatingSystem = oResult.os || {};
        oPlatform = oResult.platform || {};
    }
    if (oUserAgentData) {
        aBrands = oUserAgentData.fullVersionList || oUserAgentData.brands || [];
    }
    oFingerprint.browser_name = oBrowser.name || "";
    oFingerprint.browser_version = getFilmUaFullBrowserVersion(oBrowser.name || "", aBrands) || oBrowser.version || "";
    oFingerprint.os_name = oOperatingSystem.name || (oUserAgentData ? oUserAgentData.platform || "" : "");
    oFingerprint.os_version = (oUserAgentData ? oUserAgentData.platformVersion || "" : "") || oOperatingSystem.version || "";
    oFingerprint.platform_type = oPlatform.type || "";
    oFingerprint.device_vendor = oPlatform.vendor || "";
    oFingerprint.device_model = (oUserAgentData ? oUserAgentData.model || "" : "") || oPlatform.model || "";
    oFingerprint.architecture = oUserAgentData ? oUserAgentData.architecture || "" : "";
    oFingerprint.bitness = oUserAgentData ? oUserAgentData.bitness || "" : "";
    oFingerprint.is_mobile = oUserAgentData && typeof oUserAgentData.mobile == "boolean" ? oUserAgentData.mobile : null;
    oFingerprint.ua_brands = JSON.stringify(aBrands);
}

function collectAndSendFilmUaFingerprint() {
    var iCssWidth = window.screen.width;
    var iCssHeight = window.screen.height;
    var iDpr = window.devicePixelRatio || 1;
    var sTimeZone = "";
    var oFingerprint;
    var oLowEntropyData = null;
    if (!window.fetch) {
        return;
    }
    try {
        if (window.Intl && Intl.DateTimeFormat) {
            sTimeZone = Intl.DateTimeFormat().resolvedOptions().timeZone || "";
        }
    } catch (oException) {
        logFilmGalleryException(oException);
        sTimeZone = "";
    }
    oFingerprint = {
        gpu: getFilmUaGpu(),
        fonts: detectFilmUaFonts(),
        screen: iCssWidth + "x" + iCssHeight,
        screen_physical: Math.round(iCssWidth * iDpr) + "x" + Math.round(iCssHeight * iDpr),
        depth: window.screen.colorDepth + "-bit",
        tz: sTimeZone,
        lang: navigator.language || "",
        platform: navigator.platform || "",
        plugins: detectFilmUaPlugins(),
        mimes: detectFilmUaMimes()
    };
    if (navigator.userAgentData) {
        if (typeof navigator.userAgentData.toJSON == "function") {
            oLowEntropyData = navigator.userAgentData.toJSON();
        } else {
            oLowEntropyData = {
                brands: navigator.userAgentData.brands || [],
                mobile: navigator.userAgentData.mobile,
                platform: navigator.userAgentData.platform || ""
            }
        }
        if (typeof navigator.userAgentData.getHighEntropyValues == "function") {
            navigator.userAgentData.getHighEntropyValues(["architecture", "bitness", "fullVersionList", "model", "platformVersion"]).then(function (oUserAgentData) {
                addFilmUaBrowserData(oFingerprint, oUserAgentData);
                sendFilmUaFingerprint(oFingerprint);
            }).catch(function (oException) {
                logFilmGalleryException(oException);
                addFilmUaBrowserData(oFingerprint, oLowEntropyData);
                sendFilmUaFingerprint(oFingerprint);
            });
            return;
        }
    }
    addFilmUaBrowserData(oFingerprint, oLowEntropyData);
    sendFilmUaFingerprint(oFingerprint);
}

function resizeCameraImage() {
    var oContainer = document.getElementById("camera-image");
    var oImage;
    var iImageHeight;
    if (!oContainer) {
        return;
    }
    oImage = oContainer.getElementsByTagName("img")[0];
    if (!oImage) {
        return;
    }
    iImageHeight = oImage.offsetHeight;
    oContainer.style.height = iImageHeight > 500 ? Math.round(500 + (iImageHeight - 500) / 1.6) + "px" : "auto";
}

function setupCameraImage() {
    var oContainer = document.getElementById("camera-image");
    var oImage;
    if (!oContainer) {
        return;
    }
    oImage = oContainer.getElementsByTagName("img")[0];
    if (!oImage) {
        return;
    }
    if (oImage.complete) {
        resizeCameraImage();
    } else {
        oImage.addEventListener("load", resizeCameraImage);
    }
    if (window.ResizeObserver) {
        if (oCameraImageResizeObserver) {
            oCameraImageResizeObserver.disconnect();
        }
        oCameraImageResizeObserver = new ResizeObserver(resizeCameraImage);
        oCameraImageResizeObserver.observe(oImage);
    }
}

function getIdFromUrl(url) {
    try {
        var u = new URL(url, window.location.href);
        return u.searchParams.get("id");
    } catch (e) {
        logFilmGalleryException(e);
        return null;
    }
}

function syncJsTreeSelection(app, urlJustLoaded) {
    var topic = document.querySelector("#topic-content");
    var id = topic && topic.dataset ? topic.dataset.hndId : null;
    if (!id || id === "") {
        id = getIdFromUrl(urlJustLoaded) || getIdFromUrl(window.location.href);
    }
    if (!id) {
        return;
    }
    var tree = $(app.options.elTreeContainers).jstree(true);
    if (!tree) {
        return;
    }
    if (!tree.get_node(id)) {
        var $a = $(app.options.elTreeContainers).find('a[href="?id=' + id + '"]');
        if ($a.length) {
            var nodeId = $a.closest("li").attr("id");
            if (nodeId) {
                id = nodeId;
            }
        }
    }
    tree.deselect_all(true);
    tree.select_node(id, true, false);
}

function openFancyboxGroupFromAnchor(a) {
    var esc = (window.CSS && CSS.escape) ? CSS.escape : function (s) {
        return String(s).replace(/[^\w-]/g, '\\$&');
    };
    var group = a.getAttribute("data-fancybox") || "";
    var items = [];
    var startIndex = 0;
    if (group) {
        var nodeList = document.querySelectorAll('a[data-fancybox="' + esc(group) + '"]');
        items = Array.from(nodeList).map(function (el, idx) {
            if (el === a) {
                startIndex = idx;
            }
            var cap = el.dataset.caption || el.title || (el.querySelector("img") ? el.querySelector("img").alt : "");
            return {
                src: el.href,
                type: "image",
                caption: cap
            }
        })
    } else {
        var cap = a.dataset.caption || a.title || (a.querySelector("img") ? a.querySelector("img").alt : "");
        items = [{
                src: a.href,
                type: "image",
                caption: cap
            }
        ];
    }
    Fancybox.show(items, {
        startIndex: startIndex,
        wheel: "slide",
        Thumbs: {
            autoStart: true
        },
        Carousel: {
            infinite: false,
            transition: "fade",
            Autoplay: {
                timeout: 5000,
                autoStart: false,
                pauseOnHover: true
            }
        },
        Images: {
            zoom: false
        }
    })
}

function prepareImagesForScreenshot(container) {
    const replacements = [];
    container.querySelectorAll("img").forEach(img => {
        const style = getComputedStyle(img);
        const objectFit = style.objectFit;
        if (objectFit === "cover" || objectFit === "contain") {
            const src = img.src;
            const width = img.clientWidth;
            const height = img.clientHeight;
            const parent = img.parentNode;
            const div = document.createElement("div");
            div.style.width = width + "px";
            div.style.height = height + "px";
            div.style.backgroundImage = `url('${src}')`;
            div.style.backgroundSize = objectFit;
            div.style.backgroundPosition = "center";
            div.style.backgroundRepeat = "no-repeat";
            parent.replaceChild(div, img);
            replacements.push({
                parent,
                div,
                img
            })
        }
    });
    return function restoreDom() {
        replacements.forEach(({
                parent,
                div,
                img
            }) => {
            parent.replaceChild(img, div);
        })
    }
}

function onMetadataSubmit(form) {
    var id = form.querySelector('input[name="id"]').value;
    var metadataValue = form.querySelector('input[name="metadata"]').value;
    var url = form.action + "?id=" + encodeURIComponent(id) + "&metadata=" + encodeURIComponent(metadataValue);
    app.DoHandleLink(id, document.title, url, "_self", false);
    return false;
}

function onSelectChange(selectElement) {
    var form = selectElement.form;
    var id = form.querySelector('input[name="id"]').value;
    var paramName = selectElement.name;
    var url = form.action + "?id=" + encodeURIComponent(id) + "&" + encodeURIComponent(paramName) + "=" + encodeURIComponent(selectElement.value);
    app.DoHandleLink(id, document.title, url, "_self", false);
}

function onSetChange(selectElement) {
    var form = selectElement.form;
    var id = form.querySelector('input[name="id"]').value;
    var img = form.querySelector('input[name="img"]').value;
    var dir = form.querySelector('input[name="dir"]').value;
    var url = form.action + "?id=" + encodeURIComponent(id) + "&dir=" + encodeURIComponent(dir) + "&img=" + encodeURIComponent(img) + "&set=" + encodeURIComponent(selectElement.value);
    app.DoHandleLink(id, document.title, url, "_self", false, true);
}

function onSavePng(button, fileName) {
    button.disabled = true;
    const element = document.getElementById("main-content-gallery");
    const restoreDom = prepareImagesForScreenshot(element);
    html2canvas(element, {
        scale: 3
    }).then(canvas => {
        restoreDom();
        canvas.toBlob(function (blob) {
            const link = document.createElement("a");
            link.download = fileName + ".png";
            link.href = URL.createObjectURL(blob);
            link.click();
            URL.revokeObjectURL(link.href);
            button.disabled = false;
        }, "image/png", 1.0)
    }).catch(error => {
        logFilmGalleryException(error);
        restoreDom();
        button.disabled = false;
    })
}

function initializeGalleryControlHandlers() {
    document.addEventListener("change", function (event) {
        var selectElement = event.target.closest ? event.target.closest(".js-gallery-select") : null;
        var setSelectElement;
        if (selectElement) {
            onSelectChange(selectElement);
            return;
        }
        setSelectElement = event.target.closest ? event.target.closest(".js-gallery-set-select") : null;
        if (setSelectElement) {
            onSetChange(setSelectElement);
        }
    }, true);

    document.addEventListener("submit", function (event) {
        var form = event.target.closest ? event.target.closest(".js-gallery-metadata-form") : null;
        if (!form) {
            return;
        }
        event.preventDefault();
        onMetadataSubmit(form);
    }, true);

    document.addEventListener("click", function (event) {
        var button = event.target.closest ? event.target.closest(".js-save-png") : null;
        if (!button) {
            return;
        }
        event.preventDefault();
        event.stopPropagation();
        onSavePng(button, button.getAttribute("data-file-name") || "gallery");
    }, true)
}

function initializeAdminLinkHandlers() {
    if (initializeAdminLinkHandlers.initialized) {
        return;
    }
    initializeAdminLinkHandlers.initialized = true;

    document.addEventListener("click", function (event) {
        var link = event.target.closest ? event.target.closest("a[data-admin-link]") : null;
        if (!link) {
            return;
        }
        event.preventDefault();
        event.stopImmediatePropagation();
        window.open(link.href, link.target || "_blank", "noopener");
    }, true)
}

app.EVENTS.onTopicChanged = function (url) {
    var topicContent = document.querySelector("#topic-content");
    if (topicContent) {
        var topicTitle = topicContent.dataset.hndTitle;
        var headerH1 = document.querySelector("header.headroom h1");
        if (headerH1 && topicTitle) {
            headerH1.textContent = topicTitle;
        }
        if (topicTitle) {
            document.title = topicTitle;
        }
        syncJsTreeSelection(app, url);
    }
    setupCameraImage();
    collectAndSendFilmUaFingerprint();
};

document.addEventListener("DOMContentLoaded", function () {
    syncJsTreeSelection(app, window.location.href);
    setupCameraImage();
    collectAndSendFilmUaFingerprint();
    initializeGalleryControlHandlers();
});

window.addEventListener("resize", resizeCameraImage);
initializeAdminLinkHandlers();
app.Boot();

document.addEventListener("click", function (ev) {
    var a = ev.target.closest("a[data-fancybox]");
    if (!a) {
        return;
    }
    ev.preventDefault();
    ev.stopPropagation();
    ev.stopImmediatePropagation();
    openFancyboxGroupFromAnchor(a);
}, true);

document.addEventListener("DOMContentLoaded", function () {
    const maxRetries = 5;
    const retryDelay = 500;

    function setupImageRetry(img) {
        if (img.dataset.retryInitialized === "1") {
            return;
        }
        img.dataset.retryInitialized = "1";
        if (!img.dataset.originalSrc) {
            img.dataset.originalSrc = img.currentSrc || img.src;
        }
        img.addEventListener("error", function handleError() {
            const originalSrc = img.dataset.originalSrc;
            const currentAttempt = parseInt(img.dataset.retryAttempt || "0", 10) + 1;
            if (currentAttempt > maxRetries) {
                img.removeEventListener("error", handleError);
                return;
            }
            img.dataset.retryAttempt = currentAttempt.toString();
            const base = originalSrc;
            const suffix = "reload=" + Date.now() + "-" + currentAttempt;
            const newSrc = base + (base.indexOf("?") >= 0 ? "&" : "?") + suffix;
            setTimeout(function () {
                img.src = newSrc;
            }, retryDelay)
        });
        img.addEventListener("load", function () {
            img.dataset.retryAttempt = "";
        })
    }

    const initialImages = document.querySelectorAll("img");
    for (const img of initialImages) {
        setupImageRetry(img);
    }
    const observer = new MutationObserver(function (mutations) {
        for (const mutation of mutations) {
            for (const node of mutation.addedNodes) {
                if (node.nodeType !== Node.ELEMENT_NODE) {
                    continue;
                }
                if (node.tagName === "IMG") {
                    setupImageRetry(node);
                }
                const nestedImages = node.querySelectorAll ? node.querySelectorAll("img") : [];
                for (const nestedImg of nestedImages) {
                    setupImageRetry(nestedImg);
                }
            }
        }
    });
    observer.observe(document.documentElement, {
        childList: true,
        subtree: true
    })
});
