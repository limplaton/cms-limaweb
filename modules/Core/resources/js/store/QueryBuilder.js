
import { getDefaultQuery } from '@/Core/components/QueryBuilder/utils'

const state = {
  // The available rules for the query builder by identifier and view
  availableRules: {},

  // The rules in the query builder by identifier and view
  queryBuilderRules: {},
}

const mutations = {
  /**
   * Set the builder available rules for identifier and view
   */
  SET_AVAILABLE_RULES(state, data) {
    if (!state.availableRules[data.identifier]) {
      state.availableRules[data.identifier] = {}
    }
    state.availableRules[data.identifier][data.view] = data.rules
  },

  /**
   * Set the query builder rules for identifier and view
   */
  SET_BUILDER_RULES(state, data) {
    if (!state.queryBuilderRules[data.identifier]) {
      state.queryBuilderRules[data.identifier] = {}
    }
    state.queryBuilderRules[data.identifier][data.view] = data.rules
  },

  /**
   * Reset the query builder rules for identifier and view
   */
  RESET_BUILDER_RULES(state, data) {
    if (!state.queryBuilderRules[data.identifier]) {
      state.queryBuilderRules[data.identifier] = {}
    }
    state.queryBuilderRules[data.identifier][data.view] = getDefaultQuery()
  },

  /**
   * Add group to the query
   */
  ADD_QUERY_GROUP(state, query) {
    query.children.push({
      type: 'group',
      query: getDefaultQuery(),
    })
  },

  /**
   * Set the query children
   */
  SET_QUERY_CHILDREN(state, data) {
    data.query.children = data.children
  },

  /**
   * Add query children
   */
  ADD_QUERY_CHILD(state, data) {
    data.query.children.push(data.child)
  },

  /**
   * Remove child from the query
   */
  REMOVE_QUERY_CHILD(state, data) {
    data.query.children.splice(data.index, 1)
  },

  /**
   * Update query rule
   */
  UPDATE_QUERY_RULE(state, data) {
    data.query.rule = data.value
  },

  /**
   * Update query value
   */
  UPDATE_QUERY_VALUE(state, data) {
    data.query.value = data.value
  },

  /**
   * Update query type
   */
  UPDATE_QUERY_TYPE(state, data) {
    data.query.type = data.value
  },

  /**
   * Update query condition
   */
  UPDATE_QUERY_CONDITION(state, data) {
    data.query.condition = data.value
  },

  /**
   * Update query operand
   */
  UPDATE_QUERY_OPERAND(state, data) {
    data.query.operand = data.value
  },

  /**
   * Update query operator
   */
  UPDATE_QUERY_OPERATOR(state, data) {
    data.query.operator = data.value
  },
}

const findNested = (rules, ruleId) => {
  let found = null

  rules.every(rule => {
    if (Object.hasOwn(rule.query, 'children') && rule.query.children) {
      found = findNested(rule.query.children, ruleId)
    } else if (rule.query.rule == ruleId) {
      found = rule
    }

    return found ? false : true
  })

  return found
}

const getters = {
  findRuleInQueryBuilder: state => (identifier, view, rule) => {
    if (
      !state.queryBuilderRules[identifier] ||
      !state.queryBuilderRules[identifier][view] ||
      !state.queryBuilderRules[identifier][view].children
    ) {
      return
    }

    return findNested(state.queryBuilderRules[identifier][view].children, rule)
  },

  /**
   * Get the identifier available builder rules for the given view
   */
  getAvailableRules: state => (identifier, view) => {
    if (
      !state.availableRules[identifier] ||
      !state.availableRules[identifier][view]
    ) {
      return []
    }

    return state.availableRules[identifier][view] || []
  },

  /**
   * Get the identifier query builder rules for the given view
   */
  getBuilderRules: state => (identifier, view) => {
    if (
      !state.queryBuilderRules[identifier] ||
      !state.queryBuilderRules[identifier][view]
    ) {
      return {}
    }

    return state.queryBuilderRules[identifier][view] || {}
  },
}

export default {
  namespaced: true,
  state,
  getters,
  mutations,
}
