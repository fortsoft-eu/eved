function logEvedUaException(oException) {
    if (window.console && window.console.error) {
        window.console.error(oException);
    }
}

function getEvedUaId() {
    var oMeta = document.querySelector("meta[name=\"eved-ua-id\"]");
    var iId = oMeta ? parseInt(oMeta.getAttribute("content") || "0", 10) : 0;
    return isNaN(iId) ? 0 : iId;
}

function getEvedUaGpu() {
    try {
        var oCanvas = document.createElement("canvas");
        var oGl = oCanvas.getContext("webgl") || oCanvas.getContext("experimental-webgl");
        var sRenderer;
        if (!oGl) {
            return "Not supported";
        }
        sRenderer = oGl.getParameter(oGl.RENDERER);
        if (sRenderer) {
            return String(sRenderer);
        }
        return "Unknown GPU";
    } catch (oException) {
        logEvedUaException(oException);
        return "Error";
    }
}

function detectEvedUaFonts() {
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

function detectEvedUaPlugins() {
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

function detectEvedUaMimes() {
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

function sendEvedUaBeacon(sUrl, sBody) {
    var mBeaconBody;
    if (!navigator.sendBeacon) {
        return false;
    }
    try {
        mBeaconBody = typeof Blob == "function" ? new Blob([sBody], { type: "application/json" }) : sBody;
        return navigator.sendBeacon(sUrl, mBeaconBody);
    } catch (oException) {
        logEvedUaException(oException);
    }
    return false;
}

function sendEvedUaFingerprint(oFingerprint) {
    var sUrl = window.location.pathname + "?fingerprint=1";
    var sBody = JSON.stringify(oFingerprint);
    var blKeepAlive = sBody.length <= 60000;
    if (window.fetch) {
        fetch(sUrl, {
            method: "POST",
            credentials: "same-origin",
            cache: "no-store",
            keepalive: blKeepAlive,
            headers: {
                "Content-Type": "application/json"
            },
            body: sBody
        }).catch(function(oException) {
            logEvedUaException(oException);
            sendEvedUaBeacon(sUrl, sBody);
        });
        return;
    }
    sendEvedUaBeacon(sUrl, sBody);
}

function getEvedUaFullBrowserVersion(sBrowserName, aBrands) {
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

function addEvedUaBrowserData(oFingerprint, oUserAgentData) {
    var oResult = null;
    var oBrowser = {};
    var oOperatingSystem = {};
    var oPlatform = {};
    var aBrands = [];
    if (window.bowser && typeof window.bowser.parse == "function") {
        try {
            oResult = window.bowser.parse(navigator.userAgent || "", oUserAgentData || null);
        } catch (oException) {
            logEvedUaException(oException);
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
    oFingerprint.browser_version = getEvedUaFullBrowserVersion(oBrowser.name || "", aBrands) || oBrowser.version || "";
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

function collectAndSendEvedUaFingerprint() {
    var iUaId = getEvedUaId();
    var iCssWidth = window.screen.width;
    var iCssHeight = window.screen.height;
    var iDpr = window.devicePixelRatio || 1;
    var sTimeZone = "";
    var oFingerprint;
    var oLowEntropyData = null;
    if (iUaId < 1 || (!window.fetch && !navigator.sendBeacon)) {
        return;
    }
    try {
        if (window.Intl && Intl.DateTimeFormat) {
            sTimeZone = Intl.DateTimeFormat().resolvedOptions().timeZone || "";
        }
    } catch (oException) {
        logEvedUaException(oException);
        sTimeZone = "";
    }
    oFingerprint = {
        ua_id: iUaId,
        gpu: getEvedUaGpu(),
        fonts: detectEvedUaFonts(),
        screen: iCssWidth + "x" + iCssHeight,
        screen_physical: Math.round(iCssWidth * iDpr) + "x" + Math.round(iCssHeight * iDpr),
        depth: window.screen.colorDepth + "-bit",
        tz: sTimeZone,
        lang: navigator.language || "",
        platform: navigator.platform || "",
        plugins: detectEvedUaPlugins(),
        mimes: detectEvedUaMimes()
    };
    if (navigator.userAgentData) {
        if (typeof navigator.userAgentData.toJSON == "function") {
            oLowEntropyData = navigator.userAgentData.toJSON();
        } else {
            oLowEntropyData = {
                brands: navigator.userAgentData.brands || [],
                mobile: navigator.userAgentData.mobile,
                platform: navigator.userAgentData.platform || ""
            };
        }
        if (typeof navigator.userAgentData.getHighEntropyValues == "function") {
            navigator.userAgentData.getHighEntropyValues(["architecture", "bitness", "fullVersionList", "model", "platformVersion"]).then(function(oUserAgentData) {
                addEvedUaBrowserData(oFingerprint, oUserAgentData);
                sendEvedUaFingerprint(oFingerprint);
            }).catch(function(oException) {
                logEvedUaException(oException);
                addEvedUaBrowserData(oFingerprint, oLowEntropyData);
                sendEvedUaFingerprint(oFingerprint);
            });
            return;
        }
    }
    addEvedUaBrowserData(oFingerprint, oLowEntropyData);
    sendEvedUaFingerprint(oFingerprint);
}

window.addEventListener("load", collectAndSendEvedUaFingerprint);
