<?php

return [
    'Locker' => [        
        'timeout' => 1000,
        'FileLocker' => [
            'dir' => sys_get_temp_dir(),
        ],
    ],
];
