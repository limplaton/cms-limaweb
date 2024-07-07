<?php
 

namespace Modules\Core\App\Filters;

class Date extends Filter
{
    /**
     * Defines a filter type
     */
    public function type(): string
    {
        return 'date';
    }

    /**
     * The WAS operator options
     *
     * @return array
     */
    public function wasOperatorOptions()
    {
        return [
            'yesterday' => __('core::dates.yesterday'),
            'last_week' => __('core::dates.last_week'),
            'last_month' => __('core::dates.last_month'),
            'last_quarter' => __('core::dates.last_quarter'),
            'last_year' => __('core::dates.last_year'),
        ];
    }

    /**
     * The IS operator options
     *
     * @return array
     */
    public function isOperatorOptions()
    {
        return [
            'today' => __('core::dates.today'),
            'next_day' => __('core::dates.next_day'),
            'this_week' => __('core::dates.this_week'),
            'next_week' => __('core::dates.next_week'),
            'this_month' => __('core::dates.this_month'),
            'next_month' => __('core::dates.next_month'),
            'this_quarter' => __('core::dates.this_quarter'),
            'next_quarter' => __('core::dates.next_quarter'),
            'this_year' => __('core::dates.this_year'),
            'next_year' => __('core::dates.next_year'),
            'last_7_days' => __('core::dates.within.last_7_days'),
            'last_14_days' => __('core::dates.within.last_14_days'),
            'last_30_days' => __('core::dates.within.last_30_days'),
            'last_60_days' => __('core::dates.within.last_60_days'),
            'last_90_days' => __('core::dates.within.last_90_days'),
            'last_365_days' => __('core::dates.within.last_365_days'),
        ];
    }
}
