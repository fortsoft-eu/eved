var iAdminModalCount = 0;
var sAdminBodyOverflow = "";
var iAdminScrollLeft = 0;
var iAdminScrollTop = 0;

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
        if (oSourceRow.getAttribute("data-hover") == "1") {
            oTargetRow.setAttribute("data-hover", "1");
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
        focusAdminElement(oFilter, true);
    }

    for (var iI = 0; iI < aFilters.length; iI += 1) {
        initializeTableFilter(aFilters[iI]);
    }
}

function setupAutoRefresh() {
    var oAutoRefresh = document.querySelector(".js-auto-refresh");
    var iRefreshTimer = null;
    var oAudioContext = null;
    var sStorageKey;
    var iLatestId;
    var iRefreshInterval;
    if (!oAutoRefresh || !window.fetch) {
        return;
    }
    sStorageKey = "admin-auto-refresh:" + window.location.pathname;
    iLatestId = parseInt(oAutoRefresh.getAttribute("data-latest-id") || "0", 10);
    iRefreshInterval = parseInt(oAutoRefresh.getAttribute("data-refresh-interval") || "300000", 10);
    try {
        oAutoRefresh.checked = window.localStorage.getItem(sStorageKey) == "1";
    } catch (oException) {
        logAdminException(oException);
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
        try {
            window.localStorage.setItem(sStorageKey, oAutoRefresh.checked ? "1" : "0");
        } catch (oException) {
            logAdminException(oException);
        }
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

function padIssueDateNumber(iValue) {
    return iValue < 10 ? "0" + iValue : "" + iValue;
}

function formatIssueLocalDate(oDate) {
    return oDate.getFullYear() + "-" + padIssueDateNumber(oDate.getMonth() + 1) + "-" + padIssueDateNumber(oDate.getDate());
}

function parseIssueDateValue(sValue) {
    var sText = String(sValue || "").replace(/^\s+|\s+$/g, "");
    var aParts;
    var iYear;
    var iMonth;
    var iDay;
    var oDate;
    if (sText == "") {
        return null;
    }
    aParts = sText.match(/^([0-9]{4})[-.\/ ]([0-9]{1,2})[-.\/ ]([0-9]{1,2})$/);
    if (aParts) {
        iYear = parseInt(aParts[1], 10);
        iMonth = parseInt(aParts[2], 10);
        iDay = parseInt(aParts[3], 10);
    } else {
        aParts = sText.match(/^([0-9]{1,2})[-.\/ ]([0-9]{1,2})[-.\/ ]([0-9]{4})$/);
        if (!aParts) {
            return null;
        }
        iYear = parseInt(aParts[3], 10);
        iMonth = parseInt(aParts[2], 10);
        iDay = parseInt(aParts[1], 10);
    }
    if (!isFinite(iYear) || !isFinite(iMonth) || !isFinite(iDay) || iYear < 1 || iYear > 9999 || iMonth < 1 || iMonth > 12 || iDay < 1 || iDay > 31) {
        return null;
    }
    oDate = new Date(0);
    oDate.setFullYear(iYear, iMonth - 1, iDay);
    oDate.setHours(0, 0, 0, 0);
    if (oDate.getFullYear() !== iYear || oDate.getMonth() !== iMonth - 1 || oDate.getDate() !== iDay) {
        return null;
    }
    return oDate;
}

function normalizeIssueDateInput(oInput) {
    var oDate;
    if (!oInput || oInput.value.replace(/^\s+|\s+$/g, "") == "") {
        return;
    }
    oDate = parseIssueDateValue(oInput.value);
    if (oDate) {
        oInput.value = formatIssueLocalDate(oDate);
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
            oInput.value = this.getAttribute("data-date") || "";
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

function appendIssueDateField(oParent, sLabel, sName, sValue) {
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
    oInput.placeholder = "YYYY-MM-DD";
    oInput.maxLength = 10;
    oInput.autocomplete = "off";
    oInput.setAttribute("data-date-input-kind", "date");
    oInput.title = "Use YYYY-MM-DD.";
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
    oDescription = createAdminInput("issue-dialog-description", aRow ? aRow.description : "", false);
    oDescription.spellcheck = true;

    appendIssueDialogField(oBox, "Type", oType);
    appendIssueDialogField(oBox, "Title", oIssueTitle);
    appendIssueDialogField(oBox, "Status", oStatus);
    appendIssueDialogField(oBox, "Priority", oPriority);
    oDueDate = appendIssueDateField(oBox, "Due", "issue-dialog-due-date", aRow ? aRow.dueDate : formatIssueLocalDate(new Date()));
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
    setDashboardServiceText(oChecked, aData && aData.checked_at ? aData.checked_at : "");
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
    var iGap = 8;
    var iMinCardWidth = 320;
    var iColumns;
    if (isBusinessHoursPmdLike()) {
        return 1;
    }
    iColumns = Math.floor((iViewportWidth + iGap) / (iMinCardWidth + iGap));
    if (iColumns < 1) {
        return 1;
    }
    if (iColumns > 6) {
        return 6;
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
    var i;
    if (sId == "" && aCards.length) {
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
    if (!blFound && aCards.length) {
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
    oInput.addEventListener("focus", function() {
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
    var oAddressList;
    var oDays;
    var aDayInputs = [];
    var oActive;
    var oError;
    var oActions;
    var oSave;
    var oCancel;
    var iId = aRow ? aRow.id : 0;
    var aHours = aRow ? aRow.hours : getBusinessHoursDefaultHours();
    var blSaved = false;
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
    oAddress = createAdminInput("business-hours-dialog-address", aRow ? aRow.addressText : "", true);
    oAddress.setAttribute("list", "business-hours-address-list");
    oAddressList = document.createElement("datalist");
    oAddressList.id = "business-hours-address-list";
    appendMenuDialogField(oBox, "Address", oAddress);
    oBox.appendChild(oAddressList);

    oDays = document.createElement("div");
    oDays.className = "business-hours-days";
    for (i = 0; i < aBusinessHoursDays.length; i++) {
        aDayInputs.push(appendBusinessHoursDayRow(oDays, aBusinessHoursDays[i], aHours[aBusinessHoursDays[i].key]));
    }
    oBox.appendChild(oDays);

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

    function refreshAddressField() {
        var blDisabled = parseInt(oSubjectId.value || "0", 10) < 1;
        oAddress.disabled = blDisabled;
        if (blDisabled) {
            oAddress.value = "";
            oAddressId.value = "";
            oAddressList.innerHTML = "";
        }
    }

    function clearAddressField() {
        oAddress.value = "";
        oAddressId.value = "";
        oAddressList.innerHTML = "";
        refreshAddressField();
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
            refreshAddressField();
        },
        onClear: clearAddressField
    });
    bindBusinessHoursSuggestInput(oAddress, {
        action: "suggest_business_hours_addresses",
        resultKey: "addresses",
        idKey: "address_id",
        textKey: "address_text",
        idField: oAddressId,
        subjectIdField: oSubjectId,
        minLength: 0
    });

    beginAdminSubjectRowEdit(oSourceCard);
    oClose.addEventListener("click", closeBusinessHoursDialog);
    oCancel.addEventListener("click", closeBusinessHoursDialog);
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
        oData.append("address_id", oAddressId.value);
        appendAdminEncodedValue(oData, "subject_name", oSubject.value);
        appendAdminEncodedValue(oData, "address_text", oAddress.value);
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
    if (oDialog.hidden) {
        lockAdminModalScroll();
    }
    oDialog.hidden = false;
    refreshAddressField();
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
                }
            }
        }, 0);
    }
}

function isSnippetBoardPmdLike() {
    return document.body && document.body.getAttribute("data-pmd-like") == "1";
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
    var iGap = 8;
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
    oForm.style.height = iAvailableHeight + "px";
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
            }, 5000);
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

    function collectSnippetBoardData() {
        var oData = new FormData();
        var aValues = getSnippetBoardValues();
        var sSlot;
        oData.append("action", "save_snippet_board");
        for (sSlot in aValues) {
            if (Object.prototype.hasOwnProperty.call(aValues, sSlot)) {
                appendAdminEncodedValue(oData, "snippet_" + sSlot, aValues[sSlot]);
            }
        }
        return oData;
    }

    function applySnippetBoardValues(aValues) {
        var aTextareas = oForm.querySelectorAll(".js-snippet-board-textarea");
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
                snippets: getSnippetBoardValues()
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
            applySnippetBoardValues(aData.snippets || {});
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
        applySnippetBoardValues(aMessage.snippets || {});
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
        var i;
        for (i = 0; i < aTextareas.length; i++) {
            aTextareas[i].addEventListener("input", scheduleSnippetBoardSave);
            aTextareas[i].addEventListener("change", scheduleSnippetBoardSave);
        }
    }

    if (!oForm) {
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

function bindSnippetBoardTinyMce() {
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
            entity_encoding: "raw",
            height: 320,
            license_key: "gpl",
            resize: false,
            skin: "oxide",
            statusbar: false,
            toolbar: "undo redo | blocks | bold italic underline | bullist numlist | link unlink | removeformat code",
            toolbar_mode: "sliding",
            plugins: "lists link code autolink",
            content_style: "body{background:#fff;color:#111;font-family:Arial,sans-serif;font-size:14px;line-height:1.35;margin:8px;}p{margin:0 0 8px;}ul,ol{margin-top:0;}a{color:#075e9e;}",
            setup: function(oEditor) {
                oEditor.on("change keyup undo redo input paste", function() {
                    oEditor.save();
                    if (!blSnippetBoardApplyingRemote) {
                        dispatchAdminInputEvent(oEditor.getElement());
                    }
                });
                oEditor.on("init", function() {
                    layoutSnippetBoard();
                });
            }
        });
    } catch (oException) {
        logAdminException(oException);
    }
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
        closeAdminMenus(null);
        closeAdminDialog();
    }
});

document.addEventListener("DOMContentLoaded", function() {
    bindAdminMenus();
    setupTableRows();
    setupFilterFocusButton();
    setupTableFilter();
    setupAutoRefresh();
    bindAdminCopyLinks();
    bindMenuAdmin();
    bindDashboardServices();
    bindBusinessHours();
    layoutBusinessHours();
    bindIssueTracker();
    bindAdminSubmitOnChange();
    bindSnippetBoardTabs();
    bindSnippetBoardForm();
    bindSnippetBoardTinyMce();
    layoutSnippetBoard();
    window.addEventListener("resize", layoutBusinessHours);
    window.addEventListener("resize", layoutSnippetBoard);
    if (window.visualViewport) {
        window.visualViewport.addEventListener("resize", layoutBusinessHours);
        window.visualViewport.addEventListener("resize", layoutSnippetBoard);
    }
});
