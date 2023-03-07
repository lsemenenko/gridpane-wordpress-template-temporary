<?php

namespace Breakdance\Forms\Actions;

use function Breakdance\Elements\control;

class StoreSubmission extends Action {

    /**
     * Get the displayable label of the action.
     * @return string
     */
    public static function name()
    {
        return 'Store Submission';
    }

    /**
     * Get the URL friendly slug of the action.
     * @return string
     */
    public static function slug()
    {
        return 'store_submission';
    }

    /**
     * Get controls for the builder
     * @return array
     */
    public function controls()
    {
        return [
            control('submission_title', 'Submission Title', [
                'type' => 'text',
                'layout' => 'vertical',
                'variableOptions' => [
                    'enabled' => true,
                    'populate' => [
                        'path' => 'content.form.fields',
                        'text' => 'label',
                        'value' => 'advanced.id'
                    ]
                ]
            ]),
        ];
    }

    /**
     * Does something on form submission
     * @param FormData $form
     * @param FormSettings $settings
     * @param FormExtra $extra
     * @return ActionSuccess|ActionError|array<array-key, ActionSuccess|ActionError>
     */
    public function run($form, $settings, $extra)
    {
        $id = wp_insert_post([
            'post_title'  => $this->renderData($form, $settings['actions']['store_submission']['submission_title'] ?? 'Submission'),
            'post_type'   => 'breakdance_form_res',
            'post_status' => 'unread'
        ]);

        // Flatten files array
        /** @var FormFile[] $attachments */
        $attachments = array_merge([], ...array_values($extra['files']));
        foreach ($attachments as $attachment) {
            wp_insert_attachment([
                'post_mime_type' => $attachment['type'],
                'post_title' => sanitize_file_name(basename($attachment['url'])),
                'post_content' => '',
                'post_status' => 'inherit'
            ], $attachment['file'], (int) $id);
        }

        if (is_wp_error($id)) {
            return [
                'type' => 'error',
                'message' => $id
            ];
        }

        $fieldsToAdd = [
            '_breakdance_fields'   => 'fields',
            '_breakdance_form_id' => 'formId',
            '_breakdance_post_id' => 'postId',
            '_breakdance_ip'      => 'ip',
            '_breakdance_referer' => 'referer',
            '_breakdance_user_agent' => 'userAgent',
            '_breakdance_user_id' => 'userId',
        ];

        foreach ($fieldsToAdd as $key => $value) {
            add_post_meta((int) $id, $key, $extra[$value], true);
        }

        add_post_meta((int) $id, '_breakdance_settings', $settings);

        return [
            'type' => 'success',
            'id' => $id
        ];
    }

    /**
     * @return bool
     */
    static public function proOnly(){
        return false;
    }
}
