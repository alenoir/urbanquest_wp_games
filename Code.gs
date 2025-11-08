/****************************
 * CONFIGURATION
 ****************************/
const WP_URL = "https://urbanquest.fr"; // ton domaine WP
const POST_TYPE = "game"; // ton CPT
const WP_USER = "antoine.alenoir@gmail.com"; // ton utilisateur WP
const WP_APP_PASSWORD = "Z9kr Uhpd AYko ugoe g8Kx sbcB"; // ton mot de passe d'application

/****************************
 * MENU PERSONNALISÉ GOOGLE SHEETS
 ****************************/

/**
 * Crée le menu personnalisé
 */
function createCustomMenu() {
  const ui = SpreadsheetApp.getUi();

  ui.createMenu("🔄 WordPress Sync")
    .addItem("📥 Synchroniser depuis WordPress", "menuSyncFromWordPress")
    .addSeparator()
    .addItem(
      "📤 Pousser vers WordPress (feuille actuelle)",
      "menuPushCurrentSheet"
    )
    .addItem("📤 Pousser TOUT vers WordPress", "menuPushAllToWordPress")
    .addSeparator()
    .addSubMenu(
      ui
        .createMenu("📤 Pousser un post type spécifique")
        .addItem("Country", "menuPushCountry")
        .addItem("Region", "menuPushRegion")
        .addItem("Departement", "menuPushDepartement")
        .addItem("Ville", "menuPushVille")
        .addItem("Game", "menuPushGame")
    )
    .addSeparator()
    .addItem("✅ Valider les relations", "menuValidateRelations")
    .addItem("🔄 Mettre à jour les listes déroulantes", "menuUpdateDropdowns")
    .addSeparator()
    .addItem("🔍 Analyser la structure", "menuAnalyzeStructure")
    .addToUi();
}

/**
 * Crée le menu personnalisé quand le fichier s'ouvre
 */
function onOpen() {
  createCustomMenu();
}

/**
 * Fonctions wrapper pour le menu
 */
function menuSyncFromWordPress() {
  const ui = SpreadsheetApp.getUi();
  const response = ui.alert(
    "Synchronisation depuis WordPress",
    "Voulez-vous synchroniser toutes les données depuis WordPress et configurer les listes déroulantes ?",
    ui.ButtonSet.YES_NO
  );

  if (response === ui.Button.YES) {
    syncAllWithDropdowns();
  }
}

function menuPushCurrentSheet() {
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  const activeSheet = ss.getActiveSheet();
  const sheetName = activeSheet.getName();

  // Convertir le nom de la feuille en slug de post type
  const postTypeMap = {
    Country: "country",
    Region: "region",
    Departement: "departement",
    Ville: "ville",
    Game: "game",
  };

  const postTypeSlug = postTypeMap[sheetName];

  if (!postTypeSlug) {
    SpreadsheetApp.getActiveSpreadsheet().toast(
      `❌ Feuille "${sheetName}" non reconnue. Utilisez une feuille valide (Country, Region, Departement, Ville, Game)`
    );
    return;
  }

  const ui = SpreadsheetApp.getUi();
  const response = ui.alert(
    `Pousser vers WordPress`,
    `Voulez-vous pousser les données de la feuille "${sheetName}" vers WordPress ?\n\n⚠️ Cela écrasera les données existantes dans WordPress.`,
    ui.ButtonSet.YES_NO_CANCEL
  );

  if (response === ui.Button.YES) {
    pushPostTypeToWordPress(postTypeSlug);
  }
}

function menuPushAllToWordPress() {
  const ui = SpreadsheetApp.getUi();
  const response = ui.alert(
    "Pousser TOUT vers WordPress",
    "⚠️ ATTENTION : Cette action va pousser TOUTES les feuilles vers WordPress.\n\nCela écrasera les données existantes dans WordPress.\n\nVoulez-vous continuer ?",
    ui.ButtonSet.YES_NO_CANCEL
  );

  if (response === ui.Button.YES) {
    pushAllPostTypesToWordPress();
  }
}

function menuPushCountry() {
  const ui = SpreadsheetApp.getUi();
  const response = ui.alert(
    "Pousser Country vers WordPress",
    "Voulez-vous pousser les données de la feuille Country vers WordPress ?\n\n⚠️ Cela écrasera les données existantes.",
    ui.ButtonSet.YES_NO
  );
  if (response === ui.Button.YES) {
    pushPostTypeToWordPress("country");
  }
}

function menuPushRegion() {
  const ui = SpreadsheetApp.getUi();
  const response = ui.alert(
    "Pousser Region vers WordPress",
    "Voulez-vous pousser les données de la feuille Region vers WordPress ?\n\n⚠️ Cela écrasera les données existantes.",
    ui.ButtonSet.YES_NO
  );
  if (response === ui.Button.YES) {
    pushPostTypeToWordPress("region");
  }
}

function menuPushDepartement() {
  const ui = SpreadsheetApp.getUi();
  const response = ui.alert(
    "Pousser Departement vers WordPress",
    "Voulez-vous pousser les données de la feuille Departement vers WordPress ?\n\n⚠️ Cela écrasera les données existantes.",
    ui.ButtonSet.YES_NO
  );
  if (response === ui.Button.YES) {
    pushPostTypeToWordPress("departement");
  }
}

function menuPushVille() {
  const ui = SpreadsheetApp.getUi();
  const response = ui.alert(
    "Pousser Ville vers WordPress",
    "Voulez-vous pousser les données de la feuille Ville vers WordPress ?\n\n⚠️ Cela écrasera les données existantes.",
    ui.ButtonSet.YES_NO
  );
  if (response === ui.Button.YES) {
    pushPostTypeToWordPress("ville");
  }
}

function menuPushGame() {
  const ui = SpreadsheetApp.getUi();
  const response = ui.alert(
    "Pousser Game vers WordPress",
    "Voulez-vous pousser les données de la feuille Game vers WordPress ?\n\n⚠️ Cela écrasera les données existantes.",
    ui.ButtonSet.YES_NO
  );
  if (response === ui.Button.YES) {
    pushPostTypeToWordPress("game");
  }
}

function menuValidateRelations() {
  validateAllRelations();
}

function menuUpdateDropdowns() {
  updateAllRelationDropdowns();
}

function menuAnalyzeStructure() {
  analyzeTargetStructure();
}

function debugAcfFieldGroups() {
  const auth =
    "Basic " + Utilities.base64Encode(WP_USER + ":" + WP_APP_PASSWORD);
  const url = `${WP_URL}/wp-json/acf/v3/field_groups`;
  const res = UrlFetchApp.fetch(url, {
    headers: { Authorization: auth },
    muteHttpExceptions: true,
  });
  Logger.log("HTTP " + res.getResponseCode());
  Logger.log(res.getContentText().slice(0, 500));
}

function fetchAcfStructure() {
  const auth =
    "Basic " + Utilities.base64Encode(WP_USER + ":" + WP_APP_PASSWORD);
  const url = `${WP_URL}/wp-json/acf/v3/field_groups`;
  const res = UrlFetchApp.fetch(url, {
    headers: { Authorization: auth },
    muteHttpExceptions: true,
  });

  const groups = JSON.parse(res.getContentText());
  Logger.log("Nombre de field groups : " + groups.length);

  const structure = [];

  groups.forEach((group) => {
    // Récupère les champs de chaque field group
    const fieldsUrl = `${WP_URL}/wp-json/acf/v3/field_groups/${group.id}/fields`;
    const resFields = UrlFetchApp.fetch(fieldsUrl, {
      headers: { Authorization: auth },
      muteHttpExceptions: true,
    });
    const fields = JSON.parse(resFields.getContentText());

    fields.forEach((f) => {
      structure.push({
        group: group.title,
        name: f.name,
        label: f.label,
        type: f.type,
        required: f.required,
        key: f.key,
      });
    });
  });

  // Création / remplissage d’une feuille dédiée
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  const sheet =
    ss.getSheetByName("ACF_Structure") || ss.insertSheet("ACF_Structure");
  sheet.clearContents();
  sheet.appendRow(["Group", "Name", "Label", "Type", "Required", "Key"]);
  structure.forEach((row) => {
    sheet.appendRow([
      row.group,
      row.name,
      row.label,
      row.type,
      row.required,
      row.key,
    ]);
  });

  SpreadsheetApp.getActiveSpreadsheet().toast("✅ Structure ACF importée !");
}

/****************************
 * FETCH SCHEMA DYNAMIQUEMENT
 ****************************/
function getAcfSchema() {
  const url = `${WP_URL}/wp-json/acf/v3/fields`;
  const options = { headers: { Authorization: basicAuth() } };
  const response = UrlFetchApp.fetch(url, options);
  const data = JSON.parse(response.getContentText());

  const schema = {};
  data.forEach((f) => {
    schema[f.name] = f.label || f.name;
  });
  return schema; // { ville: "Ville", region: "Region", ... }
}

function fetchGamesPublic() {
  const url = "https://urbanquest.fr/wp-json/wp/v2/game?per_page=100";
  const res = UrlFetchApp.fetch(url);
  const posts = JSON.parse(res.getContentText());
  const sheet =
    SpreadsheetApp.getActiveSpreadsheet().getSheetByName("Games") ||
    SpreadsheetApp.getActiveSpreadsheet().insertSheet("Games");
  sheet.clearContents();

  const headers = [
    "id",
    "post_title",
    "ville",
    "point_de_depart",
    "region",
    "payment_url",
  ];
  sheet.appendRow(headers);

  posts.forEach((p) => {
    const acf = p.acf || {};
    sheet.appendRow([
      p.id,
      p.title.rendered,
      acf.ville || "",
      acf.point_de_depart || "",
      acf.region || "",
      acf.payment_url || "",
    ]);
  });

  SpreadsheetApp.getActiveSpreadsheet().toast(
    `✅ ${posts.length} jeux importés`
  );
}

/****************************
 * PUSH MODIFICATIONS → WORDPRESS
 ****************************/
function pushGamesToWordPress() {
  const sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName("Games");
  const rows = sheet.getDataRange().getValues();
  const headers = rows.shift();
  const baseCols = ["id", "post_title", "status"];
  const acfFields = headers.filter((h) => !baseCols.includes(h));
  const auth =
    "Basic " + Utilities.base64Encode(WP_USER + ":" + WP_APP_PASSWORD);

  rows.forEach((r, i) => {
    const rec = Object.fromEntries(headers.map((h, j) => [h, r[j]]));
    if (!rec.post_title) return;

    // --- Préparation des champs ACF ---
    const fields = {};
    acfFields.forEach((f) => {
      if (rec[f] !== "" && rec[f] != null) fields[f] = rec[f];
    });

    try {
      let res, code, body;

      if (rec.id) {
        // =======================
        // 🔁 1. UPDATE titre + statut via wp/v2
        // =======================
        const wpUrl = `${WP_URL}/wp-json/wp/v2/${POST_TYPE}/${rec.id}`;
        const wpPayload = {
          title: rec.post_title,
          status: rec.status || "publish",
        };
        res = UrlFetchApp.fetch(wpUrl, {
          method: "POST",
          headers: {
            Authorization: auth,
            "Content-Type": "application/json",
          },
          payload: JSON.stringify(wpPayload),
          muteHttpExceptions: true,
        });
        code = res.getResponseCode();
        body = JSON.parse(res.getContentText() || "{}");
        if (code >= 200 && code < 300) {
          Logger.log(
            `📝 Ligne ${i + 2} – titre mis à jour (${
              body.title?.rendered || rec.post_title
            })`
          );
        } else {
          Logger.log(
            `❌ Ligne ${i + 2} – échec titre (${code}) : ${res
              .getContentText()
              .slice(0, 120)}`
          );
        }

        // =======================
        // 🧩 2. UPDATE ACF via acf/v3
        // =======================
        const acfUrl = `${WP_URL}/wp-json/acf/v3/${POST_TYPE}/${rec.id}`;
        const acfPayload = { fields };
        res = UrlFetchApp.fetch(acfUrl, {
          method: "POST",
          headers: {
            Authorization: auth,
            "Content-Type": "application/json",
          },
          payload: JSON.stringify(acfPayload),
          muteHttpExceptions: true,
        });
        code = res.getResponseCode();
        if (code >= 200 && code < 300) {
          Logger.log(`✅ Ligne ${i + 2} – champs ACF mis à jour`);
        } else {
          Logger.log(
            `❌ Ligne ${i + 2} – échec ACF (${code}) : ${res
              .getContentText()
              .slice(0, 120)}`
          );
        }
      } else {
        // =======================
        // 🆕 3. CRÉATION NOUVELLE
        // =======================
        const createUrl = `${WP_URL}/wp-json/wp/v2/${POST_TYPE}`;
        const createPayload = {
          title: rec.post_title,
          status: "publish",
          acf: fields,
        };
        res = UrlFetchApp.fetch(createUrl, {
          method: "POST",
          headers: {
            Authorization: auth,
            "Content-Type": "application/json",
          },
          payload: JSON.stringify(createPayload),
          muteHttpExceptions: true,
        });
        code = res.getResponseCode();
        body = JSON.parse(res.getContentText() || "{}");

        if (code >= 200 && code < 300) {
          const newId = body.id;
          sheet.getRange(i + 2, headers.indexOf("id") + 1).setValue(newId);
          Logger.log(`🆕 Ligne ${i + 2} – jeu créé (#${newId})`);
        } else {
          Logger.log(
            `❌ Ligne ${i + 2} – création échouée (${code}) : ${res
              .getContentText()
              .slice(0, 120)}`
          );
        }
      }
    } catch (err) {
      Logger.log(`⚠️ Ligne ${i + 2} – Erreur : ${err}`);
    }
  });

  SpreadsheetApp.getActiveSpreadsheet().toast("✅ Synchronisation terminée !");
}

/****************************
 * AUTH
 ****************************/
function basicAuth() {
  return "Basic " + Utilities.base64Encode(WP_USER + ":" + WP_APP_PASSWORD);
}

/****************************
 * DIAGNOSTIC - EXPLORATION WORDPRESS
 ****************************/

/**
 * Récupère tous les post types disponibles dans WordPress
 */
function discoverPostTypes() {
  const auth = basicAuth();
  const url = `${WP_URL}/wp-json/wp/v2/types`;
  const res = UrlFetchApp.fetch(url, {
    headers: { Authorization: auth },
    muteHttpExceptions: true,
  });

  const types = JSON.parse(res.getContentText());
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  const sheet =
    ss.getSheetByName("WP_PostTypes") || ss.insertSheet("WP_PostTypes");
  sheet.clearContents();
  sheet.appendRow(["Slug", "Name", "REST Base", "Supports", "Taxonomies"]);

  Object.keys(types).forEach((slug) => {
    const type = types[slug];
    sheet.appendRow([
      slug,
      type.name,
      type.rest_base || slug,
      (type.supports || []).join(", "),
      (type.taxonomies || []).join(", "),
    ]);
  });

  SpreadsheetApp.getActiveSpreadsheet().toast("✅ Post types découverts !");
  Logger.log(`Trouvé ${Object.keys(types).length} post types`);
}

/**
 * Récupère toutes les taxonomies disponibles
 */
function discoverTaxonomies() {
  const auth = basicAuth();
  const url = `${WP_URL}/wp-json/wp/v2/taxonomies`;
  const res = UrlFetchApp.fetch(url, {
    headers: { Authorization: auth },
    muteHttpExceptions: true,
  });

  const taxonomies = JSON.parse(res.getContentText());
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  const sheet =
    ss.getSheetByName("WP_Taxonomies") || ss.insertSheet("WP_Taxonomies");
  sheet.clearContents();
  sheet.appendRow(["Slug", "Name", "REST Base", "Post Types", "Hierarchical"]);

  Object.keys(taxonomies).forEach((slug) => {
    const tax = taxonomies[slug];
    sheet.appendRow([
      slug,
      tax.name,
      tax.rest_base || slug,
      (tax.types || []).join(", "),
      tax.hierarchical ? "Oui" : "Non",
    ]);
  });

  SpreadsheetApp.getActiveSpreadsheet().toast("✅ Taxonomies découvertes !");
  Logger.log(`Trouvé ${Object.keys(taxonomies).length} taxonomies`);
}

/**
 * Debug: Affiche la structure exacte des field groups
 */
function debugAcfFieldGroupsStructure() {
  const auth = basicAuth();
  const url = `${WP_URL}/wp-json/acf/v3/field_groups`;
  const res = UrlFetchApp.fetch(url, {
    headers: { Authorization: auth },
    muteHttpExceptions: true,
  });

  const responseText = res.getContentText();
  const groupsData = JSON.parse(responseText);

  Logger.log("=== STRUCTURE DES FIELD GROUPS ===");
  Logger.log("Type: " + typeof groupsData);
  Logger.log("Is Array: " + Array.isArray(groupsData));
  Logger.log("Premier élément: " + JSON.stringify(groupsData).slice(0, 500));

  // Affiche dans une feuille aussi
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  const sheet = ss.getSheetByName("Debug_ACF") || ss.insertSheet("Debug_ACF");
  sheet.clearContents();
  sheet.appendRow(["=== DEBUG ACF FIELD GROUPS ==="]);
  sheet.appendRow(["Type", typeof groupsData]);
  sheet.appendRow(["Is Array", Array.isArray(groupsData)]);
  sheet.appendRow(["Keys", Object.keys(groupsData).join(", ")]);
  sheet.appendRow([""]);
  sheet.appendRow(["=== PREMIER GROUPE (exemple) ==="]);

  if (Array.isArray(groupsData) && groupsData.length > 0) {
    Object.keys(groupsData[0]).forEach((key) => {
      sheet.appendRow([key, groupsData[0][key]]);
    });
  } else if (typeof groupsData === "object") {
    const firstKey = Object.keys(groupsData)[0];
    sheet.appendRow(["Première clé", firstKey]);
    Object.keys(groupsData[firstKey] || {}).forEach((key) => {
      sheet.appendRow([key, groupsData[firstKey][key]]);
    });
  }
}

/**
 * Debug: Affiche la structure des champs ACF retournés par l'API
 */
function debugAcfFieldsStructure() {
  const auth = basicAuth();
  const fieldsUrl = `${WP_URL}/wp-json/acf/v3/fields`;
  const resFields = UrlFetchApp.fetch(fieldsUrl, {
    headers: { Authorization: auth },
    muteHttpExceptions: true,
  });

  const fieldsText = resFields.getContentText();
  const fieldsData = JSON.parse(fieldsText);

  const ss = SpreadsheetApp.getActiveSpreadsheet();
  const sheet =
    ss.getSheetByName("Debug_Fields") || ss.insertSheet("Debug_Fields");
  sheet.clearContents();

  sheet.appendRow(["=== DEBUG ACF FIELDS ==="]);
  sheet.appendRow(["Type", typeof fieldsData]);
  sheet.appendRow(["Is Array", Array.isArray(fieldsData)]);
  sheet.appendRow(["Keys", Object.keys(fieldsData).join(", ")]);
  sheet.appendRow([""]);

  if (Array.isArray(fieldsData) && fieldsData.length > 0) {
    sheet.appendRow(["=== PREMIER CHAMP (exemple) ==="]);
    Object.keys(fieldsData[0]).forEach((key) => {
      const value = fieldsData[0][key];
      sheet.appendRow([
        key,
        typeof value === "object" ? JSON.stringify(value).slice(0, 200) : value,
      ]);
    });
  } else if (typeof fieldsData === "object" && fieldsData !== null) {
    const firstKey = Object.keys(fieldsData)[0];
    sheet.appendRow(["Première clé", firstKey]);
    if (fieldsData[firstKey]) {
      Object.keys(fieldsData[firstKey]).forEach((key) => {
        const value = fieldsData[firstKey][key];
        sheet.appendRow([
          key,
          typeof value === "object"
            ? JSON.stringify(value).slice(0, 200)
            : value,
        ]);
      });
    }
  }

  Logger.log("Structure des champs sauvegardée dans Debug_Fields");
}

/**
 * Analyse détaillée des champs ACF avec focus sur les relations
 * Analyse directement les données ACF des posts pour détecter les relations
 */
function analyzeAcfRelations() {
  Logger.log(
    "🔍 [NOUVELLE VERSION] Démarrage de l'analyse des relations ACF..."
  );
  const auth = basicAuth();
  const relations = [];

  // Récupérer plusieurs posts pour analyser leurs données ACF
  const postsUrl = `${WP_URL}/wp-json/wp/v2/${POST_TYPE}?per_page=10`;
  const postsRes = UrlFetchApp.fetch(postsUrl, {
    headers: { Authorization: auth },
    muteHttpExceptions: true,
  });

  const posts = JSON.parse(postsRes.getContentText());
  if (posts.length === 0) {
    Logger.log("❌ Aucun post trouvé");
    SpreadsheetApp.getActiveSpreadsheet().toast("❌ Aucun post trouvé");
    return;
  }

  Logger.log(`✅ ${posts.length} posts récupérés, analyse en cours...`);

  // Set pour stocker les champs suspects de relation (pour éviter les doublons)
  const suspectedRelations = new Map();

  // Analyser chaque post
  posts.forEach((post, index) => {
    Logger.log(
      `📄 Analyse du post #${post.id} (${index + 1}/${posts.length})...`
    );
    const acfUrl = `${WP_URL}/wp-json/acf/v3/${POST_TYPE}/${post.id}`;
    const acfRes = UrlFetchApp.fetch(acfUrl, {
      headers: { Authorization: auth },
      muteHttpExceptions: true,
    });

    const acfData = JSON.parse(acfRes.getContentText() || "{}");
    const fieldNames = Object.keys(acfData);
    Logger.log(
      `  → ${fieldNames.length} champs ACF trouvés: ${fieldNames.join(", ")}`
    );

    // Analyser chaque champ ACF
    Object.keys(acfData).forEach((fieldName) => {
      const value = acfData[fieldName];
      Logger.log(
        `    Champ "${fieldName}": type=${typeof value}, isArray=${Array.isArray(
          value
        )}, value=${JSON.stringify(value).slice(0, 100)}`
      );

      // Si c'est un tableau de nombres, c'est probablement une relation multiple
      if (Array.isArray(value) && value.length > 0) {
        const firstItem = value[0];
        if (typeof firstItem === "number" && firstItem > 0) {
          // Vérifier si ce sont vraiment des IDs de posts valides
          const allNumbers = value.every((v) => typeof v === "number" && v > 0);
          if (allNumbers) {
            Logger.log(
              `      ✅ RELATION DÉTECTÉE: ${fieldName} = tableau de ${value.length} IDs`
            );
            if (!suspectedRelations.has(fieldName)) {
              suspectedRelations.set(fieldName, {
                name: fieldName,
                type: "relationship_multiple",
                example_value: value.slice(0, 3), // Garder quelques exemples
                detected_in_posts: 1,
              });
            } else {
              const existing = suspectedRelations.get(fieldName);
              existing.detected_in_posts++;
            }
          }
        }
      }
      // Si c'est un nombre seul, peut-être un post_object
      else if (typeof value === "number" && value > 0 && value < 1000000) {
        // Les IDs WordPress sont généralement < 1000000
        Logger.log(`      ✅ POST_OBJECT DÉTECTÉ: ${fieldName} = ID ${value}`);
        if (!suspectedRelations.has(fieldName)) {
          suspectedRelations.set(fieldName, {
            name: fieldName,
            type: "post_object",
            example_value: value,
            detected_in_posts: 1,
          });
        } else {
          const existing = suspectedRelations.get(fieldName);
          existing.detected_in_posts++;
        }
      }
    });
  });

  // Convertir en tableau et enrichir avec plus d'infos
  suspectedRelations.forEach((rel, fieldName) => {
    // Essayer de déterminer le post_type ciblé en vérifiant les IDs
    let postTypes = new Set();
    if (Array.isArray(rel.example_value)) {
      rel.example_value.forEach((id) => {
        // Essayer de récupérer le post pour connaître son type
        try {
          const testUrl = `${WP_URL}/wp-json/wp/v2/${POST_TYPE}/${id}`;
          const testRes = UrlFetchApp.fetch(testUrl, {
            headers: { Authorization: auth },
            muteHttpExceptions: true,
          });
          if (testRes.getResponseCode() === 200) {
            postTypes.add(POST_TYPE);
          } else {
            // Essayer d'autres post types courants
            const commonTypes = ["post", "page", "location", "quest", "game"];
            commonTypes.forEach((type) => {
              const typeUrl = `${WP_URL}/wp-json/wp/v2/${type}/${id}`;
              const typeRes = UrlFetchApp.fetch(typeUrl, {
                muteHttpExceptions: true,
              });
              if (typeRes.getResponseCode() === 200) {
                postTypes.add(type);
              }
            });
          }
        } catch (e) {
          // Ignorer les erreurs
        }
      });
    }

    relations.push({
      group: "Détecté automatiquement",
      name: rel.name,
      label: rel.name
        .replace(/_/g, " ")
        .replace(/\b\w/g, (l) => l.toUpperCase()),
      type: rel.type,
      post_type: Array.from(postTypes).join(", ") || "À déterminer",
      multiple: rel.type.includes("multiple"),
      required: false,
      taxonomy: "N/A",
      return_format: "id",
      key: rel.name,
      detected_in_posts: rel.detected_in_posts,
    });
  });

  Logger.log(`Total relations détectées: ${relations.length}`);

  // Création de la feuille
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  const sheet =
    ss.getSheetByName("ACF_Relations") || ss.insertSheet("ACF_Relations");
  sheet.clearContents();
  sheet.appendRow([
    "Group",
    "Name",
    "Label",
    "Type",
    "Post Type",
    "Multiple",
    "Required",
    "Taxonomy",
    "Return Format",
    "Key",
    "Detected In Posts",
  ]);

  relations.forEach((r) => {
    sheet.appendRow([
      r.group,
      r.name,
      r.label,
      r.type,
      Array.isArray(r.post_type) ? r.post_type.join(", ") : r.post_type,
      r.multiple ? "Oui" : "Non",
      r.required ? "Oui" : "Non",
      r.taxonomy,
      r.return_format,
      r.key,
      r.detected_in_posts || 0,
    ]);
  });

  SpreadsheetApp.getActiveSpreadsheet().toast(
    `✅ ${relations.length} champs de relation trouvés !`
  );
  Logger.log(`Trouvé ${relations.length} champs de relation`);
}

/**
 * Récupère un exemple de post avec toutes ses relations pour voir la structure
 */
function fetchSampleWithRelations() {
  const auth = basicAuth();
  const url = `${WP_URL}/wp-json/wp/v2/${POST_TYPE}?per_page=1&_embed`;
  const res = UrlFetchApp.fetch(url, {
    headers: { Authorization: auth },
    muteHttpExceptions: true,
  });

  const posts = JSON.parse(res.getContentText());
  if (posts.length === 0) {
    SpreadsheetApp.getActiveSpreadsheet().toast("❌ Aucun post trouvé");
    return;
  }

  const post = posts[0];

  // Analyse des champs ACF
  const acfUrl = `${WP_URL}/wp-json/acf/v3/${POST_TYPE}/${post.id}`;
  const acfRes = UrlFetchApp.fetch(acfUrl, {
    headers: { Authorization: auth },
    muteHttpExceptions: true,
  });
  const acfData = JSON.parse(acfRes.getContentText() || "{}");

  // Création de la feuille avec l'exemple
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  const sheet =
    ss.getSheetByName("Sample_Relations") || ss.insertSheet("Sample_Relations");
  sheet.clearContents();

  sheet.appendRow(["=== EXEMPLE DE POST ==="]);
  sheet.appendRow(["ID", post.id]);
  sheet.appendRow(["Titre", post.title?.rendered || ""]);
  sheet.appendRow(["Slug", post.slug || ""]);
  sheet.appendRow([""]);

  sheet.appendRow(["=== CHAMPS ACF ==="]);
  Object.keys(acfData).forEach((key) => {
    const value = acfData[key];
    if (Array.isArray(value)) {
      sheet.appendRow([
        key,
        value.join(", "),
        `(Array de ${value.length} éléments)`,
      ]);
      // Si ce sont des IDs, affiche les détails
      if (value.length > 0 && typeof value[0] === "number") {
        value.forEach((id, idx) => {
          sheet.appendRow([`  → ${key}[${idx}]`, id, `ID WordPress`]);
        });
      }
    } else if (typeof value === "object" && value !== null) {
      sheet.appendRow([key, JSON.stringify(value), `(Objet)`]);
    } else {
      sheet.appendRow([key, value]);
    }
  });

  sheet.appendRow([""]);
  sheet.appendRow(["=== TAXONOMIES ==="]);
  if (post._embedded && post._embedded["wp:term"]) {
    post._embedded["wp:term"].forEach((terms) => {
      if (terms.length > 0) {
        const taxonomy = terms[0].taxonomy;
        const termNames = terms
          .map((t) => `${t.name} (ID: ${t.id})`)
          .join(", ");
        sheet.appendRow([taxonomy, termNames]);
      }
    });
  }

  SpreadsheetApp.getActiveSpreadsheet().toast("✅ Exemple chargé !");
  Logger.log("Exemple de post chargé avec relations");
}

/**
 * Analyse complète de la structure WordPress pour créer le système de sync
 * Crée un rapport détaillé avec tous les post types, leurs champs ACF et les relations
 */
function analyzeCompleteStructure() {
  Logger.log("🔍 Démarrage de l'analyse complète de la structure WordPress...");
  const auth = basicAuth();
  const ss = SpreadsheetApp.getActiveSpreadsheet();

  // Créer la feuille de structure complète
  const structureSheet =
    ss.getSheetByName("Structure_Complete") ||
    ss.insertSheet("Structure_Complete");
  structureSheet.clearContents();

  // En-têtes
  structureSheet.appendRow([
    "Post Type",
    "Champ",
    "Label",
    "Type",
    "Valeur Exemple",
    "Est Relation",
    "Type Relation",
    "Post Type Cible",
    "Multiple",
    "Notes",
  ]);

  // Récupérer tous les post types
  const typesUrl = `${WP_URL}/wp-json/wp/v2/types`;
  const typesRes = UrlFetchApp.fetch(typesUrl, {
    headers: { Authorization: auth },
    muteHttpExceptions: true,
  });
  const types = JSON.parse(typesRes.getContentText());

  const postTypesToAnalyze = Object.keys(types).filter(
    (slug) => !["attachment", "revision", "nav_menu_item"].includes(slug)
  );

  Logger.log(
    `Analyse de ${
      postTypesToAnalyze.length
    } post types: ${postTypesToAnalyze.join(", ")}`
  );

  const allRelations = [];

  // Analyser chaque post type
  postTypesToAnalyze.forEach((postTypeSlug, idx) => {
    Logger.log(
      `\n📋 [${idx + 1}/${
        postTypesToAnalyze.length
      }] Analyse du post type: ${postTypeSlug}`
    );

    try {
      // Récupérer quelques posts de ce type
      const postsUrl = `${WP_URL}/wp-json/wp/v2/${postTypeSlug}?per_page=3`;
      const postsRes = UrlFetchApp.fetch(postsUrl, {
        headers: { Authorization: auth },
        muteHttpExceptions: true,
      });

      const responseText = postsRes.getContentText();
      let posts = [];

      try {
        const parsed = JSON.parse(responseText);
        // Vérifier si c'est un tableau
        if (Array.isArray(parsed)) {
          posts = parsed;
        } else if (parsed && typeof parsed === "object") {
          // Peut-être que c'est un objet avec une propriété qui contient les posts
          Logger.log(
            `  ⚠️ Réponse non-tableau pour ${postTypeSlug}, structure: ${Object.keys(
              parsed
            ).join(", ")}`
          );
        }
      } catch (e) {
        Logger.log(`  ⚠️ Erreur parsing JSON pour ${postTypeSlug}: ${e}`);
      }

      if (!Array.isArray(posts) || posts.length === 0) {
        Logger.log(`  ⚠️ Aucun post trouvé pour ${postTypeSlug}`);
        structureSheet.appendRow([
          postTypeSlug,
          "Aucun post",
          "",
          "",
          "",
          "",
          "",
          "",
          "",
          "Aucun post de ce type",
        ]);
        return;
      }

      // Analyser le premier post pour obtenir la structure ACF
      const firstPost = posts[0];
      if (!firstPost || !firstPost.id) {
        Logger.log(`  ⚠️ Post invalide pour ${postTypeSlug}`);
        structureSheet.appendRow([
          postTypeSlug,
          "Post invalide",
          "",
          "",
          "",
          "",
          "",
          "",
          "",
          "Structure de post invalide",
        ]);
        return;
      }
      const acfUrl = `${WP_URL}/wp-json/acf/v3/${postTypeSlug}/${firstPost.id}`;
      const acfRes = UrlFetchApp.fetch(acfUrl, {
        headers: { Authorization: auth },
        muteHttpExceptions: true,
      });

      const acfData = JSON.parse(acfRes.getContentText() || "{}");
      const fieldNames = Object.keys(acfData);

      Logger.log(`  ✅ ${fieldNames.length} champs ACF trouvés`);

      if (fieldNames.length === 0) {
        structureSheet.appendRow([
          postTypeSlug,
          "Aucun champ ACF",
          "",
          "",
          "",
          "",
          "",
          "",
          "",
          "Pas de champs ACF",
        ]);
        return;
      }

      // Analyser chaque champ
      fieldNames.forEach((fieldName) => {
        const value = acfData[fieldName];
        Logger.log(
          `    📝 Champ "${fieldName}": type=${typeof value}, isArray=${Array.isArray(
            value
          )}, value=${JSON.stringify(value).slice(0, 150)}`
        );
        let fieldType = typeof value;
        let isArray = Array.isArray(value);
        let isRelation = false;
        let relationType = "";
        let postTypeTarget = "";
        let isMultiple = false;
        let exampleValue = "";
        let notes = "";

        // Détecter le type de champ
        if (isArray && value.length > 0) {
          fieldType = `Array[${value.length}]`;
          const firstItem = value[0];

          // Vérifier si c'est un tableau de nombres (IDs)
          if (typeof firstItem === "number" && firstItem > 0) {
            const allNumbers = value.every(
              (v) => typeof v === "number" && v > 0
            );
            if (allNumbers) {
              isRelation = true;
              relationType = "relationship";
              isMultiple = true;
              exampleValue = `[${value.slice(0, 3).join(", ")}${
                value.length > 3 ? "..." : ""
              }]`;

              // Essayer de déterminer le post type ciblé
              const testId = value[0];
              postTypeTarget = detectPostTypeForId(testId, auth);
              notes = `Tableau de ${value.length} IDs`;
            } else {
              exampleValue = `[${JSON.stringify(firstItem).slice(0, 50)}...]`;
            }
          }
          // Vérifier si c'est un tableau de strings qui sont des IDs
          else if (typeof firstItem === "string" && /^\d+$/.test(firstItem)) {
            const allNumericStrings = value.every(
              (v) => typeof v === "string" && /^\d+$/.test(v)
            );
            if (allNumericStrings) {
              isRelation = true;
              relationType = "relationship";
              isMultiple = true;
              const numericIds = value.map((v) => parseInt(v, 10));
              exampleValue = `[${numericIds.slice(0, 3).join(", ")}${
                value.length > 3 ? "..." : ""
              }]`;
              const testId = parseInt(numericIds[0], 10);
              postTypeTarget = detectPostTypeForId(testId, auth);
              notes = `Tableau de ${value.length} IDs (strings)`;
            } else {
              exampleValue = `[${JSON.stringify(firstItem).slice(0, 50)}...]`;
            }
          }
          // Vérifier si c'est un tableau d'objets avec des IDs
          else if (
            typeof firstItem === "object" &&
            firstItem !== null &&
            firstItem.id
          ) {
            const allHaveIds = value.every(
              (v) => typeof v === "object" && v !== null && v.id
            );
            if (allHaveIds) {
              isRelation = true;
              relationType = "relationship";
              isMultiple = true;
              const ids = value.map((v) => v.id);
              exampleValue = `[${ids.slice(0, 3).join(", ")}${
                value.length > 3 ? "..." : ""
              }]`;
              const testId =
                typeof ids[0] === "string" ? parseInt(ids[0], 10) : ids[0];
              postTypeTarget = detectPostTypeForId(testId, auth);
              notes = `Tableau de ${value.length} objets avec IDs`;
            } else {
              exampleValue = `[${JSON.stringify(firstItem).slice(0, 50)}...]`;
            }
          } else {
            exampleValue = `[${JSON.stringify(firstItem).slice(0, 50)}...]`;
          }
        } else if (typeof value === "number" && value > 0 && value < 1000000) {
          isRelation = true;
          relationType = "post_object";
          exampleValue = value.toString();
          postTypeTarget = detectPostTypeForId(value, auth);
          notes = "ID unique";
        } else if (
          typeof value === "string" &&
          /^\d+$/.test(value) &&
          parseInt(value, 10) > 0
        ) {
          // ID stocké comme string
          const numericId = parseInt(value, 10);
          isRelation = true;
          relationType = "post_object";
          exampleValue = value;
          postTypeTarget = detectPostTypeForId(numericId, auth);
          notes = "ID unique (string)";
        } else if (typeof value === "object" && value !== null) {
          // Vérifier si c'est un objet avec un ID (post_object retour format object)
          if (value.id) {
            isRelation = true;
            relationType = "post_object";
            const id =
              typeof value.id === "string" ? parseInt(value.id, 10) : value.id;
            exampleValue = `{id: ${id}, ...}`;
            postTypeTarget = detectPostTypeForId(id, auth);
            notes = "Objet avec ID";
          } else {
            exampleValue = JSON.stringify(value).slice(0, 100);
            notes = "Objet complexe";
          }
        } else {
          exampleValue = String(value).slice(0, 100);
        }

        // Ajouter la ligne dans la feuille
        structureSheet.appendRow([
          postTypeSlug,
          fieldName,
          fieldName.replace(/_/g, " ").replace(/\b\w/g, (l) => l.toUpperCase()),
          fieldType,
          exampleValue,
          isRelation ? "Oui" : "Non",
          relationType,
          postTypeTarget,
          isMultiple ? "Oui" : "Non",
          notes,
        ]);

        // Si c'est une relation, l'ajouter à la liste
        if (isRelation) {
          allRelations.push({
            post_type: postTypeSlug,
            field: fieldName,
            type: relationType,
            target_post_type: postTypeTarget,
            multiple: isMultiple,
          });
        }
      });
    } catch (e) {
      Logger.log(`  ❌ Erreur lors de l'analyse de ${postTypeSlug}: ${e}`);
      structureSheet.appendRow([
        postTypeSlug,
        "ERREUR",
        "",
        "",
        "",
        "",
        "",
        "",
        "",
        String(e).slice(0, 100),
      ]);
    }
  });

  // Créer une feuille récapitulative des relations
  const relationsSheet =
    ss.getSheetByName("Relations_Recap") || ss.insertSheet("Relations_Recap");
  relationsSheet.clearContents();
  relationsSheet.appendRow([
    "Post Type Source",
    "Champ",
    "Type Relation",
    "Post Type Cible",
    "Multiple",
  ]);

  allRelations.forEach((rel) => {
    relationsSheet.appendRow([
      rel.post_type,
      rel.field,
      rel.type,
      rel.target_post_type,
      rel.multiple ? "Oui" : "Non",
    ]);
  });

  // Mise en forme
  structureSheet
    .getRange(1, 1, 1, 10)
    .setFontWeight("bold")
    .setBackground("#4285f4")
    .setFontColor("#ffffff");
  relationsSheet
    .getRange(1, 1, 1, 5)
    .setFontWeight("bold")
    .setBackground("#34a853")
    .setFontColor("#ffffff");

  SpreadsheetApp.getActiveSpreadsheet().toast(
    `✅ Analyse terminée ! ${allRelations.length} relations détectées.`
  );
  Logger.log(
    `\n✅ Analyse complète terminée. ${allRelations.length} relations détectées.`
  );
}

/**
 * Analyse de la structure WordPress pour les post types spécifiques
 * Analyse: country (pays), region, departement, ville, game (jeu)
 */
function analyzeTargetStructure() {
  Logger.log(
    "🔍 Analyse de la structure pour: country, region, departement, ville, game..."
  );
  const auth = basicAuth();
  const ss = SpreadsheetApp.getActiveSpreadsheet();

  // Post types ciblés
  const TARGET_POST_TYPES = [
    "country",
    "region",
    "departement",
    "ville",
    "game",
  ];

  // Créer la feuille de structure
  const structureSheet =
    ss.getSheetByName("Structure_Target") || ss.insertSheet("Structure_Target");
  structureSheet.clearContents();

  // En-têtes
  structureSheet.appendRow([
    "Post Type",
    "Champ",
    "Label",
    "Type",
    "Valeur Exemple",
    "Est Relation",
    "Type Relation",
    "Post Type Cible",
    "Multiple",
    "Notes",
  ]);

  const allRelations = [];

  // Analyser chaque post type ciblé
  TARGET_POST_TYPES.forEach((postTypeSlug, idx) => {
    Logger.log(
      `\n📋 [${idx + 1}/${
        TARGET_POST_TYPES.length
      }] Analyse du post type: ${postTypeSlug}`
    );

    try {
      // Récupérer quelques posts de ce type
      const postsUrl = `${WP_URL}/wp-json/wp/v2/${postTypeSlug}?per_page=5`;
      const postsRes = UrlFetchApp.fetch(postsUrl, {
        headers: { Authorization: auth },
        muteHttpExceptions: true,
      });

      const responseText = postsRes.getContentText();
      const responseCode = postsRes.getResponseCode();
      let posts = [];

      // Vérifier si c'est du HTML avant de parser
      if (
        responseText.trim().startsWith("<!DOCTYPE") ||
        responseText.trim().startsWith("<html")
      ) {
        Logger.log(
          `  ⚠️ Réponse HTML pour ${postTypeSlug} (Code HTTP: ${responseCode})`
        );
        structureSheet.appendRow([
          postTypeSlug,
          "Erreur API",
          "",
          "",
          "",
          "",
          "",
          "",
          "",
          `Code HTTP ${responseCode} - Réponse HTML (post type peut-être non accessible via REST API ou nécessite authentification)`,
        ]);
        return;
      }

      // Vérifier le code de réponse
      if (responseCode !== 200) {
        Logger.log(`  ⚠️ Code HTTP ${responseCode} pour ${postTypeSlug}`);
        structureSheet.appendRow([
          postTypeSlug,
          "Erreur API",
          "",
          "",
          "",
          "",
          "",
          "",
          "",
          `Code HTTP ${responseCode} - ${responseText.slice(0, 100)}`,
        ]);
        return;
      }

      try {
        const parsed = JSON.parse(responseText);
        if (Array.isArray(parsed)) {
          posts = parsed;
        } else if (parsed && typeof parsed === "object") {
          // Peut-être une erreur JSON
          if (parsed.code || parsed.message) {
            Logger.log(
              `  ⚠️ Erreur API pour ${postTypeSlug}: ${
                parsed.message || parsed.code
              }`
            );
            structureSheet.appendRow([
              postTypeSlug,
              "Erreur API",
              "",
              "",
              "",
              "",
              "",
              "",
              "",
              parsed.message || parsed.code || "Erreur inconnue",
            ]);
            return;
          }
        }
      } catch (e) {
        Logger.log(`  ⚠️ Erreur parsing JSON pour ${postTypeSlug}: ${e}`);
        structureSheet.appendRow([
          postTypeSlug,
          "Erreur parsing",
          "",
          "",
          "",
          "",
          "",
          "",
          "",
          `Erreur parsing JSON: ${String(e).slice(0, 100)}`,
        ]);
        return;
      }

      if (!Array.isArray(posts) || posts.length === 0) {
        Logger.log(`  ⚠️ Aucun post trouvé pour ${postTypeSlug}`);
        structureSheet.appendRow([
          postTypeSlug,
          "Aucun post",
          "",
          "",
          "",
          "",
          "",
          "",
          "",
          "Aucun post de ce type",
        ]);
        return;
      }

      // Analyser le premier post pour obtenir la structure ACF
      const firstPost = posts[0];
      if (!firstPost || !firstPost.id) {
        Logger.log(`  ⚠️ Post invalide pour ${postTypeSlug}`);
        return;
      }

      const acfUrl = `${WP_URL}/wp-json/acf/v3/${postTypeSlug}/${firstPost.id}`;
      const acfRes = UrlFetchApp.fetch(acfUrl, {
        headers: { Authorization: auth },
        muteHttpExceptions: true,
      });

      const acfResponseText = acfRes.getContentText() || "{}";
      let acfData = {};

      try {
        acfData = JSON.parse(acfResponseText);
      } catch (e) {
        Logger.log(`  ⚠️ Erreur parsing ACF pour ${postTypeSlug}: ${e}`);
        structureSheet.appendRow([
          postTypeSlug,
          "Erreur parsing ACF",
          "",
          "",
          "",
          "",
          "",
          "",
          "",
          String(e).slice(0, 100),
        ]);
        return;
      }

      // Si les champs sont imbriqués dans un objet "acf", les extraire
      if (acfData.acf && typeof acfData.acf === "object") {
        acfData = acfData.acf;
      }

      const fieldNames = Object.keys(acfData);

      Logger.log(`  ✅ ${fieldNames.length} champs ACF trouvés`);

      if (fieldNames.length === 0) {
        structureSheet.appendRow([
          postTypeSlug,
          "Aucun champ ACF",
          "",
          "",
          "",
          "",
          "",
          "",
          "",
          "Pas de champs ACF",
        ]);
        return;
      }

      // Fonction récursive pour analyser les champs (gère les structures imbriquées)
      function analyzeField(fieldName, value, prefix = "") {
        const fullFieldName = prefix ? `${prefix}.${fieldName}` : fieldName;
        Logger.log(
          `    📝 Champ "${fullFieldName}": type=${typeof value}, isArray=${Array.isArray(
            value
          )}, value=${JSON.stringify(value).slice(0, 150)}`
        );

        let fieldType = typeof value;
        let isArray = Array.isArray(value);
        let isRelation = false;
        let relationType = "";
        let postTypeTarget = "";
        let isMultiple = false;
        let exampleValue = "";
        let notes = "";

        // Détecter le type de champ (même logique que analyzeCompleteStructure)
        if (isArray && value.length > 0) {
          fieldType = `Array[${value.length}]`;
          const firstItem = value[0];

          if (typeof firstItem === "number" && firstItem > 0) {
            const allNumbers = value.every(
              (v) => typeof v === "number" && v > 0
            );
            if (allNumbers) {
              isRelation = true;
              relationType = "relationship";
              isMultiple = true;
              exampleValue = `[${value.slice(0, 3).join(", ")}${
                value.length > 3 ? "..." : ""
              }]`;
              const testId = value[0];
              postTypeTarget = detectPostTypeForId(testId, auth);
              notes = `Tableau de ${value.length} IDs`;
            } else {
              exampleValue = `[${JSON.stringify(firstItem).slice(0, 50)}...]`;
            }
          } else if (typeof firstItem === "string" && /^\d+$/.test(firstItem)) {
            const allNumericStrings = value.every(
              (v) => typeof v === "string" && /^\d+$/.test(v)
            );
            if (allNumericStrings) {
              isRelation = true;
              relationType = "relationship";
              isMultiple = true;
              const numericIds = value.map((v) => parseInt(v, 10));
              exampleValue = `[${numericIds.slice(0, 3).join(", ")}${
                value.length > 3 ? "..." : ""
              }]`;
              const testId = parseInt(numericIds[0], 10);
              postTypeTarget = detectPostTypeForId(testId, auth);
              notes = `Tableau de ${value.length} IDs (strings)`;
            } else {
              exampleValue = `[${JSON.stringify(firstItem).slice(0, 50)}...]`;
            }
          } else if (
            typeof firstItem === "object" &&
            firstItem !== null &&
            (firstItem.id || firstItem.ID)
          ) {
            // Gérer à la fois "id" et "ID" (majuscules)
            const idKey = firstItem.ID ? "ID" : "id";
            const allHaveIds = value.every(
              (v) => typeof v === "object" && v !== null && (v.id || v.ID)
            );
            if (allHaveIds) {
              isRelation = true;
              relationType = "relationship";
              isMultiple = true;
              const ids = value.map((v) => v[idKey]);
              exampleValue = `[${ids.slice(0, 3).join(", ")}${
                value.length > 3 ? "..." : ""
              }]`;
              const testId =
                typeof ids[0] === "string" ? parseInt(ids[0], 10) : ids[0];
              postTypeTarget = detectPostTypeForId(testId, auth);
              notes = `Tableau de ${value.length} objets avec ${idKey}`;
            } else {
              exampleValue = `[${JSON.stringify(firstItem).slice(0, 50)}...]`;
            }
          } else {
            exampleValue = `[${JSON.stringify(firstItem).slice(0, 50)}...]`;
          }
        } else if (typeof value === "number" && value > 0 && value < 1000000) {
          isRelation = true;
          relationType = "post_object";
          exampleValue = value.toString();
          postTypeTarget = detectPostTypeForId(value, auth);
          notes = "ID unique";
        } else if (
          typeof value === "string" &&
          /^\d+$/.test(value) &&
          parseInt(value, 10) > 0
        ) {
          const numericId = parseInt(value, 10);
          isRelation = true;
          relationType = "post_object";
          exampleValue = value;
          postTypeTarget = detectPostTypeForId(numericId, auth);
          notes = "ID unique (string)";
        } else if (typeof value === "object" && value !== null) {
          // Gérer à la fois "id" et "ID" (majuscules)
          if (value.id || value.ID) {
            isRelation = true;
            relationType = "post_object";
            const idKey = value.ID ? "ID" : "id";
            const id =
              typeof value[idKey] === "string"
                ? parseInt(value[idKey], 10)
                : value[idKey];
            exampleValue = `{${idKey}: ${id}, ...}`;
            postTypeTarget = detectPostTypeForId(id, auth);
            notes = `Objet avec ${idKey}`;
          } else {
            exampleValue = JSON.stringify(value).slice(0, 100);
            notes = "Objet complexe";
          }
        } else {
          exampleValue = String(value).slice(0, 100);
        }

        // Retourner les résultats
        return {
          fieldName: fullFieldName,
          fieldType,
          exampleValue,
          isRelation,
          relationType,
          postTypeTarget,
          isMultiple,
          notes,
        };
      }

      // Analyser chaque champ
      fieldNames.forEach((fieldName) => {
        const result = analyzeField(fieldName, acfData[fieldName]);

        // Ajouter la ligne dans la feuille
        structureSheet.appendRow([
          postTypeSlug,
          result.fieldName,
          result.fieldName
            .replace(/_/g, " ")
            .replace(/\b\w/g, (l) => l.toUpperCase()),
          result.fieldType,
          result.exampleValue,
          result.isRelation ? "Oui" : "Non",
          result.relationType,
          result.postTypeTarget,
          result.isMultiple ? "Oui" : "Non",
          result.notes,
        ]);

        // Si c'est une relation, l'ajouter à la liste
        if (result.isRelation) {
          allRelations.push({
            post_type: postTypeSlug,
            field: result.fieldName,
            type: result.relationType,
            target_post_type: result.postTypeTarget,
            multiple: result.isMultiple,
          });
        }
      });
    } catch (e) {
      Logger.log(`  ❌ Erreur lors de l'analyse de ${postTypeSlug}: ${e}`);
      structureSheet.appendRow([
        postTypeSlug,
        "ERREUR",
        "",
        "",
        "",
        "",
        "",
        "",
        "",
        String(e).slice(0, 100),
      ]);
    }
  });

  // Créer une feuille récapitulative des relations
  const relationsSheet =
    ss.getSheetByName("Relations_Target") || ss.insertSheet("Relations_Target");
  relationsSheet.clearContents();
  relationsSheet.appendRow([
    "Post Type Source",
    "Champ",
    "Type Relation",
    "Post Type Cible",
    "Multiple",
  ]);

  allRelations.forEach((rel) => {
    relationsSheet.appendRow([
      rel.post_type,
      rel.field,
      rel.type,
      rel.target_post_type,
      rel.multiple ? "Oui" : "Non",
    ]);
  });

  // Mise en forme
  structureSheet
    .getRange(1, 1, 1, 10)
    .setFontWeight("bold")
    .setBackground("#4285f4")
    .setFontColor("#ffffff");
  relationsSheet
    .getRange(1, 1, 1, 5)
    .setFontWeight("bold")
    .setBackground("#34a853")
    .setFontColor("#ffffff");

  SpreadsheetApp.getActiveSpreadsheet().toast(
    `✅ Analyse terminée ! ${allRelations.length} relations détectées pour les post types ciblés.`
  );
  Logger.log(
    `\n✅ Analyse terminée. ${allRelations.length} relations détectées.`
  );
}

/**
 * Fonction helper pour détecter le post type d'un ID
 */
function detectPostTypeForId(id, auth) {
  const commonTypes = [
    "country",
    "region",
    "departement",
    "ville",
    "game",
    POST_TYPE,
    "post",
    "page",
  ];

  for (let i = 0; i < commonTypes.length; i++) {
    const type = commonTypes[i];
    try {
      const testUrl = `${WP_URL}/wp-json/wp/v2/${type}/${id}`;
      const testRes = UrlFetchApp.fetch(testUrl, {
        headers: { Authorization: auth },
        muteHttpExceptions: true,
      });
      if (testRes.getResponseCode() === 200) {
        return type;
      }
    } catch (e) {
      // Continuer
    }
  }
  return "Inconnu";
}

/****************************
 * SYNCHRONISATION AVEC RELATIONS
 ****************************/

/**
 * Configuration des relations détectées
 */
const RELATIONS_CONFIG = {
  region: {
    countries: { target: "country", multiple: true, field: "countries" },
  },
  departement: {
    region: { target: "region", multiple: true, field: "region" },
  },
  ville: {
    ville: { target: "departement", multiple: true, field: "ville" },
  },
  game: {
    city: { target: "ville", multiple: false, field: "city" },
  },
};

/**
 * Récupère un post par son ID et retourne son titre
 */
function getPostTitleById(postType, postId, auth) {
  try {
    const url = `${WP_URL}/wp-json/wp/v2/${postType}/${postId}`;
    const res = UrlFetchApp.fetch(url, {
      headers: { Authorization: auth },
      muteHttpExceptions: true,
    });

    if (res.getResponseCode() === 200) {
      const post = JSON.parse(res.getContentText());
      return post.title?.rendered || post.title || `ID ${postId}`;
    }
  } catch (e) {
    Logger.log(`Erreur récupération titre pour ${postType}/${postId}: ${e}`);
  }
  return `ID ${postId}`;
}

/**
 * Récupère un post par son titre et retourne son ID
 */
function getPostIdByTitle(postType, title, auth) {
  try {
    const url = `${WP_URL}/wp-json/wp/v2/${postType}?search=${encodeURIComponent(
      title
    )}&per_page=1`;
    const res = UrlFetchApp.fetch(url, {
      headers: { Authorization: auth },
      muteHttpExceptions: true,
    });

    if (res.getResponseCode() === 200) {
      const posts = JSON.parse(res.getContentText());
      if (Array.isArray(posts) && posts.length > 0) {
        // Chercher une correspondance exacte
        const exactMatch = posts.find(
          (p) => (p.title?.rendered || p.title) === title
        );
        if (exactMatch) return exactMatch.id;
        // Sinon retourner le premier
        return posts[0].id;
      }
    }
  } catch (e) {
    Logger.log(`Erreur recherche ID pour ${postType}/${title}: ${e}`);
  }
  return null;
}

/**
 * Convertit les IDs de relation en noms pour l'affichage dans Sheets
 */
function convertRelationIdsToNames(postType, fieldName, value, auth) {
  if (!value) return "";

  const relationConfig = RELATIONS_CONFIG[postType]?.[fieldName];
  if (!relationConfig) return value;

  // Si c'est un tableau
  if (Array.isArray(value)) {
    // Si c'est un tableau d'objets avec ID
    if (
      value.length > 0 &&
      typeof value[0] === "object" &&
      (value[0].ID || value[0].id)
    ) {
      return value
        .map((item) => {
          const id = item.ID || item.id;
          return getPostTitleById(relationConfig.target, id, auth);
        })
        .join(", ");
    }
    // Si c'est un tableau d'IDs
    if (value.length > 0 && typeof value[0] === "number") {
      return value
        .map((id) => getPostTitleById(relationConfig.target, id, auth))
        .join(", ");
    }
    // Si c'est un tableau de strings numériques
    if (
      value.length > 0 &&
      typeof value[0] === "string" &&
      /^\d+$/.test(value[0])
    ) {
      return value
        .map((idStr) => {
          const id = parseInt(idStr, 10);
          return getPostTitleById(relationConfig.target, id, auth);
        })
        .join(", ");
    }
  }

  // Si c'est un ID unique (nombre)
  if (typeof value === "number" && value > 0) {
    return getPostTitleById(relationConfig.target, value, auth);
  }

  // Si c'est une string numérique (ID unique)
  if (
    typeof value === "string" &&
    /^\d+$/.test(value) &&
    parseInt(value, 10) > 0
  ) {
    const id = parseInt(value, 10);
    return getPostTitleById(relationConfig.target, id, auth);
  }

  // Si c'est un objet avec ID
  if (typeof value === "object" && value !== null && (value.ID || value.id)) {
    const id = value.ID || value.id;
    return getPostTitleById(relationConfig.target, id, auth);
  }

  // Sinon, retourner la valeur telle quelle (peut être déjà un nom)
  return value;
}

/**
 * Convertit les noms de relation en IDs pour le push vers WordPress
 */
function convertRelationNamesToIds(postType, fieldName, value, auth) {
  if (!value) {
    const relationConfig = RELATIONS_CONFIG[postType]?.[fieldName];
    return relationConfig?.multiple ? [] : null;
  }

  const relationConfig = RELATIONS_CONFIG[postType]?.[fieldName];
  if (!relationConfig) return value;

  if (typeof value === "string") {
    // Séparer par virgule si multiple, sinon prendre la première valeur
    const names = value
      .split(",")
      .map((n) => n.trim())
      .filter((n) => n);

    const ids = names
      .map((name) => {
        const id = getPostIdByTitle(relationConfig.target, name, auth);
        return id;
      })
      .filter((id) => id !== null);

    // Si c'est une relation unique (multiple: false), retourner le premier ID ou null
    if (!relationConfig.multiple) {
      return ids.length > 0 ? ids[0] : null;
    }

    // Sinon, retourner le tableau d'IDs
    return ids;
  }

  return value;
}

/**
 * Synchronise un post type depuis WordPress vers Google Sheets avec gestion des relations
 */
function syncPostTypeToSheet(postTypeSlug) {
  const auth = basicAuth();
  const ss = SpreadsheetApp.getActiveSpreadsheet();

  // Créer ou récupérer la feuille
  const sheetName =
    postTypeSlug.charAt(0).toUpperCase() + postTypeSlug.slice(1);
  let sheet = ss.getSheetByName(sheetName);
  if (!sheet) {
    sheet = ss.insertSheet(sheetName);
  }

  // Récupérer les posts
  const postsUrl = `${WP_URL}/wp-json/wp/v2/${postTypeSlug}?per_page=100`;
  const postsRes = UrlFetchApp.fetch(postsUrl, {
    headers: { Authorization: auth },
    muteHttpExceptions: true,
  });

  const posts = JSON.parse(postsRes.getContentText() || "[]");
  if (posts.length === 0) {
    SpreadsheetApp.getActiveSpreadsheet().toast(`Aucun ${postTypeSlug} trouvé`);
    return;
  }

  // Récupérer la structure ACF du premier post
  const firstPost = posts[0];
  const acfUrl = `${WP_URL}/wp-json/acf/v3/${postTypeSlug}/${firstPost.id}`;
  const acfRes = UrlFetchApp.fetch(acfUrl, {
    headers: { Authorization: auth },
    muteHttpExceptions: true,
  });

  let acfData = {};
  try {
    const parsed = JSON.parse(acfRes.getContentText() || "{}");
    acfData = parsed.acf || parsed;
  } catch (e) {
    Logger.log(`Erreur parsing ACF pour ${postTypeSlug}: ${e}`);
  }

  const fieldNames = Object.keys(acfData);

  // Ajouter les champs de relation configurés s'ils n'existent pas encore
  const relationConfig = RELATIONS_CONFIG[postTypeSlug];
  if (relationConfig) {
    Object.keys(relationConfig).forEach((fieldName) => {
      if (!fieldNames.includes(fieldName)) {
        fieldNames.push(fieldName);
        Logger.log(`  Ajout du champ de relation manquant: ${fieldName}`);
      }
    });
  }

  // Créer les en-têtes
  const headers = ["id", "post_title", "status"];
  fieldNames.forEach((field) => {
    headers.push(field);
  });
  headers.push("link"); // Ajouter la colonne lien à la fin

  sheet.clearContents();
  sheet.appendRow(headers);

  // Remplir les données
  posts.forEach((post) => {
    const row = [
      post.id,
      post.title?.rendered || post.title || "",
      post.status || "publish",
    ];

    // Récupérer les données ACF de ce post
    const postAcfUrl = `${WP_URL}/wp-json/acf/v3/${postTypeSlug}/${post.id}`;
    const postAcfRes = UrlFetchApp.fetch(postAcfUrl, {
      headers: { Authorization: auth },
      muteHttpExceptions: true,
    });

    let postAcfData = {};
    try {
      const parsed = JSON.parse(postAcfRes.getContentText() || "{}");
      postAcfData = parsed.acf || parsed;
    } catch (e) {
      // Ignorer
    }

    // Ajouter les valeurs des champs ACF
    fieldNames.forEach((field) => {
      const value = postAcfData[field];

      // Si c'est une relation, convertir en noms
      if (RELATIONS_CONFIG[postTypeSlug]?.[field]) {
        const converted = convertRelationIdsToNames(
          postTypeSlug,
          field,
          value,
          auth
        );
        row.push(converted);
      } else {
        // Sinon, convertir en string
        if (Array.isArray(value)) {
          row.push(value.join(", "));
        } else if (typeof value === "object" && value !== null) {
          row.push(JSON.stringify(value));
        } else {
          row.push(value || "");
        }
      }
    });

    // Ajouter le lien vers la page WordPress
    // Utiliser post.link si disponible (fourni par l'API REST WordPress)
    // Sinon construire l'URL manuellement
    let postLink = "";
    if (post.link) {
      postLink = post.link;
    } else if (post.slug) {
      // Construire l'URL avec le slug
      postLink = `${WP_URL}/${postTypeSlug}/${post.slug}/`;
    } else {
      // Fallback : utiliser l'ID
      postLink = `${WP_URL}/?p=${post.id}`;
    }
    row.push(postLink);

    sheet.appendRow(row);
  });

  // Formater la colonne "link" avec des liens hypertexte cliquables
  const linkColIndex = headers.indexOf("link");
  if (linkColIndex >= 0 && posts.length > 0) {
    const linkRange = sheet.getRange(2, linkColIndex + 1, posts.length, 1);
    const linkValues = linkRange.getValues();

    // Remplacer les URLs par des formules HYPERLINK pour rendre les liens cliquables
    for (let i = 0; i < linkValues.length; i++) {
      const url = linkValues[i][0];
      if (url && typeof url === "string" && url.startsWith("http")) {
        const cell = sheet.getRange(i + 2, linkColIndex + 1);
        // Utiliser HYPERLINK pour créer un lien cliquable avec un texte personnalisé
        // Échapper les guillemets doubles dans l'URL
        const escapedUrl = url.replace(/"/g, '""');
        cell.setFormula(`=HYPERLINK("${escapedUrl}";"Voir la page")`);
      }
    }
  }

  SpreadsheetApp.getActiveSpreadsheet().toast(
    `✅ ${posts.length} ${postTypeSlug} synchronisés !`
  );
}

/**
 * Synchronise tous les post types ciblés
 */
function syncAllTargetPostTypes() {
  const TARGET_POST_TYPES = [
    "country",
    "region",
    "departement",
    "ville",
    "game",
  ];

  TARGET_POST_TYPES.forEach((postType, idx) => {
    Logger.log(
      `Synchronisation ${idx + 1}/${TARGET_POST_TYPES.length}: ${postType}`
    );
    syncPostTypeToSheet(postType);
    Utilities.sleep(1000); // Pause entre chaque synchronisation
  });

  SpreadsheetApp.getActiveSpreadsheet().toast(
    "✅ Synchronisation complète terminée !"
  );
}

/**
 * Pousse les modifications d'une feuille vers WordPress avec gestion des relations
 */
function pushPostTypeToWordPress(postTypeSlug) {
  const auth = basicAuth();
  const ss = SpreadsheetApp.getActiveSpreadsheet();

  const sheetName =
    postTypeSlug.charAt(0).toUpperCase() + postTypeSlug.slice(1);
  const sheet = ss.getSheetByName(sheetName);

  if (!sheet) {
    SpreadsheetApp.getActiveSpreadsheet().toast(
      `❌ Feuille "${sheetName}" introuvable`
    );
    return;
  }

  const rows = sheet.getDataRange().getValues();
  if (rows.length < 2) {
    SpreadsheetApp.getActiveSpreadsheet().toast(
      `❌ Aucune donnée dans la feuille "${sheetName}"`
    );
    return;
  }

  const headers = rows[0];
  const dataRows = rows.slice(1);

  const baseCols = ["id", "post_title", "status", "link"]; // Ajouter "link" aux colonnes de base à ignorer
  const acfFields = headers.filter((h) => !baseCols.includes(h));

  let successCount = 0;
  let errorCount = 0;

  dataRows.forEach((row, index) => {
    const record = Object.fromEntries(headers.map((h, j) => [h, row[j]]));

    if (!record.post_title) {
      Logger.log(`Ligne ${index + 2}: Ignorée (pas de titre)`);
      return;
    }

    try {
      // Préparer les champs ACF
      const fields = {};
      acfFields.forEach((fieldName) => {
        const value = record[fieldName];

        // Si c'est une relation, convertir les noms en IDs (même si vide pour supprimer)
        if (RELATIONS_CONFIG[postTypeSlug]?.[fieldName]) {
          const relationConfig = RELATIONS_CONFIG[postTypeSlug][fieldName];
          const ids = convertRelationNamesToIds(
            postTypeSlug,
            fieldName,
            value,
            auth
          );

          // Gérer les relations uniques (multiple: false) et multiples (multiple: true)
          if (relationConfig.multiple) {
            // Relation multiple : ids est un tableau
            if (Array.isArray(ids) && ids.length > 0) {
              fields[fieldName] = ids;
              Logger.log(
                `  Relation ${fieldName}: "${value}" → [${ids.join(", ")}]`
              );
            } else if (value === "" || value == null) {
              // Permettre de supprimer une relation multiple en envoyant un tableau vide
              fields[fieldName] = [];
              Logger.log(`  Relation ${fieldName}: supprimée (tableau vide)`);
            }
          } else {
            // Relation unique : ids est un ID unique ou null
            if (ids !== null && ids !== undefined) {
              fields[fieldName] = ids;
              Logger.log(`  Relation ${fieldName}: "${value}" → ${ids}`);
            } else if (value === "" || value == null) {
              // Permettre de supprimer une relation unique en envoyant null ou 0
              fields[fieldName] = null;
              Logger.log(`  Relation ${fieldName}: supprimée (null)`);
            }
          }
        } else {
          // Pour les champs non-relation, ignorer les valeurs vides
          if (value === "" || value == null) {
            return; // Ignorer les valeurs vides
          }
          // Sinon, utiliser la valeur telle quelle
          fields[fieldName] = value;
          Logger.log(`  Champ ${fieldName}: "${value}"`);
        }
      });

      Logger.log(
        `Ligne ${index + 2}: ${
          Object.keys(fields).length
        } champs ACF préparés: ${Object.keys(fields).join(", ")}`
      );

      if (record.id) {
        // UPDATE existant
        // 1. Mettre à jour le post WordPress (titre, statut, etc.)
        const wpUrl = `${WP_URL}/wp-json/wp/v2/${postTypeSlug}/${record.id}`;
        const wpPayload = {
          title: record.post_title,
          status: record.status || "publish",
        };

        // Ajouter un délai pour éviter le rate limiting
        if (index > 0) {
          Utilities.sleep(500); // Pause de 500ms entre chaque requête
        }

        let wpRes = null;
        let wpCode = 0;
        let wpResponseText = "";
        let retryCount = 0;
        const maxRetries = 3;

        // Logique de retry pour les erreurs 403
        while (retryCount < maxRetries) {
          wpRes = UrlFetchApp.fetch(wpUrl, {
            method: "POST",
            headers: {
              Authorization: auth,
              "Content-Type": "application/json",
            },
            payload: JSON.stringify(wpPayload),
            muteHttpExceptions: true,
          });

          wpCode = wpRes.getResponseCode();
          wpResponseText = wpRes.getContentText();

          if (wpCode >= 200 && wpCode < 300) {
            break; // Succès, sortir de la boucle
          } else if (wpCode === 403 && retryCount < maxRetries - 1) {
            // Erreur 403, attendre avant de réessayer
            retryCount++;
            const waitTime = retryCount * 1000; // Attendre 1s, 2s, 3s...
            Logger.log(
              `⚠️ Ligne ${
                index + 2
              }: Erreur 403 (tentative ${retryCount}/${maxRetries}), attente ${waitTime}ms avant retry...`
            );
            Utilities.sleep(waitTime);
          } else {
            break; // Autre erreur ou dernière tentative, sortir
          }
        }

        if (wpCode >= 200 && wpCode < 300) {
          Logger.log(`✅ Ligne ${index + 2}: Post WordPress mis à jour`);
        } else {
          Logger.log(
            `❌ Ligne ${
              index + 2
            }: Erreur mise à jour post (${wpCode}): ${wpResponseText.slice(
              0,
              300
            )}`
          );
          errorCount++;
          return;
        }

        // 2. Mettre à jour les champs ACF séparément via l'API ACF v3
        if (Object.keys(fields).length > 0) {
          // Attendre un peu avant de mettre à jour les champs ACF
          Utilities.sleep(300);

          let acfUpdated = false;

          // Essayer plusieurs endpoints ACF v3
          const acfEndpoints = [
            `${WP_URL}/wp-json/acf/v3/${postTypeSlug}/${record.id}`,
            `${WP_URL}/wp-json/acf/v3/posts/${record.id}`,
            `${WP_URL}/wp-json/wp/v2/${postTypeSlug}/${record.id}?acf_format=standard`,
          ];

          const acfPayload = { fields };

          for (let i = 0; i < acfEndpoints.length && !acfUpdated; i++) {
            const acfUrl = acfEndpoints[i];
            Logger.log(
              `  Tentative ${i + 1}/${
                acfEndpoints.length
              }: API ACF v3 pour post #${record.id}`
            );
            Logger.log(`  URL: ${acfUrl}`);
            Logger.log(`  Payload: ${JSON.stringify(acfPayload)}`);

            let acfRes = null;
            let acfCode = 0;
            let acfResponseText = "";
            let acfRetryCount = 0;
            const acfMaxRetries = 3;

            // Logique de retry pour les erreurs 403 sur les requêtes ACF
            while (acfRetryCount < acfMaxRetries) {
              acfRes = UrlFetchApp.fetch(acfUrl, {
                method: "POST",
                headers: {
                  Authorization: auth,
                  "Content-Type": "application/json",
                },
                payload: JSON.stringify(acfPayload),
                muteHttpExceptions: true,
              });

              acfCode = acfRes.getResponseCode();
              acfResponseText = acfRes.getContentText();

              if (acfCode >= 200 && acfCode < 300) {
                break; // Succès, sortir de la boucle
              } else if (acfCode === 403 && acfRetryCount < acfMaxRetries - 1) {
                // Erreur 403, attendre avant de réessayer
                acfRetryCount++;
                const waitTime = acfRetryCount * 1000; // Attendre 1s, 2s, 3s...
                Logger.log(
                  `  ⚠️ Erreur 403 ACF (tentative ${acfRetryCount}/${acfMaxRetries}), attente ${waitTime}ms avant retry...`
                );
                Utilities.sleep(waitTime);
              } else {
                break; // Autre erreur ou dernière tentative, sortir
              }
            }

            if (acfCode >= 200 && acfCode < 300) {
              Logger.log(
                `✅ Ligne ${index + 2}: Champs ACF mis à jour via ${acfUrl}`
              );
              acfUpdated = true;
              successCount++;

              // Vérifier que les champs sont bien sauvegardés
              try {
                const responseData = JSON.parse(acfResponseText);
                const acfInResponse =
                  responseData.acf || responseData.fields || responseData;
                const fieldsSaved = Object.keys(fields).filter(
                  (key) => acfInResponse[key] !== undefined
                );
                Logger.log(
                  `  Vérification: ${fieldsSaved.length}/${
                    Object.keys(fields).length
                  } champs confirmés dans la réponse`
                );
              } catch (e) {
                // Ignorer les erreurs de parsing pour la vérification
              }
            } else {
              Logger.log(
                `  ❌ Erreur ${acfCode} avec ${acfUrl}: ${acfResponseText.slice(
                  0,
                  300
                )}`
              );
              // Si ce n'est pas le dernier endpoint, attendre un peu avant d'essayer le suivant
              if (i < acfEndpoints.length - 1) {
                Utilities.sleep(500);
              }
            }
          }

          if (!acfUpdated) {
            // Dernière tentative : utiliser le format acf directement dans wp/v2
            Logger.log(
              `  Dernière tentative: format acf dans wp/v2 pour post #${record.id}`
            );
            const wpAcfPayload = {
              title: record.post_title,
              status: record.status || "publish",
              acf: fields,
            };

            const wpAcfRes = UrlFetchApp.fetch(wpUrl, {
              method: "POST",
              headers: {
                Authorization: auth,
                "Content-Type": "application/json",
              },
              payload: JSON.stringify(wpAcfPayload),
              muteHttpExceptions: true,
            });

            const wpAcfCode = wpAcfRes.getResponseCode();
            const wpAcfResponseText = wpAcfRes.getContentText();

            if (wpAcfCode >= 200 && wpAcfCode < 300) {
              // Vérifier si les champs ACF sont dans la réponse
              try {
                const responseData = JSON.parse(wpAcfResponseText);
                const acfInResponse = responseData.acf || {};
                const fieldsUpdated = Object.keys(fields).filter(
                  (key) =>
                    acfInResponse[key] !== undefined &&
                    acfInResponse[key] === fields[key]
                );

                if (fieldsUpdated.length === Object.keys(fields).length) {
                  Logger.log(
                    `✅ Ligne ${
                      index + 2
                    }: Champs ACF mis à jour via wp/v2 avec format acf`
                  );
                  successCount++;
                } else {
                  Logger.log(
                    `⚠️ Ligne ${
                      index + 2
                    }: Post mis à jour mais champs ACF non confirmés (${
                      fieldsUpdated.length
                    }/${Object.keys(fields).length})`
                  );
                  Logger.log(`  Réponse: ${wpAcfResponseText.slice(0, 500)}`);
                  errorCount++;
                }
              } catch (parseErr) {
                Logger.log(
                  `⚠️ Ligne ${index + 2}: Erreur parsing réponse: ${parseErr}`
                );
                errorCount++;
              }
            } else {
              Logger.log(
                `❌ Ligne ${
                  index + 2
                }: Toutes les tentatives ACF ont échoué. Dernière erreur (${wpAcfCode}): ${wpAcfResponseText.slice(
                  0,
                  300
                )}`
              );
              errorCount++;
            }
          }
        } else {
          Logger.log(
            `✅ Ligne ${index + 2}: Post mis à jour (pas de champs ACF)`
          );
          successCount++;
        }
      } else {
        // CREATE nouveau - inclure les champs ACF directement dans le payload
        const createUrl = `${WP_URL}/wp-json/wp/v2/${postTypeSlug}`;
        const createPayload = {
          title: record.post_title,
          status: record.status || "publish",
          acf: fields, // Inclure les champs ACF directement dans le payload
        };

        Logger.log(
          `  Envoi CREATE avec ACF: ${JSON.stringify({ acf: fields })}`
        );

        const createRes = UrlFetchApp.fetch(createUrl, {
          method: "POST",
          headers: {
            Authorization: auth,
            "Content-Type": "application/json",
          },
          payload: JSON.stringify(createPayload),
          muteHttpExceptions: true,
        });

        const createCode = createRes.getResponseCode();
        const createResponseText = createRes.getContentText();

        if (createCode >= 200 && createCode < 300) {
          const newPost = JSON.parse(createResponseText);
          const newId = newPost.id;

          Logger.log(`🆕 Ligne ${index + 2}: Nouveau post créé (#${newId})`);

          // Vérifier si les champs ACF ont été créés en lisant le post
          if (Object.keys(fields).length > 0) {
            // Attendre un peu pour que WordPress traite les champs ACF
            Utilities.sleep(500);

            // Vérifier en récupérant les données ACF du post créé
            const verifyAcfUrl = `${WP_URL}/wp-json/acf/v3/${postTypeSlug}/${newId}`;
            const verifyRes = UrlFetchApp.fetch(verifyAcfUrl, {
              headers: { Authorization: auth },
              muteHttpExceptions: true,
            });

            if (verifyRes.getResponseCode() === 200) {
              const acfData = JSON.parse(verifyRes.getContentText() || "{}");
              const acfFieldsData = acfData.acf || acfData;
              const createdFields = Object.keys(acfFieldsData).filter(
                (key) => fields[key] !== undefined
              );

              if (createdFields.length === Object.keys(fields).length) {
                Logger.log(`✅ Ligne ${index + 2}: Tous les champs ACF créés`);
              } else {
                Logger.log(
                  `⚠️ Ligne ${index + 2}: Seulement ${createdFields.length}/${
                    Object.keys(fields).length
                  } champs ACF créés`
                );
              }
            }
          }

          // Mettre à jour l'ID dans la feuille
          const idColIndex = headers.indexOf("id");
          if (idColIndex >= 0) {
            sheet.getRange(index + 2, idColIndex + 1).setValue(newId);
          }

          successCount++;
        } else {
          Logger.log(
            `❌ Ligne ${
              index + 2
            }: Erreur création (${createCode}): ${createResponseText.slice(
              0,
              300
            )}`
          );
          errorCount++;
        }
      }
    } catch (err) {
      Logger.log(`⚠️ Ligne ${index + 2}: Erreur: ${err}`);
      errorCount++;
    }
  });

  SpreadsheetApp.getActiveSpreadsheet().toast(
    `✅ Synchronisation terminée ! ${successCount} réussies, ${errorCount} erreurs`
  );
}

/**
 * Pousse toutes les feuilles vers WordPress
 */
function pushAllPostTypesToWordPress() {
  const TARGET_POST_TYPES = [
    "country",
    "region",
    "departement",
    "ville",
    "game",
  ];

  TARGET_POST_TYPES.forEach((postType, idx) => {
    Logger.log(`Push ${idx + 1}/${TARGET_POST_TYPES.length}: ${postType}`);
    pushPostTypeToWordPress(postType);
    Utilities.sleep(1000); // Pause entre chaque push
  });

  SpreadsheetApp.getActiveSpreadsheet().toast("✅ Push complet terminé !");
}

/****************************
 * GESTION DES RELATIONS DANS SHEETS
 ****************************/

/**
 * Crée des listes déroulantes pour les champs de relation dans une feuille
 */
function setupRelationDropdowns(postTypeSlug) {
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  const sheetName =
    postTypeSlug.charAt(0).toUpperCase() + postTypeSlug.slice(1);
  const sheet = ss.getSheetByName(sheetName);

  if (!sheet) {
    SpreadsheetApp.getActiveSpreadsheet().toast(
      `❌ Feuille "${sheetName}" introuvable`
    );
    return;
  }

  const headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];
  const relationConfig = RELATIONS_CONFIG[postTypeSlug];

  if (!relationConfig) {
    SpreadsheetApp.getActiveSpreadsheet().toast(
      `Aucune relation configurée pour ${postTypeSlug}`
    );
    return;
  }

  // Pour chaque champ de relation
  Object.keys(relationConfig).forEach((fieldName) => {
    const config = relationConfig[fieldName];
    const targetPostType = config.target;

    // Trouver la colonne du champ
    const fieldColIndex = headers.indexOf(fieldName);
    if (fieldColIndex === -1) {
      Logger.log(`Champ ${fieldName} non trouvé dans les en-têtes`);
      return;
    }

    // Récupérer toutes les valeurs possibles depuis la feuille cible
    const targetSheetName =
      targetPostType.charAt(0).toUpperCase() + targetPostType.slice(1);
    const targetSheet = ss.getSheetByName(targetSheetName);

    if (!targetSheet) {
      Logger.log(`Feuille cible "${targetSheetName}" non trouvée`);
      return;
    }

    // Récupérer les titres depuis la colonne post_title
    const targetHeaders = targetSheet
      .getRange(1, 1, 1, targetSheet.getLastColumn())
      .getValues()[0];
    const titleColIndex = targetHeaders.indexOf("post_title");

    if (titleColIndex === -1) {
      Logger.log(`Colonne post_title non trouvée dans ${targetSheetName}`);
      return;
    }

    // Créer une référence dynamique à la colonne post_title de la feuille cible
    // Cela rend la validation dynamique : elle se met à jour automatiquement
    const targetRange = targetSheet.getRange(
      2,
      titleColIndex + 1,
      Math.max(targetSheet.getLastRow() - 1, 1000), // Utiliser une grande plage pour inclure les nouvelles lignes
      1
    );

    // Vérifier qu'il y a au moins une valeur
    const sampleValues = targetRange
      .getValues()
      .filter((row) => row[0] && row[0] !== "");
    if (sampleValues.length === 0) {
      Logger.log(`Aucun titre trouvé dans ${targetSheetName}`);
      return;
    }

    // Créer la validation de données avec référence dynamique
    // Utiliser requireValueInRange pour référencer directement la feuille cible
    const range = sheet.getRange(
      2,
      fieldColIndex + 1,
      Math.max(sheet.getLastRow(), 100),
      1
    );

    // Créer une formule de validation personnalisée qui vérifie que chaque valeur
    // (séparée par des virgules) est dans la plage cible
    // Format de la formule : =AND(ARRAYFORMULA(TRIM(SPLIT(A2,","))<>""), ...)
    // Mais Google Sheets ne supporte pas facilement les formules complexes dans les validations
    // Donc on utilise requireValueInRange pour la validation de base (dynamique)
    // et on permet les valeurs multiples avec setAllowInvalid(true)

    const targetRangeA1 = targetRange.getA1Notation();
    const targetSheetNameEscaped = `'${targetSheetName}'`; // Échapper le nom de la feuille

    // Créer une validation qui référence directement la plage de la feuille cible
    // Cela rend la validation dynamique : elle se met à jour automatiquement
    const rule = SpreadsheetApp.newDataValidation()
      .requireValueInRange(targetRange, true) // Référence dynamique à la feuille cible
      .setAllowInvalid(true) // Permet de saisir plusieurs valeurs séparées par des virgules
      .setHelpText(
        `Sélectionnez une valeur depuis ${targetSheetName} ou tapez plusieurs valeurs séparées par des virgules.\nLa liste se met à jour automatiquement quand vous synchronisez.`
      )
      .build();

    range.setDataValidation(rule);

    Logger.log(
      `✅ Validation dynamique créée pour ${fieldName} référençant ${targetSheetName}!${targetRangeA1}`
    );

    // Ajouter une note dans l'en-tête pour expliquer
    const headerCell = sheet.getRange(1, fieldColIndex + 1);
    headerCell.setNote(
      `Relation vers ${targetPostType}.\nPour plusieurs valeurs, séparez-les par des virgules.\nExemple: "Valeur1, Valeur2"`
    );

    Logger.log(
      `✅ Liste déroulante créée pour ${fieldName} avec ${sampleValues.length} options`
    );
  });

  SpreadsheetApp.getActiveSpreadsheet().toast(
    `✅ Listes déroulantes configurées pour ${postTypeSlug}`
  );
}

/**
 * Configure les listes déroulantes pour tous les post types
 */
function setupAllRelationDropdowns() {
  const TARGET_POST_TYPES = [
    "country",
    "region",
    "departement",
    "ville",
    "game",
  ];

  TARGET_POST_TYPES.forEach((postType) => {
    Logger.log(`Configuration des listes pour ${postType}`);
    setupRelationDropdowns(postType);
    Utilities.sleep(500);
  });

  SpreadsheetApp.getActiveSpreadsheet().toast(
    "✅ Toutes les listes déroulantes configurées !"
  );
}

/**
 * Met à jour les validations dynamiques après une synchronisation
 * Étend les plages pour inclure les nouvelles lignes
 */
function updateRelationDropdowns(postTypeSlug) {
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  const sheetName =
    postTypeSlug.charAt(0).toUpperCase() + postTypeSlug.slice(1);
  const sheet = ss.getSheetByName(sheetName);

  if (!sheet) return;

  const headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];
  const relationConfig = RELATIONS_CONFIG[postTypeSlug];

  if (!relationConfig) return;

  Object.keys(relationConfig).forEach((fieldName) => {
    const config = relationConfig[fieldName];
    const targetPostType = config.target;
    const fieldColIndex = headers.indexOf(fieldName);

    if (fieldColIndex === -1) return;

    const targetSheetName =
      targetPostType.charAt(0).toUpperCase() + targetPostType.slice(1);
    const targetSheet = ss.getSheetByName(targetSheetName);

    if (!targetSheet) return;

    const targetHeaders = targetSheet
      .getRange(1, 1, 1, targetSheet.getLastColumn())
      .getValues()[0];
    const titleColIndex = targetHeaders.indexOf("post_title");

    if (titleColIndex === -1) return;

    // Mettre à jour la plage cible pour inclure toutes les lignes
    const targetRange = targetSheet.getRange(
      2,
      titleColIndex + 1,
      Math.max(targetSheet.getLastRow() - 1, 1000),
      1
    );

    // Mettre à jour la plage de validation pour inclure toutes les lignes de données
    const range = sheet.getRange(
      2,
      fieldColIndex + 1,
      Math.max(sheet.getLastRow(), 100),
      1
    );

    // Recréer la validation avec la nouvelle plage
    const rule = SpreadsheetApp.newDataValidation()
      .requireValueInRange(targetRange, true)
      .setAllowInvalid(true)
      .setHelpText(
        `Sélectionnez une valeur depuis ${targetSheetName} ou tapez plusieurs valeurs séparées par des virgules.\nLa liste se met à jour automatiquement.`
      )
      .build();

    range.setDataValidation(rule);
  });

  Logger.log(`✅ Validations mises à jour pour ${postTypeSlug}`);
}

/**
 * Met à jour toutes les validations après synchronisation
 */
function updateAllRelationDropdowns() {
  const TARGET_POST_TYPES = [
    "country",
    "region",
    "departement",
    "ville",
    "game",
  ];

  TARGET_POST_TYPES.forEach((postType) => {
    updateRelationDropdowns(postType);
    Utilities.sleep(300);
  });

  SpreadsheetApp.getActiveSpreadsheet().toast(
    "✅ Toutes les validations dynamiques mises à jour !"
  );
}

/**
 * Améliore la fonction de synchronisation pour inclure les listes déroulantes
 */
function syncPostTypeToSheetWithDropdowns(postTypeSlug) {
  // D'abord synchroniser les données
  syncPostTypeToSheet(postTypeSlug);

  // Ensuite mettre à jour les validations dynamiques
  Utilities.sleep(500);
  updateRelationDropdowns(postTypeSlug);
}

/**
 * Synchronise tous les post types avec les listes déroulantes
 */
function syncAllWithDropdowns() {
  const TARGET_POST_TYPES = [
    "country",
    "region",
    "departement",
    "ville",
    "game",
  ];

  // D'abord synchroniser toutes les données
  TARGET_POST_TYPES.forEach((postType, idx) => {
    Logger.log(
      `Synchronisation ${idx + 1}/${TARGET_POST_TYPES.length}: ${postType}`
    );
    syncPostTypeToSheet(postType);
    Utilities.sleep(1000);
  });

  // Ensuite configurer/mettre à jour les validations dynamiques
  TARGET_POST_TYPES.forEach((postType, idx) => {
    Logger.log(
      `Configuration validations ${idx + 1}/${
        TARGET_POST_TYPES.length
      }: ${postType}`
    );
    // Utiliser setupRelationDropdowns la première fois, updateRelationDropdowns ensuite
    // On utilise setupRelationDropdowns qui gère les deux cas
    setupRelationDropdowns(postType);
    Utilities.sleep(500);
  });

  SpreadsheetApp.getActiveSpreadsheet().toast(
    "✅ Synchronisation complète avec validations dynamiques terminée !"
  );
}

/**
 * Valide et nettoie les valeurs de relation dans une feuille
 * Vérifie que toutes les valeurs sont valides et supprime les doublons
 */
function validateRelationValues(postTypeSlug) {
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  const sheetName =
    postTypeSlug.charAt(0).toUpperCase() + postTypeSlug.slice(1);
  const sheet = ss.getSheetByName(sheetName);

  if (!sheet) {
    SpreadsheetApp.getActiveSpreadsheet().toast(
      `❌ Feuille "${sheetName}" introuvable`
    );
    return;
  }

  const headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];
  const relationConfig = RELATIONS_CONFIG[postTypeSlug];

  if (!relationConfig) {
    return;
  }

  let correctedCount = 0;

  Object.keys(relationConfig).forEach((fieldName) => {
    const config = relationConfig[fieldName];
    const targetPostType = config.target;
    const fieldColIndex = headers.indexOf(fieldName);

    if (fieldColIndex === -1) return;

    // Récupérer les valeurs valides depuis la feuille cible
    const targetSheetName =
      targetPostType.charAt(0).toUpperCase() + targetPostType.slice(1);
    const targetSheet = ss.getSheetByName(targetSheetName);

    if (!targetSheet) return;

    const targetHeaders = targetSheet
      .getRange(1, 1, 1, targetSheet.getLastColumn())
      .getValues()[0];
    const titleColIndex = targetHeaders.indexOf("post_title");

    if (titleColIndex === -1) return;

    const validTitles = targetSheet
      .getRange(2, titleColIndex + 1, targetSheet.getLastRow() - 1, 1)
      .getValues()
      .map((row) => String(row[0]).trim())
      .filter((title) => title && title !== "");

    // Parcourir toutes les cellules de cette colonne
    const dataRange = sheet.getRange(
      2,
      fieldColIndex + 1,
      sheet.getLastRow() - 1,
      1
    );
    const values = dataRange.getValues();

    values.forEach((row, index) => {
      const cellValue = String(row[0]).trim();
      if (!cellValue) return;

      // Séparer par virgule et nettoyer
      const enteredValues = cellValue
        .split(",")
        .map((v) => v.trim())
        .filter((v) => v !== "");

      // Vérifier chaque valeur et garder seulement les valides
      const validValues = enteredValues.filter((val) =>
        validTitles.includes(val)
      );

      // Supprimer les doublons
      const uniqueValues = [...new Set(validValues)];

      // Si des valeurs ont été corrigées, mettre à jour la cellule
      if (
        uniqueValues.length !== enteredValues.length ||
        uniqueValues.join(", ") !== cellValue
      ) {
        const cell = sheet.getRange(index + 2, fieldColIndex + 1);
        if (uniqueValues.length > 0) {
          cell.setValue(uniqueValues.join(", "));
          correctedCount++;
        } else {
          cell.setValue("");
          cell.setNote("Aucune valeur valide trouvée");
        }
      }
    });
  });

  if (correctedCount > 0) {
    SpreadsheetApp.getActiveSpreadsheet().toast(
      `✅ ${correctedCount} valeurs corrigées dans ${sheetName}`
    );
  } else {
    SpreadsheetApp.getActiveSpreadsheet().toast(
      `✅ Toutes les valeurs sont valides dans ${sheetName}`
    );
  }
}

/**
 * Valide toutes les relations dans toutes les feuilles
 */
function validateAllRelations() {
  const TARGET_POST_TYPES = [
    "country",
    "region",
    "departement",
    "ville",
    "game",
  ];

  TARGET_POST_TYPES.forEach((postType) => {
    validateRelationValues(postType);
    Utilities.sleep(300);
  });

  SpreadsheetApp.getActiveSpreadsheet().toast(
    "✅ Validation de toutes les relations terminée !"
  );
}

/**
 * Fonction principale pour lancer tous les diagnostics
 */
function runFullDiagnostic() {
  SpreadsheetApp.getActiveSpreadsheet().toast("🔍 Démarrage du diagnostic...");

  discoverPostTypes();
  Utilities.sleep(500);

  discoverTaxonomies();
  Utilities.sleep(500);

  analyzeAcfRelations();
  Utilities.sleep(500);

  fetchSampleWithRelations();

  SpreadsheetApp.getActiveSpreadsheet().toast(
    "✅ Diagnostic terminé ! Vérifiez les feuilles créées."
  );
}
