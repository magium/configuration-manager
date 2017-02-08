<?php

return [
    'general'   => [
        'name'  => 'General',
        'groups'  => [
            'website' => [
                'name' => 'Website',
                'elements'  => [
                    'title' => [
                        'name'  => 'Title'
                    ],
                    'languages'=> [
                        'name' => 'Languages',
                        'source' => 'Namespace\MySource',
                        'type' => 'multi'
                    ]
                ]
            ]
        ]
    ]
];