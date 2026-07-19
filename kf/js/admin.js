(function () {
    "use strict";

    function closeMenu(oMenu) {
        var oButton = oMenu ? oMenu.querySelector("[data-kf-menu-button]") : null;
        var oPanel = oMenu ? oMenu.querySelector("[data-kf-menu-panel]") : null;
        if (oPanel) {
            oPanel.hidden = true;
        }
        if (oButton) {
            oButton.setAttribute("aria-expanded", "false");
        }
    }

    function setupMenu() {
        var aMenus = document.querySelectorAll("[data-kf-menu]");
        for (var iI = 0; iI < aMenus.length; iI += 1) {
            (function (oMenu) {
                var oButton = oMenu.querySelector("[data-kf-menu-button]");
                var oPanel = oMenu.querySelector("[data-kf-menu-panel]");
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
            if (oEvent.target.closest && oEvent.target.closest("[data-kf-menu]")) {
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

    function setupTableFilter() {
        var aFilters = document.querySelectorAll(".js-table-filter");
        for (var iI = 0; iI < aFilters.length; iI += 1) {
            (function (oFilter) {
                var aOperatorButtons = document.querySelectorAll(".js-filter-operator[data-filter-input=\"" + oFilter.id + "\"]");
                var aResetButtons = document.querySelectorAll(".js-filter-reset[data-filter-input=\"" + oFilter.id + "\"]");

                function filterTable() {
                    var oTable = document.getElementById(oFilter.getAttribute("data-table-filter"));
                    var aExpression = buildFilterExpression(oFilter.value);
                    var aRows;
                    var aCells;
                    var aTexts;
                    var sRowText;
                    var sDisplay;
                    var iK;
                    refreshFilterFocusButton(oFilter);
                    if (!oTable) {
                        return;
                    }
                    if (oTable.tBodies && oTable.tBodies.length == 1) {
                        aRows = oTable.tBodies[0].rows;
                    } else {
                        aRows = oTable.querySelectorAll("tbody tr");
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
                }

                oFilter.addEventListener("input", filterTable);
                for (var iL = 0; iL < aOperatorButtons.length; iL += 1) {
                    aOperatorButtons[iL].addEventListener("click", function () {
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
                        filterTable();
                    });
                }
                for (var iM = 0; iM < aResetButtons.length; iM += 1) {
                    aResetButtons[iM].addEventListener("click", function () {
                        oFilter.value = "";
                        filterTable();
                        oFilter.focus();
                    });
                }
                var oFocus = document.querySelector(".js-filter-focus[data-filter-input=\"" + oFilter.id + "\"]");
                if (oFocus) {
                    oFocus.addEventListener("click", function () {
                        oFilter.focus();
                    });
                }
                refreshFilterFocusButton(oFilter);
                if (oFilter.value.replace(/^\s+|\s+$/g, "") !== "") {
                    filterTable();
                }
            })(aFilters[iI]);
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

    function setupModals() {
        var aOpeners = document.querySelectorAll("[data-modal-target]");
        var oOpenModal = null;

        function enableModalDrag(oModal) {
            var oBox = oModal ? oModal.querySelector(".confirm-dialog-box") : null;
            var oHeader = oModal ? oModal.querySelector(".confirm-dialog-header") : null;
            var blDragging = false;
            var iOffsetX = 0;
            var iOffsetY = 0;

            function moveModal(iClientX, iClientY) {
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
                    moveModal(oEvent.clientX, oEvent.clientY);
                    oEvent.preventDefault();
                }
            }

            if (!oModal || !oBox || !oHeader || oHeader.getAttribute("data-modal-drag-bound") == "1") {
                return;
            }
            oHeader.setAttribute("data-modal-drag-bound", "1");
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

        function closeModal(oModal) {
            if (oModal && !oModal.hidden) {
                oModal.hidden = true;
            }
            if (oOpenModal === oModal) {
                oOpenModal = null;
            }
        }

        function closeOpenModal(oExceptModal) {
            var aDialogs = document.querySelectorAll(".confirm-dialog:not([hidden])");
            for (var iL = 0; iL < aDialogs.length; iL += 1) {
                if (aDialogs[iL] !== oExceptModal) {
                    closeModal(aDialogs[iL]);
                }
            }
        }

        function openModal(oModal) {
            if (!oModal) {
                return false;
            }
            if (!oModal.hidden) {
                closeOpenModal(oModal);
                return false;
            }
            closeOpenModal(oModal);
            oOpenModal = oModal;
            oModal.hidden = false;
            return true;
        }

        for (var iI = 0; iI < aOpeners.length; iI += 1) {
            aOpeners[iI].addEventListener("click", function (oEvent) {
                var oModal = document.getElementById(this.getAttribute("data-modal-target"));
                var oForm = oModal ? oModal.querySelector("form") : null;
                var sTitle = this.getAttribute("data-modal-title");
                oEvent.preventDefault();
                if (!oModal || !oForm) {
                    return;
                }
                closeOpenModal();
                oForm.reset();
                Array.prototype.forEach.call(this.attributes, function (oAttr) {
                    if (oAttr.name.indexOf("data-field-") === 0) {
                        setFieldValue(oForm, oAttr.name.substring(11), oAttr.value);
                    }
                });
                refreshConditionalFields(oForm);
                if (sTitle) {
                    oModal.querySelector("[data-modal-heading]").textContent = sTitle;
                }
                openModal(oModal);
            });
        }
        var aModals = document.querySelectorAll(".confirm-dialog");
        for (var iN = 0; iN < aModals.length; iN += 1) {
            enableModalDrag(aModals[iN]);
        }
        var aCloses = document.querySelectorAll("[data-modal-close]");
        for (var iJ = 0; iJ < aCloses.length; iJ += 1) {
            aCloses[iJ].addEventListener("click", function () {
                var oModal = this.closest(".confirm-dialog");
                closeModal(oModal);
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
            var aDialogs = document.querySelectorAll(".confirm-dialog:not([hidden])");
            for (var iL = 0; iL < aDialogs.length; iL += 1) {
                closeModal(aDialogs[iL]);
            }
        });
    }

    function focusLoginUser() {
        var oUser = document.getElementById("login-user");
        if (!oUser) {
            return;
        }
        try {
            oUser.focus({
                "preventScroll": true
            });
        } catch (oException) {
            console.error(oException);
            oUser.focus();
        }
        if (typeof oUser.select == "function") {
            try {
                oUser.select();
            } catch (oException) {
                console.error(oException);
            }
        }
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

    function setupTableRows() {
        var aRelatedRowsCache = {};
        var sHoverColor = "#fff3cd";
        var sSelectedColor = "#cfe2ff";

        function getCurrentRowColor(oRow) {
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
            return oTarget && oTarget.closest && oTarget.closest("a, button, input, select, label");
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

        function getRelatedRows(oRow) {
            var sMonth = oRow.getAttribute("data-month") || "";
            var sCacheKey;
            if (sMonth == "") {
                return [oRow];
            }
            sCacheKey = "month:" + sMonth;
            if (!aRelatedRowsCache[sCacheKey]) {
                aRelatedRowsCache[sCacheKey] = document.querySelectorAll("table tbody tr[data-month=\"" + sMonth + "\"]");
            }
            return aRelatedRowsCache[sCacheKey];
        }

        function setRelatedRowsAttribute(oRow, sName, sValue) {
            var aRelatedRows = getRelatedRows(oRow);
            for (var iI = 0; iI < aRelatedRows.length; iI += 1) {
                aRelatedRows[iI].setAttribute(sName, sValue);
                applyRowColor(aRelatedRows[iI]);
            }
        }

        document.addEventListener("mouseover", function (oEvent) {
            var oRow = getEventTableRow(oEvent);
            if (!oRow || isInsideTableRow(oRow, oEvent.relatedTarget)) {
                return;
            }
            setRelatedRowsAttribute(oRow, "data-hover", "1");
        });

        document.addEventListener("mouseout", function (oEvent) {
            var oRow = getEventTableRow(oEvent);
            if (!oRow || isInsideTableRow(oRow, oEvent.relatedTarget)) {
                return;
            }
            setRelatedRowsAttribute(oRow, "data-hover", "0");
        });

        document.addEventListener("click", function (oEvent) {
            var oRow = getEventTableRow(oEvent);
            if (!oRow || isTableRowActionTarget(oEvent.target)) {
                return;
            }
            setRelatedRowsAttribute(oRow, "data-selected", oRow.getAttribute("data-selected") == "1" ? "0" : "1");
        });
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
        setupTableFilter();
        setupModals();
        focusLoginUser();
        setupCopyLinks();
        setupTableRows();
        setupSchemaRelations();
    });
})();
