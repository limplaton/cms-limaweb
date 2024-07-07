/*
    Products Plugin
*/

;(function () {
  if (typeof _cb === 'undefined') return
  function insertAfter(newNode, existingNode) {
    existingNode.parentNode.insertBefore(newNode, existingNode.nextSibling)
  }

  function createElementFromHTML(htmlString) {
    var div = document.createElement('div')
    div.innerHTML = htmlString.trim()

    // Change this to div.childNodes to support multiple top-level nodes.
    return div.firstChild
  }

  var controlButton = document.querySelector('.add-spacer')

  if (!controlButton) return

  var button = createElementFromHTML(
    `<button title="${_cb.out(
      'Add the products table'
    )}" class="quick-add-products"><span style="display:block;margin:0 0 8px;"><svg class="is-icon-flex" style="width:13px;height:13px;"><use xlink:href="#icon-code"></use></svg></use></svg></svg></span>${_cb.out(
      'Products'
    )}</button>`
  )

  button.addEventListener('click', function (e) {
    _cb.addSnippet(
      `<div class="products-section -mx-4" style="min-height:40px;"><span class="text-center w-full inline-block p-4 font-medium">${_cb.out(
        'Products will be displayed in this section on document preview and PDF.'
      )}</span></div>`,
      true,
      true
    )
  })

  insertAfter(button, controlButton)
})()
