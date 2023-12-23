class StockDependenciesForWooCommerce {
  /**
   * Get the product's SKU
   */

  getProductSku() {
    return document.getElementById("_sku").value;
  }

  /**
   *
   * Get the variation's SKU
   *
   * @param {int} x
   *
   */

  getVariationSku(x) {
    return document.getElementById(`variable_sku${x}`).value;
  }

  /**
   *
   * @param {string} errorCode
   *
   * Create and display an error message in WordPress admin using only
   * javascript (i.e. not a server-side call)
   *
   */

  createErrorMessage(errorCode) {
    // create the div for the message
    let d = document.createElement("div");
    d.className = "sdwc_error_message notice is-dismissible notice-error";
    d.id = `sdwc_error_message_${errorCode}`;
    // create a paragrpah and append it to the div
    let p = document.createElement("P");
    p.className = "sdwc_error_message_p";
    p.id = "sdwc_error_message_p";
    if (errorCode == "sku-error") {
      document.createTextNode(
        "Error: stock dependency SKU cannot be the same as the product SKU."
      );
    } else {
      document.createTextNode("Error: unknown error.");
    }
    p.appendChild(textNode);
    d.appendChild(p);
    // create the message dismissal button and append it to the div
    let b = document.createElement("button");
    b.className = "notice-dismiss";
    b.onclick = (function (d) {
      return function () {
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
   * Highlight the stock dependency field in error
   *
   */

  markProductStockDependencyField(fieldType, y) {
    let errorField = document.getElementById(
      `sdwc_product_stock_dependency-${y}-${fieldType}`
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
   * Highlight the stock dependency field in error
   *
   */

  markVariationStockDependencyField(fieldType, x, y) {
    let errorField = document.getElementById(
      `sdwc_variation_stock_dependency-${x}-${y}-${fieldType}`
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

  createProductSettingsElement(productPricingElement) {
    let d = document.createElement("div");
    d.className = "sdwc_product_settings show_if_simple options_group";
    d.id = "sdwc_product_settings";
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

  createVariationSettingsElement(x, variablePricingElement) {
    let d = document.createElement("div");
    d.className = "sdwc_variation_settings";
    d.id = "sdwc_variation_settings-" + x;
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

  createProductStockSettings(productSettingsElement, enabled) {
    let d = document.createElement("div");
    d.className = "sdwc_product_stock_settings";
    d.id = "sdwc_product_stock_settings";
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

  createVariationStockSettings(x, variationSettingsElement, enabled) {
    let d = document.createElement("div");
    d.className = "sdwc_variation_stock_settings";
    d.id = "sdwc_variation_stock_settings-" + x;
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

  createProductEnabledCheckboxInput(values, productStockElement) {
    let p = document.createElement("P");
    p.className = "sdwc_product_enabled form-field show-if-simple";
    p.id = "sdwc_product_enabled";
    let checkbox = document.createElement("input");
    checkbox.type = "checkbox";
    checkbox.name = "enabled";
    checkbox.value = "enabled";
    checkbox.id = "sdwc_product_stock_dependency_enabled";
    checkbox.checked = values.enabled;
    if (values.enabled) {
      const manageStockElement = document.getElementById("_manage_stock");
      manageStockElement.checked = "checked";
      manageStockElement.readOnly = true;
      const stockQtyElement = document.getElementById("_stock");
      stockQtyElement.readOnly = true;
    }
    checkbox.onclick = (function (x) {
      return function () {
        let stockSettingsElement = document.getElementById(
          `sdwc_product_stock_settings`
        );
        let stockSettingsAddRowLink = document.getElementById(
          `sdwc_product_add_stock_link`
        );
        if (this.checked == true) {
          stockSettingsElement.style.display = "block";
          stockSettingsAddRowLink.style.display = "block";
          const manageStockElement = document.getElementById("_manage_stock");
          manageStockElement.checked = "checked";
          manageStockElement.readOnly = true;
          const stockQtyElement = document.getElementById("_stock");
          stockQtyElement.readOnly = true;
        } else {
          stockSettingsElement.style.display = "none";
          stockSettingsAddRowLink.style.display = "none";
        }
        wooCommerceStockDependencies.productOnChange();
      };
    })();
    let label = document.createElement("label");
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

  createVariationEnabledCheckboxInput(x, values, variationStockElement) {
    let p = document.createElement("P");
    p.className = "sdwc_variation_enabled form-row form-row-full options";
    p.id = "sdwc_variation_enabled-" + x;
    let checkbox = document.createElement("input");
    checkbox.type = "checkbox";
    checkbox.name = x + "-enabled";
    checkbox.value = "enabled";
    checkbox.id = "sdwc_variation_stock_dependency-" + x + "-enabled";
    checkbox.checked = values.enabled;
    if (values.enabled) {
      // get the variation checkbox (by class name and then by name as
      // there is no id associated with the checkbox input) and then set
      // the values and make readonly
      let manageStockElements = document.getElementsByClassName(
        "variable_manage_stock"
      );
      for (let i = 0; i < manageStockElements.length; i++) {
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
      let stockQtyElement = document.getElementById(`variable_stock${x}`);
      stockQtyElement.readOnly = true;
    }
    // checkbox.onclick = sdwc_enableCheckboxClick;
    checkbox.onclick = (function (x) {
      return function () {
        let stockSettingsElement = document.getElementById(
          `sdwc_variation_stock_settings-${x}`
        );
        let stockSettingsAddRowLink = document.getElementById(
          `sdwc_variation_add_stock_link-${x}`
        );
        if (this.checked == true) {
          stockSettingsElement.style.display = "block";
          stockSettingsAddRowLink.style.display = "block";
          // get the variation checkbox (by class name and then by name as
          // there is no id associated with the checkbox input) and then set
          // the values and make readonly
          let manageStockElements = document.getElementsByClassName(
            "variable_manage_stock"
          );
          for (let i = 0; i < manageStockElements.length; i++) {
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
          let stockQtyElement = document.getElementById(`variable_stock${x}`);
          stockQtyElement.readOnly = true;
        } else {
          stockSettingsElement.style.display = "none";
          stockSettingsAddRowLink.style.display = "none";
        }
        wooCommerceStockDependencies.variationOnChange(this);
      };
    })(x);
    let label = document.createElement("label");
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

  createProductSkuTextInput(y, values, productStockRow) {
    let p = document.createElement("P");
    p.className =
      "sdwc_product_stock_dependency_p sdwc_product_stock_dependency_sku";
    p.id = "sdwc_product_stock_dependency_p-" + y + "-sku";
    let input = document.createElement("input");
    input.setAttribute("type", "text");
    input.defaultValue = values.sku;
    input.className = "sdwc_product_stock_dependency_sku";
    input.id = "sdwc_product_stock_dependency-" + y + "-sku";
    input.onchange = (function () {
      return function () {
        wooCommerceStockDependencies.productOnChange();
      };
    })();
    let label = document.createElement("label");
    label.htmlFor = input.id;
    label.className = "sdwc_product_stock_dependency_sku_label";
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

  createVariationSkuTextInput(x, y, values, variationStockRow) {
    let p = document.createElement("P");
    p.className =
      "form-field sdwc_variable_stock_dependency_p sdwc_variable_stock_dependency_sku form-row form-row-first";
    p.id = "sdwc_variation_stock_dependency_p-" + x + "-" + y + "-sku";
    let input = document.createElement("input");
    input.setAttribute("type", "text");
    input.defaultValue = values.sku;
    input.className = "sdwc_variation_stock_dependency_sku";
    input.id = "sdwc_variation_stock_dependency-" + x + "-" + y + "-sku";
    input.onchange = (function (x) {
      return function () {
        wooCommerceStockDependencies.variationOnChange(this);
      };
    })(x);
    let label = document.createElement("label");
    label.htmlFor = input.id;
    label.className = "sdwc_variation_stock_dependency_sku_label";
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
   * Create the stock dependency quantity field and return the paragraph
   * element containing it
   *
   */

  createProductQtyTextInput(y, values, productStockRow) {
    let p = document.createElement("P");
    p.className =
      "sdwc_product_stock_dependency_p sdwc_product_stock_dependency_qty";
    p.id = "sdwc_product_stock_dependency_p-" + y + "-qty";
    let input = document.createElement("input");
    input.setAttribute("type", "number");
    input.setAttribute("min", "1");
    input.setAttribute("step", "1");
    input.defaultValue = Object.is(values.qty, undefined) ? 1 : values.qty;
    input.className = "sdwc_product_stock_dependency_qty";
    input.id = "sdwc_product_stock_dependency-" + y + "-qty";
    input.onchange = (function () {
      return function () {
        wooCommerceStockDependencies.productOnChange();
      };
    })();
    let label = document.createElement("label");
    label.htmlFor = input.id;
    label.className = "sdwc_product_stock_dependency_qty_label";
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
   * Create the stock dependency quantity field and return the paragraph
   * element containing it
   *
   */

  createVariationQtyTextInput(x, y, values, variationStockRow) {
    let p = document.createElement("P");
    p.className =
      "form-field sdwc_variable_stock_dependency_p sdwc_variable_stock_dependency_qty form-row form-row-last";
    p.id = "sdwc_variation_stock_dependency_p-" + x + "-" + y + "-qty";
    let input = document.createElement("input");
    input.setAttribute("type", "number");
    input.setAttribute("min", "1");
    input.setAttribute("step", "1");
    input.defaultValue = Object.is(values.qty, undefined) ? 1 : values.qty;
    input.className = "sdwc_variation_stock_dependency_qty";
    input.id = "sdwc_variation_stock_dependency-" + x + "-" + y + "-qty";
    input.onchange = (function (x) {
      return function () {
        wooCommerceStockDependencies.variationOnChange(this);
      };
    })(x);
    let label = document.createElement("label");
    label.htmlFor = input.id;
    label.className = "sdwc_variation_stock_dependency_qty_label";
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

  productRemoveStockDependency(productStockRow) {
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

  variationRemoveStockDependency(variationStockRow) {
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

  productAddRemoveStockLink(y, productStockRow) {
    let p = document.createElement("P");
    p.className = "sdwc_product_remove_stock_link_p form-row";
    p.id = "sdwc_product_remove_stock_link-" + y;
    let a = document.createElement("a");
    let link = document.createTextNode("Remove");
    a.appendChild(link);
    a.title = "Remove stock dependency";
    a.href = "";
    a.onclick = (function () {
      return function () {
        wooCommerceStockDependencies.productRemoveStockDependency(
          productStockRow
        );
        wooCommerceStockDependencies.productOnChange();
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

  variationAddRemoveStockLink(x, y, variationStockRow) {
    let p = document.createElement("P");
    p.className = "sdwc_variation_remove_stock_link_p form-row";
    p.id = "sdwc_variation_remove_stock_link-" + x + "-" + y;
    let a = document.createElement("a");
    let link = document.createTextNode("Remove");
    a.appendChild(link);
    a.title = "Remove stock dependency";
    a.href = "";
    a.onclick = (function (x) {
      return function () {
        wooCommerceStockDependencies.variationRemoveStockDependency(
          variationStockRow
        );
        wooCommerceStockDependencies.variationOnChange(this);
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

  createProductStockRow(y, productStockElement) {
    let d = document.createElement("div");
    d.className = "sdwc_product_stock_settings_row";
    d.id = `sdwc_product_stock_settings_row-${y}`;
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

  createVariationStockRow(x, y, variationStockElement) {
    let d = document.createElement("div");
    d.className = "sdwc_variation_stock_settings_row";
    d.id = `sdwc_variation_stock_settings_row-${x}-${y}`;
    variationStockElement.appendChild(d);
    return d;
  }

  /**
   *
   * @param {*} productStockElement
   *
   * Add a new product stock dependency row, input fields, and 'remove' link
   *
   */

  productAddStockDependency(productStockElement) {
    let existingStockRows = Array.from(
      productStockElement.getElementsByClassName(
        "sdwc_product_stock_settings_row"
      )
    );
    let y = 0;
    if (existingStockRows.length > 0) {
      for (let i = 0; i < existingStockRows; i++) {
        rowNum = existingStockRows[i].id.split("-")[1];
        let y = Math.max(y, rowNum);
      }
      y++;
    }
    let values = { sku: "", qty: "" };
    let productStockRow = this.createProductStockRow(y, productStockElement);
    this.createProductSkuTextInput(y, values, productStockRow);
    this.createProductQtyTextInput(y, values, productStockRow);
    this.productAddRemoveStockLink(y, productStockRow);
    return true;
  }

  /**
   *
   * @param {*} x
   * @param {*} variationStockElement
   *
   * Add a new variation stock dependency row, input fields, and 'remove' link
   *
   */

  variationAddStockDependency(x, variationStockElement) {
    let existingStockRows = Array.from(
      variationStockElement.getElementsByClassName(
        "sdwc_variation_stock_settings_row"
      )
    );
    let y = 0;
    if (existingStockRows.length > 0) {
      for (let i = 0; i < existingStockRows.length; i++) {
        let rowNum = existingStockRows[i].id.split("-")[2];
        y = Math.max(y, rowNum);
      }
      y++;
    }
    let values = { sku: "", qty: "" };
    let variationStockRow = this.createVariationStockRow(
      x,
      y,
      variationStockElement
    );
    this.createVariationSkuTextInput(x, y, values, variationStockRow);
    this.createVariationQtyTextInput(x, y, values, variationStockRow);
    this.variationAddRemoveStockLink(x, y, variationStockRow);
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

  productAddStockDependencyLink(
    productSettingsElement,
    productStockElement,
    enabled
  ) {
    let p = document.createElement("P");
    p.className = "sdwc_product_add_stock_link form-row form-row-full";
    p.id = "sdwc_product_add_stock_link";
    if (!enabled) {
      p.style.display = "none";
    } else {
      p.style.display = "block";
    }
    let a = document.createElement("a");
    let link = document.createTextNode("Add stock dependency");
    a.appendChild(link);
    a.title = "Add stock dependency";
    a.href = "";
    a.onclick = (function () {
      return function () {
        wooCommerceStockDependencies.productAddStockDependency(
          productStockElement
        );
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

  variationAddStockDependencyLink(
    x,
    variationSettingsElement,
    variationStockElement,
    enabled
  ) {
    let p = document.createElement("P");
    p.className = "sdwc_variation_add_stock_link form-row form-row-full";
    p.id = "sdwc_variation_add_stock_link-" + x;
    if (!enabled) {
      p.style.display = "none";
    } else {
      p.style.display = "block";
    }
    let a = document.createElement("a");
    let link = document.createTextNode("Add stock dependency");
    a.appendChild(link);
    a.title = "Add stock dependency";
    a.href = "";
    a.onclick = (function (x) {
      return function () {
        wooCommerceStockDependencies.variationAddStockDependency(
          x,
          variationStockElement
        );
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

  createVariationSettings() {
    let stockElements = Array.from(
      document.getElementsByClassName("sdwc_variation_stock_dependency")
    );
    let values = "";
    if (stockElements.length > 0) {
      for (let x = 0; x < stockElements.length; x++) {
        if (stockElements[x].value) {
          values = JSON.parse(stockElements[x].value);
        } else {
          values = JSON.parse('{ "enabled": false, "stock_dependency": [ ] }');
        }
        let variationSettingsElement = this.createVariationSettingsElement(
          x,
          stockElements[x].parentElement
        );
        this.createVariationEnabledCheckboxInput(
          x,
          values,
          variationSettingsElement
        );
        let variationStockElement = this.createVariationStockSettings(
          x,
          variationSettingsElement,
          values.enabled
        );
        if (stockElements[x].value) {
          values = JSON.parse(stockElements[x].value);
          for (let y = 0; y < values.stock_dependency.length; y++) {
            let variationStockRow = this.createVariationStockRow(
              x,
              y,
              variationStockElement
            );
            this.createVariationSkuTextInput(
              x,
              y,
              values.stock_dependency[y],
              variationStockRow
            );
            this.createVariationQtyTextInput(
              x,
              y,
              values.stock_dependency[y],
              variationStockRow
            );
            this.variationAddRemoveStockLink(x, y, variationStockRow);
          }
        } else {
          this.variationAddStockDependency(x, variationStockElement);
        }
        this.variationAddStockDependencyLink(
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

  createProductSettings() {
    let stockElement = document.getElementById("sdwc_product_stock_dependency");
    let sdwcProductSettings = document.getElementById(
      "sdwc_product_stock_dependency"
    ).value;
    let values = "";
    if (stockElement) {
      if (sdwcProductSettings) {
        values = JSON.parse(stockElement.value);
      } else {
        values = JSON.parse('{ "enabled": false, "stock_dependency": [ ] }');
      }
      let productSettingsElement = this.createProductSettingsElement(
        stockElement.parentElement
      );
      this.createProductEnabledCheckboxInput(values, productSettingsElement);
      let productStockElement = this.createProductStockSettings(
        productSettingsElement,
        values.enabled
      );
      let y = 0;
      if (stockElement.value) {
        let values = JSON.parse(stockElement.value);
        for (let i = 0; i < values.stock_dependency.length; i++) {
          let productStockRow = this.createProductStockRow(
            y,
            productStockElement
          );
          this.createProductSkuTextInput(
            y,
            values.stock_dependency[i],
            productStockRow
          );
          this.createProductQtyTextInput(
            y,
            values.stock_dependency[i],
            productStockRow
          );
          this.productAddRemoveStockLink(y, productStockRow);
          y++;
        }
      } else {
        this.productAddStockDependency(productStockElement);
      }
      this.productAddStockDependencyLink(
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

  productOnChange() {
    // Update only the product that has changed
    if (document.getElementById(`sdwc_product_settings`)) {
      if (document.getElementById(`sdwc_product_stock_dependency_enabled`)) {
        // get the current checkbox value
        let checkbox = document.getElementById(
          `sdwc_product_stock_dependency_enabled`
        );
        let y = 0;
        // create an empty array to start creating the stock settings
        let dependencyStock = [];
        let productSku = this.getProductSku();
        let productSettingsElement = document.getElementById(
          `sdwc_product_settings`
        );
        let productSettingRows = Array.from(
          productSettingsElement.getElementsByClassName(
            "sdwc_product_stock_settings_row"
          )
        );
        for (let i = 0; i < productSettingRows.length; i++) {
          let y = productSettingRows[i].id.split("-")[1];
          let dependencySku = document.getElementById(
            `sdwc_product_stock_dependency-${y}-sku`
          );
          if (productSku == dependencySku.value) {
            this.createErrorMessage("sku-error");
            this.markProductStockDependencyField("sku", y);
          } else {
            dependencySku.setAttribute("style", "border: 1px solid #7e8993");
          }
          let dependencyQty = document.getElementById(
            `sdwc_product_stock_dependency-${y}-qty`
          );
          // add the individual stock dependency sku and qty to the array
          dependencyStock.push({
            sku: dependencySku.value,
            qty: dependencyQty.value,
          });
        }
        // create the settings object that will be written to the hidden input
        let productSettings = {
          enabled: checkbox.checked,
          stock_dependency: dependencyStock,
        };
        // update the hidden field with the stock dependency settings in JSON format
        document.getElementById(`sdwc_product_stock_dependency`).value =
          JSON.stringify(productSettings);
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

  variationOnChange(changedObject) {
    // Update only the variation that has changed
    let x = changedObject.parentElement.id.split("-")[1];
    if (document.getElementById(`sdwc_variation_settings-${x}`)) {
      if (
        document.getElementById(`sdwc_variation_stock_dependency-${x}-enabled`)
      ) {
        // get the current checkbox value
        let checkbox = document.getElementById(
          `sdwc_variation_stock_dependency-${x}-enabled`
        );
        let y = 0;
        // create an empty array to start creating the stock settings
        let dependencyStock = [];
        let variationSku = this.getVariationSku(x);
        let variationSettingsElement = document.getElementById(
          `sdwc_variation_settings-${x}`
        );
        let variationSettingRows = Array.from(
          variationSettingsElement.getElementsByClassName(
            "sdwc_variation_stock_settings_row"
          )
        );
        for (let i = 0; i < variationSettingRows.length; i++) {
          y = variationSettingRows[i].id.split("-")[2];
          let dependencySku = document.getElementById(
            `sdwc_variation_stock_dependency-${x}-${y}-sku`
          );
          if (variationSku == dependencySku.value) {
            this.createErrorMessage("sku-error");
            this.markVariationStockDependencyField("sku", x, y);
          } else {
            dependencySku.setAttribute("style", "border: 1px solid #7e8993");
          }
          let dependencyQty = document.getElementById(
            `sdwc_variation_stock_dependency-${x}-${y}-qty`
          ).value;
          // add the individual stock dependency sku and qty to the array
          dependencyStock.push({
            sku: dependencySku.value,
            qty: dependencyQty,
          });
        }
        // create the settings object that will be written to the hidden input
        let variationSettings = {
          enabled: checkbox.checked,
          stock_dependency: dependencyStock,
        };
        // update the hidden field with the stock dependency settings in JSON format
        document.getElementById(`sdwc_variation_stock_dependency-${x}`).value =
          JSON.stringify(variationSettings);
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
}

wooCommerceStockDependencies = new StockDependenciesForWooCommerce();

jQuery(document).ready(function ($) {
  console.log("WC Stock Dependency starting");
  let productTypeSelectElement = document.getElementById("product-type");
  let productTypeSelectedValue =
    productTypeSelectElement.options[productTypeSelectElement.selectedIndex]
      .value;
  // check the product type and create the appropriate input fields
  if (productTypeSelectedValue == "simple") {
    createdSettings = wooCommerceStockDependencies.createProductSettings();
  } else if (productTypeSelectedValue == "variable") {
    jQuery("#woocommerce-product-data").on(
      "woocommerce_variations_loaded",
      function (event) {
        createdSettings =
          wooCommerceStockDependencies.createVariationSettings();
      }
    );
  }
});
