
class Gate {
  /**
   * Initialize a new gate instance.
   *
   * @param  {object}  user
   * @param  {string}  authorizationProperty
   * @return {void}
   */
  constructor(user, authorizationProperty = 'authorizations') {
    this.user = user
    this.authorizationProperty = authorizationProperty
  }

  /**
   * Check if the user is super admin.
   *
   * @returns {boolean}
   */
  isSuperAdmin() {
    return Boolean(this.user.super_admin)
  }

  /**
   * Check if the user is regular user.
   *
   * @returns {boolean}
   */
  isRegularUser() {
    return !this.isSuperAdmin()
  }

  /**
   * Check whether a user can perform specific action/ability based on it's permissions.
   *
   * @param  {string} ability
   *
   * @returns {boolean}
   */
  userCan(ability) {
    if (this.before()) {
      return true
    }

    return this.user.permissions.indexOf(ability) > -1
  }

  /**
   * Check whether a user cant perform specific action/ability based on it's permissions.
   *
   * @param  {string} ability
   *
   * @returns {boolean}
   */
  userCant(ability) {
    return !this.userCan(ability)
  }

  /**
   * Before gate hook.
   *
   * @returns {boolean|null}
   */
  before() {
    return this.isSuperAdmin() ? true : null
  }

  /**
   * Determine wheter the user can perform the action on the given record.
   *
   * @param  {string} ability
   * @param  {object} record
   *
   * @returns {boolean}
   */
  allows(ability, record) {
    if (this.before()) {
      return true
    }

    if (this.user && Object.hasOwn(record, this.authorizationProperty)) {
      return record[this.authorizationProperty][ability]
    }

    return false
  }

  /**
   * Determine wheter the user can't perform the action on the given record.
   *
   * @param  {string} ability
   * @param  {object} record
   *
   * @returns {boolean}
   */
  denies(ability, record) {
    return !this.allows(ability, record)
  }
}

export default Gate

export const GatePlugin = {
  install(app, user) {
    app.config.globalProperties.$gate = new Gate(user)
  },
}
