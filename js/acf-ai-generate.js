/**
 * Script pour ajouter des boutons "Générer avec IA" sur les champs ACF
 * Compatible avec ACF 5.x et 6.x
 * VERSION: 1.2.0 - Avec modale de personnalisation et mise à jour TinyMCE
 */

(function ($) {
  "use strict";

  // Vérifier que les données sont disponibles
  if (typeof urbanquestAI === "undefined") {
    return;
  }

  var fieldConfig = urbanquestAI.fieldConfig || {};
  var i18n = urbanquestAI.i18n || {};

  /**
   * Ajouter le bouton IA à un champ ACF
   */
  function addAIGenerateButton(field) {
    // Récupérer la clé du champ (essayer plusieurs méthodes)
    var fieldKey =
      field.attr("data-key") ||
      field.attr("data-name") ||
      field.data("key") ||
      field.data("name");

    // Si pas de clé trouvée, essayer de la récupérer depuis l'input
    if (!fieldKey) {
      var input = field.find("input[data-key], textarea[data-key]").first();
      if (input.length) {
        fieldKey = input.attr("data-key") || input.data("key");
      }
    }

    // Si toujours pas de clé, essayer avec ACF API
    if (!fieldKey && typeof acf !== "undefined") {
      var acfField = acf.getField(field);
      if (acfField && acfField.get("key")) {
        fieldKey = acfField.get("key");
      }
    }

    if (!fieldKey || !fieldConfig[fieldKey]) {
      return;
    }

    // Vérifier si le bouton existe déjà
    if (field.find(".urbanquest-ai-generate-btn").length > 0) {
      return;
    }

    // Trouver le label ou le wrapper du champ
    var fieldLabel = field.find(".acf-label label").first();
    var fieldWrapper = field.find(".acf-input").first();

    // Si pas de label, essayer de trouver le header du champ
    if (!fieldLabel.length) {
      fieldLabel = field.find(".acf-field-header").first();
    }

    // Créer le bouton
    var button = $("<button>", {
      type: "button",
      class: "button button-small urbanquest-ai-generate-btn",
      "data-field-key": fieldKey,
      html:
        '<span class="dashicons dashicons-admin-tools" style="margin-top: 3px;"></span> ' +
        (i18n.generate || "Générer avec IA"),
    });

    // Ajouter le bouton après le label ou au début du wrapper
    if (fieldLabel.length) {
      button.css({
        "margin-left": "10px",
        "vertical-align": "middle",
      });
      fieldLabel.after(button);
    } else if (fieldWrapper.length) {
      button.css({
        "margin-bottom": "10px",
        display: "block",
      });
      fieldWrapper.prepend(button);
    } else {
      // Fallback : ajouter au début du champ
      field.prepend(button);
    }

    // Gérer le clic sur le bouton
    button.on("click", function (e) {
      e.preventDefault();
      e.stopPropagation();
      generateText(field, fieldKey, button);
    });
  }

  /**
   * Afficher une modale pour demander des informations supplémentaires
   */
  function showCustomPromptModal(fieldKey, callback) {
    // Vérifier que jQuery est disponible
    if (typeof $ === "undefined" || typeof jQuery === "undefined") {
      console.error("jQuery n'est pas disponible!");
      alert("Erreur: jQuery n'est pas chargé");
      return;
    }

    // Créer la modale
    var modal = $("<div>", {
      class: "urbanquest-ai-modal-overlay",
      html: $("<div>", {
        class: "urbanquest-ai-modal",
        html: [
          $("<div>", {
            class: "urbanquest-ai-modal-header",
            html: [
              $("<h2>", { text: "Personnaliser la génération" }),
              $("<button>", {
                type: "button",
                class: "urbanquest-ai-modal-close",
                html: '<span class="dashicons dashicons-no"></span>',
              }),
            ],
          }),
          $("<div>", {
            class: "urbanquest-ai-modal-body",
            html: [
              $("<p>", {
                text: "Ajoutez des informations supplémentaires pour personnaliser la génération du texte :",
              }),
              $("<label>", {
                for: "urbanquest-ai-context",
                text: "Contexte ou informations supplémentaires (optionnel) :",
              }),
              $("<textarea>", {
                id: "urbanquest-ai-context",
                class: "large-text",
                rows: 4,
                placeholder:
                  "Ex: Mettre l'accent sur l'aspect historique, utiliser un ton plus formel, mentionner les monuments emblématiques...",
              }),
              $("<label>", {
                for: "urbanquest-ai-tone",
                text: "Ton souhaité (optionnel) :",
              }),
              $("<select>", {
                id: "urbanquest-ai-tone",
                html: [
                  $("<option>", { value: "", text: "Par défaut" }),
                  $("<option>", {
                    value: "enthousiaste",
                    text: "Enthousiaste",
                  }),
                  $("<option>", {
                    value: "professionnel",
                    text: "Professionnel",
                  }),
                  $("<option>", { value: "décontracté", text: "Décontracté" }),
                  $("<option>", { value: "formel", text: "Formel" }),
                  $("<option>", { value: "amical", text: "Amical" }),
                ],
              }),
              $("<label>", {
                for: "urbanquest-ai-length",
                text: "Longueur approximative (optionnel) :",
              }),
              $("<select>", {
                id: "urbanquest-ai-length",
                html: [
                  $("<option>", { value: "", text: "Par défaut" }),
                  $("<option>", {
                    value: "court",
                    text: "Court (50-100 mots)",
                  }),
                  $("<option>", {
                    value: "moyen",
                    text: "Moyen (100-200 mots)",
                  }),
                  $("<option>", {
                    value: "long",
                    text: "Long (200-300 mots)",
                  }),
                ],
              }),
            ],
          }),
          $("<div>", {
            class: "urbanquest-ai-modal-footer",
            html: [
              $("<button>", {
                type: "button",
                class: "button urbanquest-ai-modal-cancel",
                text: "Annuler",
              }),
              $("<button>", {
                type: "button",
                class: "button button-primary urbanquest-ai-modal-generate",
                text: "Générer",
              }),
            ],
          }),
        ],
      }),
    });

    // Ajouter la modale au body
    $("body").append(modal);

    // Gérer la fermeture
    modal
      .find(".urbanquest-ai-modal-close, .urbanquest-ai-modal-cancel")
      .on("click", function (e) {
        e.preventDefault();
        e.stopPropagation();
        modal.remove();
        $(document).off("keydown.urbanquest-ai-modal");
      });

    // Gérer la génération
    modal.find(".urbanquest-ai-modal-generate").on("click", function (e) {
      e.preventDefault();
      e.stopPropagation();

      var context = $("#urbanquest-ai-context").val().trim();
      var tone = $("#urbanquest-ai-tone").val();
      var length = $("#urbanquest-ai-length").val();

      var customData = {};
      if (context) customData.context = context;
      if (tone) customData.tone = tone;
      if (length) customData.length = length;

      modal.remove();
      $(document).off("keydown.urbanquest-ai-modal");

      if (typeof callback === "function") {
        callback(customData);
      }
    });

    // Fermer avec Escape
    $(document).on("keydown.urbanquest-ai-modal", function (e) {
      if (e.keyCode === 27) {
        modal.remove();
        $(document).off("keydown.urbanquest-ai-modal");
      }
    });

    // Empêcher la fermeture en cliquant sur l'overlay (optionnel - décommenter si souhaité)
    // modal.on("click", function(e) {
    //   if (e.target === modal[0]) {
    //     modal.remove();
    //     $(document).off("keydown.urbanquest-ai-modal");
    //   }
    // });

    // Focus sur le textarea
    setTimeout(function () {
      $("#urbanquest-ai-context").focus();
    }, 100);
  }

  /**
   * Générer le texte avec OpenAI
   */
  function generateText(field, fieldKey, button) {
    // Récupérer l'ID du post
    var postId = $("#post_ID").val() || 0;
    if (!postId) {
      alert("Impossible de récupérer l'ID du post");
      return;
    }

    // Afficher la modale pour demander des informations supplémentaires
    showCustomPromptModal(fieldKey, function (customData) {
      // Désactiver le bouton et afficher le loading
      button.prop("disabled", true);
      var originalText = button.html();
      button.html(
        '<span class="spinner is-active" style="float: none; margin: 0 5px 0 0;"></span> ' +
          (i18n.generating || "Génération en cours...")
      );

      // Appel AJAX
      $.ajax({
        url: urbanquestAI.ajaxUrl,
        type: "POST",
        data: {
          action: "urbanquest_generate_text",
          nonce: urbanquestAI.nonce,
          field_key: fieldKey,
          post_id: postId,
          custom_context: customData.context || "",
          custom_tone: customData.tone || "",
          custom_length: customData.length || "",
        },
        success: function (response) {
          if (response.success && response.data && response.data.text) {
            // Insérer le texte généré dans le champ
            var inserted = insertTextIntoField(field, response.data.text);

            if (inserted) {
              // Afficher un message de succès
              showNotice("success", i18n.success || "Texte généré avec succès");
            } else {
              showNotice(
                "error",
                "Texte généré mais impossible de l'insérer dans le champ. Vérifiez la console pour plus de détails."
              );
            }
          } else {
            // Afficher l'erreur
            var errorMsg =
              (response.data && response.data.message) ||
              i18n.error ||
              "Erreur lors de la génération";
            showNotice("error", errorMsg);
          }
        },
        error: function (xhr, status, error) {
          showNotice(
            "error",
            i18n.error || "Erreur lors de la génération: " + error
          );
        },
        complete: function () {
          // Réactiver le bouton
          button.prop("disabled", false);
          button.html(originalText);
        },
      });
    });
  }

  /**
   * Insérer le texte dans le champ ACF
   * @returns {boolean} true si l'insertion a réussi, false sinon
   */
  function insertTextIntoField(field, text) {
    // Détecter le type de champ
    var fieldType = field.attr("data-type");
    var fieldKey = field.attr("data-key") || field.data("key");

    // Si le type n'est pas détecté, essayer de le détecter autrement
    if (!fieldType) {
      // Chercher un textarea avec TinyMCE = WYSIWYG
      var textarea = field.find("textarea").first();
      if (textarea.length) {
        var editorId = textarea.attr("id");
        if (
          editorId &&
          typeof tinyMCE !== "undefined" &&
          tinyMCE.get(editorId)
        ) {
          fieldType = "wysiwyg";
        } else if (textarea.hasClass("wp-editor-area")) {
          fieldType = "wysiwyg";
        }
      }
    }

    // Fonction helper pour mettre à jour TinyMCE pour les champs WYSIWYG
    var updateWYSIWYGField = function (field, text) {
      if (fieldType !== "wysiwyg") return;

      var textarea = field.find("textarea").first();
      var editorId = textarea.attr("id");

      // Mettre à jour le textarea directement
      textarea.val(text);

      // Fonction pour mettre à jour TinyMCE
      var updateTinyMCE = function () {
        if (editorId && typeof tinyMCE !== "undefined") {
          if (tinyMCE.get(editorId)) {
            var editor = tinyMCE.get(editorId);
            editor.setContent(text);
            editor.save();
            // Déclencher les événements
            textarea.trigger("input").trigger("change").trigger("blur");
            return true;
          }
        }
        return false;
      };

      // Essayer immédiatement
      if (!updateTinyMCE()) {
        // Si TinyMCE n'est pas prêt, attendre un peu
        setTimeout(function () {
          updateTinyMCE();
        }, 100);
        setTimeout(function () {
          updateTinyMCE();
        }, 300);
      }
    };

    // Essayer d'abord avec l'API ACF si disponible
    if (typeof acf !== "undefined") {
      var acfField = null;

      // Essayer plusieurs méthodes pour obtenir le champ ACF
      if (fieldKey) {
        // Essayer avec la clé du champ
        acfField = acf.getField(fieldKey);
      }
      if (!acfField) {
        // Essayer avec l'élément jQuery
        acfField = acf.getField(field);
      }
      if (!acfField && fieldKey) {
        // Essayer avec le sélecteur data-key
        var fieldByKey = $('[data-key="' + fieldKey + '"]');
        if (fieldByKey.length) {
          acfField = acf.getField(fieldByKey);
        }
      }

      if (acfField) {
        // Utiliser la méthode val() de ACF pour mettre à jour le champ
        if (typeof acfField.val === "function") {
          try {
            acfField.val(text);
            updateWYSIWYGField(field, text);
            // Déclencher les événements ACF
            acf.doAction("change", acfField);
            acf.doAction("updated_field", acfField);

            return true;
          } catch (e) {
            console.log("Erreur avec acfField.val():", e);
          }
        }
        // Essayer avec setValue si disponible
        if (typeof acfField.setValue === "function") {
          try {
            acfField.setValue(text);
            updateWYSIWYGField(field, text);
            acf.doAction("change", acfField);
            acf.doAction("updated_field", acfField);
            return true;
          } catch (e) {
            console.log("Erreur avec acfField.setValue():", e);
          }
        }
        // Essayer avec updateValue si disponible (ACF 6.x)
        if (typeof acfField.updateValue === "function") {
          try {
            acfField.val(text);
            updateWYSIWYGField(field, text);
            // Déclencher les événements ACF
            acf.doAction("updated_field", acfField);
            return true;
          } catch (e) {
            console.log("Erreur avec acfField.updateValue():", e);
          }
        }
      }
    }

    // Pour les champs WYSIWYG
    if (fieldType === "wysiwyg") {
      var textarea = field.find("textarea").first();
      var editorId = textarea.attr("id");
      var fieldName = textarea.attr("name");

      // Méthode 1: Utiliser TinyMCE si disponible
      if (editorId && typeof tinyMCE !== "undefined") {
        // Attendre que TinyMCE soit prêt
        if (tinyMCE.get(editorId)) {
          var editor = tinyMCE.get(editorId);
          // Mettre à jour le contenu
          editor.setContent(text);
          // Sauvegarder dans le textarea
          editor.save();
          // Mettre aussi la valeur dans le textarea directement
          textarea.val(text);
          // Déclencher tous les événements nécessaires
          textarea.trigger("change").trigger("input").trigger("blur");

          // Forcer la mise à jour de l'éditeur après un court délai
          setTimeout(function () {
            if (tinyMCE.get(editorId)) {
              tinyMCE.get(editorId).setContent(text);
              tinyMCE.get(editorId).save();
            }
          }, 100);
        } else {
          // TinyMCE pas encore initialisé, utiliser le textarea directement
          textarea.val(text).trigger("change").trigger("input").trigger("blur");
        }
      } else {
        // Fallback sur textarea
        textarea.val(text).trigger("change").trigger("input").trigger("blur");
      }

      // Méthode 2: Essayer aussi avec le name du champ (pour certains cas ACF)
      if (fieldName) {
        var fieldByName = $('textarea[name="' + fieldName + '"]');
        if (fieldByName.length && fieldByName.attr("id") !== editorId) {
          fieldByName.val(text).trigger("change").trigger("input");
        }
      }

      // Déclencher les événements ACF après un court délai
      setTimeout(function () {
        if (typeof acf !== "undefined") {
          var acfField = acf.getField(field);
          if (!acfField && fieldKey) {
            acfField = acf.getField(fieldKey);
          }
          if (!acfField && fieldName) {
            var fieldByName = $('textarea[name="' + fieldName + '"]').closest(
              ".acf-field"
            );
            if (fieldByName.length) {
              acfField = acf.getField(fieldByName);
            }
          }
          if (acfField) {
            acf.doAction("change", acfField);
            acf.doAction("updated_field", acfField);
          }
        }
      }, 200);

      return true;
    }
    // Pour les champs textarea
    else if (fieldType === "textarea") {
      var textarea = field.find("textarea").first();
      if (textarea.length) {
        textarea.val(text).trigger("change").trigger("input");

        // Déclencher les événements ACF
        if (typeof acf !== "undefined") {
          var acfField = acf.getField(field);
          if (acfField) {
            acf.doAction("change", acfField);
            acf.doAction("updated_field", acfField);
          }
        }
        return true;
      }
    }
    // Pour les champs text
    else if (fieldType === "text") {
      var input = field.find('input[type="text"]').first();
      if (input.length) {
        input.val(text).trigger("change").trigger("input");

        // Déclencher les événements ACF
        if (typeof acf !== "undefined") {
          var acfField = acf.getField(field);
          if (acfField) {
            acf.doAction("change", acfField);
            acf.doAction("updated_field", acfField);
          }
        }
        return true;
      }
    }
    // Pour les autres types, essayer de trouver un textarea ou input
    else {
      var textarea = field.find("textarea").first();
      if (textarea.length) {
        textarea.val(text).trigger("change").trigger("input");

        // Déclencher les événements ACF
        if (typeof acf !== "undefined") {
          var acfField = acf.getField(field);
          if (acfField) {
            acf.doAction("change", acfField);
            acf.doAction("updated_field", acfField);
          }
        }
        return true;
      } else {
        var input = field.find('input[type="text"]').first();
        if (input.length) {
          input.val(text).trigger("change").trigger("input");

          // Déclencher les événements ACF
          if (typeof acf !== "undefined") {
            var acfField = acf.getField(field);
            if (acfField) {
              acf.doAction("change", acfField);
              acf.doAction("updated_field", acfField);
            }
          }
          return true;
        }
      }
    }

    return false;
  }

  /**
   * Afficher une notice
   */
  function showNotice(type, message) {
    // Supprimer les notices existantes
    $(".urbanquest-ai-notice").remove();

    // Créer la notice
    var notice = $("<div>", {
      class: "notice notice-" + type + " is-dismissible urbanquest-ai-notice",
      style: "margin: 10px 0;",
      html: "<p>" + message + "</p>",
    });

    // Ajouter le bouton de fermeture
    var dismissBtn = $("<button>", {
      type: "button",
      class: "notice-dismiss",
      html: '<span class="screen-reader-text">Fermer</span>',
    });
    dismissBtn.on("click", function () {
      notice.fadeOut(function () {
        notice.remove();
      });
    });
    notice.append(dismissBtn);

    // Ajouter la notice après le titre de la page
    var target = $("#wpbody-content h1").first();
    if (target.length) {
      target.after(notice);
    } else {
      $("#wpbody-content").prepend(notice);
    }

    // Auto-supprimer après 5 secondes pour les succès
    if (type === "success") {
      setTimeout(function () {
        notice.fadeOut(function () {
          notice.remove();
        });
      }, 5000);
    }
  }

  /**
   * Initialiser les boutons pour tous les champs configurés
   */
  function initAIGenerateButtons() {
    // Parcourir tous les champs ACF
    $(".acf-field").each(function () {
      var field = $(this);
      addAIGenerateButton(field);
    });

    // Essayer aussi avec la structure ACF 6.x
    $("[data-type]").each(function () {
      var field = $(this);
      if (field.hasClass("acf-field") || field.closest(".acf-field").length) {
        addAIGenerateButton(
          field.closest(".acf-field").length
            ? field.closest(".acf-field")
            : field
        );
      }
    });
  }

  // Initialiser au chargement de la page
  $(document).ready(function () {
    // Attendre que ACF soit complètement chargé
    setTimeout(function () {
      initAIGenerateButtons();
    }, 500);

    // Réinitialiser quand ACF ajoute des champs dynamiquement (repeater, etc.)
    if (typeof acf !== "undefined") {
      acf.addAction("append", function ($el) {
        setTimeout(function () {
          $el.find(".acf-field").each(function () {
            addAIGenerateButton($(this));
          });
        }, 100);
      });

      acf.addAction("show_field", function (field) {
        setTimeout(function () {
          if (field.$el) {
            addAIGenerateButton(field.$el);
          }
        }, 100);
      });

      acf.addAction("ready", function () {
        setTimeout(function () {
          initAIGenerateButtons();
        }, 100);
      });
    }
  });
})(jQuery);
