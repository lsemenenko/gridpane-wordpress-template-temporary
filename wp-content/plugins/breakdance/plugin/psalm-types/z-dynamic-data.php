<?php

/**
 *
 * @psalm-type DynamicFieldReturnType = string
 * @psalm-type DynamicField = array{
 * category:string,
 * slug:string,
 * label:string,
 * visibility:string,
 * controls:Control[],
 * returnTypes:DynamicFieldReturnType[],
 * handler:Closure(mixed):mixed
 * }
 *
 * @psalm-type DynamicPropertyPath = array{path:string,accepts:DynamicFieldReturnType}
 * @psalm-type DynamicDropdown = array{text: string, value: string}
 * @psalm-type ACFRule = array{param: string, operator: string, value: string}
 * @psalm-type ACFLocation = ACFRule[]
 * @psalm-type ACFGroup = array{ID: integer, location: ACFLocation[], title: string}
 * @psalm-type ACFField = array{
 *  ID: integer,
 *  key: string,
 *  prefix: string,
 *  label: string,
 *  group_id: integer,
 *  group: string,
 *  name: string,
 *  type: string,
 *  value: string,
 *  is_option_page: boolean,
 *  is_sub_field: boolean,
 *  parent_repeater?: integer,
 *  sub_fields?: array<string, string>
 * }
 * @psalm-type MetaboxField = array{
 *  id: integer,
 *  group_id: integer,
 *  group: string,
 *  name: string,
 *  type: string,
 *  value: string,
 *  is_sub_field: boolean
 * }
 * @psalm-type MetaboxGroup = object{
 *   title: string,
 *   id: string,
 *   fields: MetaboxField[]
 * }
 * @psalm-type MetaboxRegistry = object{
 *  all:callable():MetaboxGroup[]
 * }
 * @psalm-type ToolsetField = array{
 *   slug: string,
 *   name: string,
 *   group: string,
 *   post_types: string[],
 *   is_sub_field: boolean
 * }
 */
