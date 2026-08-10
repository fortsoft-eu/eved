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
var oAdminOpenDialog = null;

function logAdminException(oException) {
    if (window.console && window.console.error) {
        window.console.error(oException);
    }
}

function isAdminRenderThrobberActive() {
    var oRoot = document.documentElement;
    if (blRenderThrobberScrollLocked) {
        return true;
    }
    return oRoot && oRoot.getAttribute("data-render-throbber-lock-active") == "1";
}

function isAdminOverlayActive() {
    return iAdminModalCount > 0 || isAdminRenderThrobberActive();
}

function getAdminInputDatalist(oInput) {
    var sListId = oInput ? (oInput.getAttribute("list") || "") : "";
    return sListId ? document.getElementById(sListId) : null;
}

function openAdminInputDatalist(oInput) {
    var oList = getAdminInputDatalist(oInput);
    if (!oInput || oInput.disabled || oInput.readOnly || !oList || !oList.options || oList.options.length < 1 || typeof oInput.showPicker != "function") {
        return false;
    }
    try {
        oInput.showPicker();
        return true;
    } catch (oException) {
        logAdminException(oException);
        return false;
    }
}

function setupDatalistOpenOnFocus() {
    document.addEventListener("focusin", function (oEvent) {
        var oInput = oEvent.target;
        if (!oInput || !oInput.tagName || oInput.tagName.toLowerCase() != "input" || !oInput.getAttribute("list")) {
            return;
        }
        openAdminInputDatalist(oInput);
    });
}

function getAdminCsrfToken() {
    var oMeta = document.querySelector("meta[name=\"csrf-token\"]");
    return oMeta ? (oMeta.getAttribute("content") || "") : "";
}

function getAdminEmoji(sName) {
    var oData = document.getElementById("emoji-data");
    return oData ? (oData.getAttribute("data-" + sName) || "") : "";
}

function appendAdminCsrfToken(oData) {
    var sToken = getAdminCsrfToken();
    if (oData && sToken) {
        oData.append("csrf_token", sToken);
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

function getAdminElementText(oElement) {
    return oElement ? (oElement.textContent || "").replace(/\s+/g, " ").trim() : "";
}

function getAdminContactItemText(oItem) {
    var sType = oItem ? (oItem.getAttribute("data-contact-type-name") || "") : "";
    var sValue = oItem ? (oItem.getAttribute("data-contact-value") || "") : "";
    if (sType != "" && sValue != "") {
        return sType + ": " + sValue;
    }
    return sValue || sType;
}

function appendAdminConfirmDetail(oParent, sDetail) {
    var aLines;
    var iI;
    if (!oParent || !sDetail) {
        return;
    }
    aLines = String(sDetail).split(/\r?\n/);
    for (iI = 0; iI < aLines.length; iI += 1) {
        if (iI > 0) {
            oParent.appendChild(document.createElement("br"));
        }
        oParent.appendChild(document.createTextNode(aLines[iI]));
    }
}

function setAdminConfirmMessage(oText, sMessage, sDetail) {
    var oStrong;
    if (!oText) {
        return;
    }
    oText.textContent = "";
    oText.appendChild(document.createTextNode(sMessage));
    if (sDetail) {
        oText.appendChild(document.createElement("br"));
        oStrong = document.createElement("strong");
        appendAdminConfirmDetail(oStrong, sDetail);
        oText.appendChild(oStrong);
    }
}

function setAdminConfirmDetail(oDialog, sSelector, sDetail) {
    var oStrong = oDialog ? oDialog.querySelector(sSelector) : null;
    if (oStrong) {
        oStrong.textContent = "";
        appendAdminConfirmDetail(oStrong, sDetail || "");
    }
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

function padAdminDateTimeNumber(iValue) {
    return iValue < 10 ? "0" + iValue : "" + iValue;
}

function createAdminParsedDateTime(iYear, iMonth, iDay, iHour, iMinute, iSecond, blHasTime, blHasSeconds) {
    var oDate;
    iYear = parseInt(iYear, 10);
    iMonth = parseInt(iMonth, 10);
    iDay = parseInt(iDay, 10);
    iHour = parseInt(iHour || 0, 10);
    iMinute = parseInt(iMinute || 0, 10);
    iSecond = parseInt(iSecond || 0, 10);
    if (!isFinite(iYear) || !isFinite(iMonth) || !isFinite(iDay) || !isFinite(iHour) || !isFinite(iMinute) || !isFinite(iSecond)) {
        return null;
    }
    if (iYear < 1 || iYear > 9999 || iMonth < 1 || iMonth > 12 || iDay < 1 || iDay > 31 || iHour < 0 || iHour > 23 || iMinute < 0 || iMinute > 59 || iSecond < 0 || iSecond > 59) {
        return null;
    }
    oDate = new Date(0);
    oDate.setFullYear(iYear, iMonth - 1, iDay);
    oDate.setHours(iHour, iMinute, iSecond, 0);
    if (oDate.getFullYear() !== iYear || oDate.getMonth() !== iMonth - 1 || oDate.getDate() !== iDay || oDate.getHours() !== iHour || oDate.getMinutes() !== iMinute || oDate.getSeconds() !== iSecond) {
        return null;
    }
    return {
        date: oDate,
        year: iYear,
        month: iMonth,
        day: iDay,
        hour: iHour,
        minute: iMinute,
        second: iSecond,
        hasTime: blHasTime === true,
        hasSeconds: blHasSeconds === true
    };
}

function parseAdminCompactTime(sDigits) {
    if (!/^\d{4}(\d{2})?$/.test(sDigits || "")) {
        return null;
    }
    return {
        hour: parseInt(sDigits.substring(0, 2), 10),
        minute: parseInt(sDigits.substring(2, 4), 10),
        second: sDigits.length == 6 ? parseInt(sDigits.substring(4, 6), 10) : 0,
        hasSeconds: sDigits.length == 6
    };
}

function parseAdminCompactDateTime(sDigits) {
    var oTime;
    if (!/^\d{8}(\d{4}(\d{2})?)?$/.test(sDigits || "")) {
        return null;
    }
    oTime = sDigits.length > 8 ? parseAdminCompactTime(sDigits.substring(8)) : { hour: 0, minute: 0, second: 0, hasSeconds: false };
    if (!oTime) {
        return null;
    }
    return createAdminParsedDateTime(sDigits.substring(0, 4), sDigits.substring(4, 6), sDigits.substring(6, 8), oTime.hour, oTime.minute, oTime.second, sDigits.length > 8, oTime.hasSeconds);
}

function parseAdminNumericDateTime(sValue) {
    var sNormalized = String(sValue || "").replace(/[^\d]+/g, " ").replace(/^\s+|\s+$/g, "");
    var sDigits = String(sValue || "").replace(/[^\d]+/g, "");
    var aParts = sNormalized ? sNormalized.split(/ +/) : [];
    var iHour = 0;
    var iMinute = 0;
    var iSecond = 0;
    var blHasTime = false;
    var blHasSeconds = false;
    var oTime;
    var oParsed = parseAdminCompactDateTime(sDigits);
    if (oParsed) {
        return oParsed;
    }
    if (aParts.length < 1) {
        return null;
    }
    if (aParts.length == 1) {
        return parseAdminCompactDateTime(aParts[0]);
    }
    if (/^\d{8}$/.test(aParts[0]) && /^\d{4}(\d{2})?$/.test(aParts[1] || "")) {
        oTime = parseAdminCompactTime(aParts[1]);
        if (!oTime) {
            return null;
        }
        return createAdminParsedDateTime(aParts[0].substring(0, 4), aParts[0].substring(4, 6), aParts[0].substring(6, 8), oTime.hour, oTime.minute, oTime.second, true, oTime.hasSeconds);
    }
    if (aParts.length >= 3 && /^\d{4}$/.test(aParts[0])) {
        if (typeof aParts[3] != "undefined") {
            blHasTime = true;
            if (/^\d{4}(\d{2})?$/.test(aParts[3]) && typeof aParts[4] == "undefined") {
                oTime = parseAdminCompactTime(aParts[3]);
                if (!oTime) {
                    return null;
                }
                iHour = oTime.hour;
                iMinute = oTime.minute;
                iSecond = oTime.second;
                blHasSeconds = oTime.hasSeconds;
            } else {
                iHour = parseInt(aParts[3], 10);
                iMinute = typeof aParts[4] != "undefined" ? parseInt(aParts[4], 10) : 0;
                iSecond = typeof aParts[5] != "undefined" ? parseInt(aParts[5], 10) : 0;
                blHasSeconds = typeof aParts[5] != "undefined";
            }
        }
        return createAdminParsedDateTime(aParts[0], aParts[1], aParts[2], iHour, iMinute, iSecond, blHasTime, blHasSeconds);
    }
    if (aParts.length >= 3 && /^\d{4}$/.test(aParts[2])) {
        if (typeof aParts[3] != "undefined") {
            blHasTime = true;
            if (/^\d{4}(\d{2})?$/.test(aParts[3]) && typeof aParts[4] == "undefined") {
                oTime = parseAdminCompactTime(aParts[3]);
                if (!oTime) {
                    return null;
                }
                iHour = oTime.hour;
                iMinute = oTime.minute;
                iSecond = oTime.second;
                blHasSeconds = oTime.hasSeconds;
            } else {
                iHour = parseInt(aParts[3], 10);
                iMinute = typeof aParts[4] != "undefined" ? parseInt(aParts[4], 10) : 0;
                iSecond = typeof aParts[5] != "undefined" ? parseInt(aParts[5], 10) : 0;
                blHasSeconds = typeof aParts[5] != "undefined";
            }
        }
        return createAdminParsedDateTime(aParts[2], aParts[1], aParts[0], iHour, iMinute, iSecond, blHasTime, blHasSeconds);
    }
    return null;
}

function parseAdminFlexibleDateTime(sValue) {
    var sText = String(sValue || "").replace(/\u00a0/g, " ").replace(/([0-9])[Tt]([0-9])/g, "$1 $2").replace(/^\s+|\s+$/g, "");
    var oParsed;
    var iTime;
    var oDate;
    if (sText == "") {
        return null;
    }
    oParsed = parseAdminNumericDateTime(sText);
    if (oParsed) {
        return oParsed;
    }
    if (/^[0-9][0-9\s:.,\/-]*$/.test(sText)) {
        return null;
    }
    iTime = Date.parse(sText);
    if (isNaN(iTime)) {
        return null;
    }
    oDate = new Date(iTime);
    return createAdminParsedDateTime(oDate.getFullYear(), oDate.getMonth() + 1, oDate.getDate(), oDate.getHours(), oDate.getMinutes(), oDate.getSeconds(), /[0-9][Tt :.,-][0-9]{1,2}[ :.,-]?[0-9]{0,2}/.test(sText), /[0-9][:. -][0-9]{1,2}[^\d]+[0-9]{1,2}/.test(sText) || /[0-9]{14}/.test(sText));
}

function formatAdminParsedDate(oParsed) {
    return oParsed.year + "-" + padAdminDateTimeNumber(oParsed.month) + "-" + padAdminDateTimeNumber(oParsed.day);
}

function formatAdminParsedDateTime(oParsed, blDateTime) {
    var sValue = formatAdminParsedDate(oParsed);
    if (!blDateTime) {
        return sValue;
    }
    sValue += " " + padAdminDateTimeNumber(oParsed.hour) + ":" + padAdminDateTimeNumber(oParsed.minute);
    if (oParsed.hasSeconds || oParsed.second != 0) {
        sValue += ":" + padAdminDateTimeNumber(oParsed.second);
    }
    return sValue;
}

function normalizeAdminDateTimeInput(oInput, sValueType, blDispatch) {
    var sOldValue;
    var oParsed;
    var sNewValue;
    if (!oInput) {
        return;
    }
    sOldValue = oInput.value || "";
    if (sOldValue.replace(/^\s+|\s+$/g, "") == "") {
        return;
    }
    oParsed = parseAdminFlexibleDateTime(sOldValue);
    if (!oParsed) {
        return;
    }
    sNewValue = formatAdminParsedDateTime(oParsed, sValueType == "datetime");
    if (sOldValue != sNewValue) {
        oInput.value = sNewValue;
        if (blDispatch) {
            dispatchAdminInputEvent(oInput);
        }
    }
}

function normalizeAdminDateTimeInputs(oParent) {
    var aInputs = oParent && oParent.querySelectorAll ? oParent.querySelectorAll("input[data-date-input-kind], input[data-value-type=\"date\"], input[data-value-type=\"datetime\"]") : [];
    var sValueType;
    for (var iI = 0; iI < aInputs.length; iI += 1) {
        sValueType = aInputs[iI].getAttribute("data-value-type") || aInputs[iI].getAttribute("data-date-input-kind") || "date";
        normalizeAdminDateTimeInput(aInputs[iI], sValueType, false);
    }
}

document.addEventListener("submit", function (oEvent) {
    normalizeAdminDateTimeInputs(oEvent.target);
}, true);

function refreshAdminTableFilter() {
    dispatchAdminInputEvent(document.querySelector(".js-table-filter"));
}

function replaceAdminTableCellHtml(oCurrentCell, sCellHtml) {
    var oBody;
    var oRow;
    var oNewCell;
    if (!oCurrentCell || !oCurrentCell.parentNode || !sCellHtml) {
        return null;
    }
    oBody = document.createElement("tbody");
    oBody.innerHTML = "<tr>" + sCellHtml + "</tr>";
    oRow = oBody.querySelector("tr");
    oNewCell = oRow ? oRow.querySelector("td, th") : null;
    if (!oNewCell) {
        return null;
    }
    oCurrentCell.parentNode.replaceChild(oNewCell, oCurrentCell);
    oNewCell.parentNode._quickTableFilterText = null;
    if (window.bindAdminTableRow) {
        window.bindAdminTableRow(oNewCell.parentNode);
    }
    return oNewCell;
}

function closeAdminOpenDialog(oExceptDialog) {
    var aDialogs;
    var iI;
    if (oAdminOpenDialog && oAdminOpenDialog !== oExceptDialog && oAdminOpenDialog._adminDialogClose) {
        oAdminOpenDialog._adminDialogClose();
    }
    aDialogs = document.querySelectorAll(".confirm-dialog:not([hidden])");
    for (iI = 0; iI < aDialogs.length; iI += 1) {
        if (aDialogs[iI] !== oExceptDialog) {
            if (aDialogs[iI]._adminDialogClose) {
                aDialogs[iI]._adminDialogClose();
            } else {
                closeAdminDialogElement(aDialogs[iI]);
            }
        }
    }
}

function saveAdminReusableDialogBoxPosition(oDialog) {
    var oBox = oDialog ? oDialog.querySelector(".confirm-dialog-box") : null;
    if (!oDialog || oDialog.getAttribute("data-reusable-dialog") != "1" || !oBox) {
        return;
    }
    oDialog.setAttribute("data-reusable-dialog-position", oBox.style.position || "");
    oDialog.setAttribute("data-reusable-dialog-left", oBox.style.left || "");
    oDialog.setAttribute("data-reusable-dialog-top", oBox.style.top || "");
    oDialog.setAttribute("data-reusable-dialog-margin", oBox.style.margin || "");
}

function restoreAdminReusableDialogBoxPosition(oDialog) {
    var oBox = oDialog ? oDialog.querySelector(".confirm-dialog-box") : null;
    if (!oDialog || oDialog.getAttribute("data-reusable-dialog") != "1" || !oBox) {
        return;
    }
    oBox.style.position = oDialog.getAttribute("data-reusable-dialog-position") || "";
    oBox.style.left = oDialog.getAttribute("data-reusable-dialog-left") || "";
    oBox.style.top = oDialog.getAttribute("data-reusable-dialog-top") || "";
    oBox.style.margin = oDialog.getAttribute("data-reusable-dialog-margin") || "";
}

function openAdminDialogElement(oDialog, fClose) {
    if (!oDialog) {
        return false;
    }
    if (!oDialog.hidden) {
        closeAdminOpenDialog(oDialog);
        return false;
    }
    closeAdminOpenDialog(oDialog);
    oDialog._adminDialogClose = fClose || null;
    oAdminOpenDialog = oDialog;
    restoreAdminReusableDialogBoxPosition(oDialog);
    oDialog.hidden = false;
    lockAdminModalScroll();
    return true;
}

function closeAdminDialogElement(oDialog) {
    if (oDialog && !oDialog.hidden) {
        oDialog.hidden = true;
        unlockAdminModalScroll();
    }
    if (oAdminOpenDialog === oDialog) {
        oAdminOpenDialog = null;
    }
    if (oDialog) {
        oDialog._adminDialogClose = null;
        if (oDialog.getAttribute("data-reusable-dialog") == "1") {
            saveAdminReusableDialogBoxPosition(oDialog);
            while (oDialog.firstChild) {
                oDialog.removeChild(oDialog.firstChild);
            }
        }
    }
}

function prepareAdminReusableDialog() {
    var oDialog = document.getElementById("admin-reusable-dialog");
    if (!oDialog) {
        return null;
    }
    closeAdminOpenDialog(oDialog);
    if (oDialog._adminDialogClose) {
        oDialog._adminDialogClose();
    }
    closeAdminDialogElement(oDialog);
    oDialog.hidden = true;
    return oDialog;
}

function showAdminMessageDialog(sMessage, sTitle) {
    var oDialog = prepareAdminReusableDialog();
    var oForm;
    var oHeader;
    var oTitle;
    var oClose;
    var oText;
    var oActions;
    var oOk;
    var closeOnEscape;
    var closeDialog;
    if (!oDialog) {
        return;
    }
    oForm = document.createElement("form");
    oHeader = document.createElement("div");
    oTitle = document.createElement("strong");
    oClose = document.createElement("button");
    oText = document.createElement("p");
    oActions = document.createElement("div");
    oOk = document.createElement("button");
    closeOnEscape = function (oEvent) {
        if (oEvent.key == "Escape") {
            closeDialog();
        }
    };
    closeDialog = function () {
        document.removeEventListener("keydown", closeOnEscape);
        closeAdminDialogElement(oDialog);
    };
    oDialog.className = "confirm-dialog";
    oForm.className = "confirm-dialog-box subject-edit-dialog";
    oForm.method = "post";
    oForm.action = window.location.href;
    oHeader.className = "confirm-dialog-header";
    oTitle.textContent = sTitle || "Message";
    oClose.type = "button";
    oClose.className = "confirm-dialog-close";
    oClose.setAttribute("aria-label", "Close");
    oClose.textContent = "\u00D7";
    oText.textContent = sMessage || "";
    oActions.className = "confirm-dialog-actions";
    oOk.type = "submit";
    oOk.className = "confirm-dialog-button";
    oOk.textContent = "OK";
    oHeader.appendChild(oTitle);
    oHeader.appendChild(oClose);
    oForm.appendChild(oHeader);
    oForm.appendChild(oText);
    oActions.appendChild(oOk);
    oForm.appendChild(oActions);
    oForm.addEventListener("submit", function (oEvent) {
        oEvent.preventDefault();
        closeDialog();
    });
    oClose.addEventListener("click", closeDialog);
    oDialog.appendChild(oForm);
    enableAdminDialogDrag(oDialog, oForm, oHeader);
    document.addEventListener("keydown", closeOnEscape);
    openAdminDialogElement(oDialog, closeDialog);
    focusAdminElement(oOk);
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

function isAdminUserInput(oElement) {
    var sTag = oElement && oElement.tagName ? oElement.tagName.toLowerCase() : "";
    var sType;
    if (!oElement || oElement.disabled || oElement.getAttribute("tabindex") == "-1" || oElement.getAttribute("aria-hidden") == "true") {
        return false;
    }
    if (oElement.closest && oElement.closest("[hidden]")) {
        return false;
    }
    if (sTag == "select" || sTag == "textarea") {
        return true;
    }
    if (sTag != "input") {
        return false;
    }
    sType = (oElement.getAttribute("type") || "text").toLowerCase();
    return sType != "hidden" && sType != "submit" && sType != "button" && sType != "reset" && sType != "image";
}

function findFirstAdminUserInput(oRoot) {
    var aElements = oRoot ? oRoot.querySelectorAll("input, select, textarea") : [];
    for (var iI = 0; iI < aElements.length; iI += 1) {
        if (isAdminUserInput(aElements[iI])) {
            return aElements[iI];
        }
    }
    return null;
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
document.addEventListener("DOMContentLoaded", setupDatalistOpenOnFocus);

function setAdminMergeSourceListColumns(oDialog, oSourceList, iRenderedCount) {
    var oBox;
    var iColumnCount = Math.max(1, Math.ceil(iRenderedCount / 10));
    if (!oSourceList || iColumnCount <= 1) {
        return;
    }
    oSourceList.style.gridTemplateColumns = "repeat(" + iColumnCount + ", minmax(0, 1fr))";
    oBox = oDialog && oDialog.closest ? oDialog.closest(".confirm-dialog-box") : null;
    addAdminClass(oBox || oDialog, "merge-dialog-wide");
}

function findAdminSubjectRowById(sSubjectId) {
    return sSubjectId ? document.querySelector("#subjects-table tbody tr[data-subject-id=\"" + sSubjectId + "\"], #birthdays-table tbody tr[data-subject-id=\"" + sSubjectId + "\"], #interactions-table tbody tr[data-subject-id=\"" + sSubjectId + "\"], #contacts-table tbody tr[data-subject-id=\"" + sSubjectId + "\"]") : null;
}

function getAdminSubjectRow(oElement) {
    return oElement && oElement.closest ? oElement.closest("tr[data-subject-id]") : null;
}

function beginAdminSubjectRowEdit(oRow) {
    if (oRow) {
        removeAdminClass(oRow, "admin-row-saved");
        addAdminClass(oRow, "admin-row-modal");
    }
}

function finishAdminSubjectRowEdit(oRow, blSaved) {
    if (oRow) {
        removeAdminClass(oRow, "admin-row-modal");
        removeAdminClass(oRow, "admin-row-saved");
        if (!blSaved) {
            addAdminClass(oRow, "admin-row-modal");
            window.setTimeout(function () {
                removeAdminClass(oRow, "admin-row-modal");
            }, 1000);
            return;
        }
        oRow.offsetWidth;
        addAdminClass(oRow, "admin-row-saved");
        window.setTimeout(function () {
            removeAdminClass(oRow, "admin-row-saved");
        }, 1400);
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

    if (!oDialog || !oBox || !oHeader || oHeader.getAttribute("data-admin-dialog-drag-bound") == "1") {
        return;
    }
    oHeader.setAttribute("data-admin-dialog-drag-bound", "1");
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
    });
}

document.addEventListener("DOMContentLoaded", function () {
    var aDialogs = document.querySelectorAll(".confirm-dialog");
    var oBox;
    var oHeader;
    for (var iI = 0; iI < aDialogs.length; iI += 1) {
        oBox = aDialogs[iI].querySelector(".confirm-dialog-box");
        oHeader = aDialogs[iI].querySelector(".confirm-dialog-header");
        enableAdminDialogDrag(aDialogs[iI], oBox, oHeader);
    }
    focusAdminElement(document.getElementById("login-user"), true);
});

document.addEventListener("DOMContentLoaded", function () {
    var oOpen = document.querySelector(".js-index-settings-open");
    var oDialog = document.getElementById("index-settings-dialog");
    var oBox = oDialog ? oDialog.querySelector(".confirm-dialog-box") : null;
    var oHeader = oDialog ? oDialog.querySelector(".confirm-dialog-header") : null;
    var oClose = oDialog ? oDialog.querySelector(".js-index-settings-close") : null;
    var oCancel = oDialog ? oDialog.querySelector(".js-index-settings-cancel") : null;
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
            });
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
        if (!openAdminDialogElement(oDialog, closeDialog)) {
            return;
        }

        document.addEventListener("keydown", closeOnEscape);
        focusAdminElement(findFirstAdminUserInput(oDialog), true);
    }

    function closeDialog() {
        if (!oDialog || oDialog.hidden) {
            return;
        }
        document.removeEventListener("keydown", closeOnEscape);
        restoreCheckboxStates();
        closeAdminDialogElement(oDialog);
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
        });
    }
    if (oClose) {
        oClose.addEventListener("click", function () {
            closeDialog();
        });
    }
    if (oCancel) {
        oCancel.addEventListener("click", function () {
            closeDialog();
        });
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
    var iComplexFilterCalendarFirstDay = 1;
    var closeOnEscape = function (oEvent) {
        if (oEvent.key == "Escape") {
            closeDialog();
        }
    };
    if (document.body) {
        iComplexFilterCalendarFirstDay = parseInt(document.body.getAttribute("data-calendar-first-day") || "1", 10);
    }
    if (isNaN(iComplexFilterCalendarFirstDay) || iComplexFilterCalendarFirstDay < 0 || iComplexFilterCalendarFirstDay > 6) {
        iComplexFilterCalendarFirstDay = 1;
    }

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
        var oParsed;
        if (isComplexFilterDateValueType(sValueType)) {
            oParsed = parseAdminFlexibleDateTime(sText);
            return oParsed ? formatAdminParsedDateTime(oParsed, sValueType == "datetime") : sText;
        }
        return sText;
    }

    function isComplexFilterDateValueType(sValueType) {
        return sValueType == "date" || sValueType == "datetime";
    }

    function isComplexFilterDateField(oValue) {
        var oParent = oValue ? oValue.parentNode : null;
        return !!(oParent && (" " + oParent.className + " ").indexOf(" complex-filter-date-field ") !== -1);
    }

    function getComplexFilterValueContainer(oValue) {
        return isComplexFilterDateField(oValue) ? oValue.parentNode : oValue;
    }

    function getComplexFilterValueFromControl(oControl) {
        var oValue = oControl && oControl.querySelector ? oControl.querySelector(".js-complex-filter-value") : null;
        return oValue || oControl;
    }

    function replaceComplexFilterValueControl(oValue, oNewControl) {
        var oContainer = getComplexFilterValueContainer(oValue);
        if (!oContainer || !oContainer.parentNode) {
            return getComplexFilterValueFromControl(oNewControl);
        }
        oContainer.parentNode.replaceChild(oNewControl, oContainer);
        return getComplexFilterValueFromControl(oNewControl);
    }

    function padComplexFilterIsoDateNumber(iValue) {
        return iValue < 10 ? "0" + iValue : "" + iValue;
    }

    function formatComplexFilterIsoDate(oDate) {
        return oDate.getFullYear() + "-" + padComplexFilterIsoDateNumber(oDate.getMonth() + 1) + "-" + padComplexFilterIsoDateNumber(oDate.getDate());
    }

    function parseComplexFilterIsoDate(sValue) {
        var oParsed = parseAdminFlexibleDateTime(sValue);
        return oParsed ? oParsed.date : null;
    }

    function getComplexFilterInputDateValue(oInput) {
        var oParsed = parseAdminFlexibleDateTime(oInput.value || "");
        return oParsed ? formatAdminParsedDate(oParsed) : (oInput.value || "").substring(0, 10);
    }

    function setComplexFilterInputDateValue(oInput, sDate) {
        var sValue = oInput.value || "";
        var oParsed = parseAdminFlexibleDateTime(sValue);
        var sTime = "00:00";
        if (oInput.getAttribute("data-value-type") == "datetime") {
            if (oParsed && oParsed.hasTime) {
                sTime = padAdminDateTimeNumber(oParsed.hour) + ":" + padAdminDateTimeNumber(oParsed.minute);
                if (oParsed.hasSeconds || oParsed.second != 0) {
                    sTime += ":" + padAdminDateTimeNumber(oParsed.second);
                }
            }
            oInput.value = sDate + " " + sTime;
        } else {
            oInput.value = sDate;
        }
        dispatchAdminInputEvent(oInput);
    }

    function renderComplexFilterDateCalendar(oInput, oCalendar, oMonthDate) {
        var aDayLabels = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
        var oSelectedDate = parseComplexFilterIsoDate(getComplexFilterInputDateValue(oInput));
        var iYear = oMonthDate.getFullYear();
        var iMonth = oMonthDate.getMonth();
        var iFirstDay = new Date(iYear, iMonth, 1).getDay();
        var iOffset = (iFirstDay - iComplexFilterCalendarFirstDay + 7) % 7;
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
        oTitle.textContent = iYear + "-" + padComplexFilterIsoDateNumber(iMonth + 1);
        oGrid.className = "subject-date-calendar-grid";
        oPrev.addEventListener("click", function () {
            renderComplexFilterDateCalendar(oInput, oCalendar, new Date(iYear, iMonth - 1, 1));
            positionComplexFilterDateCalendar(oInput, oCalendar);
        });
        oNext.addEventListener("click", function () {
            renderComplexFilterDateCalendar(oInput, oCalendar, new Date(iYear, iMonth + 1, 1));
            positionComplexFilterDateCalendar(oInput, oCalendar);
        });
        oHeader.appendChild(oPrev);
        oHeader.appendChild(oTitle);
        oHeader.appendChild(oNext);
        for (iI = 0; iI < 7; iI += 1) {
            oDayLabel = document.createElement("div");
            oDayLabel.className = "subject-date-calendar-day";
            oDayLabel.textContent = aDayLabels[(iComplexFilterCalendarFirstDay + iI) % 7];
            oGrid.appendChild(oDayLabel);
        }
        for (iI = 0; iI < iOffset; iI += 1) {
            oEmpty = document.createElement("span");
            oEmpty.className = "subject-date-calendar-empty";
            oGrid.appendChild(oEmpty);
        }
        for (iI = 1; iI <= iDays; iI += 1) {
            oDate = new Date(iYear, iMonth, iI);
            sDate = formatComplexFilterIsoDate(oDate);
            oDateButton = document.createElement("button");
            oDateButton.type = "button";
            oDateButton.className = "subject-date-calendar-date" + (oSelectedDate && formatComplexFilterIsoDate(oSelectedDate) == sDate ? " subject-date-calendar-selected" : "");
            oDateButton.setAttribute("data-date", sDate);
            oDateButton.textContent = "" + iI;
            oDateButton.addEventListener("click", function () {
                setComplexFilterInputDateValue(oInput, this.getAttribute("data-date") || "");
                oCalendar.style.display = "none";
            });
            oGrid.appendChild(oDateButton);
        }
        oCalendar.appendChild(oHeader);
        oCalendar.appendChild(oGrid);
    }

    function positionComplexFilterDateCalendar(oInput, oCalendar) {
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

    function showComplexFilterDateCalendar(oInput, oCalendar) {
        var oDate = parseComplexFilterIsoDate(getComplexFilterInputDateValue(oInput)) || oCalendar._currentDate || new Date();
        renderComplexFilterDateCalendar(oInput, oCalendar, new Date(oDate.getFullYear(), oDate.getMonth(), 1));
        if (!oCalendar.parentNode) {
            document.body.appendChild(oCalendar);
        }
        oCalendar.style.display = "";
        positionComplexFilterDateCalendar(oInput, oCalendar);
    }

    function removeComplexFilterDateCalendars() {
        var aCalendars = document.querySelectorAll(".complex-filter-date-calendar");
        for (var iI = 0; iI < aCalendars.length; iI += 1) {
            if (aCalendars[iI].parentNode) {
                aCalendars[iI].parentNode.removeChild(aCalendars[iI]);
            }
        }
    }

    function prepareComplexFilterDateInput(oInput, sValueType) {
        oInput.type = "text";
        oInput.placeholder = sValueType == "datetime" ? "YYYY-MM-DD HH:mm" : "YYYY-MM-DD";
        oInput.maxLength = 19;
        oInput.autocomplete = "off";
        oInput.title = sValueType == "datetime" ? "Use YYYY-MM-DD HH:mm." : "Use YYYY-MM-DD.";
    }

    function bindComplexFilterDateCalendar(oInput, oButton, oCalendar, oWrapper) {
        if (!oInput || !oButton || !oCalendar || !oWrapper || oInput.getAttribute("data-complex-filter-date-bound") == "1") {
            return;
        }
        oInput.setAttribute("data-complex-filter-date-bound", "1");
        oCalendar.addEventListener("mousedown", function (oEvent) {
            oEvent.preventDefault();
        });
        oButton.addEventListener("click", function (oEvent) {
            oEvent.preventDefault();
            if (oCalendar.style.display == "none") {
                showComplexFilterDateCalendar(oInput, oCalendar);
            } else {
                oCalendar.style.display = "none";
            }
        });
        oInput.addEventListener("focus", function () {
            showComplexFilterDateCalendar(oInput, oCalendar);
        });
        oInput.addEventListener("input", function () {
            var oDate = parseComplexFilterIsoDate(getComplexFilterInputDateValue(oInput));
            if (oDate && oCalendar.style.display != "none") {
                renderComplexFilterDateCalendar(oInput, oCalendar, new Date(oDate.getFullYear(), oDate.getMonth(), 1));
                positionComplexFilterDateCalendar(oInput, oCalendar);
            }
        });
        oInput.addEventListener("keydown", function (oEvent) {
            if (oEvent.key == "Escape") {
                oCalendar.style.display = "none";
            }
        });
        oWrapper.addEventListener("focusout", function () {
            normalizeAdminDateTimeInput(oInput, oInput.getAttribute("data-value-type") || "date", true);
            window.setTimeout(function () {
                if (!oWrapper.contains(document.activeElement) && !oCalendar.contains(document.activeElement)) {
                    oCalendar.style.display = "none";
                }
            }, 0);
        });
        window.addEventListener("resize", function () {
            if (oCalendar.style.display != "none") {
                positionComplexFilterDateCalendar(oInput, oCalendar);
            }
        });
    }

    function updateComplexFilterInputType(oInput, sValueType) {
        if (!oInput) {
            return;
        }
        if (isComplexFilterDateValueType(sValueType)) {
            prepareComplexFilterDateInput(oInput, sValueType);
        } else if (sValueType == "number") {
            oInput.type = "number";
            oInput.removeAttribute("pattern");
            oInput.removeAttribute("placeholder");
            oInput.removeAttribute("maxlength");
            oInput.removeAttribute("title");
        } else {
            oInput.type = "text";
            oInput.removeAttribute("pattern");
            oInput.removeAttribute("placeholder");
            oInput.removeAttribute("maxlength");
            oInput.removeAttribute("title");
        }
        if (sValueType == "country") {
            oInput.setAttribute("list", "country-list");
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
        });
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

    function createComplexFilterDateField(sValue, sValueType) {
        var oWrapper = document.createElement("div");
        var oInput = document.createElement("input");
        var oButton = document.createElement("button");
        var oCalendar = document.createElement("div");
        oWrapper.className = "complex-filter-date-field";
        oInput.name = "complex_filter_value[]";
        oInput.className = "js-complex-filter-value";
        oInput.setAttribute("data-value-type", sValueType);
        prepareComplexFilterDateInput(oInput, sValueType);
        oInput.value = normalizeComplexFilterInputValue(sValue, sValueType);
        oButton.type = "button";
        oButton.className = "complex-filter-date-button subject-date-button";
        oButton.setAttribute("aria-label", "Open calendar");
        oButton.textContent = "\u25BE";
        oCalendar.className = "subject-date-calendar complex-filter-date-calendar";
        oCalendar.style.display = "none";
        bindComplexFilterDateCalendar(oInput, oButton, oCalendar, oWrapper);
        oWrapper.appendChild(oInput);
        oWrapper.appendChild(oButton);
        return oWrapper;
    }

    function ensureComplexFilterValueControl(oRow) {
        var sValueType = getRowValueType(oRow);
        var oValue = oRow ? oRow.querySelector(".js-complex-filter-value") : null;
        var oNewValue;
        var sCurrentValue;
        var sTagName;
        var blDateField;
        if (!oRow || !oValue) {
            return oValue;
        }
        sCurrentValue = oValue.value;
        sTagName = oValue.tagName ? oValue.tagName.toLowerCase() : "";
        blDateField = isComplexFilterDateField(oValue);
        if (sValueType == "boolean" && (sTagName != "select" || oValue.getAttribute("data-value-type") != "boolean" || blDateField)) {
            oNewValue = createComplexFilterBooleanSelect(sCurrentValue);
            oNewValue = replaceComplexFilterValueControl(oValue, oNewValue);
            oValue = oNewValue;
        } else if (sValueType == "group" && (sTagName != "select" || oValue.getAttribute("data-value-type") != "group" || blDateField)) {
            oNewValue = createComplexFilterGroupSelect(sCurrentValue);
            oNewValue = replaceComplexFilterValueControl(oValue, oNewValue);
            oValue = oNewValue;
        } else if (sValueType == "subject_type" && (sTagName != "select" || oValue.getAttribute("data-value-type") != "subject_type" || blDateField)) {
            oNewValue = createComplexFilterSubjectTypeSelect(sCurrentValue);
            oNewValue = replaceComplexFilterValueControl(oValue, oNewValue);
            oValue = oNewValue;
        } else if (sValueType == "address_type" && (sTagName != "select" || oValue.getAttribute("data-value-type") != "address_type" || blDateField)) {
            oNewValue = createComplexFilterAddressTypeSelect(sCurrentValue);
            oNewValue = replaceComplexFilterValueControl(oValue, oNewValue);
            oValue = oNewValue;
        } else if (isComplexFilterDateValueType(sValueType) && !blDateField) {
            oNewValue = createComplexFilterDateField(sCurrentValue, sValueType);
            oNewValue = replaceComplexFilterValueControl(oValue, oNewValue);
            oValue = oNewValue;
        } else if (isComplexFilterDateValueType(sValueType) && blDateField) {
            oValue.setAttribute("data-value-type", sValueType);
            prepareComplexFilterDateInput(oValue, sValueType);
            oValue.value = normalizeComplexFilterInputValue(oValue.value, sValueType);
        } else if (!isComplexFilterSelectValueType(sValueType) && (sTagName == "select" || blDateField)) {
            oNewValue = createComplexFilterTextInput(sCurrentValue, sValueType);
            oNewValue = replaceComplexFilterValueControl(oValue, oNewValue);
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
        }, 0);
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
        }, 300);
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
            });
        }
        if (oOperator) {
            oOperator.addEventListener("change", function () {
                updateRowValueState(oRow);
                scheduleDraftSave();
            });
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
            });
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
        removeComplexFilterDateCalendars();
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
        if (!openAdminDialogElement(oDialog, closeDialog)) {
            return;
        }
        bindRows();

        document.addEventListener("keydown", closeOnEscape);
        focusAdminElement(findFirstAdminUserInput(oForm), true);
    }

    function closeDialog() {
        if (!oDialog || oDialog.hidden) {
            return;
        }

        document.removeEventListener("keydown", closeOnEscape);
        saveDraftNow();
        removeComplexFilterDateCalendars();
        closeAdminDialogElement(oDialog);
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
        });
    }
    oOpen.addEventListener("click", function () {
        openDialog();
    });
    if (oClose) {
        oClose.addEventListener("click", function () {
            closeDialog();
        });
    }
    if (oCancel) {
        oCancel.addEventListener("click", function () {
            closeDialog();
        });
    }
    if (oAdd) {
        oAdd.addEventListener("click", function () {
            addBlankRow();
        });
    }
    if (oReset) {
        oReset.addEventListener("click", function () {
            setMatchValue("all");
            resetDialogRows();
            saveDraftNow();
            focusAdminElement(oDialog.querySelector(".js-complex-filter-field"));
        });
    }
    oForm.addEventListener("submit", function () {
        prepareApplySubmit();
    });
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
        }, 0);
    });
    document.addEventListener("keydown", function (oEvent) {
        if ((oEvent.key != "F8" && oEvent.keyCode != 119) || oEvent.altKey || oEvent.ctrlKey || oEvent.metaKey || oEvent.shiftKey) {
            return;
        }
        if (isAdminOverlayActive()) {
            oEvent.preventDefault();
            return;
        }
        oEvent.preventDefault();
        oButton.click();
    });
});

document.addEventListener("DOMContentLoaded", function () {
    var oTable = document.getElementById("birthdays-table");
    if (!oTable) {
        return;
    }
    oTable.addEventListener("click", function (oEvent) {
        var oTarget = oEvent.target;
        var oButton = null;
        var oRow;
        var oData;
        var sDefaultMessage = "Birthday could not be marked served.";
        while (oTarget && oTarget !== oTable) {
            if (oTarget.nodeType === 1 && (" " + oTarget.className + " ").indexOf(" js-birthday-served ") !== -1) {
                oButton = oTarget;
                break;
            }
            oTarget = oTarget.parentNode;
        }
        if (!oButton) {
            return;
        }
        oEvent.preventDefault();
        if (!window.fetch || !window.FormData) {
            showAdminMessageDialog(sDefaultMessage);
            return;
        }
        if (oButton.disabled) {
            return;
        }
        oRow = oButton;
        while (oRow && oRow !== oTable) {
            if (oRow.nodeType === 1 && oRow.tagName && oRow.tagName.toLowerCase() == "tr" && oRow.getAttribute("data-subject-id") !== null) {
                break;
            }
            oRow = oRow.parentNode;
        }
        if (oRow === oTable) {
            oRow = null;
        }
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
            return oResponse.text().then(function (sText) {
                var aData = null;
                var sMessage;
                if (sText) {
                    try {
                        aData = JSON.parse(sText);
                    } catch (oException) {
                        aData = null;
                    }
                }
                if (aData) {
                    return aData;
                }
                sMessage = (sText || "").replace(/<script[\s\S]*?<\/script>/gi, " ").replace(/<style[\s\S]*?<\/style>/gi, " ").replace(/<[^>]+>/g, " ").replace(/&nbsp;/g, " ");
                sMessage = sMessage.replace(/\s+/g, " ").replace(/^\s+|\s+$/g, "");
                throw new Error(sMessage || sDefaultMessage);
            });
        }).then(function (aData) {
            if (!aData || !aData.success) {
                oButton.disabled = false;
                showAdminMessageDialog(aData && aData.message ? aData.message : sDefaultMessage);
                return;
            }
            if (oRow && oRow.parentNode) {
                oRow.parentNode.removeChild(oRow);
            }
        }).catch(function (oException) {
            logAdminException(oException);
            oButton.disabled = false;
            showAdminMessageDialog(oException && oException.message ? oException.message : sDefaultMessage);
        });
    });
});

function closeExMenu(oMenu) {
    var oButton = oMenu ? oMenu.querySelector("[data-menu-button]") : null;
    var oPanel = oMenu ? oMenu.querySelector("[data-menu-panel]") : null;
    if (oPanel) {
        oPanel.hidden = true;
    }
    if (oButton) {
        oButton.setAttribute("aria-expanded", "false");
    }
}

function closeExMenus(oExcept) {
    var aMenus = document.querySelectorAll("[data-menu]");
    for (var iI = 0; iI < aMenus.length; iI += 1) {
        if (aMenus[iI] !== oExcept) {
            closeExMenu(aMenus[iI]);
        }
    }
}

document.addEventListener("DOMContentLoaded", function () {
    var aMenus = document.querySelectorAll("[data-menu]");
    var blSuppressNextMenuLinkClick = false;

    function openExMenu(oMenu) {
        var oButton = oMenu ? oMenu.querySelector("[data-menu-button]") : null;
        var oPanel = oMenu ? oMenu.querySelector("[data-menu-panel]") : null;
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
    function getExVisibleMenuLinkAtMouseEvent(oEvent) {
        var oElement;
        var oMenu;
        var oPanel;
        var oMenuLink = oEvent.target && oEvent.target.closest ? oEvent.target.closest(".menu-link") : null;
        if (oMenuLink && oMenuLink.closest) {
            oMenu = oMenuLink.closest("[data-menu]");
            oPanel = oMenu ? oMenu.querySelector("[data-menu-panel]") : null;
        }
        if (oPanel && !oPanel.hidden) {
            return oMenuLink;
        }
        if (!document.elementFromPoint || typeof oEvent.clientX == "undefined" || typeof oEvent.clientY == "undefined") {
            return null;
        }
        oElement = document.elementFromPoint(oEvent.clientX, oEvent.clientY);
        oMenuLink = oElement && oElement.closest ? oElement.closest(".menu-link") : null;
        if (oMenuLink && oMenuLink.closest) {
            oMenu = oMenuLink.closest("[data-menu]");
            oPanel = oMenu ? oMenu.querySelector("[data-menu-panel]") : null;
        }
        if (oPanel && !oPanel.hidden) {
            return oMenuLink;
        }
        return null;
    }

    function activateExMenuLink(oMenuLink) {
        var sHref = oMenuLink ? oMenuLink.href : "";
        var sTarget = oMenuLink ? oMenuLink.getAttribute("target") || "" : "";
        if (!sHref) {
            return;
        }
        if (sTarget == "" || sTarget == "_self") {
            window.location.href = sHref;
        } else if (sTarget == "_parent") {
            window.parent.location.href = sHref;
        } else if (sTarget == "_top") {
            window.top.location.href = sHref;
        } else {
            window.open(sHref, sTarget);
        }
    }

    for (var iI = 0; iI < aMenus.length; iI += 1) {
        (function (oMenu) {
            var oButton = oMenu.querySelector("[data-menu-button]");
            var oPanel = oMenu.querySelector("[data-menu-panel]");
            var blSkipButtonClick = false;
            if (!oButton || !oPanel) {
                return;
            }
            oButton.addEventListener("mousedown", function (oEvent) {
                if (typeof oEvent.button != "undefined" && oEvent.button !== 0) {
                    return;
                }
                if (!oPanel.hidden) {
                    blSkipButtonClick = false;
                    return;
                }
                blSkipButtonClick = true;
                openExMenu(oMenu);
                oEvent.preventDefault();
            });
            oButton.addEventListener("click", function (oEvent) {
                oEvent.preventDefault();
                oEvent.stopPropagation();
                if (blSkipButtonClick) {
                    blSkipButtonClick = false;
                    return;
                }
                if (oPanel.hidden) {
                    openExMenu(oMenu);
                } else {
                    closeExMenu(oMenu);
                }
            });
        })(aMenus[iI]);
    }

    document.addEventListener("mousedown", function (oEvent) {
        var oMenu = oEvent.target.closest ? oEvent.target.closest("[data-menu]") : null;
        if (!oMenu) {
            closeExMenus(null);
        }
    }, true);

    document.addEventListener("mouseup", function (oEvent) {
        var oElement;
        var oMenu;
        var oMenuLink;
        oMenuLink = getExVisibleMenuLinkAtMouseEvent(oEvent);
        if (oMenuLink) {
            blSuppressNextMenuLinkClick = true;
            window.setTimeout(function () {
                blSuppressNextMenuLinkClick = false;
            }, 0);
            activateExMenuLink(oMenuLink);
            oEvent.preventDefault();
            oEvent.stopPropagation();
            return;
        }
        if (document.elementFromPoint && typeof oEvent.clientX != "undefined" && typeof oEvent.clientY != "undefined") {
            oElement = document.elementFromPoint(oEvent.clientX, oEvent.clientY);
        }
        if (!oElement) {
            oElement = oEvent.target;
        }
        oMenu = oElement && oElement.closest ? oElement.closest("[data-menu]") : null;
        if (!oMenu) {
            closeExMenus(null);
        }
    }, true);

    document.addEventListener("click", function (oEvent) {
        if (blSuppressNextMenuLinkClick) {
            blSuppressNextMenuLinkClick = false;
            oEvent.preventDefault();
            oEvent.stopPropagation();
        }
    }, true);

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
        }, 1500);
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
        });
    }
});

document.addEventListener("click", function (oEvent) {
    var oButton = oEvent.target.closest ? oEvent.target.closest(".copy-action") : null;
    var sValue;
    if (!oButton) {
        return;
    }
    oEvent.preventDefault();
    oEvent.stopPropagation();
    sValue = oButton.getAttribute("data-copy-value") || "";


    function showCopyValueResult(blSuccess) {
        var oBox = oButton.querySelector ? oButton.querySelector(".copy-action-box") : null;
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
        }, 1000);
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
        if ((" " + oSourceRow.className + " ").indexOf(" admin-row-modal ") !== -1) {
            addAdminClass(oTargetRow, "admin-row-modal");
        }
        if (oSourceRow.getAttribute("data-selected") == "1") {
            oTargetRow.setAttribute("data-selected", "1");
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
        var aRows;
        var iI;
        if (!oRow || isInsideTableRow(oRow, oEvent.relatedTarget)) {
            return;
        }
        aRows = document.querySelectorAll("table tbody tr[data-hover=\"1\"]");
        for (iI = 0; iI < aRows.length; iI += 1) {
            if (aRows[iI] !== oRow) {
                aRows[iI].setAttribute("data-hover", "0");
                applyRowColor(aRows[iI]);
            }
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

    window.copyAdminTableRowState = copyTableRowState;
    window.bindAdminTableRow = bindTableRow;
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

    function isPointBetween(iFirst, iMiddle, iLast) {
        return (iFirst <= iMiddle && iMiddle <= iLast) || (iLast <= iMiddle && iMiddle <= iFirst);
    }

    function isRedundantPolylinePoint(oPrevious, oPoint, oNext) {
        if (oPrevious.x == oPoint.x && oPoint.x == oNext.x) {
            return isPointBetween(oPrevious.y, oPoint.y, oNext.y);
        }
        if (oPrevious.y == oPoint.y && oPoint.y == oNext.y) {
            return isPointBetween(oPrevious.x, oPoint.x, oNext.x);
        }
        return false;
    }

    function cleanPolylinePoints(aPoints) {
        var aCleanPoints = [];
        for (var iI = 0; iI < aPoints.length; iI += 1) {
            if (iI > 0 && iI + 1 < aPoints.length && isRedundantPolylinePoint(aPoints[iI - 1], aPoints[iI], aPoints[iI + 1])) {
                continue;
            }
            aCleanPoints.push(aPoints[iI]);
        }
        return aCleanPoints;
    }

    function getRoundedPolylinePath(aPoints, iRadius) {
        aPoints = cleanPolylinePoints(aPoints);
        var sPath = "M " + aPoints[0].x + " " + aPoints[0].y;
        for (var iI = 1; iI < aPoints.length - 1; iI += 1) {
            var oPrevious = aPoints[iI - 1];
            var oPoint = aPoints[iI];
            var oNext = aPoints[iI + 1];
            var iPreviousDistance = Math.sqrt(Math.pow(oPoint.x - oPrevious.x, 2) + Math.pow(oPoint.y - oPrevious.y, 2));
            var iNextDistance = Math.sqrt(Math.pow(oNext.x - oPoint.x, 2) + Math.pow(oNext.y - oPoint.y, 2));
            var iPreviousRadiusLimit = iI == 1 ? iPreviousDistance : iPreviousDistance / 2;
            var iNextRadiusLimit = iI + 1 == aPoints.length - 1 ? iNextDistance : iNextDistance / 2;
            var iCornerRadius = Math.min(iRadius, iPreviousRadiusLimit, iNextRadiusLimit);
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
    });
});

document.addEventListener("DOMContentLoaded", function () {
    var aFilters = document.querySelectorAll(".js-table-filter");

    function escapeFilterRegex(sValue) {
        return sValue.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
    }

    function buildPhoneFilterPattern(sDigits) {
        var aParts = [];
        for (var iI = 0; iI < sDigits.length; iI += 1) {
            aParts.push(sDigits.charAt(iI));
        }
        return aParts.join("[\\s\\u00a0().+\\-\\/]*");
    }

    function appendPhoneFilterPattern(aPatterns, sDigits) {
        for (var iI = 0; iI < aPatterns.length; iI += 1) {
            if (aPatterns[iI]["digits"] == sDigits) {
                return;
            }
        }
        if (sDigits.length >= 5) {
            aPatterns.push({
                "digits": sDigits,
                "pattern": buildPhoneFilterPattern(sDigits)
            });
        }
    }

    function buildPhoneFilterRegex(sTerm) {
        var sPhoneText = sTerm.replace(/^\s+|\s+$/g, "");
        var sDigits = sPhoneText.replace(/\D/g, "");
        var aPatterns = [];
        var aRegexPatterns = [];
        if (sDigits.length < 5 || sPhoneText.replace(/[0-9\s\u00a0().+\-\/]/g, "") != "") {
            return null;
        }
        appendPhoneFilterPattern(aPatterns, sDigits);
        if (sDigits.substring(0, 2) == "00") {
            appendPhoneFilterPattern(aPatterns, sDigits.substring(2));
        }
        for (var iI = 0; iI < aPatterns.length; iI += 1) {
            aRegexPatterns.push(aPatterns[iI]["pattern"]);
        }
        return aRegexPatterns.length > 0 ? new RegExp(aRegexPatterns.join("|"), "i") : null;
    }

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
                        "regex": new RegExp(escapeFilterRegex(sTerm).replace(/\s+/g, "\\s+"), "i"),
                        "phone_regex": buildPhoneFilterRegex(sTerm),
                        "negated": blNegated
                    });
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
                if (!blFound && aExpression[iI][iJ]["phone_regex"]) {
                    blFound = aExpression[iI][iJ]["phone_regex"].test(sRowText);
                }
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
        }, 250);
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
            }, 250);
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
            });
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
            });
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
    var oTable = document.getElementById("groups-table");
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
            if (sGroupId !== "" && aStates[sGroupId] && window.copyAdminTableRowState) {
                window.copyAdminTableRowState(aStates[sGroupId], aRows[iI]);
            }
            if (window.bindAdminTableRow) {
                window.bindAdminTableRow(aRows[iI]);
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
        if (oCurrentRow && window.copyAdminTableRowState) {
            window.copyAdminTableRowState(oCurrentRow, oNewRow);
        }
        if (oCurrentRow) {
            oCurrentRow.parentNode.replaceChild(oNewRow, oCurrentRow);
        } else {
            oTable.querySelector("tbody").appendChild(oNewRow);
        }
        if (window.bindAdminTableRow) {
            window.bindAdminTableRow(oNewRow);
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
        oDialogData.dialog = prepareAdminReusableDialog();
        oDialogData.form = document.createElement("form");
        oDialogData.box = oDialogData.form;
        oDialogData.header = document.createElement("div");
        oDialogData.title = document.createElement("strong");
        oDialogData.closeButton = document.createElement("button");
        oDialogData.error = document.createElement("p");
        oDialogData.actions = document.createElement("div");
        oDialogData.save = document.createElement("button");
        oDialogData.cancel = document.createElement("button");
        if (!oDialogData.dialog) {
            return null;
        }
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
            closeAdminDialogElement(oDialogData.dialog);
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
        enableAdminDialogDrag(oDialogData.dialog, oDialogData.box, oDialogData.header);

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
        if (!openAdminDialogElement(oDialogData.dialog, oDialogData.close)) {
            return;
        }
        beginAdminSubjectRowEdit(findAdminGroupRowById(oDialogData.groupId) || oDialogData.groupRow);
        focusAdminElement(findFirstAdminUserInput(oDialogData.form) || oFocus, true);
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
        });
    }

    function openGroupAdminDialog(oRow) {
        var blNewGroup = !oRow;
        var oDialogData = createGroupDialog(blNewGroup ? "New Group" : "Edit Group", oRow);
        if (!oDialogData) {
            return;
        }
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
        if (!oDialogData) {
            return;
        }
        oDialogData.save.textContent = "Yes";
        oDialogData.cancel.textContent = "No";
        setAdminConfirmMessage(oText, "Delete this group?", oRow.getAttribute("data-group-name") || "");
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
        if (!oDialogData) {
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
        var sGroupId;
        if (!oRow) {
            return;
        }
        sGroupId = oRow.getAttribute("data-group-id") || "";
        oData.append("action", "move_group");
        oData.append("group_id", sGroupId);
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
                finishAdminSubjectRowEdit(findAdminGroupRowById(sGroupId), true);
            }
        }).catch(function (oException) {
            logAdminException(oException);
        });
    }

    if (oAdd) {
        oAdd.addEventListener("click", function () {
            openGroupAdminDialog(null);
        });
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
    });
});

document.addEventListener("DOMContentLoaded", function () {
    var oTable = document.getElementById("interactions-table");
    if (!oTable) {
        return;
    }
    oTable.addEventListener("click", function (oEvent) {
        var oTarget = oEvent.target;
        var oButton = null;
        var oRow;
        var oData;
        var sDefaultMessage = "Communication could not be marked served.";
        while (oTarget && oTarget !== oTable) {
            if (oTarget.nodeType === 1 && (" " + oTarget.className + " ").indexOf(" js-communication-served ") !== -1) {
                oButton = oTarget;
                break;
            }
            oTarget = oTarget.parentNode;
        }
        if (!oButton) {
            return;
        }
        oEvent.preventDefault();
        if (!window.fetch || !window.FormData) {
            showAdminMessageDialog(sDefaultMessage);
            return;
        }
        if (oButton.disabled) {
            return;
        }
        oRow = oButton;
        while (oRow && oRow !== oTable) {
            if (oRow.nodeType === 1 && oRow.tagName && oRow.tagName.toLowerCase() == "tr" && oRow.getAttribute("data-subject-id") !== null) {
                break;
            }
            oRow = oRow.parentNode;
        }
        if (oRow === oTable) {
            oRow = null;
        }
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
            return oResponse.text().then(function (sText) {
                var aData = null;
                var sMessage;
                if (sText) {
                    try {
                        aData = JSON.parse(sText);
                    } catch (oException) {
                        aData = null;
                    }
                }
                if (aData) {
                    return aData;
                }
                sMessage = (sText || "").replace(/<script[\s\S]*?<\/script>/gi, " ").replace(/<style[\s\S]*?<\/style>/gi, " ").replace(/<[^>]+>/g, " ").replace(/&nbsp;/g, " ");
                sMessage = sMessage.replace(/\s+/g, " ").replace(/^\s+|\s+$/g, "");
                throw new Error(sMessage || sDefaultMessage);
            });
        }).then(function (aData) {
            if (!aData || !aData.success) {
                oButton.disabled = false;
                showAdminMessageDialog(aData && aData.message ? aData.message : sDefaultMessage);
                return;
            }
            if (oRow && oRow.parentNode) {
                oRow.parentNode.removeChild(oRow);
            }
        }).catch(function (oException) {
            logAdminException(oException);
            oButton.disabled = false;
            showAdminMessageDialog(oException && oException.message ? oException.message : sDefaultMessage);
        });
    });
});

document.addEventListener("DOMContentLoaded", function () {
    var oTable = document.getElementById("contact-types-table");
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
            if (sContactTypeId !== "" && aStates[sContactTypeId] && window.copyAdminTableRowState) {
                window.copyAdminTableRowState(aStates[sContactTypeId], aRows[iI]);
            }
            if (window.bindAdminTableRow) {
                window.bindAdminTableRow(aRows[iI]);
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
        if (oCurrentRow && window.copyAdminTableRowState) {
            window.copyAdminTableRowState(oCurrentRow, oNewRow);
        }
        if (oCurrentRow && oCurrentRow.parentNode) {
            oCurrentRow.parentNode.replaceChild(oNewRow, oCurrentRow);
        } else {
            oTable.querySelector("tbody").appendChild(oNewRow);
        }
        if (window.bindAdminTableRow) {
            window.bindAdminTableRow(oNewRow);
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
        var oDialog = prepareAdminReusableDialog();
        var oForm = document.createElement("form");
        var oBox = oForm;
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
        if (!oDialog) {
            return null;
        }
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
        enableAdminDialogDrag(oDialog, oBox, oHeader);
        oClose.addEventListener("click", function () {
            closeDialog();
        });
        oCancel.addEventListener("click", function () {
            closeDialog();
        });

        document.addEventListener("keydown", closeOnEscape);
        return {
            "dialog": oDialog,
            "box": oBox,
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
        if (!openAdminDialogElement(oDialogData.dialog, oDialogData.close)) {
            return;
        }
        beginAdminSubjectRowEdit(findAdminContactTypeRowById(oDialogData.contactTypeId) || oDialogData.contactTypeRow);
        focusAdminElement(findFirstAdminUserInput(oDialogData.form) || oFocus, true);
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
        });
    }

    function openContactTypeAdminDialog(oRow) {
        var blNewContactType = !oRow;
        var oDialogData = createContactTypeDialog(blNewContactType ? "New Contact Type" : "Edit Contact Type", oRow);
        if (!oDialogData) {
            return;
        }
        var oName = appendContactTypeTextField(oDialogData.form, "Name", "contact_type_name", oRow ? (oRow.getAttribute("data-contact-type-name") || "") : "");
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
        if (!oDialogData) {
            return;
        }
        oDialogData.save.textContent = "Yes";
        oDialogData.cancel.textContent = "No";
        setAdminConfirmMessage(oText, "Delete this contact type?", oRow.getAttribute("data-contact-type-name") || "");
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
        if (!oDialogData) {
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
        var sContactTypeId;
        if (!oRow) {
            return;
        }
        sContactTypeId = oRow.getAttribute("data-contact-type-id") || "";
        oData.append("action", "move_contact_type");
        oData.append("contact_type_id", sContactTypeId);
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
                finishAdminSubjectRowEdit(findAdminContactTypeRowById(sContactTypeId), true);
            }
        }).catch(function (oException) {
            logAdminException(oException);
        });
    }

    if (oAdd) {
        oAdd.addEventListener("click", function () {
            openContactTypeAdminDialog(null);
        });
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
    });
});

document.addEventListener("DOMContentLoaded", function () {
    var aSubjectButtons = document.querySelectorAll(".js-add-subject, .js-add-subject-nickname, .js-add-subject-address, .js-add-subject-group, .js-add-subject-note, .js-edit-subject, .js-edit-subject-portal, .js-edit-subject-nickname, .js-edit-subject-address, .js-edit-subject-group, .js-edit-subject-note, .js-delete-subject, .js-delete-subject-contact, .js-delete-subject-nickname, .js-delete-subject-address, .js-delete-subject-group, .js-delete-subject-note");
    var iSubjectCalendarFirstDay = 1;
    var sSubjectDateInputFormat = "YYYY-MM-DD";
    var blHideSubjectBirthNumber = false;
    var blShowComputedSubjectName = false;
    var aSubjectCountryCodes = ("AD AE AF AG AI AL AM AO AQ AR AS AT AU AW AX AZ BA BB BD BE BF BG BH BI BJ BL BM BN BO BQ BR BS BT BV BW BY BZ CA CC CD CF CG CH CI CK CL CM CN CO CR CS CU CV CW CX CY CZ DE DJ DK DM DO DZ EC EE EG EH ER ES ET FI FJ FK FM FO FR GA GB GD GE GF GG GH GI GL GM GN GP GQ GR GS GT GU GW GY HK HM HN HR HT HU ID IE IL IM IN IO IQ IR IS IT JE JM JO JP KE KG KH KI KM KN KP KR KW KY KZ LA LB LC LI LK LR LS LT LU LV LY MA MC MD ME MF MG MH MK ML MM MN MO MP MQ MR MS MT MU MV MW MX MY MZ NA NC NE NF NG NI NL NO NP NR NU NZ OM PA PE PF PG PH PK PL PM PN PR PS PT PW PY QA RE RO RS RU RW SA SB SC SD SE SG SH SI SJ SK SL SM SN SO SR SS ST SV SX SY SZ TC TD TF TG TH TJ TK TL TM TN TO TR TT TV TW TZ UA UG UM US UY UZ VA VC VE VG VI VN VU WF WS YE YT ZA ZM ZW").split(" ");
    var aSubjectCountryOptions = null;
    if (aSubjectButtons.length === 0 || !window.fetch || !window.FormData || !window.JSON) {
        return;
    }
    if (document.body) {
        iSubjectCalendarFirstDay = parseInt(document.body.getAttribute("data-calendar-first-day") || "1", 10);
        sSubjectDateInputFormat = document.body.getAttribute("data-date-input-format") || sSubjectDateInputFormat;
        blHideSubjectBirthNumber = document.body.getAttribute("data-hide-subject-birth-number") == "1";
        blShowComputedSubjectName = document.body.getAttribute("data-show-computed-subject-name") == "1";
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
        closeAdminDialogElement(oDialog);
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
            showAdminMessageDialog("Dummy data could not be loaded.");
            return null;
        }
    }

    function getSubjectItemValue(oItem, sName) {
        return oItem ? (oItem.getAttribute(sName) || "") : "";
    }

    function getSubjectNoteText(oItem) {
        var oSource = oItem ? oItem.querySelector(".subject-note-source") : null;
        return oSource ? oSource.textContent : getSubjectItemValue(oItem, "data-note-text");
    }

    function getSubjectItemText(oItem) {
        var oValue = oItem ? oItem.querySelector(".subject-item-value") : null;
        return getAdminElementText(oValue);
    }

    function getSubjectContactText(oItem) {
        return getAdminContactItemText(oItem);
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
        var oParsed = parseAdminFlexibleDateTime(sValue);
        return oParsed ? oParsed.date : null;
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
        oInput.placeholder = sSubjectDateInputFormat;
        oInput.maxLength = 19;
        oInput.autocomplete = "off";
        oInput.setAttribute("data-date-input-kind", "date");
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
            normalizeAdminDateTimeInput(oInput, "date", false);
            window.setTimeout(function () {
                if (!oWrapper.contains(document.activeElement) && !oCalendar.contains(document.activeElement)) {
                    oCalendar.style.display = "none";
                }
            }, 0);
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

    function appendSubjectDisplayField(oParent, sLabel, sValue) {
        var oLabel = document.createElement("label");
        var oValue = document.createElement("div");
        oLabel.textContent = sLabel;
        oValue.className = "subject-computed-name";
        oValue.textContent = sValue || "";
        oParent.appendChild(oLabel);
        oParent.appendChild(oValue);
        return oValue;
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

    function normalizeSubjectCountrySearch(sValue) {
        var sSearch = (sValue || "").replace(/\u00A0/g, " ").replace(/\u200B/g, "").replace(/&/g, " and ");
        var aCzechChars = {
            "\u00C1": "a",
            "\u00C9": "e",
            "\u00CD": "i",
            "\u00D3": "o",
            "\u00DA": "u",
            "\u00DD": "y",
            "\u00E1": "a",
            "\u00E9": "e",
            "\u00ED": "i",
            "\u00F3": "o",
            "\u00FA": "u",
            "\u00FD": "y",
            "\u010C": "c",
            "\u010D": "c",
            "\u010E": "d",
            "\u010F": "d",
            "\u011A": "e",
            "\u011B": "e",
            "\u0147": "n",
            "\u0148": "n",
            "\u0158": "r",
            "\u0159": "r",
            "\u0160": "s",
            "\u0161": "s",
            "\u0164": "t",
            "\u0165": "t",
            "\u016E": "u",
            "\u016F": "u",
            "\u017D": "z",
            "\u017E": "z"
        };
        sSearch = sSearch.replace(/[-\u2010-\u2015\u2212]/g, " ");
        if (typeof sSearch.normalize == "function") {
            sSearch = sSearch.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        }
        sSearch = sSearch.replace(/[\u00C1\u00C9\u00CD\u00D3\u00DA\u00DD\u00E1\u00E9\u00ED\u00F3\u00FA\u00FD\u010C\u010D\u010E\u010F\u011A\u011B\u0147\u0148\u0158\u0159\u0160\u0161\u0164\u0165\u016E\u016F\u017D\u017E]/g, function (sChar) {
            return aCzechChars[sChar] || sChar;
        });
        return sSearch.toLowerCase().replace(/[^a-z0-9]+/g, " ").replace(/\s+/g, " ").replace(/^\s+|\s+$/g, "");
    }

    function findSubjectCountryAliasCode(sValue) {
        var sSearch = normalizeSubjectCountrySearch(sValue);
        var aAliases = {
            "britain": "GB",
            "ceska republika": "CZ",
            "cesko": "CZ",
            "ceskoslovensko": "CS",
            "czech republic": "CZ",
            "czechoslovakia": "CS",
            "great britain": "GB",
            "ivory coast": "CI",
            "u k": "GB",
            "u s a": "US",
            "uk": "GB",
            "united states of america": "US",
            "usa": "US"
        };
        return aAliases[sSearch] || "";
    }

    function findSubjectCountryDelimitedCode(sValue) {
        var sSearch = (sValue || "").replace(/^\s+|\s+$/g, "");
        var aMatches = sSearch.match(/^\s*([A-Za-z]{2})\s*[-\u2010-\u2015\u2212]\s*(.*?)\s*$/);
        var sCode;
        if (aMatches) {
            sCode = aMatches[1].toUpperCase();
            if (aSubjectCountryCodes.indexOf(sCode) !== -1) {
                return sCode;
            }
        }
        aMatches = sSearch.match(/^\s*(.*?)\s*[-\u2010-\u2015\u2212]\s*([A-Za-z]{2})\s*$/);
        if (aMatches) {
            sCode = aMatches[2].toUpperCase();
            if (aSubjectCountryCodes.indexOf(sCode) !== -1) {
                return sCode;
            }
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
            });
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
        var sDelimitedCode = findSubjectCountryDelimitedCode(sSearch);
        var sAliasCode = findSubjectCountryAliasCode(sSearch);
        var sSpecialCode = findSubjectCountrySpecialAlias(sSearch);
        var sNormalizedSearch = normalizeSubjectCountrySearch(sSearch);
        var aOptions;
        var iI;
        if (sSearch === "") {
            return "";
        }
        if (sDelimitedCode !== "") {
            return sDelimitedCode;
        }
        if (sAliasCode !== "") {
            return sAliasCode;
        }
        if (sSpecialCode !== "") {
            return sSpecialCode;
        }
        if (/^[A-Z]{2}$/.test(sUpper) && aSubjectCountryCodes.indexOf(sUpper) !== -1) {
            return sUpper;
        }
        aOptions = getSubjectCountryOptions();
        for (iI = 0; iI < aOptions.length; iI += 1) {
            if (normalizeSubjectCountrySearch(aOptions[iI].name) == sNormalizedSearch) {
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

    function getSubjectCountryDisplayName(sCode) {
        var sCountryCode = (sCode || "").replace(/^\s+|\s+$/g, "").toUpperCase();
        var aOptions;
        var iI;
        if (!/^[A-Z]{2}$/.test(sCountryCode)) {
            return "";
        }
        aOptions = getSubjectCountryOptions();
        for (iI = 0; iI < aOptions.length; iI += 1) {
            if (aOptions[iI].code == sCountryCode) {
                return aOptions[iI].code + " \u2014 " + aOptions[iI].name;
            }
        }
        return "";
    }

    function ensureSubjectCountryList() {
        var oList = document.getElementById("country-list");
        var aOptions;
        var oOption;
        var iI;
        if (oList) {
            return "country-list";
        }
        if (!document.body) {
            return "";
        }
        oList = document.createElement("datalist");
        oList.id = "country-list";
        aOptions = getSubjectCountryOptions();
        for (iI = 0; iI < aOptions.length; iI += 1) {
            oOption = document.createElement("option");
            oOption.value = aOptions[iI].code + " \u2014 " + aOptions[iI].name;
            oList.appendChild(oOption);
        }
        document.body.appendChild(oList);
        return "country-list";
    }

    function appendSubjectCountryField(oParent, sValue) {
        var oInput = appendSubjectTextField(oParent, "Country", "country_name", getSubjectCountryDisplayName(sValue));
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
                oInput.value = getSubjectCountryDisplayName(oHidden.value);
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
            if ((" " + oCurrentRow.className + " ").indexOf(" admin-row-modal ") !== -1) {
                addAdminClass(oNewRow, "admin-row-modal");
            }
            if (oCurrentRow.getAttribute("data-selected") == "1") {
                oNewRow.setAttribute("data-selected", "1");
            }
            oCurrentRow.parentNode.replaceChild(oNewRow, oCurrentRow);
            if (window.bindAdminTableRow) {
                window.bindAdminTableRow(oNewRow);
            }
        } else if (!oCurrentRow && oNewRow) {
            oTableBody = document.querySelector("#subjects-table tbody, #birthdays-table tbody, #interactions-table tbody, #contacts-table tbody");
            if (oTableBody) {
                oTableBody.appendChild(oNewRow);
                if (window.bindAdminTableRow) {
                    window.bindAdminTableRow(oNewRow);
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

    window.replaceSubjectRow = replaceSubjectRow;
    window.removeSubjectRow = removeSubjectRow;

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
        aItems = document.querySelectorAll(".subject-group-item[data-group-id=\"" + aGroup.group_id + "\"]");
        for (iI = 0; iI < aItems.length; iI += 1) {
            aItems[iI].setAttribute("data-group-name", aGroup.name || "");
            if (sTimestampTooltip) {
                aItems[iI].setAttribute("data-timestamp-tooltip", sTimestampTooltip);
            }
            oValue = aItems[iI].querySelector(".subject-item-value");
            if (oValue) {
                oValue.textContent = aGroup.name || "";
                if (sTimestampTooltip) {
                    oValue.title = sTimestampTooltip;
                }
            }
        }
        oGroupList = document.getElementById("group-list");
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
        oDialogData.dialog = prepareAdminReusableDialog();
        oDialogData.form = document.createElement("form");
        oDialogData.box = oDialogData.form;
        oDialogData.header = document.createElement("div");
        oDialogData.title = document.createElement("strong");
        oDialogData.closeButton = document.createElement("button");
        oDialogData.error = document.createElement("p");
        oDialogData.actions = document.createElement("div");
        oDialogData.save = document.createElement("button");
        oDialogData.cancel = document.createElement("button");
        if (!oDialogData.dialog) {
            return null;
        }
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
        enableAdminDialogDrag(oDialogData.dialog, oDialogData.box, oDialogData.header);

        document.addEventListener("keydown", closeOnEscape);
        return oDialogData;
    }

    function finishSubjectDialog(oDialogData, oFocus) {
        oDialogData.form.appendChild(oDialogData.error);
        oDialogData.actions.appendChild(oDialogData.save);
        oDialogData.actions.appendChild(oDialogData.cancel);
        oDialogData.form.appendChild(oDialogData.actions);
        oDialogData.dialog.appendChild(oDialogData.form);
        oDialogData.openedAt = new Date().getTime();
        if (!openAdminDialogElement(oDialogData.dialog, oDialogData.close)) {
            return;
        }
        beginAdminSubjectRowEdit(oDialogData.getCurrentSubjectRow());
        focusAdminElement(findFirstAdminUserInput(oDialogData.form) || oFocus, true);
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
        });
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
        if (!oDialogData) {
            return;
        }
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
        if (!blNewSubject && blShowComputedSubjectName) {
            appendSubjectDisplayField(oDialogData.form, "Computed Name", getSubjectValue(aSubject, "subject_name"));
        }
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
        }, null, true);
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
                showAdminMessageDialog(aData && aData.message ? aData.message : "Subject could not be loaded.");
                return;
            }
            openSubjectDialog(aData.subject, getAdminSubjectRow(oButton), false);
        }).catch(function (oException) {
            logAdminException(oException);
            oButton.disabled = false;
            showAdminMessageDialog("Subject could not be loaded.");
        });
    }

    function openSubjectPortalDialog(aSubject, oSubjectRow) {
        var oDialogData = createSubjectDialog("Portal Account", oSubjectRow || findAdminSubjectRowById(getSubjectValue(aSubject, "subject_id")));
        var aPortalUser = aSubject && aSubject.portal_user ? aSubject.portal_user : null;
        var aPortalPermissions = aSubject && aSubject.portal_permissions ? aSubject.portal_permissions : [];
        var oPortalEnabled;
        var oPortalUserName;
        var oPortalPassword;
        var oPortalActive;
        var oPortalSessionTimeout;
        var oPermissionBox;
        var oPermissionTitle;
        var oLabel;
        var oInput;
        var aPortalPermissionInputs = [];
        var setPortalFields;
        var iI;
        if (!oDialogData) {
            return;
        }
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
        oPortalSessionTimeout = appendSubjectTextField(oDialogData.form, "Session Timeout", "portal_session_timeout", aPortalUser["session_timeout"] || "1200", "number");
        oPortalSessionTimeout.min = "60";
        oPortalSessionTimeout.step = "1";
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
            oPortalSessionTimeout.disabled = !blEnabled;
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
            oData.append("portal_session_timeout", oPortalSessionTimeout.value || "1200");
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
                showAdminMessageDialog(aData && aData.message ? aData.message : "Portal account could not be loaded.");
                return;
            }
            openSubjectPortalDialog(aData.subject, getAdminSubjectRow(oButton));
        }).catch(function (oException) {
            logAdminException(oException);
            oButton.disabled = false;
            showAdminMessageDialog("Portal account could not be loaded.");
        });
    }

    function openNicknameDialog(oItem, oSubjectRow, blNewNickname) {
        var oDialogData = createSubjectDialog(blNewNickname ? "New Nickname" : "Edit Nickname", blNewNickname ? oSubjectRow : getAdminSubjectRow(oItem));
        var sSubjectId = blNewNickname && oSubjectRow ? (oSubjectRow.getAttribute("data-subject-id") || "") : getSubjectItemValue(oItem, "data-subject-id");
        if (!oDialogData) {
            return;
        }
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
        if (!oDialogData) {
            return;
        }
        oDialogData.box.className += " subject-address-edit-dialog";
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
        if (!oDialogData) {
            return;
        }
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
        var attachGroupList;
        if (!oDialogData) {
            return;
        }
        var oName = appendSubjectTextField(oDialogData.form, "Name", "name", "");
        if (document.getElementById("group-list")) {
            attachGroupList = function () {
                if (oName.getAttribute("list") != "group-list") {
                    oName.setAttribute("list", "group-list");
                }
            };
            oName.addEventListener("keydown", attachGroupList);
            oName.addEventListener("mousedown", attachGroupList);
            oName.addEventListener("touchstart", attachGroupList);
            oName.addEventListener("input", attachGroupList);
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
        if (!oDialogData) {
            return;
        }
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

    function openDeleteDialog(sTitle, sMessage, aParams, oSubjectRow, sDetail) {
        var oDialogData = createSubjectDialog(sTitle, oSubjectRow);
        var oText = document.createElement("p");
        if (!oDialogData) {
            return;
        }
        setAdminConfirmMessage(oText, sMessage, sDetail);
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
            ], getAdminSubjectRow(oButton), oButton.getAttribute("data-subject-name") || "");
    }

    function openDeleteContactDialog(oItem) {
        openDeleteDialog("Confirm Deletion", "Remove this contact from the subject?", [{
                    "name": "action",
                    "value": "delete_subject_contact"
                }, {
                    "name": "subject_contact_id",
                    "value": getSubjectItemValue(oItem, "data-subject-contact-id")
                }
            ], getAdminSubjectRow(oItem), getSubjectContactText(oItem));
    }

    function openDeleteNicknameDialog(oItem) {
        openDeleteDialog("Confirm Deletion", "Delete this nickname?", [{
                    "name": "action",
                    "value": "delete_subject_nickname"
                }, {
                    "name": "nickname_id",
                    "value": getSubjectItemValue(oItem, "data-nickname-id")
                }
            ], getAdminSubjectRow(oItem), getSubjectItemText(oItem));
    }

    function openDeleteAddressDialog(oItem) {
        openDeleteDialog("Confirm Deletion", "Delete this address?", [{
                    "name": "action",
                    "value": "delete_subject_address"
                }, {
                    "name": "address_id",
                    "value": getSubjectItemValue(oItem, "data-address-id")
                }
            ], getAdminSubjectRow(oItem), getSubjectItemText(oItem));
    }

    function openDeleteGroupDialog(oItem) {
        openDeleteDialog("Confirm Deletion", "Remove the subject from this group?", [{
                    "name": "action",
                    "value": "delete_subject_group"
                }, {
                    "name": "subject_id",
                    "value": getSubjectItemValue(oItem, "data-subject-id")
                }, {
                    "name": "group_id",
                    "value": getSubjectItemValue(oItem, "data-group-id")
                }
            ], getAdminSubjectRow(oItem), getSubjectItemValue(oItem, "data-group-name"));
    }

    function openDeleteNoteDialog(oItem) {
        openDeleteDialog("Confirm Deletion", "Delete this note?", [{
                    "name": "action",
                    "value": "delete_subject_note"
                }, {
                    "name": "note_id",
                    "value": getSubjectItemValue(oItem, "data-note-id")
                }
            ], getAdminSubjectRow(oItem), getSubjectNoteText(oItem));
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
                openNicknameDialog(oButton.closest(".subject-nickname-item"), null, false);
            } else if (oButton.className.indexOf("js-edit-subject-address") !== -1) {
                openAddressDialog(oButton.closest(".subject-address-item"), null, false);
            } else if (oButton.className.indexOf("js-edit-subject-group") !== -1) {
                openGroupDialog(oButton.closest(".subject-group-item"));
            } else if (oButton.className.indexOf("js-edit-subject-note") !== -1) {
                openNoteDialog(oButton.closest(".subject-note-item"), null, false);
            } else if (oButton.className.indexOf("js-delete-subject-contact") !== -1) {
                openDeleteContactDialog(oButton.closest(".contact-item"));
            } else if (oButton.className.indexOf("js-delete-subject-nickname") !== -1) {
                openDeleteNicknameDialog(oButton.closest(".subject-nickname-item"));
            } else if (oButton.className.indexOf("js-delete-subject-address") !== -1) {
                openDeleteAddressDialog(oButton.closest(".subject-address-item"));
            } else if (oButton.className.indexOf("js-delete-subject-group") !== -1) {
                openDeleteGroupDialog(oButton.closest(".subject-group-item"));
            } else if (oButton.className.indexOf("js-delete-subject-note") !== -1) {
                openDeleteNoteDialog(oButton.closest(".subject-note-item"));
            } else if (oButton.className.indexOf("js-delete-subject") !== -1) {
                openDeleteSubjectDialog(oButton);
            } else {
                loadSubject(oButton);
            }
        }
    }, true);
});

document.addEventListener("DOMContentLoaded", function () {
    var aContactButtons = document.querySelectorAll(".js-add-subject-contact, .js-edit-subject-contact");
    var blCanEditContacts = aContactButtons.length > 0 && window.fetch && window.FormData;

    function showContactCopyResult(oButton, blSuccess) {
        var oBox = oButton.querySelector ? oButton.querySelector(".copy-action-box") : null;
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
        }, 1000);
    }

    function copyContactValue(oButton) {
        var oItem = oButton.closest ? oButton.closest(".contact-item") : null;
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

    document.addEventListener("click", function (oEvent) {
        var oButton = oEvent.target && oEvent.target.closest ? oEvent.target.closest(".contact-copy") : null;
        var oLink;
        if (oButton) {
            oEvent.preventDefault();
            oEvent.stopPropagation();
            copyContactValue(oButton);
            return;
        }
        oLink = oEvent.target && oEvent.target.closest ? oEvent.target.closest(".contact-link") : null;
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
        var oList = document.getElementById("contact-type-list");
        return oList ? oList.getElementsByTagName("option") : [];
    }

    function getNewContactDefaultTypeId() {
        var oList = document.getElementById("contact-type-list");
        return oList ? (oList.getAttribute("data-default-contact-type-id") || "") : "";
    }

    function setNewContactDefaultTypeId(sTypeId) {
        var oList = document.getElementById("contact-type-list");
        if (oList) {
            oList.setAttribute("data-default-contact-type-id", sTypeId || "");
        }
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
        oForm.appendChild(oLabel);
        oForm.appendChild(oSelect);
        return oSelect;
    }


    function openContactDialog(oItem, oSubjectRowParam, blNewContact) {
        var oDialog = prepareAdminReusableDialog();
        var oForm = document.createElement("form");
        var oBox = oForm;
        var oHeader = document.createElement("div");
        var oTitle = document.createElement("strong");
        var oClose = document.createElement("button");
        var oError = document.createElement("p");
        var oActions = document.createElement("div");
        var oSave = document.createElement("button");
        var oCancel = document.createElement("button");
        var oSubjectRow = blNewContact ? oSubjectRowParam : getAdminSubjectRow(oItem);
        var sSubjectId = oSubjectRow ? (oSubjectRow.getAttribute("data-subject-id") || "") : "";
        var sNewContactTypeId = blNewContact ? getNewContactDefaultTypeId() : "";
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
        if (!oDialog) {
            return;
        }
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
        enableAdminDialogDrag(oDialog, oBox, oHeader);
        var oSharedNote = document.createElement("p");
        oSharedNote.textContent = "Shared contact values used by other subjects are preserved.";
        oForm.appendChild(oSharedNote);
        var oType = appendContactTypeSelect(oForm, blNewContact ? sNewContactTypeId : (oItem.getAttribute("data-contact-type-id") || ""), blNewContact && !sNewContactTypeId ? "cell" : (blNewContact ? "" : (oItem.getAttribute("data-contact-type") || "")));
        var oValue = appendTextField(oForm, "Value", "contact_value", blNewContact ? "" : (oItem.getAttribute("data-contact-value") || ""));
        var oNote = appendTextField(oForm, "Note", "note", blNewContact ? "" : (oItem.getAttribute("data-contact-note") || ""));
        var oPrimary = appendCheckbox(oForm, "Primary", "is_primary", blNewContact ? false : oItem.getAttribute("data-contact-primary") == "1");
        var oActive = appendCheckbox(oForm, "Active", "is_active", blNewContact ? true : oItem.getAttribute("data-contact-active") == "1");
        oForm.appendChild(oError);
        oActions.appendChild(oSave);
        oActions.appendChild(oCancel);
        oForm.appendChild(oActions);
        oDialog.appendChild(oForm);
        if (!openAdminDialogElement(oDialog, closeDialog)) {
            return;
        }
        beginAdminSubjectRowEdit(oSubjectRow);
        focusAdminElement(findFirstAdminUserInput(oForm) || oValue, true);
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
                if (aData.subject_deleted && window.removeSubjectRow) {
                    window.removeSubjectRow(aData.subject_id);
                    closeDialog(true);
                    return;
                }
                if (aData.subject_cell_html && oItem) {
                    replaceAdminTableCellHtml(oItem.closest(".contact-subject-cell"), aData.subject_cell_html);
                    refreshAdminTableFilter();
                }
                if (aData.row_html && window.replaceSubjectRow) {
                    window.replaceSubjectRow(aData.subject_id, aData.row_html);
                }
                if (blNewContact) {
                    setNewContactDefaultTypeId(oType.value);
                }
                closeDialog(true);
            }).catch(function (oException) {
                logAdminException(oException);
                setAdminDialogError(oError, "Contact could not be saved.");
                oSave.disabled = false;
            });
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
                openContactDialog(oButton.closest(".contact-item"), null, false);
            }
        }
    }, true);
});

document.addEventListener("DOMContentLoaded", function () {
    var oTable = document.getElementById("phone-book-table");
    if (!oTable) {
        return;
    }
    var oDeleteDialog = document.getElementById("phone-book-remove-dialog");
    var oDeleteForm = oDeleteDialog ? oDeleteDialog.querySelector("form") : null;
    var oDeleteError = oDeleteDialog ? oDeleteDialog.querySelector(".contact-edit-error") : null;
    var oCurrentRow = null;
    var closeOnEscape = function (oEvent) {
        if (oEvent.key == "Escape") {
            closePhoneBookRemoveDialog(false);
        }
    };

    function getField(oForm, sName) {
        return oForm ? oForm.querySelector("[name=\"" + sName + "\"]") : null;
    }

    function getPhoneBookRow(oElement) {
        return oElement && oElement.closest ? oElement.closest("tr[data-phone-book-id]") : null;
    }

    function getPhoneBookRemoveText(oRow) {
        var oCell = oRow ? oRow.querySelector(".phone-book-contact") : null;
        return getAdminContactItemText(oCell);
    }

    function closePhoneBookRemoveDialog(blSaved) {
        var oSave = oDeleteForm ? oDeleteForm.querySelector("button[type=\"submit\"]") : null;
        if (oCurrentRow && oCurrentRow.parentNode) {
            finishAdminSubjectRowEdit(oCurrentRow, blSaved === true);
        }
        if (oSave) {
            oSave.disabled = false;
        }
        document.removeEventListener("keydown", closeOnEscape);
        closeAdminDialogElement(oDeleteDialog);
        setAdminDialogError(oDeleteError, "");
        oCurrentRow = null;
    }

    function openPhoneBookRemoveDialog(oButton) {
        var oPhoneBookId = getField(oDeleteForm, "phone_book_id");
        closeAdminOpenDialog();
        oCurrentRow = getPhoneBookRow(oButton);
        setAdminDialogError(oDeleteError, "");
        if (oPhoneBookId) {
            oPhoneBookId.value = oButton ? (oButton.getAttribute("data-phone-book-id") || "") : "";
        }
        setAdminConfirmDetail(oDeleteDialog, ".js-phone-book-remove-value", getPhoneBookRemoveText(oCurrentRow));
        beginAdminSubjectRowEdit(oCurrentRow);
        if (!openAdminDialogElement(oDeleteDialog, closePhoneBookRemoveDialog)) {
            return;
        }
        document.addEventListener("keydown", closeOnEscape);
        focusAdminElement(oDeleteForm ? oDeleteForm.querySelector(".js-phone-book-remove-cancel") : null);
    }

    function submitPhoneBookRemove() {
        var oData = new FormData();
        var oPhoneBookId = getField(oDeleteForm, "phone_book_id");
        var oSave = oDeleteForm ? oDeleteForm.querySelector("button[type=\"submit\"]") : null;
        var oRemovedRow = oCurrentRow;
        setAdminDialogError(oDeleteError, "");
        if (oSave) {
            oSave.disabled = true;
        }
        oData.append("action", "remove_phone_book_contact");
        oData.append("phone_book_id", oPhoneBookId ? oPhoneBookId.value : "");
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
                setAdminDialogError(oDeleteError, aData && aData.message ? aData.message : "Contact could not be removed from Phone Book.");
                if (oSave) {
                    oSave.disabled = false;
                }
                return;
            }
            closePhoneBookRemoveDialog(true);
            if (oRemovedRow && oRemovedRow.parentNode) {
                oRemovedRow.parentNode.removeChild(oRemovedRow);
                if (!oTable.querySelector("tbody tr[data-phone-book-id]")) {
                    window.location.reload();
                    return;
                }
                refreshAdminTableFilter();
            }
        }).catch(function (oException) {
            logAdminException(oException);
            setAdminDialogError(oDeleteError, "Contact could not be removed from Phone Book.");
            if (oSave) {
                oSave.disabled = false;
            }
        });
    }

    document.addEventListener("click", function (oEvent) {
        var oButton = oEvent.target && oEvent.target.closest ? oEvent.target.closest(".js-remove-phone-book-contact, .js-phone-book-remove-close, .js-phone-book-remove-cancel") : null;
        if (!oButton) {
            return;
        }
        oEvent.preventDefault();
        oEvent.stopPropagation();
        if (oButton.className.indexOf("js-remove-phone-book-contact") !== -1) {
            openPhoneBookRemoveDialog(oButton);
        } else {
            closePhoneBookRemoveDialog(false);
        }
    }, true);
    if (oDeleteForm) {
        oDeleteForm.addEventListener("submit", function (oEvent) {
            oEvent.preventDefault();
            submitPhoneBookRemove();
        });
    }
});

document.addEventListener("DOMContentLoaded", function () {
    if (!document.getElementById("contacts-table")) {
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
        return oButton && oButton.closest ? oButton.closest(".contact-cell") : null;
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

    function addPhoneBookContact(oButton) {
        var oData;
        var sSubjectContactId = oButton ? (oButton.getAttribute("data-subject-contact-id") || "") : "";
        if (!window.fetch || !window.FormData) {
            showAdminMessageDialog("Phone Book action is not available.", "Phone Book");
            return;
        }
        if (!sSubjectContactId || oButton.getAttribute("data-phone-book-pending") == "1") {
            return;
        }
        oButton.setAttribute("data-phone-book-pending", "1");
        oData = new FormData();
        oData.append("action", "add_phone_book_contact");
        oData.append("subject_contact_id", sSubjectContactId);
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
                oButton.removeAttribute("data-phone-book-pending");
                showAdminMessageDialog(aData && aData.message ? aData.message : "Contact could not be added to Phone Book.", "Phone Book");
                return;
            }
            if (oButton.parentNode) {
                oButton.parentNode.removeChild(oButton);
            }
        }).catch(function (oException) {
            logAdminException(oException);
            oButton.removeAttribute("data-phone-book-pending");
            showAdminMessageDialog("Contact could not be added to Phone Book.", "Phone Book");
        });
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
        if (!openAdminDialogElement(oDialog, closeSharedContactDialog)) {
            return;
        }
        document.addEventListener("keydown", closeOnEscape);
        focusAdminElement(findFirstAdminUserInput(oForm) || oFocus, true);
    }

    function hideDialog(oDialog) {
        closeAdminDialogElement(oDialog);
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
        closeAdminOpenDialog();
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
        closeAdminOpenDialog();
        oCurrentContactCell = oCell;
        setDialogError(oDeleteError, "");
        if (oContactId) {
            oContactId.value = oCell ? (oCell.getAttribute("data-contact-id") || "") : "";
        }
        setAdminConfirmDetail(oDeleteDialog, ".js-shared-contact-delete-value", getAdminContactItemText(oCell));
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
        var oPhoneBookButton;
        oPhoneBookButton = oEvent.target && oEvent.target.closest ? oEvent.target.closest(".js-add-phone-book-contact") : null;
        if (oPhoneBookButton) {
            oEvent.preventDefault();
            oEvent.stopPropagation();
            addPhoneBookContact(oPhoneBookButton);
            return;
        }
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
    if (!document.getElementById("addresses-table")) {
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
        return oButton && oButton.closest ? oButton.closest(".address-cell") : null;
    }

    function getSubjectCell(oButton) {
        return oButton && oButton.closest ? oButton.closest(".address-subject-cell") : null;
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

    function getAddressCountryValue(oCell) {
        var sCode = oCell ? (oCell.getAttribute("data-country") || "") : "";
        var sName = oCell ? (oCell.getAttribute("data-country-name") || "") : "";
        if (sCode !== "" && sName !== "") {
            return sCode + " \u2014 " + sName;
        }
        return sName || sCode;
    }

    function getAddressValue(oCell, sName) {
        var sAttribute = sName.replace(/_/g, "-");
        if (sName == "country") {
            return getAddressCountryValue(oCell);
        }
        return oCell.getAttribute("data-" + sAttribute) || "";
    }

    function getAddressCellText(oCell) {
        var oValue = oCell ? oCell.querySelector(".subject-item-value") : null;
        return getAdminElementText(oValue);
    }

    function getSubjectAddressDeleteText(oCell) {
        var oAddressCell = getAddressCellForSubject(oCell);
        var sSubject = getAddressCellText(oCell);
        var sAddress = getAddressCellText(oAddressCell);
        if (sSubject != "" && sAddress != "") {
            return sSubject + "\n" + sAddress;
        }
        return sSubject || sAddress;
    }

    function getAddressCellForSubject(oCell) {
        var oRow = oCell ? oCell.parentNode : null;
        var oAddressCell = null;
        while (oRow && !oAddressCell) {
            oAddressCell = oRow.querySelector ? oRow.querySelector(".address-cell") : null;
            oRow = oRow.previousElementSibling;
        }
        return oAddressCell;
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
                addAdminClass(oRow.parentNode.rows[iRowIndex + iI], "admin-row-modal");
            } else {
                removeAdminClass(oRow.parentNode.rows[iRowIndex + iI], "admin-row-modal");
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
            addAdminClass(oRow, "admin-row-modal");
        } else {
            removeAdminClass(oRow, "admin-row-modal");
        }
    }

    function openSharedAddressEdit(oCell) {
        var oField;
        var iI;
        if (!oEditDialog || !oEditForm || !oCell) {
            return;
        }
        closeAdminOpenDialog();
        oCurrentAddressCell = oCell;
        getField(oEditForm, "address_match").value = oCell.getAttribute("data-address-match") || "";
        for (iI = 0; iI < aAddressFields.length; iI += 1) {
            oField = getField(oEditForm, aAddressFields[iI]);
            if (oField) {
                oField.value = getAddressValue(oCell, aAddressFields[iI]);
            }
        }
        setDialogError(oEditError, "");
        if (!openAdminDialogElement(oEditDialog, closeSharedAddressDialog)) {
            return;
        }
        setAddressRowsModal(oCell, true);

        document.addEventListener("keydown", closeOnEscape);
        focusAdminElement(findFirstAdminUserInput(oEditForm) || getField(oEditForm, "street_name"), true);
    }

    function openSharedAddressDelete(oCell) {
        if (!oDeleteDialog || !oDeleteForm || !oCell) {
            return;
        }
        closeAdminOpenDialog();
        oCurrentAddressCell = oCell;
        getField(oDeleteForm, "address_match").value = oCell.getAttribute("data-address-match") || "";
        setAdminConfirmDetail(oDeleteDialog, ".js-shared-address-delete-value", getAddressCellText(oCell));
        setDialogError(oDeleteError, "");
        if (!openAdminDialogElement(oDeleteDialog, closeSharedAddressDialog)) {
            return;
        }
        setAddressRowsModal(oCell, true);

        document.addEventListener("keydown", closeOnEscape);
        focusAdminElement(oDeleteForm.querySelector(".js-shared-address-delete-cancel"));
    }

    function closeSharedAddressDialog(blSaved) {
        closeAdminDialogElement(oEditDialog);
        closeAdminDialogElement(oDeleteDialog);
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
        closeAdminOpenDialog();
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
        if (!openAdminDialogElement(oSubjectEditDialog, closeSubjectAddressDialog)) {
            return;
        }
        setSubjectRowModal(oCell, true);

        document.addEventListener("keydown", closeOnEscape);
        focusAdminElement(findFirstAdminUserInput(oSubjectEditForm) || getField(oSubjectEditForm, "street_name"), true);
    }

    function openSubjectAddressDelete(oCell) {
        if (!oSubjectDeleteDialog || !oSubjectDeleteForm || !oCell) {
            return;
        }
        closeAdminOpenDialog();
        oCurrentSubjectCell = oCell;
        getField(oSubjectDeleteForm, "address_id").value = oCell.getAttribute("data-address-id") || "";
        setAdminConfirmDetail(oSubjectDeleteDialog, ".js-subject-address-delete-value", getSubjectAddressDeleteText(oCell));
        setDialogError(oSubjectDeleteError, "");
        if (!openAdminDialogElement(oSubjectDeleteDialog, closeSubjectAddressDialog)) {
            return;
        }
        setSubjectRowModal(oCell, true);

        document.addEventListener("keydown", closeOnEscape);
        focusAdminElement(oSubjectDeleteForm.querySelector(".js-subject-address-delete-cancel"));
    }

    function closeSubjectAddressDialog(blSaved) {
        var oRow;
        closeAdminDialogElement(oSubjectEditDialog);
        closeAdminDialogElement(oSubjectDeleteDialog);
        document.removeEventListener("keydown", closeOnEscape);
        setSubjectRowModal(oCurrentSubjectCell, false);
        if (blSaved && oCurrentSubjectCell && oCurrentSubjectCell.parentNode) {
            oRow = oCurrentSubjectCell.parentNode;
            addAdminClass(oRow, "admin-row-saved");
            window.setTimeout(function () {
                removeAdminClass(oRow, "admin-row-saved");
            }, 1500);
        }
        oCurrentSubjectCell = null;
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
        });
    }

    function submitSubjectAddressForm(oForm, sAction, oError) {
        var oData = new FormData();
        var oAddressCell;
        var oAddressRow;
        var oNewCell;
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
            if (aData.address_cell_html && oCurrentSubjectCell) {
                oAddressRow = oCurrentSubjectCell.parentNode;
                while (oAddressRow && !oAddressCell) {
                    oAddressCell = oAddressRow.querySelector ? oAddressRow.querySelector(".address-cell") : null;
                    oAddressRow = oAddressRow.previousElementSibling;
                }
                replaceAdminTableCellHtml(oAddressCell, aData.address_cell_html);
            }
            if (aData.subject_cell_html) {
                oNewCell = replaceAdminTableCellHtml(oCurrentSubjectCell, aData.subject_cell_html);
                if (oNewCell) {
                    oCurrentSubjectCell = oNewCell;
                }
            }
            refreshAdminTableFilter();
            closeSubjectAddressDialog(true);
        }).catch(function (oException) {
            logAdminException(oException);
            setDialogError(oError, "Address operation failed.");
        });
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
        });
    }
    if (oDeleteForm) {
        oDeleteForm.addEventListener("submit", function (oEvent) {
            oEvent.preventDefault();
            submitSharedAddressForm(oDeleteForm, "delete_shared_address", oDeleteError);
        });
    }
    if (oSubjectEditForm) {
        oSubjectEditForm.addEventListener("submit", function (oEvent) {
            oEvent.preventDefault();
            submitSubjectAddressForm(oSubjectEditForm, "update_subject_address", oSubjectEditError);
        });
    }
    if (oSubjectDeleteForm) {
        oSubjectDeleteForm.addEventListener("submit", function (oEvent) {
            oEvent.preventDefault();
            submitSubjectAddressForm(oSubjectDeleteForm, "delete_subject_address", oSubjectDeleteError);
        });
    }
});

document.addEventListener("DOMContentLoaded", function () {
    var aSubmitOnChange = document.querySelectorAll(".js-submit-on-change");
    for (var iI = 0; iI < aSubmitOnChange.length; iI += 1) {
        aSubmitOnChange[iI].addEventListener("change", function () {
            if (this.form) {
                this.form.submit();
            }
        });
    }
});

var oMailPmdDropdown = null;
var iMailPmdDropdownToken = 0;

function submitMailAdminRequest(oData, fSuccess, fError) {
    appendAdminCsrfToken(oData);
    window.fetch(window.location.href, {
        method: "POST",
        headers: getAdminAjaxHeaders(),
        body: oData,
        credentials: "same-origin"
    }).then(function(oResponse) {
        return oResponse.text().then(function(sText) {
            var aData = null;
            try {
                aData = JSON.parse(sText);
            } catch (oException) {
                aData = {
                    success: false,
                    message: "Unexpected server response."
                };
            }
            if (!oResponse.ok || !aData.success) {
                throw aData;
            }
            return aData;
        });
    }).then(function(aData) {
        if (typeof fSuccess == "function") {
            fSuccess(aData);
        }
    }).catch(function(oError) {
        var sMessage = oError && oError.message ? oError.message : "Request failed.";
        if (typeof fError == "function") {
            fError(sMessage);
        } else {
            showAdminMessageDialog(sMessage);
        }
    });
}

function isMailFormAddressSeparator(sChar) {
    return sChar == " " || sChar == "\t" || sChar == "," || sChar == ";";
}

function mailFormReadEmailToken(sValue, iOffset) {
    var iLength = sValue.length;
    var iNextOffset = iOffset;
    var blEscaped = false;
    var sChar;
    if (iOffset >= iLength) {
        return {token: "", nextOffset: iOffset};
    }
    if (sValue.charAt(iOffset) == "\"") {
        iNextOffset++;
        while (iNextOffset < iLength) {
            sChar = sValue.charAt(iNextOffset);
            if (blEscaped) {
                blEscaped = false;
            } else if (sChar == "\\") {
                blEscaped = true;
            } else if (sChar == "\"") {
                iNextOffset++;
                break;
            }
            iNextOffset++;
        }
        if (iNextOffset >= iLength || sValue.charAt(iNextOffset) != "@") {
            return {token: "", nextOffset: iOffset};
        }
        iNextOffset++;
        while (iNextOffset < iLength && !isMailFormAddressSeparator(sValue.charAt(iNextOffset))) {
            iNextOffset++;
        }
        return {token: sValue.substring(iOffset, iNextOffset), nextOffset: iNextOffset};
    }
    while (iNextOffset < iLength && !isMailFormAddressSeparator(sValue.charAt(iNextOffset))) {
        iNextOffset++;
    }
    return {token: sValue.substring(iOffset, iNextOffset), nextOffset: iNextOffset};
}

function mailFormFindUnquotedChar(sValue, sFind, iOffset) {
    var iLength = sValue.length;
    var blQuoted = false;
    var blEscaped = false;
    var sChar;
    while (iOffset < iLength) {
        sChar = sValue.charAt(iOffset);
        if (blEscaped) {
            blEscaped = false;
        } else if (sChar == "\\") {
            blEscaped = true;
        } else if (sChar == "\"") {
            blQuoted = !blQuoted;
        } else if (!blQuoted && sChar == sFind) {
            return iOffset;
        }
        iOffset++;
    }
    return -1;
}

function mailFormFindUnquotedCharBeforeListSeparator(sValue, sFind, iOffset) {
    var iLength = sValue.length;
    var blQuoted = false;
    var blEscaped = false;
    var sChar;
    while (iOffset < iLength) {
        sChar = sValue.charAt(iOffset);
        if (blEscaped) {
            blEscaped = false;
        } else if (sChar == "\\") {
            blEscaped = true;
        } else if (sChar == "\"") {
            blQuoted = !blQuoted;
        } else if (!blQuoted && (sChar == "," || sChar == ";")) {
            return -1;
        } else if (!blQuoted && sChar == sFind) {
            return iOffset;
        }
        iOffset++;
    }
    return -1;
}

function mailFormCleanDisplayName(sValue) {
    var sName = String(sValue).trim();
    var iLength = sName.length;
    if (sName == "") {
        return "";
    }
    if (/[\x00-\x1F\x7F]/.test(String(sName)) || sName.indexOf("<") !== -1 || sName.indexOf(">") !== -1) {
        return false;
    }
    if (iLength >= 2 && sName.charAt(0) == "\"" && sName.charAt(iLength - 1) == "\"") {
        sName = sName.substring(1, iLength - 1).replace(/\\\\/g, "\\").replace(/\\"/g, "\"");
    }
    return sName.replace(/[ \t]+/g, " ").trim();
}

function isMailFormEmailAddress(sValue) {
    var sEmail = String(sValue).trim();
    if (sEmail == "" || sEmail.length > 254) {
        return false;
    }
    if (sEmail.indexOf("@") > 64) {
        return false;
    }
    return /^(?:[A-Za-z0-9!#$%&'*+\/=?^_`{|}~-]+(?:\.[A-Za-z0-9!#$%&'*+\/=?^_`{|}~-]+)*|"(?:[^"\\\r\n]|\\[\x20-\x7E])*")@(?:[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?\.)+[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?$/.test(sEmail);
}

function mailFormParseMailbox(sValue, iOffset) {
    var iLength = sValue.length;
    var oToken;
    var sToken = "";
    var sEmail = "";
    var sName = "";
    var iOpen = -1;
    var iClose = -1;
    var iProbe = iOffset;
    if (iOffset >= iLength) {
        return false;
    }
    if (sValue.charAt(iOffset) == "<") {
        iClose = mailFormFindUnquotedChar(sValue, ">", iOffset + 1);
        if (iClose < 0) {
            return false;
        }
        sToken = sValue.substring(iOffset + 1, iClose).trim();
        iProbe = iClose + 1;
        while (iProbe < iLength && (sValue.charAt(iProbe) == " " || sValue.charAt(iProbe) == "\t")) {
            iProbe++;
        }
        if (iProbe >= iLength || sValue.charAt(iProbe) == "," || sValue.charAt(iProbe) == ";") {
            if (!isMailFormEmailAddress(sToken)) {
                return false;
            }
            return {name: "", email: sToken, nextOffset: iClose + 1};
        }
        sName = mailFormCleanDisplayName(sToken);
        oToken = mailFormReadEmailToken(sValue, iProbe);
        sEmail = oToken.token.trim();
        if (sName === false || !isMailFormEmailAddress(sEmail)) {
            return false;
        }
        return {name: sName, email: sEmail, nextOffset: oToken.nextOffset};
    }
    oToken = mailFormReadEmailToken(sValue, iOffset);
    sEmail = oToken.token.trim();
    if (isMailFormEmailAddress(sEmail)) {
        return {name: "", email: sEmail, nextOffset: oToken.nextOffset};
    }
    iOpen = mailFormFindUnquotedCharBeforeListSeparator(sValue, "<", iOffset);
    if (iOpen < 0) {
        return false;
    }
    iClose = mailFormFindUnquotedChar(sValue, ">", iOpen + 1);
    if (iClose < 0) {
        return false;
    }
    sName = mailFormCleanDisplayName(sValue.substring(iOffset, iOpen));
    sEmail = sValue.substring(iOpen + 1, iClose).trim();
    if (sName === false || !isMailFormEmailAddress(sEmail)) {
        return false;
    }
    return {name: sName, email: sEmail, nextOffset: iClose + 1};
}

function mailFormNormalizeEmailList(sValue) {
    var aMailboxes = [];
    var iLength;
    var iOffset = 0;
    var iCount = 0;
    var oMailbox;
    sValue = String(sValue || "");
    iLength = sValue.length;
    if (/[\x00-\x1F\x7F]/.test(String(sValue))) {
        return false;
    }
    while (iOffset < iLength) {
        while (iOffset < iLength && isMailFormAddressSeparator(sValue.charAt(iOffset))) {
            iOffset++;
        }
        if (iOffset >= iLength) {
            break;
        }
        oMailbox = mailFormParseMailbox(sValue, iOffset);
        if (oMailbox === false || oMailbox.nextOffset <= iOffset) {
            return false;
        }
        aMailboxes.push(oMailbox);
        iCount++;
        iOffset = oMailbox.nextOffset;
        if (iOffset < iLength && !isMailFormAddressSeparator(sValue.charAt(iOffset))) {
            return false;
        }
    }
    return {count: iCount, mailboxes: aMailboxes};
}

function getMailFormRecipientSuggestSearchTerm(sValue) {
    return String(sValue || "").replace(/[<>"]/g, " ").replace(/^[\s,;]+|[\s,;]+$/g, "").replace(/[ \t]+/g, " ");
}

function mailFormValueStartsWithMailbox(sValue) {
    var iLength = String(sValue || "").length;
    var iOffset = 0;
    var oMailbox;
    sValue = String(sValue || "");
    while (iOffset < iLength && isMailFormAddressSeparator(sValue.charAt(iOffset))) {
        iOffset++;
    }
    if (iOffset >= iLength) {
        return false;
    }
    oMailbox = mailFormParseMailbox(sValue, iOffset);
    return oMailbox !== false && oMailbox.nextOffset > iOffset;
}

function getMailFormRecipientSuggestRange(oInput, blSingleAddress) {
    var sValue = String(oInput ? oInput.value || "" : "");
    var iLength = sValue.length;
    var iCaret = oInput && typeof oInput.selectionStart == "number" ? oInput.selectionStart : iLength;
    var iOffset = 0;
    var iStart = 0;
    var oMailbox;
    if (iCaret < 0 || iCaret > iLength) {
        iCaret = iLength;
    }
    if (blSingleAddress) {
        return {
            start: 0,
            end: iLength,
            term: getMailFormRecipientSuggestSearchTerm(sValue)
        };
    }
    while (iOffset < iCaret) {
        while (iOffset < iCaret && isMailFormAddressSeparator(sValue.charAt(iOffset))) {
            iOffset++;
        }
        iStart = iOffset;
        if (iOffset >= iCaret) {
            break;
        }
        oMailbox = mailFormParseMailbox(sValue, iOffset);
        if (oMailbox === false || oMailbox.nextOffset <= iOffset || oMailbox.nextOffset >= iCaret) {
            break;
        }
        iOffset = oMailbox.nextOffset;
        if (iOffset < iCaret && !isMailFormAddressSeparator(sValue.charAt(iOffset))) {
            break;
        }
    }
    return {
        start: iStart,
        end: iCaret,
        term: getMailFormRecipientSuggestSearchTerm(sValue.substring(iStart, iCaret))
    };
}

function positionMailRecipientSuggestBox(oInput, oBox) {
    var oRect;
    if (!oInput || !oBox) {
        return;
    }
    oRect = oInput.getBoundingClientRect();
    oBox.style.left = Math.max(0, Math.floor(oRect.left)) + "px";
    oBox.style.top = Math.floor(oRect.bottom + 1) + "px";
    oBox.style.width = Math.max(240, Math.floor(oRect.width)) + "px";
}

function applyMailRecipientSuggestion(oInput, aRange, sSuggestion, blSingleAddress) {
    var sValue = String(oInput ? oInput.value || "" : "");
    var sBefore = sValue.substring(0, aRange.start);
    var sAfter = sValue.substring(aRange.end);
    var sInsert = String(sSuggestion || "");
    var iCaret;
    if (!oInput || sInsert == "") {
        return;
    }
    if (blSingleAddress) {
        sBefore = "";
        sAfter = "";
    } else {
        if (sBefore != "" && !isMailFormAddressSeparator(sBefore.charAt(sBefore.length - 1))) {
            sBefore += " ";
        }
        if (sAfter == "" || !isMailFormAddressSeparator(sAfter.charAt(0))) {
            sInsert += " ";
        }
    }
    oInput.value = sBefore + sInsert + sAfter;
    iCaret = (sBefore + sInsert).length;
    if (typeof oInput.setSelectionRange == "function") {
        try {
            oInput.setSelectionRange(iCaret, iCaret);
        } catch (oException) {
            logAdminException(oException);
        }
    }
    focusAdminElement(oInput, false);
    dispatchAdminInputEvent(oInput);
}

function bindMailRecipientSuggestInput(oInput) {
    var oBox;
    var iTimer = 0;
    var iRequestIndex = 0;
    var aSuggestions = [];
    var iActiveIndex = -1;
    var aCurrentRange = null;
    var blSingleAddress = oInput && oInput.getAttribute("data-mail-recipient-suggest-single") == "1";
    var blAllowedSenderDomains = oInput && oInput.getAttribute("data-mail-recipient-suggest-allowed-domains") == "1";
    if (!window.fetch || !window.FormData || !oInput || oInput.getAttribute("data-mail-recipient-suggest-bound") == "1") {
        return;
    }
    oInput.setAttribute("data-mail-recipient-suggest-bound", "1");
    oBox = document.createElement("div");
    oBox.className = "mail-recipient-suggest-box";
    oBox.setAttribute("role", "listbox");
    oBox.hidden = true;
    document.body.appendChild(oBox);

    function hideList() {
        oBox.hidden = true;
        oBox.innerHTML = "";
        aSuggestions = [];
        iActiveIndex = -1;
    }

    function setActiveIndex(iIndex) {
        var aButtons = oBox.querySelectorAll(".mail-recipient-suggest-option");
        var i;
        if (!aButtons.length) {
            iActiveIndex = -1;
            return;
        }
        if (iIndex < 0) {
            iIndex = aButtons.length - 1;
        }
        if (iIndex >= aButtons.length) {
            iIndex = 0;
        }
        iActiveIndex = iIndex;
        for (i = 0; i < aButtons.length; i++) {
            if (i == iActiveIndex) {
                addAdminClass(aButtons[i], "mail-recipient-suggest-active");
                aButtons[i].setAttribute("aria-selected", "true");
            } else {
                removeAdminClass(aButtons[i], "mail-recipient-suggest-active");
                aButtons[i].setAttribute("aria-selected", "false");
            }
        }
    }

    function insertSuggestion(iIndex) {
        if (iIndex < 0 || iIndex >= aSuggestions.length || !aCurrentRange) {
            return false;
        }
        applyMailRecipientSuggestion(oInput, aCurrentRange, aSuggestions[iIndex].value || "", blSingleAddress);
        hideList();
        return true;
    }

    function renderSuggestions(aItems) {
        var oButton;
        var i;
        hideList();
        if (!aItems || !aItems.length || document.activeElement != oInput) {
            return;
        }
        aSuggestions = aItems;
        for (i = 0; i < aSuggestions.length; i++) {
            oButton = document.createElement("button");
            oButton.type = "button";
            oButton.className = "mail-recipient-suggest-option";
            oButton.setAttribute("role", "option");
            oButton.setAttribute("data-mail-recipient-suggest-index", String(i));
            oButton.textContent = aSuggestions[i].value || "";
            oButton.addEventListener("mousedown", function(oEvent) {
                oEvent.preventDefault();
            });
            oButton.addEventListener("click", function() {
                insertSuggestion(parseInt(this.getAttribute("data-mail-recipient-suggest-index") || "-1", 10));
            });
            oBox.appendChild(oButton);
        }
        positionMailRecipientSuggestBox(oInput, oBox);
        oBox.hidden = false;
        setActiveIndex(0);
    }

    function requestSuggestions(aRange) {
        var oData = new FormData();
        var iCurrentRequest = iRequestIndex;
        oData.append("action", "suggest_mail_recipients");
        oData.append("term", aRange.term);
        if (blAllowedSenderDomains) {
            oData.append("allowed_sender_domains", "1");
        }
        appendAdminCsrfToken(oData);
        window.fetch(window.location.href, {
            method: "POST",
            headers: getAdminAjaxHeaders(),
            body: oData,
            credentials: "same-origin"
        }).then(function(oResponse) {
            return oResponse.text().then(function(sText) {
                var aData = null;
                try {
                    aData = JSON.parse(sText);
                } catch (oException) {
                    aData = null;
                }
                if (!oResponse.ok || !aData || !aData.success) {
                    throw aData || {};
                }
                return aData;
            });
        }).then(function(aData) {
            if (iCurrentRequest != iRequestIndex) {
                return;
            }
            aCurrentRange = aRange;
            renderSuggestions(aData.recipients || []);
        }).catch(function(oException) {
            logAdminException(oException);
            hideList();
        });
    }

    function scheduleSuggestions() {
        var aRange = getMailFormRecipientSuggestRange(oInput, blSingleAddress);
        iRequestIndex += 1;
        if (iTimer) {
            window.clearTimeout(iTimer);
        }
        aCurrentRange = aRange;
        if (blSingleAddress && mailFormValueStartsWithMailbox(oInput.value)) {
            hideList();
            return;
        }
        if (aRange.term.length < 3) {
            hideList();
            return;
        }
        iTimer = window.setTimeout(function() {
            requestSuggestions(aRange);
        }, 200);
    }

    oInput.addEventListener("input", scheduleSuggestions);
    oInput.addEventListener("keydown", function(oEvent) {
        if (oBox.hidden) {
            return;
        }
        if (oEvent.key == "ArrowDown") {
            oEvent.preventDefault();
            setActiveIndex(iActiveIndex + 1);
        } else if (oEvent.key == "ArrowUp") {
            oEvent.preventDefault();
            setActiveIndex(iActiveIndex - 1);
        } else if (oEvent.key == "Enter" || oEvent.key == "Tab") {
            if (insertSuggestion(iActiveIndex)) {
                oEvent.preventDefault();
            }
        } else if (oEvent.key == "Escape") {
            hideList();
        }
    });
    oInput.addEventListener("click", function() {
        scheduleSuggestions();
        positionMailRecipientSuggestBox(oInput, oBox);
    });
    oInput.addEventListener("blur", function() {
        window.setTimeout(hideList, 150);
    });
    window.addEventListener("resize", function() {
        if (!oBox.hidden) {
            positionMailRecipientSuggestBox(oInput, oBox);
        }
    });
    window.addEventListener("scroll", hideList);
    document.addEventListener("click", function(oEvent) {
        if (oEvent.target == oInput || (oBox.contains && oBox.contains(oEvent.target))) {
            return;
        }
        hideList();
    });
}

function mailFormNormalizeSingleEmail(sValue) {
    var aList = mailFormNormalizeEmailList(sValue);
    if (aList === false || aList.count > 1) {
        return false;
    }
    return aList;
}

function getMailFormFieldValue(oForm, sName) {
    return oForm && oForm.elements[sName] ? String(oForm.elements[sName].value || "").trim() : "";
}

function getMailFormEmailDomain(sEmail) {
    var iAt = String(sEmail).lastIndexOf("@");
    if (iAt < 0) {
        return "";
    }
    return String(sEmail).substring(iAt + 1).toLowerCase();
}

function getMailFormAllowedSenderDomains(oForm) {
    var aDomains = [];
    var aParsed;
    var i;
    try {
        aParsed = JSON.parse(oForm.getAttribute("data-mail-allowed-sender-domains") || "[]");
    } catch (oException) {
        aParsed = [];
    }
    for (i = 0; i < aParsed.length; i++) {
        if (String(aParsed[i]).trim() != "") {
            aDomains.push(String(aParsed[i]).trim().toLowerCase());
        }
    }
    return aDomains;
}

function mailFormEmailListUsesAllowedSenderDomains(aEmailList, aAllowedDomains) {
    var sDomain;
    var i;
    if (aEmailList === false) {
        return false;
    }
    for (i = 0; i < aEmailList.mailboxes.length; i++) {
        sDomain = getMailFormEmailDomain(aEmailList.mailboxes[i].email);
        if (sDomain == "" || aAllowedDomains.indexOf(sDomain) < 0) {
            return false;
        }
    }
    return true;
}

function isMailFormBodyEmpty(sValue, sBodyFormat) {
    var oNode = document.createElement("div");
    var sText = String(sValue || "");
    sText = sText.replace(/<\s*(script|style)[^>]*>[\s\S]*?<\s*\/\s*\1\s*>/ig, "");
    if (sBodyFormat != "plain" && /<\s*img\b/i.test(sText)) {
        return false;
    }
    sText = sText.replace(/<\s*br\s*\/?\s*>/ig, "\n");
    sText = sText.replace(/<\s*\/\s*(p|div|h[1-6]|li|tr|blockquote|pre|table|ul|ol)\s*>/ig, "\n");
    sText = sText.replace(/<\s*\/\s*(td|th)\s*>/ig, "\t");
    oNode.innerHTML = sText;
    sText = (oNode.textContent || oNode.innerText || "").replace(/\u00a0/g, " ").replace(/\s+/g, " ").trim();
    return sText == "";
}

function validateMailForm(oForm, sBodyFormat) {
    var aErrors = [];
    var sTo = getMailFormFieldValue(oForm, "mail_to");
    var sCc = getMailFormFieldValue(oForm, "mail_cc");
    var sBcc = getMailFormFieldValue(oForm, "mail_bcc");
    var sFrom = getMailFormFieldValue(oForm, "mail_from");
    var sSender = getMailFormFieldValue(oForm, "mail_sender");
    var sReplyTo = getMailFormFieldValue(oForm, "mail_reply_to");
    var sMessage = oForm && oForm.elements.mail_message ? String(oForm.elements.mail_message.value || "") : "";
    var oAttachmentInput = oForm ? oForm.querySelector("[name=\"mail_attachments[]\"]") : null;
    var blHasAttachments = oAttachmentInput && oAttachmentInput.files && oAttachmentInput.files.length > 0;
    var blMailBodyIsEmpty = sBodyFormat == "plain" ? isMailFormBodyEmpty(sMessage, "plain") : isMailFormBodyEmpty(sMessage, "html") && !blHasAttachments;
    var aAllowedSenderDomains = getMailFormAllowedSenderDomains(oForm);
    var blRestrictFromToSingleAddress = oForm && oForm.getAttribute("data-mail-restrict-from-to-single-address") == "1";
    var aTo = mailFormNormalizeEmailList(sTo);
    var aCc = mailFormNormalizeEmailList(sCc);
    var aBcc = mailFormNormalizeEmailList(sBcc);
    var aFrom = mailFormNormalizeEmailList(sFrom);
    var aSender = mailFormNormalizeSingleEmail(sSender);
    var aReplyTo = mailFormNormalizeEmailList(sReplyTo);
    var iRecipientCount = (aTo !== false ? aTo.count : 0) + (aCc !== false ? aCc.count : 0) + (aBcc !== false ? aBcc.count : 0);
    if (sTo != "" && aTo === false) {
        aErrors.push({message: "Invalid To.", field: "mail_to"});
    }
    if (sCc != "" && aCc === false) {
        aErrors.push({message: "Invalid carbon copy.", field: "mail_cc"});
    }
    if (sBcc != "" && aBcc === false) {
        aErrors.push({message: "Invalid blind copy.", field: "mail_bcc"});
    }
    if (iRecipientCount < 1 && sTo == "" && sCc == "" && sBcc == "") {
        aErrors.push({message: "Recipient required.", field: "mail_to"});
    }
    if (sFrom != "" && aFrom === false) {
        aErrors.push({message: "Invalid From.", field: "mail_from"});
    }
    if (sFrom != "" && aFrom !== false && !mailFormEmailListUsesAllowedSenderDomains(aFrom, aAllowedSenderDomains)) {
        aErrors.push({message: "Invalid From domain.", field: "mail_from"});
    }
    if (sSender != "" && aSender === false) {
        aErrors.push({message: "Invalid Sender.", field: "mail_sender"});
    }
    if (sSender != "" && aSender !== false && !mailFormEmailListUsesAllowedSenderDomains(aSender, aAllowedSenderDomains)) {
        aErrors.push({message: "Invalid Sender domain.", field: "mail_sender"});
    }
    if (aFrom !== false && aFrom.count > 1 && blRestrictFromToSingleAddress) {
        aErrors.push({message: "Single From required.", field: "mail_from"});
    }
    if (aFrom !== false && aFrom.count > 1 && !blRestrictFromToSingleAddress && sSender == "") {
        aErrors.push({message: "Sender required.", field: "mail_sender"});
    }
    if (sReplyTo != "" && aReplyTo === false) {
        aErrors.push({message: "Invalid Reply-To.", field: "mail_reply_to"});
    }
    if (blMailBodyIsEmpty) {
        aErrors.push({message: "Message required.", field: "mail_message"});
    }
    return {errors: aErrors};
}

function getMailFormValidationMessage(aValidation) {
    var aMessages = [];
    var i;
    if (!aValidation || !aValidation.errors || aValidation.errors.length < 1) {
        return "";
    }
    if (isMailPmdLike()) {
        return aValidation.errors[0].message;
    }
    for (i = 0; i < aValidation.errors.length; i++) {
        aMessages.push(aValidation.errors[i].message);
    }
    return aMessages.join(" ");
}

function setMailFormStatus(oStatus, sMessage, sStatusClass) {
    if (!oStatus) {
        return;
    }
    oStatus.textContent = sMessage;
    oStatus.className = sStatusClass != "" ? "mail-form-status " + sStatusClass : "mail-form-status";
}

function bindMailForm() {
    var oForm = document.getElementById("mail-form");
    var oStatus = document.querySelector(".mail-form-status");
    var sStatusClass = oStatus ? " " + (oStatus.className || "") + " " : "";
    var aSuggestInputs;
    var i;
    if (!oForm) {
        return;
    }
    aSuggestInputs = oForm.querySelectorAll("[data-mail-recipient-suggest]");
    for (i = 0; i < aSuggestInputs.length; i++) {
        bindMailRecipientSuggestInput(aSuggestInputs[i]);
    }
    if (oStatus && sStatusClass.indexOf(" message-success ") !== -1) {
        window.setTimeout(function() {
            oStatus.textContent = "";
            oStatus.className = "mail-form-status";
        }, 5000);
    }
    oForm.addEventListener("submit", function(oEvent) {
        var aValidation;
        var oField;
        var oSubmitter = oEvent && oEvent.submitter ? oEvent.submitter : document.activeElement;
        var sBodyFormat = oSubmitter && oSubmitter.name == "mail_body_format" && oSubmitter.value == "plain" ? "plain" : "html";
        if (window.tinymce && typeof window.tinymce.triggerSave == "function") {
            window.tinymce.triggerSave();
        }
        aValidation = validateMailForm(oForm, sBodyFormat);
        if (aValidation.errors && aValidation.errors.length > 0) {
            if (oEvent && typeof oEvent.preventDefault == "function") {
                oEvent.preventDefault();
            }
            setMailFormStatus(oStatus, getMailFormValidationMessage(aValidation), "message-error");
            oField = aValidation.errors[0].field && oForm.elements[aValidation.errors[0].field] ? oForm.elements[aValidation.errors[0].field] : null;
            if (oField && typeof oField.focus == "function") {
                oField.focus();
            }
            return false;
        }
    });
}

function resizeMailEditor(iHeight) {
    var oTextarea = document.querySelector("#mail-form .js-snippet-board-textarea");
    var oEditor;
    var oContainer;
    if (!oTextarea || !window.tinymce || typeof window.tinymce.get != "function") {
        return;
    }
    window.setTimeout(function() {
        oEditor = window.tinymce.get(oTextarea.id);
        if (oEditor && typeof oEditor.getContainer == "function") {
            oContainer = oEditor.getContainer();
            if (oContainer) {
                oContainer.style.height = iHeight + "px";
                oContainer.style.minHeight = iHeight + "px";
            }
            if (typeof oEditor.dispatch == "function") {
                oEditor.dispatch("ResizeEditor");
            }
            scheduleMailToolbarLines(oEditor);
        }
    }, 0);
}

function layoutMailForm() {
    var oForm = document.getElementById("mail-form");
    var oTextarea = oForm ? oForm.querySelector(".js-snippet-board-textarea") : null;
    var oEditor = oTextarea && window.tinymce && typeof window.tinymce.get == "function" ? window.tinymce.get(oTextarea.id) : null;
    var oContainer = oEditor && typeof oEditor.getContainer == "function" ? oEditor.getContainer() : null;
    var oTarget = oContainer || oTextarea;
    var oVisualViewport = window.visualViewport || null;
    var iViewportHeight = oVisualViewport ? Math.floor(oVisualViewport.height) : (window.innerHeight || document.documentElement.clientHeight || 768);
    var iTop;
    var iEditorHeight;
    if (!oForm || !oTextarea || !oTarget) {
        return;
    }
    iTop = oTarget.getBoundingClientRect().top - (oVisualViewport ? oVisualViewport.offsetTop : 0);
    iEditorHeight = Math.max(220, Math.floor(iViewportHeight - iTop - 6));
    oTextarea.style.height = iEditorHeight + "px";
    resizeMailEditor(iEditorHeight);
}

function isMailEditor(oEditor) {
    var oElement = oEditor && typeof oEditor.getElement == "function" ? oEditor.getElement() : null;
    return !!(oElement && oElement.form && oElement.form.id == "mail-form");
}

function getMailRichTextPasteInput() {
    return document.querySelector("#mail-form .js-mail-rich-text-paste");
}

function setMailRichTextPasteInputValue(blEnabled) {
    var oInput = getMailRichTextPasteInput();
    if (oInput) {
        oInput.value = blEnabled ? "1" : "0";
    }
}

function setMailEditorPlainTextPasteMode(oEditor, blEnabled) {
    var blCurrent;
    if (!oEditor || typeof oEditor.execCommand != "function" || typeof oEditor.queryCommandState != "function") {
        return;
    }
    blCurrent = oEditor.queryCommandState("mceTogglePlainTextPaste");
    if ((blCurrent ? 1 : 0) != (blEnabled ? 1 : 0)) {
        oEditor.execCommand("mceTogglePlainTextPaste");
    }
    scheduleMailPasteModeButtonSync(oEditor);
}

function setMailPasteModeButtonState(oButton, blActive) {
    if (!oButton) {
        return;
    }
    if (blActive) {
        addAdminClass(oButton, "tox-tbtn--enabled");
        addAdminClass(oButton, "tox-tbtn--active");
    } else {
        removeAdminClass(oButton, "tox-tbtn--enabled");
        removeAdminClass(oButton, "tox-tbtn--active");
    }
    oButton.setAttribute("aria-pressed", blActive ? "true" : "false");
}

function updateMailPasteModeButtons(oEditor) {
    var blPlainTextPaste;
    var oContainer;
    var aFormattedButtons;
    var aPlainTextButtons;
    var i;
    if (!oEditor || typeof oEditor.queryCommandState != "function") {
        return;
    }
    blPlainTextPaste = oEditor.queryCommandState("mceTogglePlainTextPaste");
    oContainer = typeof oEditor.getContainer == "function" ? oEditor.getContainer() : null;
    if (!oContainer) {
        return;
    }
    aFormattedButtons = oContainer.querySelectorAll(".tox-tbtn[data-mce-name=\"snippetpasteformatted\"]");
    aPlainTextButtons = oContainer.querySelectorAll(".tox-tbtn[data-mce-name=\"snippetpastetext\"]");
    for (i = 0; i < aFormattedButtons.length; i++) {
        setMailPasteModeButtonState(aFormattedButtons[i], !blPlainTextPaste);
    }
    for (i = 0; i < aPlainTextButtons.length; i++) {
        setMailPasteModeButtonState(aPlainTextButtons[i], blPlainTextPaste);
    }
}

function scheduleMailPasteModeButtonSync(oEditor) {
    var iToken;
    if (!oEditor) {
        return;
    }
    oEditor._mailPasteModeSyncToken = (oEditor._mailPasteModeSyncToken || 0) + 1;
    iToken = oEditor._mailPasteModeSyncToken;
    updateMailPasteModeButtons(oEditor);
    window.setTimeout(function() {
        if (oEditor._mailPasteModeSyncToken == iToken) {
            updateMailPasteModeButtons(oEditor);
        }
    }, 0);
    window.setTimeout(function() {
        if (oEditor._mailPasteModeSyncToken == iToken) {
            updateMailPasteModeButtons(oEditor);
        }
    }, 80);
    window.setTimeout(function() {
        if (oEditor._mailPasteModeSyncToken == iToken) {
            updateMailPasteModeButtons(oEditor);
        }
    }, 250);
}

function applyMailStoredRichTextPaste(oEditor) {
    var oInput;
    if (!isMailEditor(oEditor)) {
        return;
    }
    oInput = getMailRichTextPasteInput();
    setMailEditorPlainTextPasteMode(oEditor, !oInput || oInput.value != "1");
}

function saveMailRichTextPaste(blEnabled) {
    var oData = new FormData();
    setMailRichTextPasteInputValue(blEnabled);
    appendAdminEncodedValue(oData, "action", "save_mail_rich_text_paste");
    appendAdminEncodedValue(oData, "rich_text_paste", blEnabled ? "1" : "0");
    submitMailAdminRequest(oData, null, function(sMessage) {
        logAdminException(sMessage);
    });
}

function setMailEditorStoredRichTextPaste(oEditor, blEnabled) {
    setMailEditorPlainTextPasteMode(oEditor, !blEnabled);
    saveMailRichTextPaste(blEnabled);
    scheduleMailPasteModeButtonSync(oEditor);
}

function getMailEditorPlainTextContent(oEditor) {
    var sText = "";
    if (oEditor && oEditor.selection && typeof oEditor.selection.getContent == "function") {
        sText = oEditor.selection.getContent({format: "text"});
    }
    if (sText == "" && oEditor && typeof oEditor.getContent == "function") {
        sText = oEditor.getContent({format: "text"});
    }
    return sText;
}

function getMailEditorHtmlContent(oEditor) {
    var sHtml = "";
    if (oEditor && oEditor.selection && typeof oEditor.selection.getContent == "function") {
        sHtml = oEditor.selection.getContent({format: "html"});
    }
    if (sHtml == "" && oEditor && typeof oEditor.getContent == "function") {
        sHtml = oEditor.getContent({format: "html"});
    }
    return sHtml;
}

function copyMailPlainText(oEditor) {
    var sText = getMailEditorPlainTextContent(oEditor);
    var oTextarea;
    var blCopied = false;
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(sText).catch(function(oException) {
            logAdminException(oException);
            showAdminMessageDialog("Plain text could not be copied.", "Clipboard Error");
        });
        return;
    }
    oTextarea = document.createElement("textarea");
    oTextarea.value = sText;
    oTextarea.setAttribute("readonly", "readonly");
    oTextarea.style.position = "fixed";
    oTextarea.style.left = "-9999px";
    document.body.appendChild(oTextarea);
    oTextarea.select();
    try {
        blCopied = document.execCommand("copy");
    } catch (oException) {
        logAdminException(oException);
    }
    document.body.removeChild(oTextarea);
    if (!blCopied) {
        showAdminMessageDialog("Plain text could not be copied.", "Clipboard Error");
    }
}

function copyMailRichText(oEditor) {
    var sHtml = getMailEditorHtmlContent(oEditor);
    var sText = getMailEditorPlainTextContent(oEditor);
    var oItem;
    if (navigator.clipboard && navigator.clipboard.write && window.ClipboardItem) {
        oItem = new ClipboardItem({
            "text/html": new Blob([sHtml], {type: "text/html"}),
            "text/plain": new Blob([sText], {type: "text/plain"})
        });
        navigator.clipboard.write([oItem]).catch(function(oException) {
            logAdminException(oException);
            copyMailPlainText(oEditor);
        });
        return;
    }
    copyMailPlainText(oEditor);
}

function isMailPmdLike() {
    return document.body && document.body.getAttribute("data-pmd-like") == "1";
}

function isMailScrollLockPage() {
    return !!(document.body && (" " + (document.body.className || "") + " ").indexOf(" mail-page ") !== -1);
}

function isMailPageScrollAllowedTarget(oTarget) {
    var oElement = oTarget;
    var sClass;
    while (oElement && oElement != document.body) {
        if (oElement.nodeType == 1 && typeof oElement.getAttribute == "function") {
            sClass = " " + (oElement.getAttribute("class") || "") + " ";
            if (sClass.indexOf(" tox-edit-area__iframe ") !== -1
                || sClass.indexOf(" js-snippet-board-textarea ") !== -1
                || sClass.indexOf(" confirm-dialog ") !== -1
                || sClass.indexOf(" tox-tinymce-aux ") !== -1) {
                return true;
            }
        }
        oElement = oElement.parentNode;
    }
    return false;
}

function lockMailPageScroll() {
    if (!isMailPmdLike()) {
        return;
    }
    if ((window.pageXOffset || document.documentElement.scrollLeft || document.body.scrollLeft || 0) != 0
        || (window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0) != 0) {
        window.scrollTo(0, 0);
    }
    document.documentElement.scrollLeft = 0;
    document.documentElement.scrollTop = 0;
    document.body.scrollLeft = 0;
    document.body.scrollTop = 0;
}

function preventMailPageScroll(oEvent) {
    if (!isMailPmdLike() || isMailPageScrollAllowedTarget(oEvent.target)) {
        return;
    }
    if (oEvent.cancelable) {
        oEvent.preventDefault();
    }
    lockMailPageScroll();
}

function bindMailPageScrollLock() {
    if (!isMailScrollLockPage()) {
        return;
    }
    document.addEventListener("touchmove", preventMailPageScroll, {passive: false});
    document.addEventListener("wheel", preventMailPageScroll, {passive: false});
    window.addEventListener("scroll", lockMailPageScroll);
    lockMailPageScroll();
}

function getMailPmdHoverButton(oTarget) {
    var oButton;
    var oParent;
    var sClass;
    var sParentClass;
    if (!oTarget || !oTarget.closest) {
        return null;
    }
    oButton = oTarget.closest(".tox-tbtn, .tox-split-button, .tox-split-button__main, .tox-split-button__chevron");
    if (!oButton || !oButton.closest || !oButton.closest(".snippet-board-form .tox")) {
        return null;
    }
    sClass = " " + (oButton.getAttribute("class") || "") + " ";
    if (sClass.indexOf(" tox-split-button__main ") !== -1 || sClass.indexOf(" tox-split-button__chevron ") !== -1) {
        oParent = oButton.parentNode;
        sParentClass = oParent && typeof oParent.getAttribute == "function" ? " " + (oParent.getAttribute("class") || "") + " " : "";
        if (sParentClass.indexOf(" tox-split-button ") !== -1) {
            return oParent;
        }
    }
    return oButton;
}

function isMailPmdDropdownButton(oButton) {
    var sClass;
    var sHasPopup;
    var sName;
    var oParent;
    var sParentClass;
    if (!oButton || typeof oButton.getAttribute != "function") {
        return false;
    }
    sClass = " " + (oButton.getAttribute("class") || "") + " ";
    sHasPopup = oButton.getAttribute("aria-haspopup") || "";
    sName = oButton.getAttribute("data-mce-name") || "";
    if (sClass.indexOf(" tox-split-button ") !== -1
        || sClass.indexOf(" tox-tbtn--select ") !== -1
        || sClass.indexOf(" tox-tbtn--bespoke ") !== -1
        || (sHasPopup != "" && sHasPopup != "false")
        || sName.substring(sName.length - 8) == "-chevron") {
        return true;
    }
    oParent = oButton.parentNode;
    sParentClass = oParent && typeof oParent.getAttribute == "function" ? " " + (oParent.getAttribute("class") || "") + " " : "";
    return sParentClass.indexOf(" tox-split-button ") !== -1;
}

function clearMailPmdHoverButton(oButton, blExpire) {
    if (!oButton) {
        return;
    }
    if (oButton._mailPmdHoverTimer && window.clearTimeout) {
        window.clearTimeout(oButton._mailPmdHoverTimer);
    }
    oButton._mailPmdHoverTimer = null;
    removeAdminClass(oButton, "snippet-board-pmd-hover-active");
    if (blExpire) {
        addAdminClass(oButton, "snippet-board-pmd-hover-expired");
    } else {
        removeAdminClass(oButton, "snippet-board-pmd-hover-expired");
    }
}

function getMailPmdDropdownMenu() {
    var aMenus = document.querySelectorAll(".tox.tox-tinymce-aux .tox-menu, .tox.tox-tinymce-aux .tox-dropdown-content, .tox.tox-tinymce-aux .tox-collection, .tox.tox-tinymce-aux [role=\"menu\"], .tox.tox-tinymce-aux [role=\"listbox\"]");
    var i;
    for (i = 0; i < aMenus.length; i++) {
        if (aMenus[i].getClientRects && aMenus[i].getClientRects().length > 0) {
            return aMenus[i];
        }
    }
    return null;
}

function clearMailPmdDropdown() {
    var oButton = oMailPmdDropdown;
    if (!oMailPmdDropdown) {
        return;
    }
    oMailPmdDropdown = null;
    iMailPmdDropdownToken += 1;
    clearMailPmdHoverButton(oButton, true);
}

function syncMailPmdDropdown(iToken, blCloseWhenMissing) {
    if (!oMailPmdDropdown || iToken != iMailPmdDropdownToken) {
        return;
    }
    if (getMailPmdDropdownMenu()) {
        removeAdminClass(oMailPmdDropdown, "snippet-board-pmd-hover-expired");
        addAdminClass(oMailPmdDropdown, "snippet-board-pmd-hover-active");
    } else if (blCloseWhenMissing) {
        clearMailPmdDropdown();
    }
}

function scheduleMailPmdDropdownSync(iToken, iDelay, blCloseWhenMissing) {
    if (!window.setTimeout) {
        return;
    }
    window.setTimeout(function() {
        syncMailPmdDropdown(iToken, blCloseWhenMissing);
    }, iDelay);
}

function watchMailPmdDropdown(oButton) {
    var iToken;
    if (!oButton || !window.setTimeout) {
        return;
    }
    oMailPmdDropdown = oButton;
    iMailPmdDropdownToken += 1;
    iToken = iMailPmdDropdownToken;
    removeAdminClass(oButton, "snippet-board-pmd-hover-expired");
    syncMailPmdDropdown(iToken, false);
    scheduleMailPmdDropdownSync(iToken, 0, false);
    scheduleMailPmdDropdownSync(iToken, 80, false);
    scheduleMailPmdDropdownSync(iToken, 250, false);
    scheduleMailPmdDropdownSync(iToken, 1200, true);
}

function clearMailPmdHoverButtons(oCurrentButton) {
    var aButtons = document.querySelectorAll(".snippet-board-form .tox .snippet-board-pmd-hover-active, .snippet-board-form .tox .snippet-board-pmd-hover-expired");
    var i;
    for (i = 0; i < aButtons.length; i++) {
        if (aButtons[i] == oCurrentButton) {
            continue;
        }
        clearMailPmdHoverButton(aButtons[i], true);
    }
}

function startMailPmdHoverTimeout(oButton) {
    if (!oButton || !window.setTimeout || !window.clearTimeout) {
        return;
    }
    clearMailPmdHoverButtons(oButton);
    removeAdminClass(oButton, "snippet-board-pmd-hover-expired");
    addAdminClass(oButton, "snippet-board-pmd-hover-active");
    if (oButton._mailPmdHoverTimer) {
        window.clearTimeout(oButton._mailPmdHoverTimer);
    }
    oButton._mailPmdHoverTimer = window.setTimeout(function() {
        clearMailPmdHoverButton(oButton, true);
    }, 1000);
}

function handleMailPmdHoverStart(oEvent) {
    var oButton;
    if (!isMailPmdLike() || (oEvent.pointerType && oEvent.pointerType == "mouse")) {
        return;
    }
    oButton = getMailPmdHoverButton(oEvent.target);
    if (oButton) {
        if (isMailPmdDropdownButton(oButton)) {
            if (oMailPmdDropdown && oMailPmdDropdown != oButton) {
                clearMailPmdDropdown();
            }
            clearMailPmdHoverButtons(oButton);
            clearMailPmdHoverButton(oButton, false);
            watchMailPmdDropdown(oButton);
        } else {
            clearMailPmdDropdown();
            startMailPmdHoverTimeout(oButton);
        }
    } else if (!oEvent.target.closest || !oEvent.target.closest(".tox.tox-tinymce-aux")) {
        clearMailPmdDropdown();
    }
}

function handleMailPmdDropdownMenuClick(oEvent) {
    if (!isMailPmdLike() || !oMailPmdDropdown || !oEvent.target.closest || !oEvent.target.closest(".tox.tox-tinymce-aux")) {
        return;
    }
    window.setTimeout(clearMailPmdDropdown, 0);
}

function bindMailPmdHoverTimeout() {
    if (!isMailScrollLockPage()) {
        return;
    }
    if (window.PointerEvent) {
        document.addEventListener("pointerdown", handleMailPmdHoverStart, {passive: true});
    } else {
        document.addEventListener("touchstart", handleMailPmdHoverStart, {passive: true});
    }
    document.addEventListener("click", handleMailPmdDropdownMenuClick);
}

function getMailEditorScrollState(oEditor) {
    var oDoc = oEditor && typeof oEditor.getDoc == "function" ? oEditor.getDoc() : null;
    var oRoot = oDoc ? oDoc.documentElement : null;
    var oBody = oDoc ? oDoc.body : null;
    var oScroll = oDoc ? (oDoc.scrollingElement || oRoot || oBody) : null;
    var iScrollTop = Math.max(oScroll ? oScroll.scrollTop : 0, oRoot ? oRoot.scrollTop : 0, oBody ? oBody.scrollTop : 0);
    var iClientHeight = oScroll ? oScroll.clientHeight : 0;
    var iScrollHeight = Math.max(oScroll ? oScroll.scrollHeight : 0, oRoot ? oRoot.scrollHeight : 0, oBody ? oBody.scrollHeight : 0);
    return {
        scrollTop: iScrollTop,
        clientHeight: iClientHeight,
        scrollHeight: iScrollHeight
    };
}

function shouldPreventMailEditorOverscroll(oEditor, iDeltaY) {
    var aState = getMailEditorScrollState(oEditor);
    if (aState.scrollHeight <= aState.clientHeight + 1) {
        return true;
    }
    if (iDeltaY < 0 && aState.scrollTop <= 0) {
        return true;
    }
    if (iDeltaY > 0 && aState.scrollTop + aState.clientHeight >= aState.scrollHeight - 1) {
        return true;
    }
    return false;
}

function bindMailEditorScrollLock(oEditor) {
    var oDoc = oEditor && typeof oEditor.getDoc == "function" ? oEditor.getDoc() : null;
    var oRoot = oDoc ? oDoc.documentElement : null;
    var oBody = oDoc ? oDoc.body : null;
    if (!oDoc || !isMailPmdLike() || oEditor._mailScrollLockBound) {
        return;
    }
    oEditor._mailScrollLockBound = true;
    if (oRoot && oRoot.style) {
        oRoot.style.overscrollBehavior = "contain";
    }
    if (oBody && oBody.style) {
        oBody.style.overscrollBehavior = "contain";
    }
    oDoc.addEventListener("touchstart", function(oEvent) {
        oEditor._mailTouchY = oEvent.touches && oEvent.touches.length ? oEvent.touches[0].clientY : 0;
    }, {passive: true});
    oDoc.addEventListener("touchmove", function(oEvent) {
        var iClientY = oEvent.touches && oEvent.touches.length ? oEvent.touches[0].clientY : 0;
        var iDeltaY = (oEditor._mailTouchY || iClientY) - iClientY;
        oEditor._mailTouchY = iClientY;
        if (shouldPreventMailEditorOverscroll(oEditor, iDeltaY) && oEvent.cancelable) {
            oEvent.preventDefault();
            lockMailPageScroll();
        }
    }, {passive: false});
    oDoc.addEventListener("wheel", function(oEvent) {
        if (shouldPreventMailEditorOverscroll(oEditor, oEvent.deltaY || 0) && oEvent.cancelable) {
            oEvent.preventDefault();
            lockMailPageScroll();
        }
    }, {passive: false});
}

function updateMailToolbarLines(oEditor) {
    var oContainer = oEditor && typeof oEditor.getContainer == "function" ? oEditor.getContainer() : null;
    var oHeader = oContainer ? oContainer.querySelector(".tox-editor-header") : null;
    var aGroups = oHeader ? oHeader.querySelectorAll(".tox-toolbar__group") : [];
    var oOldLayer = oHeader ? oHeader.querySelector(".snippet-board-toolbar-lines") : null;
    var oHeaderRect;
    var oGroupRect;
    var oLayer;
    var oUsedLines = {};
    var oDrawnLines = {};
    var iTop;
    var iBottom;
    var blHasToolbarRow = false;
    var sKey;
    var oLine;
    var iLineTop;
    var i;
    if (oOldLayer && oOldLayer.parentNode) {
        oOldLayer.parentNode.removeChild(oOldLayer);
    }
    if (!oHeader) {
        return false;
    }
    removeAdminClass(oHeader, "snippet-board-toolbar-lines-ready");
    oHeaderRect = oHeader.getBoundingClientRect();
    if (!aGroups.length || !oHeaderRect.width || !oHeaderRect.height) {
        return false;
    }
    for (i = 0; i < aGroups.length; i++) {
        oGroupRect = aGroups[i].getBoundingClientRect();
        if (!oGroupRect.width || !oGroupRect.height) {
            continue;
        }
        iTop = Math.round(oGroupRect.top - oHeaderRect.top);
        iBottom = Math.max(iTop, Math.round(oGroupRect.bottom - oHeaderRect.top) - 1);
        sKey = "row-" + iTop;
        blHasToolbarRow = true;
        oUsedLines[sKey] = iBottom;
    }
    if (!blHasToolbarRow) {
        return false;
    }
    addAdminClass(oHeader, "snippet-board-toolbar-lines-ready");
    oLayer = document.createElement("div");
    oLayer.className = "snippet-board-toolbar-lines";
    oHeader.appendChild(oLayer);
    for (sKey in oUsedLines) {
        iLineTop = oUsedLines[sKey];
        if (typeof iLineTop != "number") {
            continue;
        }
        if (oDrawnLines["line-" + iLineTop]) {
            continue;
        }
        oDrawnLines["line-" + iLineTop] = true;
        oLine = document.createElement("div");
        oLine.className = "snippet-board-toolbar-line";
        oLine.style.top = iLineTop + "px";
        oLayer.appendChild(oLine);
    }
    return true;
}

function scheduleMailToolbarLines(oEditor, iAttempt) {
    var iCurrentAttempt = typeof iAttempt == "number" ? iAttempt : 0;
    var iToken;
    function runToolbarLinesUpdate() {
        var blReady;
        if (oEditor._mailToolbarLineSchedule != iToken) {
            return;
        }
        blReady = updateMailToolbarLines(oEditor);
        if (iCurrentAttempt < 6 || (!blReady && iCurrentAttempt < 20)) {
            window.setTimeout(function() {
                scheduleMailToolbarLines(oEditor, iCurrentAttempt + 1);
            }, iCurrentAttempt < 6 ? 50 : 100);
        }
    }
    if (!oEditor) {
        return;
    }
    if (!iCurrentAttempt) {
        oEditor._mailToolbarLineSchedule = (oEditor._mailToolbarLineSchedule || 0) + 1;
    }
    iToken = oEditor._mailToolbarLineSchedule;
    if (window.requestAnimationFrame) {
        window.requestAnimationFrame(runToolbarLinesUpdate);
    } else {
        window.setTimeout(runToolbarLinesUpdate, 0);
    }
}

function bindMailTinyMce() {
    if (!document.querySelector("#mail-form textarea.js-snippet-board-textarea") || !window.tinymce || typeof window.tinymce.init != "function") {
        return;
    }
    try {
        window.tinymce.init({
            selector: "#mail-form textarea.js-snippet-board-textarea",
            menubar: false,
            branding: false,
            promotion: false,
            browser_spellcheck: true,
            convert_urls: false,
            content_css: "tinymce-5",
            entity_encoding: "raw",
            height: 320,
            license_key: "gpl",
            mobile: {
                toolbar_mode: "wrap"
            },
            resize: false,
            skin: "tinymce-5",
            statusbar: false,
            toolbar: "undo redo | blocks fontfamily fontsize lineheight | cut snippetcopy snippetcopyplain snippetpasteformatted snippetpastetext | bold italic underline strikethrough subscript superscript | forecolor backcolor | alignleft aligncenter alignright alignjustify alignnone ltr rtl | snippetbullist snippetnumlist outdent indent blockquote hr | link unlink anchor table | charmap emoticons insertdatetime nonbreaking pagebreak | searchreplace visualblocks help codesample",
            toolbar_mode: "wrap",
            plugins: "advlist anchor autolink charmap codesample directionality emoticons help insertdatetime link lists nonbreaking pagebreak searchreplace table visualblocks",
            content_style: "html{overscroll-behavior-y:contain;}body{background:#fff;color:#111;font-family:Arial,sans-serif;font-size:14px;line-height:1.35;margin:0;overscroll-behavior-y:contain;padding:1px;}p,h1,h2,h3,h4,h5,h6{margin:0;}ul,ol{margin:0 0 0 20px;padding-left:18px;}blockquote{margin:0 0 0 24px;}a{color:#075e9e;}",
            setup: function(oEditor) {
                oEditor.ui.registry.addIcon("snippet-copy-plain", "<svg width=\"24\" height=\"24\"><path d=\"M8 3h8l4 4v11c0 1.1-.9 2-2 2H8a2 2 0 0 1-2-2V5c0-1.1.9-2 2-2Zm7 2H8v13h10V8h-3V5Z\"/><path d=\"M4 7H2v13c0 1.1.9 2 2 2h10v-2H4V7Zm6 2h4v1.5h-4V9Zm0 3h6v1.5h-6V12Zm0 3h6v1.5h-6V15Z\"/></svg>");
                oEditor.ui.registry.addButton("snippetcopy", {
                    icon: "copy",
                    tooltip: "Copy formatted text",
                    onAction: function() {
                        copyMailRichText(oEditor);
                    }
                });
                oEditor.ui.registry.addMenuButton("snippetbullist", {
                    icon: "unordered-list",
                    tooltip: "Bullet list",
                    fetch: function(oCallback) {
                        oCallback([
                            {type: "menuitem", text: "Default", icon: "list-bull-default", onAction: function() { oEditor.execCommand("InsertUnorderedList", false, {"list-style-type": ""}); }},
                            {type: "menuitem", text: "Disc", icon: "list-bull-disc", onAction: function() { oEditor.execCommand("InsertUnorderedList", false, {"list-style-type": "disc"}); }},
                            {type: "menuitem", text: "Circle", icon: "list-bull-circle", onAction: function() { oEditor.execCommand("InsertUnorderedList", false, {"list-style-type": "circle"}); }},
                            {type: "menuitem", text: "Square", icon: "list-bull-square", onAction: function() { oEditor.execCommand("InsertUnorderedList", false, {"list-style-type": "square"}); }}
                        ]);
                    }
                });
                oEditor.ui.registry.addMenuButton("snippetnumlist", {
                    icon: "ordered-list",
                    tooltip: "Numbered list",
                    fetch: function(oCallback) {
                        oCallback([
                            {type: "menuitem", text: "Default", icon: "list-num-default", onAction: function() { oEditor.execCommand("InsertOrderedList", false, {"list-style-type": ""}); }},
                            {type: "menuitem", text: "Lower Alpha", icon: "list-num-lower-alpha", onAction: function() { oEditor.execCommand("InsertOrderedList", false, {"list-style-type": "lower-alpha"}); }},
                            {type: "menuitem", text: "Lower Greek", icon: "list-num-lower-greek", onAction: function() { oEditor.execCommand("InsertOrderedList", false, {"list-style-type": "lower-greek"}); }},
                            {type: "menuitem", text: "Lower Roman", icon: "list-num-lower-roman", onAction: function() { oEditor.execCommand("InsertOrderedList", false, {"list-style-type": "lower-roman"}); }},
                            {type: "menuitem", text: "Upper Alpha", icon: "list-num-upper-alpha", onAction: function() { oEditor.execCommand("InsertOrderedList", false, {"list-style-type": "upper-alpha"}); }},
                            {type: "menuitem", text: "Upper Roman", icon: "list-num-upper-roman", onAction: function() { oEditor.execCommand("InsertOrderedList", false, {"list-style-type": "upper-roman"}); }}
                        ]);
                    }
                });
                oEditor.ui.registry.addButton("snippetpasteformatted", {
                    icon: "paste",
                    tooltip: "Formatted text paste mode",
                    onAction: function() {
                        setMailEditorStoredRichTextPaste(oEditor, true);
                    },
                    onSetup: function(oApi) {
                        function updateFormattedPasteState() {
                            scheduleMailPasteModeButtonSync(oEditor);
                        }
                        oEditor.on("PastePlainTextToggle", updateFormattedPasteState);
                        oEditor.on("init", updateFormattedPasteState);
                        updateFormattedPasteState();
                        return function() {
                            oEditor.off("PastePlainTextToggle", updateFormattedPasteState);
                            oEditor.off("init", updateFormattedPasteState);
                        };
                    }
                });
                oEditor.ui.registry.addButton("snippetpastetext", {
                    icon: "paste-text",
                    tooltip: "Plain text paste mode",
                    onAction: function() {
                        setMailEditorStoredRichTextPaste(oEditor, false);
                    },
                    onSetup: function(oApi) {
                        function updatePasteTextState() {
                            scheduleMailPasteModeButtonSync(oEditor);
                        }
                        oEditor.on("PastePlainTextToggle", updatePasteTextState);
                        oEditor.on("init", updatePasteTextState);
                        updatePasteTextState();
                        return function() {
                            oEditor.off("PastePlainTextToggle", updatePasteTextState);
                            oEditor.off("init", updatePasteTextState);
                        };
                    }
                });
                oEditor.ui.registry.addButton("snippetcopyplain", {
                    icon: "snippet-copy-plain",
                    tooltip: "Copy plain text",
                    onAction: function() {
                        copyMailPlainText(oEditor);
                    }
                });
                oEditor.on("focus click mousedown mouseup", function() {
                    closeExMenus(null);
                });
                function syncMailEditorChange() {
                    var oElement = oEditor.getElement();
                    var sValue;
                    if (!oElement) {
                        return;
                    }
                    sValue = oElement.value;
                    oEditor.save();
                    if (oElement.value != sValue) {
                        dispatchAdminInputEvent(oElement);
                    }
                }
                function syncMailEditorChangeAfterEvent() {
                    window.setTimeout(syncMailEditorChange, 0);
                }
                oEditor.on("change keyup undo redo input", syncMailEditorChange);
                oEditor.on("paste cut ExecCommand PastePostProcess SetContent", syncMailEditorChangeAfterEvent);
                oEditor.on("init", function() {
                    bindMailEditorScrollLock(oEditor);
                    applyMailStoredRichTextPaste(oEditor);
                    layoutMailForm();
                    scheduleMailToolbarLines(oEditor);
                });
                oEditor.on("PostRender SkinLoaded ResizeEditor", function() {
                    scheduleMailToolbarLines(oEditor);
                });
                window.addEventListener("resize", function() {
                    scheduleMailToolbarLines(oEditor);
                });
            }
        });
    } catch (oException) {
        logAdminException(oException);
    }
}

document.addEventListener("DOMContentLoaded", function() {
    bindMailForm();
    bindMailPageScrollLock();
    bindMailPmdHoverTimeout();
    bindMailTinyMce();
    layoutMailForm();
    window.addEventListener("resize", layoutMailForm);
    if (window.visualViewport) {
        window.visualViewport.addEventListener("resize", layoutMailForm);
    }
});

document.addEventListener("DOMContentLoaded", scheduleRenderThrobberHide);
