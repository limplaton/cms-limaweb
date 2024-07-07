
function blankProduct(defaults = {}) {
  return Object.assign(
    {
      product_id: null,
      name: null,
      description: null,
      tax_rate: null,
      tax_label: null,
      unit_price: 0,
      qty: 1,
      discount_type: null,
      discount_total: 0,
      unit: null,
      note: null,
      display_order: -1,
    },
    defaults
  )
}

function totalTaxInAmount(fromAmount, taxRate, isTaxInclusive) {
  if (isTaxInclusive) {
    // [(Unit Price) – (Unit Price / (1+ Tax %))]
    return (
      parseFloat(parseFloat(fromAmount)) -
      parseFloat(parseFloat(fromAmount)) / (1 + parseFloat(taxRate) / 100)
    )
  } else {
    // ((Unit Price) x (Tax %))
    return parseFloat(fromAmount) * (parseFloat(taxRate) / 100)
  }
}

function totalProductDiscountAmount(product, isTaxInclusive) {
  if (product.discount_type === 'fixed') {
    return parseFloat(product.discount_total)
  }

  const discountRate = parseFloat(product.discount_total)
  const unitPrice = parseFloat(product.unit_price)
  const qty = parseFloat(product.qty)

  if (isTaxInclusive) {
    // (Discount %) x (Unit Price) x Qty
    return (discountRate / 100) * unitPrice * qty
  }

  // (Discount %) x (Unit Price x Qty)
  return (discountRate / 100) * (unitPrice * qty)
}

function totalProductAmountBeforeTax(product, isTaxInclusive) {
  const unitPrice = parseFloat(product.unit_price)
  const qty = parseFloat(product.qty)
  const taxRate = parseFloat(product.tax_rate)

  if (isTaxInclusive) {
    // Qty x ((Unit Price – Discount Amount) / (1+ Tax %))
    return (
      qty *
      (parseFloat(
        unitPrice - totalProductDiscountAmount(product, isTaxInclusive)
      ) /
        (1 + taxRate / 100))
    )
  }

  // Qty x (Unit Price – Discount Amount)
  return (
    qty *
    parseFloat(unitPrice - totalProductDiscountAmount(product, isTaxInclusive))
  )
}

function totalProductTaxAmount(product, isTaxInclusive) {
  const unitPrice = parseFloat(product.unit_price)
  const qty = parseFloat(product.qty)
  const taxRate = parseFloat(product.tax_rate)

  if (isTaxInclusive) {
    // Qty x [(Unit Price – Discount Amount) – (Unit Price – Discount Amount / (1+ Tax %))]
    return (
      qty *
      (parseFloat(
        unitPrice - totalProductDiscountAmount(product, isTaxInclusive)
      ) -
        parseFloat(
          unitPrice - totalProductDiscountAmount(product, isTaxInclusive)
        ) /
        (1 + taxRate / 100))
    )
  } else {
    // Qty x ((Unit Price - Discount Amount) x (Tax %))
    return (
      qty *
      (parseFloat(
        unitPrice - totalProductDiscountAmount(product, isTaxInclusive)
      ) *
        (taxRate / 100))
    )
  }
}

function totalProductAmount(product, isTaxInclusive) {
  const taxAmount = totalProductTaxAmount(product, isTaxInclusive)

  // Tax amount + Amount before tax
  return taxAmount + totalProductAmountBeforeTax(product, isTaxInclusive)
}

function totalProductAmountWithDiscount(product, isTaxInclusive) {
  const unitPrice = parseFloat(product.unit_price)
  const qty = parseFloat(product.qty)

  return unitPrice * qty - totalProductDiscountAmount(product, isTaxInclusive)
}

export {
  blankProduct,
  totalTaxInAmount,
  totalProductAmount,
  totalProductAmountBeforeTax,
  totalProductDiscountAmount,
  totalProductTaxAmount,
  totalProductAmountWithDiscount,
}
