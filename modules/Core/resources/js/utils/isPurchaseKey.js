
function isPurchaseKey(key) {
  // Valid purchase key test
  let re = new RegExp(
    '[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}',
    'i'
  )

  return re.test(key)
}

export default isPurchaseKey
