var iAdminModalCount = 0;
var sAdminBodyOverflow = "";

function getAdminAjaxHeaders() {
    return {
        "X-Requested-With": "XMLHttpRequest"
    }
}

function lockAdminModalScroll() {
    if (iAdminModalCount === 0) {
        sAdminBodyOverflow = document.body.style.overflow || "";
        document.body.style.overflow = "hidden";
    }
    iAdminModalCount += 1;
}

function unlockAdminModalScroll() {
    if (iAdminModalCount > 0) {
        iAdminModalCount -= 1;
    }
    if (iAdminModalCount === 0) {
        document.body.style.overflow = sAdminBodyOverflow;
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
    }
    if (typeof oElement.setSelectionRange == "function") {
        try {
            oElement.setSelectionRange(0, (oElement.value || "").length);
        } catch (oException) {
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
    });
}

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
            try {
                oFilter.focus({"preventScroll":true});
            } catch (oException) {
                oFilter.focus();
            }
            selectAdminTextField(oFilter);
            window.scrollTo(iScrollLeft, 0);
        }, 0);
    });
});

document.addEventListener("DOMContentLoaded", function () {
    var aButtons = document.querySelectorAll(".js-copy-link");
    function fallbackCopyLink(sText) {
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
            blResult = false;
        }
        document.body.removeChild(oTextArea);
        return blResult;
    }
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
            }).catch(function () {
                showCopyResult(oButton, fallbackCopyLink(sLink));
            });
            return;
        }
        showCopyResult(oButton, fallbackCopyLink(sLink));
    }
    for (var iI = 0; iI < aButtons.length; iI += 1) {
        aButtons[iI].addEventListener("click", function () {
            copyLink(this);
        });
    }
});

document.addEventListener("DOMContentLoaded", function () {
    var aRows = document.querySelectorAll("table tbody tr");
    var oOrderDetail = document.getElementById("order-detail");
    var oMessageBox = document.getElementById("message-box");
    var aSelectedOrderIds = [];
    var sLockedOrderId = "";
    var sHoverColor = "#fff3cd";
    var sSelectedColor = "#cfe2ff";
    if (oMessageBox) {
        setTimeout(function () {
            oMessageBox.style.display = "none";
        }, 10000);
    }
    function getGroupColor(oRow) {
        var sValue = oRow.getAttribute("data-order-no") || oRow.getAttribute("data-order-id") || "";
        var iHash = 0;
        if (!sValue) {
            return sSelectedColor;
        }
        for (var iI = 0; iI < sValue.length; iI += 1) {
            iHash = ((iHash << 5) - iHash) + sValue.charCodeAt(iI);
            iHash = iHash & iHash;
        }
        return "hsl(" + (Math.abs(iHash) % 360) + ", 72%, 84%)";
    }
    function applyRowColor(oRow) {
        oRow.style.backgroundColor = getCurrentColor(oRow);
    }
    function getCurrentColor(oRow) {
        if (oRow.getAttribute("data-saved") == "1") {
            return "#dff0d8";
        }
        if (oRow.getAttribute("data-confirming") == "1") {
            return "#cfe2ff";
        }
        if (oRow.getAttribute("data-selected") == "1") {
            return getGroupColor(oRow);
        }
        if (oRow.getAttribute("data-hover") == "1") {
            return sHoverColor;
        }
        return "";
    }
    function applyOrderDetailColor(oRow) {
        if (oOrderDetail) {
            oOrderDetail.style.backgroundColor = getCurrentColor(oRow);
        }
    }
    function getRelatedRows(oRow) {
        var sOrderId = oRow.getAttribute("data-order-id");
        if (!sOrderId) {
            return [oRow];
        }
        return document.querySelectorAll("table tbody tr[data-order-id=\"" + sOrderId + "\"]");
    }
    function getFirstRowByOrderId(sOrderId) {
        if (!sOrderId) {
            return null;
        }
        return document.querySelector("table tbody tr[data-order-id=\"" + sOrderId + "\"]");
    }
    function setRelatedRowsAttribute(oRow, sName, sValue) {
        var aRelatedRows = getRelatedRows(oRow);
        for (var iI = 0; iI < aRelatedRows.length; iI += 1) {
            aRelatedRows[iI].setAttribute(sName, sValue);
            applyRowColor(aRelatedRows[iI]);
        }
    }
    function setOrderDetailText(sName, sValue) {
        var oElement = document.querySelector('[data-detail="' + sName + '"]');
        if (oElement) {
            oElement.textContent = sValue || "\u2014";
        }
    }
    function updateOrderDetail(oRow) {
        var sPrice = oRow.getAttribute("data-price") || "";
        var sCurrency = oRow.getAttribute("data-currency") || "";
        setOrderDetailText("lab", oRow.getAttribute("data-lab"));
        setOrderDetailText("bag-no", oRow.getAttribute("data-bag-no"));
        setOrderDetailText("order-no", oRow.getAttribute("data-order-no"));
        setOrderDetailText("price", sPrice ? (sPrice + (sCurrency ? " " + sCurrency : "")) : "");
        setOrderDetailText("order-date", oRow.getAttribute("data-order-date"));
        setOrderDetailText("return-date", oRow.getAttribute("data-return-date"));
        setOrderDetailText("invoice-date", oRow.getAttribute("data-invoice-date"));
        setOrderDetailText("film-scan-dates", oRow.getAttribute("data-film-scan-dates"));
        setOrderDetailText("lab-scan-dates", oRow.getAttribute("data-lab-scan-dates"));
        applyOrderDetailColor(oRow);
    }
    function clearOrderDetail() {
        setOrderDetailText("lab", "");
        setOrderDetailText("bag-no", "");
        setOrderDetailText("order-no", "");
        setOrderDetailText("price", "");
        setOrderDetailText("order-date", "");
        setOrderDetailText("return-date", "");
        setOrderDetailText("invoice-date", "");
        setOrderDetailText("film-scan-dates", "");
        setOrderDetailText("lab-scan-dates", "");
        if (oOrderDetail) {
            oOrderDetail.style.backgroundColor = "";
        }
    }
    function removeSelectedOrderId(sOrderId) {
        for (var iI = aSelectedOrderIds.length - 1; iI >= 0; iI -= 1) {
            if (aSelectedOrderIds[iI] == sOrderId) {
                aSelectedOrderIds.splice(iI, 1);
            }
        }
    }
    function setLockedOrderFromLastSelected() {
        var oRow = null;
        sLockedOrderId = aSelectedOrderIds.length ? aSelectedOrderIds[aSelectedOrderIds.length - 1] : "";
        oRow = getFirstRowByOrderId(sLockedOrderId);
        if (oRow) {
            updateOrderDetail(oRow);
        } else {
            clearOrderDetail();
        }
    }
    function hasGroupBehavior(oRow) {
        return !!oRow.getAttribute("data-order-id");
    }
    function closeConfirmDialog(oDialog) {
        if (oDialog) {
            document.body.removeChild(oDialog);
            unlockAdminModalScroll();
        }
    }
    function setConfirmDialogText(oElement, sFilmRoll, sLabBag) {
        var oFilm = document.createElement("strong");
        var oBag = document.createElement("strong");
        oFilm.textContent = sFilmRoll || "\u2014";
        oBag.textContent = sLabBag || "\u2014";
        oElement.appendChild(document.createTextNode("The film roll "));
        oElement.appendChild(oFilm);
        oElement.appendChild(document.createElement("br"));
        oElement.appendChild(document.createTextNode(" will be unassigned from the lab bag "));
        oElement.appendChild(oBag);
        oElement.appendChild(document.createTextNode("."));
    }
    function openConfirmDialog(sAction, sUnassignId, sFilmRoll, sLabBag, oRow) {
        var oDialog = document.createElement("div");
        var oForm = document.createElement("form");
        var oHeader = document.createElement("div");
        var oTitle = document.createElement("strong");
        var oClose = document.createElement("button");
        var oHidden = document.createElement("input");
        var oText = document.createElement("p");
        var oActions = document.createElement("div");
        var oConfirm = document.createElement("button");
        var oCancel = document.createElement("button");
        var closeOnEscape = function (oEvent) {
            if (oEvent.key == "Escape") {
                closeDialog();
            }
        };
        var closeDialog = function (blSaved) {
            document.removeEventListener("keydown", closeOnEscape);
            if (oRow) {
                finishAdminSubjectRowEdit(oRow, blSaved === true);
                if (blSaved === true) {
                    oRow.setAttribute("data-confirming", "0");
                    oRow.setAttribute("data-saved", "1");
                    applyRowColor(oRow);
                    window.setTimeout(function () {
                        oRow.setAttribute("data-saved", "0");
                        applyRowColor(oRow);
                    }, 1400);
                } else {
                    window.setTimeout(function () {
                        oRow.setAttribute("data-confirming", "0");
                        applyRowColor(oRow);
                    }, 1000);
                }
            }
            closeConfirmDialog(oDialog);
        };
        oDialog.className = "confirm-dialog";
        oForm.className = "confirm-dialog-box";
        oForm.method = "post";
        oForm.action = sAction;
        oHeader.className = "confirm-dialog-header";
        oTitle.textContent = "Confirm Unassignment";
        oClose.type = "button";
        oClose.className = "confirm-dialog-close";
        oClose.setAttribute("aria-label", "Close");
        oClose.textContent = "\u00D7";
        oHidden.type = "hidden";
        oHidden.name = "unassign";
        oHidden.value = sUnassignId;
        oActions.className = "confirm-dialog-actions";
        setConfirmDialogText(oText, sFilmRoll, sLabBag);
        oConfirm.type = "submit";
        oConfirm.className = "confirm-dialog-button";
        oConfirm.textContent = "Yes";
        oCancel.type = "button";
        oCancel.className = "confirm-dialog-button";
        oCancel.textContent = "No";
        oActions.appendChild(oConfirm);
        oActions.appendChild(oCancel);
        oHeader.appendChild(oTitle);
        oHeader.appendChild(oClose);
        oForm.appendChild(oHidden);
        oForm.appendChild(oHeader);
        enableAdminDialogDrag(oDialog, oForm, oHeader);
        oForm.appendChild(oText);
        oForm.appendChild(oActions);
        oDialog.appendChild(oForm);
        document.body.appendChild(oDialog);
        lockAdminModalScroll();
        if (oRow) {
            oRow.setAttribute("data-confirming", "1");
            beginAdminSubjectRowEdit(oRow);
            applyRowColor(oRow);
        }
        oConfirm.focus();
        oForm.addEventListener("submit", function () {
            if (oRow) {
                finishAdminSubjectRowEdit(oRow, true);
                oRow.setAttribute("data-confirming", "0");
                oRow.setAttribute("data-saved", "1");
                applyRowColor(oRow);
            }
        });
        oCancel.addEventListener("click", function () {
            closeDialog();
        });
        oClose.addEventListener("click", function () {
            closeDialog();
        });
        document.addEventListener("keydown", closeOnEscape);
    }
    document.addEventListener("click", function (oEvent) {
        var oButton = oEvent.target.closest ? oEvent.target.closest(".js-confirm-unassign") : null;
        if (oButton) {
            oEvent.preventDefault();
            oEvent.stopPropagation();
            openConfirmDialog(oButton.getAttribute("data-confirm-action"), oButton.getAttribute("data-unassign-id"), oButton.getAttribute("data-film-roll"), oButton.getAttribute("data-lab-bag"), oButton.closest("tr"));
        }
    }, true);
    for (var iI = 0; iI < aRows.length; iI += 1) {
        aRows[iI].addEventListener("mouseenter", function () {
            setRelatedRowsAttribute(this, "data-hover", "1");
            if (!sLockedOrderId && hasGroupBehavior(this)) {
                updateOrderDetail(this);
            }
        });
        aRows[iI].addEventListener("mouseleave", function () {
            setRelatedRowsAttribute(this, "data-hover", "0");
            if (!sLockedOrderId && hasGroupBehavior(this)) {
                if (this.getAttribute("data-selected") == "1") {
                    updateOrderDetail(this);
                } else {
                    clearOrderDetail();
                }
            }
        });
        aRows[iI].addEventListener("click", function () {
            var sSelected = this.getAttribute("data-selected") == "1" ? "0" : "1";
            var sOrderId = this.getAttribute("data-order-id");
            setRelatedRowsAttribute(this, "data-selected", sSelected);
            if (sOrderId) {
                removeSelectedOrderId(sOrderId);
                if (sSelected == "1") {
                    aSelectedOrderIds.push(sOrderId);
                }
                setLockedOrderFromLastSelected();
            } else {
                clearOrderDetail();
            }
        });
    }
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
            "x": (sSide == "right" ? oRowRect.right : oRowRect.left) - oCanvasRect.left,
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
            sPath += " L " + oBefore.x + " " + oBefore.y
                + " Q " + oPoint.x + " " + oPoint.y
                + " " + oAfter.x + " " + oAfter.y;
        }
        sPath += " L " + aPoints[aPoints.length - 1].x + " " + aPoints[aPoints.length - 1].y;
        return sPath;
    }
    function removeSchemaRelationElements() {
        var aElements = oSvg.querySelectorAll(".schema-relation,.schema-relation-source,.schema-relation-target");
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
            var aSides = aRoute ? {"source":aRoute.source, "target":aRoute.target} : getSides(oSourceTableRect, oTargetTableRect, iI);
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
                var aPoints = new Array();
                aPoints.push(oStart);
                aPoints.push(oControl1);
                if (!isNaN(iRouteViaX) && !isNaN(iRouteViaY)) {
                    aPoints.push({"x": iRouteViaX, "y": oControl1.y});
                    aPoints.push({"x": iRouteViaX, "y": iRouteViaY});
                    aPoints.push({"x": oControl2.x, "y": iRouteViaY});
                } else if (!isNaN(iRouteViaX)) {
                    aPoints.push({"x": iRouteViaX, "y": oControl1.y});
                    aPoints.push({"x": iRouteViaX, "y": oControl2.y});
                } else {
                    aPoints.push({"x": oControl1.x, "y": iRouteViaY});
                    aPoints.push({"x": oControl2.x, "y": iRouteViaY});
                }
                aPoints.push(oControl2);
                aPoints.push(oEnd);
                sPath = getRoundedPolylinePath(aPoints, 18);
            } else {
                sPath = "M " + oStart.x + " " + oStart.y
                    + " C " + oControl1.x + " " + oControl1.y
                    + ", " + oControl2.x + " " + oControl2.y
                    + ", " + oEnd.x + " " + oEnd.y;
            }
            var oPath = document.createElementNS("http://www.w3.org/2000/svg", "path");
            var oCircle = document.createElementNS("http://www.w3.org/2000/svg", "circle");
            var oTargetCircle = document.createElementNS("http://www.w3.org/2000/svg", "circle");
            var oTitle = document.createElementNS("http://www.w3.org/2000/svg", "title");
            oPath.setAttribute("class", "schema-relation");
            oPath.setAttribute("marker-end", "url(#schema-arrow)");
            oPath.setAttribute("d", sPath);
            oTitle.appendChild(document.createTextNode(
                oRelation.getAttribute("data-source-table") + "."
                + oRelation.getAttribute("data-source-column") + " -> "
                + oRelation.getAttribute("data-target-table") + "."
                + oRelation.getAttribute("data-target-column")));
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
    function sendQuickTableFilterValue(oFilter, sAction) {
        var oData;
        if (!window.fetch || !window.FormData || !oFilter || !oFilter.id) {
            return;
        }
        oData = new FormData();
        oData.append("quick_table_filter_action", sAction);
        oData.append("filter_id", oFilter.id);
        if (sAction == "save") {
            oData.append("filter_value", oFilter.value);
        }
        window.fetch(window.location.href, {
            "method": "POST",
            "credentials": "same-origin",
            "headers": getAdminAjaxHeaders(),
            "body": oData
        }).catch(function () {
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
        function isFilterActive() {
            return oFilter.value.replace(/^\s+|\s+$/g, "") !== "";
        }
        function getRowFilterText(oRow) {
            if (typeof oRow._quickTableFilterText != "string") {
                oRow._quickTableFilterText = oRow.textContent || "";
            }
            return oRow._quickTableFilterText;
        }
        function setRowFilterVisible(oRow, blVisible) {
            var sDisplay = blVisible ? "" : "none";
            if (oRow.style.display != sDisplay) {
                oRow.style.display = sDisplay;
            }
        }
        var filterTable = function () {
            var oTable = document.getElementById(oFilter.getAttribute("data-table-filter"));
            var aExpression = buildFilterExpression(oFilter.value);
            var aRows;
            refreshFilterFocusButton(oFilter);
            if (!oTable) {
                return;
            }
            aRows = oTable.querySelectorAll("tbody tr");
            for (var iJ = 0; iJ < aRows.length; iJ += 1) {
                setRowFilterVisible(aRows[iJ], rowMatchesFilterExpression(getRowFilterText(aRows[iJ]), aExpression));
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
        function insertFilterOperator(sOperator) {
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
        }
        function resetFilter() {
            oFilter.value = "";
            runFilterTable();
            if (oFilter._quickTableFilterTimer) {
                window.clearTimeout(oFilter._quickTableFilterTimer);
                oFilter._quickTableFilterTimer = null;
            }
            sendQuickTableFilterValue(oFilter, "reset");
            oFilter.focus();
        }
        oFilter.addEventListener("input", function () {
            scheduleFilterTable();
            scheduleQuickTableFilterSave(oFilter);
        });
        for (var iI = 0; iI < aOperatorButtons.length; iI += 1) {
            aOperatorButtons[iI].addEventListener("click", function () {
                insertFilterOperator(this.getAttribute("data-filter-operator") || "");
            });
        }
        for (var iI = 0; iI < aResetButtons.length; iI += 1) {
            aResetButtons[iI].addEventListener("click", function () {
                resetFilter();
            });
        }
        refreshFilterFocusButton(oFilter);
        if (isFilterActive()) {
            scheduleFilterTable();
        }
        window.setTimeout(function () {
            refreshFilterFocusButton(oFilter);
        }, 0);
        window.addEventListener("pageshow", function () {
            refreshFilterFocusButton(oFilter);
        });
        try {
            oFilter.focus({"preventScroll":true});
        } catch (oException) {
            oFilter.focus();
        }
        selectAdminTextField(oFilter);
    }
    for (var iI = 0; iI < aFilters.length; iI += 1) {
        initializeTableFilter(aFilters[iI]);
    }
});
document.addEventListener("DOMContentLoaded", function () {
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
    } catch (oException) {}
    function prepareAudio() {
        var AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext) {
            return Promise.resolve(false);
        }
        if (!oAudioContext || oAudioContext.state == "closed") {
            oAudioContext = new AudioContext();
        }
        if (oAudioContext.state == "suspended") {
            return oAudioContext.resume().then(function () {
                return oAudioContext.state == "running";
            }).catch(function () {
                return false;
            });
        }
        return Promise.resolve(oAudioContext.state == "running");
    }
    function playChime() {
        return prepareAudio().then(function (blAudioReady) {
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
        }).then(function (oResponse) {
            return oResponse.text();
        }).then(function (sHtml) {
            var oDocument = new DOMParser().parseFromString(sHtml, "text/html");
            var oNewAutoRefresh = oDocument.querySelector(".js-auto-refresh");
            var iNewLatestId = oNewAutoRefresh ? parseInt(oNewAutoRefresh.getAttribute("data-latest-id") || "0", 10) : iLatestId;
            if (iNewLatestId > iLatestId) {
                playChime().then(function () {
                    window.setTimeout(function () {
                        window.location.reload();
                    }, 700);
                });
                return;
            }
            scheduleRefreshCheck();
        }).catch(function () {
            scheduleRefreshCheck();
        });
    }
    oAutoRefresh.addEventListener("change", function () {
        try {
            window.localStorage.setItem(sStorageKey, oAutoRefresh.checked ? "1" : "0");
        } catch (oException) {}
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
});
document.addEventListener("DOMContentLoaded", function () {
    var aUserAgents = document.querySelectorAll(".js-user-agent");
    var iI;
    if (!window.bowser || typeof window.bowser.parse != "function") {
        return;
    }
    function formatUserAgent(oElement) {
        var sUserAgent = oElement.getAttribute("data-user-agent") || "";
        var oResult;
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
        try {
            oResult = window.bowser.parse(sUserAgent);
        } catch (oException) {
            return sUserAgent;
        }
        if (!sBrowserName && oResult.browser && oResult.browser.name) {
            sBrowserName = oResult.browser.name;
        }
        if (!sBrowserVersion && oResult.browser && oResult.browser.version) {
            sBrowserVersion = oResult.browser.version;
        }
        if (!sOsName && oResult.os && oResult.os.name) {
            sOsName = oResult.os.name;
        }
        if (!sOsVersion && oResult.os) {
            sOsVersion = oResult.os.versionName || oResult.os.version || "";
        }
        if (!sPlatform && oResult.platform && oResult.platform.type) {
            sPlatform = oResult.platform.type;
        }
        if (!sDeviceVendor && oResult.platform && oResult.platform.vendor) {
            sDeviceVendor = oResult.platform.vendor;
        }
        if (!sDeviceModel && oResult.platform && oResult.platform.model) {
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
    for (iI = 0; iI < aUserAgents.length; iI += 1) {
        aUserAgents[iI].textContent = formatUserAgent(aUserAgents[iI]);
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
