<?php

return [
    'general'   => [
        'label'  => 'General',
        'groups'  => [
            'website' => [
                'label' => 'Website',
                'elements'  => [
                    'title' => [
                        'label'  => 'Title'
                    ],
                    'languages'=> [
                        'label' => 'Languages',
                        'source' => 'Namespace\MySource',
                        'type' => 'multi'
                    ]
                ]
            ]
        ]
    ]
];
