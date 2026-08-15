var sStorageKey = "eved-selected-style";
var oStyleLinks = document.querySelectorAll("link[rel~=\"stylesheet\"][title]");

for (let i = 0; i < oStyleLinks.length; i += 1) {
    oStyleLinks[i].setAttribute("data-style-title", oStyleLinks[i].title);
}

function logStyleException(oException) {
    if (window.console && window.console.error) {
        window.console.error(oException);
    }
}

function getStyleTitle(oStyleLink) {
    return oStyleLink.getAttribute("data-style-title") || oStyleLink.title;
}

function hasStyleTitle(sTitle) {
    for (let i = 0; i < oStyleLinks.length; i += 1) {
        if (getStyleTitle(oStyleLinks[i]) === sTitle) {
            return true;
        }
    }
    return false;
}

function getSelectedStyleTitle() {
    var sTitle = "selectedStyleSheetSet" in document && typeof document.selectedStyleSheetSet === "string" ? document.selectedStyleSheetSet : "";
    if (sTitle && hasStyleTitle(sTitle)) {
        return sTitle;
    }
    for (let i = 0; i < oStyleLinks.length; i += 1) {
        if (!oStyleLinks[i].disabled && oStyleLinks[i].sheet && !oStyleLinks[i].sheet.disabled) {
            return getStyleTitle(oStyleLinks[i]);
        }
    }
    return "";
}

function saveSelectedStyle() {
    var sTitle = getSelectedStyleTitle();
    if (!sTitle) {
        return;
    }
    try {
        window.localStorage.setItem(sStorageKey, sTitle);
    } catch (oException) {
        logStyleException(oException);
    }
}

function restoreSelectedStyle() {
    var blFound = false;
    var sTitle;
    try {
        sTitle = window.localStorage.getItem(sStorageKey);
    } catch (oException) {
        logStyleException(oException);
        return;
    }
    if (!sTitle) {
        return;
    }
    for (let i = 0; i < oStyleLinks.length; i += 1) {
        if (getStyleTitle(oStyleLinks[i]) === sTitle) {
            blFound = true;
            break;
        }
    }
    if (!blFound) {
        return;
    }
    if ("selectedStyleSheetSet" in document) {
        for (let i = 0; i < oStyleLinks.length; i += 1) {
            oStyleLinks[i].disabled = getStyleTitle(oStyleLinks[i]) !== sTitle;
        }
        document.selectedStyleSheetSet = sTitle;
    } else {
        for (let i = 0; i < oStyleLinks.length; i += 1) {
            if (getStyleTitle(oStyleLinks[i]) === sTitle) {
                oStyleLinks[i].rel = "stylesheet";
                oStyleLinks[i].removeAttribute("title");
                oStyleLinks[i].disabled = false;
            } else {
                oStyleLinks[i].rel = "alternate stylesheet";
                oStyleLinks[i].title = getStyleTitle(oStyleLinks[i]);
                oStyleLinks[i].disabled = true;
            }
        }
    }
}

if (oStyleLinks.length) {
    restoreSelectedStyle();
    document.addEventListener("mousedown", saveSelectedStyle, true);
    document.addEventListener("click", saveSelectedStyle, true);
    document.addEventListener("auxclick", saveSelectedStyle, true);
    window.addEventListener("pagehide", saveSelectedStyle);
}
