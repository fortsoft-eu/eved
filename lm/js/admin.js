var iAdminModalCount = 0;
var sAdminBodyOverflow = "";
var iAdminScrollLeft = 0;
var iAdminScrollTop = 0;
var oSbPmdDropdown = null;
var iSbPmdDropdownToken = 0;

function logAdminException(oException) {
    if (window.console && window.console.error) {
        window.console.error(oException);
    }
}

function isAdminRenderThrobberActive() {
    var oRoot = document.documentElement;
    return oRoot && oRoot.getAttribute("data-render-throbber-lock-active") == "1";
}

function isAdminOverlayActive() {
    return iAdminModalCount > 0 || isAdminRenderThrobberActive();
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

function getAdminCsrfToken() {
    var oMeta = document.querySelector("meta[name=\"csrf-token\"]");
    return oMeta ? (oMeta.getAttribute("content") || "") : "";
}

function getAdminEmoji(sName) {
    var oData = document.getElementById("emoji-data");
    return oData ? (oData.getAttribute("data-" + sName) || "") : "";
}

function getAdminContactValue(oItem) {
    var oValue = oItem ? oItem.querySelector(".contact-value") : null;
    var sValue = oValue ? (oValue.textContent || "") : "";
    return sValue != "" ? sValue : (oItem ? (oItem.getAttribute("data-contact-value") || "") : "");
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

function setupLoginDialogEscape() {
    var oDialog = document.querySelector(".login-dialog");
    var oLogin = oDialog ? oDialog.querySelector("button[type=\"submit\"][name=\"action\"][value=\"login\"]") : null;
    var oCancel = oDialog ? oDialog.querySelector("button[type=\"submit\"][name=\"action\"][value=\"cancel\"]") : null;
    var aCancelTriggers = oDialog ? oDialog.querySelectorAll(".js-login-cancel") : [];
    var aLoginInputs = oDialog ? oDialog.querySelectorAll(".login-fields input") : [];
    var iI;
    if (!oDialog || !oLogin || !oCancel) {
        return;
    }
    function submitLogin(oEvent) {
        if (oEvent) {
            oEvent.preventDefault();
        }
        oLogin.click();
    }
    function cancelLogin(oEvent) {
        if (oEvent) {
            oEvent.preventDefault();
        }
        oCancel.click();
    }
    for (iI = 0; iI < aLoginInputs.length; iI += 1) {
        aLoginInputs[iI].addEventListener("keydown", function (oEvent) {
            if (oEvent.key == "Enter") {
                submitLogin(oEvent);
            }
        });
    }
    for (iI = 0; iI < aCancelTriggers.length; iI += 1) {
        aCancelTriggers[iI].addEventListener("click", cancelLogin);
    }
    document.addEventListener("keydown", function (oEvent) {
        if (oEvent.key == "Escape") {
            cancelLogin(oEvent);
        }
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
    setupLoginDialogEscape();
});

function setupTableRows() {
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
        oRow.style.backgroundColor = sColor;
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
        var oRow;
        if (oTarget && oTarget.nodeType == 3) {
            oTarget = oTarget.parentNode;
        }
        oRow = oTarget && oTarget.closest ? oTarget.closest("table tbody tr") : null;
        if (oRow && oRow.closest && oRow.closest(".business-hours-table")) {
            return null;
        }
        if (oRow && (" " + oRow.className + " ").indexOf(" admin-static-row ") !== -1) {
            return null;
        }
        return oRow;
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
        var i;
        if (!oRow || isInsideTableRow(oRow, oEvent.relatedTarget)) {
            return;
        }
        aRows = document.querySelectorAll("table tbody tr[data-hover=\"1\"]");
        for (i = 0; i < aRows.length; i++) {
            if (aRows[i] !== oRow) {
                aRows[i].setAttribute("data-hover", "0");
                applyRowColor(aRows[i]);
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
}

function submitAdminRequest(oData, fSuccess, fError) {
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
            alert(sMessage);
        }
    });
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

function getAdminInputDatalist(oInput) {
    var sListId = oInput ? (oInput.getAttribute("list") || "") : "";
    var oRoot = oInput ? oInput.form : null;
    var aLists;
    var i;
    if (!sListId) {
        return null;
    }
    if (oRoot && typeof oRoot.querySelectorAll == "function") {
        aLists = oRoot.querySelectorAll("datalist");
        for (i = 0; i < aLists.length; i++) {
            if (aLists[i].id == sListId) {
                return aLists[i];
            }
        }
    }
    return document.getElementById(sListId);
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

function buildFilterExpression(sFilter) {
    var aOrParts = String(sFilter || "").trim().split(/\s+OR\s+/i);
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

function setupFilterFocusButton() {
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
}

function setupTableFilter() {
    var aFilters = document.querySelectorAll(".js-table-filter");

    function getTableRecordRows(oTable) {
        if (!oTable) {
            return [];
        }
        if (oTable.tBodies && oTable.tBodies.length == 1) {
            return oTable.tBodies[0].rows;
        }
        return oTable.querySelectorAll("tbody tr");
    }

    function isTableRecordCountRow(oRow) {
        if ((" " + oRow.className + " ").indexOf(" quick-filter-static-row ") !== -1 || oRow.hidden || oRow.style.display == "none") {
            return false;
        }
        return typeof oRow.getClientRects != "function" || oRow.getClientRects().length > 0;
    }

    function refreshTableRecordCount(oTable, aRows) {
        var aCounts = oTable && oTable.id ? document.querySelectorAll(".js-table-record-count[data-table-count=\"" + oTable.id + "\"]") : [];
        var iCount = 0;
        if (aCounts.length < 1) {
            return;
        }
        if (!aRows) {
            aRows = getTableRecordRows(oTable);
        }
        for (var iI = 0; iI < aRows.length; iI += 1) {
            if (isTableRecordCountRow(aRows[iI])) {
                iCount += 1;
            }
        }
        for (var iJ = 0; iJ < aCounts.length; iJ += 1) {
            aCounts[iJ].textContent = iCount;
        }
    }

    function scheduleTableRecordCount(oTable) {
        if (!oTable) {
            return;
        }
        if (!window.setTimeout || !window.clearTimeout) {
            refreshTableRecordCount(oTable);
            return;
        }
        if (oTable._tableRecordCountTimer) {
            window.clearTimeout(oTable._tableRecordCountTimer);
        }
        oTable._tableRecordCountTimer = window.setTimeout(function () {
            oTable._tableRecordCountTimer = null;
            refreshTableRecordCount(oTable);
        }, 0);
    }

    function watchTableRecordCount(oTable) {
        if (!window.MutationObserver || !oTable || oTable._tableRecordCountObserver) {
            return;
        }
        oTable._tableRecordCountObserver = new MutationObserver(function () {
            scheduleTableRecordCount(oTable);
        });
        oTable._tableRecordCountObserver.observe(oTable, {
            "attributes": true,
            "attributeFilter": ["class", "hidden", "style"],
            "childList": true,
            "subtree": true
        });
    }

    function refreshTableRecordCounts() {
        var aCounts = document.querySelectorAll(".js-table-record-count[data-table-count]");
        var oTable;
        for (var iI = 0; iI < aCounts.length; iI += 1) {
            oTable = document.getElementById(aCounts[iI].getAttribute("data-table-count") || "");
            if (oTable) {
                refreshTableRecordCount(oTable);
                watchTableRecordCount(oTable);
            } else {
                aCounts[iI].textContent = 0;
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
                if ((" " + aRows[iJ].className + " ").indexOf(" quick-filter-static-row ") !== -1) {
                    if (aRows[iJ].style.display != "") {
                        aRows[iJ].style.display = "";
                    }
                    continue;
                }
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
            refreshTableRecordCount(oTable, aRows);
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
        for (var iR = 0; iR < aResetButtons.length; iR += 1) {
            aResetButtons[iR].addEventListener("click", function () {
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
            refreshTableRecordCount(document.getElementById(oFilter.getAttribute("data-table-filter")));
        });
        refreshTableRecordCount(document.getElementById(oFilter.getAttribute("data-table-filter")));
        focusAdminElement(oFilter, true);
    }

    for (var iI = 0; iI < aFilters.length; iI += 1) {
        initializeTableFilter(aFilters[iI]);
    }
    refreshTableRecordCounts();
}

function setupAutoRefresh() {
    var oAutoRefresh = document.querySelector(".js-auto-refresh");
    var iRefreshTimer = null;
    var oAudioContext = null;
    var iLatestId;
    var iRefreshInterval;
    if (!oAutoRefresh || !window.fetch) {
        return;
    }
    iLatestId = parseInt(oAutoRefresh.getAttribute("data-latest-id") || "0", 10);
    iRefreshInterval = parseInt(oAutoRefresh.getAttribute("data-refresh-interval") || "300000", 10);

    function sendAutoRefreshState() {
        var oData;
        if (!window.FormData) {
            return;
        }
        oData = new FormData();
        oData.append("admin_auto_refresh_action", "save");
        oData.append("control_id", oAutoRefresh.id || "auto-refresh");
        oData.append("enabled", oAutoRefresh.checked ? "1" : "0");
        appendAdminCsrfToken(oData);
        fetch(window.location.href, {
            method: "POST",
            credentials: "same-origin",
            headers: getAdminAjaxHeaders(),
            body: oData
        }).catch(function(oException) {
            logAdminException(oException);
        });
    }

    function prepareAudio() {
        var AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext) {
            return Promise.resolve(false);
        }
        if (!oAudioContext || oAudioContext.state == "closed") {
            oAudioContext = new AudioContext();
        }
        if (oAudioContext.state == "suspended") {
            return oAudioContext.resume().then(function() {
                return oAudioContext.state == "running";
            }).catch(function(oException) {
                logAdminException(oException);
                return false;
            });
        }
        return Promise.resolve(oAudioContext.state == "running");
    }

    function playChime() {
        return prepareAudio().then(function(blAudioReady) {
            var oGain;
            var oFirstOscillator;
            var oSecondOscillator;
            var iNow;
            if (!blAudioReady) {
                return;
            }
            iNow = oAudioContext.currentTime;
            oGain = oAudioContext.createGain();
            oGain.gain.setValueAtTime(0.0001, iNow);
            oGain.gain.exponentialRampToValueAtTime(0.18, iNow + 0.02);
            oGain.gain.exponentialRampToValueAtTime(0.0001, iNow + 0.7);
            oGain.connect(oAudioContext.destination);
            oFirstOscillator = oAudioContext.createOscillator();
            oFirstOscillator.frequency.setValueAtTime(880, iNow);
            oFirstOscillator.connect(oGain);
            oFirstOscillator.start(iNow);
            oFirstOscillator.stop(iNow + 0.25);
            oSecondOscillator = oAudioContext.createOscillator();
            oSecondOscillator.frequency.setValueAtTime(1174.66, iNow + 0.2);
            oSecondOscillator.connect(oGain);
            oSecondOscillator.start(iNow + 0.2);
            oSecondOscillator.stop(iNow + 0.65);
        });
    }

    function scheduleRefreshCheck() {
        if (iRefreshTimer !== null) {
            window.clearTimeout(iRefreshTimer);
            iRefreshTimer = null;
        }
        if (oAutoRefresh.checked) {
            iRefreshTimer = window.setTimeout(checkForNewRecords, iRefreshInterval);
        }
    }

    function checkForNewRecords() {
        fetch(window.location.href, {
            method: "GET",
            credentials: "same-origin",
            cache: "no-store"
        }).then(function(oResponse) {
            return oResponse.text();
        }).then(function(sHtml) {
            var oDocument = new DOMParser().parseFromString(sHtml, "text/html");
            var oNewAutoRefresh = oDocument.querySelector(".js-auto-refresh");
            var iNewLatestId = oNewAutoRefresh ? parseInt(oNewAutoRefresh.getAttribute("data-latest-id") || "0", 10) : iLatestId;
            if (iNewLatestId > iLatestId) {
                playChime().then(function() {
                    window.setTimeout(function() {
                        window.location.reload();
                    }, 700);
                });
                return;
            }
            scheduleRefreshCheck();
        }).catch(function(oException) {
            logAdminException(oException);
            scheduleRefreshCheck();
        });
    }

    oAutoRefresh.addEventListener("change", function() {
        sendAutoRefreshState();
        if (oAutoRefresh.checked) {
            prepareAudio();
        }
        scheduleRefreshCheck();
    });

    function prepareStoredAutoRefreshAudio() {
        if (oAutoRefresh.checked) {
            prepareAudio();
        }
    }

    document.addEventListener("mousedown", prepareStoredAutoRefreshAudio);
    document.addEventListener("keydown", prepareStoredAutoRefreshAudio);
    document.addEventListener("touchstart", prepareStoredAutoRefreshAudio);
    scheduleRefreshCheck();
}

function closeAdminDialog() {
    var oDialog = document.getElementById("admin-reusable-dialog");
    if (!oDialog) {
        return;
    }
    if (!oDialog.hidden) {
        oDialog.hidden = true;
        unlockAdminModalScroll();
    } else {
        oDialog.hidden = true;
    }
    oDialog.innerHTML = "";
}

function createAdminInput(sId, sValue, blRequired) {
    var oInput = document.createElement("input");
    oInput.type = "text";
    oInput.id = sId;
    oInput.value = sValue || "";
    oInput.autocomplete = "off";
    oInput.spellcheck = false;
    if (blRequired) {
        oInput.required = true;
    }
    return oInput;
}

function appendMenuDialogField(oGrid, sLabel, oInput) {
    var oLabel = document.createElement("label");
    oLabel.htmlFor = oInput.id;
    oLabel.textContent = sLabel;
    oGrid.appendChild(oLabel);
    oGrid.appendChild(oInput);
}

function appendMenuDialogCheck(oGrid, sLabel, oInput) {
    var oWrap = document.createElement("label");
    oWrap.className = "checkbox-label";
    oWrap.appendChild(oInput);
    oWrap.appendChild(document.createTextNode(sLabel));
    oGrid.appendChild(oWrap);
}

function getMenuRowData(oRow) {
    if (!oRow) {
        return null;
    }
    return {
        id: parseInt(oRow.getAttribute("data-menu-id") || "0", 10),
        path: oRow.getAttribute("data-menu-path") || "",
        icon: oRow.getAttribute("data-menu-icon") || "",
        name: oRow.getAttribute("data-menu-name") || "",
        title: oRow.getAttribute("data-menu-title") || "",
        target: oRow.getAttribute("data-menu-target") || "",
        active: oRow.getAttribute("data-menu-active") == "1",
        separator: oRow.getAttribute("data-menu-separator") == "1"
    };
}

function replaceMenuTables(aData) {
    var oTables = document.getElementById("menu-admin-tables");
    var aRows = {};
    var aOldRows;
    var aNewRows;
    var sMenuId;
    var i;
    if (oTables && aData.tables_html) {
        aOldRows = oTables.querySelectorAll("tr[data-menu-id]");
        for (i = 0; i < aOldRows.length; i++) {
            sMenuId = aOldRows[i].getAttribute("data-menu-id") || "";
            if (sMenuId) {
                aRows[sMenuId] = aOldRows[i];
            }
        }
        oTables.innerHTML = aData.tables_html;
        if (window.copyAdminTableRowState && window.bindAdminTableRow) {
            aNewRows = oTables.querySelectorAll("tr[data-menu-id]");
            for (i = 0; i < aNewRows.length; i++) {
                sMenuId = aNewRows[i].getAttribute("data-menu-id") || "";
                if (sMenuId && aRows[sMenuId]) {
                    window.copyAdminTableRowState(aRows[sMenuId], aNewRows[i]);
                }
                window.bindAdminTableRow(aNewRows[i]);
            }
        }
        refreshAdminTableFilter();
    }
}

function findMenuRowById(iMenuId) {
    return document.querySelector("tr[data-menu-id=\"" + String(iMenuId) + "\"]");
}

function openMenuItemDialog(aRow, oSourceRow) {
    var oDialog = document.getElementById("admin-reusable-dialog");
    var oForm;
    var oBox;
    var oHeader;
    var oTitle;
    var oClose;
    var oGrid;
    var oPath;
    var oIcon;
    var oName;
    var oMenuTitle;
    var oTarget;
    var oActive;
    var oSeparator;
    var oError;
    var oActions;
    var oSave;
    var oCancel;
    var iMenuId = aRow ? aRow.id : 0;
    var blSaved = false;

    if (!oDialog) {
        return;
    }

    oForm = document.createElement("form");
    oForm.className = "confirm-dialog-box subject-edit-dialog";
    oBox = oForm;

    oHeader = document.createElement("div");
    oHeader.className = "confirm-dialog-header";
    oTitle = document.createElement("strong");
    oTitle.className = "confirm-dialog-title";
    oTitle.textContent = iMenuId > 0 ? "Edit Menu Item" : "New Menu Item";
    oClose = document.createElement("button");
    oClose.type = "button";
    oClose.className = "confirm-dialog-close";
    oClose.setAttribute("aria-label", "Close");
    oClose.innerHTML = "&times;";
    oHeader.appendChild(oTitle);
    oHeader.appendChild(oClose);
    oBox.appendChild(oHeader);

    oGrid = oBox;
    oPath = createAdminInput("menu-dialog-path", aRow ? aRow.path : "", true);
    oIcon = createAdminInput("menu-dialog-icon", aRow ? aRow.icon : "", false);
    oName = createAdminInput("menu-dialog-name", aRow ? aRow.name : "", false);
    oMenuTitle = createAdminInput("menu-dialog-title", aRow ? aRow.title : "", false);
    oTarget = createAdminInput("menu-dialog-target", aRow ? aRow.target : "", false);
    oActive = document.createElement("input");
    oActive.type = "checkbox";
    oActive.checked = aRow ? aRow.active : true;
    oSeparator = document.createElement("input");
    oSeparator.type = "checkbox";
    oSeparator.checked = aRow ? aRow.separator : false;

    appendMenuDialogField(oGrid, "Path", oPath);
    appendMenuDialogField(oGrid, "Icon", oIcon);
    appendMenuDialogField(oGrid, "Name", oName);
    appendMenuDialogField(oGrid, "Title", oMenuTitle);
    appendMenuDialogField(oGrid, "Target", oTarget);
    appendMenuDialogCheck(oGrid, "Active", oActive);
    appendMenuDialogCheck(oGrid, "Separator", oSeparator);

    oError = document.createElement("div");
    oError.className = "subject-edit-error";
    oError.style.display = "none";
    oBox.appendChild(oError);

    oActions = document.createElement("div");
    oActions.className = "confirm-dialog-actions";
    oSave = document.createElement("button");
    oSave.type = "submit";
    oSave.className = "confirm-dialog-button";
    oSave.textContent = "Save";
    oCancel = document.createElement("button");
    oCancel.type = "button";
    oCancel.className = "confirm-dialog-button";
    oCancel.textContent = "Cancel";
    oActions.appendChild(oSave);
    oActions.appendChild(oCancel);
    oBox.appendChild(oActions);

    function refreshSeparatorFields() {
        var blDisabled = oSeparator.checked;
        oIcon.disabled = blDisabled;
        oName.disabled = blDisabled;
        oMenuTitle.disabled = blDisabled;
        oTarget.disabled = blDisabled;
    }

    function closeMenuDialog() {
        finishAdminSubjectRowEdit(oSourceRow, blSaved);
        closeAdminDialog();
    }

    beginAdminSubjectRowEdit(oSourceRow);
    oClose.addEventListener("click", closeMenuDialog);
    oCancel.addEventListener("click", closeMenuDialog);
    oSeparator.addEventListener("change", refreshSeparatorFields);
    oForm.addEventListener("submit", function(oEvent) {
        var oData;
        var iSavedMenuId;
        var oSavedRow;
        oEvent.preventDefault();
        oError.style.display = "none";
        oError.textContent = "";
        oData = new FormData();
        oData.append("action", iMenuId > 0 ? "update_menu_item" : "create_menu_item");
        if (iMenuId > 0) {
            oData.append("menu_id", String(iMenuId));
        }
        appendAdminEncodedValue(oData, "path", oPath.value);
        appendAdminEncodedValue(oData, "icon", oIcon.value);
        appendAdminEncodedValue(oData, "name", oName.value);
        appendAdminEncodedValue(oData, "title", oMenuTitle.value);
        appendAdminEncodedValue(oData, "target", oTarget.value);
        oData.append("is_active", oActive.checked ? "1" : "0");
        oData.append("is_separator", oSeparator.checked ? "1" : "0");
        submitAdminRequest(oData, function(aData) {
            iSavedMenuId = aData && aData.menu_id ? parseInt(aData.menu_id, 10) : iMenuId;
            replaceMenuTables(aData);
            oSavedRow = iSavedMenuId ? findMenuRowById(iSavedMenuId) : null;
            finishAdminSubjectRowEdit(oSavedRow || oSourceRow, true);
            blSaved = true;
            closeAdminDialog();
        }, function(sMessage) {
            oError.textContent = sMessage;
            oError.style.display = "";
        });
    });

    oDialog.innerHTML = "";
    oDialog.appendChild(oForm);
    enableAdminDialogDrag(oDialog, oForm, oHeader);
    if (oDialog.hidden) {
        lockAdminModalScroll();
    }
    oDialog.hidden = false;
    refreshSeparatorFields();
    window.setTimeout(function() {
        oPath.focus();
        oPath.select();
    }, 0);
}

function openAdminConfirmDialog(sTitle, sMessage, sConfirmText, fConfirm, fCancel, sCancelText) {
    var oDialog = document.getElementById("admin-reusable-dialog");
    var oBox;
    var oHeader;
    var oTitle;
    var oClose;
    var oMessage;
    var oActions;
    var oConfirm;
    var oCancel;
    if (!oDialog) {
        return;
    }
    function closeConfirmDialog() {
        closeAdminDialog();
        if (typeof fCancel == "function") {
            fCancel();
        }
    }
    oBox = document.createElement("div");
    oBox.className = "confirm-dialog-box";
    oHeader = document.createElement("div");
    oHeader.className = "confirm-dialog-header";
    oTitle = document.createElement("strong");
    oTitle.className = "confirm-dialog-title";
    oTitle.textContent = sTitle;
    oClose = document.createElement("button");
    oClose.type = "button";
    oClose.className = "confirm-dialog-close";
    oClose.setAttribute("aria-label", "Close");
    oClose.innerHTML = "&times;";
    oHeader.appendChild(oTitle);
    oHeader.appendChild(oClose);
    oBox.appendChild(oHeader);
    oMessage = document.createElement("p");
    if (sMessage && sMessage.nodeType) {
        oMessage.appendChild(sMessage);
    } else {
        oMessage.textContent = sMessage;
    }
    oBox.appendChild(oMessage);
    oActions = document.createElement("div");
    oActions.className = "confirm-dialog-actions";
    oConfirm = document.createElement("button");
    oConfirm.type = "button";
    oConfirm.className = "confirm-dialog-button";
    oConfirm.textContent = sConfirmText;
    oCancel = document.createElement("button");
    oCancel.type = "button";
    oCancel.className = "confirm-dialog-button";
    oCancel.textContent = sCancelText ? sCancelText : "Cancel";
    oActions.appendChild(oConfirm);
    oActions.appendChild(oCancel);
    oBox.appendChild(oActions);
    oClose.addEventListener("click", closeConfirmDialog);
    oCancel.addEventListener("click", closeConfirmDialog);
    oConfirm.addEventListener("click", function() {
        closeAdminDialog();
        if (typeof fConfirm == "function") {
            fConfirm();
        }
    });
    oDialog.innerHTML = "";
    oDialog.appendChild(oBox);
    enableAdminDialogDrag(oDialog, oBox, oHeader);
    if (oDialog.hidden) {
        lockAdminModalScroll();
    }
    oDialog.hidden = false;
    window.setTimeout(function() {
        oConfirm.focus();
    }, 0);
}

function createIssueDeleteMessage(sTitle) {
    var oFragment = document.createDocumentFragment();
    var oStrong = document.createElement("strong");
    oStrong.textContent = sTitle;
    oFragment.appendChild(document.createTextNode("Delete "));
    oFragment.appendChild(oStrong);
    oFragment.appendChild(document.createTextNode("?"));
    return oFragment;
}

function createMenuDeleteMessage(sPath) {
    var oFragment = document.createDocumentFragment();
    var oStrong = document.createElement("strong");
    oStrong.textContent = sPath;
    oFragment.appendChild(document.createTextNode("Delete "));
    oFragment.appendChild(oStrong);
    oFragment.appendChild(document.createTextNode("?"));
    return oFragment;
}

function copyAdminTextWithInput(sText) {
    var oInput = document.createElement("textarea");
    var blSuccess = false;
    oInput.value = sText;
    oInput.setAttribute("readonly", "readonly");
    oInput.style.position = "fixed";
    oInput.style.top = "-1000px";
    document.body.appendChild(oInput);
    oInput.focus();
    oInput.select();
    try {
        blSuccess = document.execCommand("copy");
    } catch (oException) {
        logAdminException(oException);
        blSuccess = false;
    }
    document.body.removeChild(oInput);
    return blSuccess;
}

function bindAdminCopyLinks() {
    var aButtons = document.querySelectorAll(".js-copy-link");
    var i;

    function showCopyResult(oButton, blSuccess) {
        var sText = oButton.getAttribute("data-copy-text") || oButton.textContent;
        oButton.textContent = blSuccess ? "Copied" : "Copy failed";
        window.setTimeout(function() {
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
            navigator.clipboard.writeText(sLink).then(function() {
                showCopyResult(oButton, true);
            }).catch(function(oException) {
                logAdminException(oException);
                showCopyResult(oButton, copyAdminTextWithInput(sLink));
            });
            return;
        }
        showCopyResult(oButton, copyAdminTextWithInput(sLink));
    }

    for (i = 0; i < aButtons.length; i++) {
        aButtons[i].addEventListener("click", function() {
            copyLink(this);
        });
    }
}

function bindAdminContactCopy() {
    function showContactCopyResult(oButton, blSuccess) {
        var oBox = oButton.querySelector ? oButton.querySelector(".copy-action-box") : null;
        var sText = oButton.getAttribute("data-copy-text") || (oBox ? oBox.textContent : oButton.textContent);
        var sResultText = blSuccess ? getAdminEmoji("copy-success") : getAdminEmoji("copy-failure");
        if (oBox) {
            oBox.textContent = sResultText;
        } else {
            oButton.textContent = sResultText;
        }
        window.setTimeout(function() {
            if (oBox) {
                oBox.textContent = sText;
            } else {
                oButton.textContent = sText;
            }
        }, 1000);
    }

    function copyContactValue(oButton) {
        var oItem = oButton.closest ? oButton.closest(".contact-item") : null;
        var sValue = getAdminContactValue(oItem);
        oButton.setAttribute("data-copy-text", oButton.getAttribute("data-copy-text") || oButton.textContent);
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(sValue).then(function() {
                showContactCopyResult(oButton, true);
            }).catch(function(oException) {
                logAdminException(oException);
                showContactCopyResult(oButton, copyAdminTextWithInput(sValue));
            });
            return;
        }
        showContactCopyResult(oButton, copyAdminTextWithInput(sValue));
    }

    document.addEventListener("click", function(oEvent) {
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
    });
}

function bindMenuAdmin() {
    var oAdd = document.querySelector(".js-add-menu-item");
    var oTables = document.getElementById("menu-admin-tables");

    if (oAdd) {
        oAdd.addEventListener("click", function() {
            openMenuItemDialog(null);
        });
    }

    if (!oTables) {
        return;
    }

    oTables.addEventListener("click", function(oEvent) {
        var oButton = oEvent.target.closest(".js-edit-menu-item, .js-delete-menu-item, .js-move-menu-up, .js-move-menu-down");
        var oRow = oButton ? oButton.closest("tr[data-menu-id]") : null;
        var aRow = getMenuRowData(oRow);
        var oData;
        if (!oButton || !aRow) {
            return;
        }
        oEvent.preventDefault();
        if (oButton.classList.contains("js-edit-menu-item")) {
            openMenuItemDialog(aRow, oRow);
        } else if (oButton.classList.contains("js-delete-menu-item")) {
            beginAdminSubjectRowEdit(oRow);
            openAdminConfirmDialog("Confirm Deletion", createMenuDeleteMessage(aRow.path), "Yes", function() {
                oData = new FormData();
                oData.append("action", "delete_menu_item");
                oData.append("menu_id", String(aRow.id));
                submitAdminRequest(oData, function(aData) {
                    replaceMenuTables(aData);
                }, function(sMessage) {
                    finishAdminSubjectRowEdit(oRow, false);
                    alert(sMessage);
                });
            }, function() {
                finishAdminSubjectRowEdit(oRow, false);
            }, "No");
        } else if (oButton.classList.contains("js-move-menu-up") || oButton.classList.contains("js-move-menu-down")) {
            oData = new FormData();
            oData.append("action", "move_menu_item");
            oData.append("menu_id", String(aRow.id));
            oData.append("direction", oButton.classList.contains("js-move-menu-up") ? "up" : "down");
            beginAdminSubjectRowEdit(oRow);
            submitAdminRequest(oData, function(aData) {
                var iSavedMenuId = aData && aData.menu_id ? parseInt(aData.menu_id, 10) : aRow.id;
                var oSavedRow;
                replaceMenuTables(aData);
                oSavedRow = iSavedMenuId ? findMenuRowById(iSavedMenuId) : null;
                finishAdminSubjectRowEdit(oSavedRow || oRow, true);
            }, function(sMessage) {
                finishAdminSubjectRowEdit(oRow, false);
                alert(sMessage);
            });
        }
    });
}

function appendIssueDialogField(oGrid, sLabel, oInput) {
    var oLabel = document.createElement("label");
    oLabel.htmlFor = oInput.id;
    oLabel.textContent = sLabel;
    oGrid.appendChild(oLabel);
    oGrid.appendChild(oInput);
}

function createIssueSelect(sId, sValue, aOptions) {
    var oSelect = document.createElement("select");
    var oOption;
    var i;
    oSelect.id = sId;
    for (i = 0; i < aOptions.length; i++) {
        oOption = document.createElement("option");
        oOption.value = aOptions[i].value;
        oOption.textContent = aOptions[i].label;
        if (aOptions[i].value == sValue) {
            oOption.selected = true;
        }
        oSelect.appendChild(oOption);
    }
    return oSelect;
}

function createIssueDescriptionTextarea(sId, sValue) {
    var oTextarea = document.createElement("textarea");
    oTextarea.id = sId;
    oTextarea.value = sValue || "";
    oTextarea.className = "issue-description-textarea";
    oTextarea.rows = 8;
    return oTextarea;
}

function padIssueDateNumber(iValue) {
    return iValue < 10 ? "0" + iValue : "" + iValue;
}

function formatIssueLocalDate(oDate) {
    return oDate.getFullYear() + "-" + padIssueDateNumber(oDate.getMonth() + 1) + "-" + padIssueDateNumber(oDate.getDate());
}

function formatIssueLocalDateTime(oDate) {
    return formatIssueLocalDate(oDate) + " " + padIssueDateNumber(oDate.getHours()) + ":" + padIssueDateNumber(oDate.getMinutes());
}

function parseIssueDateValue(sValue) {
    var sText = String(sValue || "").replace(/\u00a0/g, " ").replace(/([0-9])[Tt]([0-9])/g, "$1 $2").replace(/^\s+|\s+$/g, "");
    var aParts;
    var iYear;
    var iMonth;
    var iDay;
    var iHour = 0;
    var iMinute = 0;
    var iSecond = 0;
    var blHasTime = false;
    var blHasSeconds = false;
    var oDate;
    if (sText == "") {
        return null;
    }
    aParts = sText.match(/^([0-9]{4})[-.\/ ]([0-9]{1,2})[-.\/ ]([0-9]{1,2})(?:[ ]+([0-9]{1,2}):([0-9]{1,2})(?::([0-9]{1,2}))?)?$/);
    if (aParts) {
        iYear = parseInt(aParts[1], 10);
        iMonth = parseInt(aParts[2], 10);
        iDay = parseInt(aParts[3], 10);
        blHasTime = typeof aParts[4] != "undefined";
        if (blHasTime) {
            iHour = parseInt(aParts[4], 10);
            iMinute = parseInt(aParts[5], 10);
            iSecond = typeof aParts[6] != "undefined" ? parseInt(aParts[6], 10) : 0;
            blHasSeconds = typeof aParts[6] != "undefined";
        }
    } else {
        aParts = sText.match(/^([0-9]{1,2})[-.\/ ]([0-9]{1,2})[-.\/ ]([0-9]{4})(?:[ ]+([0-9]{1,2}):([0-9]{1,2})(?::([0-9]{1,2}))?)?$/);
        if (!aParts) {
            return null;
        }
        iYear = parseInt(aParts[3], 10);
        iMonth = parseInt(aParts[2], 10);
        iDay = parseInt(aParts[1], 10);
        blHasTime = typeof aParts[4] != "undefined";
        if (blHasTime) {
            iHour = parseInt(aParts[4], 10);
            iMinute = parseInt(aParts[5], 10);
            iSecond = typeof aParts[6] != "undefined" ? parseInt(aParts[6], 10) : 0;
            blHasSeconds = typeof aParts[6] != "undefined";
        }
    }
    if (!isFinite(iYear) || !isFinite(iMonth) || !isFinite(iDay) || !isFinite(iHour) || !isFinite(iMinute) || !isFinite(iSecond) || iYear < 1 || iYear > 9999 || iMonth < 1 || iMonth > 12 || iDay < 1 || iDay > 31 || iHour < 0 || iHour > 23 || iMinute < 0 || iMinute > 59 || iSecond < 0 || iSecond > 59) {
        return null;
    }
    oDate = new Date(0);
    oDate.setFullYear(iYear, iMonth - 1, iDay);
    oDate.setHours(iHour, iMinute, iSecond, 0);
    if (oDate.getFullYear() !== iYear || oDate.getMonth() !== iMonth - 1 || oDate.getDate() !== iDay || oDate.getHours() !== iHour || oDate.getMinutes() !== iMinute || oDate.getSeconds() !== iSecond) {
        return null;
    }
    oDate._issueHasTime = blHasTime;
    oDate._issueHasSeconds = blHasSeconds;
    return oDate;
}

function formatIssueParsedDateValue(oDate, blDateTime) {
    var sValue = formatIssueLocalDate(oDate);
    if (!blDateTime || !oDate._issueHasTime) {
        return sValue;
    }
    sValue += " " + padIssueDateNumber(oDate.getHours()) + ":" + padIssueDateNumber(oDate.getMinutes());
    if (oDate._issueHasSeconds || oDate.getSeconds() != 0) {
        sValue += ":" + padIssueDateNumber(oDate.getSeconds());
    }
    return sValue;
}

function normalizeIssueDateInput(oInput) {
    var oDate;
    if (!oInput || oInput.value.replace(/^\s+|\s+$/g, "") == "") {
        return;
    }
    oDate = parseIssueDateValue(oInput.value);
    if (oDate) {
        oInput.value = formatIssueParsedDateValue(oDate, oInput.getAttribute("data-date-input-kind") == "datetime");
    }
}

function renderIssueDateCalendar(oInput, oCalendar, oMonthDate) {
    var aDayLabels = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
    var iCalendarFirstDay = 1;
    var oSelectedDate = parseIssueDateValue(oInput.value);
    var iYear = oMonthDate.getFullYear();
    var iMonth = oMonthDate.getMonth();
    var iFirstDay = new Date(iYear, iMonth, 1).getDay();
    var iOffset = (iFirstDay - iCalendarFirstDay + 7) % 7;
    var iDays = new Date(iYear, iMonth + 1, 0).getDate();
    var oHeader = document.createElement("div");
    var oPrev = document.createElement("button");
    var oNext = document.createElement("button");
    var oTitle = document.createElement("span");
    var oGrid = document.createElement("div");
    var i;
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
    oTitle.textContent = iYear + "-" + padIssueDateNumber(iMonth + 1);
    oGrid.className = "subject-date-calendar-grid";
    oPrev.addEventListener("click", function() {
        renderIssueDateCalendar(oInput, oCalendar, new Date(iYear, iMonth - 1, 1));
        positionIssueDateCalendar(oInput, oCalendar);
    });
    oNext.addEventListener("click", function() {
        renderIssueDateCalendar(oInput, oCalendar, new Date(iYear, iMonth + 1, 1));
        positionIssueDateCalendar(oInput, oCalendar);
    });
    oHeader.appendChild(oPrev);
    oHeader.appendChild(oTitle);
    oHeader.appendChild(oNext);
    for (i = 0; i < 7; i++) {
        oDayLabel = document.createElement("div");
        oDayLabel.className = "subject-date-calendar-day";
        oDayLabel.textContent = aDayLabels[(iCalendarFirstDay + i) % 7];
        oGrid.appendChild(oDayLabel);
    }
    for (i = 0; i < iOffset; i++) {
        oEmpty = document.createElement("span");
        oEmpty.className = "subject-date-calendar-empty";
        oGrid.appendChild(oEmpty);
    }
    for (i = 1; i <= iDays; i++) {
        oDate = new Date(iYear, iMonth, i);
        sDate = formatIssueLocalDate(oDate);
        oDateButton = document.createElement("button");
        oDateButton.type = "button";
        oDateButton.className = "subject-date-calendar-date" + (oSelectedDate && formatIssueLocalDate(oSelectedDate) == sDate ? " subject-date-calendar-selected" : "");
        oDateButton.setAttribute("data-date", sDate);
        oDateButton.textContent = "" + i;
        oDateButton.addEventListener("click", function() {
            setIssueDateInputDateValue(oInput, this.getAttribute("data-date") || "");
            oCalendar.style.display = "none";
        });
        oGrid.appendChild(oDateButton);
    }
    oCalendar.appendChild(oHeader);
    oCalendar.appendChild(oGrid);
}

function positionIssueDateCalendar(oInput, oCalendar) {
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

function setIssueDateInputDateValue(oInput, sDate) {
    var oDate = parseIssueDateValue(oInput.value);
    var sValue = sDate;
    if (oInput.getAttribute("data-date-input-kind") == "datetime" && oDate && oDate._issueHasTime) {
        sValue += " " + padIssueDateNumber(oDate.getHours()) + ":" + padIssueDateNumber(oDate.getMinutes());
        if (oDate._issueHasSeconds || oDate.getSeconds() != 0) {
            sValue += ":" + padIssueDateNumber(oDate.getSeconds());
        }
    }
    oInput.value = sValue;
}

function showIssueDateCalendar(oInput, oCalendar) {
    var oDate = parseIssueDateValue(oInput.value) || oCalendar._currentDate || new Date();
    renderIssueDateCalendar(oInput, oCalendar, new Date(oDate.getFullYear(), oDate.getMonth(), 1));
    if (!oCalendar.parentNode) {
        document.body.appendChild(oCalendar);
    }
    oCalendar.style.display = "";
    positionIssueDateCalendar(oInput, oCalendar);
}

function removeIssueDateCalendars() {
    var aCalendars = document.querySelectorAll(".subject-date-calendar");
    var i;
    for (i = 0; i < aCalendars.length; i++) {
        if (aCalendars[i].parentNode) {
            aCalendars[i].parentNode.removeChild(aCalendars[i]);
        }
    }
}

function appendIssueDateField(oParent, sLabel, sName, sValue, blDateTime) {
    var oLabel = document.createElement("label");
    var oWrapper = document.createElement("div");
    var oInput = document.createElement("input");
    var oButton = document.createElement("button");
    var oCalendar = document.createElement("div");
    oLabel.textContent = sLabel;
    oWrapper.className = "subject-date-field";
    oInput.type = "text";
    oInput.id = sName;
    oInput.name = sName;
    oInput.value = sValue || "";
    oInput.placeholder = blDateTime ? "YYYY-MM-DD HH:mm" : "YYYY-MM-DD";
    oInput.maxLength = 19;
    oInput.autocomplete = "off";
    oInput.setAttribute("data-date-input-kind", blDateTime ? "datetime" : "date");
    oInput.title = blDateTime ? "Use YYYY-MM-DD HH:mm." : "Use YYYY-MM-DD.";
    oButton.type = "button";
    oButton.className = "subject-date-button";
    oButton.setAttribute("aria-label", "Open calendar");
    oButton.textContent = "\u25BE";
    oCalendar.className = "subject-date-calendar";
    oCalendar.style.display = "none";
    oCalendar.addEventListener("mousedown", function(oEvent) {
        oEvent.preventDefault();
    });
    oButton.addEventListener("click", function(oEvent) {
        oEvent.preventDefault();
        if (oCalendar.style.display == "none") {
            showIssueDateCalendar(oInput, oCalendar);
        } else {
            oCalendar.style.display = "none";
        }
    });
    oInput.addEventListener("focus", function() {
        showIssueDateCalendar(oInput, oCalendar);
    });
    oInput.addEventListener("input", function() {
        var oDate = parseIssueDateValue(oInput.value);
        if (oDate && oCalendar.style.display != "none") {
            renderIssueDateCalendar(oInput, oCalendar, new Date(oDate.getFullYear(), oDate.getMonth(), 1));
            positionIssueDateCalendar(oInput, oCalendar);
        }
    });
    oInput.addEventListener("keydown", function(oEvent) {
        if (oEvent.key == "Escape") {
            oCalendar.style.display = "none";
        }
    });
    oWrapper.addEventListener("focusout", function() {
        normalizeIssueDateInput(oInput);
        window.setTimeout(function() {
            if (!oWrapper.contains(document.activeElement) && !oCalendar.contains(document.activeElement)) {
                oCalendar.style.display = "none";
            }
        }, 0);
    });
    window.addEventListener("resize", function() {
        if (oCalendar.style.display != "none") {
            positionIssueDateCalendar(oInput, oCalendar);
        }
    });
    oWrapper.appendChild(oInput);
    oWrapper.appendChild(oButton);
    oParent.appendChild(oLabel);
    oParent.appendChild(oWrapper);
    return oInput;
}

function getIssueRowData(oRow) {
    if (!oRow) {
        return null;
    }
    return {
        id: parseInt(oRow.getAttribute("data-issue-id") || "0", 10),
        issueType: oRow.getAttribute("data-issue-type") || "task",
        status: oRow.getAttribute("data-issue-status") || "open",
        priority: oRow.getAttribute("data-issue-priority") || "normal",
        title: oRow.getAttribute("data-issue-title") || "",
        description: oRow.getAttribute("data-issue-description") || "",
        dueDate: oRow.getAttribute("data-issue-due-date") || ""
    };
}

function findIssueRowById(iIssueId) {
    return document.querySelector("tr[data-issue-id=\"" + String(iIssueId) + "\"]");
}

function replaceIssueRows(aData) {
    var oTable = document.getElementById("issues-table");
    var oBody = oTable && oTable.tBodies.length ? oTable.tBodies[0] : null;
    var aRows = {};
    var aOldRows;
    var aNewRows;
    var sIssueId;
    var i;
    if (!aData || typeof aData.issues_html == "undefined") {
        return;
    }
    if (!oBody || aData.issues_html == "") {
        window.location.reload();
        return;
    }
    aOldRows = oBody.querySelectorAll("tr[data-issue-id]");
    for (i = 0; i < aOldRows.length; i++) {
        sIssueId = aOldRows[i].getAttribute("data-issue-id") || "";
        if (sIssueId) {
            aRows[sIssueId] = aOldRows[i];
        }
    }
    oBody.innerHTML = aData.issues_html;
    if (window.copyAdminTableRowState && window.bindAdminTableRow) {
        aNewRows = oBody.querySelectorAll("tr[data-issue-id]");
        for (i = 0; i < aNewRows.length; i++) {
            sIssueId = aNewRows[i].getAttribute("data-issue-id") || "";
            if (sIssueId && aRows[sIssueId]) {
                window.copyAdminTableRowState(aRows[sIssueId], aNewRows[i]);
            }
            window.bindAdminTableRow(aNewRows[i]);
        }
    }
    refreshAdminTableFilter();
}

function openIssueDialog(aRow, oSourceRow) {
    var oDialog = document.getElementById("admin-reusable-dialog");
    var oForm;
    var oBox;
    var oHeader;
    var oTitle;
    var oClose;
    var oType;
    var oIssueTitle;
    var oStatus;
    var oPriority;
    var oDueDate;
    var oDescription;
    var oError;
    var oActions;
    var oSave;
    var oCancel;
    var iIssueId = aRow ? aRow.id : 0;
    var blSaved = false;

    if (!oDialog) {
        return;
    }

    oForm = document.createElement("form");
    oForm.className = "confirm-dialog-box subject-edit-dialog issue-edit-dialog";
    oBox = oForm;

    oHeader = document.createElement("div");
    oHeader.className = "confirm-dialog-header";
    oTitle = document.createElement("strong");
    oTitle.className = "confirm-dialog-title";
    oTitle.textContent = iIssueId > 0 ? "Edit Issue" : "New Issue";
    oClose = document.createElement("button");
    oClose.type = "button";
    oClose.className = "confirm-dialog-close";
    oClose.setAttribute("aria-label", "Close");
    oClose.innerHTML = "&times;";
    oHeader.appendChild(oTitle);
    oHeader.appendChild(oClose);
    oBox.appendChild(oHeader);

    oType = createIssueSelect("issue-dialog-type", aRow ? aRow.issueType : "task", [
        {value: "task", label: "Task"},
        {value: "bug", label: "Bug"}
    ]);
    oIssueTitle = createAdminInput("issue-dialog-title", aRow ? aRow.title : "", true);
    oStatus = createIssueSelect("issue-dialog-status", aRow ? aRow.status : "open", [
        {value: "open", label: "Open"},
        {value: "in_progress", label: "In Progress"},
        {value: "waiting", label: "Waiting"},
        {value: "done", label: "Done"}
    ]);
    oPriority = createIssueSelect("issue-dialog-priority", aRow ? aRow.priority : "normal", [
        {value: "low", label: "Low"},
        {value: "normal", label: "Normal"},
        {value: "high", label: "High"},
        {value: "urgent", label: "Urgent"}
    ]);
    oDescription = createIssueDescriptionTextarea("issue-dialog-description", aRow ? aRow.description : "");

    appendIssueDialogField(oBox, "Type", oType);
    appendIssueDialogField(oBox, "Title", oIssueTitle);
    appendIssueDialogField(oBox, "Status", oStatus);
    appendIssueDialogField(oBox, "Priority", oPriority);
    oDueDate = appendIssueDateField(oBox, "Due", "issue-dialog-due-date", aRow ? aRow.dueDate : formatIssueLocalDateTime(new Date()), true);
    appendIssueDialogField(oBox, "Description", oDescription);

    oError = document.createElement("div");
    oError.className = "subject-edit-error";
    oError.style.display = "none";
    oBox.appendChild(oError);

    oActions = document.createElement("div");
    oActions.className = "confirm-dialog-actions";
    oSave = document.createElement("button");
    oSave.type = "submit";
    oSave.className = "confirm-dialog-button";
    oSave.textContent = "Save";
    oCancel = document.createElement("button");
    oCancel.type = "button";
    oCancel.className = "confirm-dialog-button";
    oCancel.textContent = "Cancel";
    oActions.appendChild(oSave);
    oActions.appendChild(oCancel);
    oBox.appendChild(oActions);

    function closeIssueDialog() {
        removeIssueDateCalendars();
        finishAdminSubjectRowEdit(oSourceRow, blSaved);
        closeAdminDialog();
    }

    beginAdminSubjectRowEdit(oSourceRow);
    oClose.addEventListener("click", closeIssueDialog);
    oCancel.addEventListener("click", closeIssueDialog);
    oForm.addEventListener("submit", function(oEvent) {
        var oData;
        var iSavedIssueId;
        var oSavedRow;
        oEvent.preventDefault();
        normalizeIssueDateInput(oDueDate);
        oError.style.display = "none";
        oError.textContent = "";
        oData = new FormData();
        oData.append("action", iIssueId > 0 ? "update_issue" : "create_issue");
        if (iIssueId > 0) {
            oData.append("issue_id", String(iIssueId));
        }
        oData.append("issue_type", oType.value);
        appendAdminEncodedValue(oData, "title", oIssueTitle.value);
        oData.append("status", oStatus.value);
        oData.append("priority", oPriority.value);
        oData.append("due_date", oDueDate.value);
        appendAdminEncodedValue(oData, "description", oDescription.value);
        submitAdminRequest(oData, function(aData) {
            iSavedIssueId = aData && aData.issue_id ? parseInt(aData.issue_id, 10) : iIssueId;
            replaceIssueRows(aData);
            oSavedRow = iSavedIssueId ? findIssueRowById(iSavedIssueId) : null;
            finishAdminSubjectRowEdit(oSavedRow || oSourceRow, true);
            blSaved = true;
            removeIssueDateCalendars();
            closeAdminDialog();
        }, function(sMessage) {
            oError.textContent = sMessage;
            oError.style.display = "";
        });
    });

    oDialog.innerHTML = "";
    oDialog.appendChild(oForm);
    enableAdminDialogDrag(oDialog, oForm, oHeader);
    if (oDialog.hidden) {
        lockAdminModalScroll();
    }
    oDialog.hidden = false;
    window.setTimeout(function() {
        oIssueTitle.focus();
        oIssueTitle.select();
    }, 0);
}

function bindIssueTracker() {
    var oAdd = document.querySelector(".js-add-issue");
    var oTable = document.getElementById("issues-table");

    if (oAdd) {
        oAdd.addEventListener("click", function() {
            openIssueDialog(null);
        });
    }

    if (!oTable) {
        return;
    }

    oTable.addEventListener("click", function(oEvent) {
        var oButton = oEvent.target.closest(".js-edit-issue, .js-delete-issue, .js-toggle-issue");
        var oRow = oButton ? oButton.closest("tr[data-issue-id]") : null;
        var aRow = getIssueRowData(oRow);
        var oData;
        if (!oButton || !aRow) {
            return;
        }
        oEvent.preventDefault();
        if (oButton.classList.contains("js-edit-issue")) {
            openIssueDialog(aRow, oRow);
        } else if (oButton.classList.contains("js-delete-issue")) {
            beginAdminSubjectRowEdit(oRow);
            openAdminConfirmDialog("Confirm Deletion", createIssueDeleteMessage(aRow.title), "Yes", function() {
                oData = new FormData();
                oData.append("action", "delete_issue");
                oData.append("issue_id", String(aRow.id));
                submitAdminRequest(oData, function(aData) {
                    replaceIssueRows(aData);
                }, function(sMessage) {
                    finishAdminSubjectRowEdit(oRow, false);
                    alert(sMessage);
                });
            }, function() {
                finishAdminSubjectRowEdit(oRow, false);
            }, "No");
        } else if (oButton.classList.contains("js-toggle-issue")) {
            oData = new FormData();
            oData.append("action", "toggle_issue_status");
            oData.append("issue_id", String(aRow.id));
            beginAdminSubjectRowEdit(oRow);
            submitAdminRequest(oData, function(aData) {
                var iSavedIssueId = aData && aData.issue_id ? parseInt(aData.issue_id, 10) : aRow.id;
                var oSavedRow;
                replaceIssueRows(aData);
                oSavedRow = iSavedIssueId ? findIssueRowById(iSavedIssueId) : null;
                finishAdminSubjectRowEdit(oSavedRow || oRow, true);
            }, function(sMessage) {
                finishAdminSubjectRowEdit(oRow, false);
                alert(sMessage);
            });
        }
    });
}

function createPhoneAccountDeleteMessage(aRow) {
    var oFragment = document.createDocumentFragment();
    var oStrong = document.createElement("strong");
    oStrong.textContent = aRow.number;
    oFragment.appendChild(document.createTextNode("Delete "));
    oFragment.appendChild(oStrong);
    oFragment.appendChild(document.createTextNode("?"));
    return oFragment;
}

function createPhoneAccountTextInput(sId, sValue, blRequired, sAutocomplete) {
    var oInput = document.createElement("input");
    oInput.type = "text";
    oInput.id = sId;
    oInput.value = sValue || "";
    if (sAutocomplete) {
        oInput.autocomplete = sAutocomplete;
    }
    if (blRequired) {
        oInput.required = true;
    }
    return oInput;
}

function createPhoneAccountCurrencySelect(sId, sValue) {
    var aCurrencies = getPhoneAccountCurrencyOptions(sValue);
    var oSelect = document.createElement("select");
    var oOption;
    var sCurrency = sValue || getPhoneAccountDefaultPaidCurrency();
    var i;
    oSelect.id = sId;
    oSelect.required = true;
    for (i = 0; i < aCurrencies.length; i++) {
        oOption = document.createElement("option");
        oOption.value = aCurrencies[i].currency || "";
        oOption.textContent = aCurrencies[i].label || aCurrencies[i].currency || "";
        if (String(oOption.value) == String(sCurrency)) {
            oOption.selected = true;
        }
        oSelect.appendChild(oOption);
    }
    return oSelect;
}

function createPhoneAccountNoteTextarea(sId, sValue) {
    var oTextarea = document.createElement("textarea");
    oTextarea.id = sId;
    oTextarea.value = sValue || "";
    oTextarea.className = "phone-account-note-textarea";
    oTextarea.rows = 8;
    return oTextarea;
}

function appendPhoneAccountFieldContainer(oParent) {
    var oField = document.createElement("div");
    oField.className = "phone-account-field";
    oParent.appendChild(oField);
    return oField;
}

function appendPhoneAccountField(oParent, sLabel, oInput, sClassName) {
    var oField = appendPhoneAccountFieldContainer(oParent);
    if (sClassName) {
        oField.className += " " + sClassName;
    }
    appendIssueDialogField(oField, sLabel, oInput);
}

function appendPhoneAccountDateField(oParent, sLabel, sName, sValue, blDateTime) {
    return appendIssueDateField(appendPhoneAccountFieldContainer(oParent), sLabel, sName, sValue, blDateTime);
}

function getPhoneAccountsData() {
    return document.getElementById("phone-accounts-data");
}

function getPhoneAccountDefaultPaidAmount() {
    var oData = getPhoneAccountsData();
    return oData ? (oData.getAttribute("data-default-paid-amount") || "") : "";
}

function getPhoneAccountDefaultPaidCurrency() {
    var oData = getPhoneAccountsData();
    return oData ? (oData.getAttribute("data-default-paid-currency") || "USD") : "USD";
}

function getPhoneAccountCurrencyOptions(sSelectedValue) {
    var oData = getPhoneAccountsData();
    var aCurrencies = [];
    var blSelectedFound = false;
    var sCurrency = sSelectedValue || getPhoneAccountDefaultPaidCurrency();
    var i;
    if (oData) {
        try {
            aCurrencies = JSON.parse(oData.getAttribute("data-currencies") || "[]");
        } catch (oException) {
            logAdminException(oException);
            aCurrencies = [];
        }
    }
    for (i = 0; i < aCurrencies.length; i++) {
        if (String(aCurrencies[i].currency || "") == String(sCurrency)) {
            blSelectedFound = true;
        }
    }
    if (sCurrency && !blSelectedFound) {
        aCurrencies.push({
            "currency": sCurrency,
            "label": sCurrency
        });
    }
    return aCurrencies;
}

function setPhoneAccountDefaults(aData) {
    var oData = getPhoneAccountsData();
    var aDefaults = aData && aData.phone_account_defaults ? aData.phone_account_defaults : null;
    if (!oData || !aDefaults) {
        return;
    }
    oData.setAttribute("data-default-paid-amount", aDefaults.paid_amount || "");
    oData.setAttribute("data-default-paid-currency", aDefaults.paid_currency || "USD");
}

function getPhoneAccountRowData(oRow) {
    if (!oRow) {
        return null;
    }
    return {
        id: parseInt(oRow.getAttribute("data-phone-account-id") || "0", 10),
        number: oRow.getAttribute("data-phone-account-number") || "",
        account: oRow.getAttribute("data-phone-account-account") || "",
        pin: oRow.getAttribute("data-phone-account-pin") || "",
        puk: oRow.getAttribute("data-phone-account-puk") || "",
        puk2: oRow.getAttribute("data-phone-account-puk2") || "",
        simId: oRow.getAttribute("data-phone-account-sim-id") || "",
        imei: oRow.getAttribute("data-phone-account-imei") || "",
        paidAt: oRow.getAttribute("data-phone-account-paid-at") || "",
        paidAmount: oRow.getAttribute("data-phone-account-paid-amount") || "",
        paidCurrency: oRow.getAttribute("data-phone-account-paid-currency") || "",
        note: oRow.getAttribute("data-phone-account-note") || ""
    };
}

function findPhoneAccountRowById(iPhoneAccountId) {
    return document.querySelector("tr[data-phone-account-id=\"" + String(iPhoneAccountId) + "\"]");
}

function replacePhoneAccountRows(aData) {
    var oTable = document.getElementById("phone-accounts-table");
    var oBody = oTable && oTable.tBodies.length ? oTable.tBodies[0] : null;
    var aRows = {};
    var aOldRows;
    var aNewRows;
    var sPhoneAccountId;
    var i;
    if (!aData || typeof aData.phone_accounts_html == "undefined") {
        return;
    }
    setPhoneAccountDefaults(aData);
    if (!oBody || aData.phone_accounts_html == "") {
        window.location.reload();
        return;
    }
    aOldRows = oBody.querySelectorAll("tr[data-phone-account-id]");
    for (i = 0; i < aOldRows.length; i++) {
        sPhoneAccountId = aOldRows[i].getAttribute("data-phone-account-id") || "";
        if (sPhoneAccountId) {
            aRows[sPhoneAccountId] = aOldRows[i];
        }
    }
    oBody.innerHTML = aData.phone_accounts_html;
    if (window.copyAdminTableRowState && window.bindAdminTableRow) {
        aNewRows = oBody.querySelectorAll("tr[data-phone-account-id]");
        for (i = 0; i < aNewRows.length; i++) {
            sPhoneAccountId = aNewRows[i].getAttribute("data-phone-account-id") || "";
            if (sPhoneAccountId && aRows[sPhoneAccountId]) {
                window.copyAdminTableRowState(aRows[sPhoneAccountId], aNewRows[i]);
            }
            window.bindAdminTableRow(aNewRows[i]);
        }
    }
    refreshAdminTableFilter();
}

function openPhoneAccountDialog(aRow, oSourceRow) {
    var oDialog = document.getElementById("admin-reusable-dialog");
    var oForm;
    var oBox;
    var oHeader;
    var oTitle;
    var oClose;
    var oFields;
    var oNumber;
    var oAccount;
    var oPin;
    var oPuk;
    var oPuk2;
    var oSimId;
    var oImei;
    var oPaidAt;
    var oPaidAmount;
    var oPaidCurrency;
    var oNote;
    var oError;
    var oActions;
    var oSave;
    var oCancel;
    var closeOnEscape;
    var iPhoneAccountId = aRow ? aRow.id : 0;
    var blSaved = false;

    if (!oDialog) {
        return;
    }

    oForm = document.createElement("form");
    oForm.className = "confirm-dialog-box subject-edit-dialog phone-account-edit-dialog";
    oBox = oForm;

    oHeader = document.createElement("div");
    oHeader.className = "confirm-dialog-header";
    oTitle = document.createElement("strong");
    oTitle.className = "confirm-dialog-title";
    oTitle.textContent = iPhoneAccountId > 0 ? "Edit Phone Account" : "New Phone Account";
    oClose = document.createElement("button");
    oClose.type = "button";
    oClose.className = "confirm-dialog-close";
    oClose.setAttribute("aria-label", "Close");
    oClose.innerHTML = "&times;";
    oHeader.appendChild(oTitle);
    oHeader.appendChild(oClose);
    oBox.appendChild(oHeader);

    oFields = document.createElement("div");
    oFields.className = "phone-account-field-grid";
    oBox.appendChild(oFields);

    oNumber = createPhoneAccountTextInput("phone-account-dialog-number", aRow ? aRow.number : "", true, "tel");
    oAccount = createPhoneAccountTextInput("phone-account-dialog-account", aRow ? aRow.account : "", false, "");
    oPin = createPhoneAccountTextInput("phone-account-dialog-pin", aRow ? aRow.pin : "", false, "");
    oPuk = createPhoneAccountTextInput("phone-account-dialog-puk", aRow ? aRow.puk : "", false, "");
    oPuk2 = createPhoneAccountTextInput("phone-account-dialog-puk2", aRow ? aRow.puk2 : "", false, "");
    oSimId = createPhoneAccountTextInput("phone-account-dialog-sim-id", aRow ? aRow.simId : "", false, "");
    oImei = createPhoneAccountTextInput("phone-account-dialog-imei", aRow ? aRow.imei : "", false, "");
    oPaidAmount = createPhoneAccountTextInput("phone-account-dialog-paid-amount", aRow ? aRow.paidAmount : getPhoneAccountDefaultPaidAmount(), false, "");
    oPaidCurrency = createPhoneAccountCurrencySelect("phone-account-dialog-paid-currency", aRow ? aRow.paidCurrency : getPhoneAccountDefaultPaidCurrency());
    oNote = createPhoneAccountNoteTextarea("phone-account-dialog-note", aRow ? aRow.note : "");
    oPin.maxLength = 16;
    oPuk.maxLength = 16;
    oPuk2.maxLength = 16;
    oSimId.maxLength = 64;
    oImei.maxLength = 32;
    oPaidAmount.maxLength = 24;
    oPaidAmount.inputMode = "decimal";

    appendPhoneAccountField(oFields, "Number", oNumber);
    appendPhoneAccountField(oFields, "Telegram Account", oAccount);
    appendPhoneAccountField(oFields, "PIN", oPin);
    appendPhoneAccountField(oFields, "PUK", oPuk);
    appendPhoneAccountField(oFields, "PUK2", oPuk2);
    appendPhoneAccountField(oFields, "SIM ID", oSimId);
    appendPhoneAccountField(oFields, "IMEI", oImei);
    oPaidAt = appendPhoneAccountDateField(oFields, "Paid", "phone-account-dialog-paid-at", aRow ? aRow.paidAt : "", true);
    appendPhoneAccountField(oFields, "Amount", oPaidAmount);
    appendPhoneAccountField(oFields, "Currency", oPaidCurrency);
    appendPhoneAccountField(oFields, "Note", oNote, "phone-account-field-wide");

    oError = document.createElement("div");
    oError.className = "subject-edit-error";
    oError.style.display = "none";
    oBox.appendChild(oError);

    oActions = document.createElement("div");
    oActions.className = "confirm-dialog-actions";
    oSave = document.createElement("button");
    oSave.type = "submit";
    oSave.className = "confirm-dialog-button";
    oSave.textContent = "Save";
    oCancel = document.createElement("button");
    oCancel.type = "button";
    oCancel.className = "confirm-dialog-button";
    oCancel.textContent = "Cancel";
    oActions.appendChild(oSave);
    oActions.appendChild(oCancel);
    oBox.appendChild(oActions);

    function closePhoneAccountDialog() {
        document.removeEventListener("keydown", closeOnEscape);
        removeIssueDateCalendars();
        finishAdminSubjectRowEdit(oSourceRow, blSaved);
        closeAdminDialog();
    }

    closeOnEscape = function(oEvent) {
        if (oEvent.key == "Escape") {
            oEvent.preventDefault();
            closePhoneAccountDialog();
        }
    };

    beginAdminSubjectRowEdit(oSourceRow);
    document.addEventListener("keydown", closeOnEscape);
    oClose.addEventListener("click", closePhoneAccountDialog);
    oCancel.addEventListener("click", closePhoneAccountDialog);
    oForm.addEventListener("submit", function(oEvent) {
        var oData;
        var iSavedPhoneAccountId;
        var oSavedRow;
        oEvent.preventDefault();
        normalizeIssueDateInput(oPaidAt);
        oError.style.display = "none";
        oError.textContent = "";
        oData = new FormData();
        oData.append("action", iPhoneAccountId > 0 ? "update_phone_account" : "create_phone_account");
        if (iPhoneAccountId > 0) {
            oData.append("phone_account_id", String(iPhoneAccountId));
        }
        appendAdminEncodedValue(oData, "number", oNumber.value);
        appendAdminEncodedValue(oData, "account", oAccount.value);
        appendAdminEncodedValue(oData, "pin", oPin.value);
        appendAdminEncodedValue(oData, "puk", oPuk.value);
        appendAdminEncodedValue(oData, "puk2", oPuk2.value);
        appendAdminEncodedValue(oData, "sim_id", oSimId.value);
        appendAdminEncodedValue(oData, "imei", oImei.value);
        oData.append("paid_at", oPaidAt.value);
        appendAdminEncodedValue(oData, "paid_amount", oPaidAmount.value);
        oData.append("paid_currency", oPaidCurrency.value);
        appendAdminEncodedValue(oData, "note", oNote.value);
        submitAdminRequest(oData, function(aData) {
            iSavedPhoneAccountId = aData && aData.phone_account_id ? parseInt(aData.phone_account_id, 10) : iPhoneAccountId;
            replacePhoneAccountRows(aData);
            oSavedRow = iSavedPhoneAccountId ? findPhoneAccountRowById(iSavedPhoneAccountId) : null;
            finishAdminSubjectRowEdit(oSavedRow || oSourceRow, true);
            blSaved = true;
            removeIssueDateCalendars();
            document.removeEventListener("keydown", closeOnEscape);
            closeAdminDialog();
        }, function(sMessage) {
            oError.textContent = sMessage;
            oError.style.display = "";
        });
    });

    oDialog.innerHTML = "";
    oDialog.appendChild(oForm);
    enableAdminDialogDrag(oDialog, oForm, oHeader);
    if (oDialog.hidden) {
        lockAdminModalScroll();
    }
    oDialog.hidden = false;
    window.setTimeout(function() {
        oNumber.focus();
        oNumber.select();
    }, 0);
}

function bindPhoneAccounts() {
    var oAdd = document.querySelector(".js-add-phone-account");
    var oTable = document.getElementById("phone-accounts-table");

    if (oAdd) {
        oAdd.addEventListener("click", function() {
            openPhoneAccountDialog(null);
        });
    }

    if (!oTable) {
        return;
    }

    oTable.addEventListener("click", function(oEvent) {
        var oButton = oEvent.target.closest(".js-edit-phone-account, .js-delete-phone-account, .js-move-phone-account-up, .js-move-phone-account-down");
        var oRow = oButton ? oButton.closest("tr[data-phone-account-id]") : null;
        var aRow = getPhoneAccountRowData(oRow);
        var oData;
        if (!oButton || !aRow) {
            return;
        }
        oEvent.preventDefault();
        if (oButton.classList.contains("js-edit-phone-account")) {
            openPhoneAccountDialog(aRow, oRow);
        } else if (oButton.classList.contains("js-delete-phone-account")) {
            beginAdminSubjectRowEdit(oRow);
            openAdminConfirmDialog("Confirm Deletion", createPhoneAccountDeleteMessage(aRow), "Yes", function() {
                oData = new FormData();
                oData.append("action", "delete_phone_account");
                oData.append("phone_account_id", String(aRow.id));
                submitAdminRequest(oData, function(aData) {
                    replacePhoneAccountRows(aData);
                }, function(sMessage) {
                    finishAdminSubjectRowEdit(oRow, false);
                    openAdminConfirmDialog("Request Failed", sMessage, "OK", null, null, "Close");
                });
            }, function() {
                finishAdminSubjectRowEdit(oRow, false);
            }, "No");
        } else if (oButton.classList.contains("js-move-phone-account-up") || oButton.classList.contains("js-move-phone-account-down")) {
            oData = new FormData();
            oData.append("action", "move_phone_account");
            oData.append("phone_account_id", String(aRow.id));
            oData.append("direction", oButton.classList.contains("js-move-phone-account-up") ? "up" : "down");
            beginAdminSubjectRowEdit(oRow);
            submitAdminRequest(oData, function(aData) {
                var iSavedPhoneAccountId = aData && aData.phone_account_id ? parseInt(aData.phone_account_id, 10) : aRow.id;
                var oSavedRow;
                replacePhoneAccountRows(aData);
                oSavedRow = iSavedPhoneAccountId ? findPhoneAccountRowById(iSavedPhoneAccountId) : null;
                finishAdminSubjectRowEdit(oSavedRow || oRow, true);
            }, function(sMessage) {
                finishAdminSubjectRowEdit(oRow, false);
                openAdminConfirmDialog("Request Failed", sMessage, "OK", null, null, "Close");
            });
        }
    });
}

function createEmailUsersTextarea(sId, sValue) {
    var oTextarea = document.createElement("textarea");
    oTextarea.id = sId;
    oTextarea.value = sValue || "";
    oTextarea.className = "email-users-textarea";
    oTextarea.required = true;
    oTextarea.rows = 8;
    return oTextarea;
}

function normalizeEmailAccountType(sValue) {
    sValue = String(sValue || "");
    if (sValue == "mailbox" || sValue == "alias" || sValue == "forwarder") {
        return sValue;
    }
    return "mailbox";
}

function getEmailAccountTypeFromPage() {
    var oMeta = document.querySelector("meta[name=\"email-account-type\"]");
    return normalizeEmailAccountType(oMeta ? oMeta.getAttribute("content") : "");
}

function setEmailAccountTypeInPage(sValue) {
    var oMeta = document.querySelector("meta[name=\"email-account-type\"]");
    sValue = normalizeEmailAccountType(sValue);
    if (oMeta) {
        oMeta.setAttribute("content", sValue);
    }
}

function saveEmailAccountTypeToSession(sValue) {
    var oData;
    sValue = normalizeEmailAccountType(sValue);
    setEmailAccountTypeInPage(sValue);
    if (!window.fetch || typeof FormData == "undefined") {
        return;
    }
    oData = new FormData();
    oData.append("action", "set_email_account_type");
    oData.append("account_type", sValue);
    submitAdminRequest(oData, null, function(sMessage) {
        logAdminException(new Error(sMessage));
    });
}

function createEmailAccountTypeSelect(sId, sValue) {
    var oSelect = document.createElement("select");
    var aOptions = [
        {value: "mailbox", label: "Mailbox"},
        {value: "alias", label: "Alias"},
        {value: "forwarder", label: "Forwarder"}
    ];
    var oOption;
    var i;
    oSelect.id = sId;
    for (i = 0; i < aOptions.length; i++) {
        oOption = document.createElement("option");
        oOption.value = aOptions[i].value;
        oOption.textContent = aOptions[i].label;
        if (aOptions[i].value == sValue) {
            oOption.selected = true;
        }
        oSelect.appendChild(oOption);
    }
    return oSelect;
}

function replaceEmailOverviewTable(aData) {
    var oTarget = document.getElementById("email-overview-table") || document.getElementById("email-overview-empty");
    if (!oTarget || !aData || typeof aData.table_html == "undefined") {
        return;
    }
    oTarget.outerHTML = aData.table_html;
    refreshAdminTableFilter();
}

function openEmailDomainDialog() {
    var oDialog = document.getElementById("admin-reusable-dialog");
    var oForm;
    var oBox;
    var oHeader;
    var oTitle;
    var oClose;
    var oDomain;
    var oAccountType;
    var oUsers;
    var oError;
    var oActions;
    var oSave;
    var oCancel;

    if (!oDialog) {
        return;
    }

    oForm = document.createElement("form");
    oForm.className = "confirm-dialog-box subject-edit-dialog email-domain-edit-dialog";
    oBox = oForm;

    oHeader = document.createElement("div");
    oHeader.className = "confirm-dialog-header";
    oTitle = document.createElement("strong");
    oTitle.className = "confirm-dialog-title";
    oTitle.textContent = "New E-mail Domain";
    oClose = document.createElement("button");
    oClose.type = "button";
    oClose.className = "confirm-dialog-close";
    oClose.setAttribute("aria-label", "Close");
    oClose.innerHTML = "&times;";
    oHeader.appendChild(oTitle);
    oHeader.appendChild(oClose);
    oBox.appendChild(oHeader);

    oDomain = createAdminInput("email-dialog-domain", "", true);
    oDomain.name = "domain";
    oDomain.autocomplete = "on";
    oAccountType = createEmailAccountTypeSelect("email-dialog-account-type", getEmailAccountTypeFromPage());
    oUsers = createEmailUsersTextarea("email-dialog-users", "");

    appendMenuDialogField(oBox, "Domain", oDomain);
    appendMenuDialogField(oBox, "Type", oAccountType);
    appendMenuDialogField(oBox, "Users", oUsers);

    oError = document.createElement("div");
    oError.className = "subject-edit-error";
    oError.style.display = "none";
    oBox.appendChild(oError);

    oActions = document.createElement("div");
    oActions.className = "confirm-dialog-actions";
    oSave = document.createElement("button");
    oSave.type = "submit";
    oSave.className = "confirm-dialog-button";
    oSave.textContent = "Save";
    oCancel = document.createElement("button");
    oCancel.type = "button";
    oCancel.className = "confirm-dialog-button";
    oCancel.textContent = "Cancel";
    oActions.appendChild(oSave);
    oActions.appendChild(oCancel);
    oBox.appendChild(oActions);

    function closeEmailDomainDialog() {
        closeAdminDialog();
    }

    oClose.addEventListener("click", closeEmailDomainDialog);
    oCancel.addEventListener("click", closeEmailDomainDialog);
    oAccountType.addEventListener("change", function() {
        saveEmailAccountTypeToSession(oAccountType.value);
    });
    oForm.addEventListener("submit", function(oEvent) {
        var oData;
        oEvent.preventDefault();
        oError.style.display = "none";
        oError.textContent = "";
        setEmailAccountTypeInPage(oAccountType.value);
        oData = new FormData();
        oData.append("action", "create_email_domain");
        appendAdminEncodedValue(oData, "domain", oDomain.value);
        oData.append("account_type", oAccountType.value);
        appendAdminEncodedValue(oData, "users", oUsers.value);
        submitAdminRequest(oData, function(aData) {
            replaceEmailOverviewTable(aData);
            closeAdminDialog();
        }, function(sMessage) {
            oError.textContent = sMessage;
            oError.style.display = "";
        });
    });

    oDialog.innerHTML = "";
    oDialog.appendChild(oForm);
    enableAdminDialogDrag(oDialog, oForm, oHeader);
    if (oDialog.hidden) {
        lockAdminModalScroll();
    }
    oDialog.hidden = false;
    window.setTimeout(function() {
        oDomain.focus();
        oDomain.select();
    }, 0);
}

function bindEmailOverview() {
    var oAdd = document.querySelector(".js-add-email-domain");

    if (oAdd) {
        oAdd.addEventListener("click", function() {
            openEmailDomainDialog();
        });
    }
}

function getDashboardServiceRowData(oRow) {
    if (!oRow) {
        return null;
    }
    return {
        id: parseInt(oRow.getAttribute("data-dashboard-service-id") || "0", 10),
        name: oRow.getAttribute("data-dashboard-service-name") || "",
        url: oRow.getAttribute("data-dashboard-service-url") || "",
        checkType: oRow.getAttribute("data-dashboard-service-check-type") || "auto",
        httpCode: oRow.getAttribute("data-dashboard-service-http-code") || "200",
        matchType: oRow.getAttribute("data-dashboard-service-match-type") || "contains",
        matchText: oRow.getAttribute("data-dashboard-service-match-text") || "",
        active: oRow.getAttribute("data-dashboard-service-active") == "1",
        checkedAtTs: parseInt(oRow.getAttribute("data-dashboard-service-checked-at-ts") || "0", 10),
        updatedAtTs: parseInt(oRow.getAttribute("data-dashboard-service-updated-at-ts") || "0", 10),
        ok: oRow.getAttribute("data-dashboard-service-ok")
    };
}

function findDashboardServiceRowById(iServiceId) {
    return document.querySelector("tr[data-dashboard-service-id=\"" + String(iServiceId) + "\"]");
}

function formatDateTimeWithNbspIndent(sText) {
    var sValue = String(sText || "").replace(/^\s+|\s+$/g, "");
    var aMatches;
    if (!sValue) {
        return "";
    }
    aMatches = sValue.match(/^([0-9]{4}-[0-9]{2}-[0-9]{2})[T ]([0-9]{2}:[0-9]{2}(?::[0-9]{2})?)(?:\.[0-9]+)?(?:Z|[+-][0-9]{2}:?[0-9]{2})?$/);
    if (aMatches) {
        return aMatches[1] + new Array(10).join("\u00a0") + aMatches[2];
    }
    return sValue;
}

function setDashboardServiceText(oElement, sText) {
    if (oElement) {
        oElement.textContent = sText || "";
    }
}

function updateDashboardServiceCheckCells(oRow, sState, aData) {
    var oStatus = oRow ? oRow.querySelector(".js-dashboard-service-status") : null;
    var oHttp = oRow ? oRow.querySelector(".js-dashboard-service-http") : null;
    var oChecked = oRow ? oRow.querySelector(".js-dashboard-service-checked") : null;
    var oDetail = oRow ? oRow.querySelector(".js-dashboard-service-detail") : null;
    var sStatusText = "Waiting";
    if (!oRow) {
        return;
    }
    if (sState == "checking") {
        sStatusText = "Checking";
    } else if (sState == "ok") {
        sStatusText = "OK";
    } else if (sState == "failed") {
        sStatusText = "Failed";
    } else if (sState == "error") {
        sStatusText = "Error";
    } else if (sState == "inactive") {
        sStatusText = "Inactive";
    }
    if (oStatus) {
        oStatus.className = "dashboard-status js-dashboard-service-status dashboard-status-" + sState;
        oStatus.textContent = sStatusText;
    }
    setDashboardServiceText(oHttp, aData && aData.http_status ? aData.http_status : "");
    setDashboardServiceText(oChecked, aData && aData.checked_at ? formatDateTimeWithNbspIndent(aData.checked_at) : "");
    setDashboardServiceText(oDetail, aData && aData.message ? aData.message : "");
    if (aData && typeof aData.checked_at_ts != "undefined") {
        oRow.setAttribute("data-dashboard-service-checked-at-ts", String(aData.checked_at_ts || "0"));
    }
    if (aData && typeof aData.ok != "undefined") {
        oRow.setAttribute("data-dashboard-service-ok", aData.ok === null ? "" : (aData.ok ? "1" : "0"));
    }
}

function getDashboardServiceCheckUrl(iServiceId, blForce) {
    var sUrl = window.location.href.split("#")[0].split("?")[0];
    return sUrl + "?action=check_dashboard_service&service_id=" + encodeURIComponent(String(iServiceId)) + (blForce ? "&force=1" : "");
}

var iDashboardServiceCycleInterval = 60 * 60 * 1000;
var iDashboardServiceRowDelay = 2 * 60 * 1000;
var iDashboardServicePendingInterval = 2 * 60 * 1000;
var iDashboardServiceTimer = null;
var iDashboardServiceRunId = 0;

function isDashboardServiceCheckDue(aRow) {
    var iAge;
    if (!aRow || !aRow.active) {
        return false;
    }
    if (!aRow.checkedAtTs || aRow.checkedAtTs < 1) {
        return true;
    }
    if (aRow.updatedAtTs && aRow.updatedAtTs > aRow.checkedAtTs) {
        return true;
    }
    iAge = (new Date()).getTime() - (aRow.checkedAtTs * 1000);
    if (aRow.ok == "") {
        return iAge >= iDashboardServicePendingInterval;
    }
    return iAge >= iDashboardServiceCycleInterval;
}

function checkDashboardServiceRow(oRow, blForce) {
    var aRow = getDashboardServiceRowData(oRow);
    if (!aRow || aRow.id < 1) {
        return window.Promise ? window.Promise.resolve() : null;
    }
    if (!aRow.active) {
        updateDashboardServiceCheckCells(oRow, "inactive", {
            message: "Service is inactive."
        });
        return window.Promise ? window.Promise.resolve() : null;
    }
    updateDashboardServiceCheckCells(oRow, "checking", {
        message: "Checking service..."
    });
    if (!window.fetch) {
        updateDashboardServiceCheckCells(oRow, "error", {
            message: "Fetch API is not available."
        });
        return window.Promise ? window.Promise.resolve() : null;
    }
    return window.fetch(getDashboardServiceCheckUrl(aRow.id, blForce === true), {
        method: "GET",
        headers: getAdminAjaxHeaders(),
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
        updateDashboardServiceCheckCells(oRow, aData.state ? aData.state : (aData.ok ? "ok" : "failed"), aData);
    }).catch(function(oError) {
        updateDashboardServiceCheckCells(oRow, "error", {
            message: oError && oError.message ? oError.message : "Request failed."
        });
    });
}

function getDashboardServiceRows(blOnlyDue) {
    var aNodeRows = document.querySelectorAll("#dashboard-services-table tbody tr[data-dashboard-service-id]");
    var aRows = [];
    var aRow;
    var i;
    for (i = 0; i < aNodeRows.length; i++) {
        aRow = getDashboardServiceRowData(aNodeRows[i]);
        if (!blOnlyDue || isDashboardServiceCheckDue(aRow)) {
            aRows.push(aNodeRows[i]);
        }
    }
    return aRows;
}

function clearDashboardServiceTimer() {
    if (iDashboardServiceTimer) {
        window.clearTimeout(iDashboardServiceTimer);
        iDashboardServiceTimer = null;
    }
}

function scheduleDashboardServiceNextCycle(iRunId, iCycleStartedAt) {
    var iElapsed;
    var iDelay;
    if (iRunId != iDashboardServiceRunId) {
        return;
    }
    iElapsed = (new Date()).getTime() - iCycleStartedAt;
    iDelay = Math.max(0, iDashboardServiceCycleInterval - iElapsed);
    iDashboardServiceTimer = window.setTimeout(function() {
        runDashboardServiceCycle();
    }, iDelay);
}

function runDashboardServiceRow(aRows, iIndex, iRunId, iCycleStartedAt) {
    var oResult;
    if (iRunId != iDashboardServiceRunId) {
        return;
    }
    if (iIndex >= aRows.length) {
        scheduleDashboardServiceNextCycle(iRunId, iCycleStartedAt);
        return;
    }
    oResult = checkDashboardServiceRow(aRows[iIndex]);
    function scheduleNextRow() {
        if (iRunId != iDashboardServiceRunId) {
            return;
        }
        iDashboardServiceTimer = window.setTimeout(function() {
            runDashboardServiceRow(aRows, iIndex + 1, iRunId, iCycleStartedAt);
        }, iDashboardServiceRowDelay);
    }
    if (oResult && typeof oResult.then == "function") {
        oResult.then(scheduleNextRow).catch(scheduleNextRow);
    } else {
        scheduleNextRow();
    }
}

function runDashboardServiceCycle() {
    var aRows = getDashboardServiceRows(true);
    var iRunId;
    var iCycleStartedAt = (new Date()).getTime();
    clearDashboardServiceTimer();
    iDashboardServiceRunId += 1;
    iRunId = iDashboardServiceRunId;
    if (!aRows.length) {
        scheduleDashboardServiceNextCycle(iRunId, iCycleStartedAt);
        return;
    }
    runDashboardServiceRow(aRows, 0, iRunId, iCycleStartedAt);
}

function restartDashboardServiceChecks() {
    clearDashboardServiceTimer();
    runDashboardServiceCycle();
}

function replaceDashboardServiceRows(aData) {
    var oTable = document.getElementById("dashboard-services-table");
    var oBody = oTable && oTable.tBodies.length ? oTable.tBodies[0] : null;
    var aRows = {};
    var aOldRows;
    var aNewRows;
    var sServiceId;
    var i;
    if (!aData || typeof aData.services_html == "undefined") {
        return;
    }
    if (!oBody || aData.services_html == "") {
        window.location.reload();
        return;
    }
    aOldRows = oBody.querySelectorAll("tr[data-dashboard-service-id]");
    for (i = 0; i < aOldRows.length; i++) {
        sServiceId = aOldRows[i].getAttribute("data-dashboard-service-id") || "";
        if (sServiceId) {
            aRows[sServiceId] = aOldRows[i];
        }
    }
    oBody.innerHTML = aData.services_html;
    if (window.copyAdminTableRowState && window.bindAdminTableRow) {
        aNewRows = oBody.querySelectorAll("tr[data-dashboard-service-id]");
        for (i = 0; i < aNewRows.length; i++) {
            sServiceId = aNewRows[i].getAttribute("data-dashboard-service-id") || "";
            if (sServiceId && aRows[sServiceId]) {
                window.copyAdminTableRowState(aRows[sServiceId], aNewRows[i]);
            }
            window.bindAdminTableRow(aNewRows[i]);
        }
    }
    refreshAdminTableFilter();
    restartDashboardServiceChecks();
}

function createDashboardServiceDeleteMessage(aRow) {
    var oFragment = document.createDocumentFragment();
    var oStrong = document.createElement("strong");
    oStrong.textContent = aRow.name;
    oFragment.appendChild(document.createTextNode("Delete service?"));
    oFragment.appendChild(document.createElement("br"));
    oFragment.appendChild(oStrong);
    return oFragment;
}

function openDashboardServiceDialog(aRow, oSourceRow) {
    var oDialog = document.getElementById("admin-reusable-dialog");
    var oForm;
    var oBox;
    var oHeader;
    var oTitle;
    var oClose;
    var oName;
    var oUrl;
    var oCheckType;
    var oHttpCode;
    var oMatchType;
    var oMatchText;
    var oActive;
    var oError;
    var oActions;
    var oSave;
    var oCancel;
    var iServiceId = aRow ? aRow.id : 0;
    var blSaved = false;

    if (!oDialog) {
        return;
    }

    oForm = document.createElement("form");
    oForm.className = "confirm-dialog-box subject-edit-dialog dashboard-service-edit-dialog";
    oBox = oForm;

    oHeader = document.createElement("div");
    oHeader.className = "confirm-dialog-header";
    oTitle = document.createElement("strong");
    oTitle.className = "confirm-dialog-title";
    oTitle.textContent = iServiceId > 0 ? "Edit Service" : "New Service";
    oClose = document.createElement("button");
    oClose.type = "button";
    oClose.className = "confirm-dialog-close";
    oClose.setAttribute("aria-label", "Close");
    oClose.innerHTML = "&times;";
    oHeader.appendChild(oTitle);
    oHeader.appendChild(oClose);
    oBox.appendChild(oHeader);

    oName = createAdminInput("dashboard-service-dialog-name", aRow ? aRow.name : "", true);
    oUrl = createAdminInput("dashboard-service-dialog-url", aRow ? aRow.url : "https://", true);
    oCheckType = createIssueSelect("dashboard-service-dialog-check-type", aRow ? aRow.checkType : "auto", [
        {value: "auto", label: "Auto"},
        {value: "http", label: "HTTP"},
        {value: "stream", label: "Stream"},
        {value: "tcp", label: "TCP"}
    ]);
    oHttpCode = createAdminInput("dashboard-service-dialog-http-code", aRow ? aRow.httpCode : "200", true);
    oMatchType = createIssueSelect("dashboard-service-dialog-match-type", aRow ? aRow.matchType : "starts_with", [
        {value: "starts_with", label: "Starts With"},
        {value: "contains", label: "Contains"}
    ]);
    oMatchText = createAdminInput("dashboard-service-dialog-match-text", aRow ? aRow.matchText : "", false);
    oActive = document.createElement("input");
    oActive.type = "checkbox";
    oActive.checked = aRow ? aRow.active : true;

    oHttpCode.type = "number";
    oHttpCode.min = "100";
    oHttpCode.max = "599";
    oHttpCode.step = "1";
    oHttpCode.inputMode = "numeric";

    appendMenuDialogField(oBox, "Name", oName);
    appendMenuDialogField(oBox, "Endpoint", oUrl);
    appendMenuDialogField(oBox, "Check Type", oCheckType);
    appendMenuDialogField(oBox, "HTTP Code", oHttpCode);
    appendMenuDialogField(oBox, "Body Match", oMatchType);
    appendMenuDialogField(oBox, "Match Text", oMatchText);
    appendMenuDialogCheck(oBox, "Active", oActive);

    oError = document.createElement("div");
    oError.className = "subject-edit-error";
    oError.style.display = "none";
    oBox.appendChild(oError);

    oActions = document.createElement("div");
    oActions.className = "confirm-dialog-actions";
    oSave = document.createElement("button");
    oSave.type = "submit";
    oSave.className = "confirm-dialog-button";
    oSave.textContent = "Save";
    oCancel = document.createElement("button");
    oCancel.type = "button";
    oCancel.className = "confirm-dialog-button";
    oCancel.textContent = "Cancel";
    oActions.appendChild(oSave);
    oActions.appendChild(oCancel);
    oBox.appendChild(oActions);

    function closeDashboardServiceDialog() {
        finishAdminSubjectRowEdit(oSourceRow, blSaved);
        closeAdminDialog();
    }

    function getDashboardServiceDialogScheme() {
        var aMatch = String(oUrl.value || "").match(/^([A-Za-z][A-Za-z0-9+.\-]*):/);
        return aMatch ? aMatch[1].toLowerCase() : "";
    }

    function refreshDashboardServiceDialogFields() {
        var sScheme = getDashboardServiceDialogScheme();
        var blNetworkOnly = oCheckType.value == "tcp" || oCheckType.value == "stream";
        if (oCheckType.value == "auto" && sScheme != "" && sScheme != "http" && sScheme != "https") {
            blNetworkOnly = true;
        }
        oHttpCode.disabled = blNetworkOnly;
        oMatchType.disabled = blNetworkOnly;
        oMatchText.disabled = blNetworkOnly;
    }

    beginAdminSubjectRowEdit(oSourceRow);
    oClose.addEventListener("click", closeDashboardServiceDialog);
    oCancel.addEventListener("click", closeDashboardServiceDialog);
    oCheckType.addEventListener("change", refreshDashboardServiceDialogFields);
    oUrl.addEventListener("input", refreshDashboardServiceDialogFields);
    oForm.addEventListener("submit", function(oEvent) {
        var oData;
        var iSavedServiceId;
        var oSavedRow;
        oEvent.preventDefault();
        oError.style.display = "none";
        oError.textContent = "";
        oData = new FormData();
        oData.append("action", iServiceId > 0 ? "update_dashboard_service" : "create_dashboard_service");
        if (iServiceId > 0) {
            oData.append("service_id", String(iServiceId));
        }
        appendAdminEncodedValue(oData, "name", oName.value);
        appendAdminEncodedValue(oData, "url", oUrl.value);
        oData.append("check_type", oCheckType.value);
        oData.append("http_code", oHttpCode.value);
        oData.append("match_type", oMatchType.value);
        appendAdminEncodedValue(oData, "match_text", oMatchText.value);
        if (oActive.checked) {
            oData.append("is_active", "1");
        }
        submitAdminRequest(oData, function(aData) {
            iSavedServiceId = aData && aData.service_id ? parseInt(aData.service_id, 10) : iServiceId;
            replaceDashboardServiceRows(aData);
            oSavedRow = iSavedServiceId ? findDashboardServiceRowById(iSavedServiceId) : null;
            finishAdminSubjectRowEdit(oSavedRow || oSourceRow, true);
            blSaved = true;
            closeAdminDialog();
        }, function(sMessage) {
            oError.textContent = sMessage;
            oError.style.display = "";
        });
    });

    oDialog.innerHTML = "";
    oDialog.appendChild(oForm);
    enableAdminDialogDrag(oDialog, oForm, oHeader);
    if (oDialog.hidden) {
        lockAdminModalScroll();
    }
    oDialog.hidden = false;
    refreshDashboardServiceDialogFields();
    window.setTimeout(function() {
        oName.focus();
        oName.select();
    }, 0);
}

function bindDashboardServices() {
    var oAdd = document.querySelector(".js-add-dashboard-service");
    var oTable = document.getElementById("dashboard-services-table");

    if (oAdd) {
        oAdd.addEventListener("click", function() {
            openDashboardServiceDialog(null);
        });
    }

    if (oTable) {
        oTable.addEventListener("click", function(oEvent) {
            var oButton = oEvent.target.closest(".js-edit-dashboard-service, .js-delete-dashboard-service, .js-check-dashboard-service, .js-move-dashboard-service-up, .js-move-dashboard-service-down");
            var oRow = oButton ? oButton.closest("tr[data-dashboard-service-id]") : null;
            var aRow = getDashboardServiceRowData(oRow);
            var oData;
            if (!oButton || !aRow) {
                return;
            }
            oEvent.preventDefault();
            if (oButton.classList.contains("js-edit-dashboard-service")) {
                openDashboardServiceDialog(aRow, oRow);
            } else if (oButton.classList.contains("js-delete-dashboard-service")) {
                beginAdminSubjectRowEdit(oRow);
                openAdminConfirmDialog("Confirm Deletion", createDashboardServiceDeleteMessage(aRow), "Yes", function() {
                    oData = new FormData();
                    oData.append("action", "delete_dashboard_service");
                    oData.append("service_id", String(aRow.id));
                    submitAdminRequest(oData, function(aData) {
                        replaceDashboardServiceRows(aData);
                    }, function(sMessage) {
                        finishAdminSubjectRowEdit(oRow, false);
                        alert(sMessage);
                    });
                }, function() {
                    finishAdminSubjectRowEdit(oRow, false);
                }, "No");
            } else if (oButton.classList.contains("js-move-dashboard-service-up") || oButton.classList.contains("js-move-dashboard-service-down")) {
                oData = new FormData();
                oData.append("action", "move_dashboard_service");
                oData.append("service_id", String(aRow.id));
                oData.append("direction", oButton.classList.contains("js-move-dashboard-service-up") ? "up" : "down");
                beginAdminSubjectRowEdit(oRow);
                submitAdminRequest(oData, function(aData) {
                    var iSavedServiceId = aData && aData.service_id ? parseInt(aData.service_id, 10) : aRow.id;
                    var oSavedRow;
                    replaceDashboardServiceRows(aData);
                    oSavedRow = iSavedServiceId ? findDashboardServiceRowById(iSavedServiceId) : null;
                    finishAdminSubjectRowEdit(oSavedRow || oRow, true);
                }, function(sMessage) {
                    finishAdminSubjectRowEdit(oRow, false);
                    alert(sMessage);
                });
            } else if (oButton.classList.contains("js-check-dashboard-service")) {
                checkDashboardServiceRow(oRow, true);
            }
        });
        restartDashboardServiceChecks();
    }
}

var aBusinessHoursDays = [
    {key: "mon", label: "Mon"},
    {key: "tue", label: "Tue"},
    {key: "wed", label: "Wed"},
    {key: "thu", label: "Thu"},
    {key: "fri", label: "Fri"},
    {key: "sat", label: "Sat"},
    {key: "sun", label: "Sun"}
];

function isBusinessHoursPmdLike() {
    return document.body && document.body.getAttribute("data-pmd-like") == "1";
}

function getBusinessHoursColumnCount(iViewportWidth) {
    var iGap = 5;
    var iMinCardWidth = 320;
    var iColumns;
    if (isBusinessHoursPmdLike()) {
        return 1;
    }
    iColumns = Math.floor((iViewportWidth + iGap) / (iMinCardWidth + iGap));
    if (iColumns < 1) {
        return 1;
    }
    return iColumns;
}

function getBusinessHoursCardWidth(iViewportWidth) {
    if (iViewportWidth < 320) {
        return iViewportWidth;
    }
    return 320;
}

function layoutBusinessHours() {
    var oGrid = document.getElementById("business-hours-cards");
    var aCards = oGrid ? oGrid.querySelectorAll("section[data-business-hours-id]") : [];
    var oVisualViewport = window.visualViewport || null;
    var iViewportWidth = oVisualViewport ? Math.floor(oVisualViewport.width) : (window.innerWidth || document.documentElement.clientWidth || 1024);
    var iViewportHeight = oVisualViewport ? Math.floor(oVisualViewport.height) : (window.innerHeight || document.documentElement.clientHeight || 768);
    var iGridWidth = oGrid ? Math.floor(oGrid.getBoundingClientRect().width) : iViewportWidth;
    var blPmdLike = isBusinessHoursPmdLike();
    var iColumns = getBusinessHoursColumnCount(iGridWidth);
    var iCardWidth = getBusinessHoursCardWidth(iGridWidth);
    var iTop = oGrid ? oGrid.getBoundingClientRect().top - (oVisualViewport ? oVisualViewport.offsetTop : 0) : 0;
    var iAvailableHeight = Math.max(220, Math.floor(iViewportHeight - iTop - 6));
    var i;
    if (!oGrid) {
        return;
    }
    oGrid.style.height = "";
    oGrid.style.maxHeight = iAvailableHeight + "px";
    oGrid.style.gridTemplateColumns = "repeat(" + iColumns + ", " + iCardWidth + "px)";
    oGrid.style.gridAutoRows = "auto";
    oGrid.setAttribute("data-columns", String(iColumns));
    oGrid.removeAttribute("data-rows");
    oGrid.removeAttribute("data-card-height");
    for (i = 0; i < aCards.length; i++) {
        aCards[i].style.height = "";
    }
}

function getBusinessHoursDefaultHours() {
    var aHours = {};
    var sKey;
    var i;
    for (i = 0; i < aBusinessHoursDays.length; i++) {
        sKey = aBusinessHoursDays[i].key;
        aHours[sKey] = {
            closed: sKey == "sat" || sKey == "sun",
            open: sKey == "sat" || sKey == "sun" ? "" : "08:00",
            breakStart: "",
            breakEnd: "",
            close: sKey == "sat" || sKey == "sun" ? "" : "17:00"
        };
    }
    return aHours;
}

function normalizeBusinessHoursData(aSource) {
    var aHours = getBusinessHoursDefaultHours();
    var sKey;
    var aDay;
    var i;
    if (!aSource) {
        return aHours;
    }
    for (i = 0; i < aBusinessHoursDays.length; i++) {
        sKey = aBusinessHoursDays[i].key;
        aDay = aSource[sKey] || {};
        aHours[sKey] = {
            closed: aDay.closed == 1 || aDay.closed === true,
            open: aDay.open || "",
            breakStart: aDay.break_start || aDay.breakStart || "",
            breakEnd: aDay.break_end || aDay.breakEnd || "",
            close: aDay.close || ""
        };
    }
    return aHours;
}

function parseBusinessHoursData(sHours) {
    var aData = null;
    try {
        aData = JSON.parse(sHours || "{}");
    } catch (oException) {
        logAdminException(oException);
        aData = null;
    }
    return normalizeBusinessHoursData(aData);
}

function getBusinessHoursCardData(oCard) {
    if (!oCard) {
        return null;
    }
    return {
        id: parseInt(oCard.getAttribute("data-business-hours-id") || "0", 10),
        subjectId: parseInt(oCard.getAttribute("data-business-hours-subject-id") || "0", 10),
        addressId: parseInt(oCard.getAttribute("data-business-hours-address-id") || "0", 10),
        subjectName: oCard.getAttribute("data-business-hours-subject-name") || "",
        addressText: oCard.getAttribute("data-business-hours-address-text") || "",
        hours: parseBusinessHoursData(oCard.getAttribute("data-business-hours-hours") || ""),
        icon: oCard.getAttribute("data-business-hours-icon") || "",
        active: oCard.getAttribute("data-business-hours-active") == "1"
    };
}

function findBusinessHoursCardById(iId) {
    return document.querySelector("section[data-business-hours-id=\"" + String(iId) + "\"]");
}

function copyBusinessHoursCardState(oSourceCard, oTargetCard) {
    if (!oSourceCard || !oTargetCard) {
        return;
    }
    if ((" " + oSourceCard.className + " ").indexOf(" admin-row-modal ") !== -1) {
        addAdminClass(oTargetCard, "admin-row-modal");
    }
    if ((" " + oSourceCard.className + " ").indexOf(" business-hours-card-active ") !== -1) {
        addAdminClass(oTargetCard, "business-hours-card-active");
    }
}

function activateBusinessHoursCard(iId) {
    var aCards = document.querySelectorAll("section[data-business-hours-id]");
    var aTabs = document.querySelectorAll("[data-business-hours-tab-id]");
    var sId = String(iId || "");
    var blFound = false;
    var blTabFound = false;
    var i;
    if (sId == "" && aTabs.length) {
        sId = aTabs[0].getAttribute("data-business-hours-tab-id") || "";
    }
    if (sId != "" && aTabs.length) {
        for (i = 0; i < aTabs.length; i++) {
            if (aTabs[i].getAttribute("data-business-hours-tab-id") == sId) {
                blTabFound = true;
                break;
            }
        }
        if (!blTabFound) {
            sId = aTabs[0].getAttribute("data-business-hours-tab-id") || "";
        }
    } else if (sId == "" && aCards.length && !isBusinessHoursPmdLike()) {
        sId = aCards[0].getAttribute("data-business-hours-id") || "";
    }
    for (i = 0; i < aCards.length; i++) {
        if (aCards[i].getAttribute("data-business-hours-id") == sId) {
            addAdminClass(aCards[i], "business-hours-card-active");
            blFound = true;
        } else {
            removeAdminClass(aCards[i], "business-hours-card-active");
        }
    }
    if (!blFound && aCards.length && !isBusinessHoursPmdLike()) {
        sId = aCards[0].getAttribute("data-business-hours-id") || "";
        addAdminClass(aCards[0], "business-hours-card-active");
    }
    for (i = 0; i < aTabs.length; i++) {
        if (aTabs[i].getAttribute("data-business-hours-tab-id") == sId) {
            addAdminClass(aTabs[i], "business-hours-tab-active");
            aTabs[i].setAttribute("aria-selected", "true");
        } else {
            removeAdminClass(aTabs[i], "business-hours-tab-active");
            aTabs[i].setAttribute("aria-selected", "false");
        }
    }
}

function bindBusinessHoursTabs() {
    var oTabs = document.getElementById("business-hours-tabs");
    if (!oTabs || oTabs.getAttribute("data-business-hours-tabs-bound") == "1") {
        return;
    }
    oTabs.setAttribute("data-business-hours-tabs-bound", "1");
    oTabs.addEventListener("click", function(oEvent) {
        var oButton = oEvent.target.closest("[data-business-hours-tab-id]");
        if (!oButton) {
            return;
        }
        oEvent.preventDefault();
        activateBusinessHoursCard(parseInt(oButton.getAttribute("data-business-hours-tab-id") || "0", 10));
    });
}

function replaceBusinessHoursCards(aData) {
    var oGrid = document.getElementById("business-hours-cards");
    var oTabs = document.getElementById("business-hours-tabs");
    var aCards = {};
    var aOldCards;
    var aNewCards;
    var sId;
    var iSavedId = aData && aData.business_hours_id ? parseInt(aData.business_hours_id, 10) : 0;
    var i;
    if (!aData || typeof aData.cards_html == "undefined" || !oGrid) {
        return;
    }
    aOldCards = oGrid.querySelectorAll("section[data-business-hours-id]");
    for (i = 0; i < aOldCards.length; i++) {
        sId = aOldCards[i].getAttribute("data-business-hours-id") || "";
        if (sId) {
            aCards[sId] = aOldCards[i];
        }
    }
    oGrid.innerHTML = aData.cards_html;
    if (oTabs && typeof aData.tabs_html != "undefined") {
        oTabs.innerHTML = aData.tabs_html;
    }
    aNewCards = oGrid.querySelectorAll("section[data-business-hours-id]");
    for (i = 0; i < aNewCards.length; i++) {
        sId = aNewCards[i].getAttribute("data-business-hours-id") || "";
        if (sId && aCards[sId]) {
            copyBusinessHoursCardState(aCards[sId], aNewCards[i]);
        }
    }
    activateBusinessHoursCard(iSavedId);
    layoutBusinessHours();
}

function createBusinessHoursDeleteMessage(aRow) {
    var oFragment = document.createDocumentFragment();
    var oStrong = document.createElement("strong");
    oStrong.textContent = aRow.subjectName;
    oFragment.appendChild(document.createTextNode("Delete business hours?"));
    oFragment.appendChild(document.createElement("br"));
    oFragment.appendChild(oStrong);
    if (aRow.addressText != "") {
        oFragment.appendChild(document.createElement("br"));
        oFragment.appendChild(document.createTextNode(aRow.addressText));
    }
    return oFragment;
}

function appendBusinessHoursHiddenField(oParent, sName, sValue) {
    var oInput = document.createElement("input");
    oInput.type = "hidden";
    oInput.name = sName;
    oInput.value = sValue || "";
    oParent.appendChild(oInput);
    return oInput;
}

function normalizeBusinessHoursTimeValue(sValue) {
    var sText = String(sValue || "").replace(/^\s+|\s+$/g, "").replace(/\s+/g, " ");
    var aMatches;
    var iHour;
    var iMinute = 0;
    if (sText == "") {
        return "";
    }
    aMatches = sText.match(/^([0-9]{1,2})[\s:.]+([0-9]{1,2})$/);
    if (aMatches) {
        iHour = parseInt(aMatches[1], 10);
        iMinute = parseInt(aMatches[2], 10);
    } else if (/^[0-9]{3,4}$/.test(sText)) {
        iHour = parseInt(sText.slice(0, -2), 10);
        iMinute = parseInt(sText.slice(-2), 10);
    } else if (/^[0-9]{1,2}$/.test(sText)) {
        iHour = parseInt(sText, 10);
    } else {
        return false;
    }
    if (!isFinite(iHour) || !isFinite(iMinute) || iHour < 0 || iHour > 23 || iMinute < 0 || iMinute > 59) {
        return false;
    }
    return (iHour < 10 ? "0" : "") + iHour + ":" + (iMinute < 10 ? "0" : "") + iMinute;
}

function normalizeBusinessHoursTimeInput(oInput) {
    var sValue = normalizeBusinessHoursTimeValue(oInput.value);
    if (sValue !== false) {
        oInput.value = sValue;
    }
}

function createBusinessHoursTimeInput(sValue, sLabel) {
    var oInput = document.createElement("input");
    oInput.type = "text";
    oInput.value = sValue || "";
    oInput.placeholder = sLabel;
    oInput.maxLength = 5;
    oInput.autocomplete = "off";
    oInput.spellcheck = false;
    oInput.inputMode = "numeric";
    oInput.addEventListener("blur", function() {
        normalizeBusinessHoursTimeInput(oInput);
    });
    return oInput;
}

function appendBusinessHoursDayRow(oParent, aDayInfo, aDayData) {
    var oRow = document.createElement("div");
    var oDayLabel = document.createElement("strong");
    var oClosedLabel = document.createElement("label");
    var oClosed = document.createElement("input");
    var oOpen = createBusinessHoursTimeInput(aDayData.open, "Open");
    var oBreakStart = createBusinessHoursTimeInput(aDayData.breakStart, "Break From");
    var oBreakEnd = createBusinessHoursTimeInput(aDayData.breakEnd, "Break To");
    var oClose = createBusinessHoursTimeInput(aDayData.close, "Close");
    oRow.className = "business-hours-day-row";
    oDayLabel.className = "business-hours-day-name";
    oDayLabel.textContent = aDayInfo.label;
    oClosed.type = "checkbox";
    oClosed.checked = aDayData.closed ? true : false;
    oClosedLabel.className = "checkbox-label business-hours-closed-label";
    oClosedLabel.appendChild(oClosed);
    oClosedLabel.appendChild(document.createTextNode("Closed"));
    oRow.appendChild(oDayLabel);
    oRow.appendChild(oClosedLabel);
    oRow.appendChild(oOpen);
    oRow.appendChild(oBreakStart);
    oRow.appendChild(oBreakEnd);
    oRow.appendChild(oClose);
    oParent.appendChild(oRow);

    function refreshDayFields() {
        var blClosed = oClosed.checked;
        oOpen.disabled = blClosed;
        oBreakStart.disabled = blClosed;
        oBreakEnd.disabled = blClosed;
        oClose.disabled = blClosed;
    }

    oClosed.addEventListener("change", function() {
        refreshDayFields();
        if (!oClosed.checked) {
            focusAdminElement(oOpen, true);
        }
    });
    refreshDayFields();
    return {
        key: aDayInfo.key,
        closed: oClosed,
        open: oOpen,
        breakStart: oBreakStart,
        breakEnd: oBreakEnd,
        close: oClose
    };
}

function bindBusinessHoursSuggestInput(oInput, aSettings) {
    var oIdField = aSettings.idField;
    var oList = getAdminInputDatalist(oInput);
    var iMinLength = typeof aSettings.minLength == "number" ? aSettings.minLength : 3;
    var iTimer = 0;
    var iRequestIndex = 0;
    var aIds = {};
    var aNames = {};
    var aUniqueIds = {};
    if (!window.fetch || !window.FormData || !oInput || !oIdField || !oList || oInput.getAttribute("data-business-hours-suggest-bound") == "1") {
        return;
    }
    oInput.setAttribute("data-business-hours-suggest-bound", "1");

    function hideList() {
        oList.innerHTML = "";
        aIds = {};
        aNames = {};
        aUniqueIds = {};
    }

    function getOptionValue(sName, sId, blUseId) {
        return blUseId ? sName + " (#" + sId + ")" : sName;
    }

    function selectByName(sName) {
        if (!aIds[sName]) {
            if (!aUniqueIds[sName]) {
                return false;
            }
            oIdField.value = aUniqueIds[sName];
            if (typeof aSettings.onSelect == "function") {
                aSettings.onSelect(oIdField.value, sName);
            }
            hideList();
            return true;
        }
        oIdField.value = aIds[sName];
        oInput.value = aNames[sName] || sName;
        if (typeof aSettings.onSelect == "function") {
            aSettings.onSelect(oIdField.value, oInput.value);
        }
        hideList();
        return true;
    }

    function renderSuggestions(aItems) {
        var aNameCounts = {};
        var sName;
        var sId;
        var sValue;
        var oOption;
        var i;
        hideList();
        if (!aItems || !aItems.length) {
            return;
        }
        for (i = 0; i < aItems.length; i++) {
            sName = aItems[i][aSettings.textKey] || "";
            aNameCounts[sName] = (aNameCounts[sName] || 0) + 1;
        }
        for (i = 0; i < aItems.length; i++) {
            sName = aItems[i][aSettings.textKey] || "";
            sId = aItems[i][aSettings.idKey] || "";
            sValue = getOptionValue(sName, sId, aNameCounts[sName] > 1);
            oOption = document.createElement("option");
            oOption.value = sValue;
            oOption.label = sName;
            oList.appendChild(oOption);
            aIds[sValue] = sId;
            aNames[sValue] = sName;
            if (typeof aUniqueIds[sName] == "undefined") {
                aUniqueIds[sName] = sId;
            } else if (aUniqueIds[sName] != sId) {
                aUniqueIds[sName] = "";
            }
        }
    }

    function requestSuggestions(sTerm, blOpenPicker) {
        var oData;
        var iCurrentRequest = iRequestIndex;
        var sSubjectId = aSettings.subjectIdField ? (aSettings.subjectIdField.value || "") : "";
        if (aSettings.subjectIdField && parseInt(sSubjectId || "0", 10) < 1) {
            hideList();
            return;
        }
        oData = new FormData();
        oData.append("action", aSettings.action);
        oData.append("term", sTerm);
        if (aSettings.subjectIdField) {
            oData.append("subject_id", sSubjectId);
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
            renderSuggestions(aData[aSettings.resultKey] || []);
            if (document.activeElement == oInput && (blOpenPicker === true || (aData[aSettings.resultKey] && aData[aSettings.resultKey].length))) {
                openAdminInputDatalist(oInput);
            }
        }).catch(function(oException) {
            logAdminException(oException);
            hideList();
        });
    }

    oInput.addEventListener("input", function() {
        var sTerm = oInput.value.replace(/^\s+|\s+$/g, "");
        iRequestIndex += 1;
        if (iTimer) {
            window.clearTimeout(iTimer);
        }
        if (selectByName(sTerm)) {
            return;
        }
        oIdField.value = "";
        if (typeof aSettings.onClear == "function") {
            aSettings.onClear();
        }
        if (sTerm.length < iMinLength) {
            hideList();
            return;
        }
        iTimer = window.setTimeout(function() {
            requestSuggestions(sTerm, false);
        }, 200);
    });
    oInput.addEventListener("click", function() {
        var sTerm = oInput.value.replace(/^\s+|\s+$/g, "");
        if (openAdminInputDatalist(oInput)) {
            return;
        }
        if (sTerm.length < iMinLength) {
            if (iMinLength > 0) {
                return;
            }
            sTerm = "";
        }
        iRequestIndex += 1;
        if (iTimer) {
            window.clearTimeout(iTimer);
        }
        requestSuggestions(sTerm, true);
    });
    oInput.addEventListener("change", function() {
        if (oInput.value.replace(/^\s+|\s+$/g, "") == "") {
            oIdField.value = "";
            if (typeof aSettings.onClear == "function") {
                aSettings.onClear();
            }
        } else {
            selectByName(oInput.value);
        }
    });
    oInput.addEventListener("keydown", function(oEvent) {
        if (oEvent.key == "Escape") {
            hideList();
        }
    });
}

function openBusinessHoursDialog(aRow, oSourceCard) {
    var oDialog = document.getElementById("admin-reusable-dialog");
    var oForm;
    var oBox;
    var oHeader;
    var oTitle;
    var oClose;
    var oSubjectId;
    var oSubject;
    var oSubjectList;
    var oAddressId;
    var oAddress;
    var oDays;
    var aDayInputs = [];
    var oActive;
    var oIcon;
    var oError;
    var oActions;
    var oSave;
    var oCancel;
    var iId = aRow ? aRow.id : 0;
    var aHours = aRow ? aRow.hours : getBusinessHoursDefaultHours();
    var blSaved = false;
    var iAddressRequestIndex = 0;
    var i;

    if (!oDialog) {
        return;
    }

    oForm = document.createElement("form");
    oForm.className = "confirm-dialog-box subject-edit-dialog business-hours-edit-dialog";
    oBox = oForm;

    oHeader = document.createElement("div");
    oHeader.className = "confirm-dialog-header";
    oTitle = document.createElement("strong");
    oTitle.className = "confirm-dialog-title";
    oTitle.textContent = iId > 0 ? "Edit Business Hours" : "New Business Hours";
    oClose = document.createElement("button");
    oClose.type = "button";
    oClose.className = "confirm-dialog-close";
    oClose.setAttribute("aria-label", "Close");
    oClose.innerHTML = "&times;";
    oHeader.appendChild(oTitle);
    oHeader.appendChild(oClose);
    oBox.appendChild(oHeader);

    oSubjectId = appendBusinessHoursHiddenField(oBox, "subject_id", aRow ? String(aRow.subjectId) : "");
    oSubject = createAdminInput("business-hours-dialog-subject", aRow ? aRow.subjectName : "", true);
    oSubject.setAttribute("list", "business-hours-subject-list");
    oSubjectList = document.createElement("datalist");
    oSubjectList.id = "business-hours-subject-list";
    appendMenuDialogField(oBox, "Subject", oSubject);
    oBox.appendChild(oSubjectList);

    oAddressId = appendBusinessHoursHiddenField(oBox, "address_id", aRow ? String(aRow.addressId) : "");
    oAddress = document.createElement("select");
    oAddress.id = "business-hours-dialog-address";
    oAddress.required = true;
    appendMenuDialogField(oBox, "Address", oAddress);

    oDays = document.createElement("div");
    oDays.className = "business-hours-days";
    for (i = 0; i < aBusinessHoursDays.length; i++) {
        aDayInputs.push(appendBusinessHoursDayRow(oDays, aBusinessHoursDays[i], aHours[aBusinessHoursDays[i].key]));
    }
    oBox.appendChild(oDays);

    oIcon = createAdminInput("business-hours-dialog-icon", aRow ? aRow.icon : "", false);
    appendMenuDialogField(oBox, "Icon", oIcon);

    oActive = document.createElement("input");
    oActive.type = "checkbox";
    oActive.checked = aRow ? aRow.active : true;
    appendMenuDialogCheck(oBox, "Active", oActive);

    oError = document.createElement("div");
    oError.className = "subject-edit-error";
    oError.style.display = "none";
    oBox.appendChild(oError);

    oActions = document.createElement("div");
    oActions.className = "confirm-dialog-actions";
    oSave = document.createElement("button");
    oSave.type = "submit";
    oSave.className = "confirm-dialog-button";
    oSave.textContent = "Save";
    oCancel = document.createElement("button");
    oCancel.type = "button";
    oCancel.className = "confirm-dialog-button";
    oCancel.textContent = "Cancel";
    oActions.appendChild(oSave);
    oActions.appendChild(oCancel);
    oBox.appendChild(oActions);

    function setAddressSelectMessage(sMessage) {
        var oOption = document.createElement("option");
        oAddress.innerHTML = "";
        oOption.value = "";
        oOption.textContent = sMessage;
        oAddress.appendChild(oOption);
    }

    function clearAddressField() {
        oAddressId.value = "";
        oAddress.disabled = true;
        setAddressSelectMessage("Select subject first.");
    }

    function getSelectedAddressText() {
        var iSelectedIndex = oAddress.selectedIndex;
        if (iSelectedIndex < 0 || !oAddress.options[iSelectedIndex] || oAddress.options[iSelectedIndex].value == "") {
            return "";
        }
        return oAddress.options[iSelectedIndex].text;
    }

    function loadAddressOptions(sSelectedAddressId) {
        var oData;
        var iCurrentRequest;
        var sSubjectId = oSubjectId.value || "";
        iAddressRequestIndex += 1;
        iCurrentRequest = iAddressRequestIndex;
        oAddressId.value = "";
        if (parseInt(sSubjectId || "0", 10) < 1) {
            clearAddressField();
            return;
        }
        if (!window.fetch || !window.FormData) {
            oAddress.disabled = true;
            setAddressSelectMessage("Addresses could not be loaded.");
            return;
        }
        oAddress.disabled = true;
        setAddressSelectMessage("Loading addresses...");
        oData = new FormData();
        oData.append("action", "suggest_business_hours_addresses");
        oData.append("term", "");
        oData.append("subject_id", sSubjectId);
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
            var aAddresses = aData.addresses || [];
            var oOption;
            var sAddressId;
            var i;
            if (iCurrentRequest != iAddressRequestIndex) {
                return;
            }
            oAddress.innerHTML = "";
            if (!aAddresses.length) {
                setAddressSelectMessage("No addresses found.");
                oAddress.disabled = true;
                return;
            }
            oOption = document.createElement("option");
            oOption.value = "";
            oOption.textContent = "Select address";
            oAddress.appendChild(oOption);
            for (i = 0; i < aAddresses.length; i++) {
                sAddressId = String(aAddresses[i].address_id || "");
                if (sAddressId == "") {
                    continue;
                }
                oOption = document.createElement("option");
                oOption.value = sAddressId;
                oOption.textContent = aAddresses[i].address_text || ("Address #" + sAddressId);
                if (sSelectedAddressId && sAddressId == String(sSelectedAddressId)) {
                    oOption.selected = true;
                    oAddressId.value = sAddressId;
                }
                oAddress.appendChild(oOption);
            }
            oAddress.disabled = false;
        }).catch(function(oException) {
            logAdminException(oException);
            if (iCurrentRequest != iAddressRequestIndex) {
                return;
            }
            oAddressId.value = "";
            oAddress.disabled = true;
            setAddressSelectMessage("Addresses could not be loaded.");
        });
    }

    function closeBusinessHoursDialog() {
        finishAdminSubjectRowEdit(oSourceCard, blSaved);
        closeAdminDialog();
    }

    bindBusinessHoursSuggestInput(oSubject, {
        action: "suggest_business_hours_subjects",
        resultKey: "subjects",
        idKey: "subject_id",
        textKey: "subject_name",
        idField: oSubjectId,
        minLength: 3,
        onSelect: function() {
            clearAddressField();
            loadAddressOptions("");
        },
        onClear: clearAddressField
    });

    beginAdminSubjectRowEdit(oSourceCard);
    oClose.addEventListener("click", closeBusinessHoursDialog);
    oCancel.addEventListener("click", closeBusinessHoursDialog);
    oAddress.addEventListener("change", function() {
        oAddressId.value = oAddress.value;
    });
    oForm.addEventListener("submit", function(oEvent) {
        var oData;
        var iSavedId;
        var oSavedCard;
        oEvent.preventDefault();
        oError.style.display = "none";
        oError.textContent = "";
        oData = new FormData();
        oData.append("action", iId > 0 ? "update_business_hours" : "create_business_hours");
        if (iId > 0) {
            oData.append("business_hours_id", String(iId));
        }
        oData.append("subject_id", oSubjectId.value);
        oAddressId.value = oAddress.value;
        oData.append("address_id", oAddress.value);
        appendAdminEncodedValue(oData, "subject_name", oSubject.value);
        appendAdminEncodedValue(oData, "address_text", getSelectedAddressText());
        appendAdminEncodedValue(oData, "icon", oIcon.value);
        for (i = 0; i < aDayInputs.length; i++) {
            normalizeBusinessHoursTimeInput(aDayInputs[i].open);
            normalizeBusinessHoursTimeInput(aDayInputs[i].breakStart);
            normalizeBusinessHoursTimeInput(aDayInputs[i].breakEnd);
            normalizeBusinessHoursTimeInput(aDayInputs[i].close);
        }
        for (i = 0; i < aDayInputs.length; i++) {
            if (aDayInputs[i].closed.checked) {
                oData.append("closed_" + aDayInputs[i].key, "1");
            }
            oData.append("open_" + aDayInputs[i].key, aDayInputs[i].open.value);
            oData.append("break_start_" + aDayInputs[i].key, aDayInputs[i].breakStart.value);
            oData.append("break_end_" + aDayInputs[i].key, aDayInputs[i].breakEnd.value);
            oData.append("close_" + aDayInputs[i].key, aDayInputs[i].close.value);
        }
        if (oActive.checked) {
            oData.append("is_active", "1");
        }
        oSave.disabled = true;
        submitAdminRequest(oData, function(aData) {
            iSavedId = aData && aData.business_hours_id ? parseInt(aData.business_hours_id, 10) : iId;
            replaceBusinessHoursCards(aData);
            oSavedCard = iSavedId ? findBusinessHoursCardById(iSavedId) : null;
            finishAdminSubjectRowEdit(oSavedCard || oSourceCard, true);
            blSaved = true;
            closeAdminDialog();
        }, function(sMessage) {
            oSave.disabled = false;
            oError.textContent = sMessage;
            oError.style.display = "";
        });
    });

    oDialog.innerHTML = "";
    oDialog.appendChild(oForm);
    enableAdminDialogDrag(oDialog, oForm, oHeader);
    if (oDialog.hidden) {
        lockAdminModalScroll();
    }
    oDialog.hidden = false;
    if (parseInt(oSubjectId.value || "0", 10) > 0) {
        loadAddressOptions(aRow ? String(aRow.addressId) : "");
    } else {
        clearAddressField();
    }
    window.setTimeout(function() {
        focusAdminElement(oSubject, true);
    }, 0);
}

function bindBusinessHours() {
    var oAdd = document.querySelector(".js-add-business-hours");
    var oGrid = document.getElementById("business-hours-cards");
    bindBusinessHoursTabs();
    if (oAdd) {
        oAdd.addEventListener("click", function() {
            openBusinessHoursDialog(null);
        });
    }
    if (oGrid) {
        oGrid.addEventListener("click", function(oEvent) {
            var oButton = oEvent.target.closest(".js-edit-business-hours, .js-delete-business-hours, .js-move-business-hours-up, .js-move-business-hours-down");
            var oCard = oButton ? oButton.closest("section[data-business-hours-id]") : null;
            var aRow = getBusinessHoursCardData(oCard);
            var oData;
            if (!oButton || !aRow) {
                return;
            }
            oEvent.preventDefault();
            if (oButton.classList.contains("js-edit-business-hours")) {
                openBusinessHoursDialog(aRow, oCard);
            } else if (oButton.classList.contains("js-delete-business-hours")) {
                beginAdminSubjectRowEdit(oCard);
                openAdminConfirmDialog("Confirm Deletion", createBusinessHoursDeleteMessage(aRow), "Yes", function() {
                    oData = new FormData();
                    oData.append("action", "delete_business_hours");
                    oData.append("business_hours_id", String(aRow.id));
                    submitAdminRequest(oData, function(aData) {
                        replaceBusinessHoursCards(aData);
                    }, function(sMessage) {
                        finishAdminSubjectRowEdit(oCard, false);
                        alert(sMessage);
                    });
                }, function() {
                    finishAdminSubjectRowEdit(oCard, false);
                }, "No");
            } else if (oButton.classList.contains("js-move-business-hours-up") || oButton.classList.contains("js-move-business-hours-down")) {
                oData = new FormData();
                oData.append("action", "move_business_hours");
                oData.append("business_hours_id", String(aRow.id));
                oData.append("direction", oButton.classList.contains("js-move-business-hours-up") ? "up" : "down");
                submitAdminRequest(oData, function(aData) {
                    var iSavedId = aData && aData.business_hours_id ? parseInt(aData.business_hours_id, 10) : aRow.id;
                    var oSavedCard;
                    replaceBusinessHoursCards(aData);
                    oSavedCard = iSavedId ? findBusinessHoursCardById(iSavedId) : null;
                    finishAdminSubjectRowEdit(oSavedCard || oCard, true);
                }, function(sMessage) {
                    finishAdminSubjectRowEdit(oCard, false);
                    alert(sMessage);
                });
            }
        });
        activateBusinessHoursCard(0);
    }
}

function resetMenuActivation(oMenu) {
    var aLinks;
    var iI;
    if (!oMenu) {
        return;
    }
    if (oMenu._menuActivationTimer) {
        window.clearTimeout(oMenu._menuActivationTimer);
        oMenu._menuActivationTimer = 0;
    }
    oMenu.classList.remove("menu-activating");
    aLinks = oMenu.querySelectorAll(".menu-link-activating");
    for (iI = 0; iI < aLinks.length; iI += 1) {
        aLinks[iI].classList.remove("menu-link-activating");
    }
}

function closeMenu(oMenu) {
    var oButton = oMenu ? oMenu.querySelector("[data-menu-button]") : null;
    var oPanel = oMenu ? oMenu.querySelector("[data-menu-panel]") : null;
    resetMenuActivation(oMenu);
    if (oPanel) {
        oPanel.hidden = true;
    }
    if (oButton) {
        oButton.setAttribute("aria-expanded", "false");
    }
}

function closeMenus(oExcept) {
    var aMenus = document.querySelectorAll("[data-menu]");
    for (var iI = 0; iI < aMenus.length; iI += 1) {
        if (aMenus[iI] !== oExcept) {
            closeMenu(aMenus[iI]);
        }
    }
}

function openMenu(oMenu) {
    var oButton = oMenu ? oMenu.querySelector("[data-menu-button]") : null;
    var oPanel = oMenu ? oMenu.querySelector("[data-menu-panel]") : null;
    if (!oButton || !oPanel) {
        return;
    }
    closeMenus(oMenu);
    oPanel.hidden = false;
    oButton.setAttribute("aria-expanded", "true");
}

function getVisibleMenuLinkAtMouseEvent(oEvent) {
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

function getVisibleMenuAtMouseEvent(oEvent) {
    var oElement = oEvent.target;
    var oMenu = oElement && oElement.closest ? oElement.closest("[data-menu]") : null;
    var oPanel = oMenu ? oMenu.querySelector("[data-menu-panel]") : null;
    if (oPanel && !oPanel.hidden) {
        return oMenu;
    }
    if (!document.elementFromPoint || typeof oEvent.clientX == "undefined" || typeof oEvent.clientY == "undefined") {
        return null;
    }
    oElement = document.elementFromPoint(oEvent.clientX, oEvent.clientY);
    oMenu = oElement && oElement.closest ? oElement.closest("[data-menu]") : null;
    oPanel = oMenu ? oMenu.querySelector("[data-menu-panel]") : null;
    return oPanel && !oPanel.hidden ? oMenu : null;
}

function flashMenuLink(oMenuLink) {
    var oMenu = oMenuLink && oMenuLink.closest ? oMenuLink.closest("[data-menu]") : null;
    var iTimer;
    if (!oMenuLink) {
        return;
    }
    if (oMenu) {
        oMenu.classList.add("menu-activating");
        if (oMenu._menuActivationTimer) {
            window.clearTimeout(oMenu._menuActivationTimer);
            oMenu._menuActivationTimer = 0;
        }
    }
    oMenuLink.classList.remove("menu-link-activating");
    oMenuLink.offsetWidth;
    oMenuLink.classList.add("menu-link-activating");
    iTimer = window.setTimeout(function () {
        if (oMenu) {
            oMenu._menuActivationTimer = 0;
        }
        oMenuLink.classList.remove("menu-link-activating");
        if (oMenu) {
            oMenu.classList.remove("menu-activating");
        }
        closeMenu(oMenu);
    }, 1000);
    if (oMenu) {
        oMenu._menuActivationTimer = iTimer;
    }
}

function isMenuActivating(oMenu) {
    return oMenu && oMenu.classList && oMenu.classList.contains("menu-activating");
}

function isMenuButtonEvent(oEvent) {
    return !!(oEvent.target && oEvent.target.closest && oEvent.target.closest("[data-menu-button]"));
}

function activateMenuLink(oMenuLink, sTargetOverride) {
    var sHref = oMenuLink ? oMenuLink.href : "";
    var sTarget = sTargetOverride || (oMenuLink ? oMenuLink.getAttribute("target") || "" : "");
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

function setupMenu() {
    var aMenus = document.querySelectorAll("[data-menu]");
    var blSuppressNextMenuLinkClick = false;

    if (aMenus.length === 0) {
        return;
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
                openMenu(oMenu);
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
                    openMenu(oMenu);
                } else {
                    closeMenu(oMenu);
                }
            });
        })(aMenus[iI]);
    }

    document.addEventListener("mousedown", function (oEvent) {
        var oMenu = oEvent.target.closest ? oEvent.target.closest("[data-menu]") : null;
        if (!oMenu) {
            closeMenus(null);
        }
    }, true);

    document.addEventListener("mouseup", function (oEvent) {
        var oElement;
        var iButton = typeof oEvent.button == "undefined" ? 0 : oEvent.button;
        var oMenu;
        var oMenuLink;
        oMenuLink = getVisibleMenuLinkAtMouseEvent(oEvent);
        if (oMenuLink) {
            oMenu = oMenuLink.closest ? oMenuLink.closest("[data-menu]") : null;
            if (isMenuActivating(oMenu)) {
                if (iButton === 0) {
                    blSuppressNextMenuLinkClick = true;
                }
                oEvent.preventDefault();
                oEvent.stopPropagation();
                return;
            }
            if (iButton !== 0) {
                return;
            }
            blSuppressNextMenuLinkClick = true;
            window.setTimeout(function () {
                blSuppressNextMenuLinkClick = false;
            }, 0);
            flashMenuLink(oMenuLink);
            activateMenuLink(oMenuLink, (oEvent.ctrlKey || oEvent.shiftKey) ? "_blank" : "");
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
            closeMenus(null);
        }
    }, true);

    document.addEventListener("auxclick", function (oEvent) {
        var iButton = typeof oEvent.button == "undefined" ? 0 : oEvent.button;
        var oMenu;
        var oMenuLink = getVisibleMenuLinkAtMouseEvent(oEvent);
        if (oMenuLink) {
            oMenu = oMenuLink.closest ? oMenuLink.closest("[data-menu]") : null;
            if (isMenuActivating(oMenu)) {
                oEvent.preventDefault();
                oEvent.stopPropagation();
                return;
            }
        }
        if (iButton == 1 && oMenuLink) {
            flashMenuLink(oMenuLink);
        }
    }, true);

    document.addEventListener("contextmenu", function (oEvent) {
        if (getVisibleMenuAtMouseEvent(oEvent)) {
            oEvent.preventDefault();
            oEvent.stopPropagation();
        }
    }, true);

    document.addEventListener("click", function (oEvent) {
        var oMenu = getVisibleMenuAtMouseEvent(oEvent);
        if (isMenuActivating(oMenu)) {
            if (isMenuButtonEvent(oEvent)) {
                return;
            }
            oEvent.preventDefault();
            oEvent.stopPropagation();
            return;
        }
        if (blSuppressNextMenuLinkClick) {
            blSuppressNextMenuLinkClick = false;
            oEvent.preventDefault();
            oEvent.stopPropagation();
        }
    }, true);

    document.addEventListener("keydown", function (oEvent) {
        if (oEvent.key == "Escape") {
            closeMenus(null);
        }
    });
}

function bindAdminSubmitOnChange() {
    var aInputs = document.querySelectorAll(".js-submit-on-change");
    var i;
    for (i = 0; i < aInputs.length; i++) {
        aInputs[i].addEventListener("change", function() {
            var oForm = this.form;
            if (oForm) {
                oForm.submit();
            }
        });
    }
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
    var iTokenEnd = iOffset;
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
            query: sValue,
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
        query: sValue.substring(iStart, iCaret),
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
    oInput.addEventListener("keyup", function(oEvent) {
        if (oEvent.key == "ArrowDown" || oEvent.key == "ArrowUp" || oEvent.key == "Enter" || oEvent.key == "Escape") {
            return;
        }
        positionMailRecipientSuggestBox(oInput, oBox);
    });
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
    if (isSnippetBoardPmdLike()) {
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
        }, 10000);
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
            scheduleSnippetBoardToolbarLines(oEditor);
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

function layoutCharacterConverter() {
    var oBody = document.body;
    var oForm = document.getElementById("character-converter-form");
    var oVisualViewport = window.visualViewport || null;
    var iViewportHeight = oVisualViewport ? Math.floor(oVisualViewport.height) : (window.innerHeight || document.documentElement.clientHeight || 768);
    var iViewportTop = oVisualViewport ? Math.floor(oVisualViewport.offsetTop) : 0;
    if (!oBody || !oForm) {
        return;
    }
    if (isSnippetBoardPmdLike()) {
        oBody.style.top = Math.max(0, iViewportTop + 6) + "px";
        oBody.style.bottom = "auto";
        oBody.style.height = Math.max(160, iViewportHeight - 12) + "px";
    } else {
        oBody.style.top = "";
        oBody.style.bottom = "";
        oBody.style.height = "";
    }
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

function applyMailStoredRichTextPaste(oEditor) {
    var oInput;
    if (!isMailEditor(oEditor)) {
        return false;
    }
    oInput = getMailRichTextPasteInput();
    setSnippetBoardEditorPlainTextPasteMode(oEditor, !oInput || oInput.value != "1");
    return true;
}

function saveMailRichTextPaste(blEnabled) {
    var oData = new FormData();
    setMailRichTextPasteInputValue(blEnabled);
    appendAdminEncodedValue(oData, "action", "save_mail_rich_text_paste");
    appendAdminEncodedValue(oData, "rich_text_paste", blEnabled ? "1" : "0");
    submitAdminRequest(oData, null, function(sMessage) {
        logAdminException(sMessage);
    });
}

function saveMailEditorRichTextPaste(oEditor) {
    if (!isMailEditor(oEditor) || !oEditor || typeof oEditor.queryCommandState != "function") {
        return false;
    }
    saveMailRichTextPaste(!oEditor.queryCommandState("mceTogglePlainTextPaste"));
    return true;
}

var blSnippetBoardApplyingRemote = false;

function resizeSnippetBoardEditors() {
    var oGrid = document.querySelector(".snippet-board-grid");
    var iHeight = oGrid ? parseInt(oGrid.getAttribute("data-editor-height") || "320", 10) : 320;
    if (window.tinymce && typeof window.tinymce.get == "function") {
        window.setTimeout(function() {
            var aTextareas = document.querySelectorAll(".js-snippet-board-textarea");
            var oContainer;
            var oEditor;
            var i;
            for (i = 0; i < aTextareas.length; i++) {
                oEditor = window.tinymce.get(aTextareas[i].id);
                if (oEditor && typeof oEditor.getContainer == "function") {
                    oContainer = oEditor.getContainer();
                    if (oContainer) {
                        oContainer.style.height = iHeight + "px";
                        oContainer.style.minHeight = iHeight + "px";
                    }
                    if (typeof oEditor.dispatch == "function") {
                        oEditor.dispatch("ResizeEditor");
                    }
                    scheduleSnippetBoardToolbarLines(oEditor);
                }
            }
        }, 0);
    }
}

function isSnippetBoardPmdLike() {
    return document.body && document.body.getAttribute("data-pmd-like") == "1";
}

function isSnippetBoardLocked() {
    return document.body && document.body.getAttribute("data-snippet-board-locked") == "1";
}

function isSnippetBoardScrollLockPage() {
    var sClass;
    if (!document.body) {
        return false;
    }
    sClass = " " + (document.body.className || "") + " ";
    return sClass.indexOf(" snippet-board-page ") !== -1 || sClass.indexOf(" mail-page ") !== -1;
}

function isSnippetBoardPageScrollAllowedTarget(oTarget) {
    var oElement = oTarget;
    var sClass;
    while (oElement && oElement != document.body) {
        if (oElement.nodeType == 1 && typeof oElement.getAttribute == "function") {
            sClass = " " + (oElement.getAttribute("class") || "") + " ";
            if (sClass.indexOf(" tox-edit-area__iframe ") !== -1
                || sClass.indexOf(" js-snippet-board-textarea ") !== -1
                || sClass.indexOf(" character-palette-scroll ") !== -1
                || sClass.indexOf(" character-palette ") !== -1
                || sClass.indexOf(" character-palette-button ") !== -1
                || sClass.indexOf(" character-palette-tone-popup ") !== -1
                || sClass.indexOf(" confirm-dialog ") !== -1
                || sClass.indexOf(" tox-tinymce-aux ") !== -1) {
                return true;
            }
        }
        oElement = oElement.parentNode;
    }
    return false;
}

function lockSnippetBoardPageScroll() {
    if (!isSnippetBoardPmdLike()) {
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

function preventSnippetBoardPageScroll(oEvent) {
    if (!isSnippetBoardPmdLike() || isSnippetBoardPageScrollAllowedTarget(oEvent.target)) {
        return;
    }
    if (oEvent.cancelable) {
        oEvent.preventDefault();
    }
    lockSnippetBoardPageScroll();
}

function bindSnippetBoardPageScrollLock() {
    if (!isSnippetBoardScrollLockPage()) {
        return;
    }
    document.addEventListener("touchmove", preventSnippetBoardPageScroll, {passive: false});
    document.addEventListener("wheel", preventSnippetBoardPageScroll, {passive: false});
    window.addEventListener("scroll", lockSnippetBoardPageScroll);
    lockSnippetBoardPageScroll();
}

function getSbPmdHoverButton(oTarget) {
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

function isSbPmdDropdownButton(oButton) {
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

function clearSbPmdHoverButton(oButton, blExpire) {
    if (!oButton) {
        return;
    }
    if (oButton._sbPmdHoverTimer && window.clearTimeout) {
        window.clearTimeout(oButton._sbPmdHoverTimer);
    }
    oButton._sbPmdHoverTimer = null;
    removeAdminClass(oButton, "snippet-board-pmd-hover-active");
    if (blExpire) {
        addAdminClass(oButton, "snippet-board-pmd-hover-expired");
    } else {
        removeAdminClass(oButton, "snippet-board-pmd-hover-expired");
    }
}

function getSbPmdDropdownMenu() {
    var aMenus = document.querySelectorAll(".tox.tox-tinymce-aux .tox-menu, .tox.tox-tinymce-aux .tox-dropdown-content, .tox.tox-tinymce-aux .tox-collection, .tox.tox-tinymce-aux [role=\"menu\"], .tox.tox-tinymce-aux [role=\"listbox\"]");
    var i;
    for (i = 0; i < aMenus.length; i++) {
        if (aMenus[i].getClientRects && aMenus[i].getClientRects().length > 0) {
            return aMenus[i];
        }
    }
    return null;
}

function clearSbPmdDropdown() {
    var oButton = oSbPmdDropdown;
    if (!oSbPmdDropdown) {
        return;
    }
    oSbPmdDropdown = null;
    iSbPmdDropdownToken += 1;
    clearSbPmdHoverButton(oButton, true);
}

function syncSbPmdDropdown(iToken, blCloseWhenMissing) {
    if (!oSbPmdDropdown || iToken != iSbPmdDropdownToken) {
        return;
    }
    if (getSbPmdDropdownMenu()) {
        removeAdminClass(oSbPmdDropdown, "snippet-board-pmd-hover-expired");
        addAdminClass(oSbPmdDropdown, "snippet-board-pmd-hover-active");
    } else if (blCloseWhenMissing) {
        clearSbPmdDropdown();
    }
}

function scheduleSbPmdDropdownSync(iToken, iDelay, blCloseWhenMissing) {
    if (!window.setTimeout) {
        return;
    }
    window.setTimeout(function() {
        syncSbPmdDropdown(iToken, blCloseWhenMissing);
    }, iDelay);
}

function watchSbPmdDropdown(oButton) {
    var iToken;
    if (!oButton || !window.setTimeout) {
        return;
    }
    oSbPmdDropdown = oButton;
    iSbPmdDropdownToken += 1;
    iToken = iSbPmdDropdownToken;
    removeAdminClass(oButton, "snippet-board-pmd-hover-expired");
    syncSbPmdDropdown(iToken, false);
    scheduleSbPmdDropdownSync(iToken, 0, false);
    scheduleSbPmdDropdownSync(iToken, 80, false);
    scheduleSbPmdDropdownSync(iToken, 250, false);
    scheduleSbPmdDropdownSync(iToken, 1200, true);
}

function clearSbPmdHoverButtons(oCurrentButton) {
    var aButtons = document.querySelectorAll(".snippet-board-form .tox .snippet-board-pmd-hover-active, .snippet-board-form .tox .snippet-board-pmd-hover-expired");
    var i;
    for (i = 0; i < aButtons.length; i++) {
        if (aButtons[i] == oCurrentButton) {
            continue;
        }
        clearSbPmdHoverButton(aButtons[i], true);
    }
}

function startSbPmdHoverTimeout(oButton) {
    if (!oButton || !window.setTimeout || !window.clearTimeout) {
        return;
    }
    clearSbPmdHoverButtons(oButton);
    removeAdminClass(oButton, "snippet-board-pmd-hover-expired");
    addAdminClass(oButton, "snippet-board-pmd-hover-active");
    if (oButton._sbPmdHoverTimer) {
        window.clearTimeout(oButton._sbPmdHoverTimer);
    }
    oButton._sbPmdHoverTimer = window.setTimeout(function() {
        clearSbPmdHoverButton(oButton, true);
    }, 1000);
}

function handleSbPmdHoverStart(oEvent) {
    var oButton;
    if (!isSnippetBoardPmdLike() || (oEvent.pointerType && oEvent.pointerType == "mouse")) {
        return;
    }
    oButton = getSbPmdHoverButton(oEvent.target);
    if (oButton) {
        if (isSbPmdDropdownButton(oButton)) {
            if (oSbPmdDropdown && oSbPmdDropdown != oButton) {
                clearSbPmdDropdown();
            }
            clearSbPmdHoverButtons(oButton);
            clearSbPmdHoverButton(oButton, false);
            watchSbPmdDropdown(oButton);
        } else {
            clearSbPmdDropdown();
            startSbPmdHoverTimeout(oButton);
        }
    } else if (!oEvent.target.closest || !oEvent.target.closest(".tox.tox-tinymce-aux")) {
        clearSbPmdDropdown();
    }
}

function handleSbPmdDropdownMenuClick(oEvent) {
    if (!isSnippetBoardPmdLike() || !oSbPmdDropdown || !oEvent.target.closest || !oEvent.target.closest(".tox.tox-tinymce-aux")) {
        return;
    }
    window.setTimeout(clearSbPmdDropdown, 0);
}

function bindSbPmdHoverTimeout() {
    if (!isSnippetBoardScrollLockPage()) {
        return;
    }
    if (window.PointerEvent) {
        document.addEventListener("pointerdown", handleSbPmdHoverStart, {passive: true});
    } else {
        document.addEventListener("touchstart", handleSbPmdHoverStart, {passive: true});
    }
    document.addEventListener("click", handleSbPmdDropdownMenuClick);
}

function getSnippetBoardEditorScrollState(oEditor) {
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

function shouldPreventSnippetBoardEditorOverscroll(oEditor, iDeltaY) {
    var aState = getSnippetBoardEditorScrollState(oEditor);
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

function bindSnippetBoardEditorScrollLock(oEditor) {
    var oDoc = oEditor && typeof oEditor.getDoc == "function" ? oEditor.getDoc() : null;
    var oRoot = oDoc ? oDoc.documentElement : null;
    var oBody = oDoc ? oDoc.body : null;
    if (!oDoc || !isSnippetBoardPmdLike() || oEditor._snippetBoardScrollLockBound) {
        return;
    }
    oEditor._snippetBoardScrollLockBound = true;
    if (oRoot && oRoot.style) {
        oRoot.style.overscrollBehavior = "contain";
    }
    if (oBody && oBody.style) {
        oBody.style.overscrollBehavior = "contain";
    }
    oDoc.addEventListener("touchstart", function(oEvent) {
        oEditor._snippetBoardTouchY = oEvent.touches && oEvent.touches.length ? oEvent.touches[0].clientY : 0;
    }, {passive: true});
    oDoc.addEventListener("touchmove", function(oEvent) {
        var iClientY = oEvent.touches && oEvent.touches.length ? oEvent.touches[0].clientY : 0;
        var iDeltaY = (oEditor._snippetBoardTouchY || iClientY) - iClientY;
        oEditor._snippetBoardTouchY = iClientY;
        if (shouldPreventSnippetBoardEditorOverscroll(oEditor, iDeltaY) && oEvent.cancelable) {
            oEvent.preventDefault();
            lockSnippetBoardPageScroll();
        }
    }, {passive: false});
    oDoc.addEventListener("wheel", function(oEvent) {
        if (shouldPreventSnippetBoardEditorOverscroll(oEditor, oEvent.deltaY || 0) && oEvent.cancelable) {
            oEvent.preventDefault();
            lockSnippetBoardPageScroll();
        }
    }, {passive: false});
}

function getSnippetBoardColumnCount(iViewportWidth) {
    if (isSnippetBoardPmdLike()) {
        return 1;
    }
    if (iViewportWidth >= 1180) {
        return 3;
    }
    if (iViewportWidth >= 760) {
        return 2;
    }
    return 1;
}

function getSnippetBoardEditorHeight(iAvailableHeight, iRows, blPmdLike) {
    var iGap = 5;
    var iHeight;
    if (blPmdLike) {
        return Math.max(220, iAvailableHeight);
    }
    iHeight = Math.floor((iAvailableHeight - (iRows - 1) * iGap) / iRows);
    return Math.max(220, iHeight);
}

function layoutSnippetBoard() {
    var oForm = document.getElementById("snippet-board-form");
    var oGrid = oForm ? oForm.querySelector(".snippet-board-grid") : null;
    var aTextareas = oForm ? oForm.querySelectorAll(".js-snippet-board-textarea") : [];
    var oControls = document.querySelector(".admin-controls");
    var oVisualViewport = window.visualViewport || null;
    var iViewportWidth = oVisualViewport ? Math.floor(oVisualViewport.width) : (window.innerWidth || document.documentElement.clientWidth || 1024);
    var iViewportHeight = oVisualViewport ? Math.floor(oVisualViewport.height) : (window.innerHeight || document.documentElement.clientHeight || 768);
    var blPmdLike = isSnippetBoardPmdLike();
    var iColumns = getSnippetBoardColumnCount(iViewportWidth);
    var iRows = blPmdLike ? 1 : Math.ceil(6 / iColumns);
    var iTop = oForm ? oForm.getBoundingClientRect().top - (oVisualViewport ? oVisualViewport.offsetTop : 0) : (oControls ? oControls.getBoundingClientRect().bottom + 6 : 0);
    var iAvailableHeight = Math.max(220, Math.floor(iViewportHeight - iTop - 6));
    var iEditorHeight = getSnippetBoardEditorHeight(iAvailableHeight, iRows, blPmdLike);
    var i;
    if (!oForm || !oGrid) {
        return;
    }
    oForm.style.height = blPmdLike ? iAvailableHeight + "px" : "";
    oGrid.style.gridTemplateColumns = "repeat(" + iColumns + ", minmax(0, 1fr))";
    oGrid.setAttribute("data-editor-height", String(iEditorHeight));
    oGrid.setAttribute("data-columns", String(iColumns));
    oGrid.setAttribute("data-rows", String(iRows));
    for (i = 0; i < aTextareas.length; i++) {
        aTextareas[i].style.height = iEditorHeight + "px";
    }
    resizeSnippetBoardEditors();
}

function activateSnippetBoardPanel(sSlot) {
    var aTabs = document.querySelectorAll("[data-snippet-tab]");
    var aPanels = document.querySelectorAll("[data-snippet-panel]");
    var i;
    for (i = 0; i < aTabs.length; i++) {
        if (aTabs[i].getAttribute("data-snippet-tab") == sSlot) {
            addAdminClass(aTabs[i], "snippet-board-tab-active");
            aTabs[i].setAttribute("aria-selected", "true");
        } else {
            removeAdminClass(aTabs[i], "snippet-board-tab-active");
            aTabs[i].setAttribute("aria-selected", "false");
        }
    }
    for (i = 0; i < aPanels.length; i++) {
        if (aPanels[i].getAttribute("data-snippet-panel") == sSlot) {
            addAdminClass(aPanels[i], "snippet-board-panel-active");
        } else {
            removeAdminClass(aPanels[i], "snippet-board-panel-active");
        }
    }
    resizeSnippetBoardEditors();
    layoutSnippetBoard();
}

function bindSnippetBoardTabs() {
    var aTabs = document.querySelectorAll("[data-snippet-tab]");
    var i;
    for (i = 0; i < aTabs.length; i++) {
        aTabs[i].addEventListener("click", function() {
            activateSnippetBoardPanel(this.getAttribute("data-snippet-tab") || "1");
        });
    }
}

function getSnippetBoardEditorSlot(oEditor) {
    var oElement = oEditor && typeof oEditor.getElement == "function" ? oEditor.getElement() : null;
    var sName = oElement ? (oElement.name || "") : "";
    if (sName.indexOf("snippet_") === 0) {
        return sName.replace(/^snippet_/, "");
    }
    return oElement && oElement.id ? oElement.id.replace(/^snippet-/, "") : "";
}

function getSnippetBoardRichTextPasteInput(sSlot) {
    return document.querySelector("[data-snippet-rich-text-paste=\"" + sSlot + "\"]");
}

function setSnippetBoardRichTextPasteInputValue(sSlot, blEnabled, blDispatch) {
    var oInput = getSnippetBoardRichTextPasteInput(sSlot);
    var sValue = blEnabled ? "1" : "0";
    if (!oInput || oInput.value == sValue) {
        return;
    }
    oInput.value = sValue;
    if (blDispatch) {
        dispatchAdminInputEvent(oInput);
    }
}

function setSnippetBoardEditorPlainTextPasteMode(oEditor, blEnabled) {
    var blCurrent;
    if (!oEditor || typeof oEditor.execCommand != "function" || typeof oEditor.queryCommandState != "function") {
        return;
    }
    blCurrent = oEditor.queryCommandState("mceTogglePlainTextPaste");
    if ((blCurrent ? 1 : 0) != (blEnabled ? 1 : 0)) {
        oEditor.execCommand("mceTogglePlainTextPaste");
    }
    scheduleSnippetBoardPasteModeButtonSync(oEditor);
}

function updateSnippetBoardPasteModeButtons(oEditor) {
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
        setSnippetBoardPasteModeButtonState(aFormattedButtons[i], !blPlainTextPaste);
    }
    for (i = 0; i < aPlainTextButtons.length; i++) {
        setSnippetBoardPasteModeButtonState(aPlainTextButtons[i], blPlainTextPaste);
    }
}

function setSnippetBoardPasteModeButtonState(oButton, blActive) {
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

function scheduleSnippetBoardPasteModeButtonSync(oEditor) {
    var iToken;
    if (!oEditor) {
        return;
    }
    oEditor._snippetBoardPasteModeSyncToken = (oEditor._snippetBoardPasteModeSyncToken || 0) + 1;
    iToken = oEditor._snippetBoardPasteModeSyncToken;
    updateSnippetBoardPasteModeButtons(oEditor);
    window.setTimeout(function() {
        if (oEditor._snippetBoardPasteModeSyncToken == iToken) {
            updateSnippetBoardPasteModeButtons(oEditor);
        }
    }, 0);
    window.setTimeout(function() {
        if (oEditor._snippetBoardPasteModeSyncToken == iToken) {
            updateSnippetBoardPasteModeButtons(oEditor);
        }
    }, 80);
    window.setTimeout(function() {
        if (oEditor._snippetBoardPasteModeSyncToken == iToken) {
            updateSnippetBoardPasteModeButtons(oEditor);
        }
    }, 250);
}

function applySnippetBoardStoredRichTextPaste(oEditor) {
    var sSlot = getSnippetBoardEditorSlot(oEditor);
    var oInput = getSnippetBoardRichTextPasteInput(sSlot);
    if (applyMailStoredRichTextPaste(oEditor)) {
        return;
    }
    setSnippetBoardEditorPlainTextPasteMode(oEditor, oInput && oInput.value != "1");
}

function saveSnippetBoardEditorRichTextPaste(oEditor) {
    var sSlot = getSnippetBoardEditorSlot(oEditor);
    if (saveMailEditorRichTextPaste(oEditor)) {
        return;
    }
    if (!sSlot || !oEditor || typeof oEditor.queryCommandState != "function") {
        return;
    }
    setSnippetBoardRichTextPasteInputValue(sSlot, !oEditor.queryCommandState("mceTogglePlainTextPaste"), true);
}

function setSnippetBoardEditorStoredRichTextPaste(oEditor, blEnabled) {
    setSnippetBoardEditorPlainTextPasteMode(oEditor, !blEnabled);
    saveSnippetBoardEditorRichTextPaste(oEditor);
    scheduleSnippetBoardPasteModeButtonSync(oEditor);
}

function bindSnippetBoardForm() {
    var oForm = document.getElementById("snippet-board-form");
    var oStatus = document.querySelector(".js-snippet-board-status");
    var oBoardChannel = null;
    var iSaveTimer = null;
    var iRevisionTimer = null;
    var iStatusTimer = null;
    var blSaving = false;
    var blSaveAgain = false;
    var blChanged = false;
    var blRevisionRequest = false;
    var sBoardInstanceId = String(new Date().getTime()) + "-" + String(Math.random());
    var sBoardRevision = document.body ? (document.body.getAttribute("data-snippet-board-revision") || "") : "";
    var iSaveDebounceMs = 10000;
    var iRevisionPollMs = 10000;
    var iLastRevisionRequestAt = 0;

    function setSnippetBoardStatus(sText, sState) {
        if (!oStatus) {
            return;
        }
        if (iStatusTimer) {
            window.clearTimeout(iStatusTimer);
            iStatusTimer = null;
        }
        oStatus.textContent = sText || "";
        oStatus.className = "snippet-board-status js-snippet-board-status";
        if (sState) {
            addAdminClass(oStatus, "snippet-board-status-" + sState);
        }
        if (sText && sState == "saved") {
            iStatusTimer = window.setTimeout(function() {
                iStatusTimer = null;
                oStatus.textContent = "";
                oStatus.className = "snippet-board-status js-snippet-board-status";
            }, 10000);
        }
    }

    function setSnippetBoardRevision(sRevision) {
        if (typeof sRevision != "string") {
            return;
        }
        sBoardRevision = sRevision;
        if (document.body) {
            document.body.setAttribute("data-snippet-board-revision", sRevision);
        }
    }

    function getSnippetBoardValues() {
        var aTextareas = oForm.querySelectorAll(".js-snippet-board-textarea");
        var aValues = {};
        var sSlot;
        var i;
        if (window.tinymce && typeof window.tinymce.triggerSave == "function") {
            window.tinymce.triggerSave();
        }
        for (i = 0; i < aTextareas.length; i++) {
            sSlot = aTextareas[i].name.replace(/^snippet_/, "");
            aValues[sSlot] = aTextareas[i].value;
        }
        return aValues;
    }

    function getSnippetBoardRichTextPasteModes() {
        var aInputs = oForm.querySelectorAll(".js-snippet-board-rich-text-paste");
        var aValues = {};
        var sSlot;
        var i;
        for (i = 0; i < aInputs.length; i++) {
            sSlot = aInputs[i].getAttribute("data-snippet-rich-text-paste") || "";
            if (sSlot != "") {
                aValues[sSlot] = aInputs[i].value == "1" ? "1" : "0";
            }
        }
        return aValues;
    }

    function collectSnippetBoardData() {
        var oData = new FormData();
        var aValues = getSnippetBoardValues();
        var aRichTextPasteModes = getSnippetBoardRichTextPasteModes();
        var sSlot;
        oData.append("action", "save_snippet_board");
        for (sSlot in aValues) {
            if (Object.prototype.hasOwnProperty.call(aValues, sSlot)) {
                appendAdminEncodedValue(oData, "snippet_" + sSlot, aValues[sSlot]);
            }
        }
        for (sSlot in aRichTextPasteModes) {
            if (Object.prototype.hasOwnProperty.call(aRichTextPasteModes, sSlot)) {
                appendAdminEncodedValue(oData, "rich_text_paste_" + sSlot, aRichTextPasteModes[sSlot]);
            }
        }
        return oData;
    }

    function applySnippetBoardValues(aValues, aRichTextPasteModes) {
        var aTextareas = oForm.querySelectorAll(".js-snippet-board-textarea");
        var aInputs = oForm.querySelectorAll(".js-snippet-board-rich-text-paste");
        var oEditor;
        var sSlot;
        var sValue;
        var i;
        blSnippetBoardApplyingRemote = true;
        try {
            for (i = 0; i < aTextareas.length; i++) {
                sSlot = aTextareas[i].name.replace(/^snippet_/, "");
                sValue = aValues && Object.prototype.hasOwnProperty.call(aValues, sSlot) ? String(aValues[sSlot]) : "";
                oEditor = window.tinymce && typeof window.tinymce.get == "function" ? window.tinymce.get(aTextareas[i].id) : null;
                if (oEditor && typeof oEditor.setContent == "function") {
                    if (typeof oEditor.getContent != "function" || oEditor.getContent() != sValue) {
                        oEditor.setContent(sValue);
                    }
                    if (typeof oEditor.save == "function") {
                        oEditor.save();
                    }
                } else if (aTextareas[i].value != sValue) {
                    aTextareas[i].value = sValue;
                }
            }
            for (i = 0; i < aInputs.length; i++) {
                sSlot = aInputs[i].getAttribute("data-snippet-rich-text-paste") || "";
                if (sSlot == "") {
                    continue;
                }
                sValue = aRichTextPasteModes && Object.prototype.hasOwnProperty.call(aRichTextPasteModes, sSlot) && String(aRichTextPasteModes[sSlot]) == "1" ? "1" : "0";
                aInputs[i].value = sValue;
                oEditor = window.tinymce && typeof window.tinymce.get == "function" ? window.tinymce.get("snippet-" + sSlot) : null;
                setSnippetBoardEditorPlainTextPasteMode(oEditor, sValue != "1");
            }
        } finally {
            blSnippetBoardApplyingRemote = false;
        }
    }

    function requestSnippetBoardAction(sAction, fSuccess, fError) {
        var oData = new FormData();
        oData.append("action", sAction);
        submitAdminRequest(oData, fSuccess, fError);
    }

    function canApplySnippetBoardRemote() {
        return !blChanged && !blSaving;
    }

    function broadcastSnippetBoardSaved() {
        if (!oBoardChannel || !sBoardRevision) {
            return;
        }
        try {
            oBoardChannel.postMessage({
                source: sBoardInstanceId,
                type: "saved",
                revision: sBoardRevision,
                snippets: getSnippetBoardValues(),
                richTextPasteModes: getSnippetBoardRichTextPasteModes()
            });
        } catch (oException) {
            logAdminException(oException);
        }
    }

    function loadSnippetBoardRemote(sExpectedRevision) {
        requestSnippetBoardAction("load_snippet_board", function(aData) {
            if (!canApplySnippetBoardRemote()) {
                setSnippetBoardStatus("Changed elsewhere.", "changed");
                return;
            }
            if (sExpectedRevision && aData.revision && aData.revision != sExpectedRevision) {
                setSnippetBoardRevision(aData.revision);
            } else if (aData.revision) {
                setSnippetBoardRevision(aData.revision);
            }
            applySnippetBoardValues(aData.snippets || {}, aData.richTextPasteModes || {});
            setSnippetBoardStatus("Updated.", "saved");
        }, function(sMessage) {
            logAdminException(sMessage);
        });
    }

    function handleSnippetBoardMessage(oEvent) {
        var aMessage = oEvent && oEvent.data ? oEvent.data : null;
        if (!aMessage || aMessage.source == sBoardInstanceId || aMessage.type != "saved") {
            return;
        }
        if (aMessage.revision && sBoardRevision && aMessage.revision <= sBoardRevision) {
            return;
        }
        if (!canApplySnippetBoardRemote()) {
            setSnippetBoardStatus("Changed elsewhere.", "changed");
            return;
        }
        setSnippetBoardRevision(aMessage.revision || "");
        applySnippetBoardValues(aMessage.snippets || {}, aMessage.richTextPasteModes || {});
        setSnippetBoardStatus("Updated.", "saved");
    }

    function openSnippetBoardChannel() {
        if (!window.BroadcastChannel) {
            return;
        }
        try {
            oBoardChannel = new BroadcastChannel("eved-lm-snippet-board");
            oBoardChannel.addEventListener("message", handleSnippetBoardMessage);
        } catch (oException) {
            logAdminException(oException);
        }
    }

    function scheduleSnippetBoardRevisionCheck(iDelay) {
        var iDelayMs = typeof iDelay == "number" ? iDelay : iRevisionPollMs;
        if (iRevisionTimer) {
            window.clearTimeout(iRevisionTimer);
            iRevisionTimer = null;
        }
        if (document.visibilityState == "hidden") {
            return;
        }
        iRevisionTimer = window.setTimeout(function() {
            iRevisionTimer = null;
            checkSnippetBoardRevision();
        }, iDelayMs);
    }

    function requestSnippetBoardRevisionCheck(blForce) {
        var iNow = new Date().getTime();
        var iRemaining;
        if (document.visibilityState == "hidden") {
            return;
        }
        if (!blForce && iLastRevisionRequestAt > 0 && iNow - iLastRevisionRequestAt < iRevisionPollMs) {
            iRemaining = iRevisionPollMs - (iNow - iLastRevisionRequestAt);
            scheduleSnippetBoardRevisionCheck(iRemaining);
            return;
        }
        if (iRevisionTimer) {
            window.clearTimeout(iRevisionTimer);
            iRevisionTimer = null;
        }
        checkSnippetBoardRevision();
    }

    function checkSnippetBoardRevision() {
        if (blRevisionRequest) {
            return;
        }
        if (document.visibilityState == "hidden") {
            return;
        }
        iLastRevisionRequestAt = new Date().getTime();
        blRevisionRequest = true;
        requestSnippetBoardAction("check_snippet_board_revision", function(aData) {
            blRevisionRequest = false;
            if (aData.revision && aData.revision != sBoardRevision) {
                if (canApplySnippetBoardRemote()) {
                    loadSnippetBoardRemote(aData.revision);
                } else {
                    setSnippetBoardStatus("Changed elsewhere.", "changed");
                }
            }
            scheduleSnippetBoardRevisionCheck();
        }, function(sMessage) {
            blRevisionRequest = false;
            logAdminException(sMessage);
            scheduleSnippetBoardRevisionCheck();
        });
    }

    function sendSnippetBoardBeacon() {
        var oData;
        if (!navigator.sendBeacon || !blChanged) {
            return false;
        }
        oData = collectSnippetBoardData();
        appendAdminCsrfToken(oData);
        try {
            if (navigator.sendBeacon(window.location.href, oData)) {
                blSaveAgain = false;
                setSnippetBoardStatus("Saving...", "saving");
                return true;
            }
        } catch (oException) {
            logAdminException(oException);
        }
        return false;
    }

    function saveSnippetBoardNow() {
        var oData;
        if (!blChanged && !blSaveAgain) {
            return;
        }
        if (iSaveTimer) {
            window.clearTimeout(iSaveTimer);
            iSaveTimer = null;
        }
        if (blSaving) {
            blSaveAgain = true;
            return;
        }
        blSaving = true;
        blSaveAgain = false;
        blChanged = false;
        setSnippetBoardStatus("Saving...", "saving");
        oData = collectSnippetBoardData();
        submitAdminRequest(oData, function(aData) {
            blSaving = false;
            if (blSaveAgain || blChanged) {
                saveSnippetBoardNow();
                return;
            }
            if (aData && aData.revision) {
                setSnippetBoardRevision(aData.revision);
            }
            broadcastSnippetBoardSaved();
            setSnippetBoardStatus("Saved.", "saved");
        }, function(sMessage) {
            blSaving = false;
            blChanged = true;
            setSnippetBoardStatus(sMessage || "Save failed.", "error");
        });
    }

    function scheduleSnippetBoardSave() {
        if (blSnippetBoardApplyingRemote) {
            return;
        }
        requestSnippetBoardRevisionCheck(false);
        blChanged = true;
        if (iSaveTimer) {
            window.clearTimeout(iSaveTimer);
        }
        setSnippetBoardStatus("Changed.", "changed");
        iSaveTimer = window.setTimeout(function() {
            iSaveTimer = null;
            saveSnippetBoardNow();
        }, iSaveDebounceMs);
    }

    function bindTextareaChanges() {
        var aTextareas = oForm.querySelectorAll(".js-snippet-board-textarea");
        var aInputs = oForm.querySelectorAll(".js-snippet-board-rich-text-copy-mode");
        var i;
        for (i = 0; i < aTextareas.length; i++) {
            aTextareas[i].addEventListener("input", scheduleSnippetBoardSave);
            aTextareas[i].addEventListener("change", scheduleSnippetBoardSave);
        }
        for (i = 0; i < aInputs.length; i++) {
            aInputs[i].addEventListener("input", scheduleSnippetBoardSave);
            aInputs[i].addEventListener("change", scheduleSnippetBoardSave);
        }
    }

    if (!oForm) {
        return;
    }
    if (isSnippetBoardLocked()) {
        return;
    }
    oForm.addEventListener("submit", function(oEvent) {
        oEvent.preventDefault();
        saveSnippetBoardNow();
    });
    window.addEventListener("beforeunload", function() {
        if (blChanged && !sendSnippetBoardBeacon()) {
            saveSnippetBoardNow();
        }
    });
    document.addEventListener("visibilitychange", function() {
        if (document.visibilityState == "hidden" && blChanged && !sendSnippetBoardBeacon()) {
            saveSnippetBoardNow();
        }
        if (document.visibilityState != "hidden") {
            if (blChanged) {
                saveSnippetBoardNow();
            } else {
                requestSnippetBoardRevisionCheck(true);
            }
        }
    });
    window.addEventListener("focus", function() {
        requestSnippetBoardRevisionCheck(true);
    });
    bindTextareaChanges();
    openSnippetBoardChannel();
    scheduleSnippetBoardRevisionCheck(iRevisionPollMs);
}

function bindLmEncryptionUnlock() {
    var oForm = document.querySelector(".js-lm-encryption-unlock-form");
    var oDialog = oForm && oForm.closest ? oForm.closest(".js-lm-encryption-unlock-dialog") : document.querySelector(".js-lm-encryption-unlock-dialog");
    var oInput = oForm ? oForm.querySelector("[name=\"lm_encryption_hash\"]") : null;
    var oConfirmInput = oForm ? oForm.querySelector("[name=\"lm_encryption_hash_confirm\"]") : null;
    var oError = oForm ? oForm.querySelector(".js-lm-encryption-unlock-error") : null;
    var oSubmit = oForm ? oForm.querySelector("button[type=\"submit\"]") : null;
    var oHeader = oForm ? oForm.querySelector(".confirm-dialog-header") : null;
    var blSubmitting = false;
    if (!oDialog || !oForm || !oInput) {
        return;
    }
    enableAdminDialogDrag(oDialog, oForm, oHeader);
    lockAdminModalScroll();
    focusAdminElement(oInput, true);
    oForm.addEventListener("submit", function(oEvent) {
        var oData;
        oEvent.preventDefault();
        if (blSubmitting) {
            return;
        }
        if (oError) {
            oError.hidden = true;
            oError.textContent = "";
        }
        if (oConfirmInput && (oInput.value || "") != (oConfirmInput.value || "")) {
            if (oError) {
                oError.textContent = "Hash confirmation does not match.";
                oError.hidden = false;
            }
            focusAdminElement(oConfirmInput, true);
            return;
        }
        blSubmitting = true;
        if (oSubmit) {
            oSubmit.disabled = true;
        }
        oData = new FormData();
        oData.append("action", "unlock_lm_encryption");
        oData.append("lm_encryption_hash", oInput.value || "");
        if (oConfirmInput) {
            oData.append("lm_encryption_hash_confirm", oConfirmInput.value || "");
        }
        submitAdminRequest(oData, function() {
            window.location.reload();
        }, function(sMessage) {
            blSubmitting = false;
            if (oSubmit) {
                oSubmit.disabled = false;
            }
            if (oError) {
                oError.textContent = sMessage || "Encrypted data could not be unlocked.";
                oError.hidden = false;
            }
            focusAdminElement(oInput, true);
        });
    });
}

function getSnippetBoardPlainTextContent(oEditor) {
    var sText = "";
    if (oEditor && oEditor.selection && typeof oEditor.selection.getContent == "function") {
        sText = oEditor.selection.getContent({format: "text"});
    }
    if (sText == "" && oEditor && typeof oEditor.getContent == "function") {
        sText = oEditor.getContent({format: "text"});
    }
    return sText;
}

function getSnippetBoardHtmlContent(oEditor) {
    var sHtml = "";
    if (oEditor && oEditor.selection && typeof oEditor.selection.getContent == "function") {
        sHtml = oEditor.selection.getContent({format: "html"});
    }
    if (sHtml == "" && oEditor && typeof oEditor.getContent == "function") {
        sHtml = oEditor.getContent({format: "html"});
    }
    return sHtml;
}

function copySnippetBoardPlainText(oEditor) {
    var sText = getSnippetBoardPlainTextContent(oEditor);
    var oTextarea;
    var blCopied = false;
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(sText).catch(function(oException) {
            logAdminException(oException);
            showSnippetBoardClipboardError(oEditor, "Plain text could not be copied.");
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
        showSnippetBoardClipboardError(oEditor, "Plain text could not be copied.");
    }
}

function showSnippetBoardClipboardError(oEditor, sMessage) {
    if (typeof openAdminConfirmDialog == "function" && document.getElementById("admin-reusable-dialog")) {
        openAdminConfirmDialog("Clipboard Error", sMessage, "OK", null, null, "Close");
        return;
    }
    if (oEditor && oEditor.notificationManager && typeof oEditor.notificationManager.open == "function") {
        oEditor.notificationManager.open({text: sMessage, type: "error"});
    }
}

function copySnippetBoardRichText(oEditor) {
    var sHtml = getSnippetBoardHtmlContent(oEditor);
    var sText = getSnippetBoardPlainTextContent(oEditor);
    var oItem;
    if (navigator.clipboard && navigator.clipboard.write && window.ClipboardItem) {
        oItem = new ClipboardItem({
            "text/html": new Blob([sHtml], {type: "text/html"}),
            "text/plain": new Blob([sText], {type: "text/plain"})
        });
        navigator.clipboard.write([oItem]).catch(function(oException) {
            logAdminException(oException);
            copySnippetBoardPlainText(oEditor);
        });
        return;
    }
    copySnippetBoardPlainText(oEditor);
}

function updateSnippetBoardToolbarLines(oEditor) {
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

function scheduleSnippetBoardToolbarLines(oEditor, iAttempt) {
    var iCurrentAttempt = typeof iAttempt == "number" ? iAttempt : 0;
    var iToken;
    function runToolbarLinesUpdate() {
        var blReady;
        if (oEditor._snippetBoardToolbarLineSchedule != iToken) {
            return;
        }
        blReady = updateSnippetBoardToolbarLines(oEditor);
        if (iCurrentAttempt < 6 || (!blReady && iCurrentAttempt < 20)) {
            window.setTimeout(function() {
                scheduleSnippetBoardToolbarLines(oEditor, iCurrentAttempt + 1);
            }, iCurrentAttempt < 6 ? 50 : 100);
        }
    }
    if (!oEditor) {
        return;
    }
    if (!iCurrentAttempt) {
        oEditor._snippetBoardToolbarLineSchedule = (oEditor._snippetBoardToolbarLineSchedule || 0) + 1;
    }
    iToken = oEditor._snippetBoardToolbarLineSchedule;
    if (window.requestAnimationFrame) {
        window.requestAnimationFrame(runToolbarLinesUpdate);
    } else {
        window.setTimeout(runToolbarLinesUpdate, 0);
    }
}

function bindSnippetBoardTinyMce() {
    if (isSnippetBoardLocked()) {
        return;
    }
    if (!window.tinymce || typeof window.tinymce.init != "function") {
        return;
    }
    try {
        window.tinymce.init({
            selector: "textarea.js-snippet-board-textarea",
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
                        copySnippetBoardRichText(oEditor);
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
                        setSnippetBoardEditorStoredRichTextPaste(oEditor, true);
                    },
                    onSetup: function(oApi) {
                        function updateFormattedPasteState() {
                            scheduleSnippetBoardPasteModeButtonSync(oEditor);
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
                        setSnippetBoardEditorStoredRichTextPaste(oEditor, false);
                    },
                    onSetup: function(oApi) {
                        function updatePasteTextState() {
                            scheduleSnippetBoardPasteModeButtonSync(oEditor);
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
                        copySnippetBoardPlainText(oEditor);
                    }
                });
                oEditor.on("focus click mousedown mouseup", function() {
                    closeMenus(null);
                });
                function syncSnippetBoardEditorChange() {
                    var oElement = oEditor.getElement();
                    var sValue;
                    if (!oElement) {
                        return;
                    }
                    sValue = oElement.value;
                    oEditor.save();
                    if (!blSnippetBoardApplyingRemote && oElement.value != sValue) {
                        dispatchAdminInputEvent(oElement);
                    }
                }
                function syncSnippetBoardEditorChangeAfterEvent() {
                    window.setTimeout(syncSnippetBoardEditorChange, 0);
                }
                oEditor.on("change keyup undo redo input", syncSnippetBoardEditorChange);
                oEditor.on("paste cut ExecCommand PastePostProcess SetContent", syncSnippetBoardEditorChangeAfterEvent);
                oEditor.on("init", function() {
                    bindSnippetBoardEditorScrollLock(oEditor);
                    applySnippetBoardStoredRichTextPaste(oEditor);
                    layoutSnippetBoard();
                    layoutMailForm();
                    scheduleSnippetBoardToolbarLines(oEditor);
                });
                oEditor.on("PostRender SkinLoaded ResizeEditor", function() {
                    scheduleSnippetBoardToolbarLines(oEditor);
                });
                window.addEventListener("resize", function() {
                    scheduleSnippetBoardToolbarLines(oEditor);
                });
            }
        });
    } catch (oException) {
        logAdminException(oException);
    }
}

function bindCharacterConverter() {
    var oForm = document.getElementById("character-converter-form");
    var oTextPresentationButton = document.getElementById("character-text-presentation");
    var oEmojiPresentationButton = document.getElementById("character-emoji-presentation");
    var oResetButton = document.getElementById("character-reset");
    var aCharacterPaletteButtons = document.querySelectorAll("[data-character-insert]");
    var oCharacterPaletteScroll = document.querySelector(".character-palette-scroll");
    var oCharacterPaletteTonePopup = null;
    var oCharacterPaletteTonePopupButton = null;
    var aInputs;
    var oFields = {};
    var oNamedEntities = {};
    var oNamedEntityCharacters = {};
    var oNamedEntityNames = {};
    var aEmojiPresentationCodePointRanges = [
        [0x0023, 0x0023], [0x002A, 0x002A], [0x0030, 0x0039], [0x00A9, 0x00A9],
        [0x00AE, 0x00AE], [0x203C, 0x203C], [0x2049, 0x2049], [0x2122, 0x2122],
        [0x2139, 0x2139], [0x2194, 0x2199], [0x21A9, 0x21AA], [0x231A, 0x231B],
        [0x2328, 0x2328], [0x23CF, 0x23CF], [0x23E9, 0x23F3], [0x23F8, 0x23FA],
        [0x24C2, 0x24C2], [0x25AA, 0x25AB], [0x25B6, 0x25B6], [0x25C0, 0x25C0],
        [0x25FB, 0x25FE], [0x2600, 0x2604], [0x260E, 0x260E], [0x2611, 0x2611],
        [0x2614, 0x2615], [0x2618, 0x2618], [0x261D, 0x261D], [0x2620, 0x2620],
        [0x2622, 0x2623], [0x2626, 0x2626], [0x262A, 0x262A], [0x262E, 0x262F],
        [0x2638, 0x263A], [0x2640, 0x2640], [0x2642, 0x2642], [0x2648, 0x2653],
        [0x265F, 0x2660], [0x2663, 0x2666], [0x2668, 0x2668], [0x267B, 0x267B],
        [0x267E, 0x267F], [0x2692, 0x2697], [0x2699, 0x2699], [0x269B, 0x269C],
        [0x26A0, 0x26A1], [0x26A7, 0x26A7], [0x26AA, 0x26AB], [0x26B0, 0x26B1],
        [0x26BD, 0x26BE], [0x26C4, 0x26C5], [0x26C8, 0x26C8], [0x26CE, 0x26CE],
        [0x26CF, 0x26D1], [0x26D3, 0x26D4], [0x26E9, 0x26EA], [0x26F0, 0x26F5],
        [0x26F7, 0x26FA], [0x26FD, 0x26FD], [0x2702, 0x2702], [0x2705, 0x2705],
        [0x2708, 0x270D], [0x270F, 0x270F], [0x2712, 0x2712], [0x2714, 0x2714],
        [0x2716, 0x2716], [0x271D, 0x271D], [0x2721, 0x2721], [0x2728, 0x2728],
        [0x2733, 0x2734], [0x2744, 0x2744], [0x2747, 0x2747], [0x274C, 0x274C],
        [0x274E, 0x274E], [0x2753, 0x2755], [0x2757, 0x2757], [0x2763, 0x2764],
        [0x2795, 0x2797], [0x27A1, 0x27A1], [0x27B0, 0x27B0], [0x27BF, 0x27BF],
        [0x2934, 0x2935], [0x2B05, 0x2B07], [0x2B1B, 0x2B1C], [0x2B50, 0x2B50],
        [0x2B55, 0x2B55], [0x3030, 0x3030], [0x303D, 0x303D], [0x3297, 0x3299],
        [0x1F004, 0x1F004], [0x1F0CF, 0x1F0CF], [0x1F170, 0x1F171], [0x1F17E, 0x1F17F],
        [0x1F202, 0x1F202], [0x1F21A, 0x1F21A], [0x1F22F, 0x1F22F], [0x1F237, 0x1F23A],
        [0x1F250, 0x1F251], [0x1F321, 0x1F321], [0x1F324, 0x1F32C], [0x1F336, 0x1F336],
        [0x1F37D, 0x1F37D], [0x1F396, 0x1F397], [0x1F399, 0x1F39B], [0x1F39E, 0x1F39F],
        [0x1F3CB, 0x1F3CE], [0x1F3D4, 0x1F3DF], [0x1F3F3, 0x1F3F5], [0x1F3F7, 0x1F3F7],
        [0x1F43F, 0x1F43F], [0x1F441, 0x1F441], [0x1F4FD, 0x1F4FD], [0x1F549, 0x1F54A],
        [0x1F56F, 0x1F570], [0x1F573, 0x1F579], [0x1F587, 0x1F587], [0x1F58A, 0x1F58D],
        [0x1F590, 0x1F590], [0x1F5A5, 0x1F5A5], [0x1F5A8, 0x1F5A8], [0x1F5B1, 0x1F5B2],
        [0x1F5BC, 0x1F5BC], [0x1F5C2, 0x1F5C4], [0x1F5D1, 0x1F5D3], [0x1F5DC, 0x1F5DE],
        [0x1F5E1, 0x1F5E1], [0x1F5E3, 0x1F5E3], [0x1F5E8, 0x1F5E8], [0x1F5EF, 0x1F5EF],
        [0x1F5F3, 0x1F5F3], [0x1F5FA, 0x1F5FA], [0x1F6CB, 0x1F6CB], [0x1F6CD, 0x1F6CF],
        [0x1F6E0, 0x1F6E5], [0x1F6E9, 0x1F6E9], [0x1F6F0, 0x1F6F0], [0x1F6F3, 0x1F6F3]
    ];
    var aEmojiPresentationSelectorCodePointRanges = [
        [0x0023, 0x0023], [0x002A, 0x002A], [0x0030, 0x0039], [0x00A9, 0x00A9],
        [0x00AE, 0x00AE], [0x203C, 0x203C], [0x2049, 0x2049], [0x2122, 0x2122],
        [0x2139, 0x2139], [0x2194, 0x2199], [0x21A9, 0x21AA], [0x2328, 0x2328],
        [0x23CF, 0x23CF], [0x23ED, 0x23EF], [0x23F1, 0x23F2], [0x23F8, 0x23FA],
        [0x24C2, 0x24C2], [0x25AA, 0x25AB], [0x25B6, 0x25B6], [0x25C0, 0x25C0],
        [0x25FB, 0x25FC], [0x2600, 0x2604], [0x260E, 0x260E], [0x2611, 0x2611],
        [0x2618, 0x2618], [0x261D, 0x261D], [0x2620, 0x2620], [0x2622, 0x2623],
        [0x2626, 0x2626], [0x262A, 0x262A], [0x262E, 0x262F], [0x2638, 0x263A],
        [0x2640, 0x2640], [0x2642, 0x2642], [0x265F, 0x2660], [0x2663, 0x2666],
        [0x2668, 0x2668], [0x267B, 0x267B], [0x267E, 0x267E], [0x2692, 0x2692],
        [0x2694, 0x2697], [0x2699, 0x2699], [0x269B, 0x269C], [0x26A0, 0x26A0],
        [0x26A7, 0x26A7], [0x26B0, 0x26B1], [0x26C8, 0x26C8], [0x26CF, 0x26D1],
        [0x26D3, 0x26D3], [0x26E9, 0x26E9], [0x26F0, 0x26F1], [0x26F4, 0x26F4],
        [0x26F7, 0x26F9], [0x2702, 0x2702], [0x2708, 0x2709], [0x270C, 0x270D],
        [0x270F, 0x270F], [0x2712, 0x2712], [0x2714, 0x2714], [0x2716, 0x2716],
        [0x271D, 0x271D], [0x2721, 0x2721], [0x2733, 0x2734], [0x2744, 0x2744],
        [0x2747, 0x2747], [0x2763, 0x2764], [0x27A1, 0x27A1], [0x2934, 0x2935],
        [0x2B05, 0x2B07], [0x3030, 0x3030], [0x303D, 0x303D], [0x3297, 0x3299],
        [0x1F170, 0x1F171], [0x1F17E, 0x1F17F], [0x1F202, 0x1F202], [0x1F237, 0x1F237],
        [0x1F321, 0x1F321], [0x1F324, 0x1F32C], [0x1F336, 0x1F336], [0x1F37D, 0x1F37D],
        [0x1F396, 0x1F397], [0x1F399, 0x1F39B], [0x1F39E, 0x1F39F], [0x1F3CB, 0x1F3CE],
        [0x1F3D4, 0x1F3DF], [0x1F3F3, 0x1F3F3], [0x1F3F5, 0x1F3F5], [0x1F3F7, 0x1F3F7],
        [0x1F43F, 0x1F43F], [0x1F441, 0x1F441], [0x1F4FD, 0x1F4FD], [0x1F549, 0x1F54A],
        [0x1F56F, 0x1F570], [0x1F573, 0x1F579], [0x1F587, 0x1F587], [0x1F58A, 0x1F58D],
        [0x1F590, 0x1F590], [0x1F5A5, 0x1F5A5], [0x1F5A8, 0x1F5A8], [0x1F5B1, 0x1F5B2],
        [0x1F5BC, 0x1F5BC], [0x1F5C2, 0x1F5C4], [0x1F5D1, 0x1F5D3], [0x1F5DC, 0x1F5DE],
        [0x1F5E1, 0x1F5E1], [0x1F5E3, 0x1F5E3], [0x1F5E8, 0x1F5E8], [0x1F5EF, 0x1F5EF],
        [0x1F5F3, 0x1F5F3], [0x1F5FA, 0x1F5FA], [0x1F6CB, 0x1F6CB], [0x1F6CD, 0x1F6CF],
        [0x1F6E0, 0x1F6E5], [0x1F6E9, 0x1F6E9], [0x1F6F0, 0x1F6F0], [0x1F6F3, 0x1F6F3]
    ];
    var oActiveInput = null;
    var blApplying = false;
    var iIndex;

    function fromCodePoint(iCodePoint) {
        var iValue = Number(iCodePoint);
        if (!isFinite(iValue) || iValue < 0 || iValue > 0x10FFFF || (iValue >= 0xD800 && iValue <= 0xDFFF)) {
            return "";
        }
        if (iValue <= 0xFFFF) {
            return String.fromCharCode(iValue);
        }
        iValue -= 0x10000;
        return String.fromCharCode(0xD800 + Math.floor(iValue / 0x400), 0xDC00 + (iValue % 0x400));
    }

    function codePointAt(sText, iPosition) {
        var iFirst = sText.charCodeAt(iPosition);
        var iSecond;
        if (iFirst >= 0xD800 && iFirst <= 0xDBFF && iPosition + 1 < sText.length) {
            iSecond = sText.charCodeAt(iPosition + 1);
            if (iSecond >= 0xDC00 && iSecond <= 0xDFFF) {
                return ((iFirst - 0xD800) * 0x400) + (iSecond - 0xDC00) + 0x10000;
            }
        }
        return iFirst;
    }

    function codePointsFromText(sText) {
        var aCodePoints = [];
        var iPosition;
        var iCodePoint;
        for (iPosition = 0; iPosition < sText.length; iPosition += 1) {
            iCodePoint = codePointAt(sText, iPosition);
            if (iCodePoint >= 0xD800 && iCodePoint <= 0xDFFF) {
                return null;
            }
            aCodePoints.push(iCodePoint);
            if (iCodePoint > 0xFFFF) {
                iPosition += 1;
            }
        }
        return aCodePoints;
    }

    function textFromCodePoints(aCodePoints) {
        var sResult = "";
        var iPosition;
        for (iPosition = 0; iPosition < aCodePoints.length; iPosition += 1) {
            sResult += fromCodePoint(aCodePoints[iPosition]);
        }
        return sResult;
    }

    function formatUnicodeCodeTitle(sText) {
        var aCodePoints = codePointsFromText(sText);
        var aCodes = [];
        var iPosition;
        var sCode;
        if (!aCodePoints) {
            return "";
        }
        for (iPosition = 0; iPosition < aCodePoints.length; iPosition += 1) {
            sCode = aCodePoints[iPosition].toString(16).toUpperCase();
            while (sCode.length < 4) {
                sCode = "0" + sCode;
            }
            aCodes.push("U+" + sCode);
        }
        return aCodes.join(" ");
    }

    function codePointIsInRanges(iCodePoint, aRanges) {
        var iRangeIndex;
        for (iRangeIndex = 0; iRangeIndex < aRanges.length; iRangeIndex += 1) {
            if (iCodePoint >= aRanges[iRangeIndex][0] && iCodePoint <= aRanges[iRangeIndex][1]) {
                return true;
            }
        }
        return false;
    }

    function convertEmojiPresentation(sText, blEmojiPresentation) {
        var aCodePoints = codePointsFromText(sText);
        var sResult = "";
        var iPosition;
        var iCodePoint;
        var iNextCodePoint;
        if (!aCodePoints) {
            return null;
        }
        for (iPosition = 0; iPosition < aCodePoints.length; iPosition += 1) {
            iCodePoint = aCodePoints[iPosition];
            iNextCodePoint = iPosition + 1 < aCodePoints.length ? aCodePoints[iPosition + 1] : 0;
            if (codePointIsInRanges(iCodePoint, aEmojiPresentationCodePointRanges)) {
                sResult += fromCodePoint(iCodePoint);
                if (iNextCodePoint == 0xFE0E || iNextCodePoint == 0xFE0F) {
                    iPosition += 1;
                }
                if (blEmojiPresentation && codePointIsInRanges(iCodePoint, aEmojiPresentationSelectorCodePointRanges)) {
                    sResult += fromCodePoint(0xFE0F);
                }
            } else {
                sResult += fromCodePoint(iCodePoint);
            }
        }
        return sResult;
    }

    function applyEmojiPresentation(blEmojiPresentation) {
        var sText;
        if (!oFields.text) {
            return;
        }
        sText = convertEmojiPresentation(oFields.text.value, blEmojiPresentation);
        if (sText === null) {
            return;
        }
        oActiveInput = oFields.text;
        setValues(sText, null);
    }

    function closeCharacterPaletteTonePopup() {
        if (oCharacterPaletteTonePopup && oCharacterPaletteTonePopup.parentNode) {
            oCharacterPaletteTonePopup.parentNode.removeChild(oCharacterPaletteTonePopup);
        }
        oCharacterPaletteTonePopup = null;
        oCharacterPaletteTonePopupButton = null;
    }

    function positionCharacterPaletteTonePopup() {
        var oRect;
        var iWidth;
        var iHeight;
        var iLeft;
        var iTop;
        if (!oCharacterPaletteTonePopup || !oCharacterPaletteTonePopupButton || !document.body.contains(oCharacterPaletteTonePopupButton)) {
            return;
        }
        oRect = oCharacterPaletteTonePopupButton.getBoundingClientRect();
        iWidth = oCharacterPaletteTonePopup.offsetWidth || 0;
        iHeight = oCharacterPaletteTonePopup.offsetHeight || 0;
        iLeft = Math.max(4, Math.min(oRect.left, document.documentElement.clientWidth - iWidth - 4));
        iTop = oRect.bottom + 2;
        if (iTop + iHeight > document.documentElement.clientHeight - 4) {
            iTop = oRect.top - iHeight - 2;
        }
        if (iTop < 4) {
            iTop = 4;
        }
        oCharacterPaletteTonePopup.style.left = iLeft + "px";
        oCharacterPaletteTonePopup.style.top = iTop + "px";
    }

    function showCharacterPaletteTonePopup(oButton, aCharacters, aTitles) {
        var iCharacterIndex;
        var oToneButton;
        closeCharacterPaletteTonePopup();
        if (!document.body || !aCharacters || aCharacters.length < 1) {
            return;
        }
        oCharacterPaletteTonePopup = document.createElement("div");
        oCharacterPaletteTonePopup.className = "character-palette-tone-popup";
        for (iCharacterIndex = 0; iCharacterIndex < aCharacters.length; iCharacterIndex += 1) {
            oToneButton = document.createElement("button");
            oToneButton.type = "button";
            oToneButton.className = "character-palette-button";
            oToneButton.setAttribute("data-character-tone", aCharacters[iCharacterIndex]);
            oToneButton.title = aTitles && aTitles[iCharacterIndex] ? aTitles[iCharacterIndex] : formatUnicodeCodeTitle(aCharacters[iCharacterIndex]);
            oToneButton.textContent = aCharacters[iCharacterIndex];
            oCharacterPaletteTonePopup.appendChild(oToneButton);
        }
        oCharacterPaletteTonePopup.addEventListener("click", function(oEvent) {
            var oClickedButton = oEvent.target.closest("[data-character-tone]");
            if (!oClickedButton || !oCharacterPaletteTonePopup.contains(oClickedButton)) {
                return;
            }
            oEvent.preventDefault();
            oEvent.stopPropagation();
            insertPaletteCharacter(oClickedButton.getAttribute("data-character-tone") || "");
        });
        oCharacterPaletteTonePopup.addEventListener("mousedown", function(oEvent) {
            oEvent.preventDefault();
        });
        document.body.appendChild(oCharacterPaletteTonePopup);
        oCharacterPaletteTonePopupButton = oButton;
        positionCharacterPaletteTonePopup();
    }

    function insertPaletteCharacter(sCharacter) {
        var iStart;
        var iEnd;
        var sText;
        if (!oFields.text || sCharacter == "") {
            return;
        }
        closeCharacterPaletteTonePopup();
        iStart = typeof oFields.text.selectionStart == "number" ? oFields.text.selectionStart : oFields.text.value.length;
        iEnd = typeof oFields.text.selectionEnd == "number" ? oFields.text.selectionEnd : iStart;
        sText = oFields.text.value.substring(0, iStart) + sCharacter + oFields.text.value.substring(iEnd);
        oFields.text.value = sText;
        oActiveInput = oFields.text;
        setValues(sText, null);
        oFields.text.focus();
        if (oFields.text.setSelectionRange) {
            oFields.text.setSelectionRange(iStart + sCharacter.length, iStart + sCharacter.length);
        }
    }

    function parseCodePoints(sValue, iRadix) {
        var sText = String(sValue).replace(/[,;]/g, " ").replace(/\s+/g, " ").replace(/^\s+|\s+$/g, "");
        var aTokens;
        var aCodePoints = [];
        var iPosition;
        var sToken;
        var iCodePoint;
        if (sText == "") {
            return [];
        }
        aTokens = sText.split(" ");
        for (iPosition = 0; iPosition < aTokens.length; iPosition += 1) {
            sToken = aTokens[iPosition];
            if (iRadix == 16) {
                sToken = sToken.replace(/^U\+/i, "").replace(/^0x/i, "");
                if (!/^[0-9A-F]+$/i.test(sToken)) {
                    return null;
                }
            } else if (!/^[0-9]+$/.test(sToken)) {
                return null;
            }
            iCodePoint = parseInt(sToken, iRadix);
            if (fromCodePoint(iCodePoint) == "") {
                return null;
            }
            aCodePoints.push(iCodePoint);
        }
        return aCodePoints;
    }

    function parseNumericEntityTokenCodePoint(sToken, iRadix) {
        var iCodePoint;
        sToken = String(sToken).replace(/^&/, "");
        if (/^#x[0-9A-F]+$/i.test(sToken)) {
            iCodePoint = parseInt(sToken.substring(2), 16);
        } else if (/^#[0-9]+$/.test(sToken)) {
            iCodePoint = parseInt(sToken.substring(1), 10);
        } else if (/^U\+[0-9A-F]+$/i.test(sToken)) {
            iCodePoint = parseInt(sToken.substring(2), 16);
        } else if (/^0x[0-9A-F]+$/i.test(sToken)) {
            iCodePoint = parseInt(sToken.substring(2), 16);
        } else if (iRadix == 16) {
            sToken = sToken.replace(/^x/i, "");
            if (!/^[0-9A-F]+$/i.test(sToken)) {
                return null;
            }
            iCodePoint = parseInt(sToken, 16);
        } else {
            if (!/^[0-9]+$/.test(sToken)) {
                return null;
            }
            iCodePoint = parseInt(sToken, 10);
        }
        return fromCodePoint(iCodePoint) == "" ? null : iCodePoint;
    }

    function parseNumericEntities(sValue, iRadix, blSubmit) {
        var sInput = "";
        var sToken = "";
        var sValueText = String(sValue);
        var aCodePoints = [];
        var iPosition;
        var sCharacter;
        var iCodePoint;
        var sEntityText;
        var blPendingAmpersand = false;
        function closeToken(sDelimiter) {
            var sSuffix = sDelimiter == "&" ? "&" : "";
            if (sToken == "") {
                if (sDelimiter == "&") {
                    blPendingAmpersand = true;
                }
                return true;
            }
            iCodePoint = parseNumericEntityTokenCodePoint(sToken, iRadix);
            if (iCodePoint === null) {
                return false;
            }
            aCodePoints.push(iCodePoint);
            sEntityText = iRadix == 16 ? "&#x" + iCodePoint.toString(16).toUpperCase() + ";" : "&#" + iCodePoint + ";";
            sInput += sInput.charAt(sInput.length - 1) == "&" && sEntityText.charAt(0) == "&" ? sEntityText.substring(1) + sSuffix : sEntityText + sSuffix;
            sToken = "";
            blPendingAmpersand = false;
            return true;
        }

        for (iPosition = 0; iPosition < sValueText.length; iPosition += 1) {
            sCharacter = sValueText.charAt(iPosition);
            if (sCharacter == "&") {
                if (!closeToken("&")) {
                    return null;
                }
            } else if (sCharacter == ";") {
                if (!closeToken(";")) {
                    return null;
                }
            } else if (/[\s,]/.test(sCharacter)) {
                if (!closeToken(" ")) {
                    return null;
                }
            } else {
                sToken += sCharacter;
            }
        }
        if (sToken != "") {
            if (blSubmit) {
                if (!closeToken("")) {
                    return null;
                }
            } else {
                sInput += (blPendingAmpersand ? "&" : "") + sToken;
            }
        } else if (blPendingAmpersand) {
            sInput += "&";
        }
        if (aCodePoints.length < 1 && blSubmit) {
            return null;
        }
        return {codePoints: aCodePoints, input: sInput};
    }

    function parseNamedEntityTokenText(sToken) {
        var iCodePoint;
        sToken = String(sToken).replace(/^&/, "");
        if (/^#x[0-9A-F]+$/i.test(sToken)) {
            iCodePoint = parseInt(sToken.substring(2), 16);
            return fromCodePoint(iCodePoint) == "" ? null : fromCodePoint(iCodePoint);
        }
        if (/^#[0-9]+$/.test(sToken)) {
            iCodePoint = parseInt(sToken.substring(1), 10);
            return fromCodePoint(iCodePoint) == "" ? null : fromCodePoint(iCodePoint);
        }
        if (/^U\+[0-9A-F]+$/i.test(sToken)) {
            iCodePoint = parseInt(sToken.substring(2), 16);
            return fromCodePoint(iCodePoint) == "" ? null : fromCodePoint(iCodePoint);
        }
        if (/^0x[0-9A-F]+$/i.test(sToken)) {
            iCodePoint = parseInt(sToken.substring(2), 16);
            return fromCodePoint(iCodePoint) == "" ? null : fromCodePoint(iCodePoint);
        }
        if (/^[0-9]+$/.test(sToken)) {
            iCodePoint = parseInt(sToken, 10);
            return fromCodePoint(iCodePoint) == "" ? null : fromCodePoint(iCodePoint);
        }
        if (/^[A-Za-z][A-Za-z0-9]*$/.test(sToken) && oNamedEntityNames[sToken]) {
            return oNamedEntityNames[sToken];
        }
        return null;
    }

    function parseNamedEntityText(sValue, blSubmit) {
        var sText = "";
        var sInput = "";
        var sToken = "";
        var sValueText = String(sValue);
        var iPosition;
        var sCharacter;
        var sTokenText;
        var sEntityText;
        var blPendingAmpersand = false;
        function closeToken(sDelimiter) {
            var sSuffix = "";
            if (sDelimiter == "&") {
                sSuffix = "&";
            }
            if (sToken == "") {
                if (sDelimiter == "&") {
                    blPendingAmpersand = true;
                }
                return true;
            }
            sTokenText = parseNamedEntityTokenText(sToken);
            if (sTokenText === null || sTokenText == "") {
                return false;
            }
            sText += sTokenText;
            sEntityText = namedEntityFromText(sTokenText);
            sInput += sInput.charAt(sInput.length - 1) == "&" && sEntityText.charAt(0) == "&" ? sEntityText.substring(1) + sSuffix : sEntityText + sSuffix;
            sToken = "";
            blPendingAmpersand = false;
            return true;
        }

        for (iPosition = 0; iPosition < sValueText.length; iPosition += 1) {
            sCharacter = sValueText.charAt(iPosition);
            if (sCharacter == "&") {
                if (!closeToken("&")) {
                    return null;
                }
            } else if (sCharacter == ";") {
                if (!closeToken(";")) {
                    return null;
                }
            } else if (/[\s,]/.test(sCharacter)) {
                if (!closeToken(" ")) {
                    return null;
                }
            } else {
                sToken += sCharacter;
            }
        }
        if (sToken != "") {
            if (blSubmit) {
                if (!closeToken("")) {
                    return null;
                }
            } else {
                sInput += (blPendingAmpersand ? "&" : "") + sToken;
            }
        } else if (blPendingAmpersand) {
            sInput += "&";
        }
        if (sText == "") {
            if (blSubmit) {
                return null;
            }
        }
        return {text: sText, input: sInput};
    }

    function namedEntityFromText(sText) {
        var aCodePoints;
        var sResult = "";
        var sCharacter;
        var iPosition;
        if (oNamedEntities[sText]) {
            return oNamedEntities[sText];
        }
        aCodePoints = codePointsFromText(sText);
        if (!aCodePoints || aCodePoints.length < 1) {
            return "";
        }
        for (iPosition = 0; iPosition < aCodePoints.length; iPosition += 1) {
            sCharacter = fromCodePoint(aCodePoints[iPosition]);
            sResult += oNamedEntities[sCharacter] ? oNamedEntities[sCharacter] : "&#" + aCodePoints[iPosition] + ";";
        }
        return sResult;
    }

    function setValues(sText, oExcept) {
        var aCodePoints = codePointsFromText(sText);
        var aDecimals = [];
        var aHexadecimals = [];
        var aDecimalEntities = [];
        var aHexadecimalEntities = [];
        var iPosition;
        if (!aCodePoints) {
            return false;
        }
        for (iPosition = 0; iPosition < aCodePoints.length; iPosition += 1) {
            aDecimals.push(String(aCodePoints[iPosition]));
            aHexadecimals.push(aCodePoints[iPosition].toString(16).toUpperCase());
            aDecimalEntities.push("&#" + aCodePoints[iPosition] + ";");
            aHexadecimalEntities.push("&#x" + aCodePoints[iPosition].toString(16).toUpperCase() + ";");
        }
        if (oFields.text !== oExcept) {
            oFields.text.value = sText;
        }
        if (oFields.decimal !== oExcept) {
            oFields.decimal.value = aDecimals.join(" ");
        }
        if (oFields.hexadecimal !== oExcept) {
            oFields.hexadecimal.value = aHexadecimals.join(" ");
        }
        if (oFields["decimal-entity"] !== oExcept) {
            oFields["decimal-entity"].value = aDecimalEntities.join("");
        }
        if (oFields["hexadecimal-entity"] !== oExcept) {
            oFields["hexadecimal-entity"].value = aHexadecimalEntities.join("");
        }
        if (oFields["named-entity"] !== oExcept) {
            oFields["named-entity"].value = namedEntityFromText(sText);
        }
        return true;
    }

    function clearValues(oExcept) {
        var iFieldIndex;
        for (iFieldIndex = 0; iFieldIndex < aInputs.length; iFieldIndex += 1) {
            if (aInputs[iFieldIndex] !== oExcept) {
                aInputs[iFieldIndex].value = "";
            }
        }
    }

    function applyInput(oInput, blSubmit) {
        var sField = oInput.getAttribute("data-character-converter-field");
        var sValue = oInput.value;
        var aCodePoints = null;
        var sText = "";
        var sEntityInputValue = "";
        var sNamedInputValue = "";
        var oEntityResult;
        var oNamedResult;
        if (sValue == "") {
            clearValues(oInput);
            return;
        }
        if (sField == "text") {
            sText = sValue;
        } else if (sField == "decimal") {
            aCodePoints = parseCodePoints(sValue, 10);
        } else if (sField == "hexadecimal") {
            aCodePoints = parseCodePoints(sValue, 16);
        } else if (sField == "decimal-entity" || sField == "hexadecimal-entity") {
            oEntityResult = parseNumericEntities(sValue, sField == "hexadecimal-entity" ? 16 : 10, blSubmit);
            if (oEntityResult === null) {
                return;
            }
            aCodePoints = oEntityResult.codePoints;
            sEntityInputValue = oEntityResult.input;
            if (aCodePoints.length < 1) {
                if (!blSubmit && sEntityInputValue != sValue) {
                    oInput.value = sEntityInputValue;
                }
                return;
            }
        } else if (sField == "named-entity") {
            oNamedResult = parseNamedEntityText(sValue, blSubmit);
            if (oNamedResult === null) {
                return;
            }
            sText = oNamedResult.text;
            sNamedInputValue = oNamedResult.input;
            if (sText == "") {
                if (!blSubmit && sNamedInputValue != sValue) {
                    oInput.value = sNamedInputValue;
                }
                return;
            }
        }
        if (aCodePoints === null && sText == "") {
            return;
        }
        if (aCodePoints) {
            sText = textFromCodePoints(aCodePoints);
        }
        if (sText == "") {
            return;
        }
        if (!setValues(sText, blSubmit ? null : oInput)) {
            return;
        }
        if (!blSubmit && sField == "named-entity" && sNamedInputValue != "") {
            oInput.value = sNamedInputValue;
        } else if (!blSubmit && (sField == "decimal-entity" || sField == "hexadecimal-entity") && sEntityInputValue != "") {
            oInput.value = sEntityInputValue;
        }
    }

    if (!oForm) {
        return;
    }
    aInputs = oForm.querySelectorAll("[data-character-converter-field]");
    for (iIndex = 0; iIndex < aInputs.length; iIndex += 1) {
        oFields[aInputs[iIndex].getAttribute("data-character-converter-field")] = aInputs[iIndex];
    }
    aInputs = oForm.querySelectorAll("#character-named-entities option");
    for (iIndex = 0; iIndex < aInputs.length; iIndex += 1) {
        if (aInputs[iIndex].value != "" && aInputs[iIndex].getAttribute("data-entity") != "" && !oNamedEntities[aInputs[iIndex].value]) {
            oNamedEntities[aInputs[iIndex].value] = aInputs[iIndex].getAttribute("data-entity");
            oNamedEntityCharacters[aInputs[iIndex].getAttribute("data-entity")] = aInputs[iIndex].value;
            oNamedEntityNames[aInputs[iIndex].getAttribute("data-entity").substring(1, aInputs[iIndex].getAttribute("data-entity").length - 1)] = aInputs[iIndex].value;
        }
    }
    aInputs = oForm.querySelectorAll("[data-character-converter-field]");
    for (iIndex = 0; iIndex < aInputs.length; iIndex += 1) {
        aInputs[iIndex].addEventListener("focus", function(oEvent) {
            oActiveInput = oEvent.target;
            window.setTimeout(layoutCharacterConverter, 0);
            window.setTimeout(layoutCharacterConverter, 250);
        });
        aInputs[iIndex].addEventListener("blur", function() {
            window.setTimeout(layoutCharacterConverter, 0);
            window.setTimeout(layoutCharacterConverter, 250);
        });
        aInputs[iIndex].addEventListener("input", function(oEvent) {
            if (blApplying) {
                return;
            }
            blApplying = true;
            applyInput(oEvent.target, false);
            blApplying = false;
        });
    }
    if (oTextPresentationButton) {
        oTextPresentationButton.addEventListener("click", function() {
            applyEmojiPresentation(false);
        });
    }
    if (oEmojiPresentationButton) {
        oEmojiPresentationButton.addEventListener("click", function() {
            applyEmojiPresentation(true);
        });
    }
    if (oResetButton) {
        oResetButton.addEventListener("click", function() {
            clearValues(null);
            closeCharacterPaletteTonePopup();
            oActiveInput = null;
            if (oFields.text) {
                oFields.text.focus();
            }
        });
    }
    for (iIndex = 0; iIndex < aCharacterPaletteButtons.length; iIndex += 1) {
        aCharacterPaletteButtons[iIndex].addEventListener("click", function(oEvent) {
            var sCharacterVariants = oEvent.currentTarget.getAttribute("data-character-variants");
            var sCharacterVariantTitles = oEvent.currentTarget.getAttribute("data-character-variant-titles");
            var aCharacterVariants = null;
            var aCharacterVariantTitles = null;
            if (sCharacterVariants) {
                try {
                    aCharacterVariants = JSON.parse(sCharacterVariants);
                } catch (oException) {
                    aCharacterVariants = null;
                }
                if (aCharacterVariants && aCharacterVariants.length > 0) {
                    if (sCharacterVariantTitles) {
                        try {
                            aCharacterVariantTitles = JSON.parse(sCharacterVariantTitles);
                        } catch (oException) {
                            aCharacterVariantTitles = null;
                        }
                    }
                    oEvent.preventDefault();
                    oEvent.stopPropagation();
                    showCharacterPaletteTonePopup(oEvent.currentTarget, aCharacterVariants, aCharacterVariantTitles);
                    return;
                }
            }
            insertPaletteCharacter(oEvent.currentTarget.getAttribute("data-character-insert"));
        });
    }
    document.addEventListener("click", function(oEvent) {
        if (oCharacterPaletteTonePopup && !oCharacterPaletteTonePopup.contains(oEvent.target) && (!oEvent.target.closest || !oEvent.target.closest("[data-character-variants]"))) {
            closeCharacterPaletteTonePopup();
        }
    });
    document.addEventListener("keydown", function(oEvent) {
        if (oEvent.key == "Escape") {
            closeCharacterPaletteTonePopup();
        }
    });
    if (oCharacterPaletteScroll) {
        oCharacterPaletteScroll.addEventListener("scroll", positionCharacterPaletteTonePopup);
    }
    window.addEventListener("resize", function() {
        positionCharacterPaletteTonePopup();
    });
    if (window.visualViewport) {
        window.visualViewport.addEventListener("resize", positionCharacterPaletteTonePopup);
        window.visualViewport.addEventListener("scroll", positionCharacterPaletteTonePopup);
    }
    oForm.addEventListener("submit", function(oEvent) {
        var oInput = oActiveInput;
        var iSubmitIndex;
        oEvent.preventDefault();
        if (!oInput || !oInput.getAttribute("data-character-converter-field")) {
            for (iSubmitIndex = 0; iSubmitIndex < aInputs.length; iSubmitIndex += 1) {
                if (aInputs[iSubmitIndex].value != "") {
                    oInput = aInputs[iSubmitIndex];
                    break;
                }
            }
        }
        if (oInput) {
            blApplying = true;
            applyInput(oInput, true);
            blApplying = false;
        }
    });
}

function bindFancyTextConverter() {
    var oInput = document.getElementById("fancy-text-input");
    var oOutput = document.getElementById("fancy-text-output");
    var oStyle = document.getElementById("fancy-text-style");
    var oConvert = document.querySelector(".js-fancy-text-convert");
    var sLatinUpper = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    var sLatinLower = "abcdefghijklmnopqrstuvwxyz";
    var sDigits = "0123456789";
    var sGreekUpper = "ΑΒΓΔΕΖΗΘΙΚΛΜΝΞΟΠΡϴΣΤΥΦΧΨΩ∇";
    var sGreekLower = "αβγδεζηθικλμνξοπρςστυφχψω∂ϵϑϰϕϱϖ";
    var oScriptUpperExceptions = {
        B: 0x212C,
        E: 0x2130,
        F: 0x2131,
        H: 0x210B,
        I: 0x2110,
        L: 0x2112,
        M: 0x2133,
        R: 0x211B
    };
    var oScriptLowerExceptions = {
        e: 0x212F,
        g: 0x210A,
        o: 0x2134
    };
    var oFrakturUpperExceptions = {
        C: 0x212D,
        H: 0x210C,
        I: 0x2111,
        R: 0x211C,
        Z: 0x2128
    };
    var oDoubleStruckUpperExceptions = {
        C: 0x2102,
        H: 0x210D,
        N: 0x2115,
        P: 0x2119,
        Q: 0x211A,
        R: 0x211D,
        Z: 0x2124
    };
    var oStyles = {
        "plain": {},
        "bold": {upper: 0x1D400, lower: 0x1D41A, digit: 0x1D7CE, greekUpper: 0x1D6A8, greekLower: 0x1D6C2},
        "italic": {upper: 0x1D434, lower: 0x1D44E, lowerExceptions: {h: 0x210E}, greekUpper: 0x1D6E2, greekLower: 0x1D6FC},
        "bold-italic": {upper: 0x1D468, lower: 0x1D482, greekUpper: 0x1D71C, greekLower: 0x1D736},
        "script": {upper: 0x1D49C, lower: 0x1D4B6, upperExceptions: oScriptUpperExceptions, lowerExceptions: oScriptLowerExceptions},
        "script-chancery": {upper: 0x1D49C, lower: 0x1D4B6, upperExceptions: oScriptUpperExceptions, lowerExceptions: oScriptLowerExceptions, upperVariationSelector: 0xFE00},
        "script-roundhand": {upper: 0x1D49C, lower: 0x1D4B6, upperExceptions: oScriptUpperExceptions, lowerExceptions: oScriptLowerExceptions, upperVariationSelector: 0xFE01},
        "bold-script": {upper: 0x1D4D0, lower: 0x1D4EA},
        "fraktur": {upper: 0x1D504, lower: 0x1D51E, upperExceptions: oFrakturUpperExceptions},
        "double-struck": {upper: 0x1D538, lower: 0x1D552, digit: 0x1D7D8, upperExceptions: oDoubleStruckUpperExceptions},
        "bold-fraktur": {upper: 0x1D56C, lower: 0x1D586},
        "sans-serif": {upper: 0x1D5A0, lower: 0x1D5BA, digit: 0x1D7E2},
        "sans-serif-bold": {upper: 0x1D5D4, lower: 0x1D5EE, digit: 0x1D7EC, greekUpper: 0x1D756, greekLower: 0x1D770},
        "sans-serif-italic": {upper: 0x1D608, lower: 0x1D622},
        "sans-serif-bold-italic": {upper: 0x1D63C, lower: 0x1D656, greekUpper: 0x1D790, greekLower: 0x1D7AA},
        "monospace": {upper: 0x1D670, lower: 0x1D68A, digit: 0x1D7F6}
    };
    var oReverseCharacters = null;

    function fromFancyTextCodePoint(iCodePoint) {
        var iValue = Number(iCodePoint);
        if (!isFinite(iValue) || iValue < 0 || iValue > 0x10FFFF || (iValue >= 0xD800 && iValue <= 0xDFFF)) {
            return "";
        }
        if (iValue <= 0xFFFF) {
            return String.fromCharCode(iValue);
        }
        iValue -= 0x10000;
        return String.fromCharCode(0xD800 + Math.floor(iValue / 0x400), 0xDC00 + (iValue % 0x400));
    }

    function addFancyTextReverseCharacter(oMap, sStyled, sPlain) {
        if (sStyled != "" && typeof oMap[sStyled] == "undefined") {
            oMap[sStyled] = sPlain;
        }
    }

    function addFancyTextReverseRange(oMap, sPlainCharacters, iBaseCodePoint, oExceptions, iVariationSelector) {
        var i;
        var sPlain;
        var sStyled;
        if (!iBaseCodePoint) {
            return;
        }
        for (i = 0; i < sPlainCharacters.length; i++) {
            sPlain = sPlainCharacters.charAt(i);
            sStyled = fromFancyTextCodePoint(oExceptions && oExceptions[sPlain] ? oExceptions[sPlain] : iBaseCodePoint + i);
            addFancyTextReverseCharacter(oMap, sStyled, sPlain);
            if (sStyled != "" && iVariationSelector) {
                addFancyTextReverseCharacter(oMap, sStyled + fromFancyTextCodePoint(iVariationSelector), sPlain);
            }
        }
    }

    function buildFancyTextReverseCharacters() {
        var oMap = {};
        var sStyle;
        var oStyleData;
        for (sStyle in oStyles) {
            if (Object.prototype.hasOwnProperty.call(oStyles, sStyle)) {
                oStyleData = oStyles[sStyle];
                addFancyTextReverseRange(oMap, sLatinUpper, oStyleData.upper, oStyleData.upperExceptions, oStyleData.upperVariationSelector);
                addFancyTextReverseRange(oMap, sLatinLower, oStyleData.lower, oStyleData.lowerExceptions, 0);
                addFancyTextReverseRange(oMap, sDigits, oStyleData.digit, null, 0);
                addFancyTextReverseRange(oMap, sGreekUpper, oStyleData.greekUpper, null, 0);
                addFancyTextReverseRange(oMap, sGreekLower, oStyleData.greekLower, null, 0);
            }
        }
        return oMap;
    }

    function getFancyTextCharacter(sCharacter, oStyleData) {
        var iIndex;
        var sMapped;
        if (!oStyleData) {
            return sCharacter;
        }
        iIndex = sLatinUpper.indexOf(sCharacter);
        if (iIndex >= 0 && oStyleData.upper) {
            sMapped = fromFancyTextCodePoint(oStyleData.upperExceptions && oStyleData.upperExceptions[sCharacter] ? oStyleData.upperExceptions[sCharacter] : oStyleData.upper + iIndex);
            if (sMapped != "" && oStyleData.upperVariationSelector) {
                sMapped += fromFancyTextCodePoint(oStyleData.upperVariationSelector);
            }
            return sMapped || sCharacter;
        }
        iIndex = sLatinLower.indexOf(sCharacter);
        if (iIndex >= 0 && oStyleData.lower) {
            return fromFancyTextCodePoint(oStyleData.lowerExceptions && oStyleData.lowerExceptions[sCharacter] ? oStyleData.lowerExceptions[sCharacter] : oStyleData.lower + iIndex) || sCharacter;
        }
        iIndex = sDigits.indexOf(sCharacter);
        if (iIndex >= 0 && oStyleData.digit) {
            return fromFancyTextCodePoint(oStyleData.digit + iIndex) || sCharacter;
        }
        iIndex = sGreekUpper.indexOf(sCharacter);
        if (iIndex >= 0 && oStyleData.greekUpper) {
            return fromFancyTextCodePoint(oStyleData.greekUpper + iIndex) || sCharacter;
        }
        iIndex = sGreekLower.indexOf(sCharacter);
        if (iIndex >= 0 && oStyleData.greekLower) {
            return fromFancyTextCodePoint(oStyleData.greekLower + iIndex) || sCharacter;
        }
        return sCharacter;
    }

    function transformFancyText(sText, sStyle) {
        var oStyleData = oStyles[sStyle] || oStyles.plain;
        var sResult = "";
        var iPosition;
        var iNextPosition;
        var iNextCodePoint;
        var iCodePoint;
        var sCharacter;
        var sNextCharacter;
        if (!oReverseCharacters) {
            oReverseCharacters = buildFancyTextReverseCharacters();
        }
        for (iPosition = 0; iPosition < sText.length; iPosition += 1) {
            iCodePoint = sText.charCodeAt(iPosition);
            if (iCodePoint >= 0xD800 && iCodePoint <= 0xDBFF && iPosition + 1 < sText.length) {
                iCodePoint = sText.charCodeAt(iPosition + 1);
                if (iCodePoint >= 0xDC00 && iCodePoint <= 0xDFFF) {
                    sCharacter = sText.substring(iPosition, iPosition + 2);
                    iPosition += 1;
                } else {
                    sCharacter = sText.charAt(iPosition);
                }
            } else {
                sCharacter = sText.charAt(iPosition);
            }
            iNextPosition = iPosition + 1;
            sNextCharacter = "";
            if (iNextPosition < sText.length) {
                iNextCodePoint = sText.charCodeAt(iNextPosition);
                if (iNextCodePoint >= 0xFE00 && iNextCodePoint <= 0xFE0F) {
                    sNextCharacter = sText.charAt(iNextPosition);
                }
            }
            if (sNextCharacter != "" && typeof oReverseCharacters[sCharacter + sNextCharacter] != "undefined") {
                sCharacter = oReverseCharacters[sCharacter + sNextCharacter];
                iPosition = iNextPosition;
            } else if (typeof oReverseCharacters[sCharacter] != "undefined") {
                sCharacter = oReverseCharacters[sCharacter];
            }
            sResult += getFancyTextCharacter(sCharacter, oStyleData);
        }
        return sResult;
    }

    function refreshFancyTextOutput() {
        oOutput.value = transformFancyText(oInput.value, oStyle.value);
    }

    if (!oInput || !oOutput || !oStyle) {
        return;
    }
    oInput.addEventListener("input", refreshFancyTextOutput);
    oStyle.addEventListener("change", refreshFancyTextOutput);
    if (oConvert) {
        oConvert.addEventListener("click", refreshFancyTextOutput);
    }
    refreshFancyTextOutput();
}

document.addEventListener("DOMContentLoaded", function () {
    var aUserAgents = document.querySelectorAll(".js-user-agent");
    var iUserAgentIndex = 0;
    var iUserAgentChunkSize = 40;
    if (!window.bowser || typeof window.bowser.parse != "function") {
        return;
    }

    function scheduleUserAgentChunk(oCallback) {
        if (window.requestAnimationFrame) {
            window.requestAnimationFrame(oCallback);
        } else if (window.setTimeout) {
            window.setTimeout(oCallback, 0);
        } else {
            oCallback();
        }
    }

    function formatUserAgent(oElement) {
        var sUserAgent = oElement.getAttribute("data-user-agent") || "";
        var oResult = null;
        var aParts = [];
        var sBrowserName = oElement.getAttribute("data-browser-name") || "";
        var sBrowserVersion = oElement.getAttribute("data-browser-version") || "";
        var sOsName = oElement.getAttribute("data-os-name") || "";
        var sOsVersion = oElement.getAttribute("data-os-version") || "";
        var sPlatform = oElement.getAttribute("data-platform-type") || "";
        var sDeviceVendor = oElement.getAttribute("data-device-vendor") || "";
        var sDeviceModel = oElement.getAttribute("data-device-model") || "";
        var sBrowser;
        var sOperatingSystem;
        var sDevice;
        if (!sBrowserName || !sOsName || !sPlatform) {
            try {
                oResult = window.bowser.parse(sUserAgent);
            } catch (oException) {
                console.error(oException);
                logAdminException(oException);
                return sUserAgent;
            }
        }
        if (!sBrowserName && oResult && oResult.browser && oResult.browser.name) {
            sBrowserName = oResult.browser.name;
        }
        if (!sBrowserVersion && oResult && oResult.browser && oResult.browser.version) {
            sBrowserVersion = oResult.browser.version;
        }
        if (!sOsName && oResult && oResult.os && oResult.os.name) {
            sOsName = oResult.os.name;
        }
        if (!sOsVersion && oResult && oResult.os) {
            sOsVersion = oResult.os.versionName || oResult.os.version || "";
        }
        if (!sPlatform && oResult && oResult.platform && oResult.platform.type) {
            sPlatform = oResult.platform.type;
        }
        if (!sDeviceVendor && oResult && oResult.platform && oResult.platform.vendor) {
            sDeviceVendor = oResult.platform.vendor;
        }
        if (!sDeviceModel && oResult && oResult.platform && oResult.platform.model) {
            sDeviceModel = oResult.platform.model;
        }
        sBrowser = (sBrowserName + " " + sBrowserVersion).trim();
        sOperatingSystem = (sOsName + " " + sOsVersion).trim();
        sDevice = (sDeviceVendor + " " + sDeviceModel).trim();
        if (sBrowser) {
            aParts.push(sBrowser);
        }
        if (sOperatingSystem) {
            aParts.push(sOperatingSystem);
        }
        if (sPlatform) {
            sPlatform = sPlatform.charAt(0).toUpperCase() + sPlatform.slice(1);
            aParts.push(sPlatform);
        }
        if (sDevice) {
            if (aParts.indexOf(sDevice) === -1) {
                aParts.push(sDevice);
            }
        }
        return aParts.length ? aParts.join(" / ") : sUserAgent;
    }

    function processUserAgentChunk() {
        var iEnd = Math.min(iUserAgentIndex + iUserAgentChunkSize, aUserAgents.length);
        var sValue;
        for (; iUserAgentIndex < iEnd; iUserAgentIndex += 1) {
            sValue = formatUserAgent(aUserAgents[iUserAgentIndex]);
            if (aUserAgents[iUserAgentIndex].textContent != sValue) {
                aUserAgents[iUserAgentIndex].textContent = sValue;
            }
        }
        if (iUserAgentIndex < aUserAgents.length) {
            scheduleUserAgentChunk(processUserAgentChunk);
        }
    }

    processUserAgentChunk();
});

document.addEventListener("keydown", function(oEvent) {
    if (oEvent.key == "Escape") {
        closeMenus(null);
        closeAdminDialog();
    }
});

document.addEventListener("DOMContentLoaded", function() {
    setupMenu();
    setupTableRows();
    setupFilterFocusButton();
    setupTableFilter();
    setupAutoRefresh();
    bindAdminCopyLinks();
    bindAdminContactCopy();
    bindMenuAdmin();
    bindEmailOverview();
    bindDashboardServices();
    bindBusinessHours();
    layoutBusinessHours();
    bindIssueTracker();
    bindPhoneAccounts();
    bindAdminSubmitOnChange();
    bindCharacterConverter();
    bindFancyTextConverter();
    bindMailForm();
    bindSnippetBoardPageScrollLock();
    bindSbPmdHoverTimeout();
    bindSnippetBoardTabs();
    bindLmEncryptionUnlock();
    bindSnippetBoardForm();
    bindSnippetBoardTinyMce();
    layoutSnippetBoard();
    layoutMailForm();
    layoutCharacterConverter();
    window.addEventListener("resize", layoutBusinessHours);
    window.addEventListener("resize", layoutSnippetBoard);
    window.addEventListener("resize", layoutMailForm);
    window.addEventListener("resize", layoutCharacterConverter);
    if (window.visualViewport) {
        window.visualViewport.addEventListener("resize", layoutBusinessHours);
        window.visualViewport.addEventListener("resize", layoutSnippetBoard);
        window.visualViewport.addEventListener("resize", layoutMailForm);
        window.visualViewport.addEventListener("resize", layoutCharacterConverter);
        window.visualViewport.addEventListener("scroll", layoutCharacterConverter);
    }
});
