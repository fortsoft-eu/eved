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
        oData.append("lm_csrf_token", sToken);
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

function closeAdminDialog() {
    var oDialog = document.getElementById("admin-reusable-dialog");
    if (!oDialog) {
        return;
    }
    oDialog.hidden = true;
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
    oTitle.textContent = iMenuId > 0 ? "Edit menu item" : "New menu item";
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
    oDialog.hidden = false;
    refreshSeparatorFields();
    window.setTimeout(function() {
        oPath.focus();
        oPath.select();
    }, 0);
}

function openAdminConfirmDialog(sTitle, sMessage, sConfirmText, fConfirm, fCancel) {
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
    oMessage.textContent = sMessage;
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
    oCancel.textContent = "Cancel";
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
    oDialog.hidden = false;
    window.setTimeout(function() {
        oConfirm.focus();
    }, 0);
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
            openAdminConfirmDialog("Delete menu item", "Delete " + aRow.path + "?", "Delete", function() {
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
            });
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

function closeAdminMenus(oExceptMenu) {
    var aMenus = document.querySelectorAll("[data-menu]");
    var i;
    var oButton;
    var oPanel;
    for (i = 0; i < aMenus.length; i++) {
        if (aMenus[i] === oExceptMenu) {
            continue;
        }
        oButton = aMenus[i].querySelector("[data-menu-button]");
        oPanel = aMenus[i].querySelector("[data-menu-panel]");
        if (oPanel) {
            oPanel.hidden = true;
        }
        if (oButton) {
            oButton.setAttribute("aria-expanded", "false");
        }
    }
}

function bindAdminMenus() {
    var aMenus = document.querySelectorAll("[data-menu]");
    var i;

    for (i = 0; i < aMenus.length; i++) {
        (function(oMenu) {
            var oButton = oMenu ? oMenu.querySelector("[data-menu-button]") : null;
            var oPanel = oMenu ? oMenu.querySelector("[data-menu-panel]") : null;
            if (!oButton || !oPanel) {
                return;
            }
            oButton.addEventListener("click", function(oEvent) {
                var blOpen = oPanel.hidden;
                closeAdminMenus(oMenu);
                oPanel.hidden = !blOpen;
                oButton.setAttribute("aria-expanded", blOpen ? "true" : "false");
                oEvent.preventDefault();
            });
        })(aMenus[i]);
    }

    document.addEventListener("click", function(oEvent) {
        var oMenu = oEvent.target.closest ? oEvent.target.closest("[data-menu]") : null;
        if (!oMenu) {
            closeAdminMenus(null);
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

document.addEventListener("keydown", function(oEvent) {
    if (oEvent.key == "Escape") {
        closeAdminMenus(null);
        closeAdminDialog();
    }
});

document.addEventListener("DOMContentLoaded", function() {
    bindAdminMenus();
    setupTableRows();
    setupFilterFocusButton();
    setupTableFilter();
    bindAdminCopyLinks();
    bindMenuAdmin();
    bindAdminSubmitOnChange();
});
