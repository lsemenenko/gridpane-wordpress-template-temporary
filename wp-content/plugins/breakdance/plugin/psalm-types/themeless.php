<?php

// adding comments here because Psalm is retarded and crashes with a comment.
// TemplateTypePreviewCallbackReturnType: returns false if the preview does not apply. If false it will be ignored in the logic of condition

/**
 *
 * @psalm-type TemplateRuleGroup = TemplateRule[]
 *
 * @psalm-type TemplatePreviewableItem = array{label: string, type: string, url: string}
 *
 * @psalm-type TemplateTypeSlug = string
 *
 * @psalm-type TemplateTypePreviewCallbackReturnType = TemplatePreviewableItem[]|false
 *
 *
 * @psalm-type TemplateType = array{
 * slug: TemplateTypeSlug,
 * label: string,
 * postType?: string,
 * callback: Closure():boolean,
 * templatePreviewableItems: Closure():TemplateTypePreviewCallbackReturnType,
 * defaultPriority?: int
 * }
 *
 * If TemplateType:postType is provided, it is ultimately passed to the templatePreviewableItems
 * callback on a TemplateCondition. For some conditions, knowing the postType when getting
 * the previewable items is either necessary to get the previewable items
 *
 * @psalm-type TemplateTypeCategory = array{
 * label: string,
 * types: TemplateType[]
 * }
 * @psalm-type JSTemplateType = array{slug: string,defaultPriority?: int}
 *
 * @psalm-type JSTemplateTypeCategory = array{label: string,types: JSTemplateType[]}
 *
 * @psalm-type TemplateConditionValue = array{text:string,value:string}
 * @psalm-type ItemGroup = array{label: string,items:TemplateConditionValue[],availableForType?:false|string[]}
 *
 * @psalm-type ConditionValuesCallbackReturnType = false|string[]|TemplateConditionValue[]|ItemGroup[]
 *
 * @psalm-type TemplateCondition = array{
 *   supports: ("element_display"|"templating")[],
 *   slug: string,
 *   label: string,
 *   category: string,
 *   operands: string[],
 *   valueInputType?: "dropdown",
 *   values: Closure():ConditionValuesCallbackReturnType,
 *   callback: Closure(string=,mixed=,string=):boolean,
 *   templatePreviewableItems: false|Closure(string,mixed,string):TemplatePreviewableItem[],
 *   availableForType: TemplateTypeSlug[],
 *   proOnly?: boolean,
 * }
 *
 * @psalm-type TemplateConditionWithValues = array{
 *   slug: string,
 *   label: string,
 *   operands: string[],
 *   values: ConditionValuesCallbackReturnType,
 *   callback: Closure(string,mixed):boolean,
 *   templatePreviewableItems: false|Closure(string,mixed,string):TemplatePreviewableItem[],
 *   availableForType: TemplateTypeSlug[],
 *   supports: ("element_display"|"templating")[],
 * }
 *
 * @psalm-type PopupTriggers = array{
 *   text: string,
 *   value: string,
 *   proOnly?: bool
 * }
 *
 *
 * @psalm-type TemplateRule = array{
 * operand: string,
 * ruleCategorySlug?: string,
 * ruleSlug?: string,
 * ruleDynamic?: string,
 * value?: string|string[]
 * }
 *
 * @psalm-type TemplateRuleGroup = TemplateRule[]
 *
 * @psalm-type TriggerOptions = array{
 * delay?: int,
 * percent?: int,
 * selector?: string,
 * scrollType?: string,
 * clickType?: string,
 * limit?: int
 * }
 *
 * @psalm-type Trigger = array{
 * slug: string,
 * options?: TriggerOptions
 * }
 *
 * @psalm-type PopupOptions = array{
 * showOnLoadMilliseconds?: int,
 * showOnInactivityMilliseconds?: int,
 * scrollPercent?: int,
 * scrollSelector?: string,
 * scrollLimit?: int,
 * exitIntentDelay?: int,
 * exitIntentLimit?: int,
 * clickSelector?: string,
 * clickLimit?: int,
 * onlyShowOnce?: boolean,
 * avoidMultiple?: boolean
 * }
 *
 * @psalm-type TemplateSettings = null|array{
 * parentId?: int,
 * type?: TemplateTypeSlug,
 * ruleGroups?: TemplateRuleGroup[],
 * triggers?: Trigger[],
 * priority?: int,
 * fallback?: boolean
 * }
 *
 * @psalm-type Template = array{
 * id: int,
 * settings: TemplateSettings
 * }
 *
 * @psalm-type TemplateData = array{
 * id: int,
 * title: string,
 * postType: string,
 * settings: TemplateSettings,
 * editInBreakdanceLink: string,
 * status: "publish" | "trash"
 * }
 *
 *
 * @psalm-type QueryControlParams = array{
 * active: "custom" | "text" | "php",
 * text: string,
 * php: string,
 * custom: array{
 *     source: "post_types"|"related"|"acf_relationship",
 *     includeByAuthor: boolean,
 *     includeByTaxonomies: string[]|null,
 *     postTypes: string[],
 *     conditions: TemplateRuleGroup[],
 *     orderBy: string,
 *     order: string,
 *     date:string,
 *     beforeDate:string|null,
 *     afterDate: string|null,
 *     ignoreStickyPosts: boolean,
 *     ignoreCurrentPost: boolean,
 *     offset: int|null,
 *     postsPerPage: int|null,
 *     totalPosts: int|null,
 *     acfField: string
 *   }
 * }
 *
 * @psalm-type GlobalBlock = array{label:string,id:int,tree:Tree|false, status?: "publish"|"trash"}
 *
 */
