<?php

namespace Breakdance\Themeless\Rules;

add_action(
    'breakdance_register_template_types_and_conditions',
    '\Breakdance\Themeless\Rules\registerDateAndTimeConditions'
);

function registerDateAndTimeConditions()
{
    \Breakdance\Themeless\registerCondition(
        [
            'supports' => ['element_display', 'templating'],
            'availableForType' => ['ALL'],
            'slug' => 'day-of-week',
            'label' => 'Day Of Week (WordPress)',
            'category' => 'Date & Time',
            'operands' => [OPERAND_ONE_OF, OPERAND_NONE_OF],
            'values' => function () {
                return [
                    [
                        'label' => 'Day',
                        'items' => [
                            ['text' => 'Monday', 'value' => 'Monday'],
                            ['text' => 'Tuesday', 'value' => 'Tuesday'],
                            ['text' => 'Wednesday', 'value' => 'Wednesday'],
                            ['text' => 'Thursday', 'value' => 'Thursday'],
                            ['text' => 'Friday', 'value' => 'Friday'],
                            ['text' => 'Saturday', 'value' => 'Saturday'],
                            ['text' => 'Sunday', 'value' => 'Sunday'],
                        ]
                    ]
                ];
            },
            'callback' => /**
             * @param mixed $operand
             * @param string[] $values
             * @return bool
             */
                function ($operand, $values): bool {
                    $dayOfWeek = wp_date('l');

                    switch ($operand) {
                        case OPERAND_ONE_OF:
                            return in_array($dayOfWeek, $values);
                        case OPERAND_NONE_OF:
                            return !in_array($dayOfWeek, $values);
                        default:
                            return false;
                    }
                },
            'templatePreviewableItems' => false,
        ]
    );

    \Breakdance\Themeless\registerCondition(
        [
            'supports' => ['element_display', 'templating'],
            'availableForType' => ['ALL'],
            'slug' => 'current-time',
            'label' => 'Current Time (WordPress)',
            'category' => 'Date & Time',
            'operands' => [OPERAND_BEFORE, OPERAND_AFTER],
            'values' => function () {
                return false;
            },
            'callback' => /**
             * @param mixed $operand
             * @param string $value format is 24h  (e.g 23:25)
             * @return bool
             */
                function ($operand, $value): bool {
                    $valueTime = (new \DateTime($value, wp_timezone()));

                    $currentTime = current_datetime();
                    switch ($operand) {
                        case OPERAND_BEFORE:
                            return $currentTime<= $valueTime;
                        case OPERAND_AFTER:
                            return $currentTime >= $valueTime;
                        default:
                            return false;
                    }
                },
            'templatePreviewableItems' => false,
        ]
    );

    \Breakdance\Themeless\registerCondition(
        [
            'supports' => ['element_display', 'templating'],
            'availableForType' => ['ALL'],
            'slug' => 'current-date',
            'label' => 'Current Date (WordPress)',
            'category' => 'Date & Time',
            'operands' => [OPERAND_BEFORE, OPERAND_AFTER, OPERAND_IS, OPERAND_IS_NOT, OPERAND_ONE_OF, OPERAND_NONE_OF],
            'values' => function () {
                return false;
            },
            'callback' => /**
             * @param mixed $operand
             * @param string|string[] $value
             * @return bool
             */
                function ($operand, $value): bool {
                    if (is_string($value)){
                        $valueTime = (new \DateTime($value, wp_timezone()));

                        $currentTime = current_datetime();
                        switch ($operand){
                            case OPERAND_BEFORE:
                                return $currentTime < $valueTime;
                            case OPERAND_AFTER:
                                return $currentTime > $valueTime;
                            default:
                                return false;
                        }
                    }

                    $currentDate = wp_date('Y-m-d');

                    switch ($operand) {
                        case OPERAND_IS:
                            return isInDateRange($value);
                        case OPERAND_IS_NOT:
                            return !isInDateRange($value);
                        case OPERAND_ONE_OF:
                            return in_array($currentDate, $value);
                        case OPERAND_NONE_OF:
                            return !in_array($currentDate, $value);
                        default:
                            return false;
                    }
                },
            'templatePreviewableItems' => false,
        ]
    );

    \Breakdance\Themeless\registerCondition(
        [
            'supports' => ['element_display', 'templating'],
            'availableForType' => ['ALL'],
            'slug' => 'current-month',
            'label' => 'Current Month (WordPress)',
            'category' => 'Date & Time',
            'operands' => [OPERAND_ONE_OF, OPERAND_NONE_OF],
            'values' => function () {
                return [
                    [
                        'label' => 'Month',
                        'items' => getMonthsForDropdown()
                    ]
                ];
            },
            'allowMultiselect' => true,
            'callback' => /**
             * @param mixed $operand
             * @param string[] $value
             * @return bool
             */
                function ($operand, $value): bool {
                    $month = wp_date('n');
                    switch ($operand) {
                        case OPERAND_ONE_OF:
                            return in_array($month, $value);
                        case OPERAND_NONE_OF:
                            return !in_array($month, $value);
                        default:
                            return false;
                    }

                    return false;
                },
            'templatePreviewableItems' => false,
        ]
    );
}

/**
 * @param string[] $values
 * @return bool
 */
function isInDateRange($values){
    $currentDate = current_datetime();

    // date range
    if (count($values) === 2) {
        // destructure using null as default values
        [$fromDateValue, $toDateValue] = $values + [null, null];

        if (!$fromDateValue || !$toDateValue){
            return false;
        }

        $fromDate = (new \DateTime($fromDateValue, wp_timezone()));
        $toDate = (new \DateTime($toDateValue, wp_timezone()));
        if ($fromDate > $toDate) {
            // if the "from" date is after the "to" date,
            // reverse them before the comparison
            $fromDate = (new \DateTime($toDateValue, wp_timezone()));
            $toDate = (new \DateTime($fromDateValue, wp_timezone()));
        }

        // set time to 23:59:59 include the to date
        $toDate->setTime(23, 59,59);

        return $fromDate <= $currentDate && $currentDate <= $toDate;
    }

    // specific day
     return $currentDate->format('Y-m-d') === ($values[0] ?? null);
}


/**
 * @return DropdownData[]
 */
function getMonthsForDropdown() {
    $months = [];
    for($m = 1; $m<= 12; ++$m) {
        $monthAsTimestamp = mktime(0, 0, 0, $m, 1);
        $months[] = [
            'text' => (string) wp_date('F', $monthAsTimestamp),
            'value' => (string) wp_date('n', $monthAsTimestamp)
        ];
    }

    return $months;
}
