var iAdminModalCount = 0;
var sAdminBodyOverflow = "";
var iAdminScrollLeft = 0;
var iAdminScrollTop = 0;
var aRenderThrobbers = null;
var blRenderThrobberScrollLocked = false;
var sRenderThrobberLockTarget = "";
var sRenderThrobberBodyOverflow = "";
var sRenderThrobberHtmlOverflow = "";
var sRenderThrobberViewportContent = "";
var iRenderThrobberScrollLeft = 0;
var iRenderThrobberScrollTop = 0;

function logAdminException(oException) {
    if (window.console && window.console.error) {
        window.console.error(oException);
    }
}

function getAdminCsrfToken() {
    var oMeta = document.querySelector("meta[name=\"csrf-token\"]");
    return oMeta ? (oMeta.getAttribute("content") || "") : "";
}

function getAdminEmoji(sName) {
    var oData = document.getElementById("nx-emoji-data");
    return oData ? (oData.getAttribute("data-" + sName) || "") : "";
}

function appendAdminCsrfToken(oData) {
    var sToken = getAdminCsrfToken();
    if (oData && sToken) {
        oData.append("ex_csrf_token", sToken);
    }
}

function getAdminAjaxHeaders() {
    var aHeaders = {
        "X-Requested-With": "XMLHttpRequest"
    };
    var sToken = getAdminCsrfToken();
    if (sToken) {
        aHeaders["X-CSRF-Token"] = sToken;
    }
    return aHeaders;
}

function getAdminEncodedFieldName(sName) {
    if (sName.substring(sName.length - 2) == "[]") {
        return sName.substring(0, sName.length - 2) + "_b64[]";
    }
    return sName + "_b64";
}

function encodeAdminPostValue(sValue) {
    var sText = sValue === null || typeof sValue == "undefined" ? "" : String(sValue);
    return window.btoa(unescape(encodeURIComponent(sText)));
}

function appendAdminEncodedValue(oData, sName, sValue) {
    var sText = sValue === null || typeof sValue == "undefined" ? "" : String(sValue);
    var blArrayField = sName.substring(sName.length - 2) == "[]";
    try {
        oData.append(getAdminEncodedFieldName(sName), encodeAdminPostValue(sText));
        if (blArrayField) {
            oData.append(sName, sText);
        }
        return;
    } catch (oException) {
        logAdminException(oException);
    }
    oData.append(sName, sText);
}

function appendAdminEncodedJson(oData, sName, mValue) {
    appendAdminEncodedValue(oData, sName, JSON.stringify(mValue));
}

function setAdminDialogError(oError, sMessage) {
    if (!oError) {
        return;
    }
    oError.textContent = sMessage || "";
    oError.style.display = sMessage ? "" : "none";
}

function dispatchAdminInputEvent(oElement) {
    var oEvent;
    if (!oElement) {
        return;
    }
    if (typeof Event == "function") {
        oEvent = new Event("input");
    } else {
        oEvent = document.createEvent("Event");
        oEvent.initEvent("input", true, true);
    }
    oElement.dispatchEvent(oEvent);
}

function refreshAdminTableFilter() {
    dispatchAdminInputEvent(document.querySelector(".js-table-filter"));
}

function closeAdminDialogElement(oDialog) {
    if (oDialog && oDialog.parentNode) {
        oDialog.parentNode.removeChild(oDialog);
        unlockAdminModalScroll();
    }
}

function copyAdminTextWithTextarea(sText) {
    var oTextArea = document.createElement("textarea");
    var blResult = false;
    oTextArea.value = sText;
    oTextArea.setAttribute("readonly", "readonly");
    oTextArea.style.position = "fixed";
    oTextArea.style.left = "-9999px";
    document.body.appendChild(oTextArea);
    oTextArea.select();
    try {
        blResult = document.execCommand("copy");
    } catch (oException) {
        logAdminException(oException);
        blResult = false;
    }
    document.body.removeChild(oTextArea);
    return blResult;
}

function lockAdminModalScroll() {
    if (iAdminModalCount === 0) {
        sAdminBodyOverflow = document.body.style.overflow || "";
        iAdminScrollLeft = window.pageXOffset || document.documentElement.scrollLeft || document.body.scrollLeft || 0;
        iAdminScrollTop = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
        document.body.style.overflow = "hidden";
        window.scrollTo(iAdminScrollLeft, iAdminScrollTop);
    }
    iAdminModalCount += 1;
}

function unlockAdminModalScroll() {
    if (iAdminModalCount > 0) {
        iAdminModalCount -= 1;
    }
    if (iAdminModalCount === 0) {
        document.body.style.overflow = sAdminBodyOverflow;
        window.scrollTo(iAdminScrollLeft, iAdminScrollTop);
    }
}

function focusAdminElement(oElement, blSelectText) {
    var iScrollLeft = window.pageXOffset || document.documentElement.scrollLeft || document.body.scrollLeft || 0;
    var iScrollTop = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
    if (!oElement) {
        return;
    }
    try {
        oElement.focus({
            "preventScroll": true
        });
    } catch (oException) {
        logAdminException(oException);
        oElement.focus();
    }
    window.scrollTo(iScrollLeft, iScrollTop);
    if (blSelectText === true && isAdminTextSelectionField(oElement)) {
        selectAdminTextField(oElement);
    }
}

function isAdminTextSelectionField(oElement) {
    var sTag = oElement && oElement.tagName ? oElement.tagName.toLowerCase() : "";
    var sType;
    if (!oElement || oElement.disabled) {
        return false;
    }
    if (sTag == "textarea") {
        return true;
    }
    if (sTag != "input") {
        return false;
    }
    sType = (oElement.getAttribute("type") || "text").toLowerCase();
    return sType == "text" || sType == "password" || sType == "search" || sType == "email" || sType == "url" || sType == "tel" || sType == "number";
}

function selectAdminTextField(oElement) {
    if (!isAdminTextSelectionField(oElement)) {
        return;
    }
    try {
        oElement.select();
    } catch (oException) {
        logAdminException(oException);
    }
    if (typeof oElement.setSelectionRange == "function") {
        try {
            oElement.setSelectionRange(0, (oElement.value || "").length);
        } catch (oException) {
            logAdminException(oException);
        }
    }
}

function addAdminClass(oElement, sClass) {
    if (oElement && (" " + oElement.className + " ").indexOf(" " + sClass + " ") === -1) {
        oElement.className += (oElement.className ? " " : "") + sClass;
    }
}

function removeAdminClass(oElement, sClass) {
    if (oElement) {
        oElement.className = (" " + oElement.className + " ").replace(" " + sClass + " ", " ").replace(/^\s+|\s+$/g, "");
    }
}

function preventRenderThrobberScroll(oEvent) {
    if (!blRenderThrobberScrollLocked) {
        return;
    }
    if (oEvent && oEvent.cancelable !== false && oEvent.preventDefault) {
        oEvent.preventDefault();
    }
}

function restoreRenderThrobberScroll() {
    if (blRenderThrobberScrollLocked) {
        window.scrollTo(iRenderThrobberScrollLeft, iRenderThrobberScrollTop);
    }
}

function getRenderThrobberViewportElement() {
    return document.querySelector("meta[name=\"viewport\"]");
}

function lockRenderThrobberZoom() {
    var oRoot = document.documentElement;
    var oViewport = getRenderThrobberViewportElement();
    if (!oRoot || !oViewport || oRoot.getAttribute("data-render-throbber-zoom-lock") != "1") {
        return;
    }
    sRenderThrobberViewportContent = oRoot.getAttribute("data-render-throbber-viewport-content") || oViewport.getAttribute("content") || "";
    oViewport.setAttribute("content", "width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no");
}

function unlockRenderThrobberZoom() {
    var oViewport = getRenderThrobberViewportElement();
    if (oViewport && sRenderThrobberViewportContent != "") {
        oViewport.setAttribute("content", sRenderThrobberViewportContent);
    }
    sRenderThrobberViewportContent = "";
}

function addRenderThrobberScrollLockEvent(sType) {
    try {
        document.addEventListener(sType, preventRenderThrobberScroll, {
            "capture": true,
            "passive": false
        });
    } catch (oException) {
        logAdminException(oException);
        document.addEventListener(sType, preventRenderThrobberScroll, true);
    }
}

function removeRenderThrobberScrollLockEvent(sType) {
    document.removeEventListener(sType, preventRenderThrobberScroll, true);
}

function lockRenderThrobberScroll() {
    var oRoot = document.documentElement;
    var oBody = document.body;
    if (blRenderThrobberScrollLocked || !oRoot || !oBody) {
        return;
    }
    sRenderThrobberLockTarget = oRoot.getAttribute("data-render-throbber-lock-target") == "html" ? "html" : "body";
    sRenderThrobberBodyOverflow = oBody.style.overflow || "";
    sRenderThrobberHtmlOverflow = oRoot.style.overflow || "";
    iRenderThrobberScrollLeft = window.pageXOffset || oRoot.scrollLeft || oBody.scrollLeft || 0;
    iRenderThrobberScrollTop = window.pageYOffset || oRoot.scrollTop || oBody.scrollTop || 0;
    if (sRenderThrobberLockTarget == "html") {
        oRoot.style.overflow = "hidden";
    } else {
        oBody.style.overflow = "hidden";
    }
    oRoot.setAttribute("data-render-throbber-lock-active", "1");
    blRenderThrobberScrollLocked = true;
    lockRenderThrobberZoom();
    addRenderThrobberScrollLockEvent("touchstart");
    addRenderThrobberScrollLockEvent("touchmove");
    addRenderThrobberScrollLockEvent("wheel");
    window.addEventListener("scroll", restoreRenderThrobberScroll, true);
    window.scrollTo(iRenderThrobberScrollLeft, iRenderThrobberScrollTop);
}

function unlockRenderThrobberScroll() {
    var oRoot = document.documentElement;
    var oBody = document.body;
    if (!blRenderThrobberScrollLocked) {
        if (oRoot) {
            oRoot.removeAttribute("data-render-throbber-lock-active");
        }
        return;
    }
    removeRenderThrobberScrollLockEvent("touchstart");
    removeRenderThrobberScrollLockEvent("touchmove");
    removeRenderThrobberScrollLockEvent("wheel");
    window.removeEventListener("scroll", restoreRenderThrobberScroll, true);
    if (sRenderThrobberLockTarget == "html" && oRoot) {
        oRoot.style.overflow = sRenderThrobberHtmlOverflow;
    } else if (oBody) {
        oBody.style.overflow = sRenderThrobberBodyOverflow;
    }
    if (oRoot) {
        oRoot.removeAttribute("data-render-throbber-lock-active");
    }
    unlockRenderThrobberZoom();
    blRenderThrobberScrollLocked = false;
    window.scrollTo(iRenderThrobberScrollLeft, iRenderThrobberScrollTop);
}

function prepareRenderThrobbers() {
    aRenderThrobbers = document.querySelectorAll(".js-render-throbber");
    if (aRenderThrobbers && aRenderThrobbers.length > 0) {
        lockRenderThrobberScroll();
    }
}

function scheduleRenderThrobberHide() {
    var aThrobbers = aRenderThrobbers || document.querySelectorAll(".js-render-throbber");
    if (!aThrobbers || aThrobbers.length === 0) {
        return;
    }

    function hideRenderThrobbers() {
        for (var iI = 0; iI < aThrobbers.length; iI += 1) {
            aThrobbers[iI].hidden = true;
        }
        unlockRenderThrobberScroll();
    }

    if (window.requestAnimationFrame) {
        window.requestAnimationFrame(function () {
            window.requestAnimationFrame(hideRenderThrobbers);
        });
        return;
    }
    window.setTimeout(hideRenderThrobbers, 0);
}

prepareRenderThrobbers();

document.addEventListener("DOMContentLoaded", prepareRenderThrobbers);

function setAdminMergeSourceListColumns(oDialog, oSourceList, iRenderedCount) {
    var iColumnCount = Math.max(1, Math.ceil(iRenderedCount / 10));
    if (!oSourceList || iColumnCount <= 1) {
        return;
    }
    oSourceList.style.gridTemplateColumns = "repeat(" + iColumnCount + ", minmax(0, 1fr))";
    addAdminClass(oDialog, "merge-dialog-wide");
}

function findAdminSubjectRowById(sSubjectId) {
    return sSubjectId ? document.querySelector("#nx-subjects-table tbody tr[data-subject-id=\"" + sSubjectId + "\"], #nx-birthdays-table tbody tr[data-subject-id=\"" + sSubjectId + "\"], #nx-interactions-table tbody tr[data-subject-id=\"" + sSubjectId + "\"], #nx-contacts-table tbody tr[data-subject-id=\"" + sSubjectId + "\"]") : null;
}

function getAdminSubjectRow(oElement) {
    return oElement && oElement.closest ? oElement.closest("tr[data-subject-id]") : null;
}

function beginAdminSubjectRowEdit(oRow) {
    if (oRow) {
        removeAdminClass(oRow, "nx-admin-row-saved");
        addAdminClass(oRow, "nx-admin-row-modal");
    }
}

function finishAdminSubjectRowEdit(oRow, blSaved) {
    if (oRow) {
        removeAdminClass(oRow, "nx-admin-row-modal");
        removeAdminClass(oRow, "nx-admin-row-saved");
        if (!blSaved) {
            addAdminClass(oRow, "nx-admin-row-modal");
            window.setTimeout(function () {
                removeAdminClass(oRow, "nx-admin-row-modal");
            }, 1000);
            return;
        }
        oRow.offsetWidth;
        addAdminClass(oRow, "nx-admin-row-saved");
        window.setTimeout(function () {
            removeAdminClass(oRow, "nx-admin-row-saved");
        }, 1400)
    }
}

function enableAdminDialogDrag(oDialog, oBox, oHeader) {
    var blDragging = false;
    var iOffsetX = 0;
    var iOffsetY = 0;

    function moveDialog(iClientX, iClientY) {
        var iMaxLeft = Math.max(0, window.innerWidth - oBox.offsetWidth);
        var iMaxTop = Math.max(0, window.innerHeight - oBox.offsetHeight);
        var iLeft = Math.max(0, Math.min(iClientX - iOffsetX, iMaxLeft));
        var iTop = Math.max(0, Math.min(iClientY - iOffsetY, iMaxTop));
        oBox.style.left = iLeft + "px";
        oBox.style.top = iTop + "px";
    }

    function stopDrag() {
        if (blDragging) {
            blDragging = false;
            document.body.style.userSelect = "";
            document.removeEventListener("mousemove", moveOnMouse);
            document.removeEventListener("mouseup", stopDrag);
        }
    }

    function moveOnMouse(oEvent) {
        if (blDragging) {
            moveDialog(oEvent.clientX, oEvent.clientY);
            oEvent.preventDefault();
        }
    }

    if (!oDialog || !oBox || !oHeader) {
        return;
    }
    oHeader.addEventListener("mousedown", function (oEvent) {
        var oTarget = oEvent.target;
        var oRect;
        if (oEvent.button !== 0 || (oTarget && oTarget.closest && oTarget.closest(".confirm-dialog-close"))) {
            return;
        }
        oRect = oBox.getBoundingClientRect();
        iOffsetX = oEvent.clientX - oRect.left;
        iOffsetY = oEvent.clientY - oRect.top;
        oBox.style.position = "absolute";
        oBox.style.left = oRect.left + "px";
        oBox.style.top = oRect.top + "px";
        oBox.style.margin = "0";
        blDragging = true;

        document.body.style.userSelect = "none";
        document.addEventListener("mousemove", moveOnMouse);
        document.addEventListener("mouseup", stopDrag);
        oEvent.preventDefault();
    })
}

document.addEventListener("DOMContentLoaded", function () {
    var oOpen = document.querySelector(".js-index-settings-open");
    var oDialog = document.getElementById("index-settings-dialog");
    var oBox = oDialog ? oDialog.querySelector(".confirm-dialog-box") : null;
    var oHeader = oDialog ? oDialog.querySelector(".confirm-dialog-header") : null;
    var oClose = oDialog ? oDialog.querySelector(".js-index-settings-close") : null;
    var oCancel = oDialog ? oDialog.querySelector(".js-index-settings-cancel") : null;
    var oFirstInput = oDialog ? oDialog.querySelector("input[type=\"checkbox\"]") : null;
    var oCzechiaCountry = oDialog ? oDialog.querySelector(".js-czechia-country-toggle") : null;
    var aCzechiaDependents = oDialog ? oDialog.querySelectorAll(".js-czechia-country-dependent") : [];
    var aSavedCheckboxStates = [];
    var closeOnEscape = function (oEvent) {
        if (oEvent.key == "Escape") {
            closeDialog();
        }
    };

    function rememberCheckboxStates() {
        var aInputs = oDialog ? oDialog.querySelectorAll("input[type=\"checkbox\"]") : [];
        aSavedCheckboxStates = [];
        for (var iI = 0; iI < aInputs.length; iI += 1) {
            aSavedCheckboxStates.push({
                "checked": aInputs[iI].checked,
                "disabled": aInputs[iI].disabled,
                "czechiaStored": aInputs[iI].getAttribute("data-czechia-stored")
            })
        }
    }

    function restoreCheckboxStates() {
        var aInputs = oDialog ? oDialog.querySelectorAll("input[type=\"checkbox\"]") : [];
        for (var iI = 0; iI < aInputs.length && iI < aSavedCheckboxStates.length; iI += 1) {
            aInputs[iI].checked = aSavedCheckboxStates[iI].checked;
            aInputs[iI].disabled = aSavedCheckboxStates[iI].disabled;
            aInputs[iI].removeAttribute("data-czechia-disabled");
            aInputs[iI].removeAttribute("data-czechia-disabled-checked");
            if (aSavedCheckboxStates[iI].czechiaStored !== null) {
                aInputs[iI].setAttribute("data-czechia-stored", aSavedCheckboxStates[iI].czechiaStored);
            }
        }
        updateCzechiaCountryOptions();
    }

    function updateCzechiaCountryOptions() {
        var blEnabled;
        var iI;
        var sStored;
        if (!oCzechiaCountry) {
            return;
        }
        blEnabled = oCzechiaCountry.checked;
        for (iI = 0; iI < aCzechiaDependents.length; iI += 1) {
            if (blEnabled) {
                aCzechiaDependents[iI].disabled = false;
                if (aCzechiaDependents[iI].getAttribute("data-czechia-disabled") == "1") {
                    sStored = aCzechiaDependents[iI].getAttribute("data-czechia-disabled-checked");
                    if (sStored === null) {
                        sStored = aCzechiaDependents[iI].getAttribute("data-czechia-stored");
                    }
                    aCzechiaDependents[iI].checked = sStored == "1";
                    aCzechiaDependents[iI].removeAttribute("data-czechia-disabled");
                    aCzechiaDependents[iI].removeAttribute("data-czechia-disabled-checked");
                }
            } else {
                if (aCzechiaDependents[iI].getAttribute("data-czechia-disabled") != "1") {
                    sStored = aCzechiaDependents[iI].disabled && aCzechiaDependents[iI].getAttribute("data-czechia-stored") !== null ? aCzechiaDependents[iI].getAttribute("data-czechia-stored") : (aCzechiaDependents[iI].checked ? "1" : "0");
                    aCzechiaDependents[iI].setAttribute("data-czechia-disabled-checked", sStored);
                    aCzechiaDependents[iI].setAttribute("data-czechia-disabled", "1");
                }
                aCzechiaDependents[iI].checked = false;
                aCzechiaDependents[iI].disabled = true;
            }
        }
    }

    function openDialog() {
        if (!oDialog) {
            return;
        }
        rememberCheckboxStates();
        updateCzechiaCountryOptions();
        oDialog.hidden = false;
        lockAdminModalScroll();

        document.addEventListener("keydown", closeOnEscape);
        focusAdminElement(oFirstInput, true);
    }

    function closeDialog() {
        if (!oDialog || oDialog.hidden) {
            return;
        }
        document.removeEventListener("keydown", closeOnEscape);
        restoreCheckboxStates();
        oDialog.hidden = true;
        unlockAdminModalScroll();
        focusAdminElement(oOpen);
    }

    if (!oOpen || !oDialog) {
        return;
    }
    if (oBox && oHeader) {
        enableAdminDialogDrag(oDialog, oBox, oHeader);
    }
    updateCzechiaCountryOptions();
    oOpen.addEventListener("click", function () {
        openDialog();
    });
    if (oCzechiaCountry) {
        oCzechiaCountry.addEventListener("change", function () {
            updateCzechiaCountryOptions();
        })
    }
    if (oClose) {
        oClose.addEventListener("click", function () {
            closeDialog();
        })
    }
    if (oCancel) {
        oCancel.addEventListener("click", function () {
            closeDialog();
        })
    }
});

document.addEventListener("DOMContentLoaded", function () {
    var oOpen = document.querySelector(".js-complex-filter-open");
    var oDialog = document.getElementById("complex-filter-dialog");
    var oForm = oDialog ? oDialog.querySelector(".complex-filter-form") : null;
    var oBox = oDialog ? oDialog.querySelector(".confirm-dialog-box") : null;
    var oHeader = oDialog ? oDialog.querySelector(".confirm-dialog-header") : null;
    var oClose = oDialog ? oDialog.querySelector(".js-complex-filter-close") : null;
    var oCancel = oDialog ? oDialog.querySelector(".js-complex-filter-cancel") : null;
    var oAdd = oDialog ? oDialog.querySelector(".js-complex-filter-add") : null;
    var oReset = oDialog ? oDialog.querySelector(".js-complex-filter-modal-reset") : null;
    var oRows = oDialog ? oDialog.querySelector(".js-complex-filter-rows") : null;
    var iDraftTimer = 0;
    var oRowTemplate = null;
    var closeOnEscape = function (oEvent) {
        if (oEvent.key == "Escape") {
            closeDialog();
        }
    };

    function getMatchValue() {
        var oChecked = oForm ? oForm.querySelector("input[name=\"complex_filter_match\"]:checked") : null;
        return oChecked ? oChecked.value : "all";
    }

    function setMatchValue(sValue) {
        var aInputs = oForm ? oForm.querySelectorAll("input[name=\"complex_filter_match\"]") : [];
        for (var iI = 0; iI < aInputs.length; iI += 1) {
            aInputs[iI].checked = aInputs[iI].value == sValue;
        }
    }


    function getRows() {
        return oRows ? oRows.querySelectorAll(".js-complex-filter-row") : [];
    }

    function ensureRowTemplate() {
        var aRows = getRows();
        if (!oRowTemplate && aRows.length > 0) {
            oRowTemplate = aRows[0].cloneNode(true);
        }
        return oRowTemplate;
    }

    function getRowValueType(oRow) {
        var oField = oRow ? oRow.querySelector(".js-complex-filter-field") : null;
        var oOption = oField ? oField.options[oField.selectedIndex] : null;
        return oOption ? (oOption.getAttribute("data-value-type") || "text") : "text";
    }

    function getRowOperatorValue(oRow) {
        var oOperator = oRow ? oRow.querySelector(".js-complex-filter-operator") : null;
        if (getRowValueType(oRow) == "boolean") {
            return "equals";
        }
        return oOperator ? oOperator.value : "";
    }

    function getComplexFilterGroupOptions() {
        var sData = oRows ? (oRows.getAttribute("data-group-options") || "[]") : "[]";
        var aOptions = [];
        try {
            aOptions = JSON.parse(sData);
        } catch (oException) {
            logAdminException(oException);
            aOptions = [];
        }
        return aOptions && aOptions.length ? aOptions : [];
    }

    function getComplexFilterAddressTypeOptions() {
        var sData = oRows ? (oRows.getAttribute("data-address-type-options") || "[]") : "[]";
        var aOptions = [];
        try {
            aOptions = JSON.parse(sData);
        } catch (oException) {
            logAdminException(oException);
            aOptions = [];
        }
        return aOptions && aOptions.length ? aOptions : [];
    }

    function getComplexFilterSubjectTypeOptions() {
        var sData = oRows ? (oRows.getAttribute("data-subject-type-options") || "[]") : "[]";
        var aOptions = [];
        try {
            aOptions = JSON.parse(sData);
        } catch (oException) {
            logAdminException(oException);
            aOptions = [];
        }
        return aOptions && aOptions.length ? aOptions : [];
    }

    function isComplexFilterSelectValueType(sValueType) {
        return sValueType == "boolean" || sValueType == "group" || sValueType == "subject_type" || sValueType == "address_type";
    }

    function isComplexFilterOperatorAllowed(sValueType, sOperator) {
        if (sOperator == "") {
            return true;
        }
        if (sValueType == "group" || sValueType == "subject_type" || sValueType == "address_type") {
            return sOperator == "equals" || sOperator == "not_equals" || sOperator == "contains" || sOperator == "not_contains" || sOperator == "empty" || sOperator == "not_empty";
        }
        if (sValueType == "country") {
            return sOperator == "equals" || sOperator == "not_equals" || sOperator == "contains" || sOperator == "not_contains" || sOperator == "empty" || sOperator == "not_empty";
        }
        return true;
    }

    function getComplexFilterDefaultOperator(sValueType) {
        if (sValueType == "boolean" || sValueType == "country") {
            return "equals";
        }
        return "contains";
    }

    function updateComplexFilterOperatorOptions(oOperator, sValueType) {
        var oOption;
        if (!oOperator) {
            return;
        }
        for (var iI = 0; iI < oOperator.options.length; iI += 1) {
            oOption = oOperator.options[iI];
            if (isComplexFilterOperatorAllowed(sValueType, oOption.value)) {
                oOption.hidden = false;
                oOption.disabled = false;
            } else {
                oOption.hidden = true;
                oOption.disabled = true;
            }
        }
        if (!isComplexFilterOperatorAllowed(sValueType, oOperator.value)) {
            oOperator.value = getComplexFilterDefaultOperator(sValueType);
        }
    }

    function normalizeComplexFilterBooleanValue(sValue) {
        var sText = sValue === null || typeof sValue == "undefined" ? "" : String(sValue).toLowerCase();
        if (sText == "0" || sText == "false" || sText == "no" || sText == "off") {
            return "0";
        }
        return "1";
    }

    function normalizeComplexFilterInputValue(sValue, sValueType) {
        var sText = sValue === null || typeof sValue == "undefined" ? "" : String(sValue);
        if (sValueType == "date" && sText.length >= 10) {
            return sText.substring(0, 10);
        }
        if (sValueType == "datetime" && sText.length >= 16) {
            return sText.replace(" ", "T").substring(0, 16);
        }
        return sText;
    }

    function updateComplexFilterInputType(oInput, sValueType) {
        if (!oInput) {
            return;
        }
        if (sValueType == "date") {
            oInput.type = "date";
        } else if (sValueType == "datetime") {
            oInput.type = "datetime-local";
        } else if (sValueType == "number") {
            oInput.type = "number";
        } else {
            oInput.type = "text";
        }
        if (sValueType == "country") {
            oInput.setAttribute("list", "nx-country-list");
            oInput.spellcheck = false;
        } else {
            oInput.removeAttribute("list");
            oInput.spellcheck = true;
        }
    }

    function bindComplexFilterValue(oValue) {
        if (!oValue || oValue.getAttribute("data-complex-filter-value-bound") == "1") {
            return;
        }
        oValue.setAttribute("data-complex-filter-value-bound", "1");
        oValue.addEventListener("input", function () {
            scheduleDraftSave();
        });
        oValue.addEventListener("change", function () {
            scheduleDraftSave();
        })
    }

    function createComplexFilterBooleanSelect(sValue) {
        var oSelect = document.createElement("select");
        var oYes = document.createElement("option");
        var oNo = document.createElement("option");
        oSelect.name = "complex_filter_value[]";
        oSelect.className = "js-complex-filter-value";
        oSelect.setAttribute("data-value-type", "boolean");
        oYes.value = "1";
        oYes.text = "Yes";
        oNo.value = "0";
        oNo.text = "No";
        oSelect.appendChild(oYes);
        oSelect.appendChild(oNo);
        oSelect.value = normalizeComplexFilterBooleanValue(sValue);
        return oSelect;
    }

    function createComplexFilterGroupSelect(sValue) {
        var oSelect = document.createElement("select");
        var oEmpty = document.createElement("option");
        var aOptions = getComplexFilterGroupOptions();
        var oOption;
        oSelect.name = "complex_filter_value[]";
        oSelect.className = "js-complex-filter-value";
        oSelect.setAttribute("data-value-type", "group");
        oEmpty.value = "";
        oEmpty.text = "";
        oSelect.appendChild(oEmpty);
        for (var iI = 0; iI < aOptions.length; iI += 1) {
            oOption = document.createElement("option");
            oOption.value = aOptions[iI];
            oOption.text = aOptions[iI];
            oSelect.appendChild(oOption);
        }
        oSelect.value = sValue || "";
        return oSelect;
    }

    function createComplexFilterAddressTypeSelect(sValue) {
        var oSelect = document.createElement("select");
        var oEmpty = document.createElement("option");
        var aOptions = getComplexFilterAddressTypeOptions();
        var oOption;
        var sCurrentValue = (sValue || "").toLowerCase();
        oSelect.name = "complex_filter_value[]";
        oSelect.className = "js-complex-filter-value";
        oSelect.setAttribute("data-value-type", "address_type");
        oEmpty.value = "";
        oEmpty.text = "";
        oSelect.appendChild(oEmpty);
        for (var iI = 0; iI < aOptions.length; iI += 1) {
            oOption = document.createElement("option");
            oOption.value = aOptions[iI].value;
            oOption.text = aOptions[iI].label;
            oSelect.appendChild(oOption);
            if (sCurrentValue == String(aOptions[iI].value).toLowerCase() || sCurrentValue == String(aOptions[iI].label).toLowerCase()) {
                oSelect.value = aOptions[iI].value;
            }
        }
        if (!oSelect.value) {
            oSelect.value = sValue || "";
        }
        return oSelect;
    }

    function createComplexFilterSubjectTypeSelect(sValue) {
        var oSelect = document.createElement("select");
        var oEmpty = document.createElement("option");
        var aOptions = getComplexFilterSubjectTypeOptions();
        var oOption;
        var sCurrentValue = (sValue || "").toLowerCase();
        oSelect.name = "complex_filter_value[]";
        oSelect.className = "js-complex-filter-value";
        oSelect.setAttribute("data-value-type", "subject_type");
        oEmpty.value = "";
        oEmpty.text = "";
        oSelect.appendChild(oEmpty);
        for (var iI = 0; iI < aOptions.length; iI += 1) {
            oOption = document.createElement("option");
            oOption.value = aOptions[iI].value;
            oOption.text = aOptions[iI].label;
            oSelect.appendChild(oOption);
            if (sCurrentValue == String(aOptions[iI].value).toLowerCase() || sCurrentValue == String(aOptions[iI].label).toLowerCase()) {
                oSelect.value = aOptions[iI].value;
            }
        }
        if (!oSelect.value) {
            oSelect.value = sValue || "";
        }
        return oSelect;
    }

    function createComplexFilterTextInput(sValue, sValueType) {
        var oInput = document.createElement("input");
        oInput.name = "complex_filter_value[]";
        oInput.className = "js-complex-filter-value";
        oInput.autocomplete = "off";
        oInput.setAttribute("data-value-type", sValueType);
        updateComplexFilterInputType(oInput, sValueType);
        oInput.value = normalizeComplexFilterInputValue(sValue, sValueType);
        return oInput;
    }

    function ensureComplexFilterValueControl(oRow) {
        var sValueType = getRowValueType(oRow);
        var oValue = oRow ? oRow.querySelector(".js-complex-filter-value") : null;
        var oNewValue;
        var sCurrentValue;
        var sTagName;
        if (!oRow || !oValue) {
            return oValue;
        }
        sCurrentValue = oValue.value;
        sTagName = oValue.tagName ? oValue.tagName.toLowerCase() : "";
        if (sValueType == "boolean" && (sTagName != "select" || oValue.getAttribute("data-value-type") != "boolean")) {
            oNewValue = createComplexFilterBooleanSelect(sCurrentValue);
            oValue.parentNode.replaceChild(oNewValue, oValue);
            oValue = oNewValue;
        } else if (sValueType == "group" && (sTagName != "select" || oValue.getAttribute("data-value-type") != "group")) {
            oNewValue = createComplexFilterGroupSelect(sCurrentValue);
            oValue.parentNode.replaceChild(oNewValue, oValue);
            oValue = oNewValue;
        } else if (sValueType == "subject_type" && (sTagName != "select" || oValue.getAttribute("data-value-type") != "subject_type")) {
            oNewValue = createComplexFilterSubjectTypeSelect(sCurrentValue);
            oValue.parentNode.replaceChild(oNewValue, oValue);
            oValue = oNewValue;
        } else if (sValueType == "address_type" && (sTagName != "select" || oValue.getAttribute("data-value-type") != "address_type")) {
            oNewValue = createComplexFilterAddressTypeSelect(sCurrentValue);
            oValue.parentNode.replaceChild(oNewValue, oValue);
            oValue = oNewValue;
        } else if (!isComplexFilterSelectValueType(sValueType) && sTagName == "select") {
            oNewValue = createComplexFilterTextInput(sCurrentValue, sValueType);
            oValue.parentNode.replaceChild(oNewValue, oValue);
            oValue = oNewValue;
        } else if (sTagName == "input") {
            oValue.setAttribute("data-value-type", sValueType);
            updateComplexFilterInputType(oValue, sValueType);
            oValue.value = normalizeComplexFilterInputValue(oValue.value, sValueType);
        }
        bindComplexFilterValue(oValue);
        return oValue;
    }

    function buildDraftData() {
        var oData = new FormData();
        var aRows = getRows();
        var oField;
        var oValue;
        oData.append("action", "save_full_list_complex_filter_draft");
        appendAdminCsrfToken(oData);
        oData.append("complex_filter_match", getMatchValue());
        for (var iI = 0; iI < aRows.length; iI += 1) {
            oField = aRows[iI].querySelector(".js-complex-filter-field");
            oValue = aRows[iI].querySelector(".js-complex-filter-value");
            oData.append("complex_filter_field[]", oField ? oField.value : "");
            oData.append("complex_filter_operator[]", getRowOperatorValue(aRows[iI]));
            appendAdminEncodedValue(oData, "complex_filter_value[]", oValue ? oValue.value : "");
        }
        return oData;
    }

    function removeApplyEncodedValues() {
        var aInputs = oForm ? oForm.querySelectorAll(".js-complex-filter-apply-hidden") : [];
        for (var iI = 0; iI < aInputs.length; iI += 1) {
            if (aInputs[iI].parentNode) {
                aInputs[iI].parentNode.removeChild(aInputs[iI]);
            }
        }
    }

    function restoreApplyValueFields(aValues, aOperators) {
        for (var iI = 0; iI < aValues.length; iI += 1) {
            if (aValues[iI]) {
                aValues[iI].disabled = false;
            }
        }
        for (var iJ = 0; iJ < aOperators.length; iJ += 1) {
            if (aOperators[iJ]) {
                aOperators[iJ].disabled = aOperators[iJ].hidden;
            }
        }
        removeApplyEncodedValues();
    }

    function prepareApplySubmit() {
        var aRows = getRows();
        var aValues = [];
        var aOperators = [];
        var aEncodedValues = [];
        var aOperatorValues = [];
        var oOperator;
        var oValue;
        var oHidden;
        removeApplyEncodedValues();
        try {
            for (var iI = 0; iI < aRows.length; iI += 1) {
                oOperator = aRows[iI].querySelector(".js-complex-filter-operator");
                oValue = aRows[iI].querySelector(".js-complex-filter-value");
                aOperators.push(oOperator);
                aValues.push(oValue);
                aOperatorValues.push(getRowOperatorValue(aRows[iI]));
                aEncodedValues.push(encodeAdminPostValue(oValue ? oValue.value : ""));
            }
        } catch (oException) {
            logAdminException(oException);
            return;
        }
        for (var iK = 0; iK < aOperatorValues.length; iK += 1) {
            oHidden = document.createElement("input");
            oHidden.type = "hidden";
            oHidden.name = "complex_filter_operator[]";
            oHidden.value = aOperatorValues[iK];
            oHidden.className = "js-complex-filter-apply-hidden";
            oForm.appendChild(oHidden);
            if (aOperators[iK]) {
                aOperators[iK].disabled = true;
            }
        }
        for (var iJ = 0; iJ < aEncodedValues.length; iJ += 1) {
            oHidden = document.createElement("input");
            oHidden.type = "hidden";
            oHidden.name = "complex_filter_value_b64[]";
            oHidden.value = aEncodedValues[iJ];
            oHidden.className = "js-complex-filter-apply-hidden";
            oForm.appendChild(oHidden);
            if (aValues[iJ]) {
                aValues[iJ].disabled = true;
            }
        }
        window.setTimeout(function () {
            restoreApplyValueFields(aValues, aOperators);
        }, 0)
    }

    function saveDraftNow() {
        if (iDraftTimer) {
            window.clearTimeout(iDraftTimer);
            iDraftTimer = 0;
        }
        if (!window.fetch || !window.FormData) {
            return;
        }
        fetch(window.location.href, {
            "method": "POST",
            "body": buildDraftData(),
            "credentials": "same-origin",
            "headers": getAdminAjaxHeaders()
        }).catch(function (oException) {
            logAdminException(oException);
        });
    }

    function scheduleDraftSave() {
        if (!window.fetch || !window.FormData) {
            return;
        }
        if (iDraftTimer) {
            window.clearTimeout(iDraftTimer);
        }
        iDraftTimer = window.setTimeout(function () {
            saveDraftNow();
        }, 300)
    }

    function updateRowValueState(oRow) {
        var oOperator = oRow ? oRow.querySelector(".js-complex-filter-operator") : null;
        var sValueType = getRowValueType(oRow);
        var oValue = ensureComplexFilterValueControl(oRow);
        var oOption;
        var blNeedsValue = true;
        if (!oOperator || !oValue) {
            return;
        }
        updateComplexFilterOperatorOptions(oOperator, sValueType);
        if (sValueType == "boolean") {
            oOperator.value = "equals";
            oOperator.disabled = true;
            oOperator.setAttribute("aria-hidden", "true");
            oOperator.tabIndex = -1;
        } else {
            oOperator.disabled = false;
            oOperator.removeAttribute("aria-hidden");
            oOperator.removeAttribute("tabindex");
        }
        oOption = oOperator.options[oOperator.selectedIndex];
        if (oOption && oOption.getAttribute("data-needs-value") == "0") {
            blNeedsValue = false;
        }
        if (blNeedsValue) {
            if (sValueType == "boolean" && oValue.tagName && oValue.tagName.toLowerCase() == "select" && oValue.value != "0" && oValue.value != "1") {
                oValue.value = "1";
            }
            oValue.disabled = false;
            if (oValue.tagName && oValue.tagName.toLowerCase() == "input") {
                oValue.readOnly = false;
            }
        } else {
            oValue.value = "";
            oValue.disabled = true;
            if (oValue.tagName && oValue.tagName.toLowerCase() == "input") {
                oValue.readOnly = false;
            }
        }
    }

    function setRowBlank(oRow) {
        var oField = oRow ? oRow.querySelector(".js-complex-filter-field") : null;
        var oOperator = oRow ? oRow.querySelector(".js-complex-filter-operator") : null;
        var oValue = oRow ? oRow.querySelector(".js-complex-filter-value") : null;
        if (oField) {
            oField.value = "subject_name";
        }
        if (oOperator) {
            oOperator.value = "contains";
        }
        if (oValue) {
            oValue.removeAttribute("data-complex-filter-value-bound");
            oValue.value = "";
            oValue.readOnly = false;
            oValue.disabled = false;
        }
        updateRowValueState(oRow);
    }

    function setRowReset(oRow) {
        var oField = oRow ? oRow.querySelector(".js-complex-filter-field") : null;
        var oOperator = oRow ? oRow.querySelector(".js-complex-filter-operator") : null;
        var oValue = oRow ? oRow.querySelector(".js-complex-filter-value") : null;
        if (oField) {
            oField.value = "";
        }
        if (oOperator) {
            oOperator.value = "";
            oOperator.disabled = false;
            oOperator.removeAttribute("aria-hidden");
            oOperator.removeAttribute("tabindex");
        }
        if (oValue) {
            oValue.removeAttribute("data-complex-filter-value-bound");
            oValue.value = "";
            oValue.readOnly = false;
            oValue.disabled = false;
        }
        updateRowValueState(oRow);
    }

    function bindRow(oRow) {
        var oField = oRow ? oRow.querySelector(".js-complex-filter-field") : null;
        var oOperator = oRow ? oRow.querySelector(".js-complex-filter-operator") : null;
        var oValue = oRow ? oRow.querySelector(".js-complex-filter-value") : null;
        var oRemove = oRow ? oRow.querySelector(".js-complex-filter-remove") : null;
        if (!oRow || oRow.getAttribute("data-complex-filter-bound") == "1") {
            return;
        }
        oRow.setAttribute("data-complex-filter-bound", "1");
        if (oField) {
            oField.addEventListener("change", function () {
                updateRowValueState(oRow);
                scheduleDraftSave();
            })
        }
        if (oOperator) {
            oOperator.addEventListener("change", function () {
                updateRowValueState(oRow);
                scheduleDraftSave();
            })
        }
        bindComplexFilterValue(oValue);
        if (oRemove) {
            oRemove.addEventListener("click", function () {
                var aRows = getRows();
                if (aRows.length > 1) {
                    oRow.parentNode.removeChild(oRow);
                } else {
                    setRowReset(oRow);
                }
                scheduleDraftSave();
            })
        }
        updateRowValueState(oRow);
    }

    function bindRows() {
        var aRows = getRows();
        for (var iI = 0; iI < aRows.length; iI += 1) {
            bindRow(aRows[iI]);
        }
    }

    function createBlankRow() {
        var aRows = getRows();
        var oTemplate = aRows.length > 0 ? aRows[0] : ensureRowTemplate();
        var oRow = oTemplate ? oTemplate.cloneNode(true) : null;
        if (!oRow) {
            return null;
        }
        oRow.removeAttribute("data-complex-filter-bound");
        setRowBlank(oRow);
        return oRow;
    }

    function addBlankRow() {
        var oRow = createBlankRow();
        if (oRow && oRows) {
            oRows.appendChild(oRow);
            bindRow(oRow);
            scheduleDraftSave();
            focusAdminElement(oRow.querySelector(".js-complex-filter-field"));
        }
    }

    function resetDialogRows() {
        var oTemplate;
        var oRow;
        if (!oRows) {
            return;
        }
        oTemplate = ensureRowTemplate();
        oRows.innerHTML = "";
        oRow = oTemplate ? oTemplate.cloneNode(true) : null;
        if (!oRow) {
            return;
        }
        oRow.removeAttribute("data-complex-filter-bound");
        setRowReset(oRow);
        oRows.appendChild(oRow);
        bindRow(oRow);
    }

    function openDialog() {
        if (!oDialog) {
            return;
        }
        oDialog.hidden = false;
        lockAdminModalScroll();
        bindRows();

        document.addEventListener("keydown", closeOnEscape);
        focusAdminElement(oDialog.querySelector(".js-complex-filter-field"), true);
    }

    function closeDialog() {
        if (!oDialog || oDialog.hidden) {
            return;
        }

        document.removeEventListener("keydown", closeOnEscape);
        saveDraftNow();
        oDialog.hidden = true;
        unlockAdminModalScroll();
        focusAdminElement(oOpen);
    }

    if (!oOpen || !oDialog || !oForm || !oRows) {
        return;
    }
    if (oBox && oHeader) {
        enableAdminDialogDrag(oDialog, oBox, oHeader);
    }
    bindRows();
    ensureRowTemplate();
    var aMatchInputs = oForm.querySelectorAll("input[name=\"complex_filter_match\"]");
    for (var iI = 0; iI < aMatchInputs.length; iI += 1) {
        aMatchInputs[iI].addEventListener("change", function () {
            scheduleDraftSave();
        })
    }
    oOpen.addEventListener("click", function () {
        openDialog();
    });
    if (oClose) {
        oClose.addEventListener("click", function () {
            closeDialog();
        })
    }
    if (oCancel) {
        oCancel.addEventListener("click", function () {
            closeDialog();
        })
    }
    if (oAdd) {
        oAdd.addEventListener("click", function () {
            addBlankRow();
        })
    }
    if (oReset) {
        oReset.addEventListener("click", function () {
            setMatchValue("all");
            resetDialogRows();
            saveDraftNow();
            focusAdminElement(oDialog.querySelector(".js-complex-filter-field"));
        })
    }
    oForm.addEventListener("submit", function () {
        prepareApplySubmit();
    })
});

document.addEventListener("DOMContentLoaded", function () {
    var oButton = document.querySelector(".js-filter-focus");
    var oFilter;
    var iScrollLeft;
    if (!oButton) {
        return;
    }
    oFilter = document.getElementById(oButton.getAttribute("data-filter-input") || "");
    if (!oFilter) {
        return;
    }
    oButton.addEventListener("click", function () {
        iScrollLeft = 0;
        window.scrollTo(iScrollLeft, 0);
        window.setTimeout(function () {
            focusAdminElement(oFilter, true);
            window.scrollTo(iScrollLeft, 0);
        }, 0)
    })
});

document.addEventListener("DOMContentLoaded", function () {
    var oTable = document.getElementById("nx-birthdays-table");
    if (!oTable || !window.fetch || !window.FormData) {
        return;
    }
    oTable.addEventListener("click", function (oEvent) {
        var oButton = oEvent.target.closest ? oEvent.target.closest(".js-birthday-served") : null;
        var oRow;
        var oData;
        if (!oButton) {
            return;
        }
        oEvent.preventDefault();
        if (oButton.disabled) {
            return;
        }
        oRow = oButton.closest ? oButton.closest("tr[data-subject-id]") : null;
        oData = new FormData();
        oButton.disabled = true;
        oData.append("action", "mark_birthday_served");
        oData.append("subject_id", oButton.getAttribute("data-subject-id") || (oRow ? oRow.getAttribute("data-subject-id") : ""));
        appendAdminCsrfToken(oData);
        fetch(window.location.href, {
            "method": "POST",
            "body": oData,
            "credentials": "same-origin",
            "headers": getAdminAjaxHeaders()
        }).then(function (oResponse) {
            return oResponse.json();
        }).then(function (aData) {
            if (!aData || !aData.success) {
                oButton.disabled = false;
                window.alert(aData && aData.message ? aData.message : "Birthday could not be marked served.");
                return;
            }
            if (oRow && oRow.parentNode) {
                oRow.parentNode.removeChild(oRow);
            }
        }).catch(function (oException) {
            logAdminException(oException);
            oButton.disabled = false;
            window.alert("Birthday could not be marked served.");
        })
    })
});

document.addEventListener("DOMContentLoaded", function () {
    var aMenus = document.querySelectorAll("[data-ex-menu]");

    function closeExMenu(oMenu) {
        var oButton = oMenu ? oMenu.querySelector("[data-ex-menu-button]") : null;
        var oPanel = oMenu ? oMenu.querySelector("[data-ex-menu-panel]") : null;
        if (oPanel) {
            oPanel.hidden = true;
        }
        if (oButton) {
            oButton.setAttribute("aria-expanded", "false");
        }
    }

    function closeExMenus(oExcept) {
        for (var iI = 0; iI < aMenus.length; iI += 1) {
            if (aMenus[iI] !== oExcept) {
                closeExMenu(aMenus[iI]);
            }
        }
    }

    function openExMenu(oMenu) {
        var oButton = oMenu ? oMenu.querySelector("[data-ex-menu-button]") : null;
        var oPanel = oMenu ? oMenu.querySelector("[data-ex-menu-panel]") : null;
        if (!oButton || !oPanel) {
            return;
        }
        closeExMenus(oMenu);
        oPanel.hidden = false;
        oButton.setAttribute("aria-expanded", "true");
    }

    if (aMenus.length === 0) {
        return;
    }
    for (var iI = 0; iI < aMenus.length; iI += 1) {
        (function (oMenu) {
            var oButton = oMenu.querySelector("[data-ex-menu-button]");
            var oPanel = oMenu.querySelector("[data-ex-menu-panel]");
            if (!oButton || !oPanel) {
                return;
            }
            oButton.addEventListener("click", function (oEvent) {
                oEvent.preventDefault();
                oEvent.stopPropagation();
                if (oPanel.hidden) {
                    openExMenu(oMenu);
                } else {
                    closeExMenu(oMenu);
                }
            })
        })(aMenus[iI]);
    }

    document.addEventListener("click", function (oEvent) {
        var oMenu = oEvent.target.closest ? oEvent.target.closest("[data-ex-menu]") : null;
        if (!oMenu) {
            closeExMenus(null);
        }
    });

    document.addEventListener("keydown", function (oEvent) {
        if (oEvent.key == "Escape") {
            closeExMenus(null);
        }
    });
});

document.addEventListener("DOMContentLoaded", function () {
    var aButtons = document.querySelectorAll(".js-copy-link");


    function showCopyResult(oButton, blSuccess) {
        var sText = oButton.getAttribute("data-copy-text") || oButton.textContent;
        oButton.textContent = blSuccess ? "Copied" : "Copy failed";
        window.setTimeout(function () {
            oButton.textContent = sText;
        }, 1500)
    }

    function copyLink(oButton) {
        var sLink = oButton.getAttribute("data-copy-link") || "";
        if (!sLink) {
            return;
        }
        oButton.setAttribute("data-copy-text", oButton.getAttribute("data-copy-text") || oButton.textContent);
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(sLink).then(function () {
                showCopyResult(oButton, true);
            }).catch(function (oException) {
                logAdminException(oException);
                showCopyResult(oButton, copyAdminTextWithTextarea(sLink));
            });
            return;
        }
        showCopyResult(oButton, copyAdminTextWithTextarea(sLink));
    }

    for (var iI = 0; iI < aButtons.length; iI += 1) {
        aButtons[iI].addEventListener("click", function () {
            copyLink(this);
        })
    }
});

document.addEventListener("click", function (oEvent) {
    var oButton = oEvent.target.closest ? oEvent.target.closest(".nx-copy-action") : null;
    var sValue;
    if (!oButton) {
        return;
    }
    oEvent.preventDefault();
    oEvent.stopPropagation();
    sValue = oButton.getAttribute("data-copy-value") || "";


    function showCopyValueResult(blSuccess) {
        var oBox = oButton.querySelector ? oButton.querySelector(".nx-copy-action-box") : null;
        var sText = oButton.getAttribute("data-copy-text") || (oBox ? oBox.textContent : oButton.textContent);
        var sResultText = blSuccess ? getAdminEmoji("copy-success") : getAdminEmoji("copy-failure");
        oButton.setAttribute("data-copy-text", sText);
        if (oBox) {
            oBox.textContent = sResultText;
        } else {
            oButton.textContent = sResultText;
        }
        window.setTimeout(function () {
            if (oBox) {
                oBox.textContent = sText;
            } else {
                oButton.textContent = sText;
            }
        }, 1000)
    }

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(sValue).then(function () {
            showCopyValueResult(true);
        }).catch(function (oException) {
            logAdminException(oException);
            showCopyValueResult(copyAdminTextWithTextarea(sValue));
        });
        return;
    }
    showCopyValueResult(copyAdminTextWithTextarea(sValue));
}, true);

document.addEventListener("DOMContentLoaded", function () {
    var sHoverColor = "#fff3cd";
    var sSelectedColor = "#cfe2ff";

    function getCurrentRowColor(oRow) {
        if (oRow.getAttribute("data-saved") == "1") {
            return "#dff0d8";
        }
        if (oRow.getAttribute("data-confirming") == "1") {
            return "#cfe2ff";
        }
        if (oRow.getAttribute("data-selected") == "1") {
            return sSelectedColor;
        }
        if (oRow.getAttribute("data-hover") == "1") {
            return sHoverColor;
        }
        return "";
    }

    function applyRowColor(oRow) {
        var sColor = getCurrentRowColor(oRow);
        var aCells = oRow.cells || oRow.querySelectorAll("td");
        var iI;
        oRow.style.backgroundColor = sColor;
        for (iI = 0; iI < aCells.length; iI += 1) {
            aCells[iI].style.backgroundColor = sColor;
        }
    }

    function isTableRowActionTarget(oTarget) {
        if (oTarget && oTarget.nodeType == 3) {
            oTarget = oTarget.parentNode;
        }
        return oTarget && oTarget.closest && oTarget.closest("a, button, input, select, textarea, label");
    }

    function copyTableRowState(oSourceRow, oTargetRow) {
        if (!oSourceRow || !oTargetRow) {
            return;
        }
        if ((" " + oSourceRow.className + " ").indexOf(" nx-admin-row-modal ") !== -1) {
            addAdminClass(oTargetRow, "nx-admin-row-modal");
        }
        if (oSourceRow.getAttribute("data-selected") == "1") {
            oTargetRow.setAttribute("data-selected", "1");
        }
        if (oSourceRow.getAttribute("data-hover") == "1") {
            oTargetRow.setAttribute("data-hover", "1");
        }
    }

    function getEventTableRow(oEvent) {
        var oTarget = oEvent ? oEvent.target : null;
        if (oTarget && oTarget.nodeType == 3) {
            oTarget = oTarget.parentNode;
        }
        return oTarget && oTarget.closest ? oTarget.closest("table tbody tr") : null;
    }

    function isInsideTableRow(oRow, oTarget) {
        if (!oRow || !oTarget) {
            return false;
        }
        if (oTarget.nodeType == 3) {
            oTarget = oTarget.parentNode;
        }
        return oTarget && oTarget.closest && oTarget.closest("table tbody tr") == oRow;
    }

    function bindTableRow(oRow) {
        if (!oRow) {
            return;
        }
        applyRowColor(oRow);
    }

    document.addEventListener("mouseover", function (oEvent) {
        var oRow = getEventTableRow(oEvent);
        if (!oRow || isInsideTableRow(oRow, oEvent.relatedTarget)) {
            return;
        }
        oRow.setAttribute("data-hover", "1");
        applyRowColor(oRow);
    });

    document.addEventListener("mouseout", function (oEvent) {
        var oRow = getEventTableRow(oEvent);
        if (!oRow || isInsideTableRow(oRow, oEvent.relatedTarget)) {
            return;
        }
        oRow.setAttribute("data-hover", "0");
        applyRowColor(oRow);
    });

    document.addEventListener("click", function (oEvent) {
        var oRow = getEventTableRow(oEvent);
        if (!oRow || isTableRowActionTarget(oEvent.target)) {
            return;
        }
        oRow.setAttribute("data-selected", oRow.getAttribute("data-selected") == "1" ? "0" : "1");
        applyRowColor(oRow);
    });

    window.nxCopyAdminTableRowState = copyTableRowState;
    window.nxBindAdminTableRow = bindTableRow;
});

document.addEventListener("DOMContentLoaded", function () {
    var oCanvas = document.getElementById("schema-canvas");
    var oSvg = document.getElementById("schema-lines");
    var aRelations = document.querySelectorAll(".schema-relations tbody tr");
    if (!oCanvas || !oSvg) {
        return;
    }

    function getColumnId(sTableName, sColumnName) {
        return "column-" + (sTableName + "-" + sColumnName).replace(/[^a-zA-Z0-9_-]/g, "-");
    }

    function getTableElement(oRow) {
        return oRow.parentNode.parentNode;
    }

    function getRoute(oRelation) {
        var sSourceSide = oRelation.getAttribute("data-source-side");
        if (!sSourceSide) {
            return null;
        }
        return {
            "source": sSourceSide,
            "target": oRelation.getAttribute("data-target-side") || sSourceSide,
            "curve": parseInt(oRelation.getAttribute("data-curve") || "0", 10),
            "sourceXOffset": parseInt(oRelation.getAttribute("data-source-x-offset") || "0", 10),
            "sourceYOffset": parseInt(oRelation.getAttribute("data-source-y-offset") || "0", 10),
            "targetXOffset": parseInt(oRelation.getAttribute("data-target-x-offset") || "0", 10),
            "targetYOffset": parseInt(oRelation.getAttribute("data-target-y-offset") || "0", 10),
            "viaX": parseInt(oRelation.getAttribute("data-via-x") || "", 10),
            "viaXOffset": parseInt(oRelation.getAttribute("data-via-x-offset") || "", 10),
            "viaY": parseInt(oRelation.getAttribute("data-via-y") || "", 10),
            "viaYOffset": parseInt(oRelation.getAttribute("data-via-y-offset") || "", 10),
            "viaTableBottomOffset": parseInt(oRelation.getAttribute("data-via-table-bottom-offset") || "", 10)
        }
    }

    function getSides(oSourceTableRect, oTargetTableRect, iIndex) {
        var iSourceCenterX = oSourceTableRect.left + oSourceTableRect.width / 2;
        var iTargetCenterX = oTargetTableRect.left + oTargetTableRect.width / 2;
        if (Math.abs(iSourceCenterX - iTargetCenterX) < 24) {
            var sSide = iIndex % 2 === 0 ? "right" : "left";
            return {
                "source": sSide,
                "target": sSide
            };
        }
        return {
            "source": iSourceCenterX < iTargetCenterX ? "right" : "left",
            "target": iSourceCenterX < iTargetCenterX ? "left" : "right"
        }
    }

    function getAnchor(oRowRect, oCanvasRect, sSide, iXOffset, iYOffset) {
        if (sSide == "top") {
            return {
                "x": oRowRect.left + oRowRect.width / 2 - oCanvasRect.left + iXOffset,
                "y": oRowRect.top - oCanvasRect.top + iYOffset
            }
        }
        if (sSide == "bottom") {
            return {
                "x": oRowRect.left + oRowRect.width / 2 - oCanvasRect.left + iXOffset,
                "y": oRowRect.bottom - oCanvasRect.top + iYOffset
            }
        }
        return {
            "x": (sSide == "right" ? oRowRect.right : oRowRect.left) - oCanvasRect.left,
            "y": oRowRect.top + oRowRect.height / 2 - oCanvasRect.top + iYOffset
        }
    }

    function getDirection(sSide) {
        if (sSide == "top") {
            return {
                "x": 0,
                "y": -1
            };
        }
        if (sSide == "bottom") {
            return {
                "x": 0,
                "y": 1
            };
        }
        return {
            "x": sSide == "right" ? 1 : -1,
            "y": 0
        }
    }

    function getCurveSize(oStart, oEnd, aRoute) {
        if (aRoute && !isNaN(aRoute.curve) && aRoute.curve > 0) {
            return aRoute.curve;
        }
        return Math.max(72, Math.abs(oEnd.x - oStart.x) * 0.45);
    }

    function getRoundedPolylinePath(aPoints, iRadius) {
        var sPath = "M " + aPoints[0].x + " " + aPoints[0].y;
        for (var iI = 1; iI < aPoints.length - 1; iI += 1) {
            var oPrevious = aPoints[iI - 1];
            var oPoint = aPoints[iI];
            var oNext = aPoints[iI + 1];
            var iPreviousDistance = Math.sqrt(Math.pow(oPoint.x - oPrevious.x, 2) + Math.pow(oPoint.y - oPrevious.y, 2));
            var iNextDistance = Math.sqrt(Math.pow(oNext.x - oPoint.x, 2) + Math.pow(oNext.y - oPoint.y, 2));
            var iCornerRadius = Math.min(iRadius, iPreviousDistance / 2, iNextDistance / 2);
            if (iCornerRadius <= 0) {
                sPath += " L " + oPoint.x + " " + oPoint.y;
                continue;
            }
            var oBefore = {
                "x": oPoint.x + (oPrevious.x - oPoint.x) * iCornerRadius / iPreviousDistance,
                "y": oPoint.y + (oPrevious.y - oPoint.y) * iCornerRadius / iPreviousDistance
            };
            var oAfter = {
                "x": oPoint.x + (oNext.x - oPoint.x) * iCornerRadius / iNextDistance,
                "y": oPoint.y + (oNext.y - oPoint.y) * iCornerRadius / iNextDistance
            };
            sPath += " L " + oBefore.x + " " + oBefore.y + " Q " + oPoint.x + " " + oPoint.y + " " + oAfter.x + " " + oAfter.y;
        }
        sPath += " L " + aPoints[aPoints.length - 1].x + " " + aPoints[aPoints.length - 1].y;
        return sPath;
    }

    function removeSchemaRelationElements() {
        var aElements = oSvg.querySelectorAll(".schema-relation, .schema-relation-source, .schema-relation-target");
        for (var iI = 0; iI < aElements.length; iI += 1) {
            aElements[iI].parentNode.removeChild(aElements[iI]);
        }
    }

    function drawRelations() {
        var oCanvasRect = oCanvas.getBoundingClientRect();
        var aSchemaTables = oCanvas.querySelectorAll(".schema-table");
        var iTablesBottom = 0;
        var iI;
        removeSchemaRelationElements();
        oSvg.setAttribute("width", oCanvas.scrollWidth);
        oSvg.setAttribute("height", oCanvas.scrollHeight);
        oSvg.setAttribute("viewBox", "0 0 " + oCanvas.scrollWidth + " " + oCanvas.scrollHeight);
        for (iI = 0; iI < aSchemaTables.length; iI += 1) {
            iTablesBottom = Math.max(iTablesBottom, aSchemaTables[iI].getBoundingClientRect().bottom - oCanvasRect.top);
        }
        for (iI = 0; iI < aRelations.length; iI += 1) {
            var oRelation = aRelations[iI];
            var oSource = document.getElementById(getColumnId(oRelation.getAttribute("data-source-table"), oRelation.getAttribute("data-source-column")));
            var oTarget = document.getElementById(getColumnId(oRelation.getAttribute("data-target-table"), oRelation.getAttribute("data-target-column")));
            if (!oSource || !oTarget) {
                continue;
            }
            var oSourceRect = oSource.getBoundingClientRect();
            var oTargetRect = oTarget.getBoundingClientRect();
            var oSourceTableRect = getTableElement(oSource).getBoundingClientRect();
            var oTargetTableRect = getTableElement(oTarget).getBoundingClientRect();
            var aRoute = getRoute(oRelation);
            var aSides;
            if (aRoute) {
                aSides = {
                    "source": aRoute.source,
                    "target": aRoute.target
                };
            } else {
                aSides = getSides(oSourceTableRect, oTargetTableRect, iI);
            }
            var oStart = getAnchor(oSourceRect, oCanvasRect, aSides.source, aRoute && !isNaN(aRoute.sourceXOffset) ? aRoute.sourceXOffset : 0, aRoute && !isNaN(aRoute.sourceYOffset) ? aRoute.sourceYOffset : 0);
            var oEnd = getAnchor(oTargetRect, oCanvasRect, aSides.target, aRoute && !isNaN(aRoute.targetXOffset) ? aRoute.targetXOffset : 0, aRoute && !isNaN(aRoute.targetYOffset) ? aRoute.targetYOffset : 0);
            var iCurve = getCurveSize(oStart, oEnd, aRoute);
            var oSourceDirection = getDirection(aSides.source);
            var oTargetDirection = getDirection(aSides.target);
            var oControl1 = {
                "x": oStart.x + oSourceDirection.x * iCurve,
                "y": oStart.y + oSourceDirection.y * iCurve
            };
            var oControl2 = {
                "x": oEnd.x + oTargetDirection.x * iCurve,
                "y": oEnd.y + oTargetDirection.y * iCurve
            };
            var sPath;
            var iRouteViaX = aRoute && !isNaN(aRoute.viaX) ? aRoute.viaX : NaN;
            var iRouteViaY = aRoute && !isNaN(aRoute.viaY) ? aRoute.viaY : NaN;
            if (aRoute && isNaN(iRouteViaX) && !isNaN(aRoute.viaXOffset)) {
                iRouteViaX = oEnd.x + aRoute.viaXOffset;
            }
            if (aRoute && isNaN(iRouteViaY) && !isNaN(aRoute.viaTableBottomOffset)) {
                iRouteViaY = iTablesBottom + aRoute.viaTableBottomOffset;
            }
            if (aRoute && isNaN(iRouteViaY) && !isNaN(aRoute.viaYOffset)) {
                iRouteViaY = Math.max(oStart.y, oEnd.y, oControl1.y, oControl2.y) + aRoute.viaYOffset;
            }
            if (aRoute && (!isNaN(iRouteViaX) || !isNaN(iRouteViaY))) {
                var aPoints = [];
                aPoints.push(oStart);
                aPoints.push(oControl1);
                if (!isNaN(iRouteViaX) && !isNaN(iRouteViaY)) {
                    aPoints.push({
                        "x": iRouteViaX,
                        "y": oControl1.y
                    });
                    aPoints.push({
                        "x": iRouteViaX,
                        "y": iRouteViaY
                    });
                    aPoints.push({
                        "x": oControl2.x,
                        "y": iRouteViaY
                    });
                } else if (!isNaN(iRouteViaX)) {
                    aPoints.push({
                        "x": iRouteViaX,
                        "y": oControl1.y
                    });
                    aPoints.push({
                        "x": iRouteViaX,
                        "y": oControl2.y
                    });
                } else {
                    aPoints.push({
                        "x": oControl1.x,
                        "y": iRouteViaY
                    });
                    aPoints.push({
                        "x": oControl2.x,
                        "y": iRouteViaY
                    });
                }
                aPoints.push(oControl2);
                aPoints.push(oEnd);
                sPath = getRoundedPolylinePath(aPoints, 18);
            } else {
                sPath = "M " + oStart.x + " " + oStart.y + " C " + oControl1.x + " " + oControl1.y + ", " + oControl2.x + " " + oControl2.y + ", " + oEnd.x + " " + oEnd.y;
            }
            var oPath = document.createElementNS("http://www.w3.org/2000/svg", "path");
            var oCircle = document.createElementNS("http://www.w3.org/2000/svg", "circle");
            var oTargetCircle = document.createElementNS("http://www.w3.org/2000/svg", "circle");
            var oTitle = document.createElementNS("http://www.w3.org/2000/svg", "title");
            oPath.setAttribute("class", "schema-relation");
            oPath.setAttribute("marker-end", "url(#schema-arrow)");
            oPath.setAttribute("d", sPath);
            oTitle.appendChild(document.createTextNode(oRelation.getAttribute("data-source-table") + "." + oRelation.getAttribute("data-source-column") + " -> " + oRelation.getAttribute("data-target-table") + "." + oRelation.getAttribute("data-target-column")));
            oPath.appendChild(oTitle);
            oCircle.setAttribute("class", "schema-relation-source");
            oCircle.setAttribute("cx", oStart.x);
            oCircle.setAttribute("cy", oStart.y);
            oCircle.setAttribute("r", "4");
            oTargetCircle.setAttribute("class", "schema-relation-target");
            oTargetCircle.setAttribute("cx", oEnd.x);
            oTargetCircle.setAttribute("cy", oEnd.y);
            oTargetCircle.setAttribute("r", "3");
            oSvg.appendChild(oPath);
            oSvg.appendChild(oCircle);
            oSvg.appendChild(oTargetCircle);
        }
    }

    window.setTimeout(drawRelations, 0);

    window.addEventListener("load", drawRelations);

    window.addEventListener("resize", function () {
        window.setTimeout(drawRelations, 0);
    })
});

document.addEventListener("DOMContentLoaded", function () {
    var aFilters = document.querySelectorAll(".js-table-filter");

    function buildFilterExpression(sFilter) {
        var aOrParts = sFilter.trim().split(/\s+OR\s+/i);
        var aExpression = [];
        for (var iI = 0; iI < aOrParts.length; iI += 1) {
            var aAndParts = aOrParts[iI].trim().split(/\s+AND\s+/i);
            var aTerms = [];
            for (var iJ = 0; iJ < aAndParts.length; iJ += 1) {
                var sTerm = aAndParts[iJ].trim();
                var blNegated = false;
                if (sTerm.charAt(0) == "-" && sTerm.substring(1).trim() !== "") {
                    blNegated = true;
                    sTerm = sTerm.substring(1).trim();
                }
                if (sTerm !== "") {
                    aTerms.push({
                        "regex": new RegExp(sTerm.replace(/[.*+?^${}()|[\]\\]/g, "\\$&").replace(/\s+/g, "\\s+"), "i"),
                        "negated": blNegated
                    })
                }
            }
            if (aTerms.length > 0) {
                aExpression.push(aTerms);
            }
        }
        return aExpression;
    }

    function rowMatchesFilterExpression(sRowText, aExpression) {
        if (aExpression.length === 0) {
            return true;
        }
        for (var iI = 0; iI < aExpression.length; iI += 1) {
            var blMatches = true;
            for (var iJ = 0; iJ < aExpression[iI].length; iJ += 1) {
                var blFound = aExpression[iI][iJ]["regex"].test(sRowText);
                if (aExpression[iI][iJ]["negated"] ? blFound : !blFound) {
                    blMatches = false;
                    break;
                }
            }
            if (blMatches) {
                return true;
            }
        }
        return false;
    }

    function refreshFilterFocusButton(oFilter) {
        var oButton = oFilter && oFilter.id ? document.querySelector(".js-filter-focus[data-filter-input=\"" + oFilter.id + "\"]") : null;
        var aResetButtons = oFilter && oFilter.id ? document.querySelectorAll(".js-filter-reset[data-filter-input=\"" + oFilter.id + "\"]") : [];
        var sClass = "filter-focus-active";
        var sResetClass = "quick-filter-active";
        var blActive = oFilter && oFilter.value.replace(/^\s+|\s+$/g, "") !== "";
        if (oButton) {
            if (blActive && (" " + oButton.className + " ").indexOf(" " + sClass + " ") === -1) {
                oButton.className += (oButton.className ? " " : "") + sClass;
            } else if (!blActive) {
                oButton.className = (" " + oButton.className + " ").replace(" " + sClass + " ", " ").replace(/^\s+|\s+$/g, "");
            }
        }
        for (var iI = 0; iI < aResetButtons.length; iI += 1) {
            if (blActive && (" " + aResetButtons[iI].className + " ").indexOf(" " + sResetClass + " ") === -1) {
                aResetButtons[iI].className += (aResetButtons[iI].className ? " " : "") + sResetClass;
            } else if (!blActive) {
                aResetButtons[iI].className = (" " + aResetButtons[iI].className + " ").replace(" " + sResetClass + " ", " ").replace(/^\s+|\s+$/g, "");
            }
        }
    }

    function sendQuickTableFilterValue(oFilter, sAction) {
        var oData;
        if (!window.fetch || !window.FormData || !oFilter || !oFilter.id) {
            return;
        }
        oData = new FormData();
        oData.append("quick_table_filter_action", sAction);
        oData.append("filter_id", oFilter.id);
        if (sAction == "save") {
            appendAdminEncodedValue(oData, "filter_value", oFilter.value);
        }
        window.fetch(window.location.href, {
            "method": "POST",
            "credentials": "same-origin",
            "headers": getAdminAjaxHeaders(),
            "body": oData
        }).catch(function (oException) {
            logAdminException(oException);
        });
    }

    function scheduleQuickTableFilterSave(oFilter) {
        if (!window.setTimeout || !window.clearTimeout) {
            sendQuickTableFilterValue(oFilter, "save");
            return;
        }
        if (oFilter._quickTableFilterTimer) {
            window.clearTimeout(oFilter._quickTableFilterTimer);
        }
        oFilter._quickTableFilterTimer = window.setTimeout(function () {
            oFilter._quickTableFilterTimer = null;
            sendQuickTableFilterValue(oFilter, "save");
        }, 250)
    }

    function initializeTableFilter(oFilter) {
        var aOperatorButtons = document.querySelectorAll(".js-filter-operator[data-filter-input=\"" + oFilter.id + "\"]");
        var aResetButtons = document.querySelectorAll(".js-filter-reset[data-filter-input=\"" + oFilter.id + "\"]");
        var iFilterTimer = null;

        var filterTable = function () {
            var oTable = document.getElementById(oFilter.getAttribute("data-table-filter"));
            var aExpression = buildFilterExpression(oFilter.value);
            var aRows;
            var aCells;
            var aTexts;
            var sDisplay;
            var sRowText;
            var iK;
            refreshFilterFocusButton(oFilter);
            if (!oTable) {
                return;
            }
            if (oTable && oTable.tBodies && oTable.tBodies.length == 1) {
                aRows = oTable.tBodies[0].rows;
            } else {
                aRows = oTable ? oTable.querySelectorAll("tbody tr") : [];
            }
            for (var iJ = 0; iJ < aRows.length; iJ += 1) {
                if (typeof aRows[iJ]._quickTableFilterText != "string") {
                    aCells = aRows[iJ].cells ? aRows[iJ].cells : aRows[iJ].querySelectorAll("th, td");
                    aTexts = [];
                    for (iK = 0; iK < aCells.length; iK += 1) {
                        aTexts.push(aCells[iK].textContent || "");
                    }
                    aRows[iJ]._quickTableFilterText = aTexts.join(" ");
                }
                sRowText = aRows[iJ]._quickTableFilterText;
                sDisplay = rowMatchesFilterExpression(sRowText, aExpression) ? "" : "none";
                if (aRows[iJ].style.display != sDisplay) {
                    aRows[iJ].style.display = sDisplay;
                }
            }
        };

        function scheduleFilterTable() {
            if (!window.setTimeout || !window.clearTimeout) {
                filterTable();
                return;
            }
            if (iFilterTimer) {
                window.clearTimeout(iFilterTimer);
            }
            iFilterTimer = window.setTimeout(function () {
                iFilterTimer = null;
                filterTable();
            }, 250)
        }

        function runFilterTable() {
            if (iFilterTimer) {
                window.clearTimeout(iFilterTimer);
                iFilterTimer = null;
            }
            filterTable();
        }

        oFilter.addEventListener("input", function () {
            scheduleFilterTable();
            scheduleQuickTableFilterSave(oFilter);
        });
        for (var iI = 0; iI < aOperatorButtons.length; iI += 1) {
            aOperatorButtons[iI].addEventListener("click", function () {
                var sOperator = this.getAttribute("data-filter-operator") || "";
                var iStart = typeof oFilter.selectionStart == "number" ? oFilter.selectionStart : oFilter.value.length;
                var iEnd = typeof oFilter.selectionEnd == "number" ? oFilter.selectionEnd : oFilter.value.length;
                var sBefore = oFilter.value.substring(0, iStart).replace(/\s+$/, "");
                var sAfter = oFilter.value.substring(iEnd).replace(/^\s+/, "");
                var sPrefix = sBefore !== "" ? sBefore + " " : "";
                oFilter.value = sPrefix + sOperator + " " + sAfter;
                oFilter.focus();
                if (typeof oFilter.setSelectionRange == "function") {
                    oFilter.setSelectionRange((sPrefix + sOperator + " ").length, (sPrefix + sOperator + " ").length);
                }
                runFilterTable();
                scheduleQuickTableFilterSave(oFilter);
            })
        }
        for (var iI = 0; iI < aResetButtons.length; iI += 1) {
            aResetButtons[iI].addEventListener("click", function () {
                oFilter.value = "";
                runFilterTable();
                if (oFilter._quickTableFilterTimer) {
                    window.clearTimeout(oFilter._quickTableFilterTimer);
                    oFilter._quickTableFilterTimer = null;
                }
                sendQuickTableFilterValue(oFilter, "reset");
                oFilter.focus();
            })
        }
        refreshFilterFocusButton(oFilter);
        if (oFilter.value.replace(/^\s+|\s+$/g, "") !== "") {
            scheduleFilterTable();
        }
        window.setTimeout(function () {
            refreshFilterFocusButton(oFilter);
        }, 0);
        window.addEventListener("pageshow", function () {
            refreshFilterFocusButton(oFilter);
        });
        focusAdminElement(oFilter, true);
    }
    for (var iI = 0; iI < aFilters.length; iI += 1) {
        initializeTableFilter(aFilters[iI]);
    }
});

document.addEventListener("DOMContentLoaded", function () {
    var oTable = document.getElementById("nx-groups-table");
    var oAdd = document.querySelector(".js-add-group");
    var aPortalPermissions = [];
    if (!oTable || !window.fetch || !window.FormData || !window.JSON) {
        return;
    }
    try {
        aPortalPermissions = JSON.parse(oTable.getAttribute("data-permissions") || "[]");
    } catch (oException) {
        logAdminException(oException);
        aPortalPermissions = [];
    }



    function findAdminGroupRowById(sGroupId) {
        return sGroupId ? oTable.querySelector("tbody tr[data-group-id=\"" + sGroupId + "\"]") : null;
    }

    function getGroupRowStates() {
        var aRows = oTable.querySelectorAll("tbody tr[data-group-id]");
        var aStates = {};
        var sGroupId;
        for (var iI = 0; iI < aRows.length; iI += 1) {
            sGroupId = aRows[iI].getAttribute("data-group-id") || "";
            if (sGroupId !== "") {
                aStates[sGroupId] = aRows[iI];
            }
        }
        return aStates;
    }

    function restoreGroupRowStates(aStates) {
        var aRows = oTable.querySelectorAll("tbody tr[data-group-id]");
        var sGroupId;
        for (var iI = 0; iI < aRows.length; iI += 1) {
            sGroupId = aRows[iI].getAttribute("data-group-id") || "";
            if (sGroupId !== "" && aStates[sGroupId] && window.nxCopyAdminTableRowState) {
                window.nxCopyAdminTableRowState(aStates[sGroupId], aRows[iI]);
            }
            if (window.nxBindAdminTableRow) {
                window.nxBindAdminTableRow(aRows[iI]);
            }
        }
    }

    function replaceGroupRows(sRowsHtml) {
        var oBody = document.createElement("tbody");
        var aStates = getGroupRowStates();
        oBody.innerHTML = sRowsHtml || "";
        oTable.querySelector("tbody").innerHTML = oBody.innerHTML;
        restoreGroupRowStates(aStates);
        refreshAdminTableFilter();
    }

    function replaceGroupRow(iGroupId, sRowHtml) {
        var oCurrentRow = oTable.querySelector("tbody tr[data-group-id=\"" + iGroupId + "\"]");
        var oBody = document.createElement("tbody");
        var oNewRow;
        oBody.innerHTML = sRowHtml || "";
        oNewRow = oBody.querySelector("tr");
        if (!oNewRow) {
            return;
        }
        if (oCurrentRow && window.nxCopyAdminTableRowState) {
            window.nxCopyAdminTableRowState(oCurrentRow, oNewRow);
        }
        if (oCurrentRow) {
            oCurrentRow.parentNode.replaceChild(oNewRow, oCurrentRow);
        } else {
            oTable.querySelector("tbody").appendChild(oNewRow);
        }
        if (window.nxBindAdminTableRow) {
            window.nxBindAdminTableRow(oNewRow);
        }
        refreshAdminTableFilter();
    }

    function removeGroupRow(iGroupId) {
        var oCurrentRow = oTable.querySelector("tbody tr[data-group-id=\"" + iGroupId + "\"]");
        if (oCurrentRow && oCurrentRow.parentNode) {
            oCurrentRow.parentNode.removeChild(oCurrentRow);
            refreshAdminTableFilter();
        }
    }

    function createGroupDialog(sTitle, oGroupRow) {
        var oDialogData = {};
        var closeOnEscape;
        oDialogData.dialog = document.createElement("div");
        oDialogData.form = document.createElement("form");
        oDialogData.header = document.createElement("div");
        oDialogData.title = document.createElement("strong");
        oDialogData.closeButton = document.createElement("button");
        oDialogData.error = document.createElement("p");
        oDialogData.actions = document.createElement("div");
        oDialogData.save = document.createElement("button");
        oDialogData.cancel = document.createElement("button");
        oDialogData.groupRow = oGroupRow || null;
        oDialogData.groupId = oGroupRow ? (oGroupRow.getAttribute("data-group-id") || "") : "";
        oDialogData.closed = false;
        closeOnEscape = function (oEvent) {
            if (oEvent.key == "Escape") {
                oDialogData.close();
            }
        };
        oDialogData.close = function (blSaved) {
            if (oDialogData.closed) {
                return;
            }
            oDialogData.closed = true;
            document.removeEventListener("keydown", closeOnEscape);
            finishAdminSubjectRowEdit(findAdminGroupRowById(oDialogData.groupId) || oDialogData.groupRow, blSaved === true);
            if (oDialogData.dialog.parentNode) {
                oDialogData.dialog.parentNode.removeChild(oDialogData.dialog);
                unlockAdminModalScroll();
            }
            focusAdminElement(oAdd);
        };
        oDialogData.dialog.className = "confirm-dialog";
        oDialogData.form.className = "confirm-dialog-box subject-edit-dialog";
        oDialogData.form.method = "post";
        oDialogData.form.action = window.location.href;
        oDialogData.header.className = "confirm-dialog-header";
        oDialogData.title.textContent = sTitle;
        oDialogData.closeButton.type = "button";
        oDialogData.closeButton.className = "confirm-dialog-close";
        oDialogData.closeButton.setAttribute("aria-label", "Close");
        oDialogData.closeButton.textContent = "\u00D7";
        oDialogData.error.className = "subject-edit-error";
        oDialogData.error.style.display = "none";
        oDialogData.actions.className = "confirm-dialog-actions";
        oDialogData.save.type = "submit";
        oDialogData.save.className = "confirm-dialog-button";
        oDialogData.save.textContent = "Save";
        oDialogData.cancel.type = "button";
        oDialogData.cancel.className = "confirm-dialog-button";
        oDialogData.cancel.textContent = "Cancel";
        oDialogData.header.appendChild(oDialogData.title);
        oDialogData.header.appendChild(oDialogData.closeButton);
        oDialogData.form.appendChild(oDialogData.header);
        oDialogData.cancel.addEventListener("click", function () {
            oDialogData.close();
        });
        oDialogData.closeButton.addEventListener("click", function () {
            oDialogData.close();
        });
        enableAdminDialogDrag(oDialogData.dialog, oDialogData.form, oDialogData.header);

        document.addEventListener("keydown", closeOnEscape);
        return oDialogData;
    }

    function appendGroupTextField(oParent, sLabel, sName, sValue) {
        var oLabel = document.createElement("label");
        var oInput = document.createElement("input");
        oLabel.textContent = sLabel;
        oInput.type = "text";
        oInput.name = sName;
        oInput.value = sValue || "";
        oParent.appendChild(oLabel);
        oParent.appendChild(oInput);
        return oInput;
    }

    function getGroupPermissionKeys(oRow) {
        var sKeys = oRow ? (oRow.getAttribute("data-permission-keys") || "") : "";
        var aKeys = sKeys ? sKeys.split(",") : [];
        var aResult = [];
        for (var iI = 0; iI < aKeys.length; iI += 1) {
            if (aKeys[iI]) {
                aResult.push(aKeys[iI]);
            }
        }
        return aResult;
    }

    function groupPermissionKeySelected(aKeys, sPermissionKey) {
        for (var iI = 0; iI < aKeys.length; iI += 1) {
            if (aKeys[iI] == sPermissionKey) {
                return true;
            }
        }
        return false;
    }

    function appendGroupPermissionFields(oParent, oRow) {
        var aSelectedKeys = getGroupPermissionKeys(oRow);
        var oWrapper;
        var oTitle;
        var oLabel;
        var oInput;
        var iI;
        if (!aPortalPermissions || aPortalPermissions.length === 0) {
            return [];
        }
        oWrapper = document.createElement("div");
        oWrapper.className = "group-permissions";
        oTitle = document.createElement("strong");
        oTitle.textContent = "Permissions";
        oWrapper.appendChild(oTitle);
        for (iI = 0; iI < aPortalPermissions.length; iI += 1) {
            oLabel = document.createElement("label");
            oLabel.className = "checkbox-label";
            oInput = document.createElement("input");
            oInput.type = "checkbox";
            oInput.className = "js-group-permission";
            oInput.value = aPortalPermissions[iI]["permission_key"] || "";
            oInput.checked = groupPermissionKeySelected(aSelectedKeys, oInput.value);
            oLabel.appendChild(oInput);
            oLabel.appendChild(document.createTextNode(aPortalPermissions[iI]["name"] || oInput.value));
            oWrapper.appendChild(oLabel);
        }
        oParent.appendChild(oWrapper);
        return oWrapper.querySelectorAll(".js-group-permission");
    }

    function appendGroupMergeSourceFields(oParent, oTargetRow) {
        var sTargetGroupId = oTargetRow ? (oTargetRow.getAttribute("data-group-id") || "") : "";
        var aRows = oTable.querySelectorAll("tbody tr[data-group-id]");
        var oWrapper = document.createElement("div");
        var oTitle = document.createElement("strong");
        var oSelectAllLabel = document.createElement("label");
        var oSelectAll = document.createElement("input");
        var oSourceList = document.createElement("div");
        var oMessage;
        var oLabel;
        var oInput;
        var sGroupId;
        var sGroupName;
        var iSourceCount = 0;

        function updateSelectAll() {
            var aInputs = oWrapper.querySelectorAll(".js-group-merge-source");
            var blAllChecked = aInputs.length > 0;
            for (var iI = 0; iI < aInputs.length; iI += 1) {
                if (!aInputs[iI].checked) {
                    blAllChecked = false;
                }
            }
            oSelectAll.checked = blAllChecked;
        }

        oWrapper.className = "group-permissions";
        oTitle.textContent = "Source Groups";
        oWrapper.appendChild(oTitle);
        oSelectAll.type = "checkbox";
        oSelectAll.className = "js-group-merge-select-all";
        oSelectAllLabel.className = "checkbox-label";
        oSelectAllLabel.appendChild(oSelectAll);
        oSelectAllLabel.appendChild(document.createTextNode("All remaining groups"));
        oWrapper.appendChild(oSelectAllLabel);
        oSourceList.className = "merge-source-list";
        oWrapper.appendChild(oSourceList);
        for (var iI = 0; iI < aRows.length; iI += 1) {
            sGroupId = aRows[iI].getAttribute("data-group-id") || "";
            if (!sGroupId || sGroupId == sTargetGroupId) {
                continue;
            }
            sGroupName = aRows[iI].getAttribute("data-group-name") || "";
            oLabel = document.createElement("label");
            oLabel.className = "checkbox-label";
            oInput = document.createElement("input");
            oInput.type = "checkbox";
            oInput.className = "js-group-merge-source";
            oInput.value = sGroupId;
            oInput.addEventListener("change", updateSelectAll);
            oLabel.appendChild(oInput);
            oLabel.appendChild(document.createTextNode(sGroupName || sGroupId));
            oSourceList.appendChild(oLabel);
            iSourceCount += 1;
        }
        setAdminMergeSourceListColumns(oParent, oSourceList, iSourceCount + 1);
        if (iSourceCount === 0) {
            oMessage = document.createElement("p");
            oMessage.textContent = "No source groups are available.";
            oWrapper.appendChild(oMessage);
            oSelectAll.disabled = true;
        }
        oSelectAll.addEventListener("change", function () {
            var aInputs = oWrapper.querySelectorAll(".js-group-merge-source");
            for (var iI = 0; iI < aInputs.length; iI += 1) {
                aInputs[iI].checked = oSelectAll.checked;
            }
        });
        oParent.appendChild(oWrapper);
        return oWrapper.querySelectorAll(".js-group-merge-source");
    }

    function finishGroupDialog(oDialogData, oFocus) {
        oDialogData.form.appendChild(oDialogData.error);
        oDialogData.actions.appendChild(oDialogData.save);
        oDialogData.actions.appendChild(oDialogData.cancel);
        oDialogData.form.appendChild(oDialogData.actions);
        oDialogData.dialog.appendChild(oDialogData.form);
        document.body.appendChild(oDialogData.dialog);
        lockAdminModalScroll();
        beginAdminSubjectRowEdit(findAdminGroupRowById(oDialogData.groupId) || oDialogData.groupRow);
        focusAdminElement(oFocus, true);
    }

    function submitGroupDialog(oDialogData, oData) {
        setAdminDialogError(oDialogData.error, "");
        oDialogData.save.disabled = true;
        appendAdminCsrfToken(oData);
        fetch(window.location.href, {
            "method": "POST",
            "body": oData,
            "credentials": "same-origin",
            "headers": getAdminAjaxHeaders()
        }).then(function (oResponse) {
            return oResponse.json();
        }).then(function (aData) {
            if (!aData || !aData.success) {
                setAdminDialogError(oDialogData.error, aData && aData.message ? aData.message : "Group could not be saved.");
                oDialogData.save.disabled = false;
                return;
            }
            if (aData.group_deleted) {
                removeGroupRow(aData.group_id);
            } else if (aData.groups_merged) {
                if (aData.source_groups_deleted && aData.source_group_ids) {
                    for (var iI = 0; iI < aData.source_group_ids.length; iI += 1) {
                        removeGroupRow(aData.source_group_ids[iI]);
                    }
                }
                replaceGroupRow(aData.target_group_id, aData.target_row_html);
            } else {
                replaceGroupRow(aData.group_id, aData.row_html);
            }
            oDialogData.close(true);
        }).catch(function (oException) {
            logAdminException(oException);
            setAdminDialogError(oDialogData.error, "Group could not be saved.");
            oDialogData.save.disabled = false;
        })
    }

    function openGroupAdminDialog(oRow) {
        var blNewGroup = !oRow;
        var oDialogData = createGroupDialog(blNewGroup ? "New Group" : "Edit Group", oRow);
        var oName = appendGroupTextField(oDialogData.form, "Name", "name", oRow ? (oRow.getAttribute("data-group-name") || "") : "");
        var aPermissionInputs = appendGroupPermissionFields(oDialogData.form, oRow);
        oDialogData.form.addEventListener("submit", function (oEvent) {
            var oData = new FormData();
            var iI;
            oEvent.preventDefault();
            oData.append("action", blNewGroup ? "create_group" : "update_group");
            if (!blNewGroup) {
                oData.append("group_id", oRow.getAttribute("data-group-id") || "");
            }
            appendAdminEncodedValue(oData, "name", oName.value);
            for (iI = 0; iI < aPermissionInputs.length; iI += 1) {
                if (aPermissionInputs[iI].checked) {
                    oData.append("permissions[]", aPermissionInputs[iI].value);
                }
            }
            submitGroupDialog(oDialogData, oData);
        });
        finishGroupDialog(oDialogData, oName);
    }

    function openGroupDeleteDialog(oRow) {
        var oDialogData = createGroupDialog("Confirm Deletion", oRow);
        var oText = document.createElement("p");
        if (!oRow) {
            return;
        }
        oDialogData.save.textContent = "Yes";
        oDialogData.cancel.textContent = "No";
        oText.textContent = "Delete this group?";
        oDialogData.form.appendChild(oText);
        oDialogData.form.addEventListener("submit", function (oEvent) {
            var oData = new FormData();
            oEvent.preventDefault();
            oData.append("action", "delete_group");
            oData.append("group_id", oRow.getAttribute("data-group-id") || "");
            submitGroupDialog(oDialogData, oData);
        });
        finishGroupDialog(oDialogData, oDialogData.save);
    }

    function openGroupMergeDialog(oRow) {
        var oDialogData = createGroupDialog("Merge Groups", oRow);
        var oTargetText = document.createElement("p");
        var aSourceInputs;
        var oDeleteLabel = document.createElement("label");
        var oDeleteInput = document.createElement("input");
        if (!oRow) {
            return;
        }
        oDialogData.save.textContent = "Merge";
        oTargetText.textContent = "Target group: " + (oRow.getAttribute("data-group-name") || "");
        oDialogData.form.appendChild(oTargetText);
        aSourceInputs = appendGroupMergeSourceFields(oDialogData.form, oRow);
        oDeleteInput.type = "checkbox";
        oDeleteInput.checked = true;
        oDeleteLabel.className = "checkbox-label";
        oDeleteLabel.appendChild(oDeleteInput);
        oDeleteLabel.appendChild(document.createTextNode("Delete source groups after merge"));
        oDialogData.form.appendChild(oDeleteLabel);
        oDialogData.form.addEventListener("submit", function (oEvent) {
            var oData = new FormData();
            var iSelectedCount = 0;
            var iI;
            oEvent.preventDefault();
            oData.append("action", "merge_groups");
            oData.append("target_group_id", oRow.getAttribute("data-group-id") || "");
            for (iI = 0; iI < aSourceInputs.length; iI += 1) {
                if (aSourceInputs[iI].checked) {
                    oData.append("source_group_ids[]", aSourceInputs[iI].value);
                    iSelectedCount += 1;
                }
            }
            if (iSelectedCount < 1) {
                setAdminDialogError(oDialogData.error, "Select at least one source group.");
                return;
            }
            oData.append("delete_source_groups", oDeleteInput.checked ? "1" : "0");
            submitGroupDialog(oDialogData, oData);
        });
        finishGroupDialog(oDialogData, aSourceInputs.length > 0 ? aSourceInputs[0] : oDialogData.save);
    }

    function moveGroup(oRow, sDirection) {
        var oData = new FormData();
        if (!oRow) {
            return;
        }
        oData.append("action", "move_group");
        oData.append("group_id", oRow.getAttribute("data-group-id") || "");
        oData.append("direction", sDirection);
        appendAdminCsrfToken(oData);
        fetch(window.location.href, {
            "method": "POST",
            "body": oData,
            "credentials": "same-origin",
            "headers": getAdminAjaxHeaders()
        }).then(function (oResponse) {
            return oResponse.json();
        }).then(function (aData) {
            if (aData && aData.success && aData.rows_html) {
                replaceGroupRows(aData.rows_html);
            }
        }).catch(function (oException) {
            logAdminException(oException);
        });
    }

    if (oAdd) {
        oAdd.addEventListener("click", function () {
            openGroupAdminDialog(null);
        })
    }
    oTable.addEventListener("click", function (oEvent) {
        var oButton = oEvent.target.closest ? oEvent.target.closest(".js-edit-group, .js-delete-group, .js-merge-group, .js-move-group-up, .js-move-group-down") : null;
        if (!oButton) {
            return;
        }
        oEvent.preventDefault();
        if (oButton.className.indexOf("js-merge-group") !== -1) {
            openGroupMergeDialog(oButton.closest("tr[data-group-id]"));
        } else if (oButton.className.indexOf("js-delete-group") !== -1) {
            openGroupDeleteDialog(oButton.closest("tr[data-group-id]"));
        } else if (oButton.className.indexOf("js-move-group-up") !== -1) {
            moveGroup(oButton.closest("tr[data-group-id]"), "up");
        } else if (oButton.className.indexOf("js-move-group-down") !== -1) {
            moveGroup(oButton.closest("tr[data-group-id]"), "down");
        } else {
            openGroupAdminDialog(oButton.closest("tr[data-group-id]"));
        }
    })
});

document.addEventListener("DOMContentLoaded", function () {
    var oTable = document.getElementById("nx-interactions-table");
    if (!oTable || !window.fetch || !window.FormData) {
        return;
    }
    oTable.addEventListener("click", function (oEvent) {
        var oButton = oEvent.target.closest ? oEvent.target.closest(".js-communication-served") : null;
        var oRow;
        var oData;
        if (!oButton) {
            return;
        }
        oEvent.preventDefault();
        if (oButton.disabled) {
            return;
        }
        oRow = oButton.closest ? oButton.closest("tr[data-subject-id]") : null;
        oData = new FormData();
        oButton.disabled = true;
        oData.append("action", "mark_communication_served");
        oData.append("subject_id", oButton.getAttribute("data-subject-id") || (oRow ? oRow.getAttribute("data-subject-id") : ""));
        appendAdminCsrfToken(oData);
        fetch(window.location.href, {
            "method": "POST",
            "body": oData,
            "credentials": "same-origin",
            "headers": getAdminAjaxHeaders()
        }).then(function (oResponse) {
            return oResponse.json();
        }).then(function (aData) {
            if (!aData || !aData.success) {
                oButton.disabled = false;
                window.alert(aData && aData.message ? aData.message : "Communication could not be marked served.");
                return;
            }
            if (oRow && oRow.parentNode) {
                oRow.parentNode.removeChild(oRow);
            }
        }).catch(function (oException) {
            logAdminException(oException);
            oButton.disabled = false;
            window.alert("Communication could not be marked served.");
        })
    })
});

document.addEventListener("DOMContentLoaded", function () {
    var oTable = document.getElementById("nx-contact-types-table");
    var oAdd = document.querySelector(".js-add-contact-type");
    if (!oTable || !window.fetch || !window.FormData) {
        return;
    }



    function findAdminContactTypeRowById(sContactTypeId) {
        return sContactTypeId ? oTable.querySelector("tbody tr[data-contact-type-id=\"" + sContactTypeId + "\"]") : null;
    }

    function getContactTypeRowStates() {
        var aRows = oTable.querySelectorAll("tbody tr[data-contact-type-id]");
        var aStates = {};
        var sContactTypeId;
        for (var iI = 0; iI < aRows.length; iI += 1) {
            sContactTypeId = aRows[iI].getAttribute("data-contact-type-id") || "";
            if (sContactTypeId !== "") {
                aStates[sContactTypeId] = aRows[iI];
            }
        }
        return aStates;
    }

    function restoreContactTypeRowStates(aStates) {
        var aRows = oTable.querySelectorAll("tbody tr[data-contact-type-id]");
        var sContactTypeId;
        for (var iI = 0; iI < aRows.length; iI += 1) {
            sContactTypeId = aRows[iI].getAttribute("data-contact-type-id") || "";
            if (sContactTypeId !== "" && aStates[sContactTypeId] && window.nxCopyAdminTableRowState) {
                window.nxCopyAdminTableRowState(aStates[sContactTypeId], aRows[iI]);
            }
            if (window.nxBindAdminTableRow) {
                window.nxBindAdminTableRow(aRows[iI]);
            }
        }
    }

    function replaceContactTypeRows(sRowsHtml) {
        var oBody = document.createElement("tbody");
        var aStates = getContactTypeRowStates();
        oBody.innerHTML = sRowsHtml || "";
        oTable.querySelector("tbody").innerHTML = oBody.innerHTML;
        restoreContactTypeRowStates(aStates);
        refreshAdminTableFilter();
    }

    function replaceContactTypeRow(iContactTypeId, sRowHtml) {
        var oCurrentRow = findAdminContactTypeRowById(iContactTypeId);
        var oBody = document.createElement("tbody");
        var oNewRow;
        oBody.innerHTML = sRowHtml || "";
        oNewRow = oBody.querySelector("tr");
        if (!oNewRow) {
            return;
        }
        if (oCurrentRow && window.nxCopyAdminTableRowState) {
            window.nxCopyAdminTableRowState(oCurrentRow, oNewRow);
        }
        if (oCurrentRow && oCurrentRow.parentNode) {
            oCurrentRow.parentNode.replaceChild(oNewRow, oCurrentRow);
        } else {
            oTable.querySelector("tbody").appendChild(oNewRow);
        }
        if (window.nxBindAdminTableRow) {
            window.nxBindAdminTableRow(oNewRow);
        }
        refreshAdminTableFilter();
    }

    function removeContactTypeRow(iContactTypeId) {
        var oRow = findAdminContactTypeRowById(iContactTypeId);
        if (oRow && oRow.parentNode) {
            oRow.parentNode.removeChild(oRow);
        }
        refreshAdminTableFilter();
    }


    function createContactTypeDialog(sTitle, oRow) {
        var oDialog = document.createElement("div");
        var oForm = document.createElement("form");
        var oHeader = document.createElement("div");
        var oTitle = document.createElement("strong");
        var oClose = document.createElement("button");
        var oError = document.createElement("p");
        var oActions = document.createElement("div");
        var oSave = document.createElement("button");
        var oCancel = document.createElement("button");
        var blClosed = false;
        var sContactTypeId = oRow ? (oRow.getAttribute("data-contact-type-id") || "") : "";
        var closeOnEscape = function (oEvent) {
            if (oEvent.key == "Escape") {
                closeDialog();
            }
        };
        var closeDialog = function (blSaved) {
            if (blClosed) {
                return;
            }
            blClosed = true;
            document.removeEventListener("keydown", closeOnEscape);
            finishAdminSubjectRowEdit(findAdminContactTypeRowById(sContactTypeId) || oRow, blSaved === true);
            closeAdminDialogElement(oDialog);
        };
        oDialog.className = "confirm-dialog";
        oForm.className = "confirm-dialog-box subject-edit-dialog";
        oForm.method = "post";
        oForm.action = window.location.href;
        oHeader.className = "confirm-dialog-header";
        oTitle.textContent = sTitle;
        oClose.type = "button";
        oClose.className = "confirm-dialog-close";
        oClose.setAttribute("aria-label", "Close");
        oClose.textContent = "\u00D7";
        oError.className = "subject-edit-error";
        oError.style.display = "none";
        oActions.className = "confirm-dialog-actions";
        oSave.type = "submit";
        oSave.className = "confirm-dialog-button";
        oSave.textContent = "Save";
        oCancel.type = "button";
        oCancel.className = "confirm-dialog-button";
        oCancel.textContent = "Cancel";
        oHeader.appendChild(oTitle);
        oHeader.appendChild(oClose);
        oForm.appendChild(oHeader);
        enableAdminDialogDrag(oDialog, oForm, oHeader);
        oClose.addEventListener("click", function () {
            closeDialog();
        });
        oCancel.addEventListener("click", function () {
            closeDialog();
        });

        document.addEventListener("keydown", closeOnEscape);
        return {
            "dialog": oDialog,
            "form": oForm,
            "error": oError,
            "actions": oActions,
            "save": oSave,
            "cancel": oCancel,
            "close": closeDialog,
            "contactTypeId": sContactTypeId,
            "contactTypeRow": oRow
        }
    }

    function appendContactTypeTextField(oParent, sLabel, sName, sValue) {
        var oLabel = document.createElement("label");
        var oInput = document.createElement("input");
        oLabel.textContent = sLabel;
        oLabel.setAttribute("for", "contact-type-edit-" + sName);
        oInput.type = "text";
        oInput.id = "contact-type-edit-" + sName;
        oInput.name = sName;
        oInput.value = sValue || "";
        oParent.appendChild(oLabel);
        oParent.appendChild(oInput);
        return oInput;
    }

    function appendContactTypeCheckbox(oParent, sLabel, sName, blChecked) {
        var oLabel = document.createElement("label");
        var oInput = document.createElement("input");
        oInput.type = "checkbox";
        oInput.name = sName;
        oInput.value = "1";
        oInput.checked = blChecked;
        oLabel.className = "checkbox-label";
        oLabel.appendChild(oInput);
        oLabel.appendChild(document.createTextNode(sLabel));
        oParent.appendChild(oLabel);
        return oInput;
    }

    function appendContactTypeMergeSourceFields(oParent, oTargetRow) {
        var sTargetContactTypeId = oTargetRow ? (oTargetRow.getAttribute("data-contact-type-id") || "") : "";
        var aRows = oTable.querySelectorAll("tbody tr[data-contact-type-id]");
        var oWrapper = document.createElement("div");
        var oTitle = document.createElement("strong");
        var oSelectAllLabel = document.createElement("label");
        var oSelectAll = document.createElement("input");
        var oSourceList = document.createElement("div");
        var oMessage;
        var oLabel;
        var oInput;
        var sContactTypeId;
        var sContactTypeName;
        var iSourceCount = 0;

        function updateSelectAll() {
            var aInputs = oWrapper.querySelectorAll(".js-contact-type-merge-source");
            var blAllChecked = aInputs.length > 0;
            for (var iI = 0; iI < aInputs.length; iI += 1) {
                if (!aInputs[iI].checked) {
                    blAllChecked = false;
                }
            }
            oSelectAll.checked = blAllChecked;
        }

        oWrapper.className = "group-permissions";
        oTitle.textContent = "Source Contact Types";
        oWrapper.appendChild(oTitle);
        oSelectAll.type = "checkbox";
        oSelectAll.className = "js-contact-type-merge-select-all";
        oSelectAllLabel.className = "checkbox-label";
        oSelectAllLabel.appendChild(oSelectAll);
        oSelectAllLabel.appendChild(document.createTextNode("All remaining contact types"));
        oWrapper.appendChild(oSelectAllLabel);
        oSourceList.className = "merge-source-list";
        oWrapper.appendChild(oSourceList);
        for (var iI = 0; iI < aRows.length; iI += 1) {
            sContactTypeId = aRows[iI].getAttribute("data-contact-type-id") || "";
            if (!sContactTypeId || sContactTypeId == sTargetContactTypeId) {
                continue;
            }
            sContactTypeName = aRows[iI].getAttribute("data-contact-type-name") || "";
            oLabel = document.createElement("label");
            oLabel.className = "checkbox-label";
            oInput = document.createElement("input");
            oInput.type = "checkbox";
            oInput.className = "js-contact-type-merge-source";
            oInput.value = sContactTypeId;
            oInput.addEventListener("change", updateSelectAll);
            oLabel.appendChild(oInput);
            oLabel.appendChild(document.createTextNode(sContactTypeName || sContactTypeId));
            oSourceList.appendChild(oLabel);
            iSourceCount += 1;
        }
        setAdminMergeSourceListColumns(oParent, oSourceList, iSourceCount + 1);
        if (iSourceCount === 0) {
            oMessage = document.createElement("p");
            oMessage.textContent = "No source contact types are available.";
            oWrapper.appendChild(oMessage);
            oSelectAll.disabled = true;
        }
        oSelectAll.addEventListener("change", function () {
            var aInputs = oWrapper.querySelectorAll(".js-contact-type-merge-source");
            for (var iI = 0; iI < aInputs.length; iI += 1) {
                aInputs[iI].checked = oSelectAll.checked;
            }
        });
        oParent.appendChild(oWrapper);
        return oWrapper.querySelectorAll(".js-contact-type-merge-source");
    }

    function finishContactTypeDialog(oDialogData, oFocus) {
        oDialogData.form.appendChild(oDialogData.error);
        oDialogData.actions.appendChild(oDialogData.save);
        oDialogData.actions.appendChild(oDialogData.cancel);
        oDialogData.form.appendChild(oDialogData.actions);
        oDialogData.dialog.appendChild(oDialogData.form);
        document.body.appendChild(oDialogData.dialog);
        lockAdminModalScroll();
        beginAdminSubjectRowEdit(findAdminContactTypeRowById(oDialogData.contactTypeId) || oDialogData.contactTypeRow);
        focusAdminElement(oFocus, true);
    }

    function submitContactTypeDialog(oDialogData, oData) {
        setAdminDialogError(oDialogData.error, "");
        oDialogData.save.disabled = true;
        appendAdminCsrfToken(oData);
        fetch(window.location.href, {
            "method": "POST",
            "body": oData,
            "credentials": "same-origin",
            "headers": getAdminAjaxHeaders()
        }).then(function (oResponse) {
            return oResponse.json();
        }).then(function (aData) {
            if (!aData || !aData.success) {
                setAdminDialogError(oDialogData.error, aData && aData.message ? aData.message : "Contact type could not be saved.");
                oDialogData.save.disabled = false;
                return;
            }
            if (aData.rows_html) {
                replaceContactTypeRows(aData.rows_html);
            } else if (aData.contact_type_deleted) {
                removeContactTypeRow(aData.contact_type_id);
            } else {
                replaceContactTypeRow(aData.contact_type_id, aData.row_html);
            }
            oDialogData.close(true);
        }).catch(function (oException) {
            logAdminException(oException);
            setAdminDialogError(oDialogData.error, "Contact type could not be saved.");
            oDialogData.save.disabled = false;
        })
    }

    function openContactTypeAdminDialog(oRow) {
        var blNewContactType = !oRow;
        var oDialogData = createContactTypeDialog(blNewContactType ? "New Contact Type" : "Edit Contact Type", oRow);
        var oName = appendContactTypeTextField(oDialogData.form, "Name", "name", oRow ? (oRow.getAttribute("data-contact-type-name") || "") : "");
        var oActive = appendContactTypeCheckbox(oDialogData.form, "Active", "is_active", blNewContactType ? true : oRow.getAttribute("data-contact-type-active") == "1");
        oDialogData.form.addEventListener("submit", function (oEvent) {
            var oData = new FormData();
            oEvent.preventDefault();
            oData.append("action", blNewContactType ? "create_contact_type" : "update_contact_type");
            if (!blNewContactType) {
                oData.append("contact_type_id", oRow.getAttribute("data-contact-type-id") || "");
            }
            appendAdminEncodedValue(oData, "name", oName.value);
            oData.append("is_active", oActive.checked ? "1" : "0");
            submitContactTypeDialog(oDialogData, oData);
        });
        finishContactTypeDialog(oDialogData, oName);
    }

    function openContactTypeDeleteDialog(oRow) {
        var oDialogData = createContactTypeDialog("Confirm Deletion", oRow);
        var oText = document.createElement("p");
        if (!oRow) {
            return;
        }
        oDialogData.save.textContent = "Yes";
        oDialogData.cancel.textContent = "No";
        oText.textContent = "Delete this contact type?";
        oDialogData.form.appendChild(oText);
        oDialogData.form.addEventListener("submit", function (oEvent) {
            var oData = new FormData();
            oEvent.preventDefault();
            oData.append("action", "delete_contact_type");
            oData.append("contact_type_id", oRow.getAttribute("data-contact-type-id") || "");
            submitContactTypeDialog(oDialogData, oData);
        });
        finishContactTypeDialog(oDialogData, oDialogData.save);
    }

    function openContactTypeMergeDialog(oRow) {
        var oDialogData = createContactTypeDialog("Merge Contact Types", oRow);
        var oTargetText = document.createElement("p");
        var aSourceInputs;
        var oDeleteLabel = document.createElement("label");
        var oDeleteInput = document.createElement("input");
        if (!oRow) {
            return;
        }
        oDialogData.save.textContent = "Merge";
        oTargetText.textContent = "Target contact type: " + (oRow.getAttribute("data-contact-type-name") || "");
        oDialogData.form.appendChild(oTargetText);
        aSourceInputs = appendContactTypeMergeSourceFields(oDialogData.form, oRow);
        oDeleteInput.type = "checkbox";
        oDeleteInput.checked = true;
        oDeleteLabel.className = "checkbox-label";
        oDeleteLabel.appendChild(oDeleteInput);
        oDeleteLabel.appendChild(document.createTextNode("Delete source contact types after merge"));
        oDialogData.form.appendChild(oDeleteLabel);
        oDialogData.form.addEventListener("submit", function (oEvent) {
            var oData = new FormData();
            var iSelectedCount = 0;
            var iI;
            oEvent.preventDefault();
            oData.append("action", "merge_contact_types");
            oData.append("target_contact_type_id", oRow.getAttribute("data-contact-type-id") || "");
            for (iI = 0; iI < aSourceInputs.length; iI += 1) {
                if (aSourceInputs[iI].checked) {
                    oData.append("source_contact_type_ids[]", aSourceInputs[iI].value);
                    iSelectedCount += 1;
                }
            }
            if (iSelectedCount < 1) {
                setAdminDialogError(oDialogData.error, "Select at least one source contact type.");
                return;
            }
            oData.append("delete_source_contact_types", oDeleteInput.checked ? "1" : "0");
            submitContactTypeDialog(oDialogData, oData);
        });
        finishContactTypeDialog(oDialogData, aSourceInputs.length > 0 ? aSourceInputs[0] : oDialogData.save);
    }

    function moveContactType(oRow, sDirection) {
        var oData = new FormData();
        if (!oRow) {
            return;
        }
        oData.append("action", "move_contact_type");
        oData.append("contact_type_id", oRow.getAttribute("data-contact-type-id") || "");
        oData.append("direction", sDirection);
        appendAdminCsrfToken(oData);
        fetch(window.location.href, {
            "method": "POST",
            "body": oData,
            "credentials": "same-origin",
            "headers": getAdminAjaxHeaders()
        }).then(function (oResponse) {
            return oResponse.json();
        }).then(function (aData) {
            if (aData && aData.success && aData.rows_html) {
                replaceContactTypeRows(aData.rows_html);
            }
        }).catch(function (oException) {
            logAdminException(oException);
        });
    }

    if (oAdd) {
        oAdd.addEventListener("click", function () {
            openContactTypeAdminDialog(null);
        })
    }
    oTable.addEventListener("click", function (oEvent) {
        var oButton = oEvent.target.closest ? oEvent.target.closest(".js-edit-contact-type, .js-delete-contact-type, .js-merge-contact-type, .js-move-contact-type-up, .js-move-contact-type-down") : null;
        if (!oButton) {
            return;
        }
        oEvent.preventDefault();
        if (oButton.className.indexOf("js-merge-contact-type") !== -1) {
            openContactTypeMergeDialog(oButton.closest("tr[data-contact-type-id]"));
        } else if (oButton.className.indexOf("js-delete-contact-type") !== -1) {
            openContactTypeDeleteDialog(oButton.closest("tr[data-contact-type-id]"));
        } else if (oButton.className.indexOf("js-move-contact-type-up") !== -1) {
            moveContactType(oButton.closest("tr[data-contact-type-id]"), "up");
        } else if (oButton.className.indexOf("js-move-contact-type-down") !== -1) {
            moveContactType(oButton.closest("tr[data-contact-type-id]"), "down");
        } else {
            openContactTypeAdminDialog(oButton.closest("tr[data-contact-type-id]"));
        }
    })
});

document.addEventListener("DOMContentLoaded", function () {
    var aSubjectButtons = document.querySelectorAll(".js-add-subject, .js-add-subject-nickname, .js-add-subject-address, .js-add-subject-group, .js-add-subject-note, .js-edit-subject, .js-edit-subject-portal, .js-edit-subject-nickname, .js-edit-subject-address, .js-edit-subject-group, .js-edit-subject-note, .js-delete-subject, .js-delete-subject-contact, .js-delete-subject-nickname, .js-delete-subject-address, .js-delete-subject-group, .js-delete-subject-note");
    var iSubjectCalendarFirstDay = 1;
    var sSubjectDateInputFormat = "YYYY-MM-DD";
    var sSubjectDateInputPattern = "\\d{4}-\\d{2}-\\d{2}";
    var blHideSubjectBirthNumber = false;
    var aSubjectCountryCodes = ("AD AE AF AG AI AL AM AO AQ AR AS AT AU AW AX AZ BA BB BD BE BF BG BH BI BJ BL BM BN BO BQ BR BS BT BV BW BY BZ CA CC CD CF CG CH CI CK CL CM CN CO CR CS CU CV CW CX CY CZ DE DJ DK DM DO DZ EC EE EG EH ER ES ET FI FJ FK FM FO FR GA GB GD GE GF GG GH GI GL GM GN GP GQ GR GS GT GU GW GY HK HM HN HR HT HU ID IE IL IM IN IO IQ IR IS IT JE JM JO JP KE KG KH KI KM KN KP KR KW KY KZ LA LB LC LI LK LR LS LT LU LV LY MA MC MD ME MF MG MH MK ML MM MN MO MP MQ MR MS MT MU MV MW MX MY MZ NA NC NE NF NG NI NL NO NP NR NU NZ OM PA PE PF PG PH PK PL PM PN PR PS PT PW PY QA RE RO RS RU RW SA SB SC SD SE SG SH SI SJ SK SL SM SN SO SR SS ST SV SX SY SZ TC TD TF TG TH TJ TK TL TM TN TO TR TT TV TW TZ UA UG UM US UY UZ VA VC VE VG VI VN VU WF WS YE YT ZA ZM ZW").split(" ");
    var aSubjectCountryOptions = null;
    if (aSubjectButtons.length === 0 || !window.fetch || !window.FormData || !window.JSON) {
        return;
    }
    if (document.body) {
        iSubjectCalendarFirstDay = parseInt(document.body.getAttribute("data-calendar-first-day") || "1", 10);
        sSubjectDateInputFormat = document.body.getAttribute("data-date-input-format") || sSubjectDateInputFormat;
        sSubjectDateInputPattern = document.body.getAttribute("data-date-input-pattern") || sSubjectDateInputPattern;
        blHideSubjectBirthNumber = document.body.getAttribute("data-hide-subject-birth-number") == "1";
    }
    if (isNaN(iSubjectCalendarFirstDay) || iSubjectCalendarFirstDay < 0 || iSubjectCalendarFirstDay > 6) {
        iSubjectCalendarFirstDay = 1;
    }

    function closeSubjectDialog(oDialog) {
        var aCalendars = document.querySelectorAll(".subject-date-calendar");
        var iI;
        for (iI = 0; iI < aCalendars.length; iI += 1) {
            if (aCalendars[iI].parentNode) {
                aCalendars[iI].parentNode.removeChild(aCalendars[iI]);
            }
        }
        if (oDialog && oDialog.parentNode) {
            oDialog.parentNode.removeChild(oDialog);
            unlockAdminModalScroll();
        }
    }

    function getSubjectValue(aData, sName) {
        if (!aData || typeof aData[sName] == "undefined" || aData[sName] === null) {
            return "";
        }
        return aData[sName];
    }

    function getSubjectButtonJson(oButton, sName) {
        var sJson = oButton ? (oButton.getAttribute(sName) || "") : "";
        if (sJson === "") {
            return null;
        }
        try {
            return JSON.parse(sJson);
        } catch (oException) {
            logAdminException(oException);
            window.alert("Dummy data could not be loaded.");
            return null;
        }
    }

    function getSubjectItemValue(oItem, sName) {
        return oItem ? (oItem.getAttribute(sName) || "") : "";
    }

    function getSubjectNoteText(oItem) {
        var oSource = oItem ? oItem.querySelector(".nx-subject-note-source") : null;
        return oSource ? oSource.textContent : getSubjectItemValue(oItem, "data-note-text");
    }

    function getSubjectFlag(aData, sName) {
        return parseInt(getSubjectValue(aData, sName) || "0", 10) === 1;
    }

    function getSubjectItemFlag(oItem, sName) {
        return getSubjectItemValue(oItem, sName) == "1";
    }

    function padSubjectIsoDateNumber(iValue) {
        return iValue < 10 ? "0" + iValue : "" + iValue;
    }

    function formatSubjectIsoDate(oDate) {
        return oDate.getFullYear() + "-" + padSubjectIsoDateNumber(oDate.getMonth() + 1) + "-" + padSubjectIsoDateNumber(oDate.getDate());
    }

    function parseSubjectIsoDate(sValue) {
        var aMatch;
        var iYear;
        var iMonth;
        var iDay;
        var oDate;
        if (!/^\d{4}-\d{2}-\d{2}$/.test(sValue || "")) {
            return null;
        }
        aMatch = /^(\d{4})-(\d{2})-(\d{2})$/.exec(sValue);
        iYear = parseInt(aMatch[1], 10);
        iMonth = parseInt(aMatch[2], 10);
        iDay = parseInt(aMatch[3], 10);
        oDate = new Date(iYear, iMonth - 1, iDay);
        if (oDate.getFullYear() !== iYear || oDate.getMonth() !== iMonth - 1 || oDate.getDate() !== iDay) {
            return null;
        }
        return oDate;
    }

    function renderSubjectDateCalendar(oInput, oCalendar, oMonthDate) {
        var aDayLabels = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
        var oSelectedDate = parseSubjectIsoDate(oInput.value);
        var iYear = oMonthDate.getFullYear();
        var iMonth = oMonthDate.getMonth();
        var iFirstDay = new Date(iYear, iMonth, 1).getDay();
        var iOffset = (iFirstDay - iSubjectCalendarFirstDay + 7) % 7;
        var iDays = new Date(iYear, iMonth + 1, 0).getDate();
        var oHeader = document.createElement("div");
        var oPrev = document.createElement("button");
        var oNext = document.createElement("button");
        var oTitle = document.createElement("span");
        var oGrid = document.createElement("div");
        var iI;
        var oEmpty;
        var oDayLabel;
        var oDateButton;
        var oDate;
        var sDate;
        oCalendar.innerHTML = "";
        oCalendar._currentDate = new Date(iYear, iMonth, 1);
        oHeader.className = "subject-date-calendar-header";
        oPrev.type = "button";
        oPrev.className = "subject-date-calendar-nav";
        oPrev.textContent = "<";
        oNext.type = "button";
        oNext.className = "subject-date-calendar-nav";
        oNext.textContent = ">";
        oTitle.className = "subject-date-calendar-title";
        oTitle.textContent = iYear + "-" + padSubjectIsoDateNumber(iMonth + 1);
        oGrid.className = "subject-date-calendar-grid";
        oPrev.addEventListener("click", function () {
            renderSubjectDateCalendar(oInput, oCalendar, new Date(iYear, iMonth - 1, 1));
            positionSubjectDateCalendar(oInput, oCalendar);
        });
        oNext.addEventListener("click", function () {
            renderSubjectDateCalendar(oInput, oCalendar, new Date(iYear, iMonth + 1, 1));
            positionSubjectDateCalendar(oInput, oCalendar);
        });
        oHeader.appendChild(oPrev);
        oHeader.appendChild(oTitle);
        oHeader.appendChild(oNext);
        for (iI = 0; iI < 7; iI += 1) {
            oDayLabel = document.createElement("div");
            oDayLabel.className = "subject-date-calendar-day";
            oDayLabel.textContent = aDayLabels[(iSubjectCalendarFirstDay + iI) % 7];
            oGrid.appendChild(oDayLabel);
        }
        for (iI = 0; iI < iOffset; iI += 1) {
            oEmpty = document.createElement("span");
            oEmpty.className = "subject-date-calendar-empty";
            oGrid.appendChild(oEmpty);
        }
        for (iI = 1; iI <= iDays; iI += 1) {
            oDate = new Date(iYear, iMonth, iI);
            sDate = formatSubjectIsoDate(oDate);
            oDateButton = document.createElement("button");
            oDateButton.type = "button";
            oDateButton.className = "subject-date-calendar-date" + (oSelectedDate && formatSubjectIsoDate(oSelectedDate) == sDate ? " subject-date-calendar-selected" : "");
            oDateButton.setAttribute("data-date", sDate);
            oDateButton.textContent = "" + iI;
            oDateButton.addEventListener("click", function () {
                oInput.value = this.getAttribute("data-date") || "";
                oCalendar.style.display = "none";
            });
            oGrid.appendChild(oDateButton);
        }
        oCalendar.appendChild(oHeader);
        oCalendar.appendChild(oGrid);
    }

    function positionSubjectDateCalendar(oInput, oCalendar) {
        var oRect = oInput.getBoundingClientRect();
        var iWidth = oCalendar.offsetWidth || 238;
        var iHeight = oCalendar.offsetHeight || 220;
        var iLeft = Math.max(4, Math.min(oRect.left, window.innerWidth - iWidth - 4));
        var iTop = oRect.bottom + 2;
        if (iTop + iHeight > window.innerHeight - 4) {
            iTop = oRect.top - iHeight - 2;
        }
        if (iTop < 4) {
            iTop = 4;
        }
        oCalendar.style.left = iLeft + "px";
        oCalendar.style.top = iTop + "px";
    }

    function showSubjectDateCalendar(oInput, oCalendar) {
        var oDate = parseSubjectIsoDate(oInput.value) || oCalendar._currentDate || new Date();
        renderSubjectDateCalendar(oInput, oCalendar, new Date(oDate.getFullYear(), oDate.getMonth(), 1));
        if (!oCalendar.parentNode) {
            document.body.appendChild(oCalendar);
        }
        oCalendar.style.display = "";
        positionSubjectDateCalendar(oInput, oCalendar);
    }

    function appendSubjectDateField(oParent, sLabel, sName, sValue) {
        var oLabel = document.createElement("label");
        var oWrapper = document.createElement("div");
        var oInput = document.createElement("input");
        var oButton = document.createElement("button");
        var oCalendar = document.createElement("div");
        oLabel.textContent = sLabel;
        oWrapper.className = "subject-date-field";
        oInput.type = "text";
        oInput.name = sName;
        oInput.value = sValue || "";
        oInput.pattern = sSubjectDateInputPattern;
        oInput.placeholder = sSubjectDateInputFormat;
        oInput.maxLength = 10;
        oInput.autocomplete = "off";
        oInput.setAttribute("inputmode", "numeric");
        oInput.title = "Use " + sSubjectDateInputFormat + ".";
        oButton.type = "button";
        oButton.className = "subject-date-button";
        oButton.setAttribute("aria-label", "Open calendar");
        oButton.textContent = "\u25BE";
        oCalendar.className = "subject-date-calendar";
        oCalendar.style.display = "none";
        oCalendar.addEventListener("mousedown", function (oEvent) {
            oEvent.preventDefault();
        });
        oButton.addEventListener("click", function (oEvent) {
            oEvent.preventDefault();
            if (oCalendar.style.display == "none") {
                showSubjectDateCalendar(oInput, oCalendar);
            } else {
                oCalendar.style.display = "none";
            }
        });
        oInput.addEventListener("focus", function () {
            showSubjectDateCalendar(oInput, oCalendar);
        });
        oInput.addEventListener("input", function () {
            var oDate = parseSubjectIsoDate(oInput.value);
            if (oDate && oCalendar.style.display != "none") {
                renderSubjectDateCalendar(oInput, oCalendar, new Date(oDate.getFullYear(), oDate.getMonth(), 1));
                positionSubjectDateCalendar(oInput, oCalendar);
            }
        });
        oInput.addEventListener("keydown", function (oEvent) {
            if (oEvent.key == "Escape") {
                oCalendar.style.display = "none";
            }
        });
        oWrapper.addEventListener("focusout", function () {
            window.setTimeout(function () {
                if (!oWrapper.contains(document.activeElement) && !oCalendar.contains(document.activeElement)) {
                    oCalendar.style.display = "none";
                }
            }, 0)
        });
        oParent.addEventListener("scroll", function () {
            if (oCalendar.style.display != "none") {
                positionSubjectDateCalendar(oInput, oCalendar);
            }
        });
        window.addEventListener("resize", function () {
            if (oCalendar.style.display != "none") {
                positionSubjectDateCalendar(oInput, oCalendar);
            }
        });
        oWrapper.appendChild(oInput);
        oWrapper.appendChild(oButton);
        oParent.appendChild(oLabel);
        oParent.appendChild(oWrapper);
        return oInput;
    }

    function appendSubjectTextField(oParent, sLabel, sName, sValue, sType) {
        var oLabel = document.createElement("label");
        var oInput = document.createElement("input");
        if (sType == "date") {
            return appendSubjectDateField(oParent, sLabel, sName, sValue);
        }
        oLabel.textContent = sLabel;
        oInput.type = sType || "text";
        oInput.name = sName;
        oInput.value = sValue || "";
        oParent.appendChild(oLabel);
        oParent.appendChild(oInput);
        return oInput;
    }

    function appendSubjectCheckbox(oParent, sLabel, sName, blChecked) {
        var oLabel = document.createElement("label");
        var oInput = document.createElement("input");
        oLabel.className = "checkbox-label";
        oInput.type = "checkbox";
        oInput.name = sName;
        oInput.value = "1";
        oInput.checked = blChecked;
        oLabel.appendChild(oInput);
        oLabel.appendChild(document.createTextNode(sLabel));
        oParent.appendChild(oLabel);
        return oInput;
    }

    function appendSubjectSelect(oParent, sLabel, sName, sValue, aOptions) {
        var oLabel = document.createElement("label");
        var oSelect = document.createElement("select");
        var iI;
        oLabel.textContent = sLabel;
        oSelect.name = sName;
        for (iI = 0; iI < aOptions.length; iI += 1) {
            var oOption = document.createElement("option");
            oOption.value = aOptions[iI].value;
            oOption.textContent = aOptions[iI].label;
            if (aOptions[iI].value == sValue) {
                oOption.selected = true;
            }
            oSelect.appendChild(oOption);
        }
        oParent.appendChild(oLabel);
        oParent.appendChild(oSelect);
        return oSelect;
    }

    function setSubjectFieldVisible(oField, blVisible) {
        var oContainer = oField && oField.parentNode && oField.parentNode.className == "subject-date-field" ? oField.parentNode : oField;
        var oLabel = oContainer ? oContainer.previousSibling : null;
        if (oLabel) {
            oLabel.style.display = blVisible ? "" : "none";
        }
        if (oContainer) {
            oContainer.style.display = blVisible ? "" : "none";
        }
    }

    function appendAddressTypeSelect(oParent, sValue) {
        return appendSubjectSelect(oParent, "Type", "address_type", sValue || "main", [{
                    "value": "main",
                    "label": "Main"
                }, {
                    "value": "home",
                    "label": "Home"
                }, {
                    "value": "cottage",
                    "label": "Cottage"
                }, {
                    "value": "work",
                    "label": "Work"
                }, {
                    "value": "office",
                    "label": "Office"
                }, {
                    "value": "registered",
                    "label": "Registered"
                }, {
                    "value": "delivery",
                    "label": "Delivery"
                }, {
                    "value": "billing",
                    "label": "Billing"
                }, {
                    "value": "foreign",
                    "label": "Foreign"
                }, {
                    "value": "temporary",
                    "label": "Temporary"
                }, {
                    "value": "old",
                    "label": "Old"
                }, {
                    "value": "other",
                    "label": "Other"
                }
            ]);
    }

    function getSubjectCountrySpecialName(sCode, sLanguage) {
        if (sCode == "CS") {
            return (sLanguage || "").toLowerCase().indexOf("cs") === 0 ? "Československo" : "Czechoslovakia";
        }
        return "";
    }

    function findSubjectCountrySpecialAlias(sValue) {
        var sSearch = (sValue || "").replace(/^\s+|\s+$/g, "").toLowerCase();
        if (sSearch == "czechoslovakia" || sSearch == "československo") {
            return "CS";
        }
        return "";
    }

    function getSubjectCountryOptions() {
        var aOptions = [];
        var oNames = null;
        var sLanguage = document.documentElement ? (document.documentElement.lang || "") : "";
        var sName;
        var iI;
        if (aSubjectCountryOptions) {
            return aSubjectCountryOptions;
        }
        if (!sLanguage && window.navigator) {
            sLanguage = window.navigator.language || "";
        }
        if (window.Intl && typeof window.Intl.DisplayNames == "function") {
            try {
                oNames = new window.Intl.DisplayNames([sLanguage || "en"], {
                    "type": "region"
                });
            } catch (oException) {
                logAdminException(oException);
                oNames = null;
            }
        }
        for (iI = 0; iI < aSubjectCountryCodes.length; iI += 1) {
            sName = getSubjectCountrySpecialName(aSubjectCountryCodes[iI], sLanguage);
            if (sName === "") {
                try {
                    sName = oNames ? oNames.of(aSubjectCountryCodes[iI]) : aSubjectCountryCodes[iI];
                } catch (oException) {
                    logAdminException(oException);
                    sName = aSubjectCountryCodes[iI];
                }
            }
            aOptions.push({
                "code": aSubjectCountryCodes[iI],
                "name": sName || aSubjectCountryCodes[iI]
            })
        }
        aOptions.sort(function (aLeft, aRight) {
            return aLeft.name.localeCompare(aRight.name);
        });
        aSubjectCountryOptions = aOptions;
        return aSubjectCountryOptions;
    }

    function findSubjectCountryCode(sValue) {
        var sSearch = (sValue || "").replace(/^\s+|\s+$/g, "");
        var sUpper = sSearch.toUpperCase();
        var sSpecialCode = findSubjectCountrySpecialAlias(sSearch);
        var aOptions;
        var iI;
        if (sSearch === "") {
            return "";
        }
        if (sSpecialCode !== "") {
            return sSpecialCode;
        }
        if (/^[A-Z]{2}$/.test(sUpper) && aSubjectCountryCodes.indexOf(sUpper) !== -1) {
            return sUpper;
        }
        aOptions = getSubjectCountryOptions();
        for (iI = 0; iI < aOptions.length; iI += 1) {
            if (aOptions[iI].name.toLowerCase() == sSearch.toLowerCase()) {
                return aOptions[iI].code;
            }
        }
        return "";
    }

    function getSubjectCountryName(sCode) {
        var sCountryCode = (sCode || "").replace(/^\s+|\s+$/g, "").toUpperCase();
        var aOptions;
        var iI;
        if (!/^[A-Z]{2}$/.test(sCountryCode)) {
            return "";
        }
        aOptions = getSubjectCountryOptions();
        for (iI = 0; iI < aOptions.length; iI += 1) {
            if (aOptions[iI].code == sCountryCode) {
                return aOptions[iI].name;
            }
        }
        return "";
    }

    function ensureSubjectCountryList() {
        var oList = document.getElementById("nx-country-list");
        var aOptions;
        var oOption;
        var iI;
        if (oList) {
            return "nx-country-list";
        }
        if (!document.body) {
            return "";
        }
        oList = document.createElement("datalist");
        oList.id = "nx-country-list";
        aOptions = getSubjectCountryOptions();
        for (iI = 0; iI < aOptions.length; iI += 1) {
            oOption = document.createElement("option");
            oOption.value = aOptions[iI].name;
            oOption.label = aOptions[iI].code;
            oList.appendChild(oOption);
        }
        document.body.appendChild(oList);
        return "nx-country-list";
    }

    function appendSubjectCountryField(oParent, sValue) {
        var oInput = appendSubjectTextField(oParent, "Country", "country_name", getSubjectCountryName(sValue));
        var oHidden = document.createElement("input");
        var sListId = ensureSubjectCountryList();
        var updateCountryCode;
        oHidden.type = "hidden";
        oHidden.name = "country";
        oHidden.value = findSubjectCountryCode(sValue);
        oHidden._countryInput = oInput;
        if (sListId) {
            oInput.setAttribute("list", sListId);
        }
        oInput.autocomplete = "off";
        oInput.required = true;
        oInput.spellcheck = false;
        updateCountryCode = function () {
            var sCode = findSubjectCountryCode(oInput.value);
            oHidden.value = sCode;
            oHidden.setAttribute("data-country-invalid", oInput.value.replace(/^\s+|\s+$/g, "") !== "" && sCode === "" ? "1" : "0");
        };
        oInput.addEventListener("input", updateCountryCode);
        oInput.addEventListener("change", updateCountryCode);
        oInput.addEventListener("blur", function () {
            updateCountryCode();
            if (oHidden.value !== "") {
                oInput.value = getSubjectCountryName(oHidden.value);
            }
        });
        oParent.appendChild(oHidden);
        return oHidden;
    }

    function appendSubjectAddressField(oParent) {
        var oField = document.createElement("div");
        oField.className = "subject-address-field";
        oParent.appendChild(oField);
        return oField;
    }


    function isSubjectBirthNumberDigitCountValid(sValue) {
        var sDigits = (sValue || "").replace(/\D/g, "");
        return sDigits.length === 0 || sDigits.length == 9 || sDigits.length == 10;
    }


    function replaceSubjectRow(iSubjectId, sRowHtml) {
        var oCurrentRow = findAdminSubjectRowById(iSubjectId);
        var oBody = document.createElement("tbody");
        var oNewRow;
        var oTableBody;
        oBody.innerHTML = sRowHtml || "";
        oNewRow = oBody.querySelector("tr");
        if (oCurrentRow && oNewRow) {
            if ((" " + oCurrentRow.className + " ").indexOf(" nx-admin-row-modal ") !== -1) {
                addAdminClass(oNewRow, "nx-admin-row-modal");
            }
            if (oCurrentRow.getAttribute("data-selected") == "1") {
                oNewRow.setAttribute("data-selected", "1");
            }
            if (oCurrentRow.getAttribute("data-hover") == "1") {
                oNewRow.setAttribute("data-hover", "1");
            }
            oCurrentRow.parentNode.replaceChild(oNewRow, oCurrentRow);
            if (window.nxBindAdminTableRow) {
                window.nxBindAdminTableRow(oNewRow);
            }
        } else if (!oCurrentRow && oNewRow) {
            oTableBody = document.querySelector("#nx-subjects-table tbody, #nx-birthdays-table tbody, #nx-interactions-table tbody, #nx-contacts-table tbody");
            if (oTableBody) {
                oTableBody.appendChild(oNewRow);
                if (window.nxBindAdminTableRow) {
                    window.nxBindAdminTableRow(oNewRow);
                }
            } else {
                window.location.reload();
            }
        }
        refreshAdminTableFilter();
    }

    function removeSubjectRow(iSubjectId) {
        var oCurrentRow = findAdminSubjectRowById(iSubjectId);
        if (oCurrentRow) {
            oCurrentRow.parentNode.removeChild(oCurrentRow);
        }
        refreshAdminTableFilter();
    }

    window.nxReplaceSubjectRow = replaceSubjectRow;
    window.nxRemoveSubjectRow = removeSubjectRow;

    function updateSharedGroupElements(aGroup) {
        var aItems;
        var iI;
        var oValue;
        var oGroupList;
        var oOption;
        var sTimestampTooltip;
        if (!aGroup || !aGroup.group_id) {
            return;
        }
        sTimestampTooltip = aGroup.timestamp_tooltip || "";
        aItems = document.querySelectorAll(".nx-subject-group-item[data-group-id=\"" + aGroup.group_id + "\"]");
        for (iI = 0; iI < aItems.length; iI += 1) {
            aItems[iI].setAttribute("data-group-name", aGroup.name || "");
            if (sTimestampTooltip) {
                aItems[iI].setAttribute("data-timestamp-tooltip", sTimestampTooltip);
            }
            oValue = aItems[iI].querySelector(".nx-subject-item-value");
            if (oValue) {
                oValue.textContent = aGroup.name || "";
                if (sTimestampTooltip) {
                    oValue.title = sTimestampTooltip;
                }
            }
        }
        oGroupList = document.getElementById("nx-group-list");
        if (oGroupList && aGroup.name) {
            for (iI = 0; iI < oGroupList.options.length; iI += 1) {
                if (oGroupList.options[iI].value == aGroup.name) {
                    oOption = oGroupList.options[iI];
                    break;
                }
            }
            if (!oOption) {
                oOption = document.createElement("option");
                oOption.value = aGroup.name;
                oGroupList.appendChild(oOption);
            }
        }
    }

    function createSubjectDialog(sTitle, oSubjectRow) {
        var oDialogData = {};
        var closeOnEscape;
        oDialogData.dialog = document.createElement("div");
        oDialogData.form = document.createElement("form");
        oDialogData.header = document.createElement("div");
        oDialogData.title = document.createElement("strong");
        oDialogData.closeButton = document.createElement("button");
        oDialogData.error = document.createElement("p");
        oDialogData.actions = document.createElement("div");
        oDialogData.save = document.createElement("button");
        oDialogData.cancel = document.createElement("button");
        oDialogData.openedAt = 0;
        oDialogData.subjectRow = oSubjectRow || null;
        oDialogData.subjectId = oSubjectRow ? (oSubjectRow.getAttribute("data-subject-id") || "") : "";
        oDialogData.closed = false;
        oDialogData.getCurrentSubjectRow = function () {
            if (oDialogData.subjectRow && oDialogData.subjectRow.parentNode) {
                return oDialogData.subjectRow;
            }
            return findAdminSubjectRowById(oDialogData.subjectId) || oDialogData.subjectRow;
        };
        closeOnEscape = function (oEvent) {
            if (oEvent.key == "Escape") {
                oDialogData.close();
            }
        };
        oDialogData.close = function (blSaved) {
            if (oDialogData.closed) {
                return;
            }
            oDialogData.closed = true;
            document.removeEventListener("keydown", closeOnEscape);
            finishAdminSubjectRowEdit(oDialogData.getCurrentSubjectRow(), blSaved === true);
            closeSubjectDialog(oDialogData.dialog);
        };
        oDialogData.closeFromClick = function (oEvent) {
            if (oEvent) {
                oEvent.preventDefault();
                if (oEvent.stopImmediatePropagation) {
                    oEvent.stopImmediatePropagation();
                } else {
                    oEvent.stopPropagation();
                }
            }
            if (new Date().getTime() - oDialogData.openedAt < 300) {
                return;
            }
            oDialogData.close();
        };
        oDialogData.dialog.className = "confirm-dialog";
        oDialogData.form.className = "confirm-dialog-box subject-edit-dialog";
        oDialogData.form.method = "post";
        oDialogData.form.action = window.location.href;
        oDialogData.header.className = "confirm-dialog-header";
        oDialogData.title.textContent = sTitle;
        oDialogData.closeButton.type = "button";
        oDialogData.closeButton.className = "confirm-dialog-close";
        oDialogData.closeButton.setAttribute("aria-label", "Close");
        oDialogData.closeButton.textContent = "\u00D7";
        oDialogData.error.className = "subject-edit-error";
        oDialogData.error.style.display = "none";
        oDialogData.actions.className = "confirm-dialog-actions";
        oDialogData.save.type = "submit";
        oDialogData.save.className = "confirm-dialog-button";
        oDialogData.save.textContent = "Save";
        oDialogData.cancel.type = "button";
        oDialogData.cancel.className = "confirm-dialog-button";
        oDialogData.cancel.textContent = "Cancel";
        oDialogData.header.appendChild(oDialogData.title);
        oDialogData.header.appendChild(oDialogData.closeButton);
        oDialogData.form.appendChild(oDialogData.header);
        oDialogData.form.addEventListener("submit", function (oEvent) {
            if (new Date().getTime() - oDialogData.openedAt < 300) {
                oEvent.preventDefault();
                if (oEvent.stopImmediatePropagation) {
                    oEvent.stopImmediatePropagation();
                } else {
                    oEvent.stopPropagation();
                }
            }
        }, true);
        oDialogData.cancel.addEventListener("click", function (oEvent) {
            oDialogData.closeFromClick(oEvent);
        });
        oDialogData.closeButton.addEventListener("click", function (oEvent) {
            oDialogData.closeFromClick(oEvent);
        });
        enableAdminDialogDrag(oDialogData.dialog, oDialogData.form, oDialogData.header);

        document.addEventListener("keydown", closeOnEscape);
        return oDialogData;
    }

    function finishSubjectDialog(oDialogData, oFocus) {
        oDialogData.form.appendChild(oDialogData.error);
        oDialogData.actions.appendChild(oDialogData.save);
        oDialogData.actions.appendChild(oDialogData.cancel);
        oDialogData.form.appendChild(oDialogData.actions);
        oDialogData.dialog.appendChild(oDialogData.form);
        document.body.appendChild(oDialogData.dialog);
        oDialogData.openedAt = new Date().getTime();
        lockAdminModalScroll();
        beginAdminSubjectRowEdit(oDialogData.getCurrentSubjectRow());
        if (oFocus) {
            focusAdminElement(oFocus, true);
        }
    }

    function submitSubjectDialog(oDialogData, oData) {
        setAdminDialogError(oDialogData.error, "");
        oDialogData.save.disabled = true;
        appendAdminCsrfToken(oData);
        fetch(window.location.href, {
            "method": "POST",
            "body": oData,
            "credentials": "same-origin",
            "headers": getAdminAjaxHeaders()
        }).then(function (oResponse) {
            return oResponse.json();
        }).then(function (aData) {
            if (!aData || !aData.success) {
                setAdminDialogError(oDialogData.error, aData && aData.message ? aData.message : "Item could not be saved.");
                oDialogData.save.disabled = false;
                return;
            }
            if (aData.group) {
                updateSharedGroupElements(aData.group);
            }
            if (aData.reload_required) {
                window.location.reload();
                return;
            }
            if (aData.subject_deleted) {
                removeSubjectRow(aData.subject_id);
            } else {
                replaceSubjectRow(aData.subject_id, aData.row_html);
            }
            oDialogData.close(true);
        }).catch(function (oException) {
            logAdminException(oException);
            setAdminDialogError(oDialogData.error, "Item could not be saved.");
            oDialogData.save.disabled = false;
        })
    }

    function subjectPermissionKeySelected(aKeys, sPermissionKey) {
        if (!aKeys) {
            return false;
        }
        for (var iI = 0; iI < aKeys.length; iI += 1) {
            if (aKeys[iI] == sPermissionKey) {
                return true;
            }
        }
        return false;
    }

    function openSubjectDialog(aSubject, oSubjectRow, blNewSubject) {
        var oDialogData = createSubjectDialog(blNewSubject ? "New Subject" : "Edit Subject", blNewSubject ? null : (oSubjectRow || findAdminSubjectRowById(getSubjectValue(aSubject, "subject_id"))));
        var oType;
        var oActive;
        var oSubjectName;
        var oTitleBefore;
        var oFirstName;
        var oMiddleName;
        var oLastName;
        var oTitleAfter;
        var oBirthName;
        var oBirthNumber;
        var oBirthDate;
        var oDeathDate;
        var setSubjectTypeFields;
        oType = appendSubjectSelect(oDialogData.form, "Type", "subject_type", getSubjectValue(aSubject, "subject_type"), [{
                        "value": "person",
                        "label": "Person"
                    }, {
                        "value": "organization",
                        "label": "Organization"
                    }, {
                        "value": "service",
                        "label": "Service"
                    }, {
                        "value": "other",
                        "label": "Other"
                    }
                ]);
        if (!blNewSubject) {
            oType.disabled = true;
        }
        oActive = appendSubjectCheckbox(oDialogData.form, "Active", "is_active", getSubjectFlag(aSubject, "is_active"));
        oSubjectName = appendSubjectTextField(oDialogData.form, "Name", "subject_name_value", getSubjectValue(aSubject, "subject_name_value"));
        oTitleBefore = appendSubjectTextField(oDialogData.form, "Title Before", "title_before", getSubjectValue(aSubject, "title_before"));
        oFirstName = appendSubjectTextField(oDialogData.form, "First Name", "first_name", getSubjectValue(aSubject, "first_name"));
        oMiddleName = appendSubjectTextField(oDialogData.form, "Middle Name", "middle_name", getSubjectValue(aSubject, "middle_name"));
        oLastName = appendSubjectTextField(oDialogData.form, "Last Name", "last_name", getSubjectValue(aSubject, "last_name"));
        oTitleAfter = appendSubjectTextField(oDialogData.form, "Title After", "title_after", getSubjectValue(aSubject, "title_after"));
        oBirthName = appendSubjectTextField(oDialogData.form, "Birth Name", "birth_name", getSubjectValue(aSubject, "birth_name"));
        if (!blHideSubjectBirthNumber) {
            oBirthNumber = appendSubjectTextField(oDialogData.form, "Birth Number", "birth_number", getSubjectValue(aSubject, "birth_number"));
        }
        oBirthDate = appendSubjectTextField(oDialogData.form, "Birth Date", "birth_date", getSubjectValue(aSubject, "birth_date"), "date");
        oDeathDate = appendSubjectTextField(oDialogData.form, "Death Date", "death_date", getSubjectValue(aSubject, "death_date"), "date");
        setSubjectTypeFields = function () {
            var blPerson = oType.value == "person";
            setSubjectFieldVisible(oSubjectName, !blPerson);
            setSubjectFieldVisible(oTitleBefore, blPerson);
            setSubjectFieldVisible(oFirstName, blPerson);
            setSubjectFieldVisible(oMiddleName, blPerson);
            setSubjectFieldVisible(oLastName, blPerson);
            setSubjectFieldVisible(oTitleAfter, blPerson);
            setSubjectFieldVisible(oBirthName, blPerson);
            setSubjectFieldVisible(oBirthDate, blPerson);
            setSubjectFieldVisible(oDeathDate, blPerson);
            if (oBirthNumber) {
                setSubjectFieldVisible(oBirthNumber, blPerson);
            }
        };
        oType.addEventListener("change", setSubjectTypeFields);
        setSubjectTypeFields();
        oDialogData.form.addEventListener("submit", function (oEvent) {
            var oData = new FormData();
            var blPerson = oType.value == "person";
            oEvent.preventDefault();
            if (oBirthNumber && blPerson && !isSubjectBirthNumberDigitCountValid(oBirthNumber.value)) {
                setAdminDialogError(oDialogData.error, "Birth number must contain 9 or 10 digits.");
                oBirthNumber.focus();
                return;
            }
            var aPayload = {
                "subject_id": blNewSubject ? "" : getSubjectValue(aSubject, "subject_id"),
                "subject_type": oType.value,
                "is_active": oActive.checked ? "1" : "0",
                "subject_name_value": blPerson ? "" : oSubjectName.value,
                "title_before": blPerson ? oTitleBefore.value : "",
                "first_name": blPerson ? oFirstName.value : "",
                "middle_name": blPerson ? oMiddleName.value : "",
                "last_name": blPerson ? oLastName.value : "",
                "title_after": blPerson ? oTitleAfter.value : "",
                "birth_name": blPerson ? oBirthName.value : "",
                "birth_number": oBirthNumber && blPerson ? oBirthNumber.value : "",
                "birth_date": blPerson ? oBirthDate.value : "",
                "death_date": blPerson ? oDeathDate.value : ""
            };
            oData.append("action", blNewSubject ? "create_subject" : "update_subject");
            appendAdminEncodedJson(oData, "subject_payload", aPayload);
            submitSubjectDialog(oDialogData, oData);
        });
        finishSubjectDialog(oDialogData, oType.value == "person" ? oLastName : oSubjectName);
    }

    function openNewSubjectDialog() {
        openSubjectDialog({
            "subject_type": "person",
            "is_active": "1",
            "subject_name_value": "",
            "title_before": "",
            "first_name": "",
            "middle_name": "",
            "last_name": "",
            "title_after": "",
            "birth_name": "",
            "birth_date": "",
            "death_date": ""
        }, null, true)
    }

    function loadSubject(oButton) {
        var oData = new FormData();
        var aDummySubject = getSubjectButtonJson(oButton, "data-test-subject");
        if (aDummySubject) {
            openSubjectDialog(aDummySubject, getAdminSubjectRow(oButton), false);
            return;
        }
        oButton.disabled = true;
        oData.append("action", "get_subject");
        oData.append("subject_id", oButton.getAttribute("data-subject-id") || "");
        appendAdminCsrfToken(oData);
        fetch(window.location.href, {
            "method": "POST",
            "body": oData,
            "credentials": "same-origin",
            "headers": getAdminAjaxHeaders()
        }).then(function (oResponse) {
            return oResponse.json();
        }).then(function (aData) {
            oButton.disabled = false;
            if (!aData || !aData.success) {
                window.alert(aData && aData.message ? aData.message : "Subject could not be loaded.");
                return;
            }
            openSubjectDialog(aData.subject, getAdminSubjectRow(oButton), false);
        }).catch(function (oException) {
            logAdminException(oException);
            oButton.disabled = false;
            window.alert("Subject could not be loaded.");
        })
    }

    function openSubjectPortalDialog(aSubject, oSubjectRow) {
        var oDialogData = createSubjectDialog("Portal Account", oSubjectRow || findAdminSubjectRowById(getSubjectValue(aSubject, "subject_id")));
        var aPortalUser = aSubject && aSubject.portal_user ? aSubject.portal_user : null;
        var aPortalPermissions = aSubject && aSubject.portal_permissions ? aSubject.portal_permissions : [];
        var oPortalEnabled;
        var oPortalUserName;
        var oPortalPassword;
        var oPortalActive;
        var oPermissionBox;
        var oPermissionTitle;
        var oLabel;
        var oInput;
        var aPortalPermissionInputs = [];
        var setPortalFields;
        var iI;
        if (!aPortalUser) {
            aPortalUser = {};
        }
        oPortalEnabled = appendSubjectCheckbox(oDialogData.form, "Portal account", "portal_user_enabled", !!aPortalUser["has_user"]);
        oPortalUserName = appendSubjectTextField(oDialogData.form, "User Name", "portal_user_name", aPortalUser["user_name"] || "");
        if (aPortalUser["timestamp_tooltip"]) {
            oPortalUserName.title = aPortalUser["timestamp_tooltip"];
        }
        oPortalPassword = appendSubjectTextField(oDialogData.form, "New Password", "portal_password", "", "password");
        oPortalActive = appendSubjectCheckbox(oDialogData.form, "Active", "portal_user_active", aPortalUser["is_active"] !== 0 && aPortalUser["is_active"] !== "0");
        if (aPortalPermissions.length > 0) {
            oPermissionBox = document.createElement("div");
            oPermissionTitle = document.createElement("strong");
            oPermissionBox.className = "subject-portal-permissions";
            oPermissionTitle.textContent = "Direct Permissions";
            oPermissionBox.appendChild(oPermissionTitle);
            for (iI = 0; iI < aPortalPermissions.length; iI += 1) {
                oLabel = document.createElement("label");
                oLabel.className = "checkbox-label";
                oInput = document.createElement("input");
                oInput.type = "checkbox";
                oInput.className = "js-subject-portal-permission";
                oInput.value = aPortalPermissions[iI]["permission_key"] || "";
                oInput.checked = subjectPermissionKeySelected(aPortalUser["direct_permission_keys"], oInput.value);
                oLabel.appendChild(oInput);
                oLabel.appendChild(document.createTextNode(aPortalPermissions[iI]["name"] || oInput.value));
                oPermissionBox.appendChild(oLabel);
            }
            oDialogData.form.appendChild(oPermissionBox);
            aPortalPermissionInputs = oPermissionBox.querySelectorAll(".js-subject-portal-permission");
        }
        setPortalFields = function () {
            var blSupported = aSubject["subject_type"] == "person" || aSubject["subject_type"] == "service";
            var blEnabled;
            var iJ;
            oPortalEnabled.disabled = !blSupported;
            if (!blSupported) {
                oPortalEnabled.checked = false;
            }
            oDialogData.save.disabled = !blSupported;
            setAdminDialogError(oDialogData.error, blSupported ? "" : "Portal account can be set only for person or service.");
            blEnabled = blSupported && oPortalEnabled.checked;
            oPortalUserName.disabled = !blEnabled;
            oPortalPassword.disabled = !blEnabled;
            oPortalActive.disabled = !blEnabled;
            for (iJ = 0; iJ < aPortalPermissionInputs.length; iJ += 1) {
                aPortalPermissionInputs[iJ].disabled = !blEnabled;
            }
        };
        oPortalEnabled.addEventListener("change", setPortalFields);
        setPortalFields();
        oDialogData.form.addEventListener("submit", function (oEvent) {
            var oData = new FormData();
            var iJ;
            oEvent.preventDefault();
            oData.append("action", "update_subject_portal_user");
            oData.append("subject_id", getSubjectValue(aSubject, "subject_id"));
            oData.append("portal_user_enabled", oPortalEnabled.checked ? "1" : "0");
            appendAdminEncodedValue(oData, "portal_user_name", oPortalUserName.value);
            appendAdminEncodedValue(oData, "portal_password", oPortalPassword.value);
            oData.append("portal_user_active", oPortalActive.checked ? "1" : "0");
            for (iJ = 0; iJ < aPortalPermissionInputs.length; iJ += 1) {
                if (aPortalPermissionInputs[iJ].checked) {
                    oData.append("permissions[]", aPortalPermissionInputs[iJ].value);
                }
            }
            submitSubjectDialog(oDialogData, oData);
        });
        finishSubjectDialog(oDialogData, oPortalUserName);
    }

    function loadSubjectPortal(oButton) {
        var oData = new FormData();
        var aDummySubject = getSubjectButtonJson(oButton, "data-test-subject-portal");
        if (aDummySubject) {
            openSubjectPortalDialog(aDummySubject, getAdminSubjectRow(oButton));
            return;
        }
        oButton.disabled = true;
        oData.append("action", "get_subject_portal_user");
        oData.append("subject_id", oButton.getAttribute("data-subject-id") || "");
        appendAdminCsrfToken(oData);
        fetch(window.location.href, {
            "method": "POST",
            "body": oData,
            "credentials": "same-origin",
            "headers": getAdminAjaxHeaders()
        }).then(function (oResponse) {
            return oResponse.json();
        }).then(function (aData) {
            oButton.disabled = false;
            if (!aData || !aData.success) {
                window.alert(aData && aData.message ? aData.message : "Portal account could not be loaded.");
                return;
            }
            openSubjectPortalDialog(aData.subject, getAdminSubjectRow(oButton));
        }).catch(function (oException) {
            logAdminException(oException);
            oButton.disabled = false;
            window.alert("Portal account could not be loaded.");
        })
    }

    function openNicknameDialog(oItem, oSubjectRow, blNewNickname) {
        var oDialogData = createSubjectDialog(blNewNickname ? "New Nickname" : "Edit Nickname", blNewNickname ? oSubjectRow : getAdminSubjectRow(oItem));
        var sSubjectId = blNewNickname && oSubjectRow ? (oSubjectRow.getAttribute("data-subject-id") || "") : getSubjectItemValue(oItem, "data-subject-id");
        var oNickname = appendSubjectTextField(oDialogData.form, "Nickname", "nickname", getSubjectItemValue(oItem, "data-nickname"));
        var oContext = appendSubjectTextField(oDialogData.form, "Context", "context", getSubjectItemValue(oItem, "data-context"));
        var oNote = appendSubjectTextField(oDialogData.form, "Note", "note", getSubjectItemValue(oItem, "data-note"));
        var oPrimary = appendSubjectCheckbox(oDialogData.form, "Primary", "is_primary", getSubjectItemFlag(oItem, "data-primary"));
        var oActive = appendSubjectCheckbox(oDialogData.form, "Active", "is_active", blNewNickname ? true : getSubjectItemFlag(oItem, "data-active"));
        oDialogData.form.addEventListener("submit", function (oEvent) {
            var oData = new FormData();
            oEvent.preventDefault();
            oData.append("action", blNewNickname ? "create_subject_nickname" : "update_subject_nickname");
            if (blNewNickname) {
                oData.append("subject_id", sSubjectId);
            } else {
                oData.append("nickname_id", getSubjectItemValue(oItem, "data-nickname-id"));
            }
            appendAdminEncodedValue(oData, "nickname", oNickname.value);
            appendAdminEncodedValue(oData, "context", oContext.value);
            appendAdminEncodedValue(oData, "note", oNote.value);
            oData.append("is_primary", oPrimary.checked ? "1" : "0");
            oData.append("is_active", oActive.checked ? "1" : "0");
            submitSubjectDialog(oDialogData, oData);
        });
        finishSubjectDialog(oDialogData, oNickname);
    }

    function openAddressDialog(oItem, oSubjectRow, blNewAddress) {
        var oDialogData = createSubjectDialog(blNewAddress ? "New Address" : "Edit Address", blNewAddress ? oSubjectRow : getAdminSubjectRow(oItem));
        var sSubjectId = blNewAddress && oSubjectRow ? (oSubjectRow.getAttribute("data-subject-id") || "") : getSubjectItemValue(oItem, "data-subject-id");
        var oAddressFields = document.createElement("div");
        var oAddressType;
        var oOrganizationName;
        var oDepartmentName;
        var oCareOf;
        var oStreetName;
        var oHouseNumber;
        var oEvidenceNumber;
        var oOrientationNumber;
        var oOrientationSuffix;
        var oAddressLine2;
        var oCity;
        var oCityPart;
        var oPostalCode;
        var oRegion;
        var oCountry;
        var oNote;
        var oPrimary;
        var oActive;
        oDialogData.form.className += " subject-address-edit-dialog";
        oAddressFields.className = "subject-address-field-grid";
        oDialogData.form.appendChild(oAddressFields);
        oAddressType = appendAddressTypeSelect(appendSubjectAddressField(oAddressFields), blNewAddress ? "main" : getSubjectItemValue(oItem, "data-address-type"));
        oOrganizationName = appendSubjectTextField(appendSubjectAddressField(oAddressFields), "Organization Name", "organization_name", getSubjectItemValue(oItem, "data-organization-name"));
        oDepartmentName = appendSubjectTextField(appendSubjectAddressField(oAddressFields), "Department Name", "department_name", getSubjectItemValue(oItem, "data-department-name"));
        oCareOf = appendSubjectTextField(appendSubjectAddressField(oAddressFields), "Care Of", "care_of", getSubjectItemValue(oItem, "data-care-of"));
        oStreetName = appendSubjectTextField(appendSubjectAddressField(oAddressFields), "Street", "street_name", getSubjectItemValue(oItem, "data-street-name"));
        oHouseNumber = appendSubjectTextField(appendSubjectAddressField(oAddressFields), "House Number", "house_number", getSubjectItemValue(oItem, "data-house-number"));
        oEvidenceNumber = appendSubjectTextField(appendSubjectAddressField(oAddressFields), "Evidence Number", "evidence_number", getSubjectItemValue(oItem, "data-evidence-number"));
        oOrientationNumber = appendSubjectTextField(appendSubjectAddressField(oAddressFields), "Orientation Number", "orientation_number", getSubjectItemValue(oItem, "data-orientation-number"));
        oOrientationSuffix = appendSubjectTextField(appendSubjectAddressField(oAddressFields), "Orientation Suffix", "orientation_suffix", getSubjectItemValue(oItem, "data-orientation-suffix"));
        oAddressLine2 = appendSubjectTextField(appendSubjectAddressField(oAddressFields), "Address Line 2", "address_line2", getSubjectItemValue(oItem, "data-address-line2"));
        oCity = appendSubjectTextField(appendSubjectAddressField(oAddressFields), "City", "city", getSubjectItemValue(oItem, "data-city"));
        oCityPart = appendSubjectTextField(appendSubjectAddressField(oAddressFields), "City Part", "city_part", getSubjectItemValue(oItem, "data-city-part"));
        oPostalCode = appendSubjectTextField(appendSubjectAddressField(oAddressFields), "Postal Code", "postal_code", getSubjectItemValue(oItem, "data-postal-code"));
        oRegion = appendSubjectTextField(appendSubjectAddressField(oAddressFields), "Region", "region", getSubjectItemValue(oItem, "data-region"));
        oCountry = appendSubjectCountryField(appendSubjectAddressField(oAddressFields), getSubjectItemValue(oItem, "data-country"));
        oNote = appendSubjectTextField(appendSubjectAddressField(oAddressFields), "Note", "note", getSubjectItemValue(oItem, "data-note"));
        oPrimary = appendSubjectCheckbox(oDialogData.form, "Primary", "is_primary", getSubjectItemFlag(oItem, "data-primary"));
        oActive = appendSubjectCheckbox(oDialogData.form, "Active", "is_active", blNewAddress ? true : getSubjectItemFlag(oItem, "data-active"));
        oDialogData.form.addEventListener("submit", function (oEvent) {
            var oData = new FormData();
            oEvent.preventDefault();
            oData.append("action", blNewAddress ? "create_subject_address" : "update_subject_address");
            if (blNewAddress) {
                oData.append("subject_id", sSubjectId);
            } else {
                oData.append("address_id", getSubjectItemValue(oItem, "data-address-id"));
            }
            if (oCountry._countryInput) {
                oCountry.value = findSubjectCountryCode(oCountry._countryInput.value);
                oCountry.setAttribute("data-country-invalid", oCountry._countryInput.value.replace(/^\s+|\s+$/g, "") !== "" && oCountry.value === "" ? "1" : "0");
            }
            if (oCountry._countryInput && oCountry._countryInput.value.replace(/^\s+|\s+$/g, "") === "") {
                setAdminDialogError(oDialogData.error, "Country is required.");
                return;
            }
            if (oCountry.getAttribute("data-country-invalid") == "1") {
                setAdminDialogError(oDialogData.error, "Invalid country.");
                return;
            }
            appendAdminEncodedValue(oData, "address_type", oAddressType.value);
            appendAdminEncodedValue(oData, "organization_name", oOrganizationName.value);
            appendAdminEncodedValue(oData, "department_name", oDepartmentName.value);
            appendAdminEncodedValue(oData, "care_of", oCareOf.value);
            appendAdminEncodedValue(oData, "street_name", oStreetName.value);
            appendAdminEncodedValue(oData, "house_number", oHouseNumber.value);
            appendAdminEncodedValue(oData, "evidence_number", oEvidenceNumber.value);
            appendAdminEncodedValue(oData, "orientation_number", oOrientationNumber.value);
            appendAdminEncodedValue(oData, "orientation_suffix", oOrientationSuffix.value);
            appendAdminEncodedValue(oData, "address_line2", oAddressLine2.value);
            appendAdminEncodedValue(oData, "city", oCity.value);
            appendAdminEncodedValue(oData, "city_part", oCityPart.value);
            appendAdminEncodedValue(oData, "postal_code", oPostalCode.value);
            appendAdminEncodedValue(oData, "region", oRegion.value);
            appendAdminEncodedValue(oData, "country", oCountry.value);
            appendAdminEncodedValue(oData, "note", oNote.value);
            oData.append("is_primary", oPrimary.checked ? "1" : "0");
            oData.append("is_active", oActive.checked ? "1" : "0");
            submitSubjectDialog(oDialogData, oData);
        });
        finishSubjectDialog(oDialogData, oStreetName);
    }

    function openGroupDialog(oItem) {
        var oDialogData = createSubjectDialog("Edit Group", getAdminSubjectRow(oItem));
        var oSharedNote = document.createElement("p");
        var oName = appendSubjectTextField(oDialogData.form, "Name", "name", getSubjectItemValue(oItem, "data-group-name"));
        oSharedNote.textContent = "Name is shared by all subjects using this group.";
        oDialogData.form.insertBefore(oSharedNote, oDialogData.form.children[1]);
        oDialogData.form.addEventListener("submit", function (oEvent) {
            var oData = new FormData();
            oEvent.preventDefault();
            oData.append("action", "update_subject_group");
            oData.append("subject_id", getSubjectItemValue(oItem, "data-subject-id"));
            oData.append("group_id", getSubjectItemValue(oItem, "data-group-id"));
            appendAdminEncodedValue(oData, "name", oName.value);
            submitSubjectDialog(oDialogData, oData);
        });
        finishSubjectDialog(oDialogData, oName);
    }

    function openAddSubjectGroupDialog(oSubjectRow) {
        var oDialogData = createSubjectDialog("Assign Group", oSubjectRow);
        var sSubjectId = oSubjectRow ? (oSubjectRow.getAttribute("data-subject-id") || "") : "";
        var oName = appendSubjectTextField(oDialogData.form, "Name", "name", "");
        if (document.getElementById("nx-group-list")) {
            oName.setAttribute("list", "nx-group-list");
        }
        oDialogData.form.addEventListener("submit", function (oEvent) {
            var oData = new FormData();
            oEvent.preventDefault();
            oData.append("action", "create_subject_group");
            oData.append("subject_id", sSubjectId);
            appendAdminEncodedValue(oData, "name", oName.value);
            submitSubjectDialog(oDialogData, oData);
        });
        finishSubjectDialog(oDialogData, oName);
    }

    function openNoteDialog(oItem, oSubjectRow, blNewNote) {
        var oDialogData = createSubjectDialog(blNewNote ? "New Note" : "Edit Note", blNewNote ? oSubjectRow : getAdminSubjectRow(oItem));
        var sSubjectId = blNewNote && oSubjectRow ? (oSubjectRow.getAttribute("data-subject-id") || "") : getSubjectItemValue(oItem, "data-subject-id");
        var oNoteText = appendSubjectTextField(oDialogData.form, "Text", "note_text", getSubjectNoteText(oItem));
        var oPrimary = appendSubjectCheckbox(oDialogData.form, "Primary", "is_primary", blNewNote ? false : getSubjectItemFlag(oItem, "data-primary"));
        var oActive = appendSubjectCheckbox(oDialogData.form, "Active", "is_active", blNewNote ? true : getSubjectItemFlag(oItem, "data-active"));
        oDialogData.form.addEventListener("submit", function (oEvent) {
            var oData = new FormData();
            oEvent.preventDefault();
            oData.append("action", blNewNote ? "create_subject_note" : "update_subject_note");
            if (blNewNote) {
                oData.append("subject_id", sSubjectId);
            } else {
                oData.append("note_id", getSubjectItemValue(oItem, "data-note-id"));
            }
            appendAdminEncodedValue(oData, "note_text", oNoteText.value);
            oData.append("is_primary", oPrimary.checked ? "1" : "0");
            oData.append("is_active", oActive.checked ? "1" : "0");
            submitSubjectDialog(oDialogData, oData);
        });
        finishSubjectDialog(oDialogData, oNoteText);
    }

    function openDeleteDialog(sTitle, sMessage, aParams, oSubjectRow) {
        var oDialogData = createSubjectDialog(sTitle, oSubjectRow);
        var oText = document.createElement("p");
        oText.textContent = sMessage;
        oDialogData.save.textContent = "Yes";
        oDialogData.cancel.textContent = "No";
        oDialogData.form.appendChild(oText);
        oDialogData.form.addEventListener("submit", function (oEvent) {
            var oData = new FormData();
            var iI;
            oEvent.preventDefault();
            for (iI = 0; iI < aParams.length; iI += 1) {
                oData.append(aParams[iI].name, aParams[iI].value);
            }
            submitSubjectDialog(oDialogData, oData);
        });
        finishSubjectDialog(oDialogData, oDialogData.save);
    }

    function openDeleteSubjectDialog(oButton) {
        openDeleteDialog("Confirm Deletion", "Delete this subject?", [{
                    "name": "action",
                    "value": "delete_subject"
                }, {
                    "name": "subject_id",
                    "value": oButton.getAttribute("data-subject-id") || ""
                }
            ], getAdminSubjectRow(oButton));
    }

    function openDeleteContactDialog(oItem) {
        openDeleteDialog("Confirm Deletion", "Remove this contact from the subject?", [{
                    "name": "action",
                    "value": "delete_subject_contact"
                }, {
                    "name": "subject_contact_id",
                    "value": getSubjectItemValue(oItem, "data-subject-contact-id")
                }
            ], getAdminSubjectRow(oItem));
    }

    function openDeleteNicknameDialog(oItem) {
        openDeleteDialog("Confirm Deletion", "Delete this nickname?", [{
                    "name": "action",
                    "value": "delete_subject_nickname"
                }, {
                    "name": "nickname_id",
                    "value": getSubjectItemValue(oItem, "data-nickname-id")
                }
            ], getAdminSubjectRow(oItem));
    }

    function openDeleteAddressDialog(oItem) {
        openDeleteDialog("Confirm Deletion", "Delete this address?", [{
                    "name": "action",
                    "value": "delete_subject_address"
                }, {
                    "name": "address_id",
                    "value": getSubjectItemValue(oItem, "data-address-id")
                }
            ], getAdminSubjectRow(oItem));
    }

    function openDeleteGroupDialog(oItem) {
        openDeleteDialog("Confirm Deletion", "Remove this group from the subject?", [{
                    "name": "action",
                    "value": "delete_subject_group"
                }, {
                    "name": "subject_id",
                    "value": getSubjectItemValue(oItem, "data-subject-id")
                }, {
                    "name": "group_id",
                    "value": getSubjectItemValue(oItem, "data-group-id")
                }
            ], getAdminSubjectRow(oItem));
    }

    function openDeleteNoteDialog(oItem) {
        openDeleteDialog("Confirm Deletion", "Delete this note?", [{
                    "name": "action",
                    "value": "delete_subject_note"
                }, {
                    "name": "note_id",
                    "value": getSubjectItemValue(oItem, "data-note-id")
                }
            ], getAdminSubjectRow(oItem));
    }

    document.addEventListener("click", function (oEvent) {
        var oButton = oEvent.target.closest ? oEvent.target.closest(".js-add-subject, .js-add-subject-nickname, .js-add-subject-address, .js-add-subject-group, .js-add-subject-note, .js-edit-subject, .js-edit-subject-portal, .js-edit-subject-nickname, .js-edit-subject-address, .js-edit-subject-group, .js-edit-subject-note, .js-delete-subject, .js-delete-subject-contact, .js-delete-subject-nickname, .js-delete-subject-address, .js-delete-subject-group, .js-delete-subject-note") : null;
        if (oButton) {
            oEvent.preventDefault();
            oEvent.stopPropagation();
            if (oButton.className.indexOf("js-add-subject-nickname") !== -1) {
                openNicknameDialog(null, getAdminSubjectRow(oButton), true);
            } else if (oButton.className.indexOf("js-add-subject-address") !== -1) {
                openAddressDialog(null, getAdminSubjectRow(oButton), true);
            } else if (oButton.className.indexOf("js-add-subject-group") !== -1) {
                openAddSubjectGroupDialog(getAdminSubjectRow(oButton));
            } else if (oButton.className.indexOf("js-add-subject-note") !== -1) {
                openNoteDialog(null, getAdminSubjectRow(oButton), true);
            } else if (oButton.className.indexOf("js-add-subject") !== -1) {
                openNewSubjectDialog();
            } else if (oButton.className.indexOf("js-edit-subject-portal") !== -1) {
                loadSubjectPortal(oButton);
            } else if (oButton.className.indexOf("js-edit-subject-nickname") !== -1) {
                openNicknameDialog(oButton.closest(".nx-subject-nickname-item"), null, false);
            } else if (oButton.className.indexOf("js-edit-subject-address") !== -1) {
                openAddressDialog(oButton.closest(".nx-subject-address-item"), null, false);
            } else if (oButton.className.indexOf("js-edit-subject-group") !== -1) {
                openGroupDialog(oButton.closest(".nx-subject-group-item"));
            } else if (oButton.className.indexOf("js-edit-subject-note") !== -1) {
                openNoteDialog(oButton.closest(".nx-subject-note-item"), null, false);
            } else if (oButton.className.indexOf("js-delete-subject-contact") !== -1) {
                openDeleteContactDialog(oButton.closest(".nx-contact-item"));
            } else if (oButton.className.indexOf("js-delete-subject-nickname") !== -1) {
                openDeleteNicknameDialog(oButton.closest(".nx-subject-nickname-item"));
            } else if (oButton.className.indexOf("js-delete-subject-address") !== -1) {
                openDeleteAddressDialog(oButton.closest(".nx-subject-address-item"));
            } else if (oButton.className.indexOf("js-delete-subject-group") !== -1) {
                openDeleteGroupDialog(oButton.closest(".nx-subject-group-item"));
            } else if (oButton.className.indexOf("js-delete-subject-note") !== -1) {
                openDeleteNoteDialog(oButton.closest(".nx-subject-note-item"));
            } else if (oButton.className.indexOf("js-delete-subject") !== -1) {
                openDeleteSubjectDialog(oButton);
            } else {
                loadSubject(oButton);
            }
        }
    }, true)
});

document.addEventListener("DOMContentLoaded", function () {
    var aContactButtons = document.querySelectorAll(".js-add-subject-contact, .js-edit-subject-contact");
    var blCanEditContacts = aContactButtons.length > 0 && window.fetch && window.FormData;


    function getContactTypeLabel(sType) {
        if (sType == "landline") {
            return "Landline";
        }
        if (sType == "cell") {
            return "Cell";
        }
        if (sType == "fax") {
            return "Fax";
        }
        if (sType == "pager") {
            return "Pager";
        }
        if (sType == "email") {
            return "E-mail";
        }
        if (sType == "jabber") {
            return "Jabber";
        }
        if (sType == "icq") {
            return "ICQ";
        }
        if (sType == "skype") {
            return "Skype";
        }
        if (sType == "web") {
            return "Web";
        }
        if (sType == "signal") {
            return "Signal";
        }
        if (sType == "whatsapp") {
            return "WhatsApp";
        }
        if (sType == "telegram") {
            return "Telegram";
        }
        if (sType == "messenger") {
            return "Messenger";
        }
        if (sType == "viber") {
            return "Viber";
        }
        if (sType == "discord") {
            return "Discord";
        }
        if (sType == "matrix") {
            return "Matrix";
        }
        if (sType == "session") {
            return "Session";
        }
        if (sType == "twitter") {
            return "Twitter";
        }
        if (sType == "mastodon") {
            return "Mastodon";
        }
        if (sType == "bluesky") {
            return "Bluesky";
        }
        if (sType == "threads") {
            return "Threads";
        }
        if (sType == "facebook") {
            return "Facebook";
        }
        if (sType == "instagram") {
            return "Instagram";
        }
        if (sType == "tiktok") {
            return "TikTok";
        }
        if (sType == "linkedin") {
            return "LinkedIn";
        }
        if (sType == "github") {
            return "GitHub";
        }
        if (sType == "gitlab") {
            return "GitLab";
        }
        if (sType == "bitbucket") {
            return "Bitbucket";
        }
        if (sType == "stackoverflow") {
            return "Stack Overflow";
        }
        if (sType == "deviantart") {
            return "DeviantArt";
        }
        if (sType == "furaffinity") {
            return "Fur Affinity";
        }
        if (sType == "wikifur") {
            return "WikiFur";
        }
        if (sType == "furryamino") {
            return "Furry Amino";
        }
        if (sType == "sofurry") {
            return "SoFurry";
        }
        if (sType == "artstation") {
            return "ArtStation";
        }
        if (sType == "behance") {
            return "Behance";
        }
        if (sType == "dribbble") {
            return "Dribbble";
        }
        if (sType == "youtube") {
            return "YouTube";
        }
        if (sType == "twitch") {
            return "Twitch";
        }
        if (sType == "kick") {
            return "Kick";
        }
        if (sType == "vimeo") {
            return "Vimeo";
        }
        if (sType == "reddit") {
            return "Reddit";
        }
        if (sType == "lemmy") {
            return "Lemmy";
        }
        if (sType == "steam") {
            return "Steam";
        }
        if (sType == "xbox") {
            return "Xbox";
        }
        if (sType == "playstation") {
            return "PlayStation";
        }
        if (sType == "nintendo") {
            return "Nintendo";
        }
        if (sType == "npm") {
            return "npm";
        }
        if (sType == "pypi") {
            return "PyPI";
        }
        if (sType == "docker") {
            return "Docker";
        }
        if (sType == "codeberg") {
            return "Codeberg";
        }
        if (sType == "paypal") {
            return "PayPal";
        }
        if (sType == "revolut") {
            return "Revolut";
        }
        if (sType == "wise") {
            return "Wise";
        }
        if (sType == "bankaccount") {
            return "Bank Account";
        }
        if (sType == "orcid") {
            return "ORCID";
        }
        if (sType == "goodreads") {
            return "Goodreads";
        }
        if (sType == "lastfm") {
            return "Last.fm";
        }
        if (sType == "signaly") {
            return "Signaly";
        }
        return "Other";
    }

    function decodeContactUriPart(sValue) {
        try {
            return decodeURIComponent(sValue);
        } catch (oException) {
            logAdminException(oException);
            return sValue;
        }
    }

    function getYouTubeContactHref(sValue) {
        var sText = (sValue || "").replace(/^\s+|\s+$/g, "");
        var aMatch;
        var oUrl;
        var sPath;
        var blLooksLikeUrl = false;
        if (!sText) {
            return "";
        }
        if (/^\/\//.test(sText)) {
            sText = "https:" + sText;
        }
        blLooksLikeUrl = /^https?:\/\//i.test(sText) || /^www\./i.test(sText) || /^(youtube\.com|www\.youtube\.com)(?:[\/:?#].*)?$/i.test(sText) || /^[A-Za-z0-9.-]+\.[A-Za-z]{2,}[\/:?#].*$/i.test(sText);
        if (blLooksLikeUrl) {
            try {
                oUrl = new URL(/^https?:\/\//i.test(sText) ? sText : "https://" + sText);
                if (oUrl.hostname.toLowerCase() != "youtube.com" && oUrl.hostname.toLowerCase() != "www.youtube.com") {
                    return "";
                }
                sPath = oUrl.pathname.replace(/^\/+|\/+$/g, "");
                aMatch = sPath.match(/^(user|channel)\/([^\/]+)$/i);
                if (aMatch) {
                    return "https://www.youtube.com/" + aMatch[1].toLowerCase() + "/" + encodeURIComponent(decodeContactUriPart(aMatch[2]));
                }
                aMatch = sPath.match(/^@([^\/]+)$/);
                if (aMatch) {
                    return "https://www.youtube.com/@" + encodeURIComponent(decodeContactUriPart(aMatch[1]).replace(/^@+/, ""));
                }
                return sPath ? "https://www.youtube.com/" + sPath : "";
            } catch (oException) {
                logAdminException(oException);
                return "";
            }
        }
        aMatch = sText.match(/^(user|channel)\/([^\/?#]+)\/?$/i);
        if (aMatch) {
            return "https://www.youtube.com/" + aMatch[1].toLowerCase() + "/" + encodeURIComponent(decodeContactUriPart(aMatch[2]));
        }
        aMatch = sText.match(/^@([^\/?#]+)\/?$/);
        if (aMatch) {
            return "https://www.youtube.com/@" + encodeURIComponent(decodeContactUriPart(aMatch[1]));
        }
        if (/[\/:?#]/.test(sText)) {
            return "";
        }
        return "https://www.youtube.com/@" + encodeURIComponent(sText.replace(/^@+/, ""));
    }

    function getTelegramContactHost(sHost) {
        sHost = (sHost || "").toLowerCase().replace(/^www\./, "");
        if (sHost == "t.me" || sHost == "telegram.me" || sHost == "telegram.dog") {
            return sHost;
        }
        return "";
    }

    function getTelegramInviteToken(sValue, blRequireMarker) {
        var sText = decodeContactUriPart(sValue || "");
        var blMarked = false;
        if (sText.charAt(0) == "+") {
            sText = sText.substring(1);
            blMarked = true;
        } else if (sText.charAt(0) == " ") {
            sText = sText.substring(1);
            blMarked = true;
        }
        sText = sText.replace(/^\s+|\s+$/g, "");
        if (blRequireMarker && !blMarked) {
            return "";
        }
        return /^[A-Za-z0-9_-]{6,128}$/.test(sText) ? sText : "";
    }

    function getTelegramContactHrefFromPath(sHost, sPath) {
        var aSegments;
        var sHandle;
        var sKind;
        var sToken;
        sHost = getTelegramContactHost(sHost);
        sPath = (sPath || "").replace(/^\/+|\/+$/g, "");
        aSegments = sPath ? sPath.split("/") : [];
        if (!sHost || aSegments.length < 1 || aSegments.length > 2) {
            return "";
        }
        if (aSegments.length == 1) {
            sToken = getTelegramInviteToken(aSegments[0], true);
            if (sToken) {
                return "https://" + sHost + "/joinchat/" + encodeURIComponent(sToken);
            }
            sHandle = decodeContactUriPart(aSegments[0]).replace(/^@+/, "");
            return /^[A-Za-z0-9_]{5,32}$/.test(sHandle) ? "https://" + sHost + "/" + encodeURIComponent(sHandle) : "";
        }
        sKind = decodeContactUriPart(aSegments[0]).toLowerCase();
        if (sKind == "joinchat") {
            sToken = getTelegramInviteToken(aSegments[1], false);
            return sToken ? "https://" + sHost + "/joinchat/" + encodeURIComponent(sToken) : "";
        }
        if (sKind == "addstickers" || sKind == "setlanguage") {
            sToken = decodeContactUriPart(aSegments[1] || "").replace(/^\s+|\s+$/g, "");
            sToken = /^[A-Za-z0-9_]{1,128}$/.test(sToken) ? sToken : "";
            return sToken ? "https://" + sHost + "/" + sKind + "/" + encodeURIComponent(sToken) : "";
        }
        return "";
    }

    function getTelegramContactHref(sValue) {
        var sRawText = sValue || "";
        var sText = sRawText.replace(/^\s+|\s+$/g, "");
        var aMatch;
        var oUrl;
        var sHost;
        var sToken;
        var sHandle;
        if (!sText) {
            return "";
        }
        if (/^\/\//.test(sText)) {
            sText = "https:" + sText;
        }
        if (/^https?:\/\//i.test(sText) || /^(www\.)?(t\.me|telegram\.me|telegram\.dog)(?:[\/:?#].*)?$/i.test(sText)) {
            try {
                oUrl = new URL(/^https?:\/\//i.test(sText) ? sText : "https://" + sText);
                sHost = getTelegramContactHost(oUrl.hostname);
                return sHost ? getTelegramContactHrefFromPath(sHost, oUrl.pathname) : "";
            } catch (oException) {
                logAdminException(oException);
                return "";
            }
        }
        aMatch = sText.match(/^(joinchat|addstickers|setlanguage)\/(.+)$/i);
        if (aMatch) {
            return getTelegramContactHrefFromPath("t.me", aMatch[1] + "/" + aMatch[2]);
        }
        if (sRawText.charAt(0) == " " || sText.charAt(0) == "+" || /^%20/i.test(sText)) {
            sToken = getTelegramInviteToken(sRawText.charAt(0) == " " ? sRawText : sText, true);
            return sToken ? "https://t.me/joinchat/" + encodeURIComponent(sToken) : "";
        }
        sHandle = sText.replace(/^@+/, "");
        return /^[A-Za-z0-9_]{5,32}$/.test(sHandle) ? "https://t.me/" + encodeURIComponent(sHandle) : "";
    }

    function getIcqContactDisplayValue(sValue) {
        var sText = (sValue || "").replace(/^\s+|\s+$/g, "");
        var sDigits = "";
        var sResult;
        if (!sText) {
            return "";
        }
        if (/^[0-9]{5,9}$/.test(sText)) {
            sDigits = sText;
        } else if (/^[0-9]{1,3}(?:-[0-9]{3}){1,2}$/.test(sText)) {
            sDigits = sText.replace(/-/g, "");
        } else {
            return sValue || "";
        }
        if (sDigits.length < 5 || sDigits.length > 9) {
            return sValue || "";
        }
        if (sDigits.length < 7) {
            sResult = sDigits.slice(0, -3) + "-" + sDigits.slice(-3);
        } else {
            sResult = sDigits.slice(0, -6) + "-" + sDigits.slice(-6, -3) + "-" + sDigits.slice(-3);
        }
        return sText.indexOf("-") == -1 || sText == sResult ? sResult : (sValue || "");
    }

    function getSkypeContactDisplayValue(sValue) {
        var sText = (sValue || "").replace(/^\s+|\s+$/g, "");
        if (!sText) {
            return "";
        }
        if (/^[A-Za-z][A-Za-z0-9._,-]{5,31}$/.test(sText)) {
            return sText;
        }
        if (/^live:[A-Za-z0-9._-]{1,64}$/i.test(sText)) {
            return sText;
        }
        return sValue || "";
    }

    function isPhoneContactType(sType) {
        sType = getContactTypeKey(sType);
        return sType == "landline" || sType == "cell" || sType == "fax" || sType == "pager";
    }

    function getContactTypeKey(sType) {
        return (sType || "").replace(/^\s+|\s+$/g, "").toLowerCase();
    }

    function isKnownExternalContactType(sType) {
        sType = getContactTypeKey(sType);
        return sType == "wikifur" || sType == "web" || sType == "telegram" || sType == "messenger" || sType == "twitter" || sType == "mastodon" || sType == "bluesky" || sType == "threads" || sType == "facebook" || sType == "instagram" || sType == "tiktok" || sType == "linkedin" || sType == "github" || sType == "gitlab" || sType == "bitbucket" || sType == "stackoverflow" || sType == "deviantart" || sType == "furaffinity" || sType == "sofurry" || sType == "artstation" || sType == "behance" || sType == "dribbble" || sType == "youtube" || sType == "twitch" || sType == "kick" || sType == "vimeo" || sType == "reddit" || sType == "lemmy" || sType == "steam" || sType == "npm" || sType == "pypi" || sType == "docker" || sType == "codeberg" || sType == "paypal" || sType == "revolut" || sType == "orcid" || sType == "goodreads" || sType == "lastfm" || sType == "signaly";
    }

    function getWebContactHref(sValue) {
        var sText = (sValue || "").replace(/^\s+|\s+$/g, "");
        if (!sText) {
            return "";
        }
        if (/^https?:\/\//i.test(sText)) {
            return sText;
        }
        if (/^\/\//.test(sText)) {
            return "https:" + sText;
        }
        return "https://" + sText;
    }

    function getContactHref(sType, sValue) {
        var sText = (sValue || "").replace(/^\s+|\s+$/g, "");
        var sPhone;
        sType = getContactTypeKey(sType);
        if (isPhoneContactType(sType)) {
            sPhone = (sValue || "").replace(/[^0-9+]/g, "");
            return sPhone ? "tel:" + sPhone : "";
        }
        if (sType == "email") {
            return "mailto:" + (sValue || "");
        }
        if (sType == "jabber") {
            return sText ? "xmpp:" + sText : "";
        }
        if (sType == "matrix") {
            return sText ? "https://matrix.to/#/" + encodeURIComponent(sText) : "";
        }
        if (sType == "whatsapp") {
            sPhone = (sValue || "").replace(/[^0-9+]/g, "");
            return sPhone ? "https://wa.me/" + sPhone.replace(/^\+/, "") : "";
        }
        if (sType == "viber") {
            sPhone = (sValue || "").replace(/[^0-9+]/g, "");
            return sPhone ? "viber://chat?number=%2B" + sPhone.replace(/^\+/, "") : "";
        }
        if (sType == "telegram") {
            return getTelegramContactHref(sValue);
        }
        if (sType == "web") {
            return getWebContactHref(sValue);
        }
        if (sType == "youtube") {
            return getYouTubeContactHref(sValue);
        }
        if (isKnownExternalContactType(sType) && /^https?:\/\//i.test(sText)) {
            return sText;
        }
        return "";
    }

    function getContactDisplayValue(sType, sValue) {
        sType = getContactTypeKey(sType);
        if (isPhoneContactType(sType)) {
            return sValue || "";
        }
        if (sType == "web") {
            return getContactHref(sType, sValue) || (sValue || "");
        }
        if (sType == "youtube") {
            return getYouTubeContactHref(sValue) || (sValue || "");
        }
        if (sType == "telegram") {
            return getTelegramContactHref(sValue) || (sValue || "");
        }
        if (sType == "icq") {
            return getIcqContactDisplayValue(sValue);
        }
        if (sType == "skype") {
            return getSkypeContactDisplayValue(sValue);
        }
        return sValue || "";
    }

    function getContactLinkEmoji(sType) {
        sType = getContactTypeKey(sType);
        if (sType == "email") {
            return getAdminEmoji("contact-email");
        }
        if (sType == "landline") {
            return getAdminEmoji("contact-landline");
        }
        if (sType == "cell") {
            return getAdminEmoji("contact-cell");
        }
        if (sType == "fax") {
            return getAdminEmoji("contact-fax");
        }
        if (sType == "pager") {
            return getAdminEmoji("contact-pager");
        }
        if (sType == "web") {
            return getAdminEmoji("contact-web");
        }
        if (sType == "telegram") {
            return getAdminEmoji("contact-telegram");
        }
        if (sType == "whatsapp" || sType == "viber" || sType == "jabber" || sType == "matrix") {
            return getAdminEmoji("contact-message");
        }
        if (sType == "youtube") {
            return getAdminEmoji("contact-youtube");
        }
        if (isKnownExternalContactType(sType)) {
            return getAdminEmoji("contact-web");
        }
        return "";
    }

    function getContactLinkLabel(sType) {
        sType = getContactTypeKey(sType);
        if (sType == "email") {
            return "Send e-mail";
        }
        if (sType == "landline") {
            return "Call landline";
        }
        if (sType == "cell") {
            return "Call cell phone";
        }
        if (sType == "fax") {
            return "Call fax";
        }
        if (sType == "pager") {
            return "Call pager";
        }
        if (sType == "web") {
            return "Open web";
        }
        if (sType == "telegram") {
            return "Open Telegram";
        }
        if (sType == "whatsapp") {
            return "Open WhatsApp";
        }
        if (sType == "viber") {
            return "Open Viber";
        }
        if (sType == "jabber") {
            return "Open Jabber";
        }
        if (sType == "matrix") {
            return "Open Matrix";
        }
        if (sType == "youtube") {
            return "Open YouTube";
        }
        if (isKnownExternalContactType(sType)) {
            return "Open web";
        }
        return "";
    }


    function showContactCopyResult(oButton, blSuccess) {
        var oBox = oButton.querySelector ? oButton.querySelector(".nx-copy-action-box") : null;
        var sText = oButton.getAttribute("data-copy-text") || (oBox ? oBox.textContent : oButton.textContent);
        var sResultText = blSuccess ? getAdminEmoji("copy-success") : getAdminEmoji("copy-failure");
        if (oBox) {
            oBox.textContent = sResultText;
        } else {
            oButton.textContent = sResultText;
        }
        window.setTimeout(function () {
            if (oBox) {
                oBox.textContent = sText;
            } else {
                oButton.textContent = sText;
            }
        }, 1000)
    }

    function copyContactValue(oButton) {
        var oItem = oButton.closest ? oButton.closest(".nx-contact-item") : null;
        var sValue = oItem ? (oItem.getAttribute("data-contact-value") || "") : "";
        oButton.setAttribute("data-copy-text", oButton.getAttribute("data-copy-text") || oButton.textContent);
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(sValue).then(function () {
                showContactCopyResult(oButton, true);
            }).catch(function (oException) {
                logAdminException(oException);
                showContactCopyResult(oButton, copyAdminTextWithTextarea(sValue));
            });
            return;
        }
        showContactCopyResult(oButton, copyAdminTextWithTextarea(sValue));
    }

    function updateContactValueGroupTooltip(oItem) {
        var oGroup = oItem ? oItem.querySelector(".nx-contact-db-values") : null;
        var sTooltip = oItem ? (oItem.getAttribute("data-timestamp-tooltip") || "") : "";
        if (!oGroup) {
            return;
        }
        if (sTooltip) {
            oGroup.title = sTooltip;
        } else {
            oGroup.removeAttribute("title");
        }
    }

    function createContactCopyElement(sValue) {
        var oCopy;
        var oBox;
        if (!sValue) {
            return null;
        }
        oCopy = document.createElement("a");
        oCopy.className = "nx-contact-copy";
        oCopy.href = "#";
        oCopy.title = "Copy";
        oCopy.setAttribute("aria-label", "Copy");
        oBox = document.createElement("span");
        oBox.className = "nx-copy-action-box";
        oBox.textContent = getAdminEmoji("copy");
        oCopy.appendChild(oBox);
        return oCopy;
    }

    function createContactLinkElement(sType, sValue, blInvalid) {
        var sHref = getContactHref(sType, sValue);
        var sLabel = getContactLinkLabel(sType);
        var oLink;
        if (blInvalid) {
            return null;
        }
        if (!sHref) {
            return null;
        }
        oLink = document.createElement("a");
        oLink.className = "nx-contact-link";
        oLink.href = sHref;
        oLink.title = sLabel;
        oLink.setAttribute("aria-label", sLabel);
        oLink.textContent = getContactLinkEmoji(sType);
        if (/^https?:\/\//i.test(sHref)) {
            oLink.target = "_blank";
            oLink.rel = "noopener noreferrer";
        }
        oLink.addEventListener("click", function (oEvent) {
            oEvent.stopPropagation();
        });
        return oLink;
    }

    function updateContactValueAndLink(oItem, sType, sValue, blInvalidOverride) {
        var oValue = oItem.querySelector(".nx-contact-value");
        var aLinks = oItem.querySelectorAll(".nx-contact-copy, .nx-contact-link");
        var blInvalid = typeof blInvalidOverride == "boolean" ? blInvalidOverride : false;
        var oGroup;
        var oActionAnchor;
        var oParent;
        var oReference;
        var oNext;
        if (typeof blInvalidOverride != "boolean" && oValue && oValue.className.indexOf("nx-invalid-contact-value") !== -1) {
            blInvalid = true;
        }
        var oNewValue = document.createElement("span");
        var oNewCopy = createContactCopyElement(sValue);
        var oNewLink = createContactLinkElement(sType, sValue, blInvalid);
        oNewValue.className = blInvalid ? "nx-contact-value nx-invalid-contact-value" : "nx-contact-value";
        oNewValue.textContent = getContactDisplayValue(sType, sValue);
        for (var iI = 0; iI < aLinks.length; iI += 1) {
            aLinks[iI].parentNode.removeChild(aLinks[iI]);
        }
        if (oValue) {
            oValue.parentNode.replaceChild(oNewValue, oValue);
            oGroup = oNewValue.closest ? oNewValue.closest(".nx-contact-db-values") : null;
            oActionAnchor = oGroup || oNewValue;
            oParent = oActionAnchor.parentNode;
            oNext = oActionAnchor.nextSibling;
            if (oNext && oNext.nodeType === 3 && /^\s*$/.test(oNext.nodeValue)) {
                oNext.parentNode.removeChild(oNext);
            }
            oReference = oActionAnchor.nextSibling;
            if (oNewCopy) {
                oParent.insertBefore(oNewCopy, oReference);
            }
            if (oNewLink) {
                if (!oNewCopy) {
                    oParent.insertBefore(document.createTextNode(" "), oReference);
                }
                oParent.insertBefore(oNewLink, oReference);
            }
        }
        updateContactValueGroupTooltip(oItem);
    }

    document.addEventListener("click", function (oEvent) {
        var oButton = oEvent.target && oEvent.target.closest ? oEvent.target.closest(".nx-contact-copy") : null;
        var oLink;
        if (oButton) {
            oEvent.preventDefault();
            oEvent.stopPropagation();
            copyContactValue(oButton);
            return;
        }
        oLink = oEvent.target && oEvent.target.closest ? oEvent.target.closest(".nx-contact-link") : null;
        if (oLink) {
            oEvent.stopPropagation();
        }
    }, true);
    if (!blCanEditContacts) {
        return;
    }

    function appendTextField(oForm, sLabel, sName, sValue) {
        var oLabel = document.createElement("label");
        var oInput = document.createElement("input");
        oLabel.textContent = sLabel;
        oLabel.setAttribute("for", "contact-edit-" + sName);
        oInput.type = "text";
        oInput.id = "contact-edit-" + sName;
        oInput.name = sName;
        oInput.value = sValue || "";
        oForm.appendChild(oLabel);
        oForm.appendChild(oInput);
        return oInput;
    }

    function appendCheckbox(oForm, sLabel, sName, blChecked) {
        var oLabel = document.createElement("label");
        var oInput = document.createElement("input");
        oInput.type = "checkbox";
        oInput.name = sName;
        oInput.value = "1";
        oInput.checked = blChecked;
        oLabel.appendChild(oInput);
        oLabel.appendChild(document.createTextNode(sLabel));
        oForm.appendChild(oLabel);
        return oInput;
    }

    function getContactTypeOptions() {
        var oList = document.getElementById("nx-contact-type-list");
        return oList ? oList.getElementsByTagName("option") : [];
    }

    function findContactTypeOptionByType(sType) {
        var aOptions = getContactTypeOptions();
        for (var iI = 0; iI < aOptions.length; iI += 1) {
            if ((aOptions[iI].getAttribute("data-contact-type") || "") == (sType || "")) {
                return aOptions[iI];
            }
        }
        return null;
    }

    function appendContactTypeOptions(oSelect, sSelectedTypeId) {
        var aOptions = getContactTypeOptions();
        var oOption;
        var sOptionValue;
        var sOptionActive;
        for (var iI = 0; iI < aOptions.length; iI += 1) {
            sOptionValue = aOptions[iI].value || "";
            sOptionActive = aOptions[iI].getAttribute("data-contact-type-active") || "1";
            if (sOptionActive != "1" && sOptionValue != (sSelectedTypeId || "")) {
                continue;
            }
            oOption = document.createElement("option");
            oOption.value = sOptionValue;
            oOption.textContent = aOptions[iI].textContent || "";
            oOption.setAttribute("data-contact-type", aOptions[iI].getAttribute("data-contact-type") || "");
            oOption.setAttribute("data-contact-type-active", sOptionActive);
            oSelect.appendChild(oOption);
        }
    }

    function openAdminSelectPicker(oSelect) {
        if (oSelect && typeof oSelect.showPicker == "function") {
            try {
                oSelect.showPicker();
            } catch (oException) {
                logAdminException(oException);
            }
        }
    }

    function appendContactTypeSelect(oForm, sTypeId, sType) {
        var oLabel = document.createElement("label");
        var oSelect = document.createElement("select");
        var oOption;
        oLabel.textContent = "Type";
        oLabel.setAttribute("for", "contact-edit-contact-type");
        oSelect.id = "contact-edit-contact-type";
        oSelect.name = "contact_type_id";
        appendContactTypeOptions(oSelect, sTypeId);
        if (sTypeId) {
            oSelect.value = sTypeId;
        } else if (sType) {
            oOption = findContactTypeOptionByType(sType);
            if (oOption) {
                oSelect.value = oOption.value || "";
            }
        }
        oSelect.addEventListener("focus", function () {
            window.setTimeout(function () {
                openAdminSelectPicker(oSelect);
            }, 0)
        });
        oForm.appendChild(oLabel);
        oForm.appendChild(oSelect);
        return oSelect;
    }


    function clearContactRowFilterText(oItem) {
        var oRow = oItem && oItem.closest ? oItem.closest("tr") : null;
        if (oRow) {
            oRow._quickTableFilterText = null;
        }
    }

    function updateSubjectContactCell(oItem, aContact, blIsActive) {
        var oCell = oItem && oItem.closest ? oItem.closest(".nx-contact-subject-cell") : null;
        var blSubjectActive;
        if (!oCell) {
            return;
        }
        blSubjectActive = oCell.getAttribute("data-subject-active") != "0";
        oCell.setAttribute("data-contact-type-id", aContact.contact_type_id || "");
        oCell.setAttribute("data-contact-type", aContact.contact_type || "");
        oCell.setAttribute("data-contact-type-name", aContact.contact_type_label || getContactTypeLabel(aContact.contact_type));
        oCell.setAttribute("data-contact-value", aContact.contact_display_value || getContactDisplayValue(aContact.contact_type, aContact.contact_value));
        oCell.setAttribute("data-contact-id", aContact.contact_id || "");
        oCell.setAttribute("data-contact-note", aContact.note || "");
        oCell.setAttribute("data-contact-primary", parseInt(aContact.is_primary, 10) === 1 ? "1" : "0");
        oCell.setAttribute("data-contact-active", blIsActive ? "1" : "0");
        removeAdminClass(oCell, "nx-contact-subject-active");
        removeAdminClass(oCell, "nx-contact-subject-inactive");
        addAdminClass(oCell, blSubjectActive && blIsActive ? "nx-contact-subject-active" : "nx-contact-subject-inactive");
    }

    function updateContactElement(oItem, aContact) {
        var oType = oItem.querySelector(".nx-contact-type");
        var oNote = oItem.querySelector(".nx-contact-note");
        var oPrimary = oItem.querySelector(".nx-contact-primary");
        var oInactive = oItem.querySelector(".nx-contact-inactive-label");
        var blIsPrimary = parseInt(aContact.is_primary, 10) === 1;
        var blIsActive = parseInt(aContact.is_active, 10) === 1;
        var sContactTypeLabel = aContact.contact_type_label || getContactTypeLabel(aContact.contact_type);
        var sContactValue = aContact.contact_display_value || getContactDisplayValue(aContact.contact_type, aContact.contact_value);
        oItem.setAttribute("data-contact-type-id", aContact.contact_type_id || "");
        oItem.setAttribute("data-contact-type", aContact.contact_type || "");
        oItem.setAttribute("data-contact-type-name", sContactTypeLabel);
        oItem.setAttribute("data-contact-value", sContactValue);
        oItem.setAttribute("data-contact-id", aContact.contact_id || "");
        oItem.setAttribute("data-contact-note", aContact.note || "");
        oItem.setAttribute("data-contact-primary", blIsPrimary ? "1" : "0");
        oItem.setAttribute("data-contact-active", blIsActive ? "1" : "0");
        if (aContact.timestamp_tooltip) {
            oItem.setAttribute("data-timestamp-tooltip", aContact.timestamp_tooltip);
        } else {
            oItem.removeAttribute("data-timestamp-tooltip");
        }
        oItem.removeAttribute("title");
        removeAdminClass(oItem, "nx-contact-item-inactive");
        if (!blIsActive) {
            addAdminClass(oItem, "nx-contact-item-inactive");
        }
        if (oType) {
            oType.textContent = sContactTypeLabel;
            oType.removeAttribute("title");
        }
        updateContactValueAndLink(oItem, aContact.contact_type, sContactValue, false);
        if (oNote) {
            oNote.textContent = aContact.note ? " (" + aContact.note + ")" : "";
        }
        if (oPrimary) {
            oPrimary.textContent = blIsPrimary ? getAdminEmoji("primary") : "";
        }
        if (oInactive) {
            oInactive.textContent = blIsActive ? "" : getAdminEmoji("inactive");
        }
        updateSubjectContactCell(oItem, aContact, blIsActive);
        clearContactRowFilterText(oItem);
    }

    function updateSharedContactElements(aContact) {
        var aItems = document.querySelectorAll(".nx-contact-item[data-contact-id=\"" + aContact.contact_id + "\"]");
        var sContactTypeLabel = aContact.contact_type_label || getContactTypeLabel(aContact.contact_type);
        var sContactValue = aContact.contact_display_value || getContactDisplayValue(aContact.contact_type, aContact.contact_value);
        for (var iI = 0; iI < aItems.length; iI += 1) {
            var oType = aItems[iI].querySelector(".nx-contact-type");
            aItems[iI].setAttribute("data-contact-type-id", aContact.contact_type_id || "");
            aItems[iI].setAttribute("data-contact-type", aContact.contact_type || "");
            aItems[iI].setAttribute("data-contact-type-name", sContactTypeLabel);
            aItems[iI].setAttribute("data-contact-value", sContactValue);
            if (aContact.timestamp_tooltip) {
                aItems[iI].setAttribute("data-timestamp-tooltip", aContact.timestamp_tooltip);
            } else {
                aItems[iI].removeAttribute("data-timestamp-tooltip");
            }
            aItems[iI].removeAttribute("title");
            if (oType) {
                oType.textContent = sContactTypeLabel;
                oType.removeAttribute("title");
            }
            updateContactValueAndLink(aItems[iI], aContact.contact_type, sContactValue, false);
            clearContactRowFilterText(aItems[iI]);
        }
    }
    window.nxUpdateSharedContactElements = updateSharedContactElements;

    function openContactDialog(oItem, oSubjectRowParam, blNewContact) {
        var oDialog = document.createElement("div");
        var oForm = document.createElement("form");
        var oHeader = document.createElement("div");
        var oTitle = document.createElement("strong");
        var oClose = document.createElement("button");
        var oError = document.createElement("p");
        var oActions = document.createElement("div");
        var oSave = document.createElement("button");
        var oCancel = document.createElement("button");
        var oSubjectRow = blNewContact ? oSubjectRowParam : getAdminSubjectRow(oItem);
        var sSubjectId = oSubjectRow ? (oSubjectRow.getAttribute("data-subject-id") || "") : "";
        var blClosed = false;
        var closeOnEscape = function (oEvent) {
            if (oEvent.key == "Escape") {
                closeDialog();
            }
        };
        var getCurrentSubjectRow = function () {
            if (oSubjectRow && oSubjectRow.parentNode) {
                return oSubjectRow;
            }
            return findAdminSubjectRowById(sSubjectId) || oSubjectRow;
        };
        var closeDialog = function (blSaved) {
            if (blClosed) {
                return;
            }
            blClosed = true;
            document.removeEventListener("keydown", closeOnEscape);
            finishAdminSubjectRowEdit(getCurrentSubjectRow(), blSaved === true);
            closeAdminDialogElement(oDialog);
        };
        oDialog.className = "confirm-dialog";
        oForm.className = "confirm-dialog-box contact-edit-dialog";
        oForm.method = "post";
        oForm.action = window.location.href;
        oHeader.className = "confirm-dialog-header";
        oTitle.textContent = blNewContact ? "New Contact" : "Edit Contact";
        oClose.type = "button";
        oClose.className = "confirm-dialog-close";
        oClose.setAttribute("aria-label", "Close");
        oClose.textContent = "\u00D7";
        oError.className = "contact-edit-error";
        oError.style.display = "none";
        oActions.className = "confirm-dialog-actions";
        oSave.type = "submit";
        oSave.className = "confirm-dialog-button";
        oSave.textContent = "Save";
        oCancel.type = "button";
        oCancel.className = "confirm-dialog-button";
        oCancel.textContent = "Cancel";
        oHeader.appendChild(oTitle);
        oHeader.appendChild(oClose);
        oForm.appendChild(oHeader);
        enableAdminDialogDrag(oDialog, oForm, oHeader);
        var oSharedNote = document.createElement("p");
        oSharedNote.textContent = "Shared contact values used by other subjects are preserved.";
        oForm.appendChild(oSharedNote);
        var oType = appendContactTypeSelect(oForm, blNewContact ? "" : (oItem.getAttribute("data-contact-type-id") || ""), blNewContact ? "cell" : (oItem.getAttribute("data-contact-type") || ""));
        var oValue = appendTextField(oForm, "Value", "contact_value", blNewContact ? "" : (oItem.getAttribute("data-contact-value") || ""));
        var oNote = appendTextField(oForm, "Note", "note", blNewContact ? "" : (oItem.getAttribute("data-contact-note") || ""));
        var oPrimary = appendCheckbox(oForm, "Primary", "is_primary", blNewContact ? false : oItem.getAttribute("data-contact-primary") == "1");
        var oActive = appendCheckbox(oForm, "Active", "is_active", blNewContact ? true : oItem.getAttribute("data-contact-active") == "1");
        oForm.appendChild(oError);
        oActions.appendChild(oSave);
        oActions.appendChild(oCancel);
        oForm.appendChild(oActions);
        oDialog.appendChild(oForm);
        document.body.appendChild(oDialog);
        lockAdminModalScroll();
        beginAdminSubjectRowEdit(oSubjectRow);
        focusAdminElement(oValue, true);
        oCancel.addEventListener("click", function () {
            closeDialog();
        });
        oClose.addEventListener("click", function () {
            closeDialog();
        });
        oForm.addEventListener("submit", function (oEvent) {
            var oData = new FormData();
            oEvent.preventDefault();
            setAdminDialogError(oError, "");
            oSave.disabled = true;
            if (!oType.value) {
                setAdminDialogError(oError, "Select a contact type from the list.");
                oSave.disabled = false;
                focusAdminElement(oType, false);
                return;
            }
            oData.append("action", blNewContact ? "create_contact" : "update_contact");
            if (blNewContact) {
                oData.append("subject_id", sSubjectId);
            } else {
                oData.append("subject_contact_id", oItem.getAttribute("data-subject-contact-id") || "");
            }
            oData.append("contact_type_id", oType.value);
            appendAdminEncodedValue(oData, "contact_value", oValue.value);
            appendAdminEncodedValue(oData, "note", oNote.value);
            oData.append("is_primary", oPrimary.checked ? "1" : "0");
            oData.append("is_active", oActive.checked ? "1" : "0");
            appendAdminCsrfToken(oData);
            fetch(window.location.href, {
                "method": "POST",
                "body": oData,
                "credentials": "same-origin",
                "headers": getAdminAjaxHeaders()
            }).then(function (oResponse) {
                return oResponse.json();
            }).then(function (aData) {
                if (!aData || !aData.success) {
                    setAdminDialogError(oError, aData && aData.message ? aData.message : "Contact could not be saved.");
                    oSave.disabled = false;
                    return;
                }
                if (aData.reload_required) {
                    window.location.reload();
                    return;
                }
                if (aData.contact) {
                    updateSharedContactElements(aData.contact);
                    if (oItem) {
                        updateContactElement(oItem, aData.contact);
                    }
                    refreshAdminTableFilter();
                }
                if (aData.subject_deleted && window.nxRemoveSubjectRow) {
                    window.nxRemoveSubjectRow(aData.subject_id);
                    closeDialog(true);
                    return;
                }
                if (aData.row_html && window.nxReplaceSubjectRow) {
                    window.nxReplaceSubjectRow(aData.subject_id, aData.row_html);
                }
                closeDialog(true);
            }).catch(function (oException) {
                logAdminException(oException);
                setAdminDialogError(oError, "Contact could not be saved.");
                oSave.disabled = false;
            })
        });

        document.addEventListener("keydown", closeOnEscape);
    }

    document.addEventListener("click", function (oEvent) {
        var oButton = oEvent.target.closest ? oEvent.target.closest(".js-add-subject-contact, .js-edit-subject-contact") : null;
        if (oButton) {
            oEvent.preventDefault();
            oEvent.stopPropagation();
            if (oButton.className.indexOf("js-add-subject-contact") !== -1) {
                openContactDialog(null, getAdminSubjectRow(oButton), true);
            } else {
                openContactDialog(oButton.closest(".nx-contact-item"), null, false);
            }
        }
    }, true)
});

document.addEventListener("DOMContentLoaded", function () {
    if (!document.getElementById("nx-contacts-table")) {
        return;
    }
    var oEditDialog = document.getElementById("shared-contact-edit-dialog");
    var oDeleteDialog = document.getElementById("shared-contact-delete-dialog");
    var oEditForm = oEditDialog ? oEditDialog.querySelector("form") : null;
    var oDeleteForm = oDeleteDialog ? oDeleteDialog.querySelector("form") : null;
    var oEditError = oEditDialog ? oEditDialog.querySelector(".contact-edit-error") : null;
    var oDeleteError = oDeleteDialog ? oDeleteDialog.querySelector(".contact-edit-error") : null;
    var oCurrentContactCell = null;
    var closeOnEscape = function (oEvent) {
        if (oEvent.key == "Escape") {
            closeSharedContactDialog(false);
        }
    };

    function getContactCell(oButton) {
        return oButton && oButton.closest ? oButton.closest(".nx-contact-cell") : null;
    }

    function getActionButton(oTarget) {
        if (oTarget && oTarget.nodeType == 3) {
            oTarget = oTarget.parentNode;
        }
        return oTarget && oTarget.closest ? oTarget.closest(".js-edit-shared-contact, .js-delete-shared-contact, .js-shared-contact-edit-close, .js-shared-contact-edit-cancel, .js-shared-contact-delete-close, .js-shared-contact-delete-cancel") : null;
    }

    function getField(oForm, sName) {
        return oForm ? oForm.querySelector("[name=\"" + sName + "\"]") : null;
    }

    function setDialogError(oError, sMessage) {
        if (!oError) {
            return;
        }
        oError.textContent = sMessage || "";
        oError.hidden = !sMessage;
    }

    function openDialog(oDialog, oFocus) {
        if (!oDialog) {
            return;
        }
        oDialog.hidden = false;
        lockAdminModalScroll();
        document.addEventListener("keydown", closeOnEscape);
        if (oFocus) {
            focusAdminElement(oFocus, true);
        }
    }

    function hideDialog(oDialog) {
        if (oDialog && !oDialog.hidden) {
            oDialog.hidden = true;
            unlockAdminModalScroll();
        }
    }

    function closeSharedContactDialog(blSaved) {
        if (oCurrentContactCell) {
            finishAdminSubjectRowEdit(oCurrentContactCell.parentNode, blSaved === true);
        }
        document.removeEventListener("keydown", closeOnEscape);
        hideDialog(oEditDialog);
        hideDialog(oDeleteDialog);
        setDialogError(oEditError, "");
        setDialogError(oDeleteError, "");
        oCurrentContactCell = null;
    }

    function openSharedContactEdit(oCell) {
        var oContactId = getField(oEditForm, "contact_id");
        var oContactType = getField(oEditForm, "contact_type_id");
        var oContactValue = getField(oEditForm, "contact_value");
        oCurrentContactCell = oCell;
        setDialogError(oEditError, "");
        if (oContactId) {
            oContactId.value = oCell ? (oCell.getAttribute("data-contact-id") || "") : "";
        }
        if (oContactType) {
            oContactType.value = oCell ? (oCell.getAttribute("data-contact-type-id") || "") : "";
        }
        if (oContactValue) {
            oContactValue.value = oCell ? (oCell.getAttribute("data-contact-value") || "") : "";
        }
        beginAdminSubjectRowEdit(oCell ? oCell.parentNode : null);
        openDialog(oEditDialog, oContactValue);
    }

    function openSharedContactDelete(oCell) {
        var oContactId = getField(oDeleteForm, "contact_id");
        oCurrentContactCell = oCell;
        setDialogError(oDeleteError, "");
        if (oContactId) {
            oContactId.value = oCell ? (oCell.getAttribute("data-contact-id") || "") : "";
        }
        beginAdminSubjectRowEdit(oCell ? oCell.parentNode : null);
        openDialog(oDeleteDialog, oDeleteForm ? oDeleteForm.querySelector(".js-shared-contact-delete-cancel") : null);
    }

    function submitSharedContactForm(oForm, sAction, oError) {
        var oData = new FormData();
        var oContactId = getField(oForm, "contact_id");
        var oContactType = getField(oForm, "contact_type_id");
        var oContactValue = getField(oForm, "contact_value");
        setDialogError(oError, "");
        oData.append("action", sAction);
        oData.append("contact_id", oContactId ? oContactId.value : "");
        if (oContactType) {
            oData.append("contact_type_id", oContactType.value);
        }
        if (oContactValue) {
            appendAdminEncodedValue(oData, "contact_value", oContactValue.value);
        }
        appendAdminCsrfToken(oData);
        fetch(window.location.href, {
            "method": "POST",
            "body": oData,
            "credentials": "same-origin",
            "headers": getAdminAjaxHeaders()
        }).then(function (oResponse) {
            return oResponse.json();
        }).then(function (aData) {
            if (!aData || !aData.success) {
                setDialogError(oError, aData && aData.message ? aData.message : "Contact could not be saved.");
                return;
            }
            if (aData.reload_required) {
                window.location.reload();
                return;
            }
            if (aData.contact) {
                if (window.nxUpdateSharedContactElements) {
                    window.nxUpdateSharedContactElements(aData.contact);
                }
                refreshAdminTableFilter();
            }
            closeSharedContactDialog(true);
        }).catch(function (oException) {
            logAdminException(oException);
            setDialogError(oError, "Contact could not be saved.");
        });
    }

    if (oEditForm) {
        oEditForm.addEventListener("submit", function (oEvent) {
            oEvent.preventDefault();
            submitSharedContactForm(oEditForm, "update_shared_contact", oEditError);
        });
    }
    if (oDeleteForm) {
        oDeleteForm.addEventListener("submit", function (oEvent) {
            oEvent.preventDefault();
            submitSharedContactForm(oDeleteForm, "delete_shared_contact", oDeleteError);
        });
    }
    document.addEventListener("click", function (oEvent) {
        var oButton = getActionButton(oEvent.target);
        if (!oButton) {
            return;
        }
        oEvent.preventDefault();
        oEvent.stopPropagation();
        if (oButton.className.indexOf("js-edit-shared-contact") !== -1) {
            openSharedContactEdit(getContactCell(oButton));
        } else if (oButton.className.indexOf("js-delete-shared-contact") !== -1) {
            openSharedContactDelete(getContactCell(oButton));
        } else {
            closeSharedContactDialog(false);
        }
    }, true);
});

document.addEventListener("DOMContentLoaded", function () {
    if (!document.getElementById("nx-addresses-table")) {
        return;
    }
    var aAddressFields = ["organization_name", "department_name", "care_of", "street_name", "house_number", "evidence_number", "orientation_number", "orientation_suffix", "address_line2", "city", "city_part", "postal_code", "region", "country"];
    var aSubjectAddressFields = ["address_type"].concat(aAddressFields).concat(["note"]);
    var oEditDialog = document.getElementById("shared-address-edit-dialog");
    var oDeleteDialog = document.getElementById("shared-address-delete-dialog");
    var oSubjectEditDialog = document.getElementById("subject-address-edit-dialog");
    var oSubjectDeleteDialog = document.getElementById("subject-address-delete-dialog");
    var oEditForm = oEditDialog ? oEditDialog.querySelector("form") : null;
    var oDeleteForm = oDeleteDialog ? oDeleteDialog.querySelector("form") : null;
    var oSubjectEditForm = oSubjectEditDialog ? oSubjectEditDialog.querySelector("form") : null;
    var oSubjectDeleteForm = oSubjectDeleteDialog ? oSubjectDeleteDialog.querySelector("form") : null;
    var oEditError = oEditDialog ? oEditDialog.querySelector(".subject-edit-error") : null;
    var oDeleteError = oDeleteDialog ? oDeleteDialog.querySelector(".subject-edit-error") : null;
    var oSubjectEditError = oSubjectEditDialog ? oSubjectEditDialog.querySelector(".subject-edit-error") : null;
    var oSubjectDeleteError = oSubjectDeleteDialog ? oSubjectDeleteDialog.querySelector(".subject-edit-error") : null;
    var oCurrentAddressCell = null;
    var oCurrentSubjectCell = null;
    var closeOnEscape = function (oEvent) {
        if (oEvent.key == "Escape") {
            closeSharedAddressDialog(false);
            closeSubjectAddressDialog(false);
        }
    };

    function getAddressCell(oButton) {
        return oButton && oButton.closest ? oButton.closest(".nx-address-cell") : null;
    }

    function getSubjectCell(oButton) {
        return oButton && oButton.closest ? oButton.closest(".nx-address-subject-cell") : null;
    }

    function getActionButton(oTarget) {
        if (oTarget && oTarget.nodeType == 3) {
            oTarget = oTarget.parentNode;
        }
        return oTarget && oTarget.closest ? oTarget.closest(".js-edit-shared-address, .js-delete-shared-address, .js-shared-address-edit-close, .js-shared-address-edit-cancel, .js-shared-address-delete-close, .js-shared-address-delete-cancel, .js-edit-subject-address-local, .js-delete-subject-address-local, .js-subject-address-edit-close, .js-subject-address-edit-cancel, .js-subject-address-delete-close, .js-subject-address-delete-cancel") : null;
    }

    function getField(oForm, sName) {
        return oForm ? oForm.querySelector("[name=\"" + sName + "\"]") : null;
    }

    function getAddressValue(oCell, sName) {
        var sAttribute = sName.replace(/_/g, "-");
        if (sName == "country") {
            return oCell.getAttribute("data-country-name") || oCell.getAttribute("data-country") || "";
        }
        return oCell.getAttribute("data-" + sAttribute) || "";
    }

    function setDialogError(oError, sMessage) {
        if (!oError) {
            return;
        }
        oError.textContent = sMessage || "";
        oError.hidden = !sMessage;
    }

    function setAddressRowsModal(oCell, blModal) {
        var oRow;
        var iRowIndex;
        var iRowspan;
        var iI;
        if (!oCell) {
            return;
        }
        oRow = oCell.parentNode;
        iRowIndex = oRow ? oRow.sectionRowIndex : -1;
        iRowspan = parseInt(oCell.getAttribute("rowspan") || "1", 10);
        for (iI = 0; oRow && oRow.parentNode && iI < iRowspan; iI += 1) {
            if (blModal) {
                addAdminClass(oRow.parentNode.rows[iRowIndex + iI], "nx-admin-row-modal");
            } else {
                removeAdminClass(oRow.parentNode.rows[iRowIndex + iI], "nx-admin-row-modal");
            }
        }
    }

    function setSubjectRowModal(oCell, blModal) {
        var oRow;
        if (!oCell) {
            return;
        }
        oRow = oCell.parentNode;
        if (!oRow) {
            return;
        }
        if (blModal) {
            addAdminClass(oRow, "nx-admin-row-modal");
        } else {
            removeAdminClass(oRow, "nx-admin-row-modal");
        }
    }

    function openSharedAddressEdit(oCell) {
        var oField;
        var iI;
        if (!oEditDialog || !oEditForm || !oCell) {
            return;
        }
        oCurrentAddressCell = oCell;
        getField(oEditForm, "address_match").value = oCell.getAttribute("data-address-match") || "";
        for (iI = 0; iI < aAddressFields.length; iI += 1) {
            oField = getField(oEditForm, aAddressFields[iI]);
            if (oField) {
                oField.value = getAddressValue(oCell, aAddressFields[iI]);
            }
        }
        setDialogError(oEditError, "");
        oEditDialog.hidden = false;
        lockAdminModalScroll();
        setAddressRowsModal(oCell, true);

        document.addEventListener("keydown", closeOnEscape);
        focusAdminElement(getField(oEditForm, "street_name"), true);
    }

    function openSharedAddressDelete(oCell) {
        if (!oDeleteDialog || !oDeleteForm || !oCell) {
            return;
        }
        oCurrentAddressCell = oCell;
        getField(oDeleteForm, "address_match").value = oCell.getAttribute("data-address-match") || "";
        setDialogError(oDeleteError, "");
        oDeleteDialog.hidden = false;
        lockAdminModalScroll();
        setAddressRowsModal(oCell, true);

        document.addEventListener("keydown", closeOnEscape);
        focusAdminElement(oDeleteForm.querySelector(".js-shared-address-delete-cancel"));
    }

    function closeSharedAddressDialog(blSaved) {
        if (oEditDialog && !oEditDialog.hidden) {
            oEditDialog.hidden = true;
            unlockAdminModalScroll();
        }
        if (oDeleteDialog && !oDeleteDialog.hidden) {
            oDeleteDialog.hidden = true;
            unlockAdminModalScroll();
        }
        document.removeEventListener("keydown", closeOnEscape);
        setAddressRowsModal(oCurrentAddressCell, false);
        if (blSaved) {
            window.location.reload();
        }
        oCurrentAddressCell = null;
    }

    function openSubjectAddressEdit(oCell) {
        var oField;
        var iI;
        if (!oSubjectEditDialog || !oSubjectEditForm || !oCell) {
            return;
        }
        oCurrentSubjectCell = oCell;
        getField(oSubjectEditForm, "address_id").value = oCell.getAttribute("data-address-id") || "";
        for (iI = 0; iI < aSubjectAddressFields.length; iI += 1) {
            oField = getField(oSubjectEditForm, aSubjectAddressFields[iI]);
            if (oField) {
                oField.value = getAddressValue(oCell, aSubjectAddressFields[iI]);
            }
        }
        getField(oSubjectEditForm, "is_primary").checked = oCell.getAttribute("data-primary") == "1";
        getField(oSubjectEditForm, "is_active").checked = oCell.getAttribute("data-active") == "1";
        setDialogError(oSubjectEditError, "");
        oSubjectEditDialog.hidden = false;
        lockAdminModalScroll();
        setSubjectRowModal(oCell, true);

        document.addEventListener("keydown", closeOnEscape);
        focusAdminElement(getField(oSubjectEditForm, "street_name"), true);
    }

    function openSubjectAddressDelete(oCell) {
        if (!oSubjectDeleteDialog || !oSubjectDeleteForm || !oCell) {
            return;
        }
        oCurrentSubjectCell = oCell;
        getField(oSubjectDeleteForm, "address_id").value = oCell.getAttribute("data-address-id") || "";
        setDialogError(oSubjectDeleteError, "");
        oSubjectDeleteDialog.hidden = false;
        lockAdminModalScroll();
        setSubjectRowModal(oCell, true);

        document.addEventListener("keydown", closeOnEscape);
        focusAdminElement(oSubjectDeleteForm.querySelector(".js-subject-address-delete-cancel"));
    }

    function closeSubjectAddressDialog(blSaved) {
        var oRow;
        if (oSubjectEditDialog && !oSubjectEditDialog.hidden) {
            oSubjectEditDialog.hidden = true;
            unlockAdminModalScroll();
        }
        if (oSubjectDeleteDialog && !oSubjectDeleteDialog.hidden) {
            oSubjectDeleteDialog.hidden = true;
            unlockAdminModalScroll();
        }
        document.removeEventListener("keydown", closeOnEscape);
        setSubjectRowModal(oCurrentSubjectCell, false);
        if (blSaved && oCurrentSubjectCell && oCurrentSubjectCell.parentNode) {
            oRow = oCurrentSubjectCell.parentNode;
            addAdminClass(oRow, "nx-admin-row-saved");
            window.setTimeout(function () {
                removeAdminClass(oRow, "nx-admin-row-saved");
            }, 1500)
        }
        oCurrentSubjectCell = null;
    }

    function getAddressCellForSubjectCell(oCell) {
        var oRow = oCell ? oCell.parentNode : null;
        var oAddressCell;
        while (oRow) {
            oAddressCell = oRow.querySelector ? oRow.querySelector(".nx-address-cell") : null;
            if (oAddressCell) {
                return oAddressCell;
            }
            oRow = oRow.previousElementSibling;
        }
        return null;
    }

    function updateAddressTimestampTooltip(oSubjectCell, sTimestampTooltip) {
        var oAddressCell;
        var oAddressValue;
        if (!sTimestampTooltip) {
            return;
        }
        oAddressCell = getAddressCellForSubjectCell(oSubjectCell);
        if (!oAddressCell || !oAddressCell.getAttribute("data-timestamp-tooltip")) {
            return;
        }
        oAddressCell.setAttribute("data-timestamp-tooltip", sTimestampTooltip);
        oAddressValue = oAddressCell.querySelector(".nx-subject-item-value");
        if (oAddressValue) {
            oAddressValue.title = sTimestampTooltip;
        }
    }

    function updateSubjectAddressCell(oCell, oForm, sTimestampTooltip) {
        var oPrimaryFlag;
        var oInactiveFlag;
        var oSubjectValue;
        var sAddressType;
        var blIsPrimary;
        var blIsActive;
        var blSubjectActive;
        if (!oCell || !oForm) {
            return;
        }
        sAddressType = getField(oForm, "address_type").value;
        blIsPrimary = getField(oForm, "is_primary").checked;
        blIsActive = getField(oForm, "is_active").checked;
        blSubjectActive = oCell.getAttribute("data-subject-active") != "0";
        oCell.setAttribute("data-address-type", sAddressType);
        oCell.setAttribute("data-note", getField(oForm, "note").value);
        oCell.setAttribute("data-primary", blIsPrimary ? "1" : "0");
        oCell.setAttribute("data-active", blIsActive ? "1" : "0");
        removeAdminClass(oCell, "nx-address-subject-active");
        removeAdminClass(oCell, "nx-address-subject-inactive");
        addAdminClass(oCell, blSubjectActive && blIsActive ? "nx-address-subject-active" : "nx-address-subject-inactive");
        oSubjectValue = oCell.querySelector(".nx-subject-item-value");
        if (oSubjectValue) {
            removeAdminClass(oSubjectValue, "nx-subject-address-main-value");
            if (sAddressType == "main") {
                addAdminClass(oSubjectValue, "nx-subject-address-main-value");
            }
        }
        oPrimaryFlag = oCell.querySelector(".nx-subject-item-flags span[title=\"Primary\"]");
        if (oPrimaryFlag) {
            oPrimaryFlag.textContent = blIsPrimary ? getAdminEmoji("primary") : "";
        }
        oInactiveFlag = oCell.querySelector(".nx-subject-item-flags span[title=\"Inactive\"]");
        if (oInactiveFlag) {
            oInactiveFlag.textContent = blIsActive ? "" : getAdminEmoji("inactive");
        }
        updateAddressTimestampTooltip(oCell, sTimestampTooltip);
    }

    function submitSharedAddressForm(oForm, sAction, oError) {
        var oData = new FormData();
        var iI;
        oData.append("action", sAction);
        appendAdminCsrfToken(oData);
        appendAdminEncodedValue(oData, "address_match", getField(oForm, "address_match").value);
        if (sAction == "update_shared_address") {
            for (iI = 0; iI < aAddressFields.length; iI += 1) {
                appendAdminEncodedValue(oData, aAddressFields[iI], getField(oForm, aAddressFields[iI]).value);
            }
        }
        fetch(window.location.href, {
            "method": "POST",
            "headers": getAdminAjaxHeaders(),
            "body": oData
        }).then(function (oResponse) {
            return oResponse.json();
        }).then(function (aData) {
            if (!aData || !aData.success) {
                setDialogError(oError, aData && aData.message ? aData.message : "Address operation failed.");
                return;
            }
            closeSharedAddressDialog(true);
        }).catch(function (oException) {
            logAdminException(oException);
            setDialogError(oError, "Address operation failed.");
        })
    }

    function submitSubjectAddressForm(oForm, sAction, oError) {
        var oData = new FormData();
        var iI;
        oData.append("action", sAction);
        appendAdminCsrfToken(oData);
        appendAdminEncodedValue(oData, "address_id", getField(oForm, "address_id").value);
        if (sAction == "update_subject_address") {
            for (iI = 0; iI < aSubjectAddressFields.length; iI += 1) {
                appendAdminEncodedValue(oData, aSubjectAddressFields[iI], getField(oForm, aSubjectAddressFields[iI]).value);
            }
            oData.append("is_primary", getField(oForm, "is_primary").checked ? "1" : "0");
            oData.append("is_active", getField(oForm, "is_active").checked ? "1" : "0");
        }
        fetch(window.location.href, {
            "method": "POST",
            "headers": getAdminAjaxHeaders(),
            "body": oData
        }).then(function (oResponse) {
            return oResponse.json();
        }).then(function (aData) {
            if (!aData || !aData.success) {
                setDialogError(oError, aData && aData.message ? aData.message : "Address operation failed.");
                return;
            }
            if (aData.reload_required) {
                window.location.reload();
                return;
            }
            updateSubjectAddressCell(oCurrentSubjectCell, oForm, aData.timestamp_tooltip || "");
            closeSubjectAddressDialog(true);
        }).catch(function (oException) {
            logAdminException(oException);
            setDialogError(oError, "Address operation failed.");
        })
    }

    document.addEventListener("click", function (oEvent) {
        var oButton = getActionButton(oEvent.target);
        if (oButton) {
            oEvent.preventDefault();
            oEvent.stopPropagation();
            if (oButton.className.indexOf("js-edit-shared-address") !== -1) {
                openSharedAddressEdit(getAddressCell(oButton));
            } else if (oButton.className.indexOf("js-delete-shared-address") !== -1) {
                openSharedAddressDelete(getAddressCell(oButton));
            } else if (oButton.className.indexOf("js-edit-subject-address-local") !== -1) {
                openSubjectAddressEdit(getSubjectCell(oButton));
            } else if (oButton.className.indexOf("js-delete-subject-address-local") !== -1) {
                openSubjectAddressDelete(getSubjectCell(oButton));
            } else {
                closeSharedAddressDialog(false);
                closeSubjectAddressDialog(false);
            }
        }
    }, true);

    if (oEditForm) {
        oEditForm.addEventListener("submit", function (oEvent) {
            oEvent.preventDefault();
            submitSharedAddressForm(oEditForm, "update_shared_address", oEditError);
        })
    }
    if (oDeleteForm) {
        oDeleteForm.addEventListener("submit", function (oEvent) {
            oEvent.preventDefault();
            submitSharedAddressForm(oDeleteForm, "delete_shared_address", oDeleteError);
        })
    }
    if (oSubjectEditForm) {
        oSubjectEditForm.addEventListener("submit", function (oEvent) {
            oEvent.preventDefault();
            submitSubjectAddressForm(oSubjectEditForm, "update_subject_address", oSubjectEditError);
        })
    }
    if (oSubjectDeleteForm) {
        oSubjectDeleteForm.addEventListener("submit", function (oEvent) {
            oEvent.preventDefault();
            submitSubjectAddressForm(oSubjectDeleteForm, "delete_subject_address", oSubjectDeleteError);
        })
    }
});

document.addEventListener("DOMContentLoaded", function () {
    var aSubmitOnChange = document.querySelectorAll(".js-submit-on-change");
    for (var iI = 0; iI < aSubmitOnChange.length; iI += 1) {
        aSubmitOnChange[iI].addEventListener("change", function () {
            if (this.form) {
                this.form.submit();
            }
        })
    }
});

document.addEventListener("DOMContentLoaded", scheduleRenderThrobberHide);
