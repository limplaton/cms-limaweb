
import cloneDeep from 'lodash/cloneDeep'
import each from 'lodash/each'
import find from 'lodash/find'

import { isBlank } from '@/Core/utils'

export function getDefaultQuery() {
  return {
    condition: 'and',
    children: [],
  }
}

export function isNullableOperator(operator) {
  return (
    ['is_empty', 'is_not_empty', 'is_null', 'is_not_null'].indexOf(operator) >=
    0
  )
}

export function isBetweenOperator(operator) {
  return ['between', 'not_between'].indexOf(operator) >= 0
}

export function needsArray(operator) {
  return ['in', 'not_in', 'between', 'not_between'].includes(operator)
}

export function getValuesForValidation(query, availableRules) {
  let vals = []

  each(query.children, rule => {
    if (rule.query.children) {
      vals = vals.concat(getValuesForValidation(rule.query))
    } else {
      let filter = find(availableRules, ['id', rule.query.rule])

      if (filter && filter.isStatic) {
        // Push true so it can trigger true rule
        // static rules are always valid as they do not receive any values
        vals.push(true)
      } else if (isNullableOperator(rule.query.operator)) {
        // Push only true so we can validate as valid rule
        vals.push(true)
      } else if (isBetweenOperator(rule.query.operator)) {
        // Validate between, from and to must be selected
        if (
          rule.query.value &&
          !isBlank(rule.query.value[0]) &&
          !isBlank(rule.query.value[1])
        ) {
          vals.push(cloneDeep(rule.query.value))
        } else {
          // Push null so it can trigger false rule
          vals.push(null)
        }
      } else {
        vals.push(cloneDeep(rule.query.value))
      }
    }
  })

  return vals
}
