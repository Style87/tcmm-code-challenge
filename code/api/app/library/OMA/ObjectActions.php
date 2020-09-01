<?php
namespace Phalcon\OMA;

class ObjectActions
{
  const ACTION_SELECT          = 'ActionSelect';
  const ACTION_DISTINCT        = 'ActionDistinct';
  const ACTION_EQ              = 'ActionEq';
  const ACTION_NE              = 'ActionNe';
  const ACTION_GT              = 'ActionGt';
  const ACTION_LT              = 'ActionLt';
  const ACTION_GT_EQ           = 'ActionGtEq';
  const ACTION_LT_EQ           = 'ActionLtEq';
  const ACTION_LIKE            = 'ActionLike';
  const ACTION_BETWEEN         = 'ActionBetween';
  const ACTION_IN              = 'ActionIn';
  const ACTION_NOT_IN          = 'ActionNotIn';
  const ACTION_WITHIN_BOX      = 'ActionWithinBox';
  const ACTION_IS_NULL         = 'ActionIsNull';
  const ACTION_IS_NOT_NULL     = 'ActionIsNotNull';
  const ACTION_ORDER           = 'ActionOrder';
  const ACTION_PAGE            = 'ActionPage';
  const ACTION_PER_PAGE        = 'ActionPerPage';
  const ACTION_WHERE_GROUP     = 'ActionGroup';
  const ACTION_WHERE_GROUP_END = 'ActionGroupEnd';
  const ACTION_HAVING_EQ       = 'ActionHavingEq';
  const ACTION_HAVING_NE       = 'ActionHavingNe';
  const ACTION_HAVING_GT       = 'ActionHavingGt';
  const ACTION_HAVING_LT       = 'ActionHavingLt';
  const ACTION_HAVING_GT_EQ    = 'ActionHavingGtEq';
  const ACTION_HAVING_LT_EQ    = 'ActionHavingLtEq';
  const ACTION_HAVING_BETWEEN  = 'ActionHavingBetween';
  const ACTION_HAVING_IN       = 'ActionHavingIn';
  const ACTION_HAVING_NOT_IN   = 'ActionHavingNotIn';
  const ACTION_GROUP_BY        = 'ActionGroupBy';
  const ACTION_JOIN            = 'ActionJoin';
  const ACTION_LEFT_JOIN       = 'ActionLeftJoin';
  const ACTION_RELATION        = 'ActionRelation';
  const ACTION_HAVING_MANY_IN  = 'ActionHavingManyIn';

  const ACTIONS = [
    self::ACTION_SELECT => ['select'],

    self::ACTION_DISTINCT => ['distinct'],

    self::ACTION_EQ => ['eq', 'orEq', 'where', 'whereEq', 'orWhereEq'],

    self::ACTION_NE => ['ne', 'orNe', 'whereNe', 'orWhereNe'],

    self::ACTION_GT => ['gt', 'orGt', 'whereGt', 'orWhereGt'],

    self::ACTION_LT => ['lt', 'orLt', 'whereLt', 'orWhereLt'],

    self::ACTION_GT_EQ => ['gtEq', 'orGtEq', 'whereGtEq', 'orWhereGtEq'],

    self::ACTION_LT_EQ => ['ltEq', 'orLtEq', 'whereLtEq', 'orWhereLtEq'],

    self::ACTION_LIKE => ['like', 'orLike', 'whereLike', 'orWhereLike'],

    self::ACTION_BETWEEN => ['between', 'orBetween', 'whereBetween', 'orWhereBetween'],

    self::ACTION_IN => ['in', 'orIn', 'whereIn', 'orWhereIn'],

    self::ACTION_NOT_IN => ['notIn', 'orNotIn', 'whereNotIn', 'orWhereNotIn'],

    self::ACTION_WITHIN_BOX => ['withinBox', 'orWithinBox'],

    self::ACTION_IS_NULL => ['isNull', 'orIsNull'],

    self::ACTION_IS_NOT_NULL => ['isNotNull', 'orIsNotNull'],

    self::ACTION_ORDER => ['order'],

    self::ACTION_PAGE => ['page', 'offset'],

    self::ACTION_PER_PAGE => ['perPage', 'limit'],

    self::ACTION_WHERE_GROUP => ['whereGroup', 'wsg', 'orWhereGroup', 'orWsg'],

    self::ACTION_WHERE_GROUP_END => ['whereGroupEnd', 'wge'],

    self::ACTION_HAVING_EQ => ['havingEq', 'orHavingEq'],

    self::ACTION_HAVING_NE => ['havingNe', 'orHavingNe'],

    self::ACTION_HAVING_GT => ['havingGt', 'orHavingGt'],

    self::ACTION_HAVING_LT => ['havingLt', 'orHavingLt'],

    self::ACTION_HAVING_GT_EQ => ['havingGtEq', 'orHavingGtEq'],

    self::ACTION_HAVING_LT_EQ => ['havingLtEq', 'orHavingLtEq'],

    self::ACTION_HAVING_BETWEEN => ['havingBetween', 'orHavingBetween'],

    self::ACTION_HAVING_IN => ['havingIn', 'orHavingIn'],

    self::ACTION_HAVING_NOT_IN => ['havingNotIn', 'orHavingNotIn'],

    self::ACTION_GROUP_BY => ['groupBy'],

    self::ACTION_JOIN => ['join'],

    self::ACTION_LEFT_JOIN => ['leftJoin'],

    self::ACTION_RELATION => ['relation'],

    self::ACTION_HAVING_MANY_IN => ['havingManyIn'],
  ];
}
