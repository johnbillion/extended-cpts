<?php
class Extended_CPT_Functions_Test extends Extended_CPT_Test_Admin
{

    /**
     * @dataProvider filterOptions
     */
    public function testParseFilterOptions($test, $expected)
    {
        $admin = new Extended_CPT_Admin(new Extended_CPT('test'));
        $result = $admin->parse_filter_options($test);
//        $ecptAdminMock = $this->getMockBuilder(Extended_CPT_Admin::class)
//            ->disableOriginalConstructor()
//            ->getMock();

//        $result = $ecptAdminMock->parse_filter_options($test);

        $this->assertEquals($result, $expected);
    }

    public function filterOptions()
    {
        return [
            [
                'test' => [],
                'expected' => []
            ],
            [
                'test' => [
                    'Option One',
                    'Option Two'
                ],
                'expected' => [
                    'Option One' => 'Option One',
                    'Option Two' => 'Option Two'
                ]
            ],
            [
                'test' => [
                    1 => 'Option One',
                    2 => 'Option Two'
                ],
                'expected' => [
                    'Option One' => 'Option One',
                    'Option Two' => 'Option Two'
                ]
            ],
            [
                'test' => [
                    '1' => 'Option One',
                    '2' => 'Option Two'
                ],
                'expected' => [
                    'Option One' => 'Option One',
                    'Option Two' => 'Option Two'
                ]
            ],
            [
                'test' => [
                    [
                        'value' => 0,
                        'label' => 'Option One'
                    ],
                    [
                        'value' => 1,
                        'label' => 'Option Two'
                    ],
                ],
                'expected' => [
                    0 => 'Option One',
                    1 => 'Option Two'
                ]
            ],
            [
                'test' => [
                    [
                        'value' => 'Option One',
                    ],
                    [
                        'label' => 'Option Two'
                    ],
                ],
                'expected' => [
                    'Option One' => 'Option One',
                    'Option Two' => 'Option Two'
                ]
            ],
            [
                'test' => [
                    [
                        'Option One',
                    ],
                    [
                        'Option Two'
                    ],
                    'Option Three',
                ],
                'expected' => [
                    'Option Three' => 'Option Three',
                ]
            ],
            [
                'test' => [
                    'value' => 'Option One',
                    'label' => 'Option Two'
                ],
                'expected' => [
                    'value' => 'Option One',
                    'label' => 'Option Two'
                ]
            ],
        ];
    }
}