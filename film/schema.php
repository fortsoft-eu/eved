<?php

include "main.php";


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


$aTables = array();
$aRelations = array();
$aForeignKeys = array();
try {
    $oStatement = $oPdo->query("SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, EXTRA FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME, ORDINAL_POSITION");
    while ($aRow = $oStatement->fetch()) {
        if (!preg_match("/^fs_(film|photo|flickr)_[a-zA-Z0-9_]+$/", $aRow["TABLE_NAME"])) {
            continue;
        }
        if (!isset($aTables[$aRow["TABLE_NAME"]])) {
            $aTables[$aRow["TABLE_NAME"]] = array();
        }
        $aTables[$aRow["TABLE_NAME"]][] = $aRow;
    }

    $oStatement = $oPdo->query("SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL ORDER BY TABLE_NAME, CONSTRAINT_NAME, ORDINAL_POSITION");
    while ($aRow = $oStatement->fetch()) {
        if (!preg_match("/^fs_(film|photo|flickr)_[a-zA-Z0-9_]+$/", $aRow["TABLE_NAME"])
            || !preg_match("/^fs_(film|photo|flickr)_[a-zA-Z0-9_]+$/", $aRow["REFERENCED_TABLE_NAME"])) {
            continue;
        }
        $aRelations[] = $aRow;
        $aForeignKeys[$aRow["TABLE_NAME"] . "." . $aRow["COLUMN_NAME"]] = true;
    }
} catch (Exception $oException) {
    error_log((string)$oException);
    send500AndExit("Database error: " . $oException->getMessage());
}

$aSchemaRelationRoutes = array(
    "fs_film_exposure_dates.film_scan_id>fs_film_scans.id" => array("source" => "right", "target" => "left", "curve" => "70"),
    "fs_film_photos.scan_id>fs_film_scans.id" => array("source" => "left", "target" => "right", "curve" => "70"),
    "fs_film_scans.lab_order_id>fs_photo_lab_orders.id" => array("source" => "left", "target" => "right", "curve" => "42"),
    "fs_photo_equip_groups.equip_id>fs_photo_equip.id" => array("source" => "right", "target" => "right", "curve" => "24", "target-y" => "0", "via-x-offset" => "86"),
    "fs_photo_equip_groups.member_equip_id>fs_photo_equip.id" => array("source" => "right", "target" => "right", "curve" => "24", "target-y" => "-24", "via-x-offset" => "42"),
    "fs_photo_tags.equip_id>fs_photo_equip.id" => array("source" => "right", "target" => "right", "curve" => "24", "target-y" => "24", "via-x-offset" => "42")
);

$iTime = sendPageHeaders();

?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta name="author" content="Petr Červinka &lt;cervinka@fortsoft.cz&gt;">
  <meta name="contact" content="cervinka@fortsoft.cz">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <link rel="icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <title><?php echo html(getPageTitleText()); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo $sBaseUrl; ?>css/style.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style.css")); ?>" rel="stylesheet" type="text/css" title="Original">
  <link href="<?php echo $sBaseUrl; ?>css/style-graphite.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-graphite.css")); ?>" rel="alternate stylesheet" type="text/css" title="Graphite">
  <link href="<?php echo $sBaseUrl; ?>css/style-midnight.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-midnight.css")); ?>" rel="alternate stylesheet" type="text/css" title="Midnight">
  <link href="<?php echo $sBaseUrl; ?>css/style-slate.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-slate.css")); ?>" rel="alternate stylesheet" type="text/css" title="Slate">
  <link href="<?php echo $sBaseUrl; ?>css/style-sepia.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-sepia.css")); ?>" rel="alternate stylesheet" type="text/css" title="Sepia">
  <link href="<?php echo $sBaseUrl; ?>css/style-sand.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-sand.css")); ?>" rel="alternate stylesheet" type="text/css" title="Sand">
  <link href="<?php echo $sBaseUrl; ?>css/style-forest.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-forest.css")); ?>" rel="alternate stylesheet" type="text/css" title="Forest">
  <link href="<?php echo $sBaseUrl; ?>css/style-moss.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-moss.css")); ?>" rel="alternate stylesheet" type="text/css" title="Moss">
  <link href="<?php echo $sBaseUrl; ?>css/style-ocean.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-ocean.css")); ?>" rel="alternate stylesheet" type="text/css" title="Ocean">
  <link href="<?php echo $sBaseUrl; ?>css/style-ice.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-ice.css")); ?>" rel="alternate stylesheet" type="text/css" title="Ice">
  <link href="<?php echo $sBaseUrl; ?>css/style-lavender.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-lavender.css")); ?>" rel="alternate stylesheet" type="text/css" title="Lavender">
  <link href="<?php echo $sBaseUrl; ?>css/style-rose.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-rose.css")); ?>" rel="alternate stylesheet" type="text/css" title="Rose">
  <link href="<?php echo $sBaseUrl; ?>css/style-copper.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-copper.css")); ?>" rel="alternate stylesheet" type="text/css" title="Copper">
  <link href="<?php echo $sBaseUrl; ?>css/style-burgundy.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-burgundy.css")); ?>" rel="alternate stylesheet" type="text/css" title="Burgundy">
  <link href="<?php echo $sBaseUrl; ?>css/style-monochrome.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-monochrome.css")); ?>" rel="alternate stylesheet" type="text/css" title="Monochrome">
  <link href="<?php echo $sBaseUrl; ?>css/style-high-contrast.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-high-contrast.css")); ?>" rel="alternate stylesheet" type="text/css" title="High Contrast">
  <link href="<?php echo $sBaseUrl; ?>css/style-soft.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-soft.css")); ?>" rel="alternate stylesheet" type="text/css" title="Soft">
  <link href="<?php echo $sBaseUrl; ?>css/style-paper.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-paper.css")); ?>" rel="alternate stylesheet" type="text/css" title="Paper">
  <link href="<?php echo $sBaseUrl; ?>css/style-terminal.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-terminal.css")); ?>" rel="alternate stylesheet" type="text/css" title="Terminal">
  <link href="<?php echo $sBaseUrl; ?>css/style-cobalt.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-cobalt.css")); ?>" rel="alternate stylesheet" type="text/css" title="Cobalt">
  <link href="<?php echo $sBaseUrl; ?>css/style-plum.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-plum.css")); ?>" rel="alternate stylesheet" type="text/css" title="Plum">
  <script type="text/javascript" src="/js/style.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/../js/style.js")); ?>"></script>
</head>
<body>
  <p class="admin-controls">
<?php

renderMenu();

?>
    <span class="menu png-export-menu js-schema-save-png-menu" data-menu>
      <button type="button" class="button-link png-export-menu-button" data-menu-button aria-haspopup="true" aria-expanded="false">Save PNG<span class="png-export-menu-arrow" aria-hidden="true"><?php echo $sCalendarToggleEmoji; ?></span></button>
      <span class="menu-panel png-export-menu-panel" data-menu-panel hidden>
        <button type="button" class="menu-link png-export-menu-option js-schema-save-png" data-file-name="film_database_schema" data-scale="1">Scale 1:1</button>
        <button type="button" class="menu-link png-export-menu-option js-schema-save-png" data-file-name="film_database_schema" data-scale="2">Scale 2:1</button>
        <button type="button" class="menu-link png-export-menu-option js-schema-save-png" data-file-name="film_database_schema" data-scale="3">Scale 3:1</button>
        <button type="button" class="menu-link png-export-menu-option js-schema-save-png" data-file-name="film_database_schema" data-scale="4">Scale 4:1</button>
        <button type="button" class="menu-link png-export-menu-option js-schema-save-png" data-file-name="film_database_schema" data-scale="5">Scale 5:1</button>
      </span>
    </span>
  </p>
  <p class="schema-unavailable-message"><strong>Database Schema: </strong>The database schema cannot be displayed on this device.</p>
  <div class="schema-diagram" id="schema-diagram">
    <div class="schema-canvas" id="schema-canvas">
      <svg class="schema-lines" id="schema-lines" aria-hidden="true">
        <defs>
          <marker id="schema-arrow" markerWidth="8" markerHeight="8" refX="7" refY="4" orient="auto" markerUnits="strokeWidth">
            <path d="M0,0 L8,4 L0,8 Z" fill="#A32929"></path>
          </marker>
        </defs>
      </svg>
      <div class="schema-grid">
<?php

foreach ($aTables as $sTableName => $aColumns) {
    echo "        <table class=\"schema-table\" data-table=\"" . html($sTableName) . "\">\n",
        "          <caption>" . html($sTableName) . "</caption>\n",
        "          <colgroup>\n",
        "            <col class=\"schema-col-key\">\n",
        "            <col class=\"schema-col-column\">\n",
        "            <col class=\"schema-col-type\">\n",
        "            <col class=\"schema-col-null\">\n",
        "            <col class=\"schema-col-extra\">\n",
        "          </colgroup>\n",
        "          <thead>\n",
        "            <tr>\n",
        "              <th>Key</th>\n",
        "              <th>Column</th>\n",
        "              <th>Type</th>\n",
        "              <th>Null</th>\n",
        "              <th>Extra</th>\n",
        "            </tr>\n",
        "          </thead>\n",
        "          <tbody>\n";
    foreach ($aColumns as $aColumn) {
        $sKey = "";
        $sKeyClass = "";
        $sColumnType = $aColumn["COLUMN_TYPE"];
        $sColumnTypeDisplay = schemaColumnTypeDisplay($sColumnType);
        $sColumnTypeTitleDisplay = schemaColumnTypeDisplay($sColumnType, false);
        $sColumnTypeTitle = $sColumnTypeDisplay != $sColumnTypeTitleDisplay ? " title=\"" . str_replace("…", "&hellip;", html($sColumnTypeTitleDisplay)) . "\"" : "";
        if ($aColumn["COLUMN_KEY"] == "PRI") {
            $sKey = "PK";
            $sKeyClass = " schema-key-pk";
        } elseif (isset($aForeignKeys[$sTableName . "." . $aColumn["COLUMN_NAME"]])) {
            $sKey = "FK";
            $sKeyClass = " schema-key-fk";
        } elseif ($aColumn["COLUMN_KEY"] == "UNI") {
            $sKey = "UQ";
        }
        $sColumnId = "column-" . preg_replace("/[^a-zA-Z0-9_-]/", "-", $sTableName . "-" . $aColumn["COLUMN_NAME"]);
        echo "            <tr id=\"" . html($sColumnId) . "\">\n",
            "              <td class=\"schema-key" . $sKeyClass . "\">" . $sKey . "</td>\n",
            "              <td>" . html($aColumn["COLUMN_NAME"]) . "</td>\n",
            "              <td class=\"schema-column-type\"" . $sColumnTypeTitle . ">" . str_replace("…", "&hellip;", html($sColumnTypeDisplay)) . "</td>\n",
            "              <td class=\"schema-null\">" . ($aColumn["IS_NULLABLE"] == "YES" ? "Yes" : "No") . "</td>\n",
            "              <td>" . html($aColumn["EXTRA"]) . "</td>\n",
            "            </tr>\n";
    }
    echo "          </tbody>\n",
        "        </table>\n";
}
echo "      </div>\n",
    "    </div>\n",
    "  </div>\n";
if (!$aRelations) {
    echo "  <p>No records found.</p>\n";
} else {
    echo "  <table class=\"schema-relations\">\n",
        "    <thead>\n",
        "      <tr>\n",
        "        <th>Constraint</th>\n",
        "        <th>Foreign Key</th>\n",
        "        <th>References</th>\n",
        "      </tr>\n",
        "    </thead>\n",
        "    <tbody>\n";
    foreach ($aRelations as $aRelation) {
        $sRelationKey = $aRelation["TABLE_NAME"] . "." . $aRelation["COLUMN_NAME"] . ">" . $aRelation["REFERENCED_TABLE_NAME"] . "." . $aRelation["REFERENCED_COLUMN_NAME"];
        $sRouteAttributes = "";
        if (isset($aSchemaRelationRoutes[$sRelationKey])) {
            $sRouteAttributes = " data-source-side=\"" . html($aSchemaRelationRoutes[$sRelationKey]["source"]) . "\""
                . " data-target-side=\"" . html($aSchemaRelationRoutes[$sRelationKey]["target"]) . "\"";
            if (isset($aSchemaRelationRoutes[$sRelationKey]["curve"])) {
                $sRouteAttributes .= " data-curve=\"" . html($aSchemaRelationRoutes[$sRelationKey]["curve"]) . "\"";
            }
            if (isset($aSchemaRelationRoutes[$sRelationKey]["source-x"])) {
                $sRouteAttributes .= " data-source-x-offset=\"" . html($aSchemaRelationRoutes[$sRelationKey]["source-x"]) . "\"";
            }
            if (isset($aSchemaRelationRoutes[$sRelationKey]["source-y"])) {
                $sRouteAttributes .= " data-source-y-offset=\"" . html($aSchemaRelationRoutes[$sRelationKey]["source-y"]) . "\"";
            }
            if (isset($aSchemaRelationRoutes[$sRelationKey]["target-x"])) {
                $sRouteAttributes .= " data-target-x-offset=\"" . html($aSchemaRelationRoutes[$sRelationKey]["target-x"]) . "\"";
            }
            if (isset($aSchemaRelationRoutes[$sRelationKey]["target-y"])) {
                $sRouteAttributes .= " data-target-y-offset=\"" . html($aSchemaRelationRoutes[$sRelationKey]["target-y"]) . "\"";
            }
            if (isset($aSchemaRelationRoutes[$sRelationKey]["via-x"])) {
                $sRouteAttributes .= " data-via-x=\"" . html($aSchemaRelationRoutes[$sRelationKey]["via-x"]) . "\"";
            }
            if (isset($aSchemaRelationRoutes[$sRelationKey]["via-x-offset"])) {
                $sRouteAttributes .= " data-via-x-offset=\"" . html($aSchemaRelationRoutes[$sRelationKey]["via-x-offset"]) . "\"";
            }
            if (isset($aSchemaRelationRoutes[$sRelationKey]["via-y"])) {
                $sRouteAttributes .= " data-via-y=\"" . html($aSchemaRelationRoutes[$sRelationKey]["via-y"]) . "\"";
            }
            if (isset($aSchemaRelationRoutes[$sRelationKey]["via-y-offset"])) {
                $sRouteAttributes .= " data-via-y-offset=\"" . html($aSchemaRelationRoutes[$sRelationKey]["via-y-offset"]) . "\"";
            }
            if (isset($aSchemaRelationRoutes[$sRelationKey]["via-table-bottom-offset"])) {
                $sRouteAttributes .= " data-via-table-bottom-offset=\"" . html($aSchemaRelationRoutes[$sRelationKey]["via-table-bottom-offset"]) . "\"";
            }
        }
        echo "      <tr data-source-table=\"" . html($aRelation["TABLE_NAME"]),
            "\" data-source-column=\"" . html($aRelation["COLUMN_NAME"]),
            "\" data-target-table=\"" . html($aRelation["REFERENCED_TABLE_NAME"]),
            "\" data-target-column=\"" . html($aRelation["REFERENCED_COLUMN_NAME"]) . "\"",
            $sRouteAttributes . ">\n",
            "        <td>" . html($aRelation["CONSTRAINT_NAME"]) . "</td>\n",
            "        <td>" . html($aRelation["TABLE_NAME"] . "." . $aRelation["COLUMN_NAME"]) . "</td>\n",
            "        <td>" . html($aRelation["REFERENCED_TABLE_NAME"] . "." . $aRelation["REFERENCED_COLUMN_NAME"]) . "</td>\n",
            "      </tr>\n";
    }
    echo "    </tbody>\n",
        "  </table>\n";
}

?>
  <script type="text/javascript" src="<?php echo $sOrigin; ?>/vendors/snapdom-2.23.1/snapdom.js"></script>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
