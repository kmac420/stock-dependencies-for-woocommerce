/**
 * Get the product's SKU
 */

function wcsd_getProductSku() {
  return document.getElementById("_sku").value;
}

/**
 *
 * Get the variation's SKU
 *
 * @param {int} x
 *
 */

function wcsd_getVariationSku(x) {
  return document.getElementById(`variable_sku${x}`).value;
}

/**
 *
 * @param {string} errorCode
 *
 */

function wcsd_createErrorMessage(errorCode) {
  // create the div for the message
  var d = document.createElement("div");
  d.className = "wcsd_error_message notice is-dismissible notice-error";
  d.id = `wcsd_error_message_${errorCode}`;
  // create a paragrpah and append it to the div
  var p = document.createElement("P");
  p.className = "wcsd_error_message_p";
  p.id = "wcsd_error_message_p";
  if (errorCode == "sku-error") {
    var textNode = document.createTextNode(
      "Error: stock dependency SKU cannot be the same as the product SKU."
    );
  } else {
    var textNode = document.createTextNode("Error: unknown error.");
  }
  p.appendChild(textNode);
  d.appendChild(p);
  // create the message dismissal button and append it to the div
  var b = document.createElement("button");
  b.className = "notice-dismiss";
  b.onclick = (function(d) {
    return function() {
      d.parentNode.removeChild(d);
      return false;
    };
  })(d);
  d.appendChild(b);
  // insert the div immediately before the post (product) editing form
  const wrapElement = document.getElementsByClassName("wrap")[0];
  const productForm = document.getElementById("post");
  wrapElement.insertBefore(d, productForm);
  jQuery("html, body").animate({ scrollTop: 80 }, 666);
  return d;
}

/**
 *
 * @param {string} fieldType
 * @param {int} y
 *
 */

function wcsd_markProductStockDependencyField(fieldType, y) {
  var errorField = document.getElementById(
    `wcsd_product_stock_dependency-${y}-${fieldType}`
  );
  errorField.value = "";
  errorField.setAttribute("style", "border: 1px solid red;");
}

/**
 *
 * @param {string} fieldType
 * @param {int} x
 * @param {int} y
 *
 */

function wcsd_markVariationStockDependencyField(fieldType, x, y) {
  var errorField = document.getElementById(
    `wcsd_variation_stock_dependency-${x}-${y}-${fieldType}`
  );
  errorField.value = "";
  errorField.setAttribute("style", "border: 1px solid red;");
}

/**
 *
 * @param {int} x
 * @param {Element} productPricingElement
 *
 * Create a div to hold the product stock dependency settings
 *
 */

function wcsd_createProductSettingsElement(productPricingElement) {
  var d = document.createElement("div");
  d.className = "wcsd_product_settings show_if_simple options_group";
  d.id = "wcsd_product_settings";
  productPricingElement.appendChild(d);
  return d;
}

/**
 *
 * @param {int} x
 * @param {Element} variablePricingElement
 *
 * Create a div to hold the variation stock dependency settings
 *
 */

function wcsd_createVariationSettingsElement(x, variablePricingElement) {
  var d = document.createElement("div");
  d.className = "wcsd_variation_settings";
  d.id = "wcsd_variation_settings-" + x;
  variablePricingElement.appendChild(d);
  return d;
}

/**
 *
 * @param {int} x
 * @param {Element} productSettingsElement
 * @param {boolean} enabled
 *
 * Create a div to hold the product stock dependency setting rows
 *
 */

function wcsd_createProductStockSettings(productSettingsElement, enabled) {
  var d = document.createElement("div");
  d.className = "wcsd_product_stock_settings";
  d.id = "wcsd_product_stock_settings";
  if (!enabled) {
    d.style.display = "none";
  }
  productSettingsElement.appendChild(d);
  return d;
}

/**
 *
 * @param {int} x
 * @param {Element} variationSettingsElement
 * @param {boolean} enabled
 *
 * Create a div to hold the variation stock dependency setting rows
 *
 */

function wcsd_createVariationStockSettings(
  x,
  variationSettingsElement,
  enabled
) {
  var d = document.createElement("div");
  d.className = "wcsd_variation_stock_settings";
  d.id = "wcsd_variation_stock_settings-" + x;
  if (!enabled) {
    d.style.display = "none";
  }
  variationSettingsElement.appendChild(d);
  return d;
}

/**
 *
 * @param {array} values
 * @param {Element} productStockElement
 *
 * Create the stock dependency enabled checkbox
 *
 */

function wcsd_createProductEnabledCheckboxInput(values, productStockElement) {
  var p = document.createElement("P");
  p.className = "wcsd_product_enabled form-field show-if-simple";
  p.id = "wcsd_product_enabled";
  var checkbox = document.createElement("input");
  checkbox.type = "checkbox";
  checkbox.name = "enabled";
  checkbox.value = "enabled";
  checkbox.id = "wcsd_product_stock_dependency_enabled";
  checkbox.checked = values.enabled;
  if (values.enabled) {
    const manageStockElement = document.getElementById("_manage_stock");
    manageStockElement.checked = "checked";
    manageStockElement.readOnly = true;
    const stockQtyElement = document.getElementById("_stock");
    stockQtyElement.value = 0;
    stockQtyElement.readOnly = true;
  }
  // checkbox.onclick = wcsd_enableCheckboxClick;
  checkbox.onclick = (function(x) {
    return function() {
      var stockSettingsElement = document.getElementById(
        `wcsd_product_stock_settings`
      );
      var stockSettingsAddRowLink = document.getElementById(
        `wcsd_product_add_stock_link`
      );
      if (this.checked == true) {
        stockSettingsElement.style.display = "block";
        stockSettingsAddRowLink.style.display = "block";
        const manageStockElement = document.getElementById("_manage_stock");
        manageStockElement.checked = "checked";
        manageStockElement.readOnly = true;
        const stockQtyElement = document.getElementById("_stock");
        stockQtyElement.value = 0;
        stockQtyElement.readOnly = true;
      } else {
        stockSettingsElement.style.display = "none";
        stockSettingsAddRowLink.style.display = "none";
      }
      runOnChange = wcsd_productOnChange();
    };
  })();
  // checkbox.onchange = wcsd_productOnChange();
  var label = document.createElement("label");
  label.htmlFor = checkbox.id;
  label.appendChild(document.createTextNode("Add stock dependency"));
  p.appendChild(label);
  p.appendChild(checkbox);
  productStockElement.appendChild(p);
  return true;
}

/**
 *
 * @param {int} x
 * @param {array} values
 * @param {Element} variationStockElement
 *
 * Create the stock dependency enabled checkbox
 *
 */

function wcsd_createVariationEnabledCheckboxInput(
  x,
  values,
  variationStockElement
) {
  var p = document.createElement("P");
  p.className = "wcsd_variation_enabled form-row form-row-full options";
  p.id = "wcsd_variation_enabled-" + x;
  var checkbox = document.createElement("input");
  checkbox.type = "checkbox";
  checkbox.name = x + "-enabled";
  checkbox.value = "enabled";
  checkbox.id = "wcsd_variation_stock_dependency-" + x + "-enabled";
  checkbox.checked = values.enabled;
  if (values.enabled) {
    // get the variation checkbox (by class name and then by name as
    // there is no id associated with the checkbox input) and then set
    // the values and make readonly
    manageStockElements = document.getElementsByClassName(
      "variable_manage_stock"
    );
    for (var i = 0; i < manageStockElements.length; i++) {
      if (
        manageStockElements[i].getAttribute("name") ===
        `variable_manage_stock[${x}]`
      ) {
        manageStockElements[i].checked = "enabled";
        manageStockElements[i].readOnly = true;
      }
    }
    // get the variation stock quantity input by id and then set
    // the values and make readonly
    stockQtyElement = document.getElementById(`variable_stock${x}`);
    stockQtyElement.value = 0;
    stockQtyElement.readOnly = true;
  }
  // checkbox.onclick = wcsd_enableCheckboxClick;
  checkbox.onclick = (function(x) {
    return function() {
      var stockSettingsElement = document.getElementById(
        `wcsd_variation_stock_settings-${x}`
      );
      var stockSettingsAddRowLink = document.getElementById(
        `wcsd_variation_add_stock_link-${x}`
      );
      if (this.checked == true) {
        stockSettingsElement.style.display = "block";
        stockSettingsAddRowLink.style.display = "block";
        // get the variation checkbox (by class name and then by name as
        // there is no id associated with the checkbox input) and then set
        // the values and make readonly
        manageStockElements = document.getElementsByClassName(
          "variable_manage_stock"
        );
        for (var i = 0; i < manageStockElements.length; i++) {
          if (
            manageStockElements[i].getAttribute("name") ===
            `variable_manage_stock[${x}]`
          ) {
            manageStockElements[i].checked = "enabled";
            manageStockElements[i].readOnly = true;
          }
        }
        // get the variation stock quantity input by id and then set
        // the values and make readonly
        stockQtyElement = document.getElementById(`variable_stock${x}`);
        stockQtyElement.value = 0;
        stockQtyElement.readOnly = true;
      } else {
        stockSettingsElement.style.display = "none";
        stockSettingsAddRowLink.style.display = "none";
      }
      runOnChange = wcsd_variationOnChange(this);
    };
  })(x);
  // checkbox.onchange = wcsd_variationOnChange(this);
  var label = document.createElement("label");
  label.htmlFor = checkbox.id;
  label.appendChild(document.createTextNode("Add stock dependency"));
  p.appendChild(label);
  p.appendChild(checkbox);
  variationStockElement.appendChild(p);
  return true;
}

/**
 *
 * @param {int} y
 * @param {array} values
 * @param {Element} productStockRow
 *
 * Create the stock dependency sku field
 *
 */

function wcsd_createProductSkuTextInput(y, values, productStockRow) {
  var p = document.createElement("P");
  p.className =
    "wcsd_product_stock_dependency_p wcsd_product_stock_dependency_sku";
  p.id = "wcsd_product_stock_dependency_p-" + y + "-sku";
  var input = document.createElement("input");
  input.setAttribute("type", "text");
  input.defaultValue = values.sku;
  input.className = "wcsd_product_stock_dependency_sku";
  input.id = "wcsd_product_stock_dependency-" + y + "-sku";
  input.onchange = (function() {
    return function() {
      runOnChange = wcsd_productOnChange();
    };
  })();
  var label = document.createElement("label");
  label.htmlFor = input.id;
  label.className = "wcsd_product_stock_dependency_sku_label";
  label.appendChild(document.createTextNode("Dependency SKU"));
  p.appendChild(label);
  p.appendChild(input);
  productStockRow.appendChild(p);
  return true;
}

/**
 *
 * @param {int} x
 * @param {int} y
 * @param {array} values
 * @param {Element} variationStockRow
 *
 * Create the stock dependency sku field
 *
 */

function wcsd_createVariationSkuTextInput(x, y, values, variationStockRow) {
  var p = document.createElement("P");
  p.className =
    "form-field wcsd_variable_stock_dependency_p wcsd_variable_stock_dependency_sku form-row form-row-first";
  p.id = "wcsd_variation_stock_dependency_p-" + x + "-" + y + "-sku";
  var input = document.createElement("input");
  input.setAttribute("type", "text");
  input.defaultValue = values.sku;
  input.className = "wcsd_variation_stock_dependency_sku";
  input.id = "wcsd_variation_stock_dependency-" + x + "-" + y + "-sku";
  input.onchange = (function(x) {
    return function() {
      runOnChange = wcsd_variationOnChange(this);
    };
  })(x);
  var label = document.createElement("label");
  label.htmlFor = input.id;
  label.className = "wcsd_variation_stock_dependency_sku_label";
  label.appendChild(document.createTextNode("Dependency SKU"));
  p.appendChild(label);
  p.appendChild(input);
  variationStockRow.appendChild(p);
  return true;
}

/**
 *
 * @param {int} y
 * @param {array} values
 * @param {Element} productStockRow
 *
 * Create the stock dependency quantity field
 *
 */

function wcsd_createProductQtyTextInput(y, values, productStockRow) {
  var p = document.createElement("P");
  p.className =
    "wcsd_product_stock_dependency_p wcsd_product_stock_dependency_qty";
  p.id = "wcsd_product_stock_dependency_p-" + y + "-qty";
  var input = document.createElement("input");
  input.setAttribute("type", "number");
  input.setAttribute("min", "1");
  input.setAttribute("step", "1");
  input.defaultValue = Object.is(values.qty, undefined) ? 1 : values.qty;
  input.className = "wcsd_product_stock_dependency_qty";
  input.id = "wcsd_product_stock_dependency-" + y + "-qty";
  input.onchange = (function() {
    return function() {
      runOnChange = wcsd_productOnChange();
    };
  })();
  var label = document.createElement("label");
  label.htmlFor = input.id;
  label.className = "wcsd_product_stock_dependency_qty_label";
  label.appendChild(document.createTextNode("Qty"));
  p.appendChild(label);
  p.appendChild(input);
  productStockRow.appendChild(p);
  return true;
}

/**
 *
 * @param {int} x
 * @param {int} y
 * @param {array} values
 * @param {Element} variationStockRow
 *
 * Create the stock dependency quantity field
 *
 */

function wcsd_createVariationQtyTextInput(x, y, values, variationStockRow) {
  var p = document.createElement("P");
  p.className =
    "form-field wcsd_variable_stock_dependency_p wcsd_variable_stock_dependency_qty form-row form-row-last";
  p.id = "wcsd_variation_stock_dependency_p-" + x + "-" + y + "-qty";
  var input = document.createElement("input");
  input.setAttribute("type", "number");
  input.setAttribute("min", "1");
  input.setAttribute("step", "1");
  input.defaultValue = Object.is(values.qty, undefined) ? 1 : values.qty;
  input.className = "wcsd_variation_stock_dependency_qty";
  input.id = "wcsd_variation_stock_dependency-" + x + "-" + y + "-qty";
  input.onchange = (function(x) {
    return function() {
      runOnChange = wcsd_variationOnChange(this);
    };
  })(x);
  var label = document.createElement("label");
  label.htmlFor = input.id;
  label.className = "wcsd_variation_stock_dependency_qty_label";
  label.appendChild(document.createTextNode("Qty"));
  p.appendChild(label);
  p.appendChild(input);
  variationStockRow.appendChild(p);
  return true;
}

/**
 *
 * @param {*} productStockRow
 *
 * Remove the product stock dependency row
 *
 */
function wcsd_productRemoveStockDependency(productStockRow) {
  productStockRow.parentNode.removeChild(productStockRow);
  return true;
}

/**
 *
 * @param {*} variationStockRow
 *
 * Remove the variation stock dependency row
 *
 */
function wcsd_variationRemoveStockDependency(variationStockRow) {
  jQuery(variationStockRow)
    .closest(".woocommerce_variation")
    .addClass("variation-needs-update");
  jQuery(
    "button.cancel-variation-changes, button.save-variation-changes"
  ).removeAttr("disabled");
  jQuery("#variable_product_options").trigger(
    "woocommerce_variations_input_changed"
  );
  variationStockRow.parentNode.removeChild(variationStockRow);
  return true;
}

/**
 *
 * @param {*} y
 * @param {*} productStockRow
 *
 * Create a link that can be used to remove the stock dependency row
 * and add it to the end of the product stock dependency row
 *
 */

function wcsd_productAddRemoveStockLink(y, productStockRow) {
  var p = document.createElement("P");
  p.className = "wcsd_product_remove_stock_link_p form-row";
  p.id = "wcsd_product_remove_stock_link-" + y;
  var a = document.createElement("a");
  var link = document.createTextNode("Remove");
  a.appendChild(link);
  a.title = "Remove stock dependency";
  a.href = "";
  a.onclick = (function() {
    return function() {
      runOnClick = wcsd_productRemoveStockDependency(productStockRow);
      updateProductStock = wcsd_productOnChange();
      return false;
    };
  })();
  p.appendChild(a);
  productStockRow.appendChild(p);
  return link;
}

/**
 *
 * @param {*} x
 * @param {*} y
 * @param {*} variationStockRow
 *
 * Create a link that can be used to remove the stock dependency row
 * and add it to the end of the variation stock dependency row
 *
 */

function wcsd_variationAddRemoveStockLink(x, y, variationStockRow) {
  var p = document.createElement("P");
  p.className = "wcsd_variation_remove_stock_link_p form-row";
  p.id = "wcsd_variation_remove_stock_link-" + x + "-" + y;
  var a = document.createElement("a");
  var link = document.createTextNode("Remove");
  a.appendChild(link);
  a.title = "Remove stock dependency";
  a.href = "";
  a.onclick = (function(x) {
    return function() {
      runOnClick = wcsd_variationRemoveStockDependency(variationStockRow);
      updateVariationStock = wcsd_variationOnChange(this);
      return false;
    };
  })(x, y);
  p.appendChild(a);
  variationStockRow.appendChild(p);
  return link;
}

/**
 *
 * @param {*} y
 * @param {*} productStockElement
 *
 * Add a new product stock row
 *
 */

function wcsd_createProductStockRow(y, productStockElement) {
  var d = document.createElement("div");
  d.className = "wcsd_product_stock_settings_row";
  d.id = `wcsd_product_stock_settings_row-${y}`;
  productStockElement.appendChild(d);
  return d;
}

/**
 *
 * @param {*} x
 * @param {*} y
 * @param {*} variationStockElement
 *
 * Add a new variation stock row
 *
 */

function wcsd_createVariationStockRow(x, y, variationStockElement) {
  var d = document.createElement("div");
  d.className = "wcsd_variation_stock_settings_row";
  d.id = `wcsd_variation_stock_settings_row-${x}-${y}`;
  variationStockElement.appendChild(d);
  return d;
}

/**
 *
 * @param {*} productStockElement
 *
 * Add a new product stock dependency row, input fields, and remove link
 *
 */

function wcsd_productAddStockDependency(productStockElement) {
  existingStockRows = Array.from(
    productStockElement.getElementsByClassName(
      "wcsd_product_stock_settings_row"
    )
  );
  var y = 0;
  if (existingStockRows.length > 0) {
    for (existingStockRow in existingStockRows) {
      rowNum = existingStockRows[existingStockRow].id.split("-")[1];
      y = Math.max(y, rowNum);
    }
    y++;
  }
  var values = { sku: "", qty: "" };
  productStockRow = wcsd_createProductStockRow(y, productStockElement);
  createEmptySkuInput = wcsd_createProductSkuTextInput(
    y,
    values,
    productStockRow
  );
  createEmptyQtyInput = wcsd_createProductQtyTextInput(
    y,
    values,
    productStockRow
  );
  createRemoveStockLink = wcsd_productAddRemoveStockLink(y, productStockRow);
  return true;
}

/**
 *
 * @param {*} x
 * @param {*} variationStockElement
 *
 * Add a new variation stock dependency row, input fields, and remove link
 *
 */

function wcsd_variationAddStockDependency(x, variationStockElement) {
  existingStockRows = Array.from(
    variationStockElement.getElementsByClassName(
      "wcsd_variation_stock_settings_row"
    )
  );
  var y = 0;
  if (existingStockRows.length > 0) {
    for (existingStockRow in existingStockRows) {
      rowNum = existingStockRows[existingStockRow].id.split("-")[2];
      y = Math.max(y, rowNum);
    }
    y++;
  }
  var values = { sku: "", qty: "" };
  variationStockRow = wcsd_createVariationStockRow(x, y, variationStockElement);
  createEmptySkuInput = wcsd_createVariationSkuTextInput(
    x,
    y,
    values,
    variationStockRow
  );
  createEmptyQtyInput = wcsd_createVariationQtyTextInput(
    x,
    y,
    values,
    variationStockRow
  );
  createRemoveStockLink = wcsd_variationAddRemoveStockLink(
    x,
    y,
    variationStockRow
  );
  return true;
}

/**
 *
 * @param {object} productSettingsElement
 * @param {object} productStockElement
 * @param {boolean} enabled
 *
 * Create a link that allows the user to add another stock dependency row
 * to the product
 *
 */

function wcsd_productAddStockDependencyLink(
  productSettingsElement,
  productStockElement,
  enabled
) {
  var p = document.createElement("P");
  p.className = "wcsd_product_add_stock_link form-row form-row-full";
  p.id = "wcsd_product_add_stock_link";
  if (!enabled) {
    p.style.display = "none";
  } else {
    p.style.display = "block";
  }
  var a = document.createElement("a");
  var link = document.createTextNode("Add stock dependency");
  a.appendChild(link);
  a.title = "Add stock dependency";
  a.href = "";
  a.onclick = (function() {
    return function() {
      runOnClick = wcsd_productAddStockDependency(productStockElement);
      return false;
    };
  })();
  p.appendChild(a);
  productSettingsElement.appendChild(p);
  return link;
}

/**
 *
 * @param {int} x
 * @param {object} variationSettingsElement
 * @param {object} variationStockElement
 * @param {boolean} enabled
 *
 * Create a link that allows the user to add another stock dependency row
 * to the variation
 *
 */

function wcsd_variationAddStockDependencyLink(
  x,
  variationSettingsElement,
  variationStockElement,
  enabled
) {
  var p = document.createElement("P");
  p.className = "wcsd_variation_add_stock_link form-row form-row-full";
  p.id = "wcsd_variation_add_stock_link-" + x;
  if (!enabled) {
    p.style.display = "none";
  } else {
    p.style.display = "block";
  }
  var a = document.createElement("a");
  var link = document.createTextNode("Add stock dependency");
  a.appendChild(link);
  a.title = "Add stock dependency";
  a.href = "";
  a.onclick = (function(x) {
    return function() {
      runOnClick = wcsd_variationAddStockDependency(x, variationStockElement);
      return false;
    };
  })(x);
  p.appendChild(a);
  variationSettingsElement.appendChild(p);
  return link;
}

/**
 *
 * Create and populate the stock dependency fields for all the variations
 * of a product from the data in the hidden input field.
 *
 */

function wcsd_createVariationSettings() {
  var stockElements = Array.from(
    document.getElementsByClassName("wcsd_variation_stock_dependency")
  );
  if (stockElements.length > 0) {
    for (x in stockElements) {
      if (stockElements[x].value) {
        values = JSON.parse(stockElements[x].value);
      } else {
        values = JSON.parse('{ "enabled": false, "stock_dependency": [ ] }');
      }
      variationSettingsElement = wcsd_createVariationSettingsElement(
        x,
        stockElements[x].parentElement
      );
      enabledCheckboxCreated = wcsd_createVariationEnabledCheckboxInput(
        x,
        values,
        variationSettingsElement
      );
      variationStockElement = wcsd_createVariationStockSettings(
        x,
        variationSettingsElement,
        values.enabled
      );
      var y = 0;
      if (stockElements[x].value) {
        values = JSON.parse(stockElements[x].value);
        for (dependency_sku in values.stock_dependency) {
          variationStockRow = wcsd_createVariationStockRow(
            x,
            y,
            variationStockElement
          );
          skuTextInputCreated = wcsd_createVariationSkuTextInput(
            x,
            y,
            values.stock_dependency[dependency_sku],
            variationStockRow
          );
          qtyTextInputCreated = wcsd_createVariationQtyTextInput(
            x,
            y,
            values.stock_dependency[dependency_sku],
            variationStockRow
          );
          removeLinkCreated = wcsd_variationAddRemoveStockLink(
            x,
            y,
            variationStockRow
          );
          y++;
        }
      } else {
        addEmptyVariationStockRow = wcsd_variationAddStockDependency(
          x,
          variationStockElement
        );
      }
      addDependencyVariationLinkCreated = wcsd_variationAddStockDependencyLink(
        x,
        variationSettingsElement,
        variationStockElement,
        values.enabled
      );
    }
  }
  return true;
}

/**
 *
 * Create and populate the stock dependency fields for the product
 * from the data in the hidden input field.
 *
 */

function wcsd_createProductSettings() {
  var stockElement = document.getElementById("wcsd_product_stock_dependency");
  var wcsdProductSettings = document.getElementById(
    "wcsd_product_stock_dependency"
  ).value;
  if (stockElement) {
    if (wcsdProductSettings) {
      values = JSON.parse(stockElement.value);
    } else {
      values = JSON.parse('{ "enabled": false, "stock_dependency": [ ] }');
    }
    productSettingsElement = wcsd_createProductSettingsElement(
      stockElement.parentElement
    );
    enabledCheckboxCreated = wcsd_createProductEnabledCheckboxInput(
      values,
      productSettingsElement
    );
    productStockElement = wcsd_createProductStockSettings(
      productSettingsElement,
      values.enabled
    );
    var y = 0;
    if (stockElement.value) {
      values = JSON.parse(stockElement.value);
      for (dependency_sku in values.stock_dependency) {
        productStockRow = wcsd_createProductStockRow(y, productStockElement);
        skuTextInputCreated = wcsd_createProductSkuTextInput(
          y,
          values.stock_dependency[dependency_sku],
          productStockRow
        );
        qtyTextInputCreated = wcsd_createProductQtyTextInput(
          y,
          values.stock_dependency[dependency_sku],
          productStockRow
        );
        removeLinkCreated = wcsd_productAddRemoveStockLink(y, productStockRow);
        y++;
      }
    } else {
      addEmptyProductStockRow = wcsd_productAddStockDependency(
        productStockElement
      );
    }
    addDependencyProductLinkCreated = wcsd_productAddStockDependencyLink(
      productSettingsElement,
      productStockElement,
      values.enabled
    );
  }
  return true;
}

/**
 *
 * This function gets called when a product stock dependency field value is changed
 * for a product. The hidden field is updated with each change.
 *
 */

function wcsd_productOnChange() {
  // Update only the product that has changed
  if (document.getElementById(`wcsd_product_settings`)) {
    if (document.getElementById(`wcsd_product_stock_dependency_enabled`)) {
      // get the current checkbox value
      checkbox = document.getElementById(
        `wcsd_product_stock_dependency_enabled`
      );
      var y = 0;
      // create an empty array to start creating the stock settings
      var dependencyStock = [];
      var productSku = wcsd_getProductSku();
      productSettingsElement = document.getElementById(`wcsd_product_settings`);
      productSettingRows = Array.from(
        productSettingsElement.getElementsByClassName(
          "wcsd_product_stock_settings_row"
        )
      );
      for (productSettingRow in productSettingRows) {
        y = productSettingRows[productSettingRow].id.split("-")[1];
        var dependencySku = document.getElementById(
          `wcsd_product_stock_dependency-${y}-sku`
        );
        if (productSku == dependencySku.value) {
          errorResult = wcsd_createErrorMessage("sku-error");
          fieldResult = wcsd_markProductStockDependencyField("sku", y);
        } else {
          dependencySku.setAttribute("style", "border: 1px solid #7e8993");
        }
        var dependencyQty = document.getElementById(
          `wcsd_product_stock_dependency-${y}-qty`
        );
        // add the individual stock dependency sku and qty to the array
        dependencyStock.push({
          sku: dependencySku.value,
          qty: dependencyQty.value
        });
      }
      // create the settings object that will be written to the hidden input
      productSettings = {
        enabled: checkbox.checked,
        stock_dependency: dependencyStock
      };
      // update the hidden field with the stock dependency settings in JSON format
      document.getElementById(
        `wcsd_product_stock_dependency`
      ).value = JSON.stringify(productSettings);
    } else {
      // The checkbox isn't in the DOM yet
      return false;
    }
  } else {
    // The settings div isn't in the DOM yet
    return false;
  }
  return true;
}

/**
 *
 * @param {object} changedObject
 *
 * This function gets called when a stock dependency field value is changed
 * for a product variation. The hidden field is updated with each change.
 *
 */

function wcsd_variationOnChange(changedObject) {
  // Update only the variation that has changed
  var x = changedObject.parentElement.id.split("-")[1];
  if (document.getElementById(`wcsd_variation_settings-${x}`)) {
    if (
      document.getElementById(`wcsd_variation_stock_dependency-${x}-enabled`)
    ) {
      // get the current checkbox value
      checkbox = document.getElementById(
        `wcsd_variation_stock_dependency-${x}-enabled`
      );
      var y = 0;
      // create an empty array to start creating the stock settings
      var dependencyStock = [];
      var variationSku = wcsd_getVariationSku(x);
      variationSettingsElement = document.getElementById(
        `wcsd_variation_settings-${x}`
      );
      variationSettingRows = Array.from(
        variationSettingsElement.getElementsByClassName(
          "wcsd_variation_stock_settings_row"
        )
      );
      for (variationSettingRow in variationSettingRows) {
        y = variationSettingRows[variationSettingRow].id.split("-")[2];
        var dependencySku = document.getElementById(
          `wcsd_variation_stock_dependency-${x}-${y}-sku`
        );
        if (variationSku == dependencySku.value) {
          errorResult = wcsd_createErrorMessage("sku-error");
          fieldResult = wcsd_markVariationStockDependencyField("sku", x, y);
        } else {
          dependencySku.setAttribute("style", "border: 1px solid #7e8993");
        }
        var dependencyQty = document.getElementById(
          `wcsd_variation_stock_dependency-${x}-${y}-qty`
        ).value;
        // add the individual stock dependency sku and qty to the array
        dependencyStock.push({ sku: dependencySku.value, qty: dependencyQty });
      }
      // create the settings object that will be written to the hidden input
      variationSettings = {
        enabled: checkbox.checked,
        stock_dependency: dependencyStock
      };
      // update the hidden field with the stock dependency settings in JSON format
      document.getElementById(
        `wcsd_variation_stock_dependency-${x}`
      ).value = JSON.stringify(variationSettings);
    } else {
      // The checkbox isn't in the DOM yet
      return false;
    }
  } else {
    // The settings div isn't in the DOM yet
    return false;
  }
  return true;
}

// jQuery(document).on("woocommerce_variations_loaded", function(event) {
jQuery(document).ready(function($) {
  jQuery("#woocommerce-product-data").on(
    "woocommerce_variations_loaded",
    function(event) {
      console.log("WooCommerce Stock Dependency starting");
      var productTypeSelectElement = document.getElementById("product-type");
      var productTypeSelectedValue =
        productTypeSelectElement.options[productTypeSelectElement.selectedIndex]
          .value;
      // check the product type and create the appropriate input fields
      if (productTypeSelectedValue == "simple") {
        createdSettings = wcsd_createProductSettings();
      } else if (productTypeSelectedValue == "variable") {
        createdSettings = wcsd_createVariationSettings();
      }
    }
  );
});
