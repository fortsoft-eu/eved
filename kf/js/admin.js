(function () {
    "use strict";

    var iAdminModalCount = 0;
    var sAdminBodyOverflow = "";
    var iAdminScrollLeft = 0;
    var iAdminScrollTop = 0;
    var oAdminOpenDialog = null;

    function logAdminException(oException) {
        if (window.console && window.console.error) {
            window.console.error(oException);
        }
    }

    function getAdminCsrfToken() {
        var oMeta = document.querySelector("meta[name=\"csrf-token\"]");
        return oMeta ? (oMeta.getAttribute("content") || "") : "";
    }

    function appendAdminCsrfToken(oData) {
        var sToken = getAdminCsrfToken();
        if (oData && sToken) {
            oData.append("kf_csrf_token", sToken);
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

    function setAdminDialogError(oError, sMessage) {
        if (!oError) {
            return;
        }
        oError.textContent = sMessage || "";
        oError.style.display = sMessage ? "" : "none";
    }

    function refreshAdminTableFilter() {
        var aFilters = document.querySelectorAll(".js-table-filter");
        var oEvent;
        for (var iI = 0; iI < aFilters.length; iI += 1) {
            if (typeof Event == "function") {
                oEvent = new Event("input");
            } else {
                oEvent = document.createEvent("Event");
                oEvent.initEvent("input", true, true);
            }
            aFilters[iI].dispatchEvent(oEvent);
        }
    }

    function closeMenu(oMenu) {
        var oButton = oMenu ? oMenu.querySelector("[data-menu-button]") : null;
        var oPanel = oMenu ? oMenu.querySelector("[data-menu-panel]") : null;
        if (oPanel) {
            oPanel.hidden = true;
        }
        if (oButton) {
            oButton.setAttribute("aria-expanded", "false");
        }
    }

    function setupMenu() {
        var aMenus = document.querySelectorAll("[data-menu]");
        for (var iI = 0; iI < aMenus.length; iI += 1) {
            (function (oMenu) {
                var oButton = oMenu.querySelector("[data-menu-button]");
                var oPanel = oMenu.querySelector("[data-menu-panel]");
                if (!oButton || !oPanel) {
                    return;
                }
                oButton.addEventListener("click", function (oEvent) {
                    oEvent.preventDefault();
                    oEvent.stopPropagation();
                    oPanel.hidden = !oPanel.hidden;
                    oButton.setAttribute("aria-expanded", oPanel.hidden ? "false" : "true");
                });
            })(aMenus[iI]);
        }
        document.addEventListener("click", function (oEvent) {
            if (oEvent.target.closest && oEvent.target.closest("[data-menu]")) {
                return;
            }
            for (var iI = 0; iI < aMenus.length; iI += 1) {
                closeMenu(aMenus[iI]);
            }
        });
    }

    function setupMessages() {
        var aMessages = document.querySelectorAll(".message-box");
        if (!aMessages.length) {
            return;
        }
        window.setTimeout(function () {
            for (var iI = 0; iI < aMessages.length; iI += 1) {
                aMessages[iI].style.display = "none";
            }
        }, 10000);
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
        focusElement(oOk);
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
                focusElement(oFilter, true);
                window.scrollTo(iScrollLeft, 0);
            }, 0);
        });
    }

    function setupTableFilter() {
        var aFilters = document.querySelectorAll(".js-table-filter");

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
            });
        }

        for (var iI = 0; iI < aFilters.length; iI += 1) {
            initializeTableFilter(aFilters[iI]);
        }
    }

    function setFieldValue(oForm, sName, sValue) {
        var aFields = oForm.querySelectorAll("[name=\"" + sName + "\"], [name=\"" + sName + "[]\"]");
        for (var iI = 0; iI < aFields.length; iI += 1) {
            if (aFields[iI].type == "checkbox") {
                var aValues = String(sValue || "").split(",");
                aFields[iI].checked = aValues.indexOf(aFields[iI].value) !== -1;
            } else {
                aFields[iI].value = sValue || "";
            }
        }
    }

    function refreshConditionalFields(oForm) {
        var oKind = oForm.querySelector("[name=\"type_kind\"]");
        var aGroupFields = oForm.querySelectorAll("[data-visible-for-kind]");
        for (var iI = 0; iI < aGroupFields.length; iI += 1) {
            aGroupFields[iI].hidden = !oKind || aGroupFields[iI].getAttribute("data-visible-for-kind") != oKind.value;
        }
    }

    function isTextSelectionField(oElement) {
        var sTag = oElement && oElement.tagName ? oElement.tagName.toLowerCase() : "";
        var sType;
        if (!oElement || oElement.disabled) {
            return false;
        }
        if (sTag != "input") {
            return false;
        }
        sType = (oElement.getAttribute("type") || "text").toLowerCase();
        return sType == "text" || sType == "password" || sType == "search" || sType == "email" || sType == "url" || sType == "tel" || sType == "number";
    }

    function selectTextField(oElement) {
        if (!isTextSelectionField(oElement)) {
            return;
        }
        try {
            oElement.select();
        } catch (oException) {
            console.error(oException);
        }
        if (typeof oElement.setSelectionRange == "function") {
            try {
                oElement.setSelectionRange(0, (oElement.value || "").length);
            } catch (oException) {
                console.error(oException);
            }
        }
    }

    function focusElement(oElement, blSelectText) {
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
            console.error(oException);
            oElement.focus();
        }
        window.scrollTo(iScrollLeft, iScrollTop);
        if (blSelectText === true && isTextSelectionField(oElement)) {
            selectTextField(oElement);
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

    function bindSubjectSuggestInput(oInput) {
        var oForm = oInput ? oInput.form : null;
        var sIdFieldName = oInput ? (oInput.getAttribute("data-subject-id-field") || "") : "";
        var oIdField = oForm && sIdFieldName ? oForm.querySelector("[name=\"" + sIdFieldName + "\"]") : null;
        var oList = oInput ? document.getElementById(oInput.getAttribute("list") || "") : null;
        var iMinLength = oInput ? parseInt(oInput.getAttribute("data-subject-min-length") || "3", 10) : 3;
        var iTimer = 0;
        var iRequestIndex = 0;
        var aSubjectIds = {};
        if (!window.XMLHttpRequest || !window.FormData || !window.JSON || !oInput || !oForm || !oIdField || !oList || oInput.getAttribute("data-subject-suggest-bound") == "1") {
            return;
        }
        oInput.setAttribute("data-subject-suggest-bound", "1");
        if (isNaN(iMinLength) || iMinLength < 1) {
            iMinLength = 3;
        }

        function hideList() {
            oList.innerHTML = "";
            aSubjectIds = {};
        }

        function selectSubjectByName(sSubjectName) {
            if (!aSubjectIds[sSubjectName]) {
                return false;
            }
            oIdField.value = aSubjectIds[sSubjectName];
            hideList();
            return true;
        }

        function renderSuggestions(aSubjects) {
            oList.innerHTML = "";
            aSubjectIds = {};
            if (!aSubjects || !aSubjects.length) {
                hideList();
                return;
            }
            for (var iJ = 0; iJ < aSubjects.length; iJ += 1) {
                var sSubjectName = aSubjects[iJ].subject_name || "";
                var oOption = document.createElement("option");
                oOption.value = sSubjectName;
                oOption.setAttribute("data-subject-id", aSubjects[iJ].subject_id || "");
                aSubjectIds[sSubjectName] = aSubjects[iJ].subject_id || "";
                oList.appendChild(oOption);
            }
        }

        function requestSuggestions(sTerm) {
            var oRequest = new XMLHttpRequest();
            var oData = new FormData();
            var iCurrentRequest = iRequestIndex;
            oData.append("action", "suggest_subjects");
            oData.append("term", sTerm);
            appendAdminCsrfToken(oData);
            oRequest.onreadystatechange = function () {
                var aData;
                if (oRequest.readyState != 4 || iCurrentRequest != iRequestIndex) {
                    return;
                }
                if (oRequest.status != 200) {
                    hideList();
                    return;
                }
                try {
                    aData = JSON.parse(oRequest.responseText);
                } catch (oException) {
                    logAdminException(oException);
                    hideList();
                    return;
                }
                renderSuggestions(aData && aData.success ? aData.subjects : []);
            };
            oRequest.open("POST", window.location.href, true);
            oRequest.setRequestHeader("X-Requested-With", "XMLHttpRequest");
            oRequest.send(oData);
        }

        oInput.addEventListener("input", function () {
            var sTerm = oInput.value.replace(/^\s+|\s+$/g, "");
            iRequestIndex += 1;
            if (iTimer) {
                window.clearTimeout(iTimer);
            }
            if (selectSubjectByName(sTerm)) {
                return;
            }
            oIdField.value = "";
            if (sTerm.length < iMinLength) {
                hideList();
                return;
            }
            iTimer = window.setTimeout(function () {
                requestSuggestions(sTerm);
            }, 200);
        });
        oInput.addEventListener("change", function () {
            if (oInput.value.replace(/^\s+|\s+$/g, "") == "") {
                oIdField.value = "";
            } else {
                selectSubjectByName(oInput.value);
            }
        });
        oInput.addEventListener("keydown", function (oEvent) {
            if (oEvent.key == "Escape") {
                hideList();
            }
        });
        oForm.addEventListener("submit", function () {
            if (oInput.value.replace(/^\s+|\s+$/g, "") == "") {
                oIdField.value = "";
            } else if (aSubjectIds[oInput.value]) {
                oIdField.value = aSubjectIds[oInput.value];
            }
        });
        document.addEventListener("click", function (oEvent) {
            var oTarget = oEvent.target;
            if (oTarget && oTarget.closest && oTarget.closest("[data-subject-suggest]")) {
                return;
            }
            hideList();
        });
    }

    function setupSubjectSuggest() {
        var aInputs = document.querySelectorAll("[data-subject-suggest]");
        for (var iI = 0; iI < aInputs.length; iI += 1) {
            bindSubjectSuggestInput(aInputs[iI]);
        }
    }

    function setupSettingsDialog() {
        var oOpen = document.querySelector(".js-index-settings-open");
        var oDialog = document.getElementById("index-settings-dialog");
        var oBox = oDialog ? oDialog.querySelector(".confirm-dialog-box") : null;
        var oHeader = oDialog ? oDialog.querySelector(".confirm-dialog-header") : null;
        var oClose = oDialog ? oDialog.querySelector(".js-index-settings-close") : null;
        var oCancel = oDialog ? oDialog.querySelector(".js-index-settings-cancel") : null;
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
                    "disabled": aInputs[iI].disabled
                });
            }
        }

        function restoreCheckboxStates() {
            var aInputs = oDialog ? oDialog.querySelectorAll("input[type=\"checkbox\"]") : [];
            for (var iI = 0; iI < aInputs.length && iI < aSavedCheckboxStates.length; iI += 1) {
                aInputs[iI].checked = aSavedCheckboxStates[iI].checked;
                aInputs[iI].disabled = aSavedCheckboxStates[iI].disabled;
            }
        }

        function openDialog() {
            if (!oDialog) {
                return;
            }
            rememberCheckboxStates();
            if (!openAdminDialogElement(oDialog, closeDialog)) {
                return;
            }
            document.addEventListener("keydown", closeOnEscape);
            focusElement(findFirstAdminUserInput(oDialog), true);
        }

        function closeDialog() {
            if (!oDialog || oDialog.hidden) {
                return;
            }
            document.removeEventListener("keydown", closeOnEscape);
            restoreCheckboxStates();
            closeAdminDialogElement(oDialog);
            focusElement(oOpen);
        }

        if (!oOpen || !oDialog) {
            return;
        }
        if (oBox && oHeader) {
            enableAdminDialogDrag(oDialog, oBox, oHeader);
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
    }

    function setupModals() {
        var aOpeners = document.querySelectorAll("[data-modal-target]");
        var aModals = document.querySelectorAll(".confirm-dialog");
        var oDebtsTable = document.getElementById("debts-table");
        var oAddDebt = document.querySelector(".js-add-debt");
        var oTransactionsTable = document.getElementById("transactions-table");
        var oAddTransaction = document.querySelector(".js-add-transaction");
        var oSubscriptionsTable = document.getElementById("subscriptions-table");
        var oAddSubscription = document.querySelector(".js-add-subscription");
        var oTypesTable = document.getElementById("types-table");
        var oAddType = document.querySelector(".js-add-type");
        var oBox;
        var oHeader;

        function findAdminDebtRowById(sDebtId) {
            return sDebtId && oDebtsTable ? oDebtsTable.querySelector("tbody tr[data-debt-id=\"" + sDebtId + "\"]") : null;
        }

        function getDebtMovementElement(oButton) {
            return oButton && oButton.closest ? oButton.closest(".debt-movement") : null;
        }

        function findAdminTransactionRowById(sTransactionId) {
            return sTransactionId && oTransactionsTable ? oTransactionsTable.querySelector("tbody tr[data-transaction-id=\"" + sTransactionId + "\"]") : null;
        }

        function findAdminSubscriptionRowById(sSubscriptionId) {
            return sSubscriptionId && oSubscriptionsTable ? oSubscriptionsTable.querySelector("tbody tr[data-subscription-id=\"" + sSubscriptionId + "\"]") : null;
        }

        function findAdminTypeRowById(sTypeId) {
            return sTypeId && oTypesTable ? oTypesTable.querySelector("tbody tr[data-type-id=\"" + sTypeId + "\"]") : null;
        }

        function getDebtRowStates() {
            var aRows = oDebtsTable ? oDebtsTable.querySelectorAll("tbody tr[data-debt-id]") : [];
            var aStates = {};
            var sDebtId;
            for (var iI = 0; iI < aRows.length; iI += 1) {
                sDebtId = aRows[iI].getAttribute("data-debt-id") || "";
                if (sDebtId !== "") {
                    aStates[sDebtId] = aRows[iI];
                }
            }
            return aStates;
        }

        function restoreDebtRowStates(aStates) {
            var aRows = oDebtsTable ? oDebtsTable.querySelectorAll("tbody tr[data-debt-id]") : [];
            var sDebtId;
            for (var iI = 0; iI < aRows.length; iI += 1) {
                sDebtId = aRows[iI].getAttribute("data-debt-id") || "";
                if (sDebtId !== "" && aStates[sDebtId] && window.copyAdminTableRowState) {
                    window.copyAdminTableRowState(aStates[sDebtId], aRows[iI]);
                }
                if (window.bindAdminTableRow) {
                    window.bindAdminTableRow(aRows[iI]);
                }
            }
        }

        function replaceDebtRows(sRowsHtml) {
            var oBody = document.createElement("tbody");
            var aStates = getDebtRowStates();
            if (!oDebtsTable || !oDebtsTable.querySelector("tbody")) {
                return;
            }
            oBody.innerHTML = sRowsHtml || "";
            oDebtsTable.querySelector("tbody").innerHTML = oBody.innerHTML;
            restoreDebtRowStates(aStates);
            refreshAdminTableFilter();
        }

        function removeDebtRow(iDebtId) {
            var oCurrentRow = findAdminDebtRowById(iDebtId);
            if (oCurrentRow && oCurrentRow.parentNode) {
                oCurrentRow.parentNode.removeChild(oCurrentRow);
                refreshAdminTableFilter();
            }
        }

        function getTransactionRowStates() {
            var aRows = oTransactionsTable ? oTransactionsTable.querySelectorAll("tbody tr[data-transaction-id]") : [];
            var aStates = {};
            var sTransactionId;
            for (var iI = 0; iI < aRows.length; iI += 1) {
                sTransactionId = aRows[iI].getAttribute("data-transaction-id") || "";
                if (sTransactionId !== "") {
                    aStates[sTransactionId] = aRows[iI];
                }
            }
            return aStates;
        }

        function restoreTransactionRowStates(aStates) {
            var aRows = oTransactionsTable ? oTransactionsTable.querySelectorAll("tbody tr[data-transaction-id]") : [];
            var sTransactionId;
            for (var iI = 0; iI < aRows.length; iI += 1) {
                sTransactionId = aRows[iI].getAttribute("data-transaction-id") || "";
                if (sTransactionId !== "" && aStates[sTransactionId] && window.copyAdminTableRowState) {
                    window.copyAdminTableRowState(aStates[sTransactionId], aRows[iI]);
                }
                if (window.bindAdminTableRow) {
                    window.bindAdminTableRow(aRows[iI]);
                }
            }
        }

        function replaceTransactionRows(sRowsHtml) {
            var oBody = document.createElement("tbody");
            var aStates = getTransactionRowStates();
            if (!oTransactionsTable || !oTransactionsTable.querySelector("tbody")) {
                return;
            }
            oBody.innerHTML = sRowsHtml || "";
            oTransactionsTable.querySelector("tbody").innerHTML = oBody.innerHTML;
            restoreTransactionRowStates(aStates);
            refreshAdminTableFilter();
        }

        function removeTransactionRow(iTransactionId) {
            var oCurrentRow = findAdminTransactionRowById(iTransactionId);
            if (oCurrentRow && oCurrentRow.parentNode) {
                oCurrentRow.parentNode.removeChild(oCurrentRow);
                refreshAdminTableFilter();
            }
        }

        function getSubscriptionRowStates() {
            var aRows = oSubscriptionsTable ? oSubscriptionsTable.querySelectorAll("tbody tr[data-subscription-id]") : [];
            var aStates = {};
            var sSubscriptionId;
            for (var iI = 0; iI < aRows.length; iI += 1) {
                sSubscriptionId = aRows[iI].getAttribute("data-subscription-id") || "";
                if (sSubscriptionId !== "") {
                    aStates[sSubscriptionId] = aRows[iI];
                }
            }
            return aStates;
        }

        function restoreSubscriptionRowStates(aStates) {
            var aRows = oSubscriptionsTable ? oSubscriptionsTable.querySelectorAll("tbody tr[data-subscription-id]") : [];
            var sSubscriptionId;
            for (var iI = 0; iI < aRows.length; iI += 1) {
                sSubscriptionId = aRows[iI].getAttribute("data-subscription-id") || "";
                if (sSubscriptionId !== "" && aStates[sSubscriptionId] && window.copyAdminTableRowState) {
                    window.copyAdminTableRowState(aStates[sSubscriptionId], aRows[iI]);
                }
                if (window.bindAdminTableRow) {
                    window.bindAdminTableRow(aRows[iI]);
                }
            }
        }

        function replaceSubscriptionRows(sRowsHtml) {
            var oBody = document.createElement("tbody");
            var aStates = getSubscriptionRowStates();
            if (!oSubscriptionsTable || !oSubscriptionsTable.querySelector("tbody")) {
                return;
            }
            oBody.innerHTML = sRowsHtml || "";
            oSubscriptionsTable.querySelector("tbody").innerHTML = oBody.innerHTML;
            restoreSubscriptionRowStates(aStates);
            refreshAdminTableFilter();
        }

        function removeSubscriptionRow(iSubscriptionId) {
            var oCurrentRow = findAdminSubscriptionRowById(iSubscriptionId);
            if (oCurrentRow && oCurrentRow.parentNode) {
                oCurrentRow.parentNode.removeChild(oCurrentRow);
                refreshAdminTableFilter();
            }
        }

        function getTypeRowStates() {
            var aRows = oTypesTable ? oTypesTable.querySelectorAll("tbody tr[data-type-id]") : [];
            var aStates = {};
            var sTypeId;
            for (var iI = 0; iI < aRows.length; iI += 1) {
                sTypeId = aRows[iI].getAttribute("data-type-id") || "";
                if (sTypeId !== "") {
                    aStates[sTypeId] = aRows[iI];
                }
            }
            return aStates;
        }

        function restoreTypeRowStates(aStates) {
            var aRows = oTypesTable ? oTypesTable.querySelectorAll("tbody tr[data-type-id]") : [];
            var sTypeId;
            for (var iI = 0; iI < aRows.length; iI += 1) {
                sTypeId = aRows[iI].getAttribute("data-type-id") || "";
                if (sTypeId !== "" && aStates[sTypeId] && window.copyAdminTableRowState) {
                    window.copyAdminTableRowState(aStates[sTypeId], aRows[iI]);
                }
                if (window.bindAdminTableRow) {
                    window.bindAdminTableRow(aRows[iI]);
                }
            }
        }

        function replaceTypeRows(sRowsHtml) {
            var oBody = document.createElement("tbody");
            var aStates = getTypeRowStates();
            if (!oTypesTable || !oTypesTable.querySelector("tbody")) {
                return;
            }
            oBody.innerHTML = sRowsHtml || "";
            oTypesTable.querySelector("tbody").innerHTML = oBody.innerHTML;
            restoreTypeRowStates(aStates);
            refreshAdminTableFilter();
        }

        function removeTypeRow(iTypeId) {
            var oCurrentRow = findAdminTypeRowById(iTypeId);
            if (oCurrentRow && oCurrentRow.parentNode) {
                oCurrentRow.parentNode.removeChild(oCurrentRow);
                refreshAdminTableFilter();
            }
        }

        function getAdminModalRow(oElement) {
            return oElement && oElement.closest ? oElement.closest("tr[data-debt-id], tr[data-transaction-id], tr[data-subscription-id], tr[data-type-id]") : null;
        }

        function getCurrentAdminModalRow(oButton, oRow) {
            var sId = oButton ? (oButton.getAttribute("data-field-id") || "") : "";
            var sClass = oButton ? (" " + oButton.className + " ") : "";
            if (sClass.indexOf(" js-edit-debt ") !== -1 || sClass.indexOf(" js-delete-debt ") !== -1 || sClass.indexOf(" js-add-debt-movement ") !== -1 || sClass.indexOf(" js-edit-debt-movement ") !== -1 || sClass.indexOf(" js-delete-debt-movement ") !== -1) {
                return findAdminDebtRowById(sId) || oRow;
            }
            if (sClass.indexOf(" js-edit-transaction ") !== -1) {
                return findAdminTransactionRowById(sId) || oRow;
            }
            if (sClass.indexOf(" js-edit-subscription ") !== -1) {
                return findAdminSubscriptionRowById(sId) || oRow;
            }
            if (sClass.indexOf(" js-edit-type ") !== -1) {
                return findAdminTypeRowById(sId) || oRow;
            }
            return oRow;
        }

        function getAdminActionModalId(oButton) {
            var sClass = oButton ? (" " + oButton.className + " ") : "";
            if (sClass.indexOf(" js-edit-debt ") !== -1) {
                return "debt-modal";
            }
            if (sClass.indexOf(" js-delete-debt ") !== -1) {
                return "debt-delete-modal";
            }
            if (sClass.indexOf(" js-edit-transaction ") !== -1) {
                return "transaction-modal";
            }
            if (sClass.indexOf(" js-edit-subscription ") !== -1) {
                return "subscription-modal";
            }
            if (sClass.indexOf(" js-edit-type ") !== -1) {
                return "type-modal";
            }
            return oButton ? (oButton.getAttribute("data-modal-target") || "") : "";
        }

        function closeModalFromElement(oModal) {
            if (!oModal) {
                return;
            }
            if (oModal._adminDialogClose) {
                oModal._adminDialogClose();
            } else {
                closeAdminDialogElement(oModal);
            }
        }

        function openModalFromButton(oButton, oRow) {
            var oModal = document.getElementById(getAdminActionModalId(oButton));
            var oForm = oModal ? oModal.querySelector("form") : null;
            var sTitle = oButton.getAttribute("data-modal-title");
            var oSourceRow = oRow || getAdminModalRow(oButton);
            var blClosed = false;
            var closeDialog;
            if (!oModal || !oForm) {
                return;
            }
            oForm.reset();
            Array.prototype.forEach.call(oButton.attributes, function (oAttr) {
                if (oAttr.name.indexOf("data-field-") === 0) {
                    setFieldValue(oForm, oAttr.name.substring(11), oAttr.value);
                }
            });
            refreshConditionalFields(oForm);
            Array.prototype.forEach.call(document.querySelectorAll("datalist[data-subject-suggest-list]"), function (oList) {
                oList.innerHTML = "";
            });
            if (sTitle) {
                oModal.querySelector("[data-modal-heading]").textContent = sTitle;
            }
            closeDialog = function (blSaved) {
                if (blClosed) {
                    return;
                }
                blClosed = true;
                finishAdminSubjectRowEdit(getCurrentAdminModalRow(oButton, oSourceRow), blSaved === true);
                closeAdminDialogElement(oModal);
            };
            if (!openAdminDialogElement(oModal, closeDialog)) {
                return;
            }
            beginAdminSubjectRowEdit(getCurrentAdminModalRow(oButton, oSourceRow));
            focusElement(oForm.querySelector("[data-modal-focus]") || oForm.querySelector("input:not([type=\"hidden\"]), select"), true);
        }

        function createDebtDialog(sTitle, oDebtRow) {
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
            oDialogData.debtRow = oDebtRow || null;
            oDialogData.debtId = oDebtRow ? (oDebtRow.getAttribute("data-debt-id") || "") : "";
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
                finishAdminSubjectRowEdit(findAdminDebtRowById(oDialogData.debtId) || oDialogData.debtRow, blSaved === true);
                closeAdminDialogElement(oDialogData.dialog);
                focusElement(oAddDebt);
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

        function appendDebtHiddenField(oParent, sName, sValue) {
            var oInput = document.createElement("input");
            oInput.type = "hidden";
            oInput.name = sName;
            oInput.value = sValue || "";
            oParent.appendChild(oInput);
            return oInput;
        }

        function appendDebtTextField(oParent, sLabel, sName, sValue) {
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

        function appendDebtDateField(oParent, sLabel, sName, sValue) {
            var oLabel = document.createElement("label");
            var oInput = document.createElement("input");
            oLabel.textContent = sLabel;
            oInput.type = "date";
            oInput.name = sName;
            oInput.value = sValue || "";
            oInput.required = true;
            oParent.appendChild(oLabel);
            oParent.appendChild(oInput);
            return oInput;
        }

        function appendDebtSubjectField(oParent, oRow) {
            var oLabel = document.createElement("label");
            var oInput = document.createElement("input");
            var oList = document.createElement("datalist");
            appendDebtHiddenField(oParent, "ex_subjects_id", oRow ? (oRow.getAttribute("data-ex-subjects-id") || "") : "");
            oLabel.textContent = "Subject";
            oInput.type = "text";
            oInput.name = "subject_name";
            oInput.value = oRow ? (oRow.getAttribute("data-subject-name") || "") : "";
            oInput.setAttribute("list", "debt-subject-list");
            oInput.setAttribute("data-subject-suggest", "1");
            oInput.setAttribute("data-subject-id-field", "ex_subjects_id");
            oInput.setAttribute("data-subject-min-length", "3");
            oInput.required = true;
            oList.id = "debt-subject-list";
            oList.setAttribute("data-subject-suggest-list", "1");
            oParent.appendChild(oLabel);
            oParent.appendChild(oInput);
            oParent.appendChild(oList);
            bindSubjectSuggestInput(oInput);
            return oInput;
        }

        function finishDebtDialog(oDialogData, oFocus) {
            oDialogData.form.appendChild(oDialogData.error);
            oDialogData.actions.appendChild(oDialogData.save);
            oDialogData.actions.appendChild(oDialogData.cancel);
            oDialogData.form.appendChild(oDialogData.actions);
            oDialogData.dialog.appendChild(oDialogData.form);
            if (!openAdminDialogElement(oDialogData.dialog, oDialogData.close)) {
                return;
            }
            beginAdminSubjectRowEdit(findAdminDebtRowById(oDialogData.debtId) || oDialogData.debtRow);
            focusElement(findFirstAdminUserInput(oDialogData.form) || oFocus, true);
        }

        function submitDebtDialog(oDialogData, oData) {
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
                    setAdminDialogError(oDialogData.error, aData && aData.message ? aData.message : (oDialogData.errorMessage || "Debt could not be saved."));
                    oDialogData.save.disabled = false;
                    return;
                }
                if (aData.rows_html) {
                    replaceDebtRows(aData.rows_html);
                } else if (aData.debt_deleted) {
                    removeDebtRow(aData.debt_id);
                }
                oDialogData.close(true);
            }).catch(function (oException) {
                logAdminException(oException);
                setAdminDialogError(oDialogData.error, oDialogData.errorMessage || "Debt could not be saved.");
                oDialogData.save.disabled = false;
            });
        }

        function openDebtAdminDialog(oRow) {
            var blNewDebt = !oRow;
            var oDialogData = createDebtDialog(blNewDebt ? "New Debt" : "Edit Debt", oRow);
            var oSubject;
            var oMovementDate;
            var oAmount;
            var oNote;
            var oMovementNote;
            if (!oDialogData) {
                return;
            }
            appendDebtHiddenField(oDialogData.form, "id", blNewDebt ? "" : (oRow.getAttribute("data-debt-id") || ""));
            oSubject = appendDebtSubjectField(oDialogData.form, oRow);
            oNote = appendDebtTextField(oDialogData.form, "Note", "note", oRow ? (oRow.getAttribute("data-note") || "") : "");
            if (blNewDebt) {
                oMovementDate = appendDebtDateField(oDialogData.form, "Movement Date", "movement_date", new Date().toISOString().slice(0, 10));
                oAmount = appendDebtTextField(oDialogData.form, "Amount", "amount", "");
                oAmount.required = true;
                oMovementNote = appendDebtTextField(oDialogData.form, "Movement Note", "movement_note", "");
            }
            oDialogData.form.addEventListener("submit", function (oEvent) {
                var oData = new FormData();
                oEvent.preventDefault();
                oData.append("action", "save_debt");
                oData.append("id", blNewDebt ? "" : (oRow.getAttribute("data-debt-id") || ""));
                oData.append("ex_subjects_id", oDialogData.form.querySelector("[name=\"ex_subjects_id\"]").value || "");
                oData.append("subject_name", oSubject.value);
                oData.append("note", oNote.value);
                if (blNewDebt) {
                    oData.append("movement_date", oMovementDate.value);
                    oData.append("amount", oAmount.value);
                    oData.append("movement_note", oMovementNote.value);
                }
                submitDebtDialog(oDialogData, oData);
            });
            finishDebtDialog(oDialogData, oSubject);
        }

        function openDebtMovementAdminDialog(oRow, oMovement) {
            var blNewMovement = !oMovement;
            var oDialogData = createDebtDialog(blNewMovement ? "New Debt Movement" : "Edit Debt Movement", oRow);
            var oDate;
            var oAmount;
            var oNote;
            if (!oRow || !oDialogData) {
                return;
            }
            oDialogData.errorMessage = "Debt movement could not be saved.";
            appendDebtHiddenField(oDialogData.form, "id", blNewMovement ? "" : (oMovement.getAttribute("data-debt-movement-id") || ""));
            appendDebtHiddenField(oDialogData.form, "debt_id", oRow.getAttribute("data-debt-id") || "");
            oDate = appendDebtDateField(oDialogData.form, "Date", "movement_date", blNewMovement ? new Date().toISOString().slice(0, 10) : (oMovement.getAttribute("data-movement-date") || ""));
            oAmount = appendDebtTextField(oDialogData.form, "Amount", "amount", blNewMovement ? "" : (oMovement.getAttribute("data-amount") || ""));
            oAmount.required = true;
            oNote = appendDebtTextField(oDialogData.form, "Note", "note", blNewMovement ? "" : (oMovement.getAttribute("data-note") || ""));
            oDialogData.form.addEventListener("submit", function (oEvent) {
                var oData = new FormData();
                oEvent.preventDefault();
                oData.append("action", "save_debt_movement");
                oData.append("id", blNewMovement ? "" : (oMovement.getAttribute("data-debt-movement-id") || ""));
                oData.append("debt_id", oRow.getAttribute("data-debt-id") || "");
                oData.append("movement_date", oDate.value);
                oData.append("amount", oAmount.value);
                oData.append("note", oNote.value);
                submitDebtDialog(oDialogData, oData);
            });
            finishDebtDialog(oDialogData, oDate);
        }

        function openDebtMovementDeleteDialog(oRow, oMovement) {
            var oDialogData = createDebtDialog("Confirm Deletion", oRow);
            var oText = document.createElement("p");
            if (!oRow || !oMovement) {
                return;
            }
            if (!oDialogData) {
                return;
            }
            oDialogData.errorMessage = "Debt movement could not be deleted.";
            oDialogData.save.textContent = "Yes";
            oDialogData.cancel.textContent = "No";
            oText.textContent = "Delete this debt movement?";
            oDialogData.form.appendChild(oText);
            oDialogData.form.addEventListener("submit", function (oEvent) {
                var oData = new FormData();
                oEvent.preventDefault();
                oData.append("action", "delete_debt_movement");
                oData.append("id", oMovement.getAttribute("data-debt-movement-id") || "");
                submitDebtDialog(oDialogData, oData);
            });
            finishDebtDialog(oDialogData, oDialogData.save);
        }

        function openDebtDeleteDialog(oRow) {
            var oDialogData = createDebtDialog("Confirm Deletion", oRow);
            var oText = document.createElement("p");
            if (!oRow) {
                return;
            }
            if (!oDialogData) {
                return;
            }
            oDialogData.save.textContent = "Yes";
            oDialogData.cancel.textContent = "No";
            oText.textContent = "Delete this debt?";
            oDialogData.form.appendChild(oText);
            oDialogData.form.addEventListener("submit", function (oEvent) {
                var oData = new FormData();
                oEvent.preventDefault();
                oData.append("action", "delete_debt");
                oData.append("id", oRow.getAttribute("data-debt-id") || "");
                submitDebtDialog(oDialogData, oData);
            });
            finishDebtDialog(oDialogData, oDialogData.save);
        }

        function getTransactionFinanceTypes() {
            var aTypes = [];
            if (!oTransactionsTable) {
                return aTypes;
            }
            try {
                aTypes = JSON.parse(oTransactionsTable.getAttribute("data-finance-types") || "[]");
            } catch (oException) {
                logAdminException(oException);
                aTypes = [];
            }
            return aTypes;
        }

        function createTransactionDialog(sTitle, oTransactionRow) {
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
            oDialogData.transactionRow = oTransactionRow || null;
            oDialogData.transactionId = oTransactionRow ? (oTransactionRow.getAttribute("data-transaction-id") || "") : "";
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
                finishAdminSubjectRowEdit(findAdminTransactionRowById(oDialogData.transactionId) || oDialogData.transactionRow, blSaved === true);
                closeAdminDialogElement(oDialogData.dialog);
                focusElement(oAddTransaction);
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

        function appendTransactionHiddenField(oParent, sName, sValue) {
            var oInput = document.createElement("input");
            oInput.type = "hidden";
            oInput.name = sName;
            oInput.value = sValue || "";
            oParent.appendChild(oInput);
            return oInput;
        }

        function appendTransactionTextField(oParent, sLabel, sName, sValue) {
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

        function appendTransactionDateField(oParent, sValue) {
            var oLabel = document.createElement("label");
            var oInput = document.createElement("input");
            oLabel.textContent = "Date";
            oInput.type = "date";
            oInput.name = "transaction_date";
            oInput.value = sValue || "";
            oInput.required = true;
            oParent.appendChild(oLabel);
            oParent.appendChild(oInput);
            return oInput;
        }

        function appendTransactionTypeField(oParent, sSelectedValue) {
            var aTypes = getTransactionFinanceTypes();
            var oLabel = document.createElement("label");
            var oSelect = document.createElement("select");
            var oOption;
            oLabel.textContent = "Type";
            oSelect.name = "finance_type_id";
            oSelect.required = true;
            for (var iI = 0; iI < aTypes.length; iI += 1) {
                oOption = document.createElement("option");
                oOption.value = aTypes[iI].id || "";
                oOption.textContent = (aTypes[iI].type_kind == "income" ? "Income: " : "Expense: ") + (aTypes[iI].name || "");
                if (String(oOption.value) == String(sSelectedValue || "")) {
                    oOption.selected = true;
                }
                oSelect.appendChild(oOption);
            }
            oParent.appendChild(oLabel);
            oParent.appendChild(oSelect);
            return oSelect;
        }

        function appendTransactionAdditionalRow(oDialogData, sSelectedValue) {
            var oWrapper = document.createElement("div");
            var oTitle = document.createElement("div");
            var oType;
            var oAmount;
            oWrapper.className = "transaction-additional-row";
            oTitle.className = "transaction-additional-title";
            oTitle.textContent = "Subtracted Transaction";
            oWrapper.appendChild(oTitle);
            oType = appendTransactionTypeField(oWrapper, sSelectedValue);
            oAmount = appendTransactionTextField(oWrapper, "Amount", "additional_amount", "");
            oDialogData.additionalContainer.appendChild(oWrapper);
            oDialogData.additionalTransactions.push({
                type: oType,
                amount: oAmount
            });
            if (oDialogData.additionalTransactions.length >= 5) {
                oDialogData.addSubtractedButton.disabled = true;
            }
            focusElement(oAmount, true);
        }

        function appendTransactionAdditionalControls(oDialogData, oMainType) {
            var oButton = document.createElement("button");
            var oContainer = document.createElement("div");
            oDialogData.additionalTransactions = new Array();
            oDialogData.additionalContainer = oContainer;
            oDialogData.addSubtractedButton = oButton;
            oButton.type = "button";
            oButton.className = "confirm-dialog-button transaction-additional-button";
            oButton.textContent = "Add Subtracted Transaction";
            oContainer.className = "transaction-additional-list";
            oButton.addEventListener("click", function () {
                appendTransactionAdditionalRow(oDialogData, oMainType.value);
            });
            oDialogData.form.appendChild(oButton);
            oDialogData.form.appendChild(oContainer);
        }

        function appendTransactionAdditionalFormData(oDialogData, oData) {
            var iOutputIndex = 0;
            var aRows = oDialogData.additionalTransactions || new Array();
            for (var iI = 0; iI < aRows.length; iI += 1) {
                if (!aRows[iI].amount.value) {
                    continue;
                }
                oData.append("additional_transactions[" + iOutputIndex + "][finance_type_id]", aRows[iI].type.value);
                oData.append("additional_transactions[" + iOutputIndex + "][amount]", aRows[iI].amount.value);
                iOutputIndex += 1;
            }
        }

        function validateTransactionAdditionalRows(oDialogData, oMainType) {
            var aTypeValues = new Array(String(oMainType.value || ""));
            var aRows = oDialogData.additionalTransactions || new Array();
            var sTypeValue;
            for (var iI = 0; iI < aRows.length; iI += 1) {
                if (!aRows[iI].amount.value) {
                    continue;
                }
                sTypeValue = String(aRows[iI].type.value || "");
                if (aTypeValues.indexOf(sTypeValue) >= 0) {
                    setAdminDialogError(oDialogData.error, "Each subtracted transaction type must be different from the types above.");
                    focusElement(aRows[iI].type);
                    return false;
                }
                aTypeValues.push(sTypeValue);
            }
            return true;
        }

        function finishTransactionDialog(oDialogData, oFocus) {
            oDialogData.form.appendChild(oDialogData.error);
            oDialogData.actions.appendChild(oDialogData.save);
            oDialogData.actions.appendChild(oDialogData.cancel);
            oDialogData.form.appendChild(oDialogData.actions);
            oDialogData.dialog.appendChild(oDialogData.form);
            if (!openAdminDialogElement(oDialogData.dialog, oDialogData.close)) {
                return;
            }
            beginAdminSubjectRowEdit(findAdminTransactionRowById(oDialogData.transactionId) || oDialogData.transactionRow);
            focusElement(findFirstAdminUserInput(oDialogData.form) || oFocus, true);
        }

        function submitTransactionDialog(oDialogData, oData) {
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
                    setAdminDialogError(oDialogData.error, aData && aData.message ? aData.message : "Transaction could not be saved.");
                    oDialogData.save.disabled = false;
                    return;
                }
                if (aData.rows_html) {
                    replaceTransactionRows(aData.rows_html);
                } else if (aData.transaction_deleted) {
                    removeTransactionRow(aData.transaction_id);
                }
                oDialogData.close(true);
            }).catch(function (oException) {
                logAdminException(oException);
                setAdminDialogError(oDialogData.error, "Transaction could not be saved.");
                oDialogData.save.disabled = false;
            });
        }

        function openTransactionAdminDialog(oRow) {
            var blNewTransaction = !oRow;
            var oDialogData = createTransactionDialog(blNewTransaction ? "New Transaction" : "Edit Transaction", oRow);
            var oDate;
            var oType;
            var oAmount;
            var oCounterparty;
            var oNote;
            if (!oDialogData) {
                return;
            }
            appendTransactionHiddenField(oDialogData.form, "id", blNewTransaction ? "" : (oRow.getAttribute("data-transaction-id") || ""));
            oDate = appendTransactionDateField(oDialogData.form, oRow ? (oRow.getAttribute("data-transaction-date") || "") : new Date().toISOString().slice(0, 10));
            oType = appendTransactionTypeField(oDialogData.form, oRow ? (oRow.getAttribute("data-finance-type-id") || "") : "");
            oAmount = appendTransactionTextField(oDialogData.form, "Amount", "amount", oRow ? (oRow.getAttribute("data-amount") || "") : "");
            oCounterparty = appendTransactionTextField(oDialogData.form, "Counterparty", "counterparty", oRow ? (oRow.getAttribute("data-counterparty") || "") : "");
            oNote = appendTransactionTextField(oDialogData.form, "Note", "note", oRow ? (oRow.getAttribute("data-note") || "") : "");
            appendTransactionAdditionalControls(oDialogData, oType);
            oDialogData.form.addEventListener("submit", function (oEvent) {
                var oData = new FormData();
                oEvent.preventDefault();
                if (!validateTransactionAdditionalRows(oDialogData, oType)) {
                    return;
                }
                oData.append("action", "save_transaction");
                oData.append("id", blNewTransaction ? "" : (oRow.getAttribute("data-transaction-id") || ""));
                oData.append("transaction_date", oDate.value);
                oData.append("finance_type_id", oType.value);
                oData.append("amount", oAmount.value);
                oData.append("counterparty", oCounterparty.value);
                oData.append("note", oNote.value);
                appendTransactionAdditionalFormData(oDialogData, oData);
                submitTransactionDialog(oDialogData, oData);
            });
            finishTransactionDialog(oDialogData, oDate);
        }

        function openTransactionDeleteDialog(oRow) {
            var oDialogData = createTransactionDialog("Confirm Deletion", oRow);
            var oText = document.createElement("p");
            if (!oRow) {
                return;
            }
            if (!oDialogData) {
                return;
            }
            oDialogData.save.textContent = "Yes";
            oDialogData.cancel.textContent = "No";
            oText.textContent = "Delete this transaction?";
            oDialogData.form.appendChild(oText);
            oDialogData.form.addEventListener("submit", function (oEvent) {
                var oData = new FormData();
                oEvent.preventDefault();
                oData.append("action", "delete_transaction");
                oData.append("id", oRow.getAttribute("data-transaction-id") || "");
                submitTransactionDialog(oDialogData, oData);
            });
            finishTransactionDialog(oDialogData, oDialogData.save);
        }

        function getSubscriptionFinanceTypes() {
            var aTypes = [];
            if (!oSubscriptionsTable) {
                return aTypes;
            }
            try {
                aTypes = JSON.parse(oSubscriptionsTable.getAttribute("data-finance-types") || "[]");
            } catch (oException) {
                logAdminException(oException);
                aTypes = [];
            }
            return aTypes;
        }

        function getSubscriptionBillingPeriods() {
            return [
                {"id": "weekly", "name": "Weekly"},
                {"id": "monthly", "name": "Monthly"},
                {"id": "quarterly", "name": "Quarterly"},
                {"id": "yearly", "name": "Yearly"},
                {"id": "other", "name": "Other"}
            ];
        }

        function createSubscriptionDialog(sTitle, oSubscriptionRow) {
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
            oDialogData.subscriptionRow = oSubscriptionRow || null;
            oDialogData.subscriptionId = oSubscriptionRow ? (oSubscriptionRow.getAttribute("data-subscription-id") || "") : "";
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
                finishAdminSubjectRowEdit(findAdminSubscriptionRowById(oDialogData.subscriptionId) || oDialogData.subscriptionRow, blSaved === true);
                closeAdminDialogElement(oDialogData.dialog);
                focusElement(oAddSubscription);
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

        function appendSubscriptionHiddenField(oParent, sName, sValue) {
            var oInput = document.createElement("input");
            oInput.type = "hidden";
            oInput.name = sName;
            oInput.value = sValue || "";
            oParent.appendChild(oInput);
            return oInput;
        }

        function appendSubscriptionTextField(oParent, sLabel, sName, sValue) {
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

        function appendSubscriptionDateTimeField(oParent, sLabel, sName, sValue) {
            var oLabel = document.createElement("label");
            var oInput = document.createElement("input");
            oLabel.textContent = sLabel;
            oInput.type = "datetime-local";
            oInput.name = sName;
            oInput.step = "60";
            oInput.value = sValue || "";
            oParent.appendChild(oLabel);
            oParent.appendChild(oInput);
            return oInput;
        }

        function appendSubscriptionTypeField(oParent, sSelectedValue) {
            var aTypes = getSubscriptionFinanceTypes();
            var oLabel = document.createElement("label");
            var oSelect = document.createElement("select");
            var oOption;
            oLabel.textContent = "Type";
            oSelect.name = "finance_type_id";
            oSelect.required = true;
            for (var iI = 0; iI < aTypes.length; iI += 1) {
                oOption = document.createElement("option");
                oOption.value = aTypes[iI].id || "";
                oOption.textContent = (aTypes[iI].type_kind == "income" ? "Income: " : "Expense: ") + (aTypes[iI].name || "");
                if (String(oOption.value) == String(sSelectedValue || "")) {
                    oOption.selected = true;
                }
                oSelect.appendChild(oOption);
            }
            oParent.appendChild(oLabel);
            oParent.appendChild(oSelect);
            return oSelect;
        }

        function appendSubscriptionPeriodField(oParent, sSelectedValue) {
            var aPeriods = getSubscriptionBillingPeriods();
            var oLabel = document.createElement("label");
            var oSelect = document.createElement("select");
            var oOption;
            oLabel.textContent = "Period";
            oSelect.name = "billing_period";
            oSelect.required = true;
            for (var iI = 0; iI < aPeriods.length; iI += 1) {
                oOption = document.createElement("option");
                oOption.value = aPeriods[iI].id || "";
                oOption.textContent = aPeriods[iI].name || "";
                if (String(oOption.value) == String(sSelectedValue || "monthly")) {
                    oOption.selected = true;
                }
                oSelect.appendChild(oOption);
            }
            oParent.appendChild(oLabel);
            oParent.appendChild(oSelect);
            return oSelect;
        }

        function appendSubscriptionActiveField(oParent, sValue) {
            var oLabel = document.createElement("label");
            var oInput = document.createElement("input");
            oLabel.className = "checkbox-label";
            oInput.type = "checkbox";
            oInput.name = "is_active";
            oInput.value = "1";
            oInput.checked = String(sValue || "1") == "1";
            oLabel.appendChild(oInput);
            oLabel.appendChild(document.createTextNode("Active"));
            oParent.appendChild(oLabel);
            return oInput;
        }

        function finishSubscriptionDialog(oDialogData, oFocus) {
            oDialogData.form.appendChild(oDialogData.error);
            oDialogData.actions.appendChild(oDialogData.save);
            oDialogData.actions.appendChild(oDialogData.cancel);
            oDialogData.form.appendChild(oDialogData.actions);
            oDialogData.dialog.appendChild(oDialogData.form);
            if (!openAdminDialogElement(oDialogData.dialog, oDialogData.close)) {
                return;
            }
            beginAdminSubjectRowEdit(findAdminSubscriptionRowById(oDialogData.subscriptionId) || oDialogData.subscriptionRow);
            focusElement(findFirstAdminUserInput(oDialogData.form) || oFocus, true);
        }

        function submitSubscriptionDialog(oDialogData, oData) {
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
                    setAdminDialogError(oDialogData.error, aData && aData.message ? aData.message : "Subscription could not be saved.");
                    oDialogData.save.disabled = false;
                    return;
                }
                if (aData.rows_html) {
                    replaceSubscriptionRows(aData.rows_html);
                } else if (aData.subscription_deleted) {
                    removeSubscriptionRow(aData.subscription_id);
                }
                oDialogData.close(true);
            }).catch(function (oException) {
                logAdminException(oException);
                setAdminDialogError(oDialogData.error, "Subscription could not be saved.");
                oDialogData.save.disabled = false;
            });
        }

        function markSubscriptionServed(oButton, oRow) {
            var oData;
            var sDefaultMessage = "Subscription could not be marked served.";
            if (!window.fetch || !window.FormData) {
                showAdminMessageDialog(sDefaultMessage);
                return;
            }
            if (oButton.disabled) {
                return;
            }
            oData = new FormData();
            oButton.disabled = true;
            beginAdminSubjectRowEdit(oRow);
            oData.append("action", "mark_subscription_served");
            oData.append("id", oButton.getAttribute("data-subscription-id") || (oRow ? oRow.getAttribute("data-subscription-id") : ""));
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
                var oCurrentRow;
                if (!aData || !aData.success) {
                    oButton.disabled = false;
                    finishAdminSubjectRowEdit(oRow, false);
                    showAdminMessageDialog(aData && aData.message ? aData.message : sDefaultMessage);
                    return;
                }
                if (aData.rows_html) {
                    replaceSubscriptionRows(aData.rows_html);
                }
                oCurrentRow = findAdminSubscriptionRowById(aData.subscription_id || "");
                finishAdminSubjectRowEdit(oCurrentRow || oRow, true);
            }).catch(function (oException) {
                logAdminException(oException);
                oButton.disabled = false;
                finishAdminSubjectRowEdit(oRow, false);
                showAdminMessageDialog(oException && oException.message ? oException.message : sDefaultMessage);
            });
        }

        function openSubscriptionAdminDialog(oRow) {
            var blNewSubscription = !oRow;
            var oDialogData = createSubscriptionDialog(blNewSubscription ? "New Subscription" : "Edit Subscription", oRow);
            var oName;
            var oType;
            var oAmount;
            var oPeriod;
            var oNextDueAt;
            var oCounterparty;
            var oNote;
            var oActive;
            if (!oDialogData) {
                return;
            }
            appendSubscriptionHiddenField(oDialogData.form, "id", blNewSubscription ? "" : (oRow.getAttribute("data-subscription-id") || ""));
            oName = appendSubscriptionTextField(oDialogData.form, "Name", "name", oRow ? (oRow.getAttribute("data-name") || "") : "");
            oName.required = true;
            oType = appendSubscriptionTypeField(oDialogData.form, oRow ? (oRow.getAttribute("data-finance-type-id") || "") : "");
            oAmount = appendSubscriptionTextField(oDialogData.form, "Amount", "amount", oRow ? (oRow.getAttribute("data-amount") || "") : "");
            oAmount.required = true;
            oPeriod = appendSubscriptionPeriodField(oDialogData.form, oRow ? (oRow.getAttribute("data-billing-period") || "") : "monthly");
            oNextDueAt = appendSubscriptionDateTimeField(oDialogData.form, "Next Due", "next_due_at", oRow ? (oRow.getAttribute("data-next-due-at") || "") : "");
            oCounterparty = appendSubscriptionTextField(oDialogData.form, "Counterparty", "counterparty", oRow ? (oRow.getAttribute("data-counterparty") || "") : "");
            oNote = appendSubscriptionTextField(oDialogData.form, "Note", "note", oRow ? (oRow.getAttribute("data-note") || "") : "");
            oActive = appendSubscriptionActiveField(oDialogData.form, oRow ? (oRow.getAttribute("data-is-active") || "1") : "1");
            oDialogData.form.addEventListener("submit", function (oEvent) {
                var oData = new FormData();
                oEvent.preventDefault();
                oData.append("action", "save_subscription");
                oData.append("id", blNewSubscription ? "" : (oRow.getAttribute("data-subscription-id") || ""));
                oData.append("name", oName.value);
                oData.append("finance_type_id", oType.value);
                oData.append("amount", oAmount.value);
                oData.append("billing_period", oPeriod.value);
                oData.append("next_due_at", oNextDueAt.value);
                oData.append("counterparty", oCounterparty.value);
                oData.append("note", oNote.value);
                oData.append("is_active", oActive.checked ? "1" : "0");
                submitSubscriptionDialog(oDialogData, oData);
            });
            finishSubscriptionDialog(oDialogData, oName);
        }

        function openSubscriptionDeleteDialog(oRow) {
            var oDialogData = createSubscriptionDialog("Confirm Deletion", oRow);
            var oText = document.createElement("p");
            if (!oRow) {
                return;
            }
            if (!oDialogData) {
                return;
            }
            oDialogData.save.textContent = "Yes";
            oDialogData.cancel.textContent = "No";
            oText.textContent = "Delete this subscription?";
            oDialogData.form.appendChild(oText);
            oDialogData.form.addEventListener("submit", function (oEvent) {
                var oData = new FormData();
                oEvent.preventDefault();
                oData.append("action", "delete_subscription");
                oData.append("id", oRow.getAttribute("data-subscription-id") || "");
                submitSubscriptionDialog(oDialogData, oData);
            });
            finishSubscriptionDialog(oDialogData, oDialogData.save);
        }

        function getTypeMemberTypes() {
            var aTypes = [];
            if (!oTypesTable) {
                return aTypes;
            }
            try {
                aTypes = JSON.parse(oTypesTable.getAttribute("data-member-types") || "[]");
            } catch (oException) {
                logAdminException(oException);
                aTypes = [];
            }
            return aTypes;
        }

        function typeMemberSelected(sMembers, sMemberId) {
            var aMembers = sMembers ? String(sMembers).split(",") : [];
            for (var iI = 0; iI < aMembers.length; iI += 1) {
                if (aMembers[iI] == String(sMemberId)) {
                    return true;
                }
            }
            return false;
        }

        function createTypeDialog(sTitle, oTypeRow) {
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
            oDialogData.typeRow = oTypeRow || null;
            oDialogData.typeId = oTypeRow ? (oTypeRow.getAttribute("data-type-id") || "") : "";
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
                finishAdminSubjectRowEdit(findAdminTypeRowById(oDialogData.typeId) || oDialogData.typeRow, blSaved === true);
                closeAdminDialogElement(oDialogData.dialog);
                focusElement(oAddType);
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

        function appendTypeHiddenField(oParent, sName, sValue) {
            var oInput = document.createElement("input");
            oInput.type = "hidden";
            oInput.name = sName;
            oInput.value = sValue || "";
            oParent.appendChild(oInput);
            return oInput;
        }

        function appendTypeTextField(oParent, sLabel, sName, sValue) {
            var oLabel = document.createElement("label");
            var oInput = document.createElement("input");
            oLabel.textContent = sLabel;
            oInput.type = "text";
            oInput.name = sName;
            oInput.value = sValue || "";
            oInput.required = true;
            oParent.appendChild(oLabel);
            oParent.appendChild(oInput);
            return oInput;
        }

        function appendTypeKindField(oParent, sSelectedValue) {
            var oLabel = document.createElement("label");
            var oSelect = document.createElement("select");
            var aKinds = [
                {"value": "income", "label": "Income"},
                {"value": "expense", "label": "Expense"},
                {"value": "group", "label": "Group"}
            ];
            var oOption;
            oLabel.textContent = "Kind";
            oSelect.name = "type_kind";
            oSelect.required = true;
            for (var iI = 0; iI < aKinds.length; iI += 1) {
                oOption = document.createElement("option");
                oOption.value = aKinds[iI].value;
                oOption.textContent = aKinds[iI].label;
                if (oOption.value == (sSelectedValue || "expense")) {
                    oOption.selected = true;
                }
                oSelect.appendChild(oOption);
            }
            oParent.appendChild(oLabel);
            oParent.appendChild(oSelect);
            return oSelect;
        }

        function appendTypeMemberFields(oParent, oRow, oKind) {
            var aTypes = getTypeMemberTypes();
            var sTypeId = oRow ? (oRow.getAttribute("data-type-id") || "") : "";
            var sMembers = oRow ? (oRow.getAttribute("data-members") || "") : "";
            var oWrapper = document.createElement("div");
            var oLabel = document.createElement("label");
            var oGrid = document.createElement("div");
            var oMemberLabel;
            var oInput;
            oWrapper.setAttribute("data-visible-for-kind", "group");
            oLabel.textContent = "Group Members";
            oGrid.className = "checkbox-grid";
            oWrapper.appendChild(oLabel);
            oWrapper.appendChild(oGrid);
            for (var iI = 0; iI < aTypes.length; iI += 1) {
                if (String(aTypes[iI].id || "") == sTypeId) {
                    continue;
                }
                oMemberLabel = document.createElement("label");
                oMemberLabel.className = "checkbox-label";
                oInput = document.createElement("input");
                oInput.type = "checkbox";
                oInput.name = "members[]";
                oInput.value = aTypes[iI].id || "";
                oInput.checked = typeMemberSelected(sMembers, oInput.value);
                oMemberLabel.appendChild(oInput);
                oMemberLabel.appendChild(document.createTextNode(" " + (aTypes[iI].name || "")));
                oGrid.appendChild(oMemberLabel);
            }
            oParent.appendChild(oWrapper);
            oKind.addEventListener("change", function () {
                refreshConditionalFields(oParent);
            });
            refreshConditionalFields(oParent);
            return oWrapper.querySelectorAll("input[name=\"members[]\"]");
        }

        function finishTypeDialog(oDialogData, oFocus) {
            oDialogData.form.appendChild(oDialogData.error);
            oDialogData.actions.appendChild(oDialogData.save);
            oDialogData.actions.appendChild(oDialogData.cancel);
            oDialogData.form.appendChild(oDialogData.actions);
            oDialogData.dialog.appendChild(oDialogData.form);
            if (!openAdminDialogElement(oDialogData.dialog, oDialogData.close)) {
                return;
            }
            beginAdminSubjectRowEdit(findAdminTypeRowById(oDialogData.typeId) || oDialogData.typeRow);
            focusElement(findFirstAdminUserInput(oDialogData.form) || oFocus, true);
        }

        function submitTypeDialog(oDialogData, oData) {
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
                    setAdminDialogError(oDialogData.error, aData && aData.message ? aData.message : "Type could not be saved.");
                    oDialogData.save.disabled = false;
                    return;
                }
                if (aData.rows_html) {
                    replaceTypeRows(aData.rows_html);
                } else if (aData.type_deleted) {
                    removeTypeRow(aData.type_id);
                }
                oDialogData.close(true);
            }).catch(function (oException) {
                logAdminException(oException);
                setAdminDialogError(oDialogData.error, "Type could not be saved.");
                oDialogData.save.disabled = false;
            });
        }

        function openTypeAdminDialog(oRow) {
            var blNewType = !oRow;
            var oDialogData = createTypeDialog(blNewType ? "New Type" : "Edit Type", oRow);
            var oName;
            var oKind;
            var aMembers;
            if (!oDialogData) {
                return;
            }
            appendTypeHiddenField(oDialogData.form, "id", blNewType ? "" : (oRow.getAttribute("data-type-id") || ""));
            oName = appendTypeTextField(oDialogData.form, "Name", "name", oRow ? (oRow.getAttribute("data-type-name") || "") : "");
            oKind = appendTypeKindField(oDialogData.form, oRow ? (oRow.getAttribute("data-type-kind") || "") : "expense");
            aMembers = appendTypeMemberFields(oDialogData.form, oRow, oKind);
            oDialogData.form.addEventListener("submit", function (oEvent) {
                var oData = new FormData();
                oEvent.preventDefault();
                oData.append("action", "save_type");
                oData.append("id", blNewType ? "" : (oRow.getAttribute("data-type-id") || ""));
                oData.append("name", oName.value);
                oData.append("type_kind", oKind.value);
                for (var iI = 0; iI < aMembers.length; iI += 1) {
                    if (aMembers[iI].checked) {
                        oData.append("members[]", aMembers[iI].value);
                    }
                }
                submitTypeDialog(oDialogData, oData);
            });
            finishTypeDialog(oDialogData, oName);
        }

        function openTypeDeleteDialog(oRow) {
            var oDialogData = createTypeDialog("Confirm Deletion", oRow);
            var oText = document.createElement("p");
            if (!oRow) {
                return;
            }
            if (!oDialogData) {
                return;
            }
            oDialogData.save.textContent = "Yes";
            oDialogData.cancel.textContent = "No";
            oText.textContent = "Delete this type?";
            oDialogData.form.appendChild(oText);
            oDialogData.form.addEventListener("submit", function (oEvent) {
                var oData = new FormData();
                oEvent.preventDefault();
                oData.append("action", "delete_type");
                oData.append("id", oRow.getAttribute("data-type-id") || "");
                submitTypeDialog(oDialogData, oData);
            });
            finishTypeDialog(oDialogData, oDialogData.save);
        }

        for (var iI = 0; iI < aOpeners.length; iI += 1) {
            aOpeners[iI].addEventListener("click", function (oEvent) {
                if (getAdminModalRow(this)) {
                    return;
                }
                oEvent.preventDefault();
                openModalFromButton(this, null);
            });
        }
        if (oAddDebt) {
            oAddDebt.addEventListener("click", function () {
                openDebtAdminDialog(null);
            });
        }
        if (oAddTransaction) {
            oAddTransaction.addEventListener("click", function () {
                openTransactionAdminDialog(null);
            });
        }
        if (oAddSubscription) {
            oAddSubscription.addEventListener("click", function () {
                openSubscriptionAdminDialog(null);
            });
        }
        if (oAddType) {
            oAddType.addEventListener("click", function () {
                openTypeAdminDialog(null);
            });
        }
        if (oDebtsTable) {
            oDebtsTable.addEventListener("click", function (oEvent) {
                var oButton = oEvent.target && oEvent.target.closest ? oEvent.target.closest(".js-add-debt-movement, .js-edit-debt-movement, .js-delete-debt-movement, .js-edit-debt, .js-delete-debt") : null;
                var oRow;
                var oMovement;
                if (!oButton) {
                    return;
                }
                oEvent.preventDefault();
                oRow = oButton.closest("tr[data-debt-id]");
                oMovement = getDebtMovementElement(oButton);
                if (oButton.className.indexOf("js-add-debt-movement") !== -1) {
                    openDebtMovementAdminDialog(oRow, null);
                } else if (oButton.className.indexOf("js-edit-debt-movement") !== -1) {
                    openDebtMovementAdminDialog(oRow, oMovement);
                } else if (oButton.className.indexOf("js-delete-debt-movement") !== -1) {
                    openDebtMovementDeleteDialog(oRow, oMovement);
                } else if (oButton.className.indexOf("js-delete-debt") !== -1) {
                    openDebtDeleteDialog(oRow);
                } else {
                    openDebtAdminDialog(oRow);
                }
            });
        }
        if (oTransactionsTable) {
            oTransactionsTable.addEventListener("click", function (oEvent) {
                var oButton = oEvent.target && oEvent.target.closest ? oEvent.target.closest(".js-edit-transaction, .js-delete-transaction") : null;
                if (!oButton) {
                    return;
                }
                oEvent.preventDefault();
                if (oButton.className.indexOf("js-delete-transaction") !== -1) {
                    openTransactionDeleteDialog(oButton.closest("tr[data-transaction-id]"));
                } else {
                    openTransactionAdminDialog(oButton.closest("tr[data-transaction-id]"));
                }
            });
        }
        if (oSubscriptionsTable) {
            oSubscriptionsTable.addEventListener("click", function (oEvent) {
                var oButton = oEvent.target && oEvent.target.closest ? oEvent.target.closest(".js-subscription-served, .js-edit-subscription, .js-delete-subscription") : null;
                var oRow;
                if (!oButton) {
                    return;
                }
                oEvent.preventDefault();
                oRow = oButton.closest("tr[data-subscription-id]");
                if (oButton.className.indexOf("js-subscription-served") !== -1) {
                    markSubscriptionServed(oButton, oRow);
                } else if (oButton.className.indexOf("js-delete-subscription") !== -1) {
                    openSubscriptionDeleteDialog(oRow);
                } else {
                    openSubscriptionAdminDialog(oRow);
                }
            });
        }
        if (oTypesTable) {
            oTypesTable.addEventListener("click", function (oEvent) {
                var oButton = oEvent.target && oEvent.target.closest ? oEvent.target.closest(".js-edit-type, .js-delete-type") : null;
                if (!oButton) {
                    return;
                }
                oEvent.preventDefault();
                if (oButton.className.indexOf("js-delete-type") !== -1) {
                    openTypeDeleteDialog(oButton.closest("tr[data-type-id]"));
                } else {
                    openTypeAdminDialog(oButton.closest("tr[data-type-id]"));
                }
            });
        }
        for (var iN = 0; iN < aModals.length; iN += 1) {
            oBox = aModals[iN].querySelector(".confirm-dialog-box");
            oHeader = aModals[iN].querySelector(".confirm-dialog-header");
            enableAdminDialogDrag(aModals[iN], oBox, oHeader);
        }
        var aCloses = document.querySelectorAll("[data-modal-close]");
        for (var iJ = 0; iJ < aCloses.length; iJ += 1) {
            aCloses[iJ].addEventListener("click", function () {
                var oModal = this.closest(".confirm-dialog");
                closeModalFromElement(oModal);
            });
        }
        var aKindFields = document.querySelectorAll("[name=\"type_kind\"]");
        for (var iK = 0; iK < aKindFields.length; iK += 1) {
            aKindFields[iK].addEventListener("change", function () {
                refreshConditionalFields(this.form);
            });
        }
        document.addEventListener("keydown", function (oEvent) {
            if (oEvent.key != "Escape") {
                return;
            }
            closeAdminOpenDialog();
        });
    }

    function focusLoginUser() {
        var oUser = document.getElementById("login-user");
        focusElement(oUser, true);
    }

    function copyTextWithInput(sText) {
        var oInput = document.createElement("input");
        var blSuccess = false;
        oInput.type = "text";
        oInput.value = sText;
        oInput.setAttribute("readonly", "readonly");
        oInput.style.position = "fixed";
        oInput.style.left = "-9999px";
        document.body.appendChild(oInput);
        oInput.select();
        try {
            blSuccess = document.execCommand("copy");
        } catch (oException) {
            console.error(oException);
            blSuccess = false;
        }
        document.body.removeChild(oInput);
        return blSuccess;
    }

    function getAdminEmoji(sName) {
        var oData = document.getElementById("emoji-data");
        return oData ? (oData.getAttribute("data-" + sName) || "") : "";
    }

    function setupCopyLinks() {
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
                    console.error(oException);
                    showCopyResult(oButton, copyTextWithInput(sLink));
                });
                return;
            }
            showCopyResult(oButton, copyTextWithInput(sLink));
        }

        for (var iI = 0; iI < aButtons.length; iI += 1) {
            aButtons[iI].addEventListener("click", function () {
                copyLink(this);
            });
        }
    }

    function setupCopyActions() {
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
                    console.error(oException);
                    showCopyValueResult(copyTextWithInput(sValue));
                });
                return;
            }
            showCopyValueResult(copyTextWithInput(sValue));
        });

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
                    console.error(oException);
                    showContactCopyResult(oButton, copyTextWithInput(sValue));
                });
                return;
            }
            showContactCopyResult(oButton, copyTextWithInput(sValue));
        }
    }

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

        window.copyAdminTableRowState = copyTableRowState;
        window.bindAdminTableRow = bindTableRow;
    }

    function setupSchemaRelations() {
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
            };
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
            };
        }

        function getAnchor(oRowRect, oCanvasRect, sSide, iXOffset, iYOffset) {
            if (sSide == "top") {
                return {
                    "x": oRowRect.left + oRowRect.width / 2 - oCanvasRect.left + iXOffset,
                    "y": oRowRect.top - oCanvasRect.top + iYOffset
                };
            }
            if (sSide == "bottom") {
                return {
                    "x": oRowRect.left + oRowRect.width / 2 - oCanvasRect.left + iXOffset,
                    "y": oRowRect.bottom - oCanvasRect.top + iYOffset
                };
            }
            return {
                "x": (sSide == "right" ? oRowRect.right : oRowRect.left) - oCanvasRect.left + iXOffset,
                "y": oRowRect.top + oRowRect.height / 2 - oCanvasRect.top + iYOffset
            };
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
            };
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
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        setupMenu();
        setupMessages();
        setupFilterFocusButton();
        setupTableFilter();
        setupSubjectSuggest();
        setupSettingsDialog();
        setupModals();
        focusLoginUser();
        setupCopyLinks();
        setupCopyActions();
        setupTableRows();
        setupSchemaRelations();
    });
})();
