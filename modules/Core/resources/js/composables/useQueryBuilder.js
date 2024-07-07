
import { computed, toValue } from 'vue'
import { useI18n } from 'vue-i18n'
import { useStore } from 'vuex'

import { isBlank } from '@/Core/utils'

import { getValuesForValidation } from '../components/QueryBuilder/utils'

export function useQueryBuilder(identifier, view) {
  const store = useStore()

  const viewName = toValue(view || identifier)

  const availableRules = computed({
    set(newValue) {
      store.commit('queryBuilder/SET_AVAILABLE_RULES', {
        identifier: toValue(identifier),
        view: viewName,
        rules: newValue,
      })
    },
    get() {
      return (
        store.getters['queryBuilder/getAvailableRules'](
          toValue(identifier),
          viewName
        ) || []
      )
    },
  })

  /**
   * Get the currently rules in the builder
   */
  const queryBuilderRules = computed({
    set(newValue) {
      store.commit('queryBuilder/SET_BUILDER_RULES', {
        identifier: toValue(identifier),
        view: viewName,
        rules: newValue,
      })
    },
    get() {
      return (
        store.getters['queryBuilder/getBuilderRules'](
          toValue(identifier),
          viewName
        ) || {}
      )
    },
  })

  /**
   * Indicates if there are any rules in the builder
   */
  const hasAnyBuilderRules = computed(
    () =>
      queryBuilderRules.value.children &&
      queryBuilderRules.value.children.length > 0
  )

  /**
   * Get the applied query builder rules values
   */
  const rulesValidationValues = computed(() =>
    getValuesForValidation(queryBuilderRules.value, availableRules.value)
  )

  /**
   * Total number of rules in the query builder
   * Checks are performed based on the values that exists
   */
  const totalValidRules = computed(() => rulesValidationValues.value.length)

  /**
   * Indicates if there are rules applied in the query builder
   */
  const hasRulesApplied = computed(() => {
    // If there is values, this means that there is at least one rule added in the filter
    return totalValidRules.value > 0
  })

  /**
   * Indicates if the applied rules in the query builder are valid
   */
  const rulesAreValid = computed(() => {
    if (!hasRulesApplied.value) {
      return true
    }

    let totalValid = 0

    rulesValidationValues.value.forEach(value => {
      if (!isBlank(value)) {
        totalValid++
      }
    })

    // If all rules has values, the filters are valid
    return totalValidRules.value === totalValid
  })

  /**
   * Find rule from the query builder from the given rule attribute ID
   */
  function findRule(ruleId) {
    return store.getters['queryBuilder/findRuleInQueryBuilder'](
      toValue(identifier),
      viewName,
      ruleId
    )
  }

  /**
   * Reset the query builder rules
   */
  function resetQueryBuilderRules() {
    store.commit('queryBuilder/RESET_BUILDER_RULES', {
      identifier: toValue(identifier),
      view: viewName,
    })
  }

  return {
    availableRules,
    queryBuilderRules,
    hasAnyBuilderRules,
    hasRulesApplied,
    rulesAreValid,
    totalValidRules,
    findRule,
    resetQueryBuilderRules,
  }
}

export function useQueryBuilderLabels() {
  const { t } = useI18n()

  const labels = {
    operatorLabels: {
      is: t('core::filters.operators.is'),
      was: t('core::filters.operators.was'),
      equal: t('core::filters.operators.equal'),
      not_equal: t('core::filters.operators.not_equal'),
      in: t('core::filters.operators.in'),
      not_in: t('core::filters.operators.not_in'),
      less: t('core::filters.operators.less'),
      less_or_equal: t('core::filters.operators.less_or_equal'),
      greater: t('core::filters.operators.greater'),
      greater_or_equal: t('core::filters.operators.greater_or_equal'),
      between: t('core::filters.operators.between'),
      not_between: t('core::filters.operators.not_between'),
      begins_with: t('core::filters.operators.begins_with'),
      not_begins_with: t('core::filters.operators.not_begins_with'),
      contains: t('core::filters.operators.contains'),
      not_contains: t('core::filters.operators.not_contains'),
      ends_with: t('core::filters.operators.ends_with'),
      not_ends_with: t('core::filters.operators.not_ends_with'),
      is_empty: t('core::filters.operators.is_empty'),
      is_not_empty: t('core::filters.operators.is_not_empty'),
      is_null: t('core::filters.operators.is_null'),
      is_not_null: t('core::filters.operators.is_not_null'),
    },
    matchType: t('core::filters.match_type'),
    matchTypeAll: t('core::filters.match_type_all'),
    matchTypeAny: t('core::filters.match_type_any'),
    addCondition: t('core::filters.add_condition'),
    addAnotherCondition: t('core::filters.add_another_condition'),
    addGroup: t('core::filters.add_group'),
    selectRule: t('core::filters.select_rule'),
  }

  return { labels }
}
